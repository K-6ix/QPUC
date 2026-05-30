/* ══════════════════════════════════════════════════
   QPC — Game 1v1 (logique côté client)

   ★ FIX BUG 1 :
   À la connexion, on émet IMMÉDIATEMENT `rejoin_room` pour signaler
   au serveur qu'on est bien arrivé sur la page de jeu. Le serveur
   attend que les 2 joueurs aient fait pareil avant de lancer le
   countdown 3-2-1 (puis la 1ère question). Plus de question perdue
   pendant la redirection lobby → game.

   ★ FIX BUG 2 :
   Le bouton "Revanche" envoie un event `rematch` au serveur. Le
   serveur attend que les 2 joueurs aient cliqué. Tant que l'autre
   n'a pas confirmé, on affiche un statut "En attente de l'adversaire".
   Quand les 2 sont OK, le serveur émet `rematch_ready` puis relance
   un nouveau countdown 3-2-1 et la partie démarre proprement.
══════════════════════════════════════════════════ */

'use strict';

// ══════════════════════════════════════════════════
// CONFIG & CONSTANTS
// ══════════════════════════════════════════════════
const SERVER_URL    = 'http://localhost:3000';
const LETTERS       = ['A','B','C','D'];
const POINTS_TO_WIN = 1000;

// PlayerId persistant (= identité stable côté serveur)
let PLAYER_ID = localStorage.getItem('qpc_player_id');
if (!PLAYER_ID) {
    PLAYER_ID = crypto.randomUUID();
    localStorage.setItem('qpc_player_id', PLAYER_ID);
}

// Infos posées par la lobby
const ROOM_CODE = new URLSearchParams(location.search).get('room')
                   || localStorage.getItem('qpc_room') || '';
const MY_NAME   = localStorage.getItem('qpc_name') || 'Joueur';
const MY_ELO    = parseInt(localStorage.getItem('qpc_elo')) || 1200;

// ══════════════════════════════════════════════════
// STATE
// ══════════════════════════════════════════════════
let socket        = null;
let players       = {};       // { playerId: {id, name, score, elo} }
let myId          = null;
let buzzedBy      = null;
let answered      = false;
let buzzOpen      = false;
let currentQ      = null;
let timerInterval = null;
let timerLeft     = 0;
let timerMax      = 15;
let myRematchSent = false;

// ══════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════
function $(id) { return document.getElementById(id); }

