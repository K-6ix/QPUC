// ============================================================================
//  QPC Championship - Module socket.io
//  À inclure depuis server.js via : require('./server-championship')(io, QUESTIONS)
//
//  Ce module ajoute tous les handlers de socket pour le mode Championnat
//  (4 joueurs, 3 manches M1+M2+M3) sans toucher aux handlers 1v1 existants.
//
//  Tous les events sont préfixés "champ_*", "m1_*", "m2_*", "m3_*" pour éviter
//  les collisions avec les events du 1v1 (create_room, buzz, answer, etc.).
// ============================================================================

module.exports = function setupChampionship(io, QUESTIONS_RAW) {

    const http   = require('http');
    const https  = require('https');
    const crypto = require('crypto');
    const PHP_BASE_URL = process.env.PHP_URL || 'http://localhost/qvpcPhpV1';
    // Même clé que server.js (env SERVER_KEY en prod, cf. qpc_secret.php côté PHP)
    const SERVER_KEY   = process.env.SERVER_KEY || 'qpc_server_2026';
    const DIRECT_SAVE  = process.env.DIRECT_SAVE !== '0';

    // Enveloppe signée HMAC — même mécanisme que server.js / qpc_hmac.php
    function signEnvelope(obj) {
        const payload   = JSON.stringify(obj);
        const signature = crypto.createHmac('sha256', SERVER_KEY).update(payload).digest('hex');
        return { payload, signature };
    }

    // Normalisation des questions du projet QPC vers le format interne champion :
    // - Le projet : { question, options, answer (string), catLabel, ... }
    // - Champion  : { id, q, options, answer (index 0-3), cat }
    const CATS_BY_NAME = {};
    const questionsData = (() => {
        const norm = [];
        const cats = new Set();
        (QUESTIONS_RAW || []).forEach((q, idx) => {
            const cat = (q.catLabel || q.cat || 'General').replace(/\s+/g, '_');
            cats.add(cat);
            // Trouver l'index de la bonne réponse
            const answerIdx = q.options.indexOf(q.answer);
            if (answerIdx === -1) return; // skip si la réponse n'est pas dans options
            norm.push({
                id: q.id !== undefined ? q.id : idx,
                q: q.question || q.q,
                options: q.options,
                answer: answerIdx,
                cat
            });
        });
        return { questions: norm, categories: Array.from(cats) };
    })();
    console.log(`[CHAMP] ${questionsData.questions.length} questions, ${questionsData.categories.length} categories`);

// ============================================================================
//  CONFIGURATION GAMEPLAY
// ============================================================================
const CONFIG = {
    MAX_PLAYERS:          4,
    // ----- Manche 1 -----
    M1_TARGET_SCORE:      9,
    M1_MAX_QUESTIONS:     15,
    M1_QUESTION_TIME:     15000,
    M1_REVEAL_TIME:       3000,
    M1_TIEBREAK_TIME:     12000,
    INTER_QUESTION_GAP:   1000,
    // ----- Transition vers M2/M3 -----
    M1_END_TO_M3_DELAY:   5000,  // 5s avant de passer en M2
    // ----- Manche 2 (NEW) : 3 joueurs, similaire a M3 -----
    M2_CATEGORY_SELECTION_TIME: 20000,
    M2_BET_TIME:          15000,
    M2_QUESTION_TIME:     12000,
    M2_TOTAL_QUESTIONS:   8,
    // ----- Manche 3 -----
    M3_CATEGORY_SELECTION_TIME: 20000,  // 20s pour choisir 4 cats (etait 15s)
    M3_BET_TIME:          15000,        // 15s pour placer son pari (etait 10s)
    M3_READING_TIME:      3000,         // 3s lecture question seule (NEW D.1)
    M3_OPTIONS_REVEAL_TIME: 3000,       // 3s options visibles avant buzz (NEW D.1)
    M3_QUESTION_TIME:     12000,        // 12s timer global de buzz
    M3_BUZZ_RESPONSE_TIME: 5000,        // 5s pour repondre apres buzz
    M3_REVEAL_TIME:       3500,         // 3.5s reveal
    M3_TOTAL_QUESTIONS:   7,
    M3_TARGET_SCORE:      8,
    M3_SUDDEN_DEATH_TIME: 12000,
    M3_INTER_PHASE_GAP:   2000          // pause entre phases
};

const rooms = {};

// ============================================================================
//  HELPERS
// ============================================================================
function generateCode() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code;
    do {
        code = '';
        for (let i = 0; i < 5; i++) code += chars[Math.floor(Math.random() * chars.length)];
    } while (rooms[code]);
    return code;
}

function getPublicRoom(room) {
    return {
        code: room.code,
        status: room.status,
        hostId: room.hostId,
        players: Object.entries(room.players).map(([id, p]) => ({
            id, name: p.name, ready: p.ready, alive: p.alive, score: p.score,
            isHost: id === room.hostId
        }))
    };
}

function broadcastRoom(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    io.to(roomCode).emit('room_state', getPublicRoom(room));
}

function pickRandomQuestion(usedIds, categoryFilter = null) {
    let available = questionsData.questions.filter(q => !usedIds.includes(q.id));
    if (categoryFilter && categoryFilter.length > 0) {
        const filtered = available.filter(q => categoryFilter.includes(q.cat));
        if (filtered.length > 0) available = filtered;
    }
    if (available.length === 0) return null;
    return available[Math.floor(Math.random() * available.length)];
}

function clearAllTimers(room) {
    ['questionTimer', 'revealTimer'].forEach(t => {
        if (room.m1?.[t]) clearTimeout(room.m1[t]);
        if (room.m3?.[t]) clearTimeout(room.m3[t]);
    });
    if (room.m1?.tiebreak?.timer) clearTimeout(room.m1.tiebreak.timer);
    if (room.m3?.phaseTimer) clearTimeout(room.m3.phaseTimer);
    if (room.m3?.buzzTimer)  clearTimeout(room.m3.buzzTimer);
    if (room.m3?.suddenTimer) clearTimeout(room.m3.suddenTimer);
    // [M2]
    if (room.m2?.phaseTimer) clearTimeout(room.m2.phaseTimer);
    if (room.m2?.buzzTimer)  clearTimeout(room.m2.buzzTimer);
}

// ============================================================================
//  MANCHE 1
// ============================================================================
function startM1(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'm1';
    room.startedAt = Date.now(); // pour calculer la durée totale en fin de partie
    room.m1 = {
        questionIndex: 0, currentQuestion: null, answers: {},
        usedQuestionIds: [], questionTimer: null, revealTimer: null, finished: false
    };
    Object.values(room.players).forEach(p => { p.score = 0; p.alive = true; });
    console.log(`[M1_START] room ${roomCode}`);
    io.to(roomCode).emit('m1_started', {
        targetScore: CONFIG.M1_TARGET_SCORE,
        maxQuestions: CONFIG.M1_MAX_QUESTIONS,
        questionTime: CONFIG.M1_QUESTION_TIME
    });
    setTimeout(() => nextM1Question(roomCode), 1500);
}

function nextM1Question(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m1 || room.m1.finished) return;
    room.m1.questionIndex++;
    if (room.m1.questionIndex > CONFIG.M1_MAX_QUESTIONS) {
        endM1(roomCode, 'max_questions');
        return;
    }
    const q = pickRandomQuestion(room.m1.usedQuestionIds);
    if (!q) { endM1(roomCode, 'no_more_questions'); return; }
    room.m1.usedQuestionIds.push(q.id);
    room.m1.currentQuestion = q;
    room.m1.answers = {};
    room.m1.questionDeadline = Date.now() + CONFIG.M1_QUESTION_TIME;
    console.log(`[M1_Q${room.m1.questionIndex}] room ${roomCode} : Q#${q.id} (${q.cat})`);
    io.to(roomCode).emit('m1_question', {
        index: room.m1.questionIndex, total: CONFIG.M1_MAX_QUESTIONS,
        category: q.cat, question: q.q, options: q.options,
        deadline: room.m1.questionDeadline, time: CONFIG.M1_QUESTION_TIME
    });
    if (room.m1.questionTimer) clearTimeout(room.m1.questionTimer);
    room.m1.questionTimer = setTimeout(() => revealM1Question(roomCode, 'timeout'), CONFIG.M1_QUESTION_TIME);
}

function handleM1Answer(socket, { answer }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || room.status !== 'm1' || !room.m1.currentQuestion) return;
    if (room.m1.answers[playerId]) return;
    if (typeof answer !== 'number' || answer < 0 || answer > 3) return;
    const player = room.players[playerId];
    if (!player || !player.alive) return;
    room.m1.answers[playerId] = { answer, time: Date.now() };
    io.to(roomCode).emit('m1_player_answered', {
        playerId,
        answeredCount: Object.keys(room.m1.answers).length,
        totalAlive: Object.values(room.players).filter(p => p.alive).length
    });
    const alivePlayers = Object.entries(room.players).filter(([id, p]) => p.alive);
    if (alivePlayers.every(([id]) => room.m1.answers[id])) {
        revealM1Question(roomCode, 'all_answered');
    }
}

function revealM1Question(roomCode, reason) {
    const room = rooms[roomCode];
    if (!room || !room.m1.currentQuestion) return;
    if (room.m1.questionTimer) clearTimeout(room.m1.questionTimer);
    const q = room.m1.currentQuestion;
    const results = {};
    Object.entries(room.players).forEach(([id, player]) => {
        if (!player.alive) return;
        const ans = room.m1.answers[id];
        let delta = 0, correct = null, chosenAnswer = null;
        if (ans) {
            chosenAnswer = ans.answer;
            correct = (ans.answer === q.answer);
            delta = correct ? +1 : -1;
        }
        player.score += delta;
        results[id] = { answer: chosenAnswer, correct, delta, newScore: player.score };
    });
    console.log(`[M1_REVEAL] room ${roomCode} (${reason})`);
    io.to(roomCode).emit('m1_reveal', {
        correctAnswer: q.answer, results,
        scores: Object.entries(room.players).map(([id, p]) => ({ id, score: p.score, alive: p.alive }))
    });
    const targetReached = Object.values(room.players).some(p => p.alive && p.score >= CONFIG.M1_TARGET_SCORE);
    const isLastQuestion = room.m1.questionIndex >= CONFIG.M1_MAX_QUESTIONS;
    if (targetReached || isLastQuestion) {
        room.m1.revealTimer = setTimeout(() => endM1(roomCode, targetReached ? 'target_reached' : 'max_questions'), CONFIG.M1_REVEAL_TIME);
    } else {
        room.m1.revealTimer = setTimeout(() => nextM1Question(roomCode), CONFIG.M1_REVEAL_TIME + CONFIG.INTER_QUESTION_GAP);
    }
}

function endM1(roomCode, reason) {
    const room = rooms[roomCode];
    if (!room || room.m1.finished) return;
    room.m1.finished = true;
    clearAllTimers(room);
    const alivePlayers = Object.entries(room.players)
        .filter(([id, p]) => p.alive)
        .map(([id, p]) => ({ id, name: p.name, score: p.score }));
    alivePlayers.sort((a, b) => b.score - a.score);
    const lastScore = alivePlayers[alivePlayers.length - 1].score;
    const lastPlace = alivePlayers.filter(p => p.score === lastScore);
    console.log(`[M1_END] room ${roomCode} (${reason})`);
    if (lastPlace.length === 1) {
        finalizeM1(roomCode, alivePlayers, lastPlace[0].id);
        return;
    }
    console.log(`[M1_TIEBREAK] ${lastPlace.length} ex aequo`);
    startM1Tiebreak(roomCode, lastPlace.map(p => p.id), alivePlayers);
}

