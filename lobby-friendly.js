// ═══════════════════════════════════════════════════════════
// lobby-friendly.js — Lobby amical unifié (duel + championnat)
// ═══════════════════════════════════════════════════════════
// Consomme window.QPC_MODE ('duel'|'tournoi'), QPC_USER, QPC_CODE_LEN.
// Route les événements Socket vers create_room/join_room (duel)
// ou champ_create_room/champ_join_room (tournoi). Server.js
// inchangé — on utilise les mêmes handlers qu'avant.
// ═══════════════════════════════════════════════════════════

const $  = id => document.getElementById(id);
const MODE     = window.QPC_MODE     || 'duel';       // 'duel'|'tournoi'
const IS_TOURNOI = MODE === 'tournoi';
const CODE_LEN = window.QPC_CODE_LEN || (IS_TOURNOI ? 5 : 4);
const MAX_PLAYERS = IS_TOURNOI ? 4 : 2;

const SERVER_URL = window.QPC_CONFIG?.SERVER_URL || 'http://localhost:3000';

// PlayerId : stable si user connecté (u<id>), sinon UUID persisté guest
function ensurePlayerId() {
    let pid = localStorage.getItem('qpc_player_id');
    if (!pid) {
        pid = 'g' + Math.random().toString(36).slice(2, 10);
        localStorage.setItem('qpc_player_id', pid);
    }
    return pid;
}
const PLAYER_ID = ensurePlayerId();
const MY_ELO    = parseInt(localStorage.getItem('qpc_elo') || '1200', 10);
let   MY_NAME   = window.QPC_USER?.username || localStorage.getItem('qpc_name') || '';

// État
let socket      = null;
let roomCode    = null;
let isHost      = false;
let redirecting = false;
let inQueue     = false;    // true = en file quick-match

// ═══════════════════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    initSocket();
    initSlots();
    initUI();
    loadRecents();
});

function initSocket() {
    socket = io(SERVER_URL, { transports: ['websocket'] });

    socket.on('connect_error', () => {
        showErr('Impossible de rejoindre le serveur. Vérifie que node server.js tourne.');
    });

    // ── ÉVÉNEMENTS DUEL 1v1 ────────────────────────────────
    socket.on('room_created', ({ code, players }) => {
        roomCode = code; isHost = true;
        openLobby(code, players, true);
    });
    socket.on('room_joined', ({ code, players }) => {
        roomCode = code; isHost = false;
        openLobby(code, players, false);
    });
    socket.on('player_joined', ({ players }) => {
        updatePlayers(players);
        if (!IS_TOURNOI && players.length === 2) {
            $('lobby-status').innerHTML = 'Les deux joueurs sont <span>prêts</span>';
            if (isHost) { const b = $('start-btn'); if (b) { b.style.display = ''; b.disabled = false; } }
        }
    });
    socket.on('player_left', ({ playerName }) => {
        $('lobby-status').innerHTML = `<span>${playerName}</span> a quitté la room.`;
        if (!IS_TOURNOI) {
            const b = $('start-btn'); if (b) b.disabled = true;
            resetSlot(1);
        }
    });
    socket.on('join_error', ({ message }) => showErr(message || 'Room introuvable.'));
    socket.on('error',      ({ message }) => showErr(message || 'Une erreur est survenue.'));

    socket.on('start_countdown', () => {
        if (redirecting) return;
        redirecting = true;
        localStorage.setItem('qpc_room', roomCode);
        localStorage.setItem('qpc_host', isHost ? '1' : '0');
        $('lobby-status').innerHTML = 'La partie démarre…';
        setTimeout(() => {
            window.location.href = `game-1v1.php?room=${roomCode}&friendly=1`;
        }, 400);
    });

    // ── ÉVÉNEMENTS CHAMPIONNAT 4P ──────────────────────────
    socket.on('champ_room_joined', ({ code, name }) => {
        roomCode = code;
        openLobby(code, [], true);
    });
    socket.on('room_state', (room) => {
        if (!room?.players) return;
        updatePlayers(room.players);
        const filled = room.players.length;
        $('lobby-status').innerHTML = filled < 4
            ? `En attente <span>(${filled}/4)</span>`
            : 'Tout le monde est là — cliquez PRÊT quand vous êtes chauds';
    });
    socket.on('champ_match_starting', () => {
        $('lobby-status').innerHTML = '<span>Ça démarre !</span>';
    });
    socket.on('champ_countdown', ({ count }) => {
        $('lobby-status').innerHTML = `Lancement dans <span>${count}</span>…`;
    });
    socket.on('champ_redirect_to_game', () => {
        if (redirecting) return;
        redirecting = true;
        localStorage.setItem('qpc_room', roomCode);
        window.location.href = `championship/game.php?room=${roomCode}&friendly=1`;
    });
    socket.on('champ_error', ({ message }) => showErr(message || 'Erreur championnat.'));

    // ── ÉVÉNEMENTS QUICK-MATCH AMICAL (duel + champ) ───────
    socket.on('friendly_queued', ({ inQueue: n }) => {
        setQuickMatchState('queued', n);
    });
    socket.on('friendly_cancelled', () => {
        setQuickMatchState('idle');
    });
    socket.on('friendly_match_found', ({ code, host }) => {
        if (redirecting) return;
        redirecting = true;
        roomCode = code; isHost = !!host;
        localStorage.setItem('qpc_room', code);
        localStorage.setItem('qpc_host', isHost ? '1' : '0');
        setQuickMatchState('found');
        setTimeout(() => {
            window.location.href = `game-1v1.php?room=${code}&friendly=1`;
        }, 600);
    });

    socket.on('champ_friendly_queued', ({ inQueue: n, needed }) => {
        setQuickMatchState('queued', n, needed);
    });
    socket.on('champ_friendly_cancelled', () => {
        setQuickMatchState('idle');
    });
    socket.on('champ_friendly_match_found', ({ code }) => {
        if (redirecting) return;
        redirecting = true;
        roomCode = code;
        localStorage.setItem('qpc_room', code);
        setQuickMatchState('found');
        // Le server envoie ensuite champ_match_starting/countdown/redirect
        // → on ne redirige PAS ici, on attend champ_redirect_to_game.
    });
}

