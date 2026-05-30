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

<<<<<<< HEAD
// ── Config API PHP (Apache) pour persister ELO en BDD ──
// Adapter le chemin selon votre installation XAMPP
const PHP_BASE_URL = process.env.PHP_URL || 'http://localhost/qvpcPhpV1';
const SERVER_KEY   = 'qpc_server_2026';  // doit matcher save_elo.php

=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
process.on('uncaughtException',  err => console.error('UNCAUGHT EXCEPTION:', err));
process.on('unhandledRejection', err => console.error('UNHANDLED REJECTION:', err));

const app    = express();
const server = http.createServer(app);
const io     = new Server(server, {
<<<<<<< HEAD
    cors: { origin: ['http://localhost','http://127.0.0.1','http://localhost:8080','http://localhost:8888','http://localhost:3000'], methods: ['GET','POST'] },
=======
    cors: { origin: '*', methods: ['GET','POST'] },
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
});

app.use(express.json());

<<<<<<< HEAD
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
=======
// On sert tous les fichiers statiques à côté de server.js (HTML/CSS/JS)
app.use(express.static(__dirname));
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec

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

function getDifficultyForElo(elo) {
    if (elo < ELO.THRESHOLDS.DIV3)  return ['facile'];
    if (elo < ELO.THRESHOLDS.DIV2)  return ['facile', 'moyen'];
    if (elo < ELO.THRESHOLDS.DIV1)  return ['moyen', 'difficile'];
    return ['difficile'];
}

// ══════════════════════════════════════════
// QUESTIONS
// ══════════════════════════════════════════
let QUESTIONS = [];

