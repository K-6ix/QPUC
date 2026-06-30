/**
 * server.js — QPC 1v1
 *
 * CORRECTIONS apportées :
 *  - Bug "déconnexion au début" : la partie attend que les 2 joueurs aient
 *    rejoint la page game-1v1.html (via rejoin_room) avant de lancer le
 *    countdown + la 1ère question. Plus de question perdue pendant la
 *    redirection.
 *  - Bug "revanche bloquée" : le handler rematch attend que LES DEUX
 *    joueurs cliquent, puis relance proprement countdown + startQuestion.
 *    Le timer de suppression de room (60s) est annulé si une revanche est
 *    demandée.
 *
 * npm install express socket.io
 * node server.js → port 3000
 */

const express    = require('express');
const http       = require('http');
const { Server } = require('socket.io');
const path       = require('path');
const fs         = require('fs');

// ── Config API PHP (Apache) pour persister ELO en BDD ──
// Adapter le chemin selon votre installation XAMPP
const PHP_BASE_URL = process.env.PHP_URL || 'http://localhost/qvpcPhpV1';
const SERVER_KEY   = 'qpc_server_2026';  // doit matcher save_elo.php

process.on('uncaughtException',  err => console.error('UNCAUGHT EXCEPTION:', err));
process.on('unhandledRejection', err => console.error('UNHANDLED REJECTION:', err));

const app    = express();
const server = http.createServer(app);
const io     = new Server(server, {
    cors: { origin: ['http://localhost','http://127.0.0.1','http://localhost:8080','http://localhost:8888','http://localhost:3000'], methods: ['GET','POST'] },
});

app.use(express.json());

// ══════════════════════════════════════════════════════════════
// SÉCURITÉ — Filtrage des fichiers servis en statique
// ══════════════════════════════════════════════════════════════
// Node ne sert PAS les PHP : ils sont traités par Apache sur un autre port.
// On bloque tout ce qui pourrait exposer du code source ou des secrets :
//  - .php (db.php contient les credentials BDD)
//  - .sql, .env, .log, .htaccess, package.json, server.js
//  - dossiers cachés (.git/) et node_modules/, logs/
// On laisse passer les assets publics (.css, .js, images, fonts, fichiers JSON
// publics autres que package.json).
// ══════════════════════════════════════════════════════════════
const BLOCKED_EXT = new Set([
    '.php', '.sql', '.env', '.log', '.htaccess',
    '.ini', '.conf', '.sh', '.bak'
]);
const BLOCKED_FILES = new Set([
    'server.js', 'package.json', 'package-lock.json',
    'questions.json', '.gitignore', 'readme.md'
]);
const BLOCKED_DIRS = ['node_modules', 'logs', '.git', 'uploads'];

app.use((req, res, next) => {
    // Normalisation du chemin (insensible à la casse, sans query)
    const rawPath = decodeURIComponent(req.path).toLowerCase();

    // Anti path-traversal : tout chemin contenant '..' est rejeté
    if (rawPath.includes('..')) {
        return res.status(403).end();
    }

    // Blocage des dossiers sensibles
    for (const dir of BLOCKED_DIRS) {
        if (rawPath.startsWith('/' + dir + '/') || rawPath === '/' + dir) {
            return res.status(403).end();
        }
    }

    // Blocage par nom de fichier exact
    const basename = path.basename(rawPath);
    if (BLOCKED_FILES.has(basename)) {
        return res.status(403).end();
    }

    // Blocage par extension
    const ext = path.extname(basename);
    if (BLOCKED_EXT.has(ext)) {
        return res.status(403).end();
    }

    // Blocage des fichiers cachés (commencent par '.')
    if (basename.startsWith('.')) {
        return res.status(403).end();
    }

    next();
});

// Sert uniquement les assets publics autorisés par le filtre ci-dessus.
// Note : les pages PHP sont servies par Apache (port 80), pas par Node.
// `index: false` évite que Node serve un éventuel index.html par défaut.
app.use(express.static(__dirname, {
    index: false,
    dotfiles: 'deny'
}));

// ══════════════════════════════════════════
// ELO CONFIG
// ══════════════════════════════════════════
const ELO = {
    START : 1200,
    FLOOR : 1100,
    THRESHOLDS: { DIV3: 1200, DIV2: 1500, DIV1: 1800, ELITE: 2000 },
    POINTS: {
        FILET: { win: 30, loss:  0 },
        DIV3 : { win: 30, loss: 30 },
        DIV2 : { win: 25, loss: 30 },
        DIV1 : { win: 15, loss: 30 },
        ELITE: { win: 10, loss: 30 },
    },
    BUZZ_TIMEOUT_PENALTY: 50,
};

function getEloZone(elo) {
    if (elo < ELO.THRESHOLDS.DIV3)  return 'FILET';
    if (elo < ELO.THRESHOLDS.DIV2)  return 'DIV3';
    if (elo < ELO.THRESHOLDS.DIV1)  return 'DIV2';
    if (elo < ELO.THRESHOLDS.ELITE) return 'DIV1';
    return 'ELITE';
}

function applyElo(winnerElo, loserElo) {
    const winPts  = ELO.POINTS[getEloZone(winnerElo)].win;
    const lossPts = ELO.POINTS[getEloZone(loserElo)].loss;
    return {
        newWinner: winnerElo + winPts,
        newLoser : Math.max(ELO.FLOOR, loserElo - lossPts),
        winPts, lossPts,
    };
}