// ═══════════════════════════════════════════════════════════
// UI EVENTS
// ═══════════════════════════════════════════════════════════
function initUI() {
    $('btn-create')?.addEventListener('click', createRoom);
    $('btn-join')  ?.addEventListener('click', joinRoom);
    $('btn-quick') ?.addEventListener('click', toggleQuickMatch);
    $('start-btn') ?.addEventListener('click', startGame);
    $('ready-btn') ?.addEventListener('click', toggleReady);
    $('leave-btn') ?.addEventListener('click', leaveRoom);

    $('copy-code-btn')?.addEventListener('click', copyCode);
    $('copy-link-btn')?.addEventListener('click', copyLink);

    // Active le bouton quick-match (server.js supporte maintenant la queue amicale)
    const qm = $('btn-quick');
    if (qm) qm.disabled = false;
    setQuickMatchState('idle');

    // Enter dans le champ code = rejoindre
    $('join-code')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') joinRoom();
    });

    // Extraction auto : si l'utilisateur colle un lien, on garde le code
    $('join-code')?.addEventListener('input', e => {
        const raw = e.target.value;
        const code = extractCode(raw);
        if (code !== raw.trim().toUpperCase()) {
            e.target.value = code;
        }
    });
}

// ═══════════════════════════════════════════════════════════
// QUICK MATCH
// ═══════════════════════════════════════════════════════════
function toggleQuickMatch() {
    if (!socket || !socket.connected) return;
    if (inQueue) {
        // Annuler
        if (IS_TOURNOI) socket.emit('champ_cancel_friendly', { playerId: PLAYER_ID });
        else            socket.emit('cancel_friendly',       { playerId: PLAYER_ID });
    } else {
        // Rejoindre la file
        const name = window.QPC_USER ? MY_NAME : (IS_TOURNOI ? 'Joueur' : `Joueur ${Math.floor(Math.random()*900)+100}`);
        localStorage.setItem('qpc_name', name);
        setQuickMatchState('searching');
        if (IS_TOURNOI) {
            socket.emit('champ_find_friendly_match', { playerId: PLAYER_ID, name, elo: MY_ELO });
        } else {
            socket.emit('find_friendly_match',       { playerId: PLAYER_ID, name, elo: MY_ELO });
        }
    }
}

