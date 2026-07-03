/* ══════════════════════════════════════════════════
   QPC — Lobby 1v1 (logique côté client)
   ★ FIX BUG 1 :
   La redirection vers game-1v1.php se fait DÈS la réception de
   `start_countdown` (avant que le serveur ne lance la moindre
   question). Le serveur attend ensuite que les 2 joueurs aient
   rejoint la page de jeu (via rejoin_room) avant de lancer le
   countdown 3-2-1, donc plus aucun event n'est perdu.
══════════════════════════════════════════════════ */

'use strict';

// ────────────────────────────────────────────
// CONFIG
// ────────────────────────────────────────────
const SERVER_URL = (window.QPC_CONFIG && window.QPC_CONFIG.SERVER_URL) || 'http://localhost:3000';
const MY_ELO     = parseInt(localStorage.getItem('qpc_elo'))  || 1200;
const MY_NAME    = localStorage.getItem('qpc_name') || 'Joueur';

// PlayerId persistant (= identité stable côté serveur)
let PLAYER_ID = localStorage.getItem('qpc_player_id');
if (!PLAYER_ID) {
    PLAYER_ID = crypto.randomUUID();
    localStorage.setItem('qpc_player_id', PLAYER_ID);
}

// ────────────────────────────────────────────
// STATE
// ────────────────────────────────────────────
let socket    = null;
let roomCode  = null;
let isHost    = false;
let redirecting = false;   // évite les double-redirections

// ────────────────────────────────────────────
// HELPERS
// ────────────────────────────────────────────
function getDivisionLabel(elo) {
    if (elo < 1200) return { label:'Filet',      cls:'div-filet' };
    if (elo < 1500) return { label:'Division 3', cls:'div-3'     };
    if (elo < 1800) return { label:'Division 2', cls:'div-2'     };
    if (elo < 2000) return { label:'Division 1', cls:'div-1'     };
    return              { label:'Élite 👑',    cls:'div-elite' };
}

function $(id) { return document.getElementById(id); }

// Échappe le HTML pour éviter toute injection via un pseudo joueur
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}

// ────────────────────────────────────────────
// INIT
// ────────────────────────────────────────────
function init() {
    // Pré-remplissage des champs avec les valeurs sauvegardées
    $('create-name').value = MY_NAME;
    $('join-name').value   = MY_NAME;

    // Affichage ELO uniquement si le bloc existe (caché en mode invité)
    const myEloEl = $('my-elo');
    if (myEloEl) myEloEl.textContent = MY_ELO;

    const divEl = $('my-division');
    if (divEl) {
        const div = getDivisionLabel(MY_ELO);
        divEl.textContent = div.label;
        divEl.className   = 'elo-division ' + div.cls;
    }

    // Bouton listeners
    $('btn-create').addEventListener('click', createRoom);
    $('btn-join').addEventListener('click',   joinRoom);
    $('start-btn').addEventListener('click',  startGame);
    $('leave-btn').addEventListener('click',  leaveRoom);
    $('copy-hint').addEventListener('click',  copyCode);

    showConnecting();
    socket = io(SERVER_URL, { transports: ['websocket'] });

    socket.on('connect',       () => showMain());
    socket.on('connect_error', () => showError('Impossible de rejoindre le serveur. Vérifiez que node server.js tourne.'));

    socket.on('room_created', ({ code, players }) => {
        roomCode = code; isHost = true;
        showLobby(code, players, true);
    });

    socket.on('room_joined', ({ code, players }) => {
        roomCode = code; isHost = false;
        showLobby(code, players, false);
    });

    socket.on('player_joined', ({ players }) => {
        updateLobbyPlayers(players);
        if (players.length === 2) {
            $('lobby-status').innerHTML = 'Les deux joueurs sont <span>prêts !</span>';
            if (isHost) {
                const btn = $('start-btn');
                btn.style.display = '';
                btn.disabled = false;
            }
        }
    });

    socket.on('player_left', ({ playerName }) => {
        $('lobby-status').innerHTML = `<span>${playerName}</span> a quitté la room.`;
        $('start-btn').disabled = true;
        resetSlot2();
    });

    socket.on('join_error', ({ message }) => showInputError(message));
    socket.on('error',      ({ message }) => showInputError(message));

    // ★ FIX BUG 1 : on redirige DÈS qu'on reçoit start_countdown.
    // Le serveur ne lance la 1ère question qu'une fois que les 2
    // sont reconnectés sur game-1v1.php → plus de question perdue.
    socket.on('start_countdown', () => {
        if (redirecting) return;
        redirecting = true;

        // On stocke les infos utiles pour la page de jeu
        localStorage.setItem('qpc_room', roomCode);
        localStorage.setItem('qpc_host', isHost ? '1' : '0');

        $('lobby-status').innerHTML = 'La partie démarre…';

        // Petit délai pour laisser l'utilisateur lire le message
        setTimeout(() => {
            // Propage le flag amical dans l'URL pour que game-1v1.php skip aussi l'auth
            const friendlyParam = window.QPC_FRIENDLY ? '&friendly=1' : '';
            window.location.href = `game-1v1.php?room=${roomCode}${friendlyParam}`;
        }, 400);
    });
}