function startM1Tiebreak(roomCode, contenderIds, finalRanking) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'm1_tiebreak';
    room.m1.tiebreak = { contenderIds, finalRanking, currentQuestion: null, answers: {}, timer: null, round: 0 };
    io.to(roomCode).emit('m1_tiebreak_start', {
        contenderIds, contenderNames: contenderIds.map(id => room.players[id].name)
    });
    setTimeout(() => nextTiebreakQuestion(roomCode), 2000);
}

function nextTiebreakQuestion(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m1.tiebreak) return;
    room.m1.tiebreak.round++;
    const q = pickRandomQuestion(room.m1.usedQuestionIds);
    if (!q) {
        const eliminated = room.m1.tiebreak.contenderIds[Math.floor(Math.random() * room.m1.tiebreak.contenderIds.length)];
        finalizeM1(roomCode, room.m1.tiebreak.finalRanking, eliminated);
        return;
    }
    room.m1.usedQuestionIds.push(q.id);
    room.m1.tiebreak.currentQuestion = q;
    room.m1.tiebreak.answers = {};
    io.to(roomCode).emit('m1_tiebreak_question', {
        round: room.m1.tiebreak.round, category: q.cat, question: q.q, options: q.options,
        time: CONFIG.M1_TIEBREAK_TIME, deadline: Date.now() + CONFIG.M1_TIEBREAK_TIME,
        contenderIds: room.m1.tiebreak.contenderIds
    });
    if (room.m1.tiebreak.timer) clearTimeout(room.m1.tiebreak.timer);
    room.m1.tiebreak.timer = setTimeout(() => resolveTiebreakQuestion(roomCode), CONFIG.M1_TIEBREAK_TIME);
}

function handleM1TiebreakAnswer(socket, { answer }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || room.status !== 'm1_tiebreak') return;
    if (!room.m1.tiebreak.contenderIds.includes(playerId)) return;
    if (room.m1.tiebreak.answers[playerId]) return;
    if (typeof answer !== 'number' || answer < 0 || answer > 3) return;
    room.m1.tiebreak.answers[playerId] = { answer, time: Date.now() };
    io.to(roomCode).emit('m1_tiebreak_answered', {
        playerId, answeredCount: Object.keys(room.m1.tiebreak.answers).length,
        totalContenders: room.m1.tiebreak.contenderIds.length
    });
    if (room.m1.tiebreak.contenderIds.every(id => room.m1.tiebreak.answers[id])) {
        if (room.m1.tiebreak.timer) clearTimeout(room.m1.tiebreak.timer);
        resolveTiebreakQuestion(roomCode);
    }
}

function resolveTiebreakQuestion(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m1.tiebreak) return;
    const tb = room.m1.tiebreak;
    const q = tb.currentQuestion;
    if (!q) return;
    const correctIds = [], wrongIds = [];
    tb.contenderIds.forEach(id => {
        const ans = tb.answers[id];
        if (ans && ans.answer === q.answer) correctIds.push(id);
        else wrongIds.push(id);
    });
    io.to(roomCode).emit('m1_tiebreak_reveal', { correctAnswer: q.answer, correctIds, wrongIds, answers: tb.answers });
    setTimeout(() => {
        if (correctIds.length === 0 || wrongIds.length === 0) nextTiebreakQuestion(roomCode);
        else if (wrongIds.length === 1) finalizeM1(roomCode, tb.finalRanking, wrongIds[0]);
        else { tb.contenderIds = wrongIds; nextTiebreakQuestion(roomCode); }
    }, CONFIG.M1_REVEAL_TIME);
}

function finalizeM1(roomCode, ranking, eliminatedId) {
    const room = rooms[roomCode];
    if (!room) return;
    room.players[eliminatedId].alive = false;
    const finalRanking = ranking
        .filter(p => p.id !== eliminatedId)
        .concat(ranking.filter(p => p.id === eliminatedId));
    const winner = finalRanking[0];
    console.log(`[M1_FINALIZED] room ${roomCode} : elimine = ${room.players[eliminatedId].name}, gagnant = ${winner.name}`);
    room.status = 'm1_finished';
    // On stocke le ranking pour la suite
    room.m1Ranking = finalRanking.map(p => ({ id: p.id, score: p.score }));
    io.to(roomCode).emit('m1_finished', {
        ranking: finalRanking.map(p => ({
            id: p.id, name: room.players[p.id].name, score: p.score,
            alive: room.players[p.id].alive
        })),
        eliminatedId, winnerId: winner.id,
        message: `Manche 1 terminée. ${room.players[eliminatedId].name} est éliminé. M3 dans 5 secondes…`
    });
    broadcastRoom(roomCode);

    // [PLACEHOLDER M2] : On simule la fin de M2 en eliminant le 3eme
    // → seuls les 2 meilleurs (rang 1 et 2) passent en M3
    setTimeout(() => startM2(roomCode), CONFIG.M1_END_TO_M3_DELAY);
}

// ============================================================================
//  TRANSITION M1 -> M2 (la vraie Manche 2 maintenant !)
// ============================================================================
function startM2(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;

    // Identifier les 3 alive (qualifies de M1)
    const alive = Object.entries(room.players)
        .filter(([id, p]) => p.alive)
        .map(([id, p]) => ({ id, name: p.name, score: p.score }));
    alive.sort((a, b) => b.score - a.score);

    if (alive.length < 3) {
        console.error(`[M2_START] room ${roomCode} : moins de 3 joueurs alive !`);
        // Si moins de 3 (cas dev), on saute direct en M3 avec ceux qui restent
        const finalists = alive.slice(0, 2).map(p => p.id);
        if (finalists.length === 2) startM3(roomCode, finalists);
        return;
    }

    const m2PlayersIds = alive.slice(0, 3).map(p => p.id);

    room.status = 'm2_categories';
    room.m2 = {
        playersIds: m2PlayersIds,
        playersNames: m2PlayersIds.map(id => room.players[id].name),
        categorySelections: {},      // { idA: [4 cats], idB: [4 cats], idC: [4 cats] }
        categoryPool: [],            // 4 cats tirees au sort
        bets: {},                    // { idA: { questionIndex, amount }, ... }
        usedQuestionIds: [...(room.m1?.usedQuestionIds || [])],
        questionIndex: 0,
        currentQuestion: null,
        buzzerId: null,
        answeredBy: [],
        buzzOpen: false,
        questionDeadline: null,
        timeRemainingOnBuzz: 0,
        phaseTimer: null,
        buzzTimer: null,
        finished: false
    };

    // Reset des scores M2 pour les 3 joueurs
    m2PlayersIds.forEach(id => {
        room.players[id].m2Score = 0;
    });

    console.log(`[M2_START] room ${roomCode} : ${room.m2.playersNames.join(', ')}`);
    io.to(roomCode).emit('m2_started', {
        playersIds: m2PlayersIds,
        playersNames: room.m2.playersNames,
        availableCategories: questionsData.categories,
        selectionTime: CONFIG.M2_CATEGORY_SELECTION_TIME,
        totalQuestions: CONFIG.M2_TOTAL_QUESTIONS
    });

    if (room.m2.phaseTimer) clearTimeout(room.m2.phaseTimer);
    room.m2.phaseTimer = setTimeout(() => forceCloseM2CategorySelection(roomCode), CONFIG.M2_CATEGORY_SELECTION_TIME);
}

function handleM2CategorySelection(socket, { categories }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || room.status !== 'm2_categories') return;
    if (!room.m2.playersIds.includes(playerId)) return;
    if (!Array.isArray(categories) || categories.length !== 4) return;
    if (!categories.every(c => questionsData.categories.includes(c))) return;
    if (new Set(categories).size !== 4) return;

    room.m2.categorySelections[playerId] = categories;
    console.log(`[M2_CATS] ${room.players[playerId].name} -> ${categories.join(', ')}`);

    io.to(roomCode).emit('m2_player_selected_categories', {
        playerId, selectedCount: Object.keys(room.m2.categorySelections).length
    });

    if (Object.keys(room.m2.categorySelections).length === 3) {
        if (room.m2.phaseTimer) clearTimeout(room.m2.phaseTimer);
        finishM2CategorySelection(roomCode);
    }
}

function forceCloseM2CategorySelection(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    room.m2.playersIds.forEach(id => {
        if (!room.m2.categorySelections[id]) {
            const shuffled = [...questionsData.categories].sort(() => Math.random() - 0.5);
            room.m2.categorySelections[id] = shuffled.slice(0, 4);
            console.log(`[M2_CATS_AUTO] ${room.players[id].name} -> ${room.m2.categorySelections[id].join(', ')}`);
        }
    });
    finishM2CategorySelection(roomCode);
}

function finishM2CategorySelection(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;

    // Pool = union des 12 catégories selectionnées (4x3), dedup, on tire 4 distinctes
    const allCats = room.m2.playersIds.flatMap(id => room.m2.categorySelections[id]);
    const uniqueCats = [...new Set(allCats)];
    const shuffled = uniqueCats.sort(() => Math.random() - 0.5);
    room.m2.categoryPool = shuffled.slice(0, Math.min(4, shuffled.length));
    while (room.m2.categoryPool.length < 4) {
        const remaining = questionsData.categories.filter(c => !room.m2.categoryPool.includes(c));
        if (remaining.length === 0) break;
        room.m2.categoryPool.push(remaining[Math.floor(Math.random() * remaining.length)]);
    }

    console.log(`[M2_POOL] ${roomCode} : ${room.m2.categoryPool.join(', ')}`);
    room.status = 'm2_bet';
    io.to(roomCode).emit('m2_category_pool', {
        pool: room.m2.categoryPool,
        selections: room.m2.categorySelections
    });

    if (room.m2.phaseTimer) clearTimeout(room.m2.phaseTimer);
    room.m2.phaseTimer = setTimeout(() => forceCloseM2BetPhase(roomCode), CONFIG.M2_BET_TIME);

    io.to(roomCode).emit('m2_bet_phase', {
        time: CONFIG.M2_BET_TIME,
        deadline: Date.now() + CONFIG.M2_BET_TIME,
        totalQuestions: CONFIG.M2_TOTAL_QUESTIONS,
        possibleAmounts: [1, 2, 3]
    });
}

function handleM2Bet(socket, { questionIndex, amount }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || room.status !== 'm2_bet') return;
    if (!room.m2.playersIds.includes(playerId)) return;
    if (typeof questionIndex !== 'number' || questionIndex < 1 || questionIndex > CONFIG.M2_TOTAL_QUESTIONS) return;
    if (![1, 2, 3].includes(amount)) return;

    room.m2.bets[playerId] = { questionIndex, amount };
    console.log(`[M2_BET] ${room.players[playerId].name} mise ${amount} pts sur Q${questionIndex}`);

    io.to(roomCode).emit('m2_player_bet', {
        playerId, betCount: Object.keys(room.m2.bets).length
    });

    if (Object.keys(room.m2.bets).length === 3) {
        if (room.m2.phaseTimer) clearTimeout(room.m2.phaseTimer);
        startM2Questions(roomCode);
    }
}