// ──────────────────────────────────────────────────────────────
// DIFFICULTÉ ADAPTATIVE PAR ELO
// ──────────────────────────────────────────────────────────────
// Une seule fonction centralise tout : on lui donne un ELO, elle
// renvoie le MIX de difficultés à respecter pour ce niveau, sous
// forme de proportions { facile, moyen, difficile } sommant à 1.
//
// Switch par tranche de 100 ELO → progression douce, pas de saut
// brutal entre divisions.
//
function getQuestionMix(elo) {
    switch (true) {
        case (elo < 1200): return { facile: 0.85, moyen: 0.15, difficile: 0.00 };
        case (elo < 1300): return { facile: 0.75, moyen: 0.23, difficile: 0.02 };
        case (elo < 1400): return { facile: 0.60, moyen: 0.35, difficile: 0.05 };
        case (elo < 1500): return { facile: 0.50, moyen: 0.40, difficile: 0.10 };
        case (elo < 1600): return { facile: 0.40, moyen: 0.45, difficile: 0.15 };
        case (elo < 1700): return { facile: 0.30, moyen: 0.45, difficile: 0.25 };
        case (elo < 1800): return { facile: 0.20, moyen: 0.45, difficile: 0.35 };
        case (elo < 1900): return { facile: 0.10, moyen: 0.40, difficile: 0.50 };
        default:           return { facile: 0.05, moyen: 0.30, difficile: 0.65 };
    }
}

// @deprecated — gardée pour compat éventuelle, mais on n'utilise plus
// que getQuestionMix dans la pioche.
function getDifficultyForElo(elo) {
    const mix = getQuestionMix(elo);
    return Object.entries(mix).filter(([_, p]) => p > 0).map(([d]) => d);
}

// ══════════════════════════════════════════
// QUESTIONS
// ══════════════════════════════════════════
let QUESTIONS = [];

function loadQuestions() {
    const candidates = [
        path.join(__dirname, 'questions.json'),                // racine projet
        path.join(__dirname, 'data', 'questions.json'),        // ./data/
        path.join(__dirname, '..', 'questions.json'),          // ../
        path.join(__dirname, '..', 'data', 'questions.json'),  // ../data/
    ];
    const src = candidates.find(p => fs.existsSync(p));

    if (!src) {
        console.warn('⚠️  [QUESTIONS] questions.json introuvable — questions de test');
        console.warn('    Emplacements testés :');
        candidates.forEach(p => console.warn(`      - ${p}`));
        QUESTIONS = getTestQuestions();
        return;
    }

    try {
        const data = JSON.parse(fs.readFileSync(src, 'utf8'));
        if (!Array.isArray(data.categories)) {
            throw new Error('Structure invalide : "categories" doit être un tableau');
        }
        data.categories.forEach(cat => {
            if (!Array.isArray(cat.questions)) return;
            cat.questions.forEach(q => {
                QUESTIONS.push({
                    ...q,
                    catLabel:   cat.id,      // clé interne stable (sciences, geographie, ...)
                    catIcon:    cat.icon,
                    catDisplay: cat.label    // nom joli pour le front (Sciences & Nature, ...)
                });
            });
        });
        console.log(`✅ [QUESTIONS] ${QUESTIONS.length} chargées depuis ${src}`);
        console.log(`   ${data.categories.length} catégories : ${data.categories.map(c => c.id).join(', ')}`);
    } catch(e) {
        console.error(`❌ [QUESTIONS] Erreur lecture ${src} : ${e.message}`);
        QUESTIONS = getTestQuestions();
    }
}

function getTestQuestions() {
    return [
        {id:'t1', question:"Quelle est la capitale du Maroc ?", options:["Casablanca","Marrakech","Fès","Rabat"], answer:"Rabat", difficulty:"facile", points:100, time:15, catLabel:"Géographie", catIcon:"🌍"},
        {id:'t2', question:"Quel est le symbole chimique de l'or ?", options:["Au","Ag","Fe","Cu"], answer:"Au", difficulty:"facile", points:100, time:15, catLabel:"Sciences", catIcon:"🔬"},
        {id:'t3', question:"Qui a peint la Joconde ?", options:["Michel-Ange","Rafael","Léonard de Vinci","Botticelli"], answer:"Léonard de Vinci", difficulty:"facile", points:100, time:15, catLabel:"Culture", catIcon:"🧠"},
        {id:'t4', question:"Combien font 12 × 12 ?", options:["132","144","124","148"], answer:"144", difficulty:"facile", points:100, time:15, catLabel:"Maths", catIcon:"📐"},
        {id:'t5', question:"En quelle année le Maroc a obtenu son indépendance ?", options:["1954","1956","1958","1960"], answer:"1956", difficulty:"facile", points:100, time:15, catLabel:"Histoire", catIcon:"🏛️"},
        {id:'t6', question:"Quel est le gaz le plus abondant dans l'atmosphère ?", options:["Oxygène","CO2","Azote","Argon"], answer:"Azote", difficulty:"facile", points:100, time:15, catLabel:"Sciences", catIcon:"🔬"},
        {id:'t7', question:"Qu'est-ce que Git ?", options:["Un langage","Un système de versions","Un serveur","Une BDD"], answer:"Un système de versions", difficulty:"facile", points:100, time:15, catLabel:"Informatique", catIcon:"💻"},
        {id:'t8', question:"Quel pays a remporté la Coupe du monde 2022 ?", options:["France","Argentine","Brésil","Angleterre"], answer:"Argentine", difficulty:"facile", points:100, time:15, catLabel:"Sport", catIcon:"⚽"},
        {id:'t9', question:"Qui a écrit Le Petit Prince ?", options:["Jules Verne","Camus","Saint-Exupéry","Proust"], answer:"Saint-Exupéry", difficulty:"facile", points:100, time:15, catLabel:"Art", catIcon:"🎨"},
        {id:'t10',question:"Quel est le plus grand océan du monde ?", options:["Atlantique","Indien","Pacifique","Arctique"], answer:"Pacifique", difficulty:"facile", points:100, time:15, catLabel:"Géographie", catIcon:"🌍"},
    ];
}

