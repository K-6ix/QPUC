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

// ── Helper : URL de retour selon l'état de l'utilisateur ──
// Les guests n'ont pas accès au dashboard → on les renvoie vers game.php
function returnUrl() {
    return window.QPC_USER ? 'dashboard.php' : 'game.php';
}

function init() {
    if (!ROOM_CODE) {
        alert('Code de room manquant. Retour au lobby.');
        location.href = 'lobby-1v1.php';
        return;
    }

    // ─── Boutons UI ───
    $('buzz-btn').addEventListener('click', onBuzz);
    // L'ancien bouton croix top-right a été retiré ; on branche le bouton du bas.
    $('abandon-btn-text')?.addEventListener('click', confirmAbandon);
    $('rematch-btn').addEventListener('click', onRematchClick);
    $('dashboard-btn').addEventListener('click', () => {
        location.href = returnUrl();
    });

    // ─── Modal d'abandon ───
    $('abandon-modal-cancel')?.addEventListener('click', closeAbandonModal);
    initAbandonSlide();   // remplace l'ancien bouton "Quitter" par le slide-to-confirm
    $('abandon-modal-backdrop')?.addEventListener('click', closeAbandonModal);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && $('abandon-modal')?.classList.contains('open')) {
            closeAbandonModal();
        }
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
    socket.on('state_resync',     onStateResync);
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

function onCountdown({ count }) {
    const overlay = $('countdown-overlay');
    const num     = $('countdown-number');
    overlay.classList.remove('hidden');
    num.textContent = count;
    // Reset animation via double-rAF (évite forced reflow synchrone)
    num.style.animation = 'none';
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            num.style.animation = '';
        });
    });
    // FX : son countdown
    window.QPCFx?.onCountdown(count);
}

function onQuestionText({ index, total, question, catLabel, catIcon, difficulty, scores, pointsToWin }) {
    $('countdown-overlay').classList.add('hidden');

    // Reset state
    buzzedBy = null;
    answered = false;
    buzzOpen = false;

    updateScores(scores, pointsToWin);

    $('q-counter').textContent      = `${index+1}/${total}`;
    $('cat-icon').textContent       = catIcon || '🎲';
    $('cat-label').textContent      = catLabel || '—';
    const diffEl = $('diff-badge');
    diffEl.textContent = difficulty;
    diffEl.className   = 'diff-badge ' + difficulty;

    const card = $('question-card');
    card.classList.add('reading-phase');
    // FX : split words + flash + shockwave + son
    if (window.QPCFx) {
        window.QPCFx.onQuestionTextArrive(question, $('question-text'), card);
    } else {
        $('question-text').textContent = question;
    }

    resetOptions();
    setBuzzState('locked');
    $('buzz-status').textContent = 'Lecture…';
    $('buzz-status').className   = 'buzz-status locked-msg';

    stopTimer();
    $('timer-num').textContent  = '—';
    if ($('timer-fill')) $('timer-fill').style.width = '0%';
    // Reset le SVG ring à plein
    const ring = $('ring-progress');
    if (ring) ring.setAttribute('stroke-dashoffset', 0);
}

function onQuestionOptions({ options, points, time }) {
    currentQ = { options, points, time };
    $('question-card').classList.remove('reading-phase');
    renderOptions(options);
    timerMax  = time;
    timerLeft = time;
    updateTimerUI();
    // FX : son + flip 3D des options
    window.QPCFx?.onOptionsAppear();
}

function onBuzzOpen() {
    buzzOpen = true;
    setBuzzState('active');
    $('buzz-status').textContent = 'BUZZ !';
    $('buzz-status').className   = 'buzz-status active-msg';
    // FX : son ding + shockwave
    window.QPCFx?.onBuzzOpen();
}

function onTimerTick({ timeLeft, timeMax }) {
    timerLeft = timeLeft;
    timerMax  = timeMax;
    updateTimerUI();
    // FX : tick sonore (urgent les 5 dernières secondes)
    window.QPCFx?.onTimerTick(timeLeft);
}

function onBuzzed({ buzzerId, buzzerName, timeLeft }) {
    buzzedBy  = buzzerId;
    buzzOpen  = false;
    timerLeft = timeLeft;

    // FX : burst couleur selon qui buzz + son
    window.QPCFx?.onBuzzed(buzzerId === PLAYER_ID);

    if (buzzerId === PLAYER_ID) {
        setBuzzState('my-buzz');
        enableOptions();
        $('buzz-status').textContent = 'Réponds vite !';
        $('buzz-status').className   = 'buzz-status my-msg';
        $('p1-avatar').closest('.hex-frame')?.classList.add('buzzed');
    } else {
        setBuzzState('opponent-buzz');
        disableOptions();
        $('buzz-status').textContent = `${buzzerName} a buzzé !`;
        $('buzz-status').className   = 'buzz-status opp-msg';
        $('p2-avatar').closest('.hex-frame')?.classList.add('buzzed');
    }
}