function forceCloseM2BetPhase(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    room.m2.playersIds.forEach(id => {
        if (!room.m2.bets[id]) {
            room.m2.bets[id] = { questionIndex: 0, amount: 0 };
            console.log(`[M2_BET_NONE] ${room.players[id].name} n'a pas parie`);
        }
    });
    startM2Questions(roomCode);
}

function startM2Questions(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'm2_duel';
    io.to(roomCode).emit('m2_bets_locked', {
        message: 'Mises verrouillees. Le duel a 3 commence !'
    });
    setTimeout(() => nextM2Question(roomCode), 2000);
}

function nextM2Question(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m2 || room.m2.finished) return;
    room.m2.questionIndex++;

    if (room.m2.questionIndex > CONFIG.M2_TOTAL_QUESTIONS) {
        endM2(roomCode, 'all_questions_done');
        return;
    }

    const q = pickRandomQuestion(room.m2.usedQuestionIds, room.m2.categoryPool);
    if (!q) {
        endM2(roomCode, 'no_more_questions');
        return;
    }
    room.m2.usedQuestionIds.push(q.id);
    room.m2.currentQuestion = q;
    room.m2.answers = {};  // [E.1 PARALLELE] { playerId: { answer, time } }

    const bets = {};
    room.m2.playersIds.forEach(id => {
        if (room.m2.bets[id]?.questionIndex === room.m2.questionIndex && room.m2.bets[id].amount > 0) {
            bets[id] = room.m2.bets[id].amount;
        }
    });

    console.log(`[M2_Q${room.m2.questionIndex}] room ${roomCode} : Q#${q.id} (${q.cat})${Object.keys(bets).length > 0 ? ' BETS!' : ''}`);

    room.m2.questionDeadline = Date.now() + CONFIG.M2_QUESTION_TIME;

    // [PARALLELE] Tout en un coup : question + options visibles direct, comme M1
    io.to(roomCode).emit('m2_question', {
        index: room.m2.questionIndex,
        total: CONFIG.M2_TOTAL_QUESTIONS,
        category: q.cat,
        question: q.q,
        options: q.options,
        deadline: room.m2.questionDeadline,
        time: CONFIG.M2_QUESTION_TIME,
        bets
    });

    if (room.m2.phaseTimer) clearTimeout(room.m2.phaseTimer);
    room.m2.phaseTimer = setTimeout(() => revealM2Question(roomCode, 'timeout'), CONFIG.M2_QUESTION_TIME);
}

// [PARALLELE] Handler pour la reponse d'un joueur (style M1)
function handleM2Answer(socket, { answer }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || (room.status !== 'm2_duel' && room.status !== 'm2_tiebreak')) return;
    if (!room.m2.playersIds.includes(playerId)) {
        // En tiebreak, seuls les contenders peuvent repondre
        if (room.status === 'm2_tiebreak' && !room.m2.tiebreak?.contenderIds.includes(playerId)) return;
        if (room.status !== 'm2_tiebreak') return;
    }
    if (room.m2.answers[playerId]) return; // deja repondu
    if (typeof answer !== 'number' || answer < 0 || answer > 3) return;

    room.m2.answers[playerId] = { answer, time: Date.now() };
    console.log(`[M2_ANSWER] ${room.players[playerId].name} -> opt ${answer}`);

    // Notifier les autres (pastilles ✓)
    const expected = (room.status === 'm2_tiebreak') ? room.m2.tiebreak.contenderIds : room.m2.playersIds;
    io.to(roomCode).emit('m2_player_answered', {
        playerId,
        answeredCount: Object.keys(room.m2.answers).length,
        totalPlayers: expected.length
    });

    // Si tous ont répondu, on révèle direct
    if (expected.every(id => room.m2.answers[id])) {
        revealM2Question(roomCode, 'all_answered');
    }
}

function revealM2Question(roomCode, reason) {
    const room = rooms[roomCode];
    if (!room || !room.m2.currentQuestion) return;
    if (room.m2.phaseTimer) clearTimeout(room.m2.phaseTimer);

    const q = room.m2.currentQuestion;
    const results = {};

    // [PARALLELE] Calcul scores pour TOUS les joueurs (pas seulement le buzzer)
    const expected = (room.status === 'm2_tiebreak') ? room.m2.tiebreak.contenderIds : room.m2.playersIds;

    expected.forEach(id => {
        const ans = room.m2.answers[id];
        let delta = 0, correct = null, chosen = null, betAmount = 0;
        if (ans) {
            chosen = ans.answer;
            correct = (ans.answer === q.answer);
            delta = correct ? +1 : -1;
            // Appliquer le pari si la question correspond
            const bet = room.m2.bets[id];
            if (bet?.questionIndex === room.m2.questionIndex && bet.amount > 0) {
                betAmount = bet.amount;
                delta += correct ? betAmount : -betAmount;
            }
        }
        // En tiebreak on n'applique pas les scores (juste pour départager)
        if (room.status !== 'm2_tiebreak') {
            room.players[id].m2Score += delta;
        }
        results[id] = {
            answer: chosen, correct, delta, betAmount,
            newScore: room.players[id].m2Score
        };
    });

    console.log(`[M2_REVEAL] room ${roomCode} (${reason})`);
    Object.entries(results).forEach(([id, r]) => {
        const ans = r.answer !== null ? `opt ${r.answer}` : 'timeout';
        const ok = r.correct === true ? '✓' : r.correct === false ? '✗' : '-';
        console.log(`  - ${room.players[id].name}: ${ans} ${ok} (${r.delta >= 0 ? '+' : ''}${r.delta}) → ${r.newScore}`);
    });

    io.to(roomCode).emit('m2_reveal', {
        correctAnswer: q.answer,
        results,
        scores: room.m2.playersIds.map(id => ({ id, score: room.players[id].m2Score }))
    });

    // En tiebreak : on gere la logique d'elimination
    if (room.status === 'm2_tiebreak') {
        setTimeout(() => resolveM2Tiebreak(roomCode), CONFIG.M3_REVEAL_TIME);
        return;
    }

    // Sinon : on enchaine
    const allQuestionsDone = room.m2.questionIndex >= CONFIG.M2_TOTAL_QUESTIONS;
    if (allQuestionsDone) {
        setTimeout(() => endM2(roomCode, 'all_questions'), CONFIG.M3_REVEAL_TIME);
    } else {
        setTimeout(() => nextM2Question(roomCode), CONFIG.M3_REVEAL_TIME + CONFIG.INTER_QUESTION_GAP);
    }
}

function endM2(roomCode, reason) {
    const room = rooms[roomCode];
    if (!room || !room.m2 || room.m2.finished) return;
    room.m2.finished = true;
    clearAllTimers(room);

    const scores = room.m2.playersIds.map(id => ({
        id, name: room.players[id].name, score: room.players[id].m2Score
    }));
    scores.sort((a, b) => b.score - a.score);

    const lastScore = scores[scores.length - 1].score;
    const lastPlace = scores.filter(p => p.score === lastScore);

    console.log(`[M2_END] room ${roomCode} (${reason})`);
    scores.forEach((p, i) => console.log(`  ${i + 1}. ${p.name} : ${p.score}`));

    // Si un seul dernier : il est elimine, on passe en M3 avec les 2 autres
    if (lastPlace.length === 1) {
        finalizeM2(roomCode, scores, lastPlace[0].id);
        return;
    }

    // Egalite au dernier rang : mort subite pour departager
    console.log(`[M2_TIEBREAK] ${lastPlace.length} ex aequo a ${lastScore} pts`);
    startM2Tiebreak(roomCode, lastPlace.map(p => p.id), scores);
}

function startM2Tiebreak(roomCode, contenderIds, finalRanking) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'm2_tiebreak';
    room.m2.tiebreak = { contenderIds, finalRanking, round: 0 };
    io.to(roomCode).emit('m2_tiebreak_start', {
        contenderIds,
        contenderNames: contenderIds.map(id => room.players[id].name)
    });
    setTimeout(() => nextM2TiebreakQuestion(roomCode), 2000);
}

function nextM2TiebreakQuestion(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m2.tiebreak) return;
    room.m2.tiebreak.round++;
    const q = pickRandomQuestion(room.m2.usedQuestionIds);
    if (!q) {
        const eliminated = room.m2.tiebreak.contenderIds[Math.floor(Math.random() * room.m2.tiebreak.contenderIds.length)];
        finalizeM2(roomCode, room.m2.tiebreak.finalRanking, eliminated);
        return;
    }
    room.m2.usedQuestionIds.push(q.id);
    room.m2.currentQuestion = q;
    room.m2.answers = {};

    console.log(`[M2_TB_Q${room.m2.tiebreak.round}] room ${roomCode} : Q#${q.id}`);

    room.m2.questionDeadline = Date.now() + CONFIG.M2_QUESTION_TIME;

    // [PARALLELE] Tiebreak en mode Kahoot : tout le monde voit, contenders peuvent répondre
    io.to(roomCode).emit('m2_tiebreak_question', {
        round: room.m2.tiebreak.round,
        category: q.cat,
        question: q.q,
        options: q.options,
        deadline: room.m2.questionDeadline,
        time: CONFIG.M2_QUESTION_TIME,
        contenderIds: room.m2.tiebreak.contenderIds
    });

    if (room.m2.phaseTimer) clearTimeout(room.m2.phaseTimer);
    room.m2.phaseTimer = setTimeout(() => revealM2Question(roomCode, 'tiebreak_timeout'), CONFIG.M2_QUESTION_TIME);
}

// Resolution du tiebreak : qui a juste / qui a faux
function resolveM2Tiebreak(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m2.tiebreak) return;
    const tb = room.m2.tiebreak;
    const q = room.m2.currentQuestion;

    const correctIds = [];
    const wrongIds = [];
    tb.contenderIds.forEach(id => {
        const ans = room.m2.answers[id];
        if (ans && ans.answer === q.answer) correctIds.push(id);
        else wrongIds.push(id);
    });

    console.log(`[M2_TB_RESOLVE] correct=${correctIds.length}, wrong=${wrongIds.length}`);

    // Logique d'elimination tiebreak :
    // - Tous corrects ou tous faux : on rejoue
    // - Mixte : les wrongs restent en course, sauf si 1 seul wrong → il est elimine
    if (correctIds.length === 0 || wrongIds.length === 0) {
        // Tous sur le meme bord, on relance
        setTimeout(() => nextM2TiebreakQuestion(roomCode), CONFIG.INTER_QUESTION_GAP);
    } else if (wrongIds.length === 1) {
        // Un seul rate : il est elimine
        finalizeM2(roomCode, tb.finalRanking, wrongIds[0]);
    } else {
        // Plusieurs rates : on continue avec eux
        tb.contenderIds = wrongIds;
        setTimeout(() => nextM2TiebreakQuestion(roomCode), CONFIG.INTER_QUESTION_GAP);
    }
}