loadQuestions();

// Pioche N questions en respectant le mix { facile, moyen, difficile }.
// Si une difficulté est sous-fournie dans le pool, on rebascule le surplus
// sur 'moyen' (le bucket le plus polyvalent).
function pickQuestionsByMix(pool, mix, total = 10) {
    // Calcul des cibles arrondies
    const targets = {
        facile:    Math.round(total * mix.facile),
        moyen:     Math.round(total * mix.moyen),
        difficile: Math.round(total * mix.difficile),
    };
    // Réajustement (arrondi peut donner ±1 vs total)
    let sum = targets.facile + targets.moyen + targets.difficile;
    if (sum !== total) targets.moyen = Math.max(0, targets.moyen + (total - sum));

    const shuffle = arr => [...arr].sort(() => Math.random() - 0.5);
    const buckets = {
        facile:    shuffle(pool.filter(q => q.difficulty === 'facile')),
        moyen:     shuffle(pool.filter(q => q.difficulty === 'moyen')),
        difficile: shuffle(pool.filter(q => q.difficulty === 'difficile')),
    };

    const picked = [];
    for (const lvl of ['facile', 'moyen', 'difficile']) {
        const take = Math.min(targets[lvl], buckets[lvl].length);
        picked.push(...buckets[lvl].slice(0, take));
    }

    // Si pool trop pauvre dans une difficulté, on complète au hasard avec ce qui reste
    if (picked.length < total) {
        const remaining = shuffle(pool.filter(q => !picked.includes(q)));
        picked.push(...remaining.slice(0, total - picked.length));
    }

    // Re-shuffle final : pas d'enchaînement "tous les faciles d'abord"
    return shuffle(picked).slice(0, total);
}

function buildQuestions(avgElo = ELO.START) {
    const mix = getQuestionMix(avgElo);
    const picked = pickQuestionsByMix(QUESTIONS, mix, 10);
    const stats = picked.reduce((acc, q) => { acc[q.difficulty]++; return acc; },
        { facile: 0, moyen: 0, difficile: 0 });
    console.log(`🎯 ELO ${Math.round(avgElo)} → cible ${JSON.stringify(mix)} → tiré F:${stats.facile}/M:${stats.moyen}/D:${stats.difficile}`);
    return picked;
}

// ══════════════════════════════════════════
// ROOMS
// ══════════════════════════════════════════
const rooms = new Map();

const POINTS_TO_WIN   = 1000;
const READ_DELAY      = 3000;   // afficher la question
const BUZZ_DELAY      = 2000;   // afficher les options
const ANSWER_TIMEOUT  = 8;      // secondes pour répondre après buzz
const ROOM_TTL_MS     = 60000;  // suppression auto de la room après la fin

function destroyRoom(roomCode) {
    const room = rooms.get(roomCode);
    if (!room) return;
    clearRoomTimers(room);
    if (room.deleteTimeout) clearTimeout(room.deleteTimeout);
    Object.values(room.players).forEach(p => {
        if (p.disconnectTimeout) clearTimeout(p.disconnectTimeout);
    });
    rooms.delete(roomCode);
    console.log(`🗑 Room ${roomCode} détruite proprement`);
}

function clearRoomTimers(room) {
    if (!room) return;
    if (room.timerInterval)       { clearInterval(room.timerInterval);    room.timerInterval = null; }
    if (room.nextQuestionTimeout) { clearTimeout(room.nextQuestionTimeout); room.nextQuestionTimeout = null; }
    if (room.readTimeout)         { clearTimeout(room.readTimeout);       room.readTimeout = null; }
    if (room.buzzTimeout)         { clearTimeout(room.buzzTimeout);       room.buzzTimeout = null; }
}

function scheduleNextQuestion(roomCode, delay = 3000) {
    const room = rooms.get(roomCode);
    if (!room || room.transitioning) return;
    room.transitioning = true;

    if (room.nextQuestionTimeout) clearTimeout(room.nextQuestionTimeout);

    room.nextQuestionTimeout = setTimeout(() => {
        const r = rooms.get(roomCode);
        if (r) r.transitioning = false;
        nextQuestion(roomCode);
    }, delay);
}

function generateCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let c = '';
    for (let i = 0; i < 4; i++) c += chars[Math.floor(Math.random() * chars.length)];
    return c;
}

function getPublicScores(room) {
    return Object.values(room.players).map(p => ({
        id: p.id, name: p.name, score: p.score, elo: p.elo,
        correct: p.correct, wrong: p.wrong,
    })).sort((a, b) => b.score - a.score);
}