function loadQuestions() {
    const p1  = path.join(__dirname, 'data', 'questions.json');
    const p2  = path.join(__dirname, '..', 'data', 'questions.json');
    const src = fs.existsSync(p1) ? p1 : fs.existsSync(p2) ? p2 : null;

    if (src) {
        try {
            const data = JSON.parse(fs.readFileSync(src, 'utf8'));
            data.categories.forEach(cat => {
                cat.questions.forEach(q => {
                    QUESTIONS.push({ ...q, catLabel: cat.label, catIcon: cat.icon });
                });
            });
            console.log(`✅ [QUESTIONS] ${QUESTIONS.length} chargées depuis ${src}`);
        } catch(e) {
            console.error('❌ [QUESTIONS]', e.message);
            QUESTIONS = getTestQuestions();
        }
    } else {
        console.warn('⚠️  [QUESTIONS] questions.json introuvable — questions de test');
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

function buildQuestions(avgElo = ELO.START) {
    const allowed = getDifficultyForElo(avgElo);
    const pool    = QUESTIONS.filter(q => allowed.includes(q.difficulty));
    const src     = pool.length >= 10 ? pool : QUESTIONS;
    return [...src].sort(() => Math.random() - 0.5).slice(0, 10);
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

<<<<<<< HEAD
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

=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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

<<<<<<< HEAD
    if (room.countdownStarted) return;
    room.countdownStarted = true;
    room.phase = 'countdown';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
    room.status    = 'countdown';
    room.questions = buildQuestions();
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
<<<<<<< HEAD
            const latestRoom = rooms.get(roomCode);
            if (latestRoom) latestRoom.countdownStarted = false;
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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
<<<<<<< HEAD
    room.phase = 'question';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
    room.status   = 'playing';
    room.buzzedBy = null;
    room.answered = false;
    room.buzzOpen = false;
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
<<<<<<< HEAD
            room.phase = 'buzz';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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

<<<<<<< HEAD
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

=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
function endGame(roomCode, winnerId) {
    const room = rooms.get(roomCode);
    if (!room) return;
    clearRoomTimers(room);
    if (room.status === 'gameOver') return;
<<<<<<< HEAD
    room.phase = 'gameover';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
    room.status = 'gameOver';

    const winner = room.players[winnerId];
    const loser  = Object.values(room.players).find(p => p.id !== winnerId);

    let eloResult = null;
    if (winner && loser) {
        const { newWinner, newLoser, winPts, lossPts } = applyElo(winner.elo, loser.elo);
        eloResult = {
            [winner.id]: { oldElo: winner.elo, newElo: newWinner, delta:  winPts },
            [loser.id ]: { oldElo: loser.elo,  newElo: newLoser,  delta: -lossPts },
        };
        winner.elo = newWinner;
        loser.elo  = newLoser;
        console.log(`🏆 ${winner.name} +${winPts} ELO | ${loser.name} -${lossPts} ELO`);
<<<<<<< HEAD

        // Persister en BDD (ELO + stats de partie)
        persistElo(room, winnerId, eloResult);
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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
<<<<<<< HEAD
    room.phase = 'rematch';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
    room.rematchAccepted = new Set();

    // Suppression auto après 60s SAUF si revanche entre-temps
    room.deleteTimeout = setTimeout(() => {
<<<<<<< HEAD
        destroyRoom(roomCode);
=======
        rooms.delete(roomCode);
        console.log(`🗑  Room ${roomCode} supprimée (timeout)`);
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
    }, ROOM_TTL_MS);
}

// ══════════════════════════════════════════
// SOCKET.IO EVENTS
// ══════════════════════════════════════════
io.on('connection', (socket) => {
    console.log(`🔌 [connect] ${socket.id}`);

    // ── CRÉER ──
    socket.on('create_room', ({ playerId, name, elo }) => {
        if (!playerId) { socket.emit('error', { message: 'playerId manquant' }); return; }

        const playerName = (name || 'Joueur').trim().slice(0, 20);
        const playerElo  = parseInt(elo) || ELO.START;

        let code;
        do { code = generateCode(); } while (rooms.has(code));

        const room = {
            code, status: 'lobby',
            players: {
                [playerId]: {
                    id: playerId, socketId: socket.id,
                    name: playerName, elo: playerElo,
<<<<<<< HEAD
                    score: 0, correct: 0, wrong: 0, catResults: [],
=======
                    score: 0, correct: 0, wrong: 0,
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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
<<<<<<< HEAD
            phase: 'lobby',
            transitionQueue: [],
            countdownStarted: false,
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
            gameReady: {},          // { playerId: true } — qui est sur game-1v1.html
            rematchAccepted: new Set(),
        };

        rooms.set(code, room);
        socket.join(code);

        socket.emit('room_created', { code, players: getPublicScores(room) });
        console.log(`🏠 Room "${code}" créée par ${playerName}`);
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
<<<<<<< HEAD
            score: 0, correct: 0, wrong: 0, catResults: [],
=======
            score: 0, correct: 0, wrong: 0,
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
            ready: false, isHost: false,
        };

        socket.join(roomCode);
        socket.emit('room_joined', { code: roomCode, players: getPublicScores(room) });
        io.to(roomCode).emit('player_joined', { players: getPublicScores(room) });

        console.log(`✅ ${playerName} a rejoint "${roomCode}"`);
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
<<<<<<< HEAD
        if (player.disconnectTimeout) { clearTimeout(player.disconnectTimeout); player.disconnectTimeout = null; }
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
        player.socketId = socket.id;
        socket.join(roomCode);

        // Si on attendait l'arrivée des joueurs sur la page de jeu
        if (room.status === 'preparing_game') {
            room.gameReady[playerId] = true;
            const total    = Object.keys(room.players).length;
            const ready    = Object.values(room.gameReady).filter(Boolean).length;
            console.log(`🚪 [${roomCode}] ${player.name} prêt en jeu (${ready}/${total})`);

<<<<<<< HEAD
            if (ready >= 2 && total >= 2 && !room.countdownStarted) {
=======
            if (ready >= 2 && total >= 2) {
                // Petit délai pour laisser la page finir son rendu
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
                setTimeout(() => startGameCountdown(roomCode), 300);
            }
        }

<<<<<<< HEAD
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

=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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

<<<<<<< HEAD
        room.phase = 'redirecting';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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
<<<<<<< HEAD
        if (!room || room.phase !== 'buzz' || room.status !== 'playing' || !room.buzzOpen || room.buzzedBy || room.answered) return;
=======
        if (!room || room.status !== 'playing' || !room.buzzOpen || room.buzzedBy || room.answered) return;
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec

        const player = room.players[playerId];
        if (!player) return;

        room.processingAnswer = false;
<<<<<<< HEAD
        room.phase = 'answer';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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
                    player.score = Math.max(0, player.score + malus);
                    player.wrong++;
<<<<<<< HEAD
                    player.catResults.push({ catId: q.category_id || 0, correct: 0 });
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
                    io.to(code).emit('answer_result', {
                        answerId: playerId, correct: false,
                        pts: malus, answer: q.answer,
                        scores: getPublicScores(room), timeout: true,
                    });
                    scheduleNextQuestion(code, 3000);
                }
            }
        }, 1000);
    });

    // ── RÉPONDRE ──
    socket.on('answer', ({ code, playerId, chosen }) => {
        const room = rooms.get(code);
<<<<<<< HEAD
        if (!room || room.phase !== 'answer' || room.answered || room.processingAnswer) return;
        if (room.buzzedBy !== playerId) return;
        if (typeof chosen !== 'string' || chosen.length > 120) return;
        room.processingAnswer = true;
=======
        if (!room || room.answered || room.processingAnswer) return;
        room.processingAnswer = true;
        if (room.buzzedBy !== playerId) return;
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec

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

        player.score = Math.max(0, player.score + pts);
        if (isCorrect) player.correct++;
        else           player.wrong++;

<<<<<<< HEAD
        // Tracking catégorie pour le radar
        player.catResults.push({ catId: q.category_id || 0, correct: isCorrect ? 1 : 0 });

=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
        io.to(code).emit('answer_result', {
            answerId: playerId, correct: isCorrect, pts,
            answer  : q.answer, scores: getPublicScores(room), timeout: false,
        });

        if (!isCorrect) {
            const oppId = getOpponentId(room, playerId);
            if (oppId) {
                room.buzzedBy = null;
                room.answered = false;
                room.processingAnswer = false;

                io.to(code).emit('opponent_chance', { playerId: oppId, timeLeft: 5 });

                let oppTimer = 5;
                room.timerInterval = setInterval(() => {
                    oppTimer--;
                    io.to(code).emit('answer_timer', { timeLeft: oppTimer });
                    if (oppTimer <= 0) {
                        clearInterval(room.timerInterval);
                        if (!room.answered) {
                            room.answered = true;
                            io.to(code).emit('time_out', {
                                answer: q.answer, scores: getPublicScores(room),
                            });
                            scheduleNextQuestion(code, 2500);
                        }
                    }
                }, 1000);
            }
        } else {
            scheduleNextQuestion(code, 3000);
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
<<<<<<< HEAD
                player.disconnectTimeout = setTimeout(() => {
=======
                setTimeout(() => {
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
                    const r = rooms.get(code);
                    if (!r) return;
                    const p = r.players[playerId];
                    if (p && p.socketId === null) {
                        console.log(`🏳  ${p.name} pas reconnecté → forfait`);
                        delete r.players[playerId];

                        if (Object.keys(r.players).length === 0) {
<<<<<<< HEAD
                            destroyRoom(code);
=======
                            rooms.delete(code);
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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
<<<<<<< HEAD
        room.phase = 'rematch';
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
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
<<<<<<< HEAD
// CHAMPIONSHIP — Mode 4 joueurs (séparé)
// ══════════════════════════════════════════
require('./server-championship')(io, QUESTIONS);

// ══════════════════════════════════════════
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
// START
// ══════════════════════════════════════════
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`🚀 QPC Server → http://localhost:${PORT}`);
    console.log(`📡 Socket.io prêt`);
    console.log(`❓ ${QUESTIONS.length} questions | ELO start: ${ELO.START} | Floor: ${ELO.FLOOR}`);
});