function onAnswerTimer({ timeLeft }) {
    timerLeft = timeLeft;
    timerMax  = 8;
    updateTimerUI();
}

function onAnswerResult({ answerId, correct, pts, chosen, answer, scores, timeout }) {
    answered = true;
    buzzedBy = null;

    $('p1-avatar').closest('.hex-frame')?.classList.remove('buzzed');
    $('p2-avatar').closest('.hex-frame')?.classList.remove('buzzed');

    // Si `answer` est présent → on révèle la bonne (correct, ou timeout, ou final).
    // Si `answer` est absent → la mauvaise réponse est juste marquée en rouge,
    // et l'adversaire va avoir sa chance via opponent_chance.
    if (answer) {
        revealAnswer(answer, answerId, correct, chosen);
    } else if (chosen) {
        markWrongChoice(chosen, answerId);
    }

    const name = players[answerId]?.name || 'Joueur';
    showFeedback(correct, pts, name, timeout);
    updateScores(scores, POINTS_TO_WIN);

    if (correct && answerId === PLAYER_ID) spawnParticles();

    // FX : big mark + flash + confetti + son selon contexte
    const isMe = answerId === PLAYER_ID;
    const scoreEl = isMe ? $('p1-score') : $('p2-score');
    window.QPCFx?.onAnswerResult({
        correct, isMe, timeout,
        pts: pts || 0,
        targetScoreEl: scoreEl
    });

    setBuzzState('locked');
    $('buzz-status').textContent = '';
    // ⚠ on ne stopTimer() PAS systématiquement : si opponent_chance arrive
    // juste après (cas "réponse fausse, pas révélée"), le serveur va relancer
    // un timer de 5s. Le stopTimer reste valide dans tous les autres cas.
    if (answer || timeout) stopTimer();
}

function onOpponentChance({ playerId, timeLeft, wrongAnswer, wrongPlayerId }) {
    // La mauvaise réponse a déjà été marquée en rouge par onAnswerResult/markWrongChoice.
    // Ici on gère uniquement l'état du buzz pour les 5 secondes qui suivent.

    if (playerId === PLAYER_ID) {
        // ─── C'est À MOI de buzzer si je veux tenter ───
        buzzOpen = true;             // important pour que onBuzz() laisse passer
        buzzedBy = null;
        answered = false;
        setBuzzState('open');         // bouton actif (pas 'my-buzz' : pas encore mes options)
        $('buzz-status').textContent = '⚡ À toi ! Buzz pour tenter.';
        $('buzz-status').className   = 'buzz-status open-msg';
    } else {
        // ─── L'adversaire peut buzzer pour tenter ───
        buzzOpen = false;
        setBuzzState('locked');
        $('buzz-status').textContent = 'Ton adversaire peut tenter…';
        $('buzz-status').className   = 'buzz-status opp-msg';
    }

    // Timer 5s — visible pour les deux joueurs
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
    // FX : big mark ⏱ + shake + son
    window.QPCFx?.onAnswerResult({ correct: false, isMe: false, timeout: true });
}