function finalizeM2(roomCode, ranking, eliminatedId) {
    const room = rooms[roomCode];
    if (!room) return;
    room.players[eliminatedId].alive = false;
    const finalRanking = ranking
        .filter(p => p.id !== eliminatedId)
        .concat(ranking.filter(p => p.id === eliminatedId));
    const finalistsIds = finalRanking.slice(0, 2).map(p => p.id);

    console.log(`[M2_FINALIZED] room ${roomCode} : elimine = ${room.players[eliminatedId].name}, finalistes = ${finalistsIds.map(id => room.players[id].name).join(' & ')}`);

    room.status = 'm2_finished';
    io.to(roomCode).emit('m2_finished', {
        ranking: finalRanking.map(p => ({
            id: p.id, name: room.players[p.id].name, score: p.score,
            alive: room.players[p.id].alive
        })),
        eliminatedId,
        finalistsIds,
        finalistsNames: finalistsIds.map(id => room.players[id].name),
        message: `Manche 2 terminée. ${room.players[eliminatedId].name} est éliminé. M3 dans 4s…`
    });
    broadcastRoom(roomCode);

    setTimeout(() => startM3(roomCode, finalistsIds), 4000);
}

// ============================================================================
//  MANCHE 3 : FACE-A-FACE
// ============================================================================
function startM3(roomCode, finalistsIds) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'm3_categories';

    // Reset des scores des finalistes pour M3 (le score M1 ne s'additionne pas)
    finalistsIds.forEach(id => {
        room.players[id].m3Score = 0;
    });

    room.m3 = {
        finalistsIds,                  // [idA, idB]
        finalistsNames: finalistsIds.map(id => room.players[id].name),
        categorySelections: {},        // { idA: [cat1, cat2, cat3, cat4], idB: [...] }
        categoryPool: [],              // 4 categories tirees au sort apres selection
        bets: {},                      // { idA: { questionIndex, amount }, idB: {...} }
        usedQuestionIds: [...(room.m1?.usedQuestionIds || [])], // pas de doublons avec M1
        questionIndex: 0,
        currentQuestion: null,
        currentQuestionCategory: null,
        buzzerId: null,                // qui a buzze actuellement
        answeredBy: [],                // [idA, idB?] qui ont deja buzze sur cette Q (pour eviter double buzz)
        questionDeadline: null,        // timer global
        timeRemainingOnBuzz: 0,        // pour mettre en pause/reprendre le timer global
        phaseTimer: null,
        buzzTimer: null,
        revealTimer: null,
        suddenTimer: null,
        suddenDeath: false,
        suddenRound: 0,
        finished: false
    };

    console.log(`[M3_START] room ${roomCode}`);
    io.to(roomCode).emit('m3_started', {
        finalistsIds, finalistsNames: room.m3.finalistsNames,
        availableCategories: questionsData.categories,
        selectionTime: CONFIG.M3_CATEGORY_SELECTION_TIME
    });

    // Lancer phase categories
    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => forceCloseCategorySelection(roomCode), CONFIG.M3_CATEGORY_SELECTION_TIME);
}

function handleM3CategorySelection(socket, { categories }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || room.status !== 'm3_categories') return;
    if (!room.m3.finalistsIds.includes(playerId)) return;
    if (!Array.isArray(categories) || categories.length !== 4) return;
    // Valider que toutes les categories existent
    if (!categories.every(c => questionsData.categories.includes(c))) return;
    // Pas de doublons
    if (new Set(categories).size !== 4) return;

    room.m3.categorySelections[playerId] = categories;
    console.log(`[M3_CATS] ${room.players[playerId].name} -> ${categories.join(', ')}`);

    io.to(roomCode).emit('m3_player_selected_categories', {
        playerId, selectedCount: Object.keys(room.m3.categorySelections).length
    });

    if (Object.keys(room.m3.categorySelections).length === 2) {
        if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
        finishCategorySelection(roomCode);
    }
}

function forceCloseCategorySelection(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    // Pour les joueurs qui n'ont pas selectionne : tirage aleatoire de 4 categories
    room.m3.finalistsIds.forEach(id => {
        if (!room.m3.categorySelections[id]) {
            const shuffled = [...questionsData.categories].sort(() => Math.random() - 0.5);
            room.m3.categorySelections[id] = shuffled.slice(0, 4);
            console.log(`[M3_CATS_AUTO] ${room.players[id].name} -> ${room.m3.categorySelections[id].join(', ')}`);
        }
    });
    finishCategorySelection(roomCode);
}

function finishCategorySelection(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;

    // Pool = union des 8 catégories selectionnées (4+4)
    const allCats = [
        ...room.m3.categorySelections[room.m3.finalistsIds[0]],
        ...room.m3.categorySelections[room.m3.finalistsIds[1]]
    ];
    // Le pool peut avoir des doublons (si les 2 joueurs ont choisi la meme cat)
    // On garde les doublons pour donner plus de poids aux choix communs
    // Mais on tire 4 categories distinctes
    const uniqueCats = [...new Set(allCats)];
    const shuffled = uniqueCats.sort(() => Math.random() - 0.5);
    room.m3.categoryPool = shuffled.slice(0, Math.min(4, shuffled.length));
    // S'il n'y a moins de 4 cats uniques, on complete avec d'autres au hasard
    while (room.m3.categoryPool.length < 4) {
        const remaining = questionsData.categories.filter(c => !room.m3.categoryPool.includes(c));
        if (remaining.length === 0) break;
        room.m3.categoryPool.push(remaining[Math.floor(Math.random() * remaining.length)]);
    }

    console.log(`[M3_POOL] ${roomCode} : ${room.m3.categoryPool.join(', ')}`);
    room.status = 'm3_bet';
    io.to(roomCode).emit('m3_category_pool', {
        pool: room.m3.categoryPool,
        selections: room.m3.categorySelections,
        message: 'Phase de mise : choisis sur quelle question parier.'
    });

    // Phase de pari
    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => forceCloseBetPhase(roomCode), CONFIG.M3_BET_TIME);

    io.to(roomCode).emit('m3_bet_phase', {
        time: CONFIG.M3_BET_TIME,
        deadline: Date.now() + CONFIG.M3_BET_TIME,
        totalQuestions: CONFIG.M3_TOTAL_QUESTIONS,
        possibleAmounts: [1, 2, 3]
    });
}

function handleM3Bet(socket, { questionIndex, amount }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || room.status !== 'm3_bet') return;
    if (!room.m3.finalistsIds.includes(playerId)) return;
    if (typeof questionIndex !== 'number' || questionIndex < 1 || questionIndex > CONFIG.M3_TOTAL_QUESTIONS) return;
    if (![1, 2, 3].includes(amount)) return;

    room.m3.bets[playerId] = { questionIndex, amount };
    console.log(`[M3_BET] ${room.players[playerId].name} mise ${amount} pts sur Q${questionIndex}`);

    io.to(roomCode).emit('m3_player_bet', {
        playerId, betCount: Object.keys(room.m3.bets).length
    });

    if (Object.keys(room.m3.bets).length === 2) {
        if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
        startM3Questions(roomCode);
    }
}

function forceCloseBetPhase(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    // Pour ceux qui n'ont pas parie : aucune mise (= mise 0)
    room.m3.finalistsIds.forEach(id => {
        if (!room.m3.bets[id]) {
            room.m3.bets[id] = { questionIndex: 0, amount: 0 }; // 0 = pas de pari
            console.log(`[M3_BET_NONE] ${room.players[id].name} n'a pas parie`);
        }
    });
    startM3Questions(roomCode);
}

function startM3Questions(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'm3_duel';
    io.to(roomCode).emit('m3_bets_locked', {
        message: 'Mises verrouillees. Le duel commence !'
    });
    setTimeout(() => nextM3Question(roomCode), 2000);
}

function nextM3Question(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || room.m3.finished) return;
    room.m3.questionIndex++;

    if (room.m3.questionIndex > CONFIG.M3_TOTAL_QUESTIONS) {
        // Toutes les questions jouees : on conclut
        endM3(roomCode, 'all_questions_done');
        return;
    }

    // Tirer une question dans le pool
    const q = pickRandomQuestion(room.m3.usedQuestionIds, room.m3.categoryPool);
    if (!q) {
        endM3(roomCode, 'no_more_questions');
        return;
    }
    room.m3.usedQuestionIds.push(q.id);
    room.m3.currentQuestion = q;
    room.m3.currentQuestionCategory = q.cat;
    room.m3.buzzerId = null;
    room.m3.answeredBy = [];
    room.m3.buzzOpen = false; // [D.1] flag : buzzer pas encore actif
    room.m3.timeRemainingOnBuzz = CONFIG.M3_QUESTION_TIME;

    // Y a-t-il un pari sur cette question ?
    const bets = {};
    room.m3.finalistsIds.forEach(id => {
        if (room.m3.bets[id]?.questionIndex === room.m3.questionIndex && room.m3.bets[id].amount > 0) {
            bets[id] = room.m3.bets[id].amount;
        }
    });

    console.log(`[M3_Q${room.m3.questionIndex}] room ${roomCode} : Q#${q.id} (${q.cat})${Object.keys(bets).length > 0 ? ' BETS!' : ''}`);

    // [D.1] PHASE 1 : question seule (sans options) pendant M3_READING_TIME
    io.to(roomCode).emit('m3_question_reading', {
        index: room.m3.questionIndex,
        total: CONFIG.M3_TOTAL_QUESTIONS,
        category: q.cat,
        question: q.q,
        readingTime: CONFIG.M3_READING_TIME,
        optionsRevealTime: CONFIG.M3_OPTIONS_REVEAL_TIME,
        bets
    });

    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => revealM3Options(roomCode), CONFIG.M3_READING_TIME);
}

// [D.1] PHASE 2 : on revele les options, buzzer encore inactif
function revealM3Options(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || !room.m3.currentQuestion) return;
    const q = room.m3.currentQuestion;
    io.to(roomCode).emit('m3_options_revealed', {
        options: q.options,
        optionsRevealTime: CONFIG.M3_OPTIONS_REVEAL_TIME
    });
    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => openM3Buzz(roomCode), CONFIG.M3_OPTIONS_REVEAL_TIME);
}

// [D.1] PHASE 3 : on ouvre le buzz et on demarre le timer global
function openM3Buzz(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || !room.m3.currentQuestion) return;
    room.m3.buzzOpen = true;
    room.m3.questionDeadline = Date.now() + CONFIG.M3_QUESTION_TIME;
    room.m3.timeRemainingOnBuzz = CONFIG.M3_QUESTION_TIME;
    io.to(roomCode).emit('m3_buzz_open', {
        deadline: room.m3.questionDeadline,
        time: CONFIG.M3_QUESTION_TIME
    });
    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => closeM3QuestionTimeout(roomCode), CONFIG.M3_QUESTION_TIME);
}

function handleM3Buzz(socket) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || (room.status !== 'm3_duel' && room.status !== 'm3_sudden_death')) return;
    if (!room.m3.finalistsIds.includes(playerId)) return;
    if (!room.m3.buzzOpen) return; // [D.1] buzz pas encore ouvert
    if (room.m3.buzzerId !== null) return; // quelqu'un a deja buzze
    if (room.m3.answeredBy.includes(playerId)) return; // ce joueur a deja buzze sur cette Q

    // Verrouiller le buzz et mettre en pause le timer global
    room.m3.buzzerId = playerId;
    room.m3.timeRemainingOnBuzz = Math.max(0, room.m3.questionDeadline - Date.now());

    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);

    console.log(`[M3_BUZZ] ${room.players[playerId].name} buzze ! (temps restant : ${Math.ceil(room.m3.timeRemainingOnBuzz / 1000)}s)`);

    io.to(roomCode).emit('m3_buzz', {
        playerId,
        buzzResponseTime: CONFIG.M3_BUZZ_RESPONSE_TIME,
        buzzDeadline: Date.now() + CONFIG.M3_BUZZ_RESPONSE_TIME
    });

    // Timer de reponse au buzz
    if (room.m3.buzzTimer) clearTimeout(room.m3.buzzTimer);
    room.m3.buzzTimer = setTimeout(() => handleM3BuzzTimeout(roomCode), CONFIG.M3_BUZZ_RESPONSE_TIME);
}