// ══════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════
function init() {
    if (!ROOM_CODE) {
        alert('Code de room manquant. Retour au lobby.');
<<<<<<< HEAD
        location.href = 'lobby-1v1.php';
=======
        location.href = 'lobby-1v1.html';
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
        return;
    }

    // ─── Boutons UI ───
    $('buzz-btn').addEventListener('click', onBuzz);
    $('abandon-btn').addEventListener('click', confirmAbandon);
    $('rematch-btn').addEventListener('click', onRematchClick);
    $('dashboard-btn').addEventListener('click', () => {
        location.href = 'dashboard.php';
    });

    // ─── Clavier : n'importe quelle touche = buzz ───
    document.addEventListener('keydown', (e) => {
        if (['Tab','Escape','F5'].includes(e.key)) return;
        // On ignore aussi si on est dans un input
        if (document.activeElement && ['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) return;
        if (buzzOpen && !buzzedBy && !answered) onBuzz();
    });

    // ─── Socket ───
    socket = io(SERVER_URL, {
        transports: ['websocket', 'polling'],
        upgrade: true,
        rememberUpgrade: true,
    });
    myId = PLAYER_ID;

    socket.on('connect', () => {
        setConnectionStatus(true);
        console.log('✅ Connecté avec PLAYER_ID :', myId);

        // ★ FIX BUG 1 : on signale immédiatement au serveur qu'on
        // est sur la page de jeu. Le serveur attendra que les 2
        // joueurs aient fait pareil avant de lancer le countdown.
        socket.emit('rejoin_room', {
            playerId: PLAYER_ID,
            code    : ROOM_CODE,
            name    : MY_NAME,
            elo     : MY_ELO,
        });
    });

    socket.on('disconnect',    () => setConnectionStatus(false));
    socket.on('connect_error', () => setConnectionStatus(false));

    // ─── Events serveur ───
    socket.on('game_state',       onGameState);
    socket.on('countdown',        onCountdown);
    socket.on('question_text',    onQuestionText);
    socket.on('question_options', onQuestionOptions);
    socket.on('buzz_open',        onBuzzOpen);
    socket.on('timer_tick',       onTimerTick);
    socket.on('buzzed',           onBuzzed);
    socket.on('answer_timer',     onAnswerTimer);
    socket.on('answer_result',    onAnswerResult);
    socket.on('opponent_chance',  onOpponentChance);
    socket.on('time_out',         onTimeOut);
    socket.on('game_over',        onGameOver);
    socket.on('rematch_pending',  onRematchPending);
    socket.on('rematch_ready',    onRematchReady);
    socket.on('player_left',      onPlayerLeft);
<<<<<<< HEAD
    socket.on('state_resync',     onStateResync);
=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
    socket.on('error',            ({ message }) => alert(message));
}

// ══════════════════════════════════════════════════
// SOCKET HANDLERS
// ══════════════════════════════════════════════════
function onGameState({ players: scores, status }) {
    console.log('📡 game_state | status:', status, '| joueurs:', scores.length);
    updatePlayersInfo(scores);
    updateScores(scores, POINTS_TO_WIN);
}

<<<<<<< HEAD
// ── Reconnexion mid-game : reconstruction de l'état ──
function onStateResync(data) {
    console.log('🔄 state_resync | phase:', data.phase);

    // Mettre à jour les scores
    if (data.scores) {
        updatePlayersInfo(data.scores);
        updateScores(data.scores, POINTS_TO_WIN);
    }

    // Si la partie est en cours et qu'on a une question active
    if (['question', 'buzz', 'answer'].includes(data.phase) && data.question) {
        // Cacher countdown
        $('countdown-overlay').classList.add('hidden');

        // Afficher la question
        $('q-counter').textContent = `Q ${(data.currentQ || 0) + 1}/10`;
        $('cat-icon').textContent  = data.question.catIcon || '🎲';
        $('cat-label').textContent = data.question.catLabel || '—';
        $('question-text').textContent = data.question.question;

        // Afficher les options si la phase le permet
        if (data.phase !== 'question') {
            renderOptions(data.question.options);
            timerMax  = data.question.time;
            timerLeft = data.timerLeft || 0;
            updateTimerUI();
        }

        // État du buzz
        buzzedBy = data.buzzedBy || null;
        buzzOpen = data.buzzOpen || false;
        answered = data.answered || false;

        if (buzzOpen && !buzzedBy) {
            setBuzzState('active');
            $('buzz-status').textContent = 'BUZZ !';
            $('buzz-status').className   = 'buzz-status active-msg';
        } else if (buzzedBy) {
            if (buzzedBy === PLAYER_ID) {
                setBuzzState('my-buzz');
                enableOptions();
            } else {
                setBuzzState('opponent-buzz');
                disableOptions();
            }
        } else {
            setBuzzState('locked');
        }
    } else if (data.phase === 'gameover') {
        // Si on reconnecte après game over — le server émettra game_over séparément
        console.log('🔄 Resync: partie terminée');
    }
}

=======
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
function onCountdown({ count }) {
    const overlay = $('countdown-overlay');
    const num     = $('countdown-number');
    overlay.classList.remove('hidden');
    num.textContent = count;
<<<<<<< HEAD
    // Reset animation via double-rAF (évite forced reflow synchrone)
    num.style.animation = 'none';
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            num.style.animation = '';
        });
    });
=======
    // Reset de l'animation pour la rejouer
    num.style.animation = 'none';
    void num.offsetWidth;
    num.style.animation = '';
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
}