function getPlayerIdBySocket(room, socketId) {
    const p = Object.values(room.players).find(p => p.socketId === socketId);
    return p?.id || null;
}

function getOpponentId(room, myPlayerId) {
    const opp = Object.values(room.players).find(p => p.id !== myPlayerId);
    return opp?.id || null;
}

function checkWinner(room) {
    for (const p of Object.values(room.players)) {
        if (p.score >= POINTS_TO_WIN) return p.id;
    }
    if (room.currentQ >= room.questions.length) {
        const sorted = Object.values(room.players).sort((a, b) => b.score - a.score);
        return sorted[0]?.id;
    }
    return null;
}

// ══════════════════════════════════════════
// GAME FLOW
// ══════════════════════════════════════════

/**
 * Lance le countdown 3-2-1 puis la 1ère question.
 * Appelée :
 *  - après que les 2 joueurs aient rejoint game-1v1.html (1ère partie)
 *  - après une revanche acceptée par les 2 joueurs
 */
function startGameCountdown(roomCode) {
    const room = rooms.get(roomCode);
    if (!room) return;

    // Annule le timer de suppression si présent (cas revanche)
    if (room.deleteTimeout) {
        clearTimeout(room.deleteTimeout);
        room.deleteTimeout = null;
    }

    if (room.countdownStarted) return;
    room.countdownStarted = true;
    room.phase = 'countdown';
    room.status    = 'countdown';

    // Calibration des questions selon la moyenne ELO des 2 joueurs
    const playersList = Object.values(room.players);
    const avgElo = playersList.length > 0
        ? playersList.reduce((sum, p) => sum + (p.elo || ELO.START), 0) / playersList.length
        : ELO.START;
    room.questions = buildQuestions(avgElo);
    room.currentQ  = 0;

    console.log(`🎮 [${roomCode}] countdown lancé`);

    io.to(roomCode).emit('game_state', {
        players: getPublicScores(room), status: 'countdown', roomCode,
    });

    let count = 3;
    io.to(roomCode).emit('countdown', { count });

    const interval = setInterval(() => {
        count--;
        if (count > 0) {
            io.to(roomCode).emit('countdown', { count });
        } else {
            clearInterval(interval);
            const latestRoom = rooms.get(roomCode);
            if (latestRoom) latestRoom.countdownStarted = false;
            startQuestion(roomCode);
        }
    }, 1000);
}

function startQuestion(roomCode) {
    const room = rooms.get(roomCode);
    if (!room) return;

    clearRoomTimers(room);
    room.transitioning = false;

    const winnerId = checkWinner(room);
    if (winnerId) return endGame(roomCode, winnerId);

    const q       = room.questions[room.currentQ];
    room.phase = 'question';
    room.status   = 'playing';
    room.buzzedBy = null;
    room.answered = false;
    room.buzzOpen = false;
    room.opponentChanceFor = null;   // reset par sécurité
    room.alreadyOfferedChance = false;
    room.timerLeft = q.time;

    console.log(`❓ Q${room.currentQ + 1}: "${q.question.slice(0, 50)}..."`);

    // Phase 1 : texte
    io.to(roomCode).emit('question_text', {
        index   : room.currentQ,
        total   : room.questions.length,
        question: q.question,
        catLabel: q.catLabel,
        catIcon : q.catIcon,
        difficulty : q.difficulty,
        scores  : getPublicScores(room),
        pointsToWin: POINTS_TO_WIN,
    });

    // Phase 2 : options
    room.readTimeout = setTimeout(() => {
        if (!rooms.has(roomCode)) return;
        io.to(roomCode).emit('question_options', {
            options: [...q.options].sort(() => Math.random() - 0.5),
            points : q.points,
            time   : q.time,
        });

        // Phase 3 : buzz ouvert
        room.buzzTimeout = setTimeout(() => {
            if (!rooms.has(roomCode)) return;
            room.phase = 'buzz';
            room.buzzOpen = true;
            io.to(roomCode).emit('buzz_open');

            // Timer global
            clearInterval(room.timerInterval);
            room.timerInterval = setInterval(() => {
                room.timerLeft--;
                io.to(roomCode).emit('timer_tick', {
                    timeLeft: room.timerLeft, timeMax: q.time,
                });
                if (room.timerLeft <= 0) {
                    clearInterval(room.timerInterval);
                    if (!room.answered) {
                        room.answered = true;
                        io.to(roomCode).emit('time_out', {
                            answer: q.answer, scores: getPublicScores(room),
                        });
                        scheduleNextQuestion(roomCode, 3000);
                    }
                }
            }, 1000);
        }, BUZZ_DELAY);
    }, READ_DELAY);
}

function nextQuestion(roomCode) {
    const room = rooms.get(roomCode);
    if (!room || room.status === 'gameOver') return;

    clearInterval(room.timerInterval);
    room.currentQ++;

    if (room.currentQ >= room.questions.length) {
        const sorted = Object.values(room.players).sort((a,b) => b.score - a.score);
        return endGame(roomCode, sorted[0]?.id);
    }
    const winnerId = checkWinner(room);
    if (winnerId) return endGame(roomCode, winnerId);

    startQuestion(roomCode);
}