function handleM3BuzzAnswer(socket, { answer }) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room || (room.status !== 'm3_duel' && room.status !== 'm3_sudden_death')) return;
    if (room.m3.buzzerId !== playerId) return; // seul le buzzer peut repondre
    if (typeof answer !== 'number' || answer < 0 || answer > 3) return;

    if (room.m3.buzzTimer) clearTimeout(room.m3.buzzTimer);

    const q = room.m3.currentQuestion;
    const correct = (answer === q.answer);
    const bet = room.m3.bets[playerId];
    const isBettedQuestion = bet?.questionIndex === room.m3.questionIndex && bet.amount > 0;
    const betAmount = isBettedQuestion ? bet.amount : 0;

    let delta = correct ? +1 : -1;
    if (isBettedQuestion) delta += correct ? betAmount : -betAmount;

    room.players[playerId].m3Score += delta;
    room.m3.answeredBy.push(playerId);

    console.log(`[M3_ANSWER] ${room.players[playerId].name} -> opt ${answer} ${correct ? '✓' : '✗'} (${delta >= 0 ? '+' : ''}${delta})`);

    io.to(roomCode).emit('m3_buzz_result', {
        playerId, answer, correct, delta, betAmount,
        newScore: room.players[playerId].m3Score
    });

    if (correct) {
        // Bonne reponse : on revele et on passe a la suivante
        finishM3Question(roomCode, q.answer);
    } else {
        // Mauvaise reponse : l'autre peut buzzer si pas encore essaye
        const otherId = room.m3.finalistsIds.find(id => id !== playerId);
        if (room.m3.answeredBy.includes(otherId)) {
            // L'autre a deja essaye : on cloture
            finishM3Question(roomCode, q.answer);
        } else {
            // Reprise du timer global avec le temps restant
            room.m3.buzzerId = null;
            room.m3.questionDeadline = Date.now() + room.m3.timeRemainingOnBuzz;
            io.to(roomCode).emit('m3_buzz_released', {
                nextBuzzerCandidate: otherId,
                remainingTime: room.m3.timeRemainingOnBuzz,
                deadline: room.m3.questionDeadline
            });
            if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
            room.m3.phaseTimer = setTimeout(() => closeM3QuestionTimeout(roomCode), room.m3.timeRemainingOnBuzz);
        }
    }
}

function handleM3BuzzTimeout(roomCode) {
    // Le buzzer n'a pas repondu dans les 5s -> penalite -1 (-mise si pari)
    const room = rooms[roomCode];
    if (!room || !room.m3 || !room.m3.buzzerId) return;

    const playerId = room.m3.buzzerId;
    const bet = room.m3.bets[playerId];
    const isBettedQuestion = bet?.questionIndex === room.m3.questionIndex && bet.amount > 0;
    const betAmount = isBettedQuestion ? bet.amount : 0;
    const delta = -1 - betAmount;

    room.players[playerId].m3Score += delta;
    room.m3.answeredBy.push(playerId);

    console.log(`[M3_BUZZ_TIMEOUT] ${room.players[playerId].name} a buzze mais pas repondu (${delta})`);

    io.to(roomCode).emit('m3_buzz_result', {
        playerId, answer: null, correct: false, delta, betAmount,
        newScore: room.players[playerId].m3Score,
        reason: 'buzz_timeout'
    });

    const otherId = room.m3.finalistsIds.find(id => id !== playerId);
    if (room.m3.answeredBy.includes(otherId)) {
        finishM3Question(roomCode, room.m3.currentQuestion.answer);
    } else {
        room.m3.buzzerId = null;
        room.m3.questionDeadline = Date.now() + room.m3.timeRemainingOnBuzz;
        io.to(roomCode).emit('m3_buzz_released', {
            nextBuzzerCandidate: otherId,
            remainingTime: room.m3.timeRemainingOnBuzz,
            deadline: room.m3.questionDeadline
        });
        if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
        room.m3.phaseTimer = setTimeout(() => closeM3QuestionTimeout(roomCode), room.m3.timeRemainingOnBuzz);
    }
}

function closeM3QuestionTimeout(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || !room.m3.currentQuestion) return;
    // Timeout global : personne n'a buzze (ou personne ne peut plus)
    console.log(`[M3_TIMEOUT] room ${roomCode} : question fermee`);
    finishM3Question(roomCode, room.m3.currentQuestion.answer);
}

function finishM3Question(roomCode, correctAnswer) {
    const room = rooms[roomCode];
    if (!room || !room.m3) return;
    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    if (room.m3.buzzTimer) clearTimeout(room.m3.buzzTimer);

    io.to(roomCode).emit('m3_reveal', {
        correctAnswer,
        scores: room.m3.finalistsIds.map(id => ({
            id, score: room.players[id].m3Score
        }))
    });

    // [D.1] EN MORT SUBITE : on termine des qu'il y a un ecart de score
    if (room.m3.suddenDeath) {
        const scores = room.m3.finalistsIds.map(id => room.players[id].m3Score);
        if (scores[0] !== scores[1]) {
            // Un joueur est devant : il gagne
            setTimeout(() => finalizeSuddenDeath(roomCode), CONFIG.M3_REVEAL_TIME);
        } else {
            // Toujours egal : on relance une question de mort subite
            setTimeout(() => nextSuddenDeathQuestion(roomCode), CONFIG.M3_REVEAL_TIME + CONFIG.INTER_QUESTION_GAP);
        }
        return;
    }

    // Verifier conditions de fin normales
    const targetReached = room.m3.finalistsIds.some(id => room.players[id].m3Score >= CONFIG.M3_TARGET_SCORE);
    const allQuestionsDone = room.m3.questionIndex >= CONFIG.M3_TOTAL_QUESTIONS;

    if (targetReached || allQuestionsDone) {
        setTimeout(() => endM3(roomCode, targetReached ? 'target_reached' : 'all_questions'), CONFIG.M3_REVEAL_TIME);
    } else {
        setTimeout(() => nextM3Question(roomCode), CONFIG.M3_REVEAL_TIME + CONFIG.INTER_QUESTION_GAP);
    }
}

// [D.1] Fin propre de mort subite : on declare le vainqueur
function finalizeSuddenDeath(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || room.m3.finished) return;
    room.m3.finished = true;
    clearAllTimers(room);
    room.status = 'm3_finished';
    const scores = room.m3.finalistsIds.map(id => ({
        id, name: room.players[id].name, score: room.players[id].m3Score
    }));
    scores.sort((a, b) => b.score - a.score);
    const winner = scores[0];
    console.log(`[M3_SD_END] room ${roomCode} : ${winner.name} gagne en mort subite (${winner.score} vs ${scores[1].score})`);
    emitM3Finished(roomCode, winner.id, winner.name, scores,
        `🏆 ${winner.name} remporte le championnat en mort subite !`, true);

    // Sauvegarder les résultats en BDD
    saveChampionshipResults(room, true);
}

function emitM3Finished(roomCode, winnerId, winnerName, scores, message, suddenDeath) {
    const room = rooms[roomCode];
    if (!room) return;
    const final = buildFinalRanking(room);
    io.to(roomCode).emit('m3_finished', {
        winnerId,
        winnerName,
        scores,
        message,
        ranking: final.ranking,
        duration: final.duration,
        totalQuestions: final.totalQuestions,
        suddenDeath: !!suddenDeath
    });
}

// ============================================================================
//  Construire le ranking final + delta ELO pour l'ecran de fin
//  Reutilise la meme logique que saveChampionshipResults mais retourne
//  un objet enrichi pour l'affichage cote client.
// ============================================================================
function buildFinalRanking(room) {
    // En mode amical, on n'affiche aucun gain/perte d'ELO sur l'écran de fin
    const ELO_BY_RANK = room.isRanked
        ? { 1: 50, 2: 30, 3: 0, 4: -20 }
        : { 1: 0,  2: 0,  3: 0, 4: 0  };
    const ranking = [];

    // Finalistes M3
    const m3Ids = room.m3?.finalistsIds || [];
    const m3Sorted = m3Ids
        .map(id => ({ id, name: room.players[id]?.name || '?', score: room.players[id]?.m3Score || 0 }))
        .sort((a, b) => b.score - a.score);

    if (m3Sorted.length >= 2) {
        ranking.push({
            id: m3Sorted[0].id,
            name: m3Sorted[0].name,
            score_m3: m3Sorted[0].score,
            rank: 1,
            elo_delta: ELO_BY_RANK[1]
        });
        ranking.push({
            id: m3Sorted[1].id,
            name: m3Sorted[1].name,
            score_m3: m3Sorted[1].score,
            rank: 2,
            elo_delta: ELO_BY_RANK[2]
        });
    } else if (m3Sorted.length === 1) {
        // Forfait : un seul finaliste
        ranking.push({
            id: m3Sorted[0].id,
            name: m3Sorted[0].name,
            score_m3: m3Sorted[0].score,
            rank: 1,
            elo_delta: ELO_BY_RANK[1]
        });
    }

    // Eliminés (rang 3 = M2, rang 4 = M1)
    const eliminated = Object.entries(room.players)
        .filter(([id]) => !m3Ids.includes(id));
    let rank = m3Sorted.length + 1;
    eliminated.forEach(([id, p]) => {
        if (rank > 4) return;
        ranking.push({
            id,
            name: p.name,
            score_m3: 0,
            rank,
            elo_delta: ELO_BY_RANK[rank] || 0
        });
        rank++;
    });

    // Stats globales
    const startedAt = room.startedAt || Date.now();
    const durationMs = Date.now() - startedAt;
    const durationStr = formatDuration(durationMs);
    const totalQuestions = (room.m1?.questionIndex || 0)
                         + (room.m2?.questionIndex || 0)
                         + (room.m3?.questionIndex || 0);

    return {
        ranking,
        duration: durationStr,
        durationMs,
        totalQuestions
    };
}

function formatDuration(ms) {
    const totalSec = Math.floor(ms / 1000);
    const min = Math.floor(totalSec / 60);
    const sec = totalSec % 60;
    return `${min}m ${sec.toString().padStart(2, '0')}s`;
}