function onQuestionText({ index, total, question, catLabel, catIcon, difficulty, scores, pointsToWin }) {
    $('countdown-overlay').classList.add('hidden');

    // Reset state
    buzzedBy = null;
    answered = false;
    buzzOpen = false;

    updateScores(scores, pointsToWin);

    $('q-counter').textContent      = `Q ${index+1}/${total}`;
    $('cat-icon').textContent       = catIcon || '🎲';
    $('cat-label').textContent      = catLabel || '—';
    const diffEl = $('diff-badge');
    diffEl.textContent = difficulty;
    diffEl.className   = 'diff-badge ' + difficulty;

    const card = $('question-card');
    card.classList.add('reading-phase');
    $('question-text').textContent = question;

    resetOptions();
    setBuzzState('locked');
    $('buzz-status').textContent = 'Lecture…';
    $('buzz-status').className   = 'buzz-status locked-msg';

    stopTimer();
    $('timer-num').textContent  = '—';
    $('timer-fill').style.width = '0%';
}

function onQuestionOptions({ options, points, time }) {
    currentQ = { options, points, time };
    $('question-card').classList.remove('reading-phase');
    renderOptions(options);
    timerMax  = time;
    timerLeft = time;
    updateTimerUI();
}

function onBuzzOpen() {
    buzzOpen = true;
    setBuzzState('active');
    $('buzz-status').textContent = 'BUZZ !';
    $('buzz-status').className   = 'buzz-status active-msg';
}

function onTimerTick({ timeLeft, timeMax }) {
    timerLeft = timeLeft;
    timerMax  = timeMax;
    updateTimerUI();
}

function onBuzzed({ buzzerId, buzzerName, timeLeft }) {
    buzzedBy  = buzzerId;
    buzzOpen  = false;
    timerLeft = timeLeft;

    if (buzzerId === PLAYER_ID) {
        setBuzzState('my-buzz');
        enableOptions();
        $('buzz-status').textContent = 'Réponds vite !';
        $('buzz-status').className   = 'buzz-status my-msg';
        $('p1-avatar').classList.add('buzzed');
    } else {
        setBuzzState('opponent-buzz');
        disableOptions();
        $('buzz-status').textContent = `${buzzerName} a buzzé !`;
        $('buzz-status').className   = 'buzz-status opp-msg';
        $('p2-avatar').classList.add('buzzed');
    }
}

function onAnswerTimer({ timeLeft }) {
    timerLeft = timeLeft;
    timerMax  = 8;
    updateTimerUI();
}

function onAnswerResult({ answerId, correct, pts, answer, scores, timeout }) {
    answered = true;
    buzzedBy = null;

    $('p1-avatar').classList.remove('buzzed');
    $('p2-avatar').classList.remove('buzzed');

    revealAnswer(answer, answerId, correct);

    const name = players[answerId]?.name || 'Joueur';
    showFeedback(correct, pts, name, timeout);
    updateScores(scores, POINTS_TO_WIN);

    if (correct && answerId === PLAYER_ID) spawnParticles();

    setBuzzState('locked');
    $('buzz-status').textContent = '';
    stopTimer();
}

function onOpponentChance({ playerId, timeLeft }) {
    if (playerId === PLAYER_ID) {
        buzzedBy = PLAYER_ID;
        enableOptions();
        setBuzzState('my-buzz');
        $('buzz-status').textContent = 'Ta chance ! Réponds !';
        $('buzz-status').className   = 'buzz-status my-msg';
    } else {
        $('buzz-status').textContent = 'Adversaire peut répondre…';
        $('buzz-status').className   = 'buzz-status opp-msg';
    }
    timerLeft = timeLeft;
    timerMax  = 5;
    updateTimerUI();
}

function onTimeOut({ answer, scores }) {
    answered = true;
    revealAnswer(answer, null, false);
    updateScores(scores, POINTS_TO_WIN);
    setBuzzState('locked');
    stopTimer();
    $('buzz-status').textContent = 'Temps écoulé !';
    $('buzz-status').className   = 'buzz-status locked-msg';
    $('timer-num').textContent   = '0';
}

function onGameOver({ winnerId, winnerName, scores, eloResult, forfeit }) {
    stopTimer();
    // Reset état revanche pour la nouvelle fin de partie
    myRematchSent = false;
    $('rematch-btn').disabled = false;
    $('rematch-btn').textContent = '↺ Revanche';
    $('rematch-status').classList.remove('visible');
    $('rematch-status').textContent = '';

    setTimeout(() => showEndScreen(winnerId, winnerName, scores, eloResult, forfeit), 1500);
}

