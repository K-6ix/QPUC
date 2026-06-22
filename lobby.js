// ============================================================================
//  QPC - Championship Lobby JS
//  Connecte au serveur Node (port 3000, déjà actif pour le 1v1)
//  et gère le lobby 4 joueurs avant la partie.
// ============================================================================

(() => {
    'use strict';

    // ─── Identité (depuis window.QPC_USER injecté par le PHP, ou guest) ──
    let USER_ID, PLAYER_ID, USERNAME, MY_ELO;
    if (window.QPC_USER && window.QPC_USER.id) {
        // Utilisateur connecté
        USER_ID   = window.QPC_USER.id;
        PLAYER_ID = 'u' + USER_ID;                // stable, basé sur user_id BDD
        USERNAME  = window.QPC_USER.username;
        MY_ELO    = window.QPC_USER.elo;
    } else {
        // Mode invité : PLAYER_ID = UUID aléatoire, le serveur auto-nommera "Joueur N"
        USER_ID = null;

        // Le guest DOIT garder le même ID pendant tout le championnat.
        let pid = localStorage.getItem('qpc_player_id');

        if (!pid || pid.startsWith('u')) {
            pid = 'guest_' + crypto.randomUUID();
            try {
                localStorage.setItem('qpc_player_id', pid);
            } catch (e) {}
        }

        PLAYER_ID = pid;

        // ── On laisse USERNAME vide pour que le serveur auto-nomme "Joueur N"
        //    (N = ordre d'arrivée dans la room). Le nom assigné nous reviendra
        //    via l'event champ_room_joined, qu'on stocke alors dans localStorage
        //    pour que game.js l'utilise comme currentName.
        USERNAME  = '';

        MY_ELO = 1200;
    }

    let currentCode = null;
    let roomPlayers = [];

    // ─── DOM refs ───────────────────────────────────────────────────
    const elConnecting   = document.getElementById('connecting');
    const elMain         = document.getElementById('main-screen');
    const elLobby        = document.getElementById('lobby-screen');
    const elCountdown    = document.getElementById('countdown-overlay');
    const elCountdownNum = document.getElementById('countdown-number');

    const btnCreate   = document.getElementById('btn-create');
    const btnJoin     = document.getElementById('btn-join');
    const btnReady    = document.getElementById('ready-btn');
    const btnLeave    = document.getElementById('leave-btn');
    const inputCode   = document.getElementById('join-code');
    const errMsg      = document.getElementById('error-msg');

    const lobbyCode   = document.getElementById('lobby-code-display');
    const playersGrid = document.getElementById('players-grid');
    const lobbyStatus = document.getElementById('lobby-status');
    const copyHint    = document.getElementById('copy-hint');

    // ─── Helpers ────────────────────────────────────────────────────
    function showError(msg) {
        errMsg.textContent = msg;
        setTimeout(() => { errMsg.textContent = ''; }, 4000);
    }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    function showScreen(name) {
        elConnecting.style.display = (name === 'connecting') ? 'flex' : 'none';
        elMain.style.display       = (name === 'main')       ? 'flex' : 'none';
        elLobby.style.display      = (name === 'lobby')      ? 'flex' : 'none';
    }

    // ─── Rendering du lobby ─────────────────────────────────────────
    function renderLobby(room) {
        currentCode = room.code;
        lobbyCode.textContent = room.code;
        roomPlayers = room.players;

        let html = '';
        for (let i = 0; i < 4; i++) {
            const p = room.players[i];
            if (p) {
                const initial = p.name.charAt(0).toUpperCase();
                const isMe = (p.id === PLAYER_ID);
                html += `
                    <div class="player-slot filled ${p.ready ? 'ready' : ''}">
                        ${p.isHost ? '<span class="host-badge">HOST</span>' : ''}
                        <div class="player-avatar">${initial}</div>
                        <div class="player-name">${escapeHtml(p.name)}${isMe ? ' (toi)' : ''}</div>
                        <div class="player-status">${p.ready ? '✓ Prêt' : 'En attente'}</div>
                    </div>`;
            } else {
                html += `
                    <div class="player-slot empty">
                        <div class="player-avatar">?</div>
                        <div class="player-name">Libre</div>
                        <div class="player-status">—</div>
                    </div>`;
            }
        }
        playersGrid.innerHTML = html;

        const total = room.players.length;
        const ready = room.players.filter(p => p.ready).length;
        lobbyStatus.textContent = (total < 4)
            ? `En attente de joueurs (${total}/4)…`
            : `Joueurs prêts : ${ready}/4`;

        const me = room.players.find(p => p.id === PLAYER_ID);
        if (me) {
            if (me.ready) {
                btnReady.textContent = '✓ Annuler';
                btnReady.classList.add('ready-state');
            } else {
                btnReady.textContent = 'JE SUIS PRÊT';
                btnReady.classList.remove('ready-state');
            }
        }
    }

    // ─── Socket ─────────────────────────────────────────────────────
    // Le serveur Node tourne sur le port 3000 (cohérent avec le 1v1)
    const SERVER_URL = 'http://localhost:3000';
    const socket = io(SERVER_URL, { transports: ['websocket', 'polling'] });

    socket.on('connect', () => {
        console.log('[CHAMP] connecté au serveur', socket.id);
        showScreen('main');
    });

    socket.on('connect_error', (err) => {
        console.error('[CHAMP] erreur de connexion :', err.message);
        showError('Impossible de se connecter au serveur de jeu.');
    });

    socket.on('disconnect', () => {
        console.log('[CHAMP] déconnecté');
    });

    // ─── Events championship ────────────────────────────────────────
    socket.on('champ_error', ({ message }) => {
        showError(message);
        btnCreate.disabled = false;
        btnJoin.disabled = false;
    });

    socket.on('champ_room_joined', ({ code, name }) => {
        currentCode = code;
        // ── Le serveur peut avoir auto-assigné "Joueur N" pour les guests :
        //    on stocke le nom retourné pour que game.js l'utilise comme currentName.
        if (name) {
            try { localStorage.setItem('qpc_name', name); } catch (e) {}
        }
        showScreen('lobby');
    });

    socket.on('room_state', (room) => {
        if (room.status === 'lobby') renderLobby(room);
    });

    socket.on('champ_match_starting', ({ in: countStart }) => {
        elCountdownNum.textContent = countStart;
        elCountdown.classList.add('active');
        try { localStorage.setItem('qpc_champ_room', currentCode); } catch (e) {}
    });

    socket.on('champ_countdown', ({ count }) => {
        elCountdownNum.textContent = count;
    });

    // Le serveur émet ce signal quand le countdown est fini → on bascule vers game.php
    // (Le serveur attend ensuite notre 'champ_rejoin' depuis game.php avant de lancer M1)
    socket.on('champ_redirect_to_game', () => {
        try { localStorage.setItem('qpc_champ_room', currentCode); } catch (e) {}
        const friendlyParam = window.QPC_FRIENDLY ? '&friendly=1' : '';
        window.location.href = `game.php?code=${encodeURIComponent(currentCode)}${friendlyParam}`;
    });

    // Fallback : si jamais m1_started arrive avant qu'on ait redirigé
    socket.on('m1_started', () => {
        if (window.location.pathname.includes('lobby.php')) {
            const friendlyParam = window.QPC_FRIENDLY ? '&friendly=1' : '';
            window.location.href = `game.php?code=${encodeURIComponent(currentCode)}${friendlyParam}`;
        }
    });

    // ─── UI events ──────────────────────────────────────────────────
    btnCreate.addEventListener('click', () => {
        btnCreate.disabled = true;
        // ── Flag classé/amical depuis $_GET['friendly'] (injecté par lobby.php)
        const isRanked = !window.QPC_FRIENDLY;
        socket.emit('champ_create_room', {
            playerId: PLAYER_ID,
            name:     USERNAME,      // vide pour guest → serveur auto-nomme "Joueur 1"
            elo:      MY_ELO,
            isRanked
        });
    });

    btnJoin.addEventListener('click', () => {
        const code = inputCode.value.trim().toUpperCase();
        if (!code) { showError('Entre un code de partie.'); return; }
        btnJoin.disabled = true;
        socket.emit('champ_join_room', {
            playerId: PLAYER_ID,
            name:     USERNAME,      // vide pour guest → serveur auto-nomme "Joueur N"
            elo:      MY_ELO,
            code:     code
        });
    });

    btnReady.addEventListener('click', () => {
        socket.emit('champ_toggle_ready');
    });

    btnLeave.addEventListener('click', () => {
        if (confirm('Quitter la partie ?')) {
            socket.emit('champ_leave_room');
            currentCode = null;
            showScreen('main');
            btnCreate.disabled = false;
            btnJoin.disabled = false;
        }
    });

    copyHint.addEventListener('click', () => {
        if (!currentCode) return;
        navigator.clipboard.writeText(currentCode).then(() => {
            copyHint.textContent = '✓ Copié !';
            setTimeout(() => { copyHint.textContent = '📋 Copier le code'; }, 1500);
        });
    });

    inputCode.addEventListener('input', (e) => {
        e.target.value = e.target.value.toUpperCase();
    });
    inputCode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') btnJoin.click();
    });

    // ─── Init ───────────────────────────────────────────────────────
    showScreen('connecting');
    console.log('[CHAMP] lobby initialized, USER_ID =', USER_ID, 'PLAYER_ID =', PLAYER_ID);
})();