// ============================================================================
//  SAUVEGARDE DES RESULTATS EN BDD (via save_championship.php)
// ============================================================================
function saveChampionshipResults(room, suddenDeath) {
    if (!room || room._saved) return;
    room._saved = true; // éviter double-save

    // ── Mode amical : on ne persiste rien en BDD (pas de delta ELO, pas de stats)
    if (!room.isRanked) {
        console.log(`[CHAMP AMICAL] ${room.code} terminé — pas de save BDD`);
        return;
    }

    // Construire le ranking final (1er → 4ème)
    const allPlayers = Object.entries(room.players);
    const ranking = [];

    // 1er = gagnant M3 (meilleur m3Score parmi les 2 finalistes)
    // 2ème = perdant M3
    // 3ème = éliminé en M2
    // 4ème = éliminé en M1
    const m3Ids = room.m3?.finalistsIds || [];
    const m3Scores = m3Ids.map(id => ({
        id, score: room.players[id]?.m3Score || 0
    })).sort((a, b) => b.score - a.score);

    if (m3Scores.length >= 2) {
        ranking.push({
            user_id: parseInt(m3Scores[0].id.replace('u', '')),
            rank: 1, score_m3: m3Scores[0].score,
            eliminated_in: 0
        });
        ranking.push({
            user_id: parseInt(m3Scores[1].id.replace('u', '')),
            rank: 2, score_m3: m3Scores[1].score,
            eliminated_in: 3
        });
    }

    // Trouver les éliminés M2 et M1 parmi les joueurs non-finalistes
    const eliminatedPlayers = allPlayers
        .filter(([id]) => !m3Ids.includes(id));

    let rank = 3;
    eliminatedPlayers.forEach(([id, p]) => {
        if (rank <= 4) {
            ranking.push({
                user_id: parseInt(id.replace('u', '')),
                rank: rank,
                score_m3: 0,
                eliminated_in: rank === 3 ? 2 : 1
            });
            rank++;
        }
    });

    // Calculer les stats globales par joueur
    ranking.forEach(r => {
        // Compter les réponses correctes/fausses à travers les manches
        // (approximation : on utilise les données disponibles dans room)
        r.correct = 0;
        r.wrong = 0;
        r.total_q = 0;
    });

    // Nombre total de questions posées dans la partie
    const totalQ = (room.m1?.questionIndex || 0)
                 + (room.m2?.questionIndex || 0)
                 + (room.m3?.questionIndex || 0);

    const envelope = signEnvelope({
        type:            'championship',
        room_code:       room.code,
        issued_at:       Date.now(),
        sudden_death:    suddenDeath || false,
        total_questions: totalQ,
        ranking:         ranking,
        bets:            [] // TODO : collecter les paris M2/M3 pour les sauvegarder
    });

    console.log(`[CHAMP_SAVE] Sauvegarde résultats room ${room.code}...`);

    // 1) Tentative directe Node → PHP (local ; coupée en prod via DIRECT_SAVE=0
    //    car InfinityFree bloque les requêtes serveur→serveur)
    if (DIRECT_SAVE) {
        try {
            const url  = new URL(PHP_BASE_URL + '/championship/save_championship.php');
            const body = JSON.stringify(envelope);
            const mod  = url.protocol === 'https:' ? https : http;
            const req  = mod.request({
                hostname: url.hostname,
                port:     url.port || (url.protocol === 'https:' ? 443 : 80),
                path:     url.pathname,
                method:   'POST',
                headers: {
                    'Content-Type':   'application/json',
                    'Content-Length':  Buffer.byteLength(body),
                },
            }, (res) => {
                let out = '';
                res.on('data', c => out += c);
                res.on('end', () => {
                    if (res.statusCode === 200) {
                        console.log(`💾 [CHAMP_SAVE] OK :`, out);
                    } else {
                        console.warn(`⚠️ [CHAMP_SAVE] HTTP ${res.statusCode}:`, out);
                    }
                });
            });
            req.on('error', err => console.warn('⚠️ [CHAMP_SAVE] erreur:', err.message));
            req.write(body);
            req.end();
        } catch (e) {
            console.warn('⚠️ [CHAMP_SAVE] exception:', e.message);
        }
    }

    // 2) Relais navigateur : les 4 clients reçoivent l'enveloppe signée et la
    //    livrent à save_championship.php (déduplication côté PHP → 1 seul traitement)
    io.to(room.code).emit('champ_save_envelope', envelope);
}

function endM3(roomCode, reason) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || room.m3.finished) return;

    const scores = room.m3.finalistsIds.map(id => ({
        id, name: room.players[id].name, score: room.players[id].m3Score
    }));
    scores.sort((a, b) => b.score - a.score);

    // Egalite ?
    if (scores[0].score === scores[1].score) {
        // Mort subite
        if (!room.m3.suddenDeath) {
            startM3SuddenDeath(roomCode);
            return;
        }
    }

    room.m3.finished = true;
    clearAllTimers(room);
    room.status = 'm3_finished';

    const winner = scores[0];
    console.log(`[M3_END] room ${roomCode} : ${winner.name} gagne avec ${winner.score} pts ! (${reason})`);

    emitM3Finished(roomCode, winner.id, winner.name, scores,
        `🏆 ${winner.name} remporte le championnat !`, false);

    // Sauvegarder les résultats en BDD
    saveChampionshipResults(room, false);
}

function startM3SuddenDeath(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'm3_sudden_death'; // [D.1] vrai status dedie
    room.m3.suddenDeath = true;
    room.m3.suddenRound = 0;
    console.log(`[M3_SUDDEN_DEATH] room ${roomCode}`);
    io.to(roomCode).emit('m3_sudden_death_start');
    setTimeout(() => nextSuddenDeathQuestion(roomCode), 2000);
}

function nextSuddenDeathQuestion(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3) return;
    room.m3.suddenRound++;
    const q = pickRandomQuestion(room.m3.usedQuestionIds);
    if (!q) {
        // Plus de questions : on conclut avec le score actuel ou au hasard
        const scores = room.m3.finalistsIds.map(id => ({
            id, name: room.players[id].name, score: room.players[id].m3Score
        }));
        scores.sort((a, b) => b.score - a.score);
        if (scores[0].score === scores[1].score) {
            const winner = room.m3.finalistsIds[Math.floor(Math.random() * 2)];
            room.players[winner].m3Score += 1; // brise l'egalite
        }
        endM3(roomCode, 'sudden_death_no_questions');
        return;
    }
    room.m3.usedQuestionIds.push(q.id);
    room.m3.currentQuestion = q;
    room.m3.buzzerId = null;
    room.m3.answeredBy = [];
    room.m3.buzzOpen = false; // [D.1] phase lecture aussi en mort subite
    room.m3.timeRemainingOnBuzz = CONFIG.M3_SUDDEN_DEATH_TIME;

    console.log(`[M3_SD_Q${room.m3.suddenRound}] room ${roomCode} : Q#${q.id} (${q.cat})`);

    // [D.1] PHASE 1 : question seule
    io.to(roomCode).emit('m3_sudden_death_question_reading', {
        round: room.m3.suddenRound,
        category: q.cat,
        question: q.q,
        readingTime: CONFIG.M3_READING_TIME,
        optionsRevealTime: CONFIG.M3_OPTIONS_REVEAL_TIME
    });

    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => revealSDOptions(roomCode), CONFIG.M3_READING_TIME);
}

// [D.1] PHASE 2 mort subite : options
function revealSDOptions(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || !room.m3.currentQuestion) return;
    const q = room.m3.currentQuestion;
    io.to(roomCode).emit('m3_sudden_death_options_revealed', {
        options: q.options,
        optionsRevealTime: CONFIG.M3_OPTIONS_REVEAL_TIME
    });
    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => openSDBuzz(roomCode), CONFIG.M3_OPTIONS_REVEAL_TIME);
}

// [D.1] PHASE 3 mort subite : buzz
function openSDBuzz(roomCode) {
    const room = rooms[roomCode];
    if (!room || !room.m3 || !room.m3.currentQuestion) return;
    room.m3.buzzOpen = true;
    room.m3.questionDeadline = Date.now() + CONFIG.M3_SUDDEN_DEATH_TIME;
    room.m3.timeRemainingOnBuzz = CONFIG.M3_SUDDEN_DEATH_TIME;
    io.to(roomCode).emit('m3_sudden_death_buzz_open', {
        deadline: room.m3.questionDeadline,
        time: CONFIG.M3_SUDDEN_DEATH_TIME
    });
    if (room.m3.phaseTimer) clearTimeout(room.m3.phaseTimer);
    room.m3.phaseTimer = setTimeout(() => closeM3QuestionTimeout(roomCode), CONFIG.M3_SUDDEN_DEATH_TIME);
}

// ============================================================================
//  MATCHMAKING CLASSÉ — file d'attente à 4 (ELO en jeu)
// ============================================================================
//  Même principe que le 1v1, version tournoi : on rassemble 4 joueurs de
//  niveau proche puis on lance EXACTEMENT le championnat existant (startM1…).
//  Les parties AMICALES continuent de passer par champ_create_room /
//  champ_join_room avec un code (championship/lobby.php) — on ne touche à rien.
const champRankedQueue = []; // { playerId, name, elo, socketId, joinedAt }

const CHAMP_MM_BASE_TOL      = 300;   // écart ELO toléré au départ (4 joueurs = plus large)
const CHAMP_MM_WIDEN_PER_SEC = 50;    // +50 par seconde d'attente
const CHAMP_MM_TICK_MS       = 2000;  // re-tentative périodique

function champQueueTol(entry) {
    return CHAMP_MM_BASE_TOL + ((Date.now() - entry.joinedAt) / 1000) * CHAMP_MM_WIDEN_PER_SEC;
}
function champRemoveFromQueueByPlayer(playerId) {
    const i = champRankedQueue.findIndex(e => e.playerId === playerId);
    if (i !== -1) champRankedQueue.splice(i, 1);
}
function champRemoveFromQueueBySocket(socketId) {
    const i = champRankedQueue.findIndex(e => e.socketId === socketId);
    if (i !== -1) champRankedQueue.splice(i, 1);
}

// Séquence de lancement (countdown → redirection vers game.php → attente rejoin).
// Extraite de champ_toggle_ready pour être réutilisée par le matchmaking.
// Les 4 joueurs doivent déjà être dans room.players.
function launchChampStartSequence(roomCode) {
    const room = rooms[roomCode];
    if (!room) return;
    room.status = 'starting';
    room.pendingRejoin = new Set();
    io.to(roomCode).emit('champ_match_starting', { in: 3 });
    let count = 3;
    const interval = setInterval(() => {
        count--;
        if (count > 0) {
            io.to(roomCode).emit('champ_countdown', { count });
        } else {
            clearInterval(interval);
            room.status = 'awaiting_rejoin';
            io.to(roomCode).emit('champ_redirect_to_game');
            if (room.rejoinTimer) clearTimeout(room.rejoinTimer);
            room.rejoinTimer = setTimeout(() => {
                console.log(`[CHAMP] ${roomCode} : timeout rejoin, on lance M1 quand même`);
                if (room.status === 'awaiting_rejoin') {
                    const connected = Object.values(room.players).filter(p => !p.disconnected);
                    if (connected.length === 0) {
                        console.log(`[CLEANUP] ${roomCode} : rejoinTimer fired mais 0 joueur connecté, suppression`);
                        clearAllTimers(room);
                        delete rooms[roomCode];
                        return;
                    }
                    room.status = 'game_countdown';
                    io.to(roomCode).emit('champ_match_starting', { in: 3 });
                    let ct = 3;
                    const ctInterval = setInterval(() => {
                        ct--;
                        if (ct > 0) io.to(roomCode).emit('champ_countdown', { count: ct });
                        else { clearInterval(ctInterval); startM1(roomCode); }
                    }, 1000);
                }
            }, 8000);
        }
    }, 1000);
}

