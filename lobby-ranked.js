/* ══════════════════════════════════════════════════
   QPC — Lobby CLASSÉ (matchmaking côté client)

   Flux :
     1. Connexion socket → écran PRÊT (carte joueur + bouton)
     2. "Lancer la recherche" → find_ranked_match → écran RECHERCHE
        (radar + chrono + nb en file). "Annuler" → cancel_ranked.
     3. match_found → écran ADVERSAIRE TROUVÉ (clash VS) → après 2.6s,
        redirection vers game-1v1.php?room=CODE (ELO en jeu, pas de &friendly).

   La partie classée réutilise tout le flux de game-1v1.php : à l'arrivée,
   game-1v1.js émet rejoin_room, et le serveur lance le countdown quand les
   2 joueurs sont sur la page. La room est déjà créée côté serveur au moment
   de l'appariement (status 'preparing_game').
══════════════════════════════════════════════════ */

'use strict';

const SERVER_URL = (window.QPC_CONFIG && window.QPC_CONFIG.SERVER_URL) || 'http://localhost:3000';

const MY_NAME = localStorage.getItem('qpc_name') || (window.QPC_USER && window.QPC_USER.username) || 'Joueur';
const MY_ELO  = parseInt(localStorage.getItem('qpc_elo')) || (window.QPC_USER && window.QPC_USER.elo) || 1200;
const MY_PIC  = (window.QPC_USER && window.QPC_USER.pic) || '';

let PLAYER_ID = localStorage.getItem('qpc_player_id');
if (!PLAYER_ID) { // sécurité : ne devrait pas arriver (PHP exige un compte)
    PLAYER_ID = 'u0';
    localStorage.setItem('qpc_player_id', PLAYER_ID);
}

let socket      = null;
let searching   = false;
let timerInt    = null;
let elapsed     = 0;
let redirecting = false;

function $(id) { return document.getElementById(id); }

// ── Division selon ELO (mêmes seuils que le reste du projet) ──
function getDivision(elo) {
    if (elo < 1200) return { label:'Filet',      cls:'div-filet' };
    if (elo < 1500) return { label:'Division 3', cls:'div-3'     };
    if (elo < 1800) return { label:'Division 2', cls:'div-2'     };
    if (elo < 2000) return { label:'Division 1', cls:'div-1'     };
    return              { label:'Élite 👑',    cls:'div-elite' };
}

function setAvatar(el, name, pic) {
    const initial = (name || '?').charAt(0).toUpperCase();
    if (pic) {
        el.innerHTML = '';
        const img = document.createElement('img');
        img.src = pic; img.alt = '';
        img.onerror = () => { el.textContent = initial; };
        el.appendChild(img);
    } else {
        el.textContent = initial;
    }
}

function fillCard(prefix, name, elo, pic) {
    setAvatar($(prefix + '-avatar'), name, pic);
    $(prefix + '-name').textContent = name;
    $(prefix + '-elo').innerHTML    = `${elo}<small> ELO</small>`;
    const d = getDivision(elo);
    const dEl = $(prefix + '-div');
    dEl.textContent = d.label;
    dEl.className   = 'pcard-div ' + d.cls;
}

// ── Navigation entre écrans ──
function showStage(id) {
    $('connecting').classList.remove('active');
    ['stage-ready', 'stage-search', 'stage-found'].forEach(s => $(s).classList.remove('active'));
    if (id) $(id).classList.add('active');
}

// ── Chrono de recherche ──
function startTimer() {
    elapsed = 0;
    $('search-timer').textContent = '0:00';
    timerInt = setInterval(() => {
        elapsed++;
        const m = Math.floor(elapsed / 60);
        const s = String(elapsed % 60).padStart(2, '0');
        $('search-timer').textContent = `${m}:${s}`;
    }, 1000);
}
function stopTimer() { if (timerInt) { clearInterval(timerInt); timerInt = null; } }

// ── Actions ──
function startSearch() {
    if (searching) return;
    searching = true;
    showStage('stage-search');
    $('search-queue').textContent = '1';
    startTimer();
    socket.emit('find_ranked_match', { playerId: PLAYER_ID, name: MY_NAME, elo: MY_ELO });
}

function cancelSearch() {
    if (!searching) return;
    searching = false;
    stopTimer();
    socket.emit('cancel_ranked', { playerId: PLAYER_ID });
    showStage('stage-ready');
}

// ── INIT ──
function init() {
    // Carte joueur (écran prêt)
    fillCard('ready', MY_NAME, MY_ELO, MY_PIC);

    $('btn-search').addEventListener('click', startSearch);
    $('btn-cancel').addEventListener('click', cancelSearch);

    socket = io(SERVER_URL, { transports: ['websocket'] });

    socket.on('connect',       () => { if (!searching) showStage('stage-ready'); });
    socket.on('connect_error', () => {
        showStage(null);
        $('connecting').classList.add('active');
        $('connecting').querySelector('.connecting-text').textContent =
            'Serveur injoignable — lance « node server.js ».';
    });

    socket.on('ranked_queued', ({ inQueue }) => {
        if (typeof inQueue === 'number') $('search-queue').textContent = inQueue;
    });

    socket.on('match_found', ({ code, you, opponent, host }) => {
        if (redirecting) return;
        redirecting = true;
        searching = false;
        stopTimer();

        // Remplit le clash VS
        fillCard('found-you', you.name, you.elo, MY_PIC);
        fillCard('found-opp', opponent.name, opponent.elo, '');
        showStage('stage-found');

        // Mémorise la room pour game-1v1.php (lu via ?room= ou localStorage)
        localStorage.setItem('qpc_room', code);
        localStorage.setItem('qpc_host', host ? '1' : '0');

        // Laisse l'animation VS jouer, puis bascule sur le duel (ELO en jeu)
        setTimeout(() => { window.location.href = `game-1v1.php?room=${code}`; }, 2600);
    });
}

// Best-effort : si on quitte la page en pleine recherche, on sort de la file.
window.addEventListener('beforeunload', () => {
    if (searching && socket && socket.connected) {
        socket.emit('cancel_ranked', { playerId: PLAYER_ID });
    }
});

init();