function onGameOver({ winnerId, winnerName, scores, eloResult, forfeit }) {
    stopTimer();
    // Reset état revanche pour la nouvelle fin de partie
    myRematchSent = false;
    $('rematch-btn').disabled = false;
    $('rematch-btn').textContent = '↺ Revanche';
    $('rematch-status').classList.remove('visible');
    $('rematch-status').textContent = '';

    // FX : son + confetti si on gagne
    window.QPCFx?.onGameOver(winnerId === PLAYER_ID);

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

function openAbandonModal() {
    const modal = $('abandon-modal');
    const desc  = $('abandon-modal-desc');
    if (!modal) return;

    // Message contextualisé : amical vs classé
    if (desc) {
        desc.textContent = window.QPC_FRIENDLY
            ? 'Tu vas quitter le duel en cours.'
            : 'Tu vas quitter le duel en cours et perdre des points ELO.';
    }

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    // Focus sur "Continuer" par défaut (option safe)
    setTimeout(() => $('abandon-modal-cancel')?.focus(), 0);
}

function closeAbandonModal() {
    const modal = $('abandon-modal');
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    resetAbandonSlide();
}

function doAbandon() {
    closeAbandonModal();
    try { socket.disconnect(); } catch (e) {}
    location.href = returnUrl();
}

// ─── Slide-to-confirm pour l'abandon ───
let _abSlideReset = null;
function resetAbandonSlide() { if (_abSlideReset) _abSlideReset(); }

function initAbandonSlide() {
    const slide  = $('abandon-slide');
    const handle = $('abandon-slide-handle');
    const fill   = $('abandon-slide-fill');
    const track  = $('abandon-slide-track');
    if (!slide || !handle) return;

    let dragging = false, done = false;
    const maxX = () => slide.clientWidth - handle.offsetWidth - 8;

    function setX(x) {
        x = Math.max(0, Math.min(maxX(), x));
        handle.style.left = (x + 4) + 'px';
        fill.style.width = (x + 46) + 'px';
        if (x >= maxX() - 2 && !done) complete();
    }
    function complete() {
        done = true;
        track.innerHTML = '<span class="slide-confirm-done">Forfait ✓</span>';
        setTimeout(doAbandon, 350);   // petit délai pour que le ✓ soit vu
    }
    _abSlideReset = function () {
        done = false; dragging = false;
        handle.style.transition = 'left .25s ease';
        fill.style.transition   = 'width .25s ease';
        handle.style.left = '4px';
        fill.style.width  = '48px';
        if (track) track.textContent = 'Glisser pour abandonner →';
        setTimeout(() => { handle.style.transition = ''; fill.style.transition = ''; }, 260);
    };

    function down(e) { if (done) return; dragging = true; handle.style.transition = ''; fill.style.transition = ''; e.preventDefault(); }
    function move(e) {
        if (!dragging) return;
        const r = slide.getBoundingClientRect();
        const cx = (e.touches ? e.touches[0].clientX : e.clientX) - r.left - 25;
        setX(cx);
    }
    function up() {
        if (!dragging) return;
        dragging = false;
        if (!done) {
            handle.style.transition = 'left .25s ease';
            fill.style.transition   = 'width .25s ease';
            setX(0);
        }
    }
    handle.addEventListener('pointerdown', down);
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', up);
    // Accessibilité clavier : Entrée/Espace sur la poignée = abandon direct
    handle.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); complete(); }
    });
}

// Alias gardé pour ne pas casser les appels existants ailleurs dans le fichier
function confirmAbandon() { openAbandonModal(); }

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

function revealAnswer(correctAnswer, answerId, correct, chosen) {
    document.querySelectorAll('.option-btn').forEach(btn => {
        btn.disabled = true;
        const text = btn.dataset.opt || btn.querySelector('span:last-child')?.textContent;

        // La bonne réponse : vert si elle a été cliquée, gold sinon (révélation)
        if (text === correctAnswer) {
            btn.classList.add(correct ? 'correct' : 'reveal');
        }
        // La réponse fautive (de n'importe quel joueur) : rouge
        if (chosen && text === chosen && !correct) {
            btn.classList.add('wrong');
        }
    });
}