// Crée une room classée à partir de 4 entrées de file, puis lance le tournoi.
function createRankedChampMatch(entries) {
    let code;
    do { code = generateCode(); } while (rooms[code]);

    const players = {};
    entries.forEach(e => {
        players[e.playerId] = {
            socketId: e.socketId, name: e.name,
            ready: true, alive: true, score: 0, m3Score: 0, joinedAt: Date.now()
        };
    });

    rooms[code] = {
        code, status: 'starting', hostId: entries[0].playerId, createdAt: Date.now(),
        isRanked: true, players
    };

    // Les 4 sockets (côté lobby) rejoignent la room avant la redirection.
    entries.forEach(e => {
        const s = io.sockets.sockets.get(e.socketId);
        if (s) { s.join(code); s.data = { playerId: e.playerId, roomCode: code }; }
    });

    io.to(code).emit('champ_match_found', {
        code,
        players: entries.map(e => ({ id: e.playerId, name: e.name, elo: e.elo }))
    });
    console.log(`[CHAMP CLASSÉ] Match ${code} : ${entries.map(e => `${e.name}(${e.elo})`).join(', ')}`);

    // Laisse jouer la révélation des 4 joueurs, puis lance la séquence de départ.
    setTimeout(() => launchChampStartSequence(code), 2800);
}

function tryChampMatch() {
    // Purge des entrées dont le socket est déconnecté.
    for (let i = champRankedQueue.length - 1; i >= 0; i--) {
        if (!io.sockets.sockets.get(champRankedQueue[i].socketId)) champRankedQueue.splice(i, 1);
    }
    if (champRankedQueue.length < CONFIG.MAX_PLAYERS) return;

    // Fenêtre glissante de 4 sur la file triée par ELO : on prend le 1er groupe
    // dont l'écart max ≤ tolérance (qui s'élargit avec l'attente).
    const byElo = [...champRankedQueue].sort((a, b) => a.elo - b.elo);
    for (let i = 0; i + CONFIG.MAX_PLAYERS <= byElo.length; i++) {
        const win    = byElo.slice(i, i + CONFIG.MAX_PLAYERS);
        const spread = win[win.length - 1].elo - win[0].elo;
        const tol    = Math.max(...win.map(champQueueTol));
        if (spread <= tol) {
            win.forEach(e => champRemoveFromQueueByPlayer(e.playerId));
            createRankedChampMatch(win);
            return tryChampMatch(); // relance pour d'éventuels autres groupes
        }
    }
}

setInterval(tryChampMatch, CHAMP_MM_TICK_MS);

// ============================================================================
//  SOCKET HANDLERS
// ============================================================================
io.on('connection', (socket) => {
    console.log(`[CONNECT] ${socket.id}`);

    // ── MATCHMAKING CLASSÉ : rejoindre la file (tournoi à 4) ──
    socket.on('champ_find_ranked_match', ({ playerId, name, elo }) => {
        if (!playerId) { socket.emit('champ_error', { message: 'playerId manquant' }); return; }
        champRemoveFromQueueByPlayer(playerId); // anti-doublon
        const entry = {
            playerId,
            name: (name && name.trim()) ? name.trim().slice(0, 20) : 'Joueur',
            elo:  parseInt(elo) || 1200,
            socketId: socket.id,
            joinedAt: Date.now(),
        };
        champRankedQueue.push(entry);
        socket.data = { playerId, roomCode: null }; // roomCode assigné à l'appariement
        socket.emit('champ_ranked_queued', { inQueue: champRankedQueue.length, needed: CONFIG.MAX_PLAYERS });
        console.log(`[CHAMP CLASSÉ] ${entry.name} (${entry.elo}) en file (${champRankedQueue.length}/${CONFIG.MAX_PLAYERS})`);
        tryChampMatch();
    });

    // ── MATCHMAKING CLASSÉ : quitter la file ──
    socket.on('champ_cancel_ranked', ({ playerId }) => {
        if (playerId) champRemoveFromQueueByPlayer(playerId);
        else          champRemoveFromQueueBySocket(socket.id);
        socket.emit('champ_ranked_cancelled');
    });

    // ============================================================
    //  [DEV] Mode test rapide M3 : lance M3 directement avec 2 joueurs
    // ============================================================
    socket.on('dev_quick_m3', ({ playerId, name }) => {
        if (!playerId || !name) return;
        // Trouver ou creer une room de test
        let testCode = null;
        for (const [code, room] of Object.entries(rooms)) {
            if (room.isDevTest && Object.keys(room.players).length < 2 && room.status === 'lobby') {
                testCode = code;
                break;
            }
        }
        if (!testCode) {
            // Creer nouvelle room de test
            testCode = 'DEV' + Math.floor(Math.random() * 99);
            rooms[testCode] = {
                code: testCode, status: 'lobby', hostId: playerId, createdAt: Date.now(),
                isDevTest: true,
                players: {}
            };
        }
        const room = rooms[testCode];
        room.players[playerId] = {
            socketId: socket.id, name: name.trim().slice(0, 20),
            ready: true, alive: true, score: 0, m3Score: 0, joinedAt: Date.now()
        };
        socket.join(testCode);
        socket.data = { playerId, roomCode: testCode };
        console.log(`[DEV_QUICK_M3] ${name} rejoint ${testCode} (${Object.keys(room.players).length}/2)`);
        socket.emit('champ_room_joined', { code: testCode, playerId });
        broadcastRoom(testCode);

        // Si on a 2 joueurs, on lance M3 direct
        if (Object.keys(room.players).length === 2) {
            const finalistsIds = Object.keys(room.players);
            console.log(`[DEV_QUICK_M3] Lancement M3 direct avec ${finalistsIds.length} finalistes`);
            io.to(testCode).emit('m2_simulated', {
                finalistsIds,
                finalistsNames: finalistsIds.map(id => room.players[id].name),
                eliminatedId: 'dev_ghost',
                eliminatedName: '(mode test, pas de M1/M2)'
            });
            setTimeout(() => startM3(testCode, finalistsIds), 2000);
        }
    });

    // ============================================================
    //  [DEV] Mode test rapide M2 : lance M2 directement avec 3 joueurs
    // ============================================================
    socket.on('dev_quick_m2', ({ playerId, name }) => {
        if (!playerId || !name) return;
        let testCode = null;
        for (const [code, room] of Object.entries(rooms)) {
            if (room.isDevTestM2 && Object.keys(room.players).length < 3 && room.status === 'lobby') {
                testCode = code;
                break;
            }
        }
        if (!testCode) {
            testCode = 'DV2' + Math.floor(Math.random() * 99);
            rooms[testCode] = {
                code: testCode, status: 'lobby', hostId: playerId, createdAt: Date.now(),
                isDevTest: true,
                isDevTestM2: true,
                players: {}
            };
        }
        const room = rooms[testCode];
        room.players[playerId] = {
            socketId: socket.id, name: name.trim().slice(0, 20),
            ready: true, alive: true, score: 0, m2Score: 0, m3Score: 0, joinedAt: Date.now()
        };
        socket.join(testCode);
        socket.data = { playerId, roomCode: testCode };
        console.log(`[DEV_QUICK_M2] ${name} rejoint ${testCode} (${Object.keys(room.players).length}/3)`);
        socket.emit('champ_room_joined', { code: testCode, playerId });
        broadcastRoom(testCode);

        // Si on a 3 joueurs, on lance M2 direct
        if (Object.keys(room.players).length === 3) {
            console.log(`[DEV_QUICK_M2] Lancement M2 direct avec 3 joueurs`);
            setTimeout(() => startM2(testCode), 1500);
        }
    });

    socket.on('champ_create_room', ({ playerId, name, isRanked }) => {
        if (!playerId) {
            socket.emit('champ_error', { message: 'playerId manquant' });
            return;
        }
        const code = generateCode();
        // Flag classé/amical : true par défaut (sécurité)
        const ranked = isRanked !== false;
        // Auto-naming : si pas de pseudo (guest), on assigne "Joueur 1" (1er entrant)
        const assignedName = (name && name.trim()) ? name.trim().slice(0, 20) : 'Joueur 1';

        rooms[code] = {
            code, status: 'lobby', hostId: playerId, createdAt: Date.now(),
            isRanked: ranked,
            players: {
                [playerId]: {
                    socketId: socket.id, name: assignedName,
                    ready: false, alive: true, score: 0, m3Score: 0, joinedAt: Date.now()
                }
            }
        };
        socket.join(code);
        socket.data = { playerId, roomCode: code };
        console.log(`[ROOM_CREATED] ${code} par ${assignedName} ${ranked ? '[CLASSÉ]' : '[AMICAL]'}`);
        socket.emit('champ_room_joined', { code, playerId, name: assignedName, isRanked: ranked });
        broadcastRoom(code);
    });

    socket.on('champ_join_room', ({ playerId, name, code }) => {
        if (!playerId || !code) {
            socket.emit('champ_error', { message: 'Donnees incompletes' });
            return;
        }
        code = code.toUpperCase().trim();
        const room = rooms[code];
        if (!room) { socket.emit('champ_error', { message: 'Room introuvable.' }); return; }
        if (room.status !== 'lobby') { socket.emit('champ_error', { message: 'Partie deja en cours.' }); return; }
        if (room.players[playerId]) {
            room.players[playerId].socketId = socket.id;
            socket.join(code);
            socket.data = { playerId, roomCode: code };
            socket.emit('champ_room_joined', { code, playerId, name: room.players[playerId].name, isRanked: room.isRanked });
            broadcastRoom(code);
            return;
        }
        if (Object.keys(room.players).length >= CONFIG.MAX_PLAYERS) {
            socket.emit('champ_error', { message: 'Room pleine (4 joueurs max).' });
            return;
        }
        // Auto-naming : si pas de pseudo (guest), on assigne "Joueur N" (N = ordre d'arrivée)
        const nextNum = Object.keys(room.players).length + 1;
        const assignedName = (name && name.trim()) ? name.trim().slice(0, 20) : `Joueur ${nextNum}`;
        room.players[playerId] = {
            socketId: socket.id, name: assignedName,
            ready: false, alive: true, score: 0, m3Score: 0, joinedAt: Date.now()
        };
        socket.join(code);
        socket.data = { playerId, roomCode: code };
        console.log(`[JOIN] ${assignedName} rejoint ${code}`);
        socket.emit('champ_room_joined', { code, playerId, name: assignedName, isRanked: room.isRanked });
        broadcastRoom(code);
    });

    socket.on('champ_toggle_ready', () => {
        const { playerId, roomCode } = socket.data || {};
        if (!roomCode) return;
        const room = rooms[roomCode];
        if (!room || room.status !== 'lobby') return;
        const player = room.players[playerId];
        if (!player) return;
        player.ready = !player.ready;
        broadcastRoom(roomCode);
        const playerList = Object.values(room.players);
        if (playerList.length === CONFIG.MAX_PLAYERS && playerList.every(p => p.ready)) {
            launchChampStartSequence(roomCode);
        }
    });

    // [CHAMP INTEGRATION] Handler de rejoin depuis game.php
    socket.on('champ_rejoin', ({ code, playerId }) => {
        if (!code || !playerId) return;
        const room = rooms[code];
        if (!room) {
            socket.emit('champ_error', { message: 'Room introuvable (peut-être terminée).' });
            return;
        }
        if (!room.players[playerId]) {
            socket.emit('champ_error', { message: 'Tu n\'es pas dans cette partie.' });
            return;
        }
        // Mettre à jour le socketId du joueur et le re-joindre au channel
        room.players[playerId].socketId = socket.id;
        room.players[playerId].disconnected = false;
        socket.join(code);
        socket.data = { playerId, roomCode: code };

        // Annuler le timer de disqualification si reconnexion dans les 10s
        if (room.players[playerId].disconnectTimer) {
            clearTimeout(room.players[playerId].disconnectTimer);
            room.players[playerId].disconnectTimer = null;
            console.log(`[CHAMP_REJOIN] ${room.players[playerId].name} reconnecté à temps ! Timer annulé.`);
            io.to(code).emit('champ_player_reconnected', {
                playerId, playerName: room.players[playerId].name
            });
        }

        console.log(`[CHAMP_REJOIN] ${room.players[playerId].name} → ${code} (status: ${room.status})`);
        socket.emit('champ_room_joined', { code, playerId });

        // Si on est en awaiting_rejoin, on note ce joueur comme prêt
        if (room.status === 'awaiting_rejoin' && room.pendingRejoin) {
            room.pendingRejoin.add(playerId);
            const totalPlayers = Object.keys(room.players).length;
            console.log(`[CHAMP_REJOIN] ${room.pendingRejoin.size}/${totalPlayers} ont rejoint game.php`);
            if (room.pendingRejoin.size >= totalPlayers) {
                if (room.rejoinTimer) clearTimeout(room.rejoinTimer);
                // [COUNTDOWN] 3..2..1 sur game.php avant de lancer M1
                room.status = 'game_countdown';
                io.to(code).emit('champ_match_starting', { in: 3 });
                let count = 3;
                const countdownInterval = setInterval(() => {
                    count--;
                    if (count > 0) {
                        io.to(code).emit('champ_countdown', { count });
                    } else {
                        clearInterval(countdownInterval);
                        startM1(code);
                    }
                }, 1000);
            }
        } else if (room.status === 'm1' || room.status === 'm2_categories' || room.status.startsWith('m2_') || room.status.startsWith('m3_')) {
            // Reconnexion en pleine partie : envoyer l'état actuel
            io.to(socket.id).emit('room_state', getPublicRoom(room));
            // [TODO] Pour faire propre : renvoyer la question en cours.
            // Pour l'instant le joueur va voir un écran vide jusqu'à la question suivante.
        }
    });

    socket.on('m1_answer',          (data) => handleM1Answer(socket, data));
    socket.on('m1_tiebreak_answer', (data) => handleM1TiebreakAnswer(socket, data));

    socket.on('m3_select_categories', (data) => handleM3CategorySelection(socket, data));
    socket.on('m3_place_bet',         (data) => handleM3Bet(socket, data));
    socket.on('m3_buzz',              ()     => handleM3Buzz(socket));
    socket.on('m3_buzz_answer',       (data) => handleM3BuzzAnswer(socket, data));

    // [M2 PARALLELE]
    socket.on('m2_select_categories', (data) => handleM2CategorySelection(socket, data));
    socket.on('m2_place_bet',         (data) => handleM2Bet(socket, data));
    socket.on('m2_answer',            (data) => handleM2Answer(socket, data));

    socket.on('champ_leave_room', () => handleLeave(socket, 'leave'));
    socket.on('disconnect',       () => {
        console.log(`[DISCONNECT] ${socket.id}`);
        champRemoveFromQueueBySocket(socket.id); // sort de la file matchmaking si présent
        handleLeave(socket, 'disconnect');
    });
});