// ★ FIX BUG 2 : feedback en attendant que l'autre joueur clique aussi
function onRematchPending({ playerId, accepted, total }) {
    if (playerId === PLAYER_ID) return;     // c'est moi → pas de message
    const status = $('rematch-status');
    status.textContent = `Adversaire prêt — ${accepted}/${total}`;
    status.classList.add('visible');
}

// ★ FIX BUG 2 : les 2 joueurs ont validé → on cache l'end-screen
function onRematchReady({ players: scores }) {
    console.log('↺ Revanche acceptée des deux côtés !');
    $('end-screen').classList.remove('visible');
    $('rematch-status').classList.remove('visible');
    $('rematch-status').textContent = '';
    myRematchSent = false;

    // Reset complet de l'UI de jeu
    resetGameUI();

    updatePlayersInfo(scores);
    updateScores(scores, POINTS_TO_WIN);
    // Le serveur va enchaîner avec countdown puis question_text
}

function onPlayerLeft({ playerName }) {
    stopTimer();
    setBuzzState('locked');
    $('buzz-status').textContent       = `${playerName} a quitté.`;
    $('buzz-status').className         = 'buzz-status locked-msg';
    $('status-text').textContent       = 'Adversaire déconnecté';
}

// ══════════════════════════════════════════════════
// ACTIONS JOUEUR
// ══════════════════════════════════════════════════
function onBuzz() {
    if (!buzzOpen || buzzedBy || answered) return;
    socket.emit('buzz', { code: ROOM_CODE, playerId: PLAYER_ID });
}

function onAnswer(chosen) {
    if (buzzedBy !== PLAYER_ID || answered) return;
    answered = true;
    disableOptions();
    socket.emit('answer', { code: ROOM_CODE, playerId: PLAYER_ID, chosen });
}

function confirmAbandon() {
    if (confirm('Abandonner la partie ? Vous perdrez des points ELO.')) {
        socket.disconnect();
        location.href = 'dashboard.php';
    }
}

// ★ FIX BUG 2 : envoie l'event, désactive le bouton, affiche le statut
function onRematchClick() {
    if (myRematchSent) return;
    myRematchSent = true;

    socket.emit('rematch', { code: ROOM_CODE, playerId: PLAYER_ID });

    const btn = $('rematch-btn');
    btn.disabled = true;
    btn.textContent = '⏳ En attente…';

    const status = $('rematch-status');
    status.textContent = 'Tu attends l\'adversaire…';
    status.classList.add('visible');
}

// ══════════════════════════════════════════════════
// UI — RESET (utilisé en début de partie et de revanche)
// ══════════════════════════════════════════════════
function resetGameUI() {
    // Réinitialise tous les flags
    buzzedBy = null;
    answered = false;
    buzzOpen = false;
    stopTimer();

    // Question
    $('question-card').classList.add('reading-phase');
    $('question-text').textContent = 'En attente de la partie…';

    // Options vides
    resetOptions();

    // Buzz
    setBuzzState('locked');
    $('buzz-status').textContent = 'En attente…';
    $('buzz-status').className   = 'buzz-status locked-msg';

    // Avatars
    $('p1-avatar').classList.remove('buzzed');
    $('p2-avatar').classList.remove('buzzed');

    // Timer
    $('timer-num').textContent  = '—';
    $('timer-fill').style.width = '0%';
    $('timer-fill').classList.remove('danger');
    $('timer-num').classList.remove('danger');

    // Counter
    $('q-counter').textContent = 'Q —/—';

    // Feedback / countdown
    $('feedback-badge').classList.remove('show');
}

// ══════════════════════════════════════════════════
// UI — OPTIONS
// ══════════════════════════════════════════════════
function renderOptions(options) {
    const grid = $('options-grid');
    grid.innerHTML = '';
    options.forEach((opt, i) => {
        const btn = document.createElement('button');
        btn.className = 'option-btn';
        btn.innerHTML = `<span class="option-letter">${LETTERS[i]}</span><span>${opt}</span>`;
        btn.disabled  = true;
        btn.dataset.opt = opt;
        btn.addEventListener('click', () => onAnswer(opt));
        grid.appendChild(btn);
        setTimeout(() => btn.classList.add('visible'), i * 80);
    });
}