// ────────────────────────────────────────────
// ACTIONS
// ────────────────────────────────────────────
function createRoom() {
    // ── Auto-naming en mode invité : host = "Joueur 1"
    const name = window.QPC_USER
        ? ($('create-name').value.trim() || MY_NAME)
        : 'Joueur 1';
    hideInputError();
    localStorage.setItem('qpc_name', name);
    // ── Flag classé/amical injecté par lobby-1v1.php depuis $_GET['friendly']
    const isRanked = !window.QPC_FRIENDLY;
    socket.emit('create_room', { playerId: PLAYER_ID, name, elo: MY_ELO, isRanked });
}

function joinRoom() {
    // ── Auto-naming en mode invité : joiner = "Joueur 2"
    const name = window.QPC_USER
        ? ($('join-name').value.trim() || MY_NAME)
        : 'Joueur 2';
    const code = $('join-code').value.trim().toUpperCase();
    if (!code || code.length < 4) { showInputError('Entre un code à 4 caractères.'); return; }
    localStorage.setItem('qpc_name', name);
    hideInputError();
    socket.emit('join_room', { playerId: PLAYER_ID, code, name, elo: MY_ELO });
}

function startGame() {
    socket.emit('player_ready', { code: roomCode, playerId: PLAYER_ID });
}

function leaveRoom() {
    socket.disconnect();
    showMain();
    roomCode = null;
    isHost   = false;
}

function copyCode() {
    if (!roomCode) return;
    navigator.clipboard.writeText(roomCode).then(() => {
        const el = $('copy-hint');
        el.textContent = '✅ Copié !';
        setTimeout(() => el.textContent = '📋 Copier le code', 2000);
    });
}

// ────────────────────────────────────────────
// UI — Affichage des écrans
// ────────────────────────────────────────────
function showConnecting() {
    $('connecting').classList.add('active');
    $('main-screen').style.display = 'none';
    $('lobby-screen').classList.remove('active');
}

function showMain() {
    $('connecting').classList.remove('active');
    $('main-screen').style.display = 'flex';
    $('lobby-screen').classList.remove('active');
}

function showLobby(code, players, host) {
    $('main-screen').style.display = 'none';
    $('lobby-screen').classList.add('active');
    $('lobby-code-display').textContent = code;
    $('start-btn').disabled = true;
    if (!host) $('start-btn').style.display = 'none';
    updateLobbyPlayers(players);
}

function updateLobbyPlayers(players) {
    if (players[0]) {
        const p = players[0];
        $('avatar-1').textContent = p.name.charAt(0).toUpperCase();
        $('name-1').textContent   = p.name;
        $('elo-1').textContent    = `${p.elo} ELO`;
        $('slot-1').classList.add('filled');
    }
    if (players[1]) {
        const p = players[1];
        const slot2 = $('slot-2');
        slot2.classList.add('filled');
        slot2.innerHTML = `
            <div class="player-avatar" style="background:linear-gradient(135deg,#2255aa,#5599dd);border-color:#5599dd;color:#fff;">
                ${p.name.charAt(0).toUpperCase()}
            </div>
            <div class="player-name" style="color:var(--text1);">${escapeHtml(p.name)}</div>
            <div class="player-elo" style="color:#5599dd;">${p.elo} ELO</div>
        `;
    }
}

function resetSlot2() {
    const slot = $('slot-2');
    slot.classList.remove('filled');
    slot.innerHTML = `
        <div class="player-avatar" id="avatar-2">?</div>
        <div class="player-name" id="name-2">En attente</div>
        <div class="waiting-dots">
            <div class="waiting-dot"></div>
            <div class="waiting-dot"></div>
            <div class="waiting-dot"></div>
        </div>
    `;
}

// ────────────────────────────────────────────
// UI — Erreurs
// ────────────────────────────────────────────
function showInputError(msg) {
    const el = $('error-msg');
    el.textContent = msg;
    el.classList.add('visible');
}
function hideInputError() { $('error-msg').classList.remove('visible'); }
function showError(msg) {
    $('connecting').classList.remove('active');
    $('main-screen').style.display = 'flex';
    showInputError(msg);
}

// ────────────────────────────────────────────
// Raccourcis clavier
// ────────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    const lobby = $('lobby-screen');
    if (lobby.classList.contains('active')) return;
    const active = document.activeElement.id;
    if (active === 'join-code' || active === 'join-name') joinRoom();
    else createRoom();
});

// ────────────────────────────────────────────
// GO
// ────────────────────────────────────────────
init();