function setQuickMatchState(state, n, needed) {
    const btn   = $('btn-quick');
    const title = btn?.querySelector('.quick-match-title');
    const sub   = btn?.querySelector('.quick-match-sub');
    const badge = btn?.querySelector('.quick-match-badge');
    if (!btn || !title || !sub || !badge) return;

    switch (state) {
        case 'idle':
            inQueue = false;
            title.textContent = 'Match rapide';
            sub.textContent = IS_TOURNOI
                ? 'On te matche avec 3 autres joueurs en file amicale.'
                : 'On te matche avec un autre joueur en file amicale.';
            badge.textContent = 'Rejoindre';
            btn.disabled = false;
            break;
        case 'searching':
            inQueue = true;
            title.textContent = 'Recherche en cours…';
            sub.textContent = 'On te matche dès qu\'il y a assez de joueurs.';
            badge.textContent = 'Annuler';
            break;
        case 'queued':
            inQueue = true;
            title.textContent = 'En file d\'attente';
            sub.textContent = needed
                ? `${n}/${needed} joueur(s) en attente. Ça avance…`
                : `${n} joueur(s) en file. Ça devrait être rapide.`;
            badge.textContent = 'Annuler';
            break;
        case 'found':
            inQueue = false;
            title.textContent = 'Adversaire trouvé !';
            sub.textContent = 'Redirection vers la partie…';
            badge.textContent = 'GO';
            btn.disabled = true;
            break;
    }
}

function extractCode(raw) {
    if (!raw) return '';
    // Extrait un code alphanum. de la fin d'une URL ou brut
    const clean = raw.trim();
    const urlMatch = clean.match(/(?:[?&]j=|[?&]join=|\/j\/|\/join\/)([A-Za-z0-9]{4,6})/);
    if (urlMatch) return urlMatch[1].toUpperCase();
    return clean.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
}

// ═══════════════════════════════════════════════════════════
// ACTIONS
// ═══════════════════════════════════════════════════════════
function createRoom() {
    hideErr();
    const name = window.QPC_USER
        ? ($('create-name').value.trim() || MY_NAME)
        : 'Joueur 1';
    localStorage.setItem('qpc_name', name);

    if (IS_TOURNOI) {
        socket.emit('champ_create_room', {
            playerId: PLAYER_ID,
            name,
            elo:      MY_ELO,
            isRanked: false
        });
    } else {
        socket.emit('create_room', {
            playerId: PLAYER_ID,
            name,
            elo:      MY_ELO,
            isRanked: false
        });
    }
}

function joinRoom() {
    hideErr();
    const raw = $('join-code').value;
    const code = extractCode(raw);
    if (!code || code.length < CODE_LEN) {
        showErr(`Entre un code à ${CODE_LEN} caractères (ou colle le lien reçu).`);
        return;
    }
    const name = window.QPC_USER
        ? ($('join-name').value.trim() || MY_NAME)
        : (IS_TOURNOI ? 'Joueur' : 'Joueur 2');
    localStorage.setItem('qpc_name', name);

    if (IS_TOURNOI) {
        socket.emit('champ_join_room', {
            playerId: PLAYER_ID, code, name, elo: MY_ELO, isRanked: false
        });
    } else {
        socket.emit('join_room', {
            playerId: PLAYER_ID, code, name, elo: MY_ELO
        });
    }
}

function startGame() {
    socket.emit('player_ready', { code: roomCode, playerId: PLAYER_ID });
}
function toggleReady() {
    socket.emit('champ_toggle_ready');
}
function leaveRoom() {
    try { socket.disconnect(); } catch (e) {}
    setTimeout(() => location.reload(), 100);
}

// ═══════════════════════════════════════════════════════════
// LOBBY UI
// ═══════════════════════════════════════════════════════════
function openLobby(code, players, host) {
    $('landing').style.display = 'none';
    $('lobby-screen').classList.add('visible');
    $('lobby-code-display').textContent = code;
    updatePlayers(players);
    if (IS_TOURNOI) {
        $('lobby-status').innerHTML = 'En attente <span>(1/4)</span>';
    } else {
        $('lobby-status').innerHTML = 'En attente du <span>2ème joueur</span>…';
    }
}

function initSlots() {
    const wrap = $('players-wrap');
    if (!wrap) return;
    wrap.innerHTML = '';
    for (let i = 0; i < MAX_PLAYERS; i++) {
        wrap.appendChild(createSlot(i));
    }
    if (!IS_TOURNOI) {
        const vs = document.createElement('div');
        vs.className = 'vs-sep';
        vs.textContent = 'VS';
        wrap.appendChild(vs);
    }
}