function resetOptions() {
    const grid = $('options-grid');
    grid.innerHTML = '';
    for (let i = 0; i < 4; i++) {
        const btn = document.createElement('button');
        btn.className = 'option-btn';
        btn.innerHTML = `<span class="option-letter">${LETTERS[i]}</span><span>—</span>`;
        btn.disabled  = true;
        grid.appendChild(btn);
    }
}

function enableOptions() {
    document.querySelectorAll('.option-btn').forEach(b => b.disabled = false);
}
function disableOptions() {
    document.querySelectorAll('.option-btn').forEach(b => b.disabled = true);
}

function revealAnswer(correctAnswer, answerId, correct) {
    document.querySelectorAll('.option-btn').forEach(btn => {
        btn.disabled = true;
        const text = btn.dataset.opt || btn.querySelector('span:last-child')?.textContent;
        if (text === correctAnswer) {
            btn.classList.add(correct && answerId === myId ? 'correct' : 'reveal');
        }
        if (answerId === myId && !correct && text !== correctAnswer && btn.classList.contains('visible')) {
            btn.classList.add('wrong');
        }
    });
}

// ══════════════════════════════════════════════════
// UI — BUZZ BUTTON
// ══════════════════════════════════════════════════
function setBuzzState(state) {
    const btn = $('buzz-btn');
    btn.className = 'buzz-btn ' + state;
}

// ══════════════════════════════════════════════════
// UI — TIMER
// ══════════════════════════════════════════════════
function updateTimerUI() {
    const pct      = timerMax > 0 ? (timerLeft / timerMax) * 100 : 0;
    const isDanger = timerLeft <= 5;
    const fill = $('timer-fill');
    const num  = $('timer-num');

    fill.style.width = pct + '%';
    num.textContent  = timerLeft;
    fill.classList.toggle('danger', isDanger);
    num.classList.toggle('danger', isDanger);
}
function stopTimer() { clearInterval(timerInterval); timerInterval = null; }

// ══════════════════════════════════════════════════
// UI — SCORES
// ══════════════════════════════════════════════════
function updateScores(scoresList, pointsToWin) {
    if (!scoresList || scoresList.length === 0) return;

    const me  = scoresList.find(p => p.id === myId) || scoresList[0];
    const opp = scoresList.find(p => p.id !== myId) || scoresList[1];
    if (!me || !opp) return;

    players[me.id]  = me;
    players[opp.id] = opp;

    animateScore('p1-score', me.score);
    animateScore('p2-score', opp.score);

    const total = pointsToWin || POINTS_TO_WIN;
    const p1Pct = Math.min(100, (me.score  / total) * 50);
    const p2Pct = Math.min(100, (opp.score / total) * 50);
    $('score-bar-p1').style.width = p1Pct + '%';
    $('score-bar-p2').style.width = p2Pct + '%';
}

function animateScore(elId, target) {
    const el = $(elId);
    if (!el) return;
    const current = parseInt(el.textContent) || 0;
    if (current === target) return;
    el.textContent = target;
    el.classList.add('bump');
    setTimeout(() => el.classList.remove('bump'), 300);
}

function updatePlayersInfo(scoresList) {
    if (!scoresList || scoresList.length < 1) return;
    const me  = scoresList.find(p => p.id === myId) || scoresList[0];
    const opp = scoresList.find(p => p.id !== myId) || scoresList[1];

    if (me) {
        $('p1-name').textContent   = me.name  || 'Joueur 1';
        $('p1-elo').textContent    = `${me.elo  || 1200} ELO`;
        $('p1-avatar').textContent = (me.name  || 'J').charAt(0).toUpperCase();
    }
    if (opp) {
        $('p2-name').textContent   = opp.name || 'Joueur 2';
        $('p2-elo').textContent    = `${opp.elo || 1200} ELO`;
        $('p2-avatar').textContent = (opp.name || 'J').charAt(0).toUpperCase();
    }
}