// ── Persister l'ELO + stats de partie en BDD via Apache/PHP ──
function persistElo(room, winnerId, eloResult) {
    if (!eloResult) return;

    const players = Object.values(room.players);
    const totalQuestions = room.questions?.length || 10;

    const game_results = players.map(p => {
        const numericId = parseInt(String(p.id).replace(/^u/, ''), 10);
        const eloData = eloResult[p.id] || {};
        return {
            user_id:         numericId,
            new_elo:         eloData.newElo || p.elo,
            score:           p.score || 0,
            correct:         p.correct || 0,
            wrong:           p.wrong || 0,
            total_q:         totalQuestions,
            is_winner:       p.id === winnerId,
            category_results: p.catResults || [],
        };
    }).filter(u => u.user_id > 0);

    if (game_results.length === 0) return;

    const payload = JSON.stringify({
        server_key: SERVER_KEY,
        game_results: game_results,
    });

    const url = new URL(PHP_BASE_URL + '/save_elo.php');
    const options = {
        hostname: url.hostname,
        port: url.port || 80,
        path: url.pathname,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(payload),
        },
    };

    const req = http.request(options, (res) => {
        let body = '';
        res.on('data', c => body += c);
        res.on('end', () => {
            if (res.statusCode === 200) {
                console.log(`💾 ELO + stats persistés :`, body);
            } else {
                console.warn(`⚠️ save_elo HTTP ${res.statusCode}:`, body);
            }
        });
    });
    req.on('error', err => console.warn('⚠️ save_elo erreur:', err.message));
    req.write(payload);
    req.end();
}

function endGame(roomCode, winnerId) {
    const room = rooms.get(roomCode);
    if (!room) return;
    clearRoomTimers(room);
    if (room.status === 'gameOver') return;
    room.phase = 'gameover';
    room.status = 'gameOver';

    const winner = room.players[winnerId];
    const loser  = Object.values(room.players).find(p => p.id !== winnerId);

    let eloResult = null;
    if (winner && loser && room.isRanked) {
        const { newWinner, newLoser, winPts, lossPts } = applyElo(winner.elo, loser.elo);
        eloResult = {
            [winner.id]: { oldElo: winner.elo, newElo: newWinner, delta:  winPts },
            [loser.id ]: { oldElo: loser.elo,  newElo: newLoser,  delta: -lossPts },
        };
        winner.elo = newWinner;
        loser.elo  = newLoser;
        console.log(`🏆 ${winner.name} +${winPts} ELO | ${loser.name} -${lossPts} ELO`);

        // Persister en BDD (ELO + stats de partie)
        persistElo(room, winnerId, eloResult);
    } else if (winner && loser) {
        // Partie amicale : on log mais on ne touche pas à l'ELO en BDD
        console.log(`🎉 [AMICAL] ${winner.name} bat ${loser.name} — pas de changement ELO`);
    }

    io.to(roomCode).emit('game_over', {
        winnerId,
        winnerName : winner?.name  || '?',
        winnerScore: winner?.score || 0,
        scores     : getPublicScores(room),
        eloResult,
        forfeit    : false,
    });

    // Reset des "ready in game" pour préparer une éventuelle revanche
    room.gameReady       = {};
    room.phase = 'rematch';
    room.rematchAccepted = new Set();

    // Suppression auto après 60s SAUF si revanche entre-temps
    room.deleteTimeout = setTimeout(() => {
        destroyRoom(roomCode);
    }, ROOM_TTL_MS);
}