// ─── Marque la réponse choisie en rouge SANS révéler la bonne réponse ─────
//     (utilisé quand on va passer en phase opponent_chance)
function markWrongChoice(chosen, answerId) {
    document.querySelectorAll('.option-btn').forEach(btn => {
        const text = btn.dataset.opt || btn.querySelector('span:last-child')?.textContent;
        if (text === chosen) {
            btn.classList.add('wrong');
        }
        // On désactive TOUS les boutons pendant que l'adversaire décide s'il buzze.
        // (Ils seront réactivés quand l'adversaire buzzera, dans onBuzzed.)
        btn.disabled = true;
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
    const ratio    = timerMax > 0 ? Math.max(0, timerLeft) / timerMax : 0;
    const isDanger = timerLeft <= 5;
    const fill = $('timer-fill');
    const num  = $('timer-num');
    const ring = $('ring-progress');

    // Legacy bar (caché mais maintenu)
    if (fill) {
        fill.style.width = pct + '%';
        fill.classList.toggle('danger', isDanger);
    }

    // SVG ring (nouveau visuel principal)
    if (ring) {
        const C = 2 * Math.PI * 110; // r=110 → ~691.15
        ring.setAttribute('stroke-dashoffset', C * (1 - ratio));
        let col = '#d4af37';
        if (ratio < 0.3)      col = '#e05555';
        else if (ratio < 0.5) col = '#f59e0b';
        ring.style.stroke = col;
        ring.style.filter = `drop-shadow(0 0 6px ${col})`;
    }

    if (num) {
        num.textContent = timerLeft;
        num.classList.toggle('danger', isDanger);
    }
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
        $('p1-name').textContent   = me.name || 'Joueur 1';
        $('p1-elo').textContent    = me.elo  || 1200;
        setAvatar('p1-avatar', me.name, me.profile_pic || null);
        renderStreak('p1-streak', streakFromScore(me.score || 0), 'p1');
    }
    if (opp) {
        $('p2-name').textContent   = opp.name || 'Joueur 2';
        $('p2-elo').textContent    = opp.elo  || 1200;
        setAvatar('p2-avatar', opp.name, opp.profile_pic || null);
        renderStreak('p2-streak', streakFromScore(opp.score || 0), 'p2');
    }
}

// 2 caractères d'initiales (prend "First Last" → "FL", sinon les 2 premiers chars)
function getInitials(name) {
    if (!name) return '?';
    const parts = String(name).trim().split(/[\s_\-\.]+/).filter(Boolean);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return String(name).substring(0, 2).toUpperCase();
}

// Avatar : image si profile_pic, sinon initiales
function setAvatar(elId, name, picUrl) {
    const el = $(elId);
    if (!el) return;
    if (picUrl) {
        el.innerHTML = `<img src="${picUrl}" alt="${name || ''}" onerror="this.parentNode.textContent='${getInitials(name)}'">`;
    } else {
        el.textContent = getInitials(name);
    }
}

// Streak basé sur le score (visuel de momentum, 0-5 dots)
function streakFromScore(score) {
    return Math.min(5, Math.max(0, Math.floor(score / 100)));
}

function renderStreak(elId, count, who) {
    const c = $(elId);
    if (!c) return;
    c.innerHTML = '';
    for (let i = 0; i < 5; i++) {
        const d = document.createElement('div');
        d.className = 'dot' + (i < count ? ' on ' + who : '');
        c.appendChild(d);
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
// FOND : gemmes wireframe SVG flottantes
//   Une seule passe d'init au boot. Les animations tournent ensuite
//   100% côté CSS / GPU (transform composé) — aucun rAF, aucun coût
//   sur le thread principal pendant le jeu.
// ══════════════════════════════════════════════════
function initBgGems() {
    const host = $('bg-gems');
    if (!host) return;

    // Un seul markup SVG pour un octaèdre filaire — réutilisé partout via cloneNode.
    const svgNS = 'http://www.w3.org/2000/svg';
    function buildGemSvg() {
        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 100 100');
        const g = document.createElementNS(svgNS, 'g');
        g.setAttribute('fill', 'none');
        g.setAttribute('stroke', '#d4af37');
        g.setAttribute('stroke-width', '1.4');
        g.setAttribute('stroke-linejoin', 'round');
        // Contour + lignes internes (vue de face de l'octaèdre)
        const paths = [
            'M50,8 L92,50 L50,92 L8,50 Z',  // contour losange
            'M50,8 L50,92',                  // diag verticale
            'M8,50 L92,50',                  // diag horizontale
            'M50,8 L35,50 L50,92 L65,50 Z'   // facettes internes
        ];
        paths.forEach(d => {
            const p = document.createElementNS(svgNS, 'path');
            p.setAttribute('d', d);
            g.appendChild(p);
        });
        svg.appendChild(g);
        return svg;
    }

    const COUNT = 16;
    const variants = ['v1', 'v2', 'v3'];
    const W = window.innerWidth, H = window.innerHeight;
    const frag = document.createDocumentFragment();

    for (let i = 0; i < COUNT; i++) {
        const gem = document.createElement('div');
        const v = variants[i % variants.length];
        const size = 40 + Math.random() * 90;        // 40-130 px
        const opacity = 0.10 + Math.random() * 0.25; // 0.10-0.35
        const dur = 12 + Math.random() * 14;         // 12-26s par cycle
        const delay = -Math.random() * dur;          // démarrages décorrelés
        gem.className = 'bg-gem ' + v;
        gem.style.cssText =
            'left:'   + (Math.random() * W) + 'px;' +
            'top:'    + (Math.random() * H) + 'px;' +
            'width:'  + size + 'px;' +
            'height:' + size + 'px;' +
            'opacity:' + opacity + ';' +
            'animation-duration:' + dur + 's;' +
            'animation-delay:'    + delay + 's;';
        gem.appendChild(buildGemSvg());
        frag.appendChild(gem);
    }
    host.appendChild(frag);
}


// ══════════════════════════════════════════════════
// START
// ══════════════════════════════════════════════════
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { initBgGems(); init(); });
} else {
    initBgGems();
    init();
}