// ══════════════════════════════════════════════════
// UI — FEEDBACK BADGE
// ══════════════════════════════════════════════════
function showFeedback(correct, pts, who, timeout) {
    const badge = $('feedback-badge');
    badge.className = 'feedback-badge ' + (timeout ? 'wrong-fb' : correct ? 'correct-fb' : 'wrong-fb');
    $('fb-icon').textContent = timeout ? '⏱' : correct ? '✓' : '✗';
    $('fb-who').textContent  = who;
    $('fb-text').textContent = timeout ? 'Temps écoulé !'
                              : correct ? 'Bonne réponse !'
                                        : 'Mauvaise réponse !';
    const ptsText = pts > 0 ? `+${pts} pts` : pts < 0 ? `${pts} pts` : '+0 pts';
    $('fb-pts').textContent  = ptsText;

    badge.classList.add('show');
    setTimeout(() => badge.classList.remove('show'), 2000);
}

// ══════════════════════════════════════════════════
// UI — END SCREEN
// (winnerId est une STRING, et winnerName arrive séparément)
// ══════════════════════════════════════════════════
function showEndScreen(winnerId, winnerName, scores, eloResult, forfeit) {
    const me  = scores.find(p => p.id === myId)  || scores[0];
    const opp = scores.find(p => p.id !== myId) || scores[1];

    $('end-winner-name').textContent = (winnerName || '—') + (forfeit ? ' (forfait)' : '');
    $('end-p1-name').textContent     = me?.name  || '—';
    $('end-p2-name').textContent     = opp?.name || '—';
    $('end-p1-score').textContent    = me?.score  ?? 0;
    $('end-p2-score').textContent    = opp?.score ?? 0;

    // ELO variation
    if (eloResult && me) {
        const myElo = eloResult[me.id];
        if (myElo) {
            const sign = myElo.delta >= 0 ? '+' : '';
            $('end-p1-elo').innerHTML =
                `<span class="${myElo.delta >= 0 ? 'elo-up' : 'elo-down'}">${sign}${myElo.delta} ELO (${myElo.newElo})</span>`;
            localStorage.setItem('qpc_elo', myElo.newElo);
        }
    }
    if (eloResult && opp) {
        const oppElo = eloResult[opp.id];
        if (oppElo) {
            const sign = oppElo.delta >= 0 ? '+' : '';
            $('end-p2-elo').innerHTML =
                `<span class="${oppElo.delta >= 0 ? 'elo-up' : 'elo-down'}">${sign}${oppElo.delta} ELO (${oppElo.newElo})</span>`;
        }
    }

    // Winner highlight
    $('end-p1').classList.toggle('winner', me?.id  === winnerId);
    $('end-p2').classList.toggle('winner', opp?.id === winnerId);

    // Si forfait, on cache le bouton revanche
    $('rematch-btn').style.display = forfeit ? 'none' : '';

    $('end-screen').classList.add('visible');
}

// ══════════════════════════════════════════════════
// UI — CONNECTION STATUS
// ══════════════════════════════════════════════════
function setConnectionStatus(connected) {
    const dot  = $('status-dot');
    const text = $('status-text');
    dot.className    = 'status-dot' + (connected ? '' : ' disconnected');
    text.textContent = connected ? 'Connecté' : 'Déconnecté';
}

// ══════════════════════════════════════════════════
// PARTICLES
// ══════════════════════════════════════════════════
function spawnParticles() {
    const buzz = $('buzz-btn');
    const rect = buzz.getBoundingClientRect();
    const cx   = rect.left + rect.width  / 2;
    const cy   = rect.top  + rect.height / 2;

    for (let i = 0; i < 10; i++) {
        const p    = document.createElement('div');
        p.className = 'particle';
        const size = 4 + Math.random() * 6;
        p.style.cssText = `
            width:${size}px; height:${size}px;
            background:${Math.random() > .5 ? '#d4af37' : '#fcf6ba'};
            left:${cx}px; top:${cy}px;
            --dx:${(Math.random() - .5) * 140}px;
            --dy:${(Math.random() - 1)  * 120}px;
        `;
        document.body.appendChild(p);
        setTimeout(() => p.remove(), 900);
    }
}

// ══════════════════════════════════════════════════
// START
// ══════════════════════════════════════════════════
init();