// ══════════════════════════════════════════
// SOCKET.IO EVENTS
// ══════════════════════════════════════════
io.on('connection', (socket) => {
    console.log(`🔌 [connect] ${socket.id}`);

    // ── CRÉER ──
    socket.on('create_room', ({ playerId, name, elo, isRanked }) => {
        if (!playerId) { socket.emit('error', { message: 'playerId manquant' }); return; }

        const playerName = (name || 'Joueur').trim().slice(0, 20);
        const playerElo  = parseInt(elo) || ELO.START;
        // ── Flag classé/amical : true par défaut (sécurité, ne change rien à l'existant)
        const ranked     = isRanked !== false;

        let code;
        do { code = generateCode(); } while (rooms.has(code));

        const room = {
            code, status: 'lobby',
            isRanked: ranked,
            players: {
                [playerId]: {
                    id: playerId, socketId: socket.id,
                    name: playerName, elo: playerElo,
                    score: 0, correct: 0, wrong: 0, catResults: [],
                    ready: false, isHost: true,
                }
            },
            questions: [], currentQ: 0,
            buzzedBy: null, answered: false, buzzOpen: false,
            timerInterval: null, nextQuestionTimeout: null,
            readTimeout: null, buzzTimeout: null,
            deleteTimeout: null,
            timerLeft: 0,
            transitioning: false,
            processingAnswer: false,
            phase: 'lobby',
            opponentChanceFor: null,  // ID du joueur qui a la chance de buzzer après une faute adverse
            alreadyOfferedChance: false, // pour empêcher le ping-pong infini de chances
            transitionQueue: [],
            countdownStarted: false,
            gameReady: {},          // { playerId: true } — qui est sur game-1v1.html
            rematchAccepted: new Set(),
        };

        rooms.set(code, room);
        socket.join(code);

        socket.emit('room_created', { code, players: getPublicScores(room), isRanked: room.isRanked });
        console.log(`🏠 Room "${code}" créée par ${playerName} ${room.isRanked ? '[CLASSÉ]' : '[AMICAL]'}`);
    });

    // ── REJOINDRE ──
    socket.on('join_room', ({ playerId, code, name, elo }) => {
        if (!playerId) { socket.emit('join_error', { message: 'playerId manquant' }); return; }

        const roomCode   = (code || '').toUpperCase().trim();
        const playerName = (name || 'Joueur').trim().slice(0, 20);
        const playerElo  = parseInt(elo) || ELO.START;
        const room       = rooms.get(roomCode);

        if (!room)                                                           { socket.emit('join_error', { message: 'Room introuvable.' });    return; }
        if (room.status !== 'lobby')                                         { socket.emit('join_error', { message: 'Partie déjà en cours.' });return; }
        if (Object.keys(room.players).length >= 2 && !room.players[playerId]){ socket.emit('join_error', { message: 'Room pleine.' });          return; }

        room.players[playerId] = {
            id: playerId, socketId: socket.id,
            name: playerName, elo: playerElo,
            score: 0, correct: 0, wrong: 0, catResults: [],
            ready: false, isHost: false,
        };

        socket.join(roomCode);
        socket.emit('room_joined', { code: roomCode, players: getPublicScores(room), isRanked: room.isRanked });
        io.to(roomCode).emit('player_joined', { players: getPublicScores(room) });

        console.log(`✅ ${playerName} a rejoint "${roomCode}" ${room.isRanked ? '[CLASSÉ]' : '[AMICAL]'}`);
    });

    // ──────────────────────────────────────────────────────────────
    // RECONNEXION (après redirect lobby → game)
    // ★ FIX BUG 1 : c'est ici qu'on déclenche le countdown quand
    //   les DEUX joueurs sont bien arrivés sur game-1v1.html.
    // ──────────────────────────────────────────────────────────────
    socket.on('rejoin_room', ({ code, playerId }) => {
        if (!playerId) { socket.emit('error', { message: 'playerId manquant' }); return; }

        const roomCode = (code || '').toUpperCase().trim();
        const room     = rooms.get(roomCode);
        if (!room)                  { socket.emit('error', { message: 'Room introuvable.' });        return; }
        const player = room.players[playerId];
        if (!player)                { socket.emit('error', { message: 'Joueur pas dans la room.' }); return; }

        // Met à jour le socket.id (playerId reste stable)
        if (player.disconnectTimeout) { clearTimeout(player.disconnectTimeout); player.disconnectTimeout = null; }
        player.socketId = socket.id;
        socket.join(roomCode);

        // Si on attendait l'arrivée des joueurs sur la page de jeu
        if (room.status === 'preparing_game') {
            room.gameReady[playerId] = true;
            const total    = Object.keys(room.players).length;
            const ready    = Object.values(room.gameReady).filter(Boolean).length;
            console.log(`🚪 [${roomCode}] ${player.name} prêt en jeu (${ready}/${total})`);

            if (ready >= 2 && total >= 2 && !room.countdownStarted) {
                setTimeout(() => startGameCountdown(roomCode), 300);
            }
        }

        // State resync — TOUJOURS envoyé, quel que soit le status
        // Permet au client de se reconstruire entièrement après reconnexion
        const currentQuestion = room.questions?.[room.currentQ] || null;
        socket.emit('state_resync', {
            phase: room.phase || room.status,
            currentQ: room.currentQ,
            timerLeft: room.timerLeft,
            buzzedBy: room.buzzedBy,
            buzzOpen: room.buzzOpen,
            answered: room.answered,
            scores: getPublicScores(room),
            question: currentQuestion ? {
                question: currentQuestion.question,
                options: currentQuestion.options,
                catLabel: currentQuestion.catLabel,
                catIcon: currentQuestion.catIcon,
                difficulty: currentQuestion.difficulty,
                points: currentQuestion.points,
                time: currentQuestion.time,
            } : null,
        });

        socket.emit('game_state', {
            players: getPublicScores(room),
            status : room.status,
            roomCode,
        });

        console.log(`🔄 ${player.name} reconnecté à "${roomCode}" (status: ${room.status})`);
    });

    // ──────────────────────────────────────────────────────────────
    // JOUEUR PRÊT (host lance la partie depuis la lobby)
    // ★ FIX BUG 1 : on n'émet plus le countdown ici, on demande
    //   juste aux clients de rediriger vers la page de jeu. Le
    //   countdown sera lancé une fois les 2 joueurs reconnectés.
    // ──────────────────────────────────────────────────────────────
    socket.on('player_ready', ({ code, playerId }) => {
        const room = rooms.get(code);
        if (!room) return;

        const player = room.players[playerId];
        if (!player || !player.isHost) {
            console.warn(`⚠️  player_ready refusé pour ${playerId}`);
            return;
        }
        if (Object.keys(room.players).length < 2) return;
        if (room.status !== 'lobby') return;

        room.phase = 'redirecting';
        room.status    = 'preparing_game';
        room.gameReady = {};
        Object.keys(room.players).forEach(pid => room.gameReady[pid] = false);

        // Signal aux 2 lobbys de rediriger
        io.to(code).emit('start_countdown');
        console.log(`🎬 [${code}] redirection vers game-1v1.html demandée`);
    });

    // ── BUZZ ──
    socket.on('buzz', ({ code, playerId }) => {
        const room = rooms.get(code);
        if (!room || room.status !== 'playing' || room.buzzedBy || room.answered) return;

        const player = room.players[playerId];
        if (!player) return;

        // ─── CAS 1 : phase normale "buzz" — premier arrivé prend la main ───
        if (room.phase === 'buzz' && room.buzzOpen) {
            room.processingAnswer = false;
            room.phase    = 'answer';
            room.buzzedBy = playerId;
            clearRoomTimers(room);

            const q = room.questions[room.currentQ];
            console.log(`🔔 ${player.name} a buzzé (${room.timerLeft}s restantes)`);

            io.to(code).emit('buzzed', {
                buzzerId  : playerId,
                buzzerName: player.name,
                timeLeft  : room.timerLeft,
                timeMax   : q.time,
            });

            let answerTimer = ANSWER_TIMEOUT;
            room.timerInterval = setInterval(() => {
                answerTimer--;
                io.to(code).emit('answer_timer', { timeLeft: answerTimer });
                if (answerTimer <= 0) {
                    clearInterval(room.timerInterval);
                    if (!room.answered) {
                        room.answered = true;
                        const malus = -ELO.BUZZ_TIMEOUT_PENALTY;
                        player.score = player.score + malus;   // pas de clamp : malus visible
                        player.wrong++;
                        player.catResults.push({ catId: q.category_id || 0, correct: 0 });
                        io.to(code).emit('answer_result', {
                            answerId: playerId, correct: false,
                            pts: malus, answer: q.answer,
                            scores: getPublicScores(room), timeout: true,
                        });
                        scheduleNextQuestion(code, 3000);
                    }
                }
            }, 1000);
            return;
        }

        // ─── CAS 2 : phase "opponent_chance" — seul l'adversaire désigné peut buzzer ───
        if (room.phase === 'opponent_chance' && room.opponentChanceFor === playerId) {
            clearRoomTimers(room);
            room.opponentChanceFor = null;
            room.phase    = 'answer';
            room.buzzedBy = playerId;

            const q = room.questions[room.currentQ];
            console.log(`🔔 ${player.name} a buzzé (chance adversaire)`);

            io.to(code).emit('buzzed', {
                buzzerId  : playerId,
                buzzerName: player.name,
                timeLeft  : 5,
                timeMax   : 5,
            });

            // 5s pour répondre. Si timeout : malus + révélation de la bonne.
            let answerTimer = 5;
            room.timerInterval = setInterval(() => {
                answerTimer--;
                io.to(code).emit('answer_timer', { timeLeft: answerTimer });
                if (answerTimer <= 0) {
                    clearInterval(room.timerInterval);
                    if (!room.answered) {
                        room.answered = true;
                        const malus = -ELO.BUZZ_TIMEOUT_PENALTY;
                        player.score = player.score + malus;
                        player.wrong++;
                        player.catResults.push({ catId: q.category_id || 0, correct: 0 });
                        io.to(code).emit('answer_result', {
                            answerId: playerId, correct: false,
                            pts: malus, answer: q.answer,
                            scores: getPublicScores(room), timeout: true,
                        });
                        scheduleNextQuestion(code, 3000);
                    }
                }
            }, 1000);
            return;
        }

        // Hors de ces 2 cas : on ignore (sécurité).
    });

    // ── RÉPONDRE ──
    socket.on('answer', ({ code, playerId, chosen }) => {
        const room = rooms.get(code);
        if (!room || room.phase !== 'answer' || room.answered || room.processingAnswer) return;
        if (room.buzzedBy !== playerId) return;
        if (typeof chosen !== 'string' || chosen.length > 120) return;
        room.processingAnswer = true;

        const player = room.players[playerId];
        if (!player) return;

        clearRoomTimers(room);
        room.answered = true;

        const q         = room.questions[room.currentQ];
        const isCorrect = chosen === q.answer;
        const ratio     = room.timerLeft / q.time;
        const pts       = isCorrect
            ? Math.round(q.points * (1 + ratio))
            : -Math.round(q.points * ratio);

        // Le score peut désormais descendre sous zéro : le malus est VISIBLE
        // dans le total. Avant : Math.max(0, …) → en début de partie le malus
        // était neutralisé et le joueur avait l'impression qu'il n'existait pas.
        player.score = player.score + pts;
        if (isCorrect) player.correct++;
        else           player.wrong++;

        // Tracking catégorie pour le radar
        player.catResults.push({ catId: q.category_id || 0, correct: isCorrect ? 1 : 0 });

        if (isCorrect) {
            // ─── BONNE RÉPONSE : on révèle (answer transmis) et on enchaîne ───
            io.to(code).emit('answer_result', {
                answerId: playerId, correct: true, pts,
                chosen,                     // la réponse choisie par le buzzer
                answer  : q.answer,         // la bonne réponse (présente → révélation)
                scores  : getPublicScores(room),
                timeout : false,
            });
            scheduleNextQuestion(code, 3000);
        } else {
            // ─── MAUVAISE RÉPONSE : on n'annonce QUE la réponse choisie (en rouge)
            //     L'adversaire aura sa chance via opponent_chance ───
            io.to(code).emit('answer_result', {
                answerId: playerId, correct: false, pts,
                chosen,                     // ← affiché en rouge côté client
                // answer ABSENT volontairement : on ne révèle pas la bonne tout de suite
                scores  : getPublicScores(room),
                timeout : false,
            });

            const oppId = getOpponentId(room, playerId);
            if (oppId && !room.alreadyOfferedChance) {
                // Première faute de la question → on offre la chance à l'adversaire.
                room.alreadyOfferedChance = true;
                room.buzzedBy = null;
                room.answered = false;
                room.processingAnswer = false;
                room.opponentChanceFor = oppId;
                room.phase = 'opponent_chance';

                io.to(code).emit('opponent_chance', {
                    playerId      : oppId,
                    timeLeft      : 5,
                    wrongAnswer   : chosen,
                    wrongPlayerId : playerId,
                });

                let oppTimer = 5;
                room.timerInterval = setInterval(() => {
                    oppTimer--;
                    io.to(code).emit('answer_timer', { timeLeft: oppTimer });
                    if (oppTimer <= 0) {
                        clearInterval(room.timerInterval);
                        if (!room.answered && room.opponentChanceFor === oppId) {
                            room.answered = true;
                            room.opponentChanceFor = null;
                            io.to(code).emit('time_out', {
                                answer: q.answer,
                                scores: getPublicScores(room),
                            });
                            scheduleNextQuestion(code, 2500);
                        }
                    }
                }, 1000);
            } else {
                // Soit pas d'adversaire, soit la chance a déjà été offerte sur cette question
                // (les deux ont fauté) → on révèle la bonne réponse maintenant.
                io.to(code).emit('time_out', {
                    answer: q.answer,
                    scores: getPublicScores(room),
                });
                scheduleNextQuestion(code, 2500);
            }
        }
    });

    // ── DÉCONNEXION ──
    socket.on('disconnect', () => {
        for (const [code, room] of rooms) {
            const playerId = getPlayerIdBySocket(room, socket.id);
            if (!playerId) continue;
            const player = room.players[playerId];
            if (player.socketId !== socket.id) continue;

            if (room.status === 'lobby' || room.status === 'preparing_game'
             || room.status === 'countdown' || room.status === 'playing') {
                player.socketId = null;
                console.log(`⚠️  ${player.name} disconnect — attente reconnexion`);

                // 10s pour se reconnecter avant forfait
                player.disconnectTimeout = setTimeout(() => {
                    const r = rooms.get(code);
                    if (!r) return;
                    const p = r.players[playerId];
                    if (p && p.socketId === null) {
                        console.log(`🏳  ${p.name} pas reconnecté → forfait`);
                        delete r.players[playerId];

                        if (Object.keys(r.players).length === 0) {
                            destroyRoom(code);
                        } else if (r.status !== 'gameOver') {
                            const remaining = Object.values(r.players)[0];
                            r.status = 'gameOver';
                            io.to(code).emit('game_over', {
                                winnerId   : remaining.id,
                                winnerName : remaining.name,
                                winnerScore: remaining.score,
                                scores     : getPublicScores(r),
                                eloResult  : null,
                                forfeit    : true,
                            });
                        }
                    }
                }, 10000);
            }
            break;
        }
    });

    // ──────────────────────────────────────────────────────────────
    // ★ FIX BUG 2 : REVANCHE
    // On attend que les DEUX joueurs cliquent sur "Revanche".
    // Quand les 2 sont OK, on annule la suppression de la room,
    // on reset les scores et on relance startGameCountdown().
    // ──────────────────────────────────────────────────────────────
    socket.on('rematch', ({ code, playerId }) => {
        const room = rooms.get(code);
        if (!room) return;
        if (!room.players[playerId]) return;

        if (!room.rematchAccepted) room.rematchAccepted = new Set();
        room.phase = 'rematch';
        room.rematchAccepted.add(playerId);

        const playerCount   = Object.keys(room.players).length;
        const acceptedCount = room.rematchAccepted.size;

        console.log(`↺ [${code}] revanche acceptée (${acceptedCount}/${playerCount})`);

        io.to(code).emit('rematch_pending', {
            playerId,
            accepted: acceptedCount,
            total   : playerCount,
        });

        if (acceptedCount >= playerCount && playerCount >= 2) {
            // Les 2 sont OK → on relance
            room.rematchAccepted = new Set();

            // Annule la suppression de la room
            if (room.deleteTimeout) {
                clearTimeout(room.deleteTimeout);
                room.deleteTimeout = null;
            }

            // Reset des stats
            Object.values(room.players).forEach(p => {
                p.score = 0; p.correct = 0; p.wrong = 0; p.ready = true;
            });
            room.buzzedBy = null;
            room.answered = false;
            room.buzzOpen = false;
            room.transitioning = false;
            room.processingAnswer = false;
            clearRoomTimers(room);

            // Signal aux clients : on ferme l'end-screen, on relance
            io.to(code).emit('rematch_ready', { players: getPublicScores(room) });

            // Petit délai pour laisser l'UI se reset
            setTimeout(() => startGameCountdown(code), 800);
        }
    });
});

// ══════════════════════════════════════════
// API
// ══════════════════════════════════════════
app.get('/api/health', (req, res) => {
    res.json({ ok: true, questions: QUESTIONS.length, rooms: rooms.size });
});

// ══════════════════════════════════════════
// CHAMPIONSHIP — Mode 4 joueurs (séparé)
// ══════════════════════════════════════════
require('./server-championship')(io, QUESTIONS);

// ══════════════════════════════════════════
// START
// ══════════════════════════════════════════
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`🚀 QPC Server → http://localhost:${PORT}`);
    console.log(`📡 Socket.io prêt`);
    console.log(`❓ ${QUESTIONS.length} questions | ELO start: ${ELO.START} | Floor: ${ELO.FLOOR}`);
});