function createSlot(idx) {
    const slot = document.createElement('div');
    slot.className = 'player-slot';
    slot.id = `slot-${idx}`;
    slot.innerHTML = `
        <div class="player-avatar" id="avatar-${idx}">?</div>
        <div class="player-name" id="name-${idx}">En attente</div>
        <div class="player-elo"  id="elo-${idx}"></div>
        <div class="waiting-dots"><div class="waiting-dot"></div><div class="waiting-dot"></div><div class="waiting-dot"></div></div>
    `;
    return slot;
}

function updatePlayers(players) {
    for (let i = 0; i < MAX_PLAYERS; i++) {
        const slot = $(`slot-${i}`);
        if (!slot) continue;
        const p = players[i];
        if (p) {
            slot.classList.add('filled');
            if (p.playerId === PLAYER_ID) slot.classList.add('self');
            $(`avatar-${i}`).textContent = (p.name || '?').charAt(0).toUpperCase();
            $(`name-${i}`).textContent = p.name || `Joueur ${i+1}`;
            $(`elo-${i}`).textContent = p.elo ? `ELO ${p.elo}` : '';
            const dots = slot.querySelector('.waiting-dots');
            if (dots) dots.style.display = 'none';
        } else {
            slot.classList.remove('filled', 'self');
            $(`avatar-${i}`).textContent = '?';
            $(`name-${i}`).textContent = 'En attente';
            $(`elo-${i}`).textContent = '';
            const dots = slot.querySelector('.waiting-dots');
            if (dots) dots.style.display = '';
        }
    }
}

function resetSlot(i) {
    const slot = $(`slot-${i}`);
    if (!slot) return;
    slot.classList.remove('filled', 'self');
    $(`avatar-${i}`).textContent = '?';
    $(`name-${i}`).textContent = 'En attente';
    $(`elo-${i}`).textContent = '';
}

// ═══════════════════════════════════════════════════════════
// CLIPBOARD
// ═══════════════════════════════════════════════════════════
function copyCode() {
    if (!roomCode) return;
    navigator.clipboard.writeText(roomCode)
        .then(() => toast('Code copié !'))
        .catch(() => toast('Copie impossible'));
}
function copyLink() {
    if (!roomCode) return;
    const url = `${location.origin}${location.pathname}?mode=${MODE}&join=${roomCode}`;
    navigator.clipboard.writeText(url)
        .then(() => toast('Lien copié ! Partage-le 🎉'))
        .catch(() => toast('Copie impossible'));
}

// ═══════════════════════════════════════════════════════════
// ADVERSAIRES RÉCENTS
// ═══════════════════════════════════════════════════════════
async function loadRecents() {
    const box  = $('recents');
    const list = $('recents-list');
    if (!box || !list || !window.QPC_USER) return;

    try {
        const res = await fetch(`get_recent_opponents.php?mode=${MODE}`, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.ok || !data.opponents.length) return;

        list.innerHTML = data.opponents.map(o => {
            const initials = (o.username || '?').slice(0, 2).toUpperCase();
            return `
                <div class="recent-item">
                    <div class="recent-avatar">${initials}</div>
                    <div class="recent-info">
                        <div class="recent-name">${escapeHtml(o.username)}</div>
                        <div class="recent-meta">${formatDate(o.last_played)} · ELO ${o.elo}</div>
                    </div>
                    <button class="recent-btn" data-opp-id="${o.id}" data-opp-name="${escapeHtml(o.username)}" disabled title="Défi temps réel : arrive à l'étape 3">Défier</button>
                </div>
            `;
        }).join('');
        box.hidden = false;
    } catch (e) {
        // silencieux — la section reste cachée
    }
}

function formatDate(sql) {
    if (!sql) return '';
    const d = new Date(sql.replace(' ', 'T'));
    const diffH = (Date.now() - d.getTime()) / 3600000;
    if (diffH < 1)  return 'Il y a moins d\'1 h';
    if (diffH < 24) return `Il y a ${Math.round(diffH)} h`;
    const days = Math.round(diffH / 24);
    if (days < 7)   return `Il y a ${days} j`;
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}

function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));
}

// ═══════════════════════════════════════════════════════════
// UTILS
// ═══════════════════════════════════════════════════════════
function showErr(msg) {
    const el = $('error-msg');
    if (!el) return toast(msg);
    el.textContent = msg;
    el.classList.add('visible');
}
function hideErr() {
    const el = $('error-msg');
    if (el) el.classList.remove('visible');
}

let toastTimer = null;
function toast(msg) {
    const t = $('toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('visible'), 2200);
}
