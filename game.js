// ============================================================================
//  CHAMPIONNAT - Client prototype
//  Etape D : lobby + M1 + transition M2 simulee + M3 + final
// ============================================================================

(() => {

    // -------------------------------------------------------------------
    //  Identite (vient de PHP via window.QPC_USER, ou guest)
    // -------------------------------------------------------------------
    let USER_ID, PLAYER_ID, currentName, MY_ELO;
    if (window.QPC_USER && window.QPC_USER.id) {
        // Utilisateur connecté
        USER_ID     = window.QPC_USER.id;
        PLAYER_ID   = 'u' + USER_ID;       // stable, basé sur user_id BDD
        currentName = window.QPC_USER.username;
        MY_ELO      = window.QPC_USER.elo;
    } else {
        // Mode invité : on lit depuis localStorage (le lobby a stocké qpc_player_id et qpc_name)
        USER_ID = null;

        // IMPORTANT : l'identité guest doit rester STRICTEMENT la même
        // pendant tout le tournoi (M1 → M2 → M3 → reconnect).
        let storedId = localStorage.getItem('qpc_player_id');

        if (!storedId || storedId.startsWith('u')) {
            storedId = 'guest_' + crypto.randomUUID();
            localStorage.setItem('qpc_player_id', storedId);
        }

        PLAYER_ID = storedId;

        currentName = localStorage.getItem('qpc_name') || 'Joueur';
        localStorage.setItem('qpc_name', currentName);

        MY_ELO = 1200;
    }
    let currentCode = window.QPC_ROOM_CODE || null;
    let roomPlayers = [];

    // ── URL de retour selon l'état de l'utilisateur (connecté → dashboard, guest → hub)
    function returnUrl() {
        return window.QPC_USER ? '../dashboard.php' : '../game.php';
    }

    // Etats M1
    let m1MyAnswer = null;
    let tbMyAnswer = null;

    // Etats M3
    let m3Finalists = [];        // [idA, idB]
    let m3IsFinalist = false;    // suis-je en M3 ?
    let m3MyCategories = new Set();
    let m3MyBetQ = null;
    let m3MyBetAmt = null;
    let m3MyBet = null;          // pari verrouille pour cette partie
    let m3CanBuzz = false;
    let m3IsBuzzing = false;
    let m3HasBuzzedThisQ = false;
    let m3CurrentScores = {};

    let timerInterval = null;

    // -------------------------------------------------------------------
    //  Refs DOM
    // -------------------------------------------------------------------
    const screens = {
        loading:        document.getElementById('screen-loading'),
        countdown:      document.getElementById('screen-countdown'),
        m1:             document.getElementById('screen-m1'),
        tiebreak:       document.getElementById('screen-tiebreak'),
        m1end:          document.getElementById('screen-m1-end'),
        m2simulated:    document.getElementById('screen-m2-simulated'),
        m3categories:   document.getElementById('screen-m3-categories'),
        m3bet:          document.getElementById('screen-m3-bet'),
        m3duel:         document.getElementById('screen-m3-duel'),
        m3sudden:       document.getElementById('screen-m3-sudden'),
        final:          document.getElementById('screen-final')
    };
    const els = {
        loadingMsg:   document.getElementById('loading-msg'),
        countdownNum: document.getElementById('countdown-number'),
        // M1
        m1QCounter:  document.getElementById('m1-q-counter'),
        m1Players:   document.getElementById('m1-players'),
        m1Category:  document.getElementById('m1-category'),
        m1QText:     document.getElementById('m1-question-text'),
        m1Options:   document.getElementById('m1-options'),
        m1TimerFill: document.getElementById('m1-timer-fill'),
        m1TimerText: document.getElementById('m1-timer-text'),
        m1Status:    document.getElementById('m1-status'),
        // Tiebreak
        tbRound:     document.getElementById('tb-round'),
        tbCategory:  document.getElementById('tb-category'),
        tbQText:     document.getElementById('tb-question-text'),
        tbOptions:   document.getElementById('tb-options'),
        tbTimerFill: document.getElementById('tb-timer-fill'),
        tbTimerText: document.getElementById('tb-timer-text'),
        tbStatus:    document.getElementById('tb-status'),
        tbInfo:      document.getElementById('tiebreak-info'),
        // Fin M1
        m1EndRanking: document.getElementById('m1-end-ranking'),
        m1EndMsg:     document.getElementById('m1-end-message'),
        // M2 sim
        m2Finalists: document.getElementById('m2-finalists'),
        // M3 categories
        m3CatTimerText: document.getElementById('m3-cat-timer-text'),
        m3CatInstr:     document.getElementById('m3-cat-instruction'),
        m3CatGrid:      document.getElementById('m3-categories-grid'),
        m3CatCounter:   document.getElementById('m3-cat-counter'),
        m3CatValidate:  document.getElementById('btn-m3-validate-cats'),
        m3CatStatus:    document.getElementById('m3-cat-status'),
        // M3 bet
        m3BetTimerText: document.getElementById('m3-bet-timer-text'),
        m3PoolDisplay:  document.getElementById('m3-pool-display'),
        m3BetQs:        document.getElementById('m3-bet-questions'),
        m3BetAmts:      document.getElementById('m3-bet-amounts'),
        m3BetValidate:  document.getElementById('btn-m3-validate-bet'),
        m3BetStatus:    document.getElementById('m3-bet-status'),
        // M3 duel
        m3QCounter:     document.getElementById('m3-q-counter'),
        m3DuelPlayers:  document.getElementById('m3-duel-players'),
        m3BetBanner:    document.getElementById('m3-bet-banner'),
        m3BetBannerText:document.getElementById('m3-bet-banner-text'),
        m3Category:     document.getElementById('m3-category'),
        m3TimerFill:    document.getElementById('m3-timer-fill'),
        m3TimerText:    document.getElementById('m3-timer-text'),
        m3QText:        document.getElementById('m3-question-text'),
        m3BuzzBtn:      document.getElementById('btn-m3-buzz'),
        m3BuzzSub:      document.getElementById('m3-buzz-sub'),
        m3Options:      document.getElementById('m3-options'),
        m3Status:       document.getElementById('m3-status'),
        // M3 sudden death
        m3SDRound:      document.getElementById('m3-sd-round'),
        m3SDPlayers:    document.getElementById('m3-sd-players'),
        m3SDCategory:   document.getElementById('m3-sd-category'),
        m3SDTimerFill:  document.getElementById('m3-sd-timer-fill'),
        m3SDTimerText:  document.getElementById('m3-sd-timer-text'),
        m3SDQText:      document.getElementById('m3-sd-question-text'),
        m3SDBuzzBtn:    document.getElementById('btn-m3-sd-buzz'),
        m3SDOptions:    document.getElementById('m3-sd-options'),
        m3SDStatus:     document.getElementById('m3-sd-status'),
        // Final
        finalWinnerName: document.getElementById('final-winner-name'),
        btnBackLobby:     document.getElementById('btn-back-lobby'),
        btnBackDashboard: document.getElementById('btn-back-dashboard'),
        // Banner spectator
        spectatorBanner: document.getElementById('spectator-banner')
    };

    // (Pas de inputName en game.php — le pseudo vient déjà de window.QPC_USER)

    // -------------------------------------------------------------------
    //  Helpers
    // -------------------------------------------------------------------
    function showScreen(name) {
        Object.values(screens).forEach(s => s.classList.remove('active'));
        screens[name].classList.add('active');
    }
    function showError(msg) {
        // En game.php, pas de homeError → on affiche dans loadingMsg ou alert
        if (els.loadingMsg) {
            els.loadingMsg.innerHTML = `<span style="color:#e54848">⚠️ ${msg}</span>`;
        }
        console.warn('[CHAMP-GAME]', msg);
    }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    function clearTimer() {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
    }
    function setSpectator(isSpectator) {
        els.spectatorBanner.style.display = isSpectator ? 'block' : 'none';
    }
    function genericTimer(deadline, totalMs, fillEl, textEl, onEnd) {
        clearTimer();
        const update = () => {
            const remaining = Math.max(0, deadline - Date.now());
            const pct = Math.max(0, (remaining / totalMs) * 100);
            fillEl.style.width = pct + '%';
            textEl.textContent = Math.ceil(remaining / 1000);
            fillEl.classList.toggle('warning', pct < 50 && pct >= 25);
            fillEl.classList.toggle('danger',  pct < 25);
            if (remaining <= 0) {
                clearTimer();
                if (onEnd) onEnd();
            }
        };
        update();
        timerInterval = setInterval(update, 100);
    }
    function simpleCountdownTimer(deadline, totalMs, textEl) {
        clearTimer();
        const update = () => {
            const remaining = Math.max(0, deadline - Date.now());
            textEl.textContent = Math.ceil(remaining / 1000);
            if (remaining <= 0) clearTimer();
        };
        update();
        timerInterval = setInterval(update, 200);
    }

    // -------------------------------------------------------------------
    //  LOBBY rendering — pas utilisé en game.php (le lobby est dans lobby.php)
    //  On garde juste la mise à jour de roomPlayers pour les écrans M1/M2/M3.
    // -------------------------------------------------------------------
    function syncRoomPlayers(room) {
        roomPlayers = room.players;
        if (room.code) currentCode = room.code;
    }


    // -------------------------------------------------------------------
    //  M1 rendering
    // -------------------------------------------------------------------
    function renderM1Players(opts = {}) {
        const answered = opts.answeredIds || new Set();
        const correct  = opts.correctIds  || new Set();
        const wrong    = opts.wrongIds    || new Set();
        const deltas   = opts.deltas      || {};
        let html = '';
        roomPlayers.forEach(p => {
            const isMe   = p.id === PLAYER_ID;
            const isDead = p.alive === false;
            const classes = [
                'm1-player-card',
                isMe ? 'is-me' : '',
                isDead ? 'dead' : '',
                answered.has(p.id) ? 'answered' : '',
                correct.has(p.id) ? 'correct' : '',
                wrong.has(p.id) ? 'wrong' : ''
            ].filter(Boolean).join(' ');
            const initial = p.name.charAt(0).toUpperCase();
            const delta = deltas[p.id];
            const deltaHtml = (delta !== undefined && delta !== 0)
                ? `<span class="score-delta ${delta > 0 ? 'plus' : 'minus'}">${delta > 0 ? '+' : ''}${delta}</span>`
                : '';
            html += `
                <div class="${classes}" data-id="${p.id}">
                    <div class="answer-dot"></div>
                    <div class="mini-avatar">${initial}</div>
                    <div class="m1-player-info">
                        <div class="name">${escapeHtml(p.name)}${isMe ? ' (toi)' : ''}</div>
                        <div class="score-row">
                            <span class="score">${p.score} pts</span>
                            ${deltaHtml}
                        </div>
                    </div>
                </div>`;
        });
        els.m1Players.innerHTML = html;
    }

    function renderM1Options(options) {
        const letters = ['A', 'B', 'C', 'D'];
        let html = '';
        options.forEach((opt, idx) => {
            html += `
                <button class="m1-option" data-idx="${idx}">
                    <div class="opt-letter">${letters[idx]}</div>
                    <span class="opt-text">${escapeHtml(opt)}</span>
                </button>`;
        });
        els.m1Options.innerHTML = html;
        els.m1Options.querySelectorAll('.m1-option').forEach(btn => {
            btn.addEventListener('click', () => {
                if (m1MyAnswer !== null) return;
                const idx = parseInt(btn.dataset.idx, 10);
                m1MyAnswer = idx;
                els.m1Options.querySelectorAll('.m1-option').forEach((b, i) => {
                    b.disabled = true;
                    if (i === idx) b.classList.add('selected');
                });
                els.m1Status.textContent = 'Réponse envoyée. En attente des autres…';
                socket.emit('m1_answer', { answer: idx });
            });
        });
    }

    function renderTbOptions(options) {
        const letters = ['A', 'B', 'C', 'D'];
        let html = '';
        options.forEach((opt, idx) => {
            html += `
                <button class="m1-option" data-idx="${idx}">
                    <div class="opt-letter">${letters[idx]}</div>
                    <span class="opt-text">${escapeHtml(opt)}</span>
                </button>`;
        });
        els.tbOptions.innerHTML = html;
        els.tbOptions.querySelectorAll('.m1-option').forEach(btn => {
            btn.addEventListener('click', () => {
                if (tbMyAnswer !== null) return;
                const idx = parseInt(btn.dataset.idx, 10);
                tbMyAnswer = idx;
                els.tbOptions.querySelectorAll('.m1-option').forEach((b, i) => {
                    b.disabled = true;
                    if (i === idx) b.classList.add('selected');
                });
                els.tbStatus.textContent = 'Réponse envoyée…';
                socket.emit('m1_tiebreak_answer', { answer: idx });
            });
        });
    }

    // -------------------------------------------------------------------
    //  M3 rendering
    // -------------------------------------------------------------------
    function renderM3Categories(categories) {
        let html = '';
        categories.forEach(cat => {
            html += `<button class="m3-cat-chip" data-cat="${cat}">${cat.replace(/_/g, ' ')}</button>`;
        });
        els.m3CatGrid.innerHTML = html;
        m3MyCategories = new Set();
        // m2MyCategories sera resettée si on est en mode m2 (cf. m2_started)
        els.m3CatGrid.querySelectorAll('.m3-cat-chip').forEach(btn => {
            btn.addEventListener('click', () => {
                // On synchronise les 2 sets (le bouton validate lira le bon selon dataset.mode)
                const mode = els.m3CatValidate.dataset.mode || 'm3';
                const targetSet = (mode === 'm2') ? m2MyCategories : m3MyCategories;
                const cat = btn.dataset.cat;
                if (targetSet.has(cat)) {
                    targetSet.delete(cat);
                    btn.classList.remove('selected');
                } else if (targetSet.size < 4) {
                    targetSet.add(cat);
                    btn.classList.add('selected');
                }
                els.m3CatCounter.textContent = `${targetSet.size} / 4 sélectionnées`;
                els.m3CatValidate.disabled = (targetSet.size !== 4);
            });
        });
        els.m3CatCounter.textContent = `0 / 4 sélectionnées`;
        els.m3CatValidate.disabled = true;
    }

    function renderM3DuelPlayers(buzzingId = null) {
        let html = '';
        m3Finalists.forEach(id => {
            const p = roomPlayers.find(pl => pl.id === id);
            if (!p) return;
            const isMe = id === PLAYER_ID;
            const initial = p.name.charAt(0).toUpperCase();
            const score = m3CurrentScores[id] || 0;
            const classes = [
                'm3-player-card',
                isMe ? 'is-me' : '',
                buzzingId === id ? 'buzzing' : ''
            ].filter(Boolean).join(' ');
            html += `
                <div class="${classes}" data-id="${id}">
                    <div class="big-avatar">${initial}</div>
                    <div class="player-info">
                        <div class="name">${escapeHtml(p.name)}${isMe ? ' (toi)' : ''}</div>
                        <div class="score-row">
                            <span class="score-big">${score}</span>
                            <span class="score-sub">pts</span>
                        </div>
                    </div>
                </div>`;
        });
        els.m3DuelPlayers.innerHTML = html;
        // Pour la mort subite, on duplique
        els.m3SDPlayers.innerHTML = html;
    }

    function renderM3Options(options, target = 'duel') {
        const letters = ['A', 'B', 'C', 'D'];
        const container = (target === 'duel') ? els.m3Options : els.m3SDOptions;
        let html = '';
        options.forEach((opt, idx) => {
            // [D.1] boutons desactives par defaut, ne s'activent qu'apres buzz reussi
            html += `
                <button class="m1-option" data-idx="${idx}" disabled>
                    <div class="opt-letter">${letters[idx]}</div>
                    <span class="opt-text">${escapeHtml(opt)}</span>
                </button>`;
        });
        container.innerHTML = html;
        container.querySelectorAll('.m1-option').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!m3IsBuzzing) return;
                const idx = parseInt(btn.dataset.idx, 10);
                m3IsBuzzing = false;
                container.querySelectorAll('.m1-option').forEach((b, i) => {
                    b.disabled = true;
                    if (i === idx) b.classList.add('selected');
                });
                socket.emit('m3_buzz_answer', { answer: idx });
            });
        });
    }

    // -------------------------------------------------------------------
    //  SOCKET
    // -------------------------------------------------------------------
    const SERVER_URL = 'http://localhost:3000';
    const socket = io(SERVER_URL, { transports: ['websocket', 'polling'] });

    socket.on('connect', () => console.log('[SOCKET] connected', socket.id));

    socket.on('champ_error', ({ message }) => {
        showError(message);
    });

    socket.on('champ_room_joined', ({ code }) => {
        currentCode = code;
        if (els.loadingMsg) els.loadingMsg.innerHTML = '✅ Connecté ! En attente des autres joueurs…';
    });

    socket.on('room_state', (room) => {
        syncRoomPlayers(room);
    });

    // ─── Déconnexion / reconnexion / disqualification ─────────
    socket.on('champ_player_disconnected', ({ playerId, playerName, timeout }) => {
        const isMe = (playerId === PLAYER_ID);
        if (!isMe) {
            const statusEl = document.querySelector('.m1-status:not([style])') ||
                             document.getElementById('m1-status') ||
                             document.getElementById('m3-status');
            if (statusEl) statusEl.textContent = `⚠️ ${playerName} a déconnecté (10s pour revenir)…`;
        }
    });
    socket.on('champ_player_reconnected', ({ playerId, playerName }) => {
        const statusEl = document.querySelector('.m1-status:not([style])') ||
                         document.getElementById('m1-status') ||
                         document.getElementById('m3-status');
        if (statusEl) statusEl.textContent = `✅ ${playerName} est revenu !`;
    });
    socket.on('champ_player_disqualified', ({ playerId, playerName, message }) => {
        const statusEl = document.querySelector('.m1-status:not([style])') ||
                         document.getElementById('m1-status') ||
                         document.getElementById('m3-status');
        if (statusEl) statusEl.textContent = `❌ ${message}`;
    });

    socket.on('champ_match_starting', ({ in: countStart }) => {
        els.countdownNum.textContent = countStart;
        showScreen('countdown');
    });
    socket.on('champ_countdown', ({ count }) => {
        els.countdownNum.textContent = count;
    });

    // ----- M1 -----
    socket.on('m1_started', () => {
        roomPlayers = roomPlayers.map(p => ({ ...p, score: 0, alive: true }));
    });

    socket.on('m1_question', (data) => {
        m1MyAnswer = null;
        showScreen('m1');
        els.m1QCounter.textContent = `Q${data.index} / ${data.total}`;
        els.m1Category.textContent = data.category.replace(/_/g, ' ');
        els.m1QText.textContent = data.question;
        renderM1Options(data.options);
        renderM1Players();
        els.m1Status.textContent = '';
        genericTimer(data.deadline, data.time, els.m1TimerFill, els.m1TimerText);
    });

    socket.on('m1_player_answered', ({ playerId, answeredCount, totalAlive }) => {
        const card = els.m1Players.querySelector(`[data-id="${playerId}"]`);
        if (card) card.classList.add('answered');
        if (playerId === PLAYER_ID) {
            els.m1Status.textContent = `Tu as répondu. (${answeredCount}/${totalAlive})`;
        } else {
            const stillWaiting = totalAlive - answeredCount;
            if (m1MyAnswer !== null && stillWaiting > 0) {
                els.m1Status.textContent = `${answeredCount}/${totalAlive} ont répondu, en attente de ${stillWaiting}…`;
            }
        }
    });

    socket.on('m1_reveal', ({ correctAnswer, results, scores }) => {
        clearTimer();
        els.m1Options.querySelectorAll('.m1-option').forEach((btn, i) => {
            btn.disabled = true;
            btn.classList.remove('selected');
            if (i === correctAnswer) btn.classList.add('correct');
            else if (m1MyAnswer === i) btn.classList.add('wrong');
        });
        const deltas = {};
        const correctIds = new Set();
        const wrongIds = new Set();
        Object.entries(results).forEach(([id, r]) => {
            deltas[id] = r.delta;
            if (r.correct === true) correctIds.add(id);
            else if (r.correct === false) wrongIds.add(id);
        });
        roomPlayers = roomPlayers.map(p => {
            const sObj = scores.find(s => s.id === p.id);
            return sObj ? { ...p, score: sObj.score, alive: sObj.alive } : p;
        });
        renderM1Players({ correctIds, wrongIds, deltas });
        const myResult = results[PLAYER_ID];
        if (myResult) {
            if (myResult.correct === true)      els.m1Status.textContent = `✓ Bonne réponse ! +1 pt`;
            else if (myResult.correct === false) els.m1Status.textContent = `✗ Mauvaise réponse. -1 pt`;
            else                                 els.m1Status.textContent = `Trop tard. 0 pt.`;
        }
    });

    socket.on('m1_tiebreak_start', ({ contenderIds, contenderNames }) => {
        const isMe = contenderIds.includes(PLAYER_ID);
        showScreen('tiebreak');
        els.tbInfo.textContent = isMe
            ? `Ex aequo. Premier à rater = éliminé.`
            : `${contenderNames.join(', ')} se départagent en mort subite.`;
    });

    socket.on('m1_tiebreak_question', (data) => {
        tbMyAnswer = null;
        const isContender = data.contenderIds.includes(PLAYER_ID);
        els.tbRound.textContent = `Question ${data.round}`;
        els.tbCategory.textContent = data.category.replace(/_/g, ' ');
        els.tbQText.textContent = data.question;
        renderTbOptions(data.options);
        if (!isContender) {
            els.tbOptions.querySelectorAll('.m1-option').forEach(btn => btn.disabled = true);
            els.tbStatus.textContent = 'Tu observes — tu n\'es pas concerné par ce barrage.';
        } else {
            els.tbStatus.textContent = '';
        }
        genericTimer(data.deadline, data.time, els.tbTimerFill, els.tbTimerText);
    });

    socket.on('m1_tiebreak_answered', ({ playerId, answeredCount, totalContenders }) => {
        if (playerId === PLAYER_ID) {
            els.tbStatus.textContent = `Tu as répondu. (${answeredCount}/${totalContenders})`;
        }
    });

    socket.on('m1_tiebreak_reveal', ({ correctAnswer, correctIds, wrongIds }) => {
        clearTimer();
        els.tbOptions.querySelectorAll('.m1-option').forEach((btn, i) => {
            btn.disabled = true;
            btn.classList.remove('selected');
            if (i === correctAnswer) btn.classList.add('correct');
            else if (tbMyAnswer === i) btn.classList.add('wrong');
        });
        if (correctIds.includes(PLAYER_ID)) els.tbStatus.textContent = '✓ Tu es sauvé.';
        else if (wrongIds.includes(PLAYER_ID)) els.tbStatus.textContent = '✗ Tu as raté…';
    });

    socket.on('m1_finished', ({ ranking, eliminatedId, winnerId, message }) => {
        clearTimer();
        let html = '';
        ranking.forEach((p, i) => {
            const isEliminated = p.id === eliminatedId;
            const isWinner = p.id === winnerId;
            const rowClass = isEliminated ? 'eliminated' : (isWinner ? 'winner' : '');
            const tag = isEliminated ? '<span class="ranking-tag">Éliminé</span>'
                      : (isWinner ? '<span class="ranking-tag">1er M1</span>' : '');
            html += `
                <div class="ranking-row ${rowClass}">
                    <div class="rank">${i + 1}</div>
                    <div class="ranking-name">${escapeHtml(p.name)}${p.id === PLAYER_ID ? ' (toi)' : ''}</div>
                    <div class="ranking-score">${p.score} pts</div>
                    ${tag}
                </div>`;
        });
        els.m1EndRanking.innerHTML = html;
        els.m1EndMsg.textContent = message;
        // Si je suis l'eliminé, activer le mode spectateur
        if (eliminatedId === PLAYER_ID) {
            setSpectator(true);
        }
        showScreen('m1end');
    });

    // ============================================================
    //  MANCHE 2 (vraie) — réutilise les écrans M3 (catégories, pari, duel, fin)
    //  mais avec 3 joueurs au lieu de 2
    // ============================================================

    // Variables M2 (parallèles aux variables M3)
    let m2Players = [];
    let m2IsPlayer = false;
    let m2MyCategories = new Set();
    let m2MyBetQ = null;
    let m2MyBetAmt = null;
    let m2MyAnswer = null;          // [PARALLELE] réponse du joueur sur la Q en cours
    let m2CurrentScores = {};

    // Helper : adapter le header M3 pour M2
    function setM2HeaderTitle(phase) {
        const labelMap = {
            categories: { label: 'Manche 2 · Phase 1', title: 'Choix des catégories' },
            bet:        { label: 'Manche 2 · Phase 2', title: 'Pari secret' },
            duel:       { label: 'Manche 2 · Duel à 3', title: 'Le 4 à la suite' }
        };
        const data = labelMap[phase];
        if (!data) return;
        // On modifie les badges des écrans M3 réutilisés
        const screenMap = {
            categories: '#screen-m3-categories',
            bet:        '#screen-m3-bet',
            duel:       '#screen-m3-duel'
        };
        const screen = document.querySelector(screenMap[phase]);
        if (screen) {
            const label = screen.querySelector('.manche-label');
            const title = screen.querySelector('.manche-title');
            if (label) label.textContent = data.label;
            if (title) title.textContent = data.title;
        }
    }

    socket.on('m2_started', ({ playersIds, playersNames, availableCategories, selectionTime, totalQuestions }) => {
        m2Players = playersIds;
        m2IsPlayer = playersIds.includes(PLAYER_ID);
        m2CurrentScores = {};
        playersIds.forEach(id => { m2CurrentScores[id] = 0; });
        m2MyCategories = new Set();  // Reset pour ce tour

        // On réutilise renderM3Categories
        renderM3Categories(availableCategories);
        // [HACK] Marquer le mode m2 AVANT d'afficher (pour que les clicks sur cat aillent dans m2MyCategories)
        els.m3CatValidate.dataset.mode = 'm2';
        setM2HeaderTitle('categories');

        if (m2IsPlayer) {
            els.m3CatInstr.textContent = 'Choisis 4 catégories parmi les 8 (M2 à 3 joueurs).';
            els.m3CatValidate.style.display = '';
            els.m3CatStatus.textContent = '';
        } else {
            els.m3CatInstr.textContent = 'Les 3 joueurs choisissent leurs catégories.';
            els.m3CatGrid.querySelectorAll('.m3-cat-chip').forEach(b => b.disabled = true);
            els.m3CatValidate.style.display = 'none';
            els.m3CatStatus.textContent = 'Mode spectateur.';
            setSpectator(true);
        }
        showScreen('m3categories');
        simpleCountdownTimer(Date.now() + selectionTime, selectionTime, els.m3CatTimerText);
    });

    socket.on('m2_player_selected_categories', ({ playerId, selectedCount }) => {
        if (playerId === PLAYER_ID) {
            els.m3CatStatus.textContent = `✓ Tes catégories validées. En attente (${selectedCount}/3)…`;
        } else {
            els.m3CatStatus.textContent = `Un joueur a validé (${selectedCount}/3).`;
        }
    });

    socket.on('m2_category_pool', ({ pool }) => {
        clearTimer();
        let html = '';
        pool.forEach(cat => {
            html += `<span class="m3-pool-cat">${cat.replace(/_/g, ' ')}</span>`;
        });
        els.m3PoolDisplay.innerHTML = html;
    });

    socket.on('m2_bet_phase', ({ time, deadline, totalQuestions }) => {
        m2MyBetQ = null;
        m2MyBetAmt = null;
        setM2HeaderTitle('bet');
        // Reconstruire les boutons Q selon totalQuestions (8 pour M2 vs 5 pour M3)
        let qHtml = '';
        for (let i = 1; i <= totalQuestions; i++) {
            qHtml += `<button class="m3-bet-chip" data-q="${i}">Q${i}</button>`;
        }
        els.m3BetQs.innerHTML = qHtml;
        // Re-binder events sur les nouveaux chips
        els.m3BetQs.querySelectorAll('.m3-bet-chip').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!m2IsPlayer) return;
                els.m3BetQs.querySelectorAll('.m3-bet-chip').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                m2MyBetQ = parseInt(btn.dataset.q, 10);
                els.m3BetValidate.disabled = !(m2MyBetQ && m2MyBetAmt);
            });
        });
        els.m3BetAmts.querySelectorAll('.m3-bet-chip').forEach(b => {
            b.classList.remove('selected');
            b.disabled = !m2IsPlayer;
        });
        els.m3BetValidate.disabled = true;
        els.m3BetValidate.dataset.mode = 'm2';
        if (m2IsPlayer) {
            els.m3BetStatus.textContent = '';
            els.m3BetValidate.style.display = '';
        } else {
            els.m3BetStatus.textContent = 'Mode spectateur.';
            els.m3BetValidate.style.display = 'none';
        }
        showScreen('m3bet');
        simpleCountdownTimer(deadline, time, els.m3BetTimerText);
    });

    socket.on('m2_player_bet', ({ playerId, betCount }) => {
        if (playerId === PLAYER_ID) {
            els.m3BetStatus.textContent = `✓ Ta mise verrouillée. En attente (${betCount}/3)…`;
        } else {
            els.m3BetStatus.textContent = `Un joueur a verrouillé (${betCount}/3).`;
        }
    });

    socket.on('m2_bets_locked', ({ message }) => {
        clearTimer();
        els.m3BetStatus.textContent = message;
    });

    // Helper pour afficher les 3 joueurs M2 (au lieu des 2 de M3)
    function renderM2Players(buzzingId = null) {
        let html = '';
        m2Players.forEach(id => {
            const p = roomPlayers.find(pl => pl.id === id);
            if (!p) return;
            const isMe = id === PLAYER_ID;
            const initial = p.name.charAt(0).toUpperCase();
            const score = m2CurrentScores[id] || 0;
            const classes = [
                'm3-player-card',
                isMe ? 'is-me' : '',
                buzzingId === id ? 'buzzing' : ''
            ].filter(Boolean).join(' ');
            html += `
                <div class="${classes}" data-id="${id}">
                    <div class="big-avatar">${initial}</div>
                    <div class="player-info">
                        <div class="name">${escapeHtml(p.name)}${isMe ? ' (toi)' : ''}</div>
                        <div class="score-row">
                            <span class="score-big">${score}</span>
                            <span class="score-sub">pts</span>
                        </div>
                    </div>
                </div>`;
        });
        els.m3DuelPlayers.innerHTML = html;
        // Forcer le grid à 3 colonnes pour M2
        els.m3DuelPlayers.style.gridTemplateColumns = '1fr 1fr 1fr';
    }

    // [PARALLELE] M2 question : question + options direct, chacun clique sa réponse
    socket.on('m2_question', (data) => {
        m2MyAnswer = null;
        setM2HeaderTitle('duel');

        // Reset des titres écran
        const sc = document.querySelector('#screen-m3-duel');
        if (sc) {
            // [HACK] Cacher le buzzer (inutile en mode parallèle)
            const buzzZone = sc.querySelector('.m3-buzz-zone');
            if (buzzZone) buzzZone.style.display = 'none';
        }
        els.m3DuelPlayers.style.gridTemplateColumns = '1fr 1fr 1fr';

        showScreen('m3duel');
        els.m3QCounter.textContent = `Q${data.index} / ${data.total}`;
        els.m3Category.textContent = data.category.replace(/_/g, ' ');
        els.m3QText.textContent = data.question;
        renderM2Players();

        // Render des options cliquables (style M1)
        const letters = ['A', 'B', 'C', 'D'];
        let html = '';
        data.options.forEach((opt, idx) => {
            html += `<button class="m1-option" data-idx="${idx}">
                <div class="opt-letter">${letters[idx]}</div>
                <span class="opt-text">${escapeHtml(opt)}</span>
            </button>`;
        });
        els.m3Options.innerHTML = html;
        els.m3Options.classList.add('revealed');

        const canAnswer = m2IsPlayer;
        els.m3Options.querySelectorAll('.m1-option').forEach(btn => {
            if (!canAnswer) {
                btn.disabled = true;
                return;
            }
            btn.addEventListener('click', () => {
                if (m2MyAnswer !== null) return;
                const idx = parseInt(btn.dataset.idx, 10);
                m2MyAnswer = idx;
                els.m3Options.querySelectorAll('.m1-option').forEach((b, i) => {
                    b.disabled = true;
                    if (i === idx) b.classList.add('selected');
                });
                els.m3Status.textContent = 'Réponse envoyée. En attente des autres…';
                socket.emit('m2_answer', { answer: idx });
            });
        });

        els.m3Status.textContent = canAnswer ? 'Choisis ta réponse !' : 'Spectateur';

        // Bandeau pari
        const bets = data.bets || {};
        const betEntries = Object.entries(bets);
        if (betEntries.length > 0) {
            const lines = betEntries.map(([id, amt]) => {
                const p = roomPlayers.find(pl => pl.id === id);
                return `${p ? p.name : '?'} a misé <strong>${amt} pt${amt > 1 ? 's' : ''}</strong>`;
            });
            els.m3BetBannerText.innerHTML = lines.join(' · ');
            els.m3BetBanner.style.display = 'flex';
        } else {
            els.m3BetBanner.style.display = 'none';
        }

        // Reset timer + lancer
        els.m3TimerFill.style.width = '100%';
        els.m3TimerFill.classList.remove('warning', 'danger', 'paused');
        genericTimer(data.deadline, data.time, els.m3TimerFill, els.m3TimerText);
    });

    // [PARALLELE] Notification quand un joueur a répondu (pastilles ✓)
    socket.on('m2_player_answered', ({ playerId, answeredCount, totalPlayers }) => {
        // Mettre la classe "answered" sur la carte joueur correspondante
        const card = els.m3DuelPlayers.querySelector(`[data-id="${playerId}"]`);
        if (card) card.classList.add('answered');
        if (playerId === PLAYER_ID) {
            els.m3Status.textContent = `Tu as répondu (${answeredCount}/${totalPlayers}). En attente…`;
        } else if (m2MyAnswer !== null) {
            const waiting = totalPlayers - answeredCount;
            if (waiting > 0) {
                els.m3Status.textContent = `${answeredCount}/${totalPlayers} ont répondu, en attente de ${waiting}…`;
            }
        }
    });

    // [PARALLELE] Révélation : tout le monde voit la bonne réponse + les scores
    socket.on('m2_reveal', ({ correctAnswer, results, scores }) => {
        clearTimer();
        els.m3TimerFill.classList.remove('paused');

        els.m3Options.querySelectorAll('.m1-option').forEach((btn, i) => {
            btn.disabled = true;
            btn.classList.remove('selected');
            if (i === correctAnswer) btn.classList.add('correct');
            else if (m2MyAnswer === i) btn.classList.add('wrong');
        });

        if (scores) scores.forEach(s => { m2CurrentScores[s.id] = s.score; });
        renderM2Players();

        // Status perso
        if (results && results[PLAYER_ID]) {
            const r = results[PLAYER_ID];
            let msg = '';
            if (r.correct === true) msg = `✓ Bonne réponse ! ${r.delta >= 0 ? '+' : ''}${r.delta} pts`;
            else if (r.correct === false) msg = `✗ Mauvaise réponse. ${r.delta} pts`;
            else msg = `Pas de réponse. 0 pt.`;
            if (r.betAmount > 0) msg += ` (pari de ${r.betAmount})`;
            els.m3Status.textContent = msg;
        }
    });

    socket.on('m2_finished', ({ ranking, eliminatedId, finalistsIds, finalistsNames, message }) => {
        clearTimer();
        // On affiche l'écran m2simulated (réutilisation) avec le vrai classement
        let html = '';
        ranking.forEach((p, i) => {
            const isEliminated = p.id === eliminatedId;
            const cls = isEliminated ? 'eliminated' : 'winner';
            const tag = isEliminated ? 'Éliminé M2' : 'Finaliste';
            html += `
                <div class="ranking-row ${cls}">
                    <div class="rank">${i + 1}</div>
                    <div class="ranking-name">${escapeHtml(p.name)}${p.id === PLAYER_ID ? ' (toi)' : ''}</div>
                    <div class="ranking-score">${p.score} pts</div>
                    <span class="ranking-tag">${tag}</span>
                </div>`;
        });
        els.m2Finalists.innerHTML = html;
        if (eliminatedId === PLAYER_ID) setSpectator(true);
        // Mettre à jour le titre de l'écran
        const titleEl = screens.m2simulated.querySelector('.m1-end-title');
        if (titleEl) titleEl.textContent = 'Manche 2 terminée';
        showScreen('m2simulated');
    });

    // Tiebreak M2 : on réutilise l'écran tiebreak M1
    socket.on('m2_tiebreak_start', ({ contenderIds, contenderNames }) => {
        const isMe = contenderIds.includes(PLAYER_ID);
        showScreen('tiebreak');
        els.tbInfo.textContent = isMe
            ? `Tu es à égalité au dernier rang. Tu peux être éliminé.`
            : `${contenderNames.join(', ')} se départagent en mort subite.`;
    });

    socket.on('m2_tiebreak_question_reading', (data) => {
        const isContender = data.contenderIds.includes(PLAYER_ID);
        els.tbRound.textContent = `Question ${data.round}`;
        els.tbCategory.textContent = data.category.replace(/_/g, ' ');
        els.tbQText.textContent = data.question;
        els.tbOptions.innerHTML = '';
        els.tbStatus.textContent = isContender ? 'Lis la question…' : 'Tu observes le barrage.';
        els.tbTimerFill.style.width = '100%';
        els.tbTimerText.textContent = '—';
    });

    socket.on('m2_tiebreak_options_revealed', (data) => {
        // Pour le tiebreak M2 on garde simple : on affiche les options mais elles sont passives
        // (le timeout du serveur passe à la question suivante)
        const letters = ['A', 'B', 'C', 'D'];
        let html = '';
        data.options.forEach((opt, idx) => {
            html += `<button class="m1-option" data-idx="${idx}" disabled>
                <div class="opt-letter">${letters[idx]}</div>
                <span class="opt-text">${escapeHtml(opt)}</span>
            </button>`;
        });
        els.tbOptions.innerHTML = html;
    });

    socket.on('m2_tiebreak_buzz_open', (data) => {
        genericTimer(data.deadline, data.time, els.tbTimerFill, els.tbTimerText);
        els.tbStatus.textContent = 'Buzz ouvert ! (Implémentation tiebreak simplifiée : on attend le timeout)';
    });

    socket.on('m2_tiebreak_reveal', ({ correctAnswer }) => {
        clearTimer();
        els.tbOptions.querySelectorAll('.m1-option').forEach((btn, i) => {
            if (i === correctAnswer) btn.classList.add('correct');
        });
        els.tbStatus.textContent = 'On relance une nouvelle question…';
    });

    // ----- M3 -----
    socket.on('m3_started', ({ finalistsIds, finalistsNames, availableCategories, selectionTime }) => {
        m3Finalists = finalistsIds;
        m3IsFinalist = finalistsIds.includes(PLAYER_ID);
        m3CurrentScores = {};
        finalistsIds.forEach(id => { m3CurrentScores[id] = 0; });
        m3MyCategories = new Set();  // Reset pour ce tour

        renderM3Categories(availableCategories);
        // [HACK] Reset au mode m3 (au cas où on vient de M2)
        els.m3CatValidate.dataset.mode = 'm3';

        if (m3IsFinalist) {
            els.m3CatInstr.textContent = 'Choisis 4 catégories parmi les 8. Tu joueras potentiellement dans ces thèmes.';
            els.m3CatValidate.style.display = '';
            els.m3CatStatus.textContent = '';
        } else {
            // Spectateur : on bloque l'UI
            els.m3CatInstr.textContent = 'Les 2 finalistes choisissent leurs catégories.';
            els.m3CatGrid.querySelectorAll('.m3-cat-chip').forEach(b => b.disabled = true);
            els.m3CatValidate.style.display = 'none';
            els.m3CatStatus.textContent = 'Mode spectateur.';
            setSpectator(true);
        }
        showScreen('m3categories');
        simpleCountdownTimer(Date.now() + selectionTime, selectionTime, els.m3CatTimerText);
    });

    socket.on('m3_player_selected_categories', ({ playerId, selectedCount }) => {
        if (playerId === PLAYER_ID) {
            els.m3CatStatus.textContent = `✓ Tu as validé tes 4 catégories. En attente de l'adversaire (${selectedCount}/2)…`;
        } else {
            els.m3CatStatus.textContent = `L'adversaire a validé ses catégories (${selectedCount}/2).`;
        }
    });

    socket.on('m3_category_pool', ({ pool, selections, message }) => {
        clearTimer();
        let html = '';
        pool.forEach(cat => {
            html += `<span class="m3-pool-cat">${cat.replace(/_/g, ' ')}</span>`;
        });
        els.m3PoolDisplay.innerHTML = html;
    });

    socket.on('m3_bet_phase', ({ time, deadline, totalQuestions, possibleAmounts }) => {
        m3MyBetQ = null;
        m3MyBetAmt = null;
        // [HACK] Reconstruire dynamiquement les boutons Q (M3 = 7 maintenant, M2 = 8)
        const nbQ = totalQuestions || 7;
        let qHtml = '';
        for (let i = 1; i <= nbQ; i++) {
            qHtml += `<button class="m3-bet-chip" data-q="${i}">Q${i}</button>`;
        }
        els.m3BetQs.innerHTML = qHtml;
        // Re-binder
        els.m3BetQs.querySelectorAll('.m3-bet-chip').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!m3IsFinalist) return;
                els.m3BetQs.querySelectorAll('.m3-bet-chip').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                m3MyBetQ = parseInt(btn.dataset.q, 10);
                els.m3BetValidate.disabled = !(m3MyBetQ && m3MyBetAmt);
            });
        });
        els.m3BetAmts.querySelectorAll('.m3-bet-chip').forEach(b => {
            b.classList.remove('selected');
            b.disabled = !m3IsFinalist;
        });
        els.m3BetValidate.disabled = true;
        els.m3BetValidate.dataset.mode = 'm3';  // Reset mode m3
        if (m3IsFinalist) {
            els.m3BetStatus.textContent = '';
            els.m3BetValidate.style.display = '';
        } else {
            els.m3BetStatus.textContent = 'Mode spectateur — les finalistes placent leur pari.';
            els.m3BetValidate.style.display = 'none';
        }
        showScreen('m3bet');
        simpleCountdownTimer(deadline, time, els.m3BetTimerText);
    });

    socket.on('m3_player_bet', ({ playerId, betCount }) => {
        if (playerId === PLAYER_ID) {
            els.m3BetStatus.textContent = `✓ Ta mise est verrouillée. En attente de l'adversaire (${betCount}/2)…`;
        } else {
            els.m3BetStatus.textContent = `L'adversaire a verrouillé sa mise (${betCount}/2).`;
        }
    });

    socket.on('m3_bets_locked', ({ message }) => {
        clearTimer();
        els.m3BetStatus.textContent = message;
    });

    // [D.1] PHASE 1 : question seule, pas d'options, buzzer gris
    socket.on('m3_question_reading', (data) => {
        m3HasBuzzedThisQ = false;
        m3IsBuzzing = false;
        m3CanBuzz = false; // pas encore !

        // Mettre a jour scores en cache
        m3Finalists.forEach(id => {
            if (m3CurrentScores[id] === undefined) m3CurrentScores[id] = 0;
        });

        // Mettre à jour le titre de l'écran (au cas où on vient de M2)
        const sc = document.querySelector('#screen-m3-duel');
        if (sc) {
            const lbl = sc.querySelector('.manche-label');
            const ttl = sc.querySelector('.manche-title');
            if (lbl) lbl.textContent = 'Manche 3 · Duel';
            if (ttl) ttl.textContent = 'Face-à-face';
        }
        // [HACK] Restaurer grid 2 colonnes (M2 a forcé 3 colonnes)
        els.m3DuelPlayers.style.gridTemplateColumns = '1fr 1fr';
        els.m3BuzzBtn.dataset.mode = 'm3';
        // [PARALLELE] Re-afficher le buzzer (M2 le cachait)
        const buzzZone = document.querySelector('#screen-m3-duel .m3-buzz-zone');
        if (buzzZone) buzzZone.style.display = '';

        showScreen('m3duel');
        els.m3QCounter.textContent = `Q${data.index} / ${data.total}`;
        els.m3Category.textContent = data.category.replace(/_/g, ' ');
        els.m3QText.textContent = data.question;
        renderM3DuelPlayers();
        // Options cachees
        els.m3Options.innerHTML = '';
        els.m3Options.classList.remove('revealed');
        els.m3Status.textContent = '';

        // Timer : on n'affiche pas encore le decompte du buzz
        els.m3TimerFill.style.width = '100%';
        els.m3TimerFill.classList.remove('warning', 'danger', 'paused');
        els.m3TimerText.textContent = '—';

        // Reveler les paris s'il y en a sur cette question
        const bets = data.bets || {};
        const betEntries = Object.entries(bets);
        if (betEntries.length > 0) {
            const lines = betEntries.map(([id, amt]) => {
                const p = roomPlayers.find(pl => pl.id === id);
                return `${p ? p.name : '?'} a misé <strong>${amt} pt${amt > 1 ? 's' : ''}</strong> sur cette question`;
            });
            els.m3BetBannerText.innerHTML = lines.join(' · ');
            els.m3BetBanner.style.display = 'flex';
        } else {
            els.m3BetBanner.style.display = 'none';
        }

        // Buzzer grise + bordure doree pulsante sur la zone question
        els.m3BuzzBtn.disabled = true;
        els.m3BuzzSub.textContent = 'Lis la question…';
        document.querySelector('#screen-m3-duel .m1-question-area')?.classList.add('reading-mode');
    });

    // [D.1] PHASE 2 : options apparaissent, buzzer toujours gris
    socket.on('m3_options_revealed', (data) => {
        renderM3Options(data.options, 'duel');
        els.m3Options.classList.add('revealed');
        els.m3BuzzSub.textContent = 'Prépare-toi…';
    });

    // [D.1] PHASE 3 : buzz ouvert
    socket.on('m3_buzz_open', (data) => {
        document.querySelector('#screen-m3-duel .m1-question-area')?.classList.remove('reading-mode');
        m3CanBuzz = m3IsFinalist;
        els.m3BuzzBtn.disabled = !m3IsFinalist;
        els.m3BuzzSub.textContent = m3IsFinalist ? 'Appuie pour répondre' : 'Spectateur';
        genericTimer(data.deadline, data.time, els.m3TimerFill, els.m3TimerText);
    });

    socket.on('m3_buzz', ({ playerId, buzzResponseTime, buzzDeadline }) => {
        clearTimer();
        // [D.1] Detecter dans quel ecran on est
        const isSD = screens.m3sudden.classList.contains('active');
        const fillEl = isSD ? els.m3SDTimerFill : els.m3TimerFill;
        const textEl = isSD ? els.m3SDTimerText : els.m3TimerText;
        const optsEl = isSD ? els.m3SDOptions : els.m3Options;
        const buzzBtn = isSD ? els.m3SDBuzzBtn : els.m3BuzzBtn;
        const statusEl = isSD ? els.m3SDStatus : els.m3Status;

        fillEl.classList.add('paused');
        renderM3DuelPlayers(playerId);

        if (playerId === PLAYER_ID) {
            m3IsBuzzing = true;
            optsEl.classList.add('revealed');
            optsEl.querySelectorAll('.m1-option').forEach(b => b.disabled = false);
            buzzBtn.disabled = true;
            statusEl.textContent = `Tu as buzzé ! 5s pour répondre.`;
        } else {
            buzzBtn.disabled = true;
            statusEl.textContent = `${roomPlayers.find(p => p.id === playerId)?.name || '?'} a buzzé !`;
        }
        simpleCountdownTimer(buzzDeadline, buzzResponseTime, textEl);
    });

    socket.on('m3_buzz_result', ({ playerId, answer, correct, delta, betAmount, newScore, reason }) => {
        m3CurrentScores[playerId] = newScore;
        renderM3DuelPlayers();
        const player = roomPlayers.find(p => p.id === playerId);
        const pname = player ? player.name : '?';
        const isSD = screens.m3sudden.classList.contains('active');
        const statusEl = isSD ? els.m3SDStatus : els.m3Status;
        const optsEl = isSD ? els.m3SDOptions : els.m3Options;

        const isMe = playerId === PLAYER_ID;
        let msg = '';
        if (correct) {
            msg = isMe ? `✓ Bonne réponse ! ${delta >= 0 ? '+' : ''}${delta} pts` : `${pname} a trouvé ! ${delta >= 0 ? '+' : ''}${delta} pts`;
        } else if (reason === 'buzz_timeout') {
            msg = isMe ? `Tu n'as pas répondu… ${delta} pts` : `${pname} n'a pas répondu… ${delta} pts`;
        } else {
            msg = isMe ? `✗ Mauvaise réponse. ${delta} pts` : `${pname} a raté. ${delta} pts`;
        }
        statusEl.textContent = msg;
        if (betAmount > 0) statusEl.textContent += ` (pari de ${betAmount})`;

        // [NEW] Si le buzzer a raté, on marque sa mauvaise réponse en rouge
        // pour que l'adversaire (qui pourra peut-être buzzer) la voie aussi
        if (!correct && answer !== null && answer !== undefined) {
            const wrongBtn = optsEl.querySelector(`.m1-option[data-idx="${answer}"]`);
            if (wrongBtn) {
                wrongBtn.classList.add('wrong-by-other');
            }
        }

        m3IsBuzzing = false;
    });

    socket.on('m3_buzz_released', ({ nextBuzzerCandidate, remainingTime, deadline }) => {
        const isSD = screens.m3sudden.classList.contains('active');
        const fillEl = isSD ? els.m3SDTimerFill : els.m3TimerFill;
        const textEl = isSD ? els.m3SDTimerText : els.m3TimerText;
        const buzzBtn = isSD ? els.m3SDBuzzBtn : els.m3BuzzBtn;
        const statusEl = isSD ? els.m3SDStatus : els.m3Status;
        fillEl.classList.remove('paused');
        if (nextBuzzerCandidate === PLAYER_ID) {
            buzzBtn.disabled = false;
            statusEl.textContent += ' — À toi de buzzer !';
            m3CanBuzz = true;
        } else {
            statusEl.textContent += ' — Attente du second buzz…';
        }
        genericTimer(deadline, remainingTime, fillEl, textEl);
    });

    socket.on('m3_reveal', ({ correctAnswer, scores }) => {
        clearTimer();
        const isSD = screens.m3sudden.classList.contains('active');
        const fillEl = isSD ? els.m3SDTimerFill : els.m3TimerFill;
        const optsEl = isSD ? els.m3SDOptions : els.m3Options;
        const buzzBtn = isSD ? els.m3SDBuzzBtn : els.m3BuzzBtn;
        fillEl.classList.remove('paused');
        optsEl.classList.add('revealed');
        optsEl.querySelectorAll('.m1-option').forEach((btn, i) => {
            btn.disabled = true;
            if (i === correctAnswer) btn.classList.add('correct');
        });
        scores.forEach(s => { m3CurrentScores[s.id] = s.score; });
        renderM3DuelPlayers();
        buzzBtn.disabled = true;
    });

    socket.on('m3_sudden_death_start', () => {
        showScreen('m3sudden');
        els.m3SDStatus.textContent = 'Égalité. Le premier à marquer remporte le titre.';
        renderM3DuelPlayers();
    });

    // [D.1] Mort subite : 3 phases comme M3 normal
    socket.on('m3_sudden_death_question_reading', (data) => {
        m3HasBuzzedThisQ = false;
        m3IsBuzzing = false;
        m3CanBuzz = false;
        els.m3SDRound.textContent = `Question ${data.round}`;
        els.m3SDCategory.textContent = data.category.replace(/_/g, ' ');
        els.m3SDQText.textContent = data.question;
        renderM3DuelPlayers();
        els.m3SDOptions.innerHTML = '';
        els.m3SDOptions.classList.remove('revealed');
        els.m3SDStatus.textContent = '';
        els.m3SDTimerFill.style.width = '100%';
        els.m3SDTimerFill.classList.remove('warning', 'danger', 'paused');
        els.m3SDTimerText.textContent = '—';
        els.m3SDBuzzBtn.disabled = true;
        document.querySelector('#screen-m3-sudden .m1-question-area')?.classList.add('reading-mode');
    });

    socket.on('m3_sudden_death_options_revealed', (data) => {
        renderM3Options(data.options, 'sudden');
        els.m3SDOptions.classList.add('revealed');
    });

    socket.on('m3_sudden_death_buzz_open', (data) => {
        document.querySelector('#screen-m3-sudden .m1-question-area')?.classList.remove('reading-mode');
        m3CanBuzz = m3IsFinalist;
        els.m3SDBuzzBtn.disabled = !m3IsFinalist;
        genericTimer(data.deadline, data.time, els.m3SDTimerFill, els.m3SDTimerText);
    });

    socket.on('m3_finished', ({ winnerId, winnerName, scores, message, ranking, duration, totalQuestions, suddenDeath }) => {
        clearTimer();
        setSpectator(false);

        // ── Identité du gagnant ─────────────────────────
        const finalWinnerEl = document.getElementById('final-winner-name');
        const finalMsgEl = document.getElementById('final-message');
        if (finalWinnerEl) finalWinnerEl.textContent = winnerName || '?';
        if (finalMsgEl) finalMsgEl.textContent = message || '';

        // ── Podium 1-4 avec ELO ───────────────────────────
        const podium = document.getElementById('final-podium');
        const medals = { 1: '🥇', 2: '🥈', 3: '🥉', 4: '4️⃣' };
        if (podium && Array.isArray(ranking) && ranking.length > 0) {
            let html = '';
            ranking.forEach(p => {
                const isMe = (p.id === PLAYER_ID);
                const eloClass = p.elo_delta > 0 ? 'positive' : (p.elo_delta < 0 ? 'negative' : 'neutral');
                const eloSign  = p.elo_delta > 0 ? '+' : '';
                const eloArrow = p.elo_delta > 0 ? ' ↑' : (p.elo_delta < 0 ? ' ↓' : ' →');
                html += `
                    <div class="podium-row rank-${p.rank} ${isMe ? 'is-me' : ''}">
                        <span class="podium-medal">${medals[p.rank] || p.rank}</span>
                        <span class="podium-name">
                            ${escapeHtml(p.name)}
                            ${isMe ? '<span class="me-tag">(toi)</span>' : ''}
                        </span>
                        <span class="podium-elo ${eloClass}">${eloSign}${p.elo_delta} ELO${eloArrow}</span>
                    </div>`;
            });
            podium.innerHTML = html;
        }

        // ── Stats partie ─────────────────────────────────
        const durEl = document.getElementById('final-duration');
        const totQEl = document.getElementById('final-total-q');
        if (durEl) durEl.textContent = duration || '—';
        if (totQEl) totQEl.textContent = totalQuestions || '—';

        showScreen('final');

        // ── Confetti dorés pour le gagnant ───────────────
        const iAmWinner = (winnerId === PLAYER_ID);
        if (iAmWinner) {
            launchConfetti();
        }
    });

    // ─── Confetti (vanilla, pas de lib externe) ─────────────────
    function launchConfetti() {
        const canvas = document.getElementById('confetti-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        canvas.width = window.innerWidth * dpr;
        canvas.height = window.innerHeight * dpr;
        canvas.style.width = window.innerWidth + 'px';
        canvas.style.height = window.innerHeight + 'px';
        ctx.scale(dpr, dpr);

        const colors = ['#d4af37', '#fcf6ba', '#f0c850', '#8a7124', '#ffffff'];
        const W = window.innerWidth;
        const H = window.innerHeight;
        const particles = [];
        const COUNT = 120;

        for (let i = 0; i < COUNT; i++) {
            particles.push({
                x: W / 2 + (Math.random() - 0.5) * 60,
                y: H / 3,
                vx: (Math.random() - 0.5) * 12,
                vy: -Math.random() * 12 - 4,
                gravity: 0.25 + Math.random() * 0.15,
                size: 5 + Math.random() * 5,
                color: colors[Math.floor(Math.random() * colors.length)],
                rotation: Math.random() * Math.PI * 2,
                vRotation: (Math.random() - 0.5) * 0.3,
                shape: Math.random() > 0.5 ? 'rect' : 'circle',
                opacity: 1
            });
        }

        let frames = 0;
        const MAX_FRAMES = 240; // ~4 secondes à 60fps

        function tick() {
            ctx.clearRect(0, 0, W, H);
            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += p.gravity;
                p.vx *= 0.99;
                p.rotation += p.vRotation;
                if (frames > 150) p.opacity = Math.max(0, p.opacity - 0.02);

                ctx.save();
                ctx.globalAlpha = p.opacity;
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rotation);
                ctx.fillStyle = p.color;
                if (p.shape === 'rect') {
                    ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
                } else {
                    ctx.beginPath();
                    ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.restore();
            });
            frames++;
            if (frames < MAX_FRAMES) {
                requestAnimationFrame(tick);
            } else {
                ctx.clearRect(0, 0, W, H);
            }
        }
        tick();
    }

    // -------------------------------------------------------------------
    //  Actions UI (en game.php : pas de lobby, juste les actions in-game)
    // -------------------------------------------------------------------

    // M3/M2 categories (le même bouton sert pour les 2 modes via dataset.mode)
    els.m3CatValidate.addEventListener('click', () => {
        const mode = els.m3CatValidate.dataset.mode || 'm3';
        if (mode === 'm2') {
            if (m2MyCategories.size !== 4) return;
            socket.emit('m2_select_categories', { categories: Array.from(m2MyCategories) });
        } else {
            if (m3MyCategories.size !== 4) return;
            socket.emit('m3_select_categories', { categories: Array.from(m3MyCategories) });
        }
        els.m3CatValidate.disabled = true;
        els.m3CatGrid.querySelectorAll('.m3-cat-chip').forEach(b => b.disabled = true);
    });

    // M3 bet amounts (les Qs sont reconstruites dynamiquement pour M2)
    els.m3BetAmts.querySelectorAll('.m3-bet-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = els.m3BetValidate.dataset.mode || 'm3';
            if (mode === 'm2' && !m2IsPlayer) return;
            if (mode === 'm3' && !m3IsFinalist) return;
            els.m3BetAmts.querySelectorAll('.m3-bet-chip').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            if (mode === 'm2') {
                m2MyBetAmt = parseInt(btn.dataset.amt, 10);
                els.m3BetValidate.disabled = !(m2MyBetQ && m2MyBetAmt);
            } else {
                m3MyBetAmt = parseInt(btn.dataset.amt, 10);
                els.m3BetValidate.disabled = !(m3MyBetQ && m3MyBetAmt);
            }
        });
    });
    // M3 bet Qs (initial — sera reconstruit pour M2 avec 8 boutons)
    els.m3BetQs.querySelectorAll('.m3-bet-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!m3IsFinalist) return;
            els.m3BetQs.querySelectorAll('.m3-bet-chip').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            m3MyBetQ = parseInt(btn.dataset.q, 10);
            els.m3BetValidate.disabled = !(m3MyBetQ && m3MyBetAmt);
        });
    });
    els.m3BetValidate.addEventListener('click', () => {
        const mode = els.m3BetValidate.dataset.mode || 'm3';
        if (mode === 'm2') {
            if (!m2MyBetQ || !m2MyBetAmt) return;
            socket.emit('m2_place_bet', { questionIndex: m2MyBetQ, amount: m2MyBetAmt });
        } else {
            if (!m3MyBetQ || !m3MyBetAmt) return;
            socket.emit('m3_place_bet', { questionIndex: m3MyBetQ, amount: m3MyBetAmt });
        }
        els.m3BetValidate.disabled = true;
        els.m3BetQs.querySelectorAll('.m3-bet-chip').forEach(b => b.disabled = true);
        els.m3BetAmts.querySelectorAll('.m3-bet-chip').forEach(b => b.disabled = true);
    });

    // M3 buzz (en M2 le bouton est caché, plus de buzz)
    els.m3BuzzBtn.addEventListener('click', () => {
        // [PARALLELE] En mode M2 le bouton est caché donc ce handler ne s'applique qu'à M3
        if (!m3CanBuzz || m3HasBuzzedThisQ) return;
        m3HasBuzzedThisQ = true;
        socket.emit('m3_buzz');
    });
    els.m3SDBuzzBtn.addEventListener('click', () => {
        if (!m3IsFinalist) return;
        socket.emit('m3_buzz');
    });

    // Final
    els.btnBackLobby.addEventListener('click', () => {
        const friendlyParam = window.QPC_FRIENDLY ? '?friendly=1' : '';
        window.location.href = 'lobby.php' + friendlyParam;
    });
    els.btnBackDashboard.addEventListener('click', () => {
        window.location.href = returnUrl();
    });

    console.log('[CHAMP-GAME] initialized, playerId =', PLAYER_ID, 'room =', currentCode);

    // ============================================================
    //  AUTO-JOIN : on rejoint la room dont le code est dans l'URL
    //  (la lobby.php nous a redirigés ici avec ?code=XXX)
    //  On utilise champ_rejoin pour que le serveur sache qu'on est
    //  une reconnexion depuis le lobby (pas une nouvelle inscription).
    // ============================================================
    const tryAutoJoin = () => {
        if (!socket.connected) {
            setTimeout(tryAutoJoin, 100);
            return;
        }
        if (!currentCode) {
            els.loadingMsg.innerHTML = '⚠️ Aucun code de room. <a href="lobby.php" style="color:#d4af37">Retour au lobby</a>';
            return;
        }
        console.log('[CHAMP-GAME] rejoint la room', currentCode);
        socket.emit('champ_rejoin', {
            code:     currentCode,
            playerId: PLAYER_ID
        });
    };
    tryAutoJoin();
})();