function handleLeave(socket, reason) {
    const { playerId, roomCode } = socket.data || {};
    if (!roomCode) return;
    const room = rooms[roomCode];
    if (!room) return;
    const player = room.players[playerId];
    if (!player) return;

    if (room.status === 'lobby') {
        delete room.players[playerId];
        if (room.hostId === playerId) {
            const remaining = Object.entries(room.players).sort((a, b) => a[1].joinedAt - b[1].joinedAt);
            if (remaining.length > 0) room.hostId = remaining[0][0];
        }
        if (Object.keys(room.players).length === 0) {
            delete rooms[roomCode];
            return;
        }
        broadcastRoom(roomCode);
    } else {
        // En cours de partie : le joueur a 10s pour revenir
        console.log(`[MID_GAME_LEAVE] ${player.name} sur ${roomCode} en ${room.status} (${reason})`);
        player.disconnected = true;

        // ── Joueur déjà éliminé (M1) ou déjà disqualifié : on note la déco mais
        //    pas de re-disqualification ni de notif (sinon ça casse l'état de la room).
        if (!player.alive || player.disqualified) {
            console.log(`[QUIET_LEAVE] ${player.name} était déjà éliminé/disqualifié, pas de cascade`);
            return;
        }

        // ── Phases de transition : disconnects attendus pendant la navigation
        //    lobby.php → game.php. Ne PAS cleanup ici, le rejoinTimer (8s) gère.
        const isTransitionPhase = (room.status === 'awaiting_rejoin' || room.status === 'game_countdown');

        // Si tous les joueurs sont deconnectes : nettoyer immédiatement
        // (sauf en phase de transition où les déco sont attendues)
        const stillConnected = Object.values(room.players).filter(p => !p.disconnected);
        if (stillConnected.length === 0 && !isTransitionPhase) {
            console.log(`[CLEANUP] Room ${roomCode} : tous les joueurs sont partis, suppression`);
            clearAllTimers(room);
            delete rooms[roomCode];
            return;
        }
        if (stillConnected.length === 0 && isTransitionPhase) {
            console.log(`[NAV_TRANSITION] ${roomCode} : tous déconnectés en ${room.status}, attente du rejoinTimer (8s)`);
            // On ne fait rien : le rejoinTimer va vérifier dans 8s si quelqu'un est revenu
            return;
        }

        // Mode dev : cleanup immédiat si pas assez de joueurs
        const minPlayers = room.isDevTestM2 ? 3 : 2;
        if (room.isDevTest && stillConnected.length < minPlayers) {
            console.log(`[CLEANUP] Room dev ${roomCode} : moins de ${minPlayers} joueurs, suppression`);
            clearAllTimers(room);
            delete rooms[roomCode];
            return;
        }

        // En phase de transition, on ne déclenche pas non plus le timer de disqualification
        // ni la notif aux autres clients (qui sont eux-mêmes en train de naviguer).
        if (isTransitionPhase) return;

        // Notifier les autres qu'un joueur a déconnecté (timer 10s visible côté client)
        io.to(roomCode).emit('champ_player_disconnected', {
            playerId: playerId,
            playerName: player.name,
            timeout: 10000
        });

        // Timer 10s : si pas de reconnexion → disqualification
        if (player.disconnectTimer) clearTimeout(player.disconnectTimer);
        player.disconnectTimer = setTimeout(() => {
            if (!player.disconnected) return; // il s'est reconnecté entre temps
            console.log(`[DISQUALIFIED] ${player.name} disqualifié (10s timeout) sur ${roomCode} en ${room.status}`);
            player.alive = false;
            player.disqualified = true;

            io.to(roomCode).emit('champ_player_disqualified', {
                playerId: playerId,
                playerName: player.name,
                message: `${player.name} a été disqualifié (déconnexion).`
            });

            // Gérer la suite selon la manche en cours
            handleDisqualification(roomCode, playerId);
        }, 10000);
    }
}

// Gestion de la disqualification selon la manche en cours
function handleDisqualification(roomCode, disqualifiedId) {
    const room = rooms[roomCode];
    if (!room) return;

    const alivePlayers = Object.entries(room.players)
        .filter(([id, p]) => p.alive && !p.disqualified)
        .map(([id, p]) => ({ id, name: p.name, score: p.score || 0 }));

    console.log(`[DISQUAL] ${room.status} — ${alivePlayers.length} joueurs restants`);

    // ── M1 en cours : 1 éliminé → passer à M2 avec les 3 restants
    if (room.status === 'm1' || room.status === 'm1_tiebreak') {
        clearAllTimers(room);
        if (room.m1) room.m1.finished = true;
        room.m1Ranking = alivePlayers.map(p => ({ id: p.id, score: p.score }));
        room.status = 'm1_finished';
        io.to(roomCode).emit('m1_finished', {
            ranking: [
                ...alivePlayers.map((p, i) => ({ id: p.id, name: p.name, score: p.score, alive: true })),
                { id: disqualifiedId, name: room.players[disqualifiedId].name, score: 0, alive: false }
            ],
            eliminatedId: disqualifiedId,
            winnerId: alivePlayers[0]?.id,
            message: `${room.players[disqualifiedId].name} disqualifié. Passage à la Manche 2.`
        });
        setTimeout(() => {
            if (alivePlayers.length >= 3) startM2(roomCode);
            else if (alivePlayers.length === 2) startM3(roomCode, alivePlayers.map(p => p.id));
            else if (alivePlayers.length === 1) {
                // Un seul joueur restant → il gagne
                forceWin(roomCode, alivePlayers[0].id);
            }
        }, 4000);
        return;
    }

    // ── M2 en cours : 1 éliminé → passer à M3 avec les 2 restants
    if (room.status.startsWith('m2_')) {
        clearAllTimers(room);
        if (room.m2) room.m2.finished = true;
        const finalistsIds = alivePlayers.slice(0, 2).map(p => p.id);
        room.status = 'm2_finished';
        io.to(roomCode).emit('m2_finished', {
            ranking: [
                ...alivePlayers.map((p, i) => ({ id: p.id, name: p.name, score: p.m2Score || 0, alive: true })),
                { id: disqualifiedId, name: room.players[disqualifiedId].name, score: 0, alive: false }
            ],
            eliminatedId: disqualifiedId,
            finalistsIds,
            finalistsNames: finalistsIds.map(id => room.players[id].name),
            message: `${room.players[disqualifiedId].name} disqualifié. Passage à la Manche 3.`
        });
        setTimeout(() => {
            if (finalistsIds.length >= 2) startM3(roomCode, finalistsIds);
            else if (finalistsIds.length === 1) forceWin(roomCode, finalistsIds[0]);
        }, 4000);
        return;
    }

    // ── M3 en cours : l'autre joueur gagne par forfait
    if (room.status.startsWith('m3_')) {
        clearAllTimers(room);
        if (room.m3) room.m3.finished = true;
        const winner = alivePlayers[0];
        if (winner) {
            room.status = 'm3_finished';
            emitM3Finished(roomCode, winner.id, winner.name, [
                { id: winner.id, name: winner.name, score: room.players[winner.id].m3Score || 0 },
                { id: disqualifiedId, name: room.players[disqualifiedId].name, score: 0 }
            ], `🏆 ${winner.name} gagne par forfait !`, false);
            saveChampionshipResults(room, false);
        }
        return;
    }
}

// Victoire par forfait quand il ne reste qu'un joueur
function forceWin(roomCode, winnerId) {
    const room = rooms[roomCode];
    if (!room) return;
    clearAllTimers(room);
    room.status = 'm3_finished';
    const winner = room.players[winnerId];
    emitM3Finished(roomCode, winnerId, winner?.name || '?', [
        { id: winnerId, name: winner?.name || '?', score: 0 }
    ], `🏆 ${winner?.name || '?'} gagne par forfait !`, false);
    saveChampionshipResults(room, false);
}


}; // fin module.exports setupChampionship
