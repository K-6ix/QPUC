/* ══════════════════════════════════════════════════
   QPC — Lobby TOURNOI CLASSÉ (matchmaking à 4)

   Flux :
     1. Connexion → écran PRÊT (carte joueur + bouton)
     2. "Lancer la recherche" → champ_find_ranked_match → écran RECHERCHE
        (radar + chrono + N/4 en file). "Annuler" → champ_cancel_ranked.
     3. champ_match_found → écran TOURNOI FORMÉ (les 4 joueurs) →
        champ_match_starting/champ_countdown → champ_redirect_to_game →
        game.php?code=CODE (ELO en jeu, pas de &friendly).

   Le tournoi lui-même (M1/M2/M3) est le code existant : à l'appariement,
   le serveur crée la room à 4 puis lance EXACTEMENT startM1 comme avant.
══════════════════════════════════════════════════ */

(() => {
'use strict';

const SERVER_URL = (window.QPC_CONFIG && window.QPC_CONFIG.SERVER_URL) || 'http://localhost:3000';

const U = window.QPC_USER || {};
const MY_NAME = U.username || localStorage.getItem('qpc_name') || 'Joueur';
const MY_ELO  = parseInt(U.elo) || parseInt(localStorage.getItem('qpc_elo')) || 1200;
const MY_PIC  = U.pic || '';
let PLAYER_ID = localStorage.getItem('qpc_player_id') || ('u' + (U.id || 0));

let socket = null, searching = false, timerInt = null, elapsed = 0, redirecting = false;
let currentCode = null;

const $ = (id) => document.getElementById(id);

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

function fillReadyCard() {
    setAvatar($('ready-avatar'), MY_NAME, MY_PIC);
    $('ready-name').textContent = MY_NAME;
    $('ready-elo').innerHTML    = `${MY_ELO}<small> ELO</small>`;
    const d = getDivision(MY_ELO);
    $('ready-div').textContent = d.label;
    $('ready-div').className   = 'pcard-div ' + d.cls;
}

function showStage(id) {
    $('connecting').classList.remove('active');
    ['stage-ready','stage-search','stage-found'].forEach(s => $(s).classList.remove('active'));
    if (id) $(id).classList.add('active');
}

function updateSlots(n) {
    const filled = Math.max(1, Math.min(4, n));
    $('search-queue').textContent = `${filled}/4`;
    const dots = document.querySelectorAll('#slot-dots .slot-dot');
    dots.forEach((d, i) => d.classList.toggle('on', i < filled));
}

function startTimer() {
    elapsed = 0; $('search-timer').textContent = '0:00';
    timerInt = setInterval(() => {
        elapsed++;
        const m = Math.floor(elapsed / 60), s = String(elapsed % 60).padStart(2, '0');
        $('search-timer').textContent = `${m}:${s}`;
    }, 1000);
}
function stopTimer() { if (timerInt) { clearInterval(timerInt); timerInt = null; } }

function startSearch() {
    if (searching) return;
    searching = true;
    showStage('stage-search');
    updateSlots(1);
    startTimer();
    socket.emit('champ_find_ranked_match', { playerId: PLAYER_ID, name: MY_NAME, elo: MY_ELO });
}
function cancelSearch() {
    if (!searching) return;
    searching = false;
    stopTimer();
    socket.emit('champ_cancel_ranked', { playerId: PLAYER_ID });
    showStage('stage-ready');
}

function renderRoster(players) {
    const grid = $('roster');
    grid.innerHTML = '';
    players.forEach(p => {
        const mine = (p.id === PLAYER_ID);
        const d = getDivision(p.elo || 1200);
        const card = document.createElement('div');
        card.className = 'pcard' + (mine ? ' you' : ' gold-edge');
        card.innerHTML = `
            <div class="pcard-avatar"></div>
            <div class="pcard-name">${escapeHtml(p.name)}${mine ? ' (toi)' : ''}</div>
            <div class="pcard-elo">${p.elo || 1200}<small> ELO</small></div>
            <div class="pcard-div ${d.cls}">${d.label}</div>`;
        setAvatar(card.querySelector('.pcard-avatar'), p.name, mine ? MY_PIC : '');
        grid.appendChild(card);
    });
}
function escapeHtml(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

function init() {
    fillReadyCard();
    $('btn-search').addEventListener('click', startSearch);
    $('btn-cancel').addEventListener('click', cancelSearch);

    socket = io(SERVER_URL, { transports: ['websocket', 'polling'] });

    socket.on('connect',       () => { if (!searching) showStage('stage-ready'); });
    socket.on('connect_error', () => {
        showStage(null);
        $('connecting').classList.add('active');
        $('connecting').querySelector('.connecting-text').textContent =
            'Serveur injoignable — lance « node server.js ».';
    });

    socket.on('champ_ranked_queued', ({ inQueue }) => {
        if (typeof inQueue === 'number') updateSlots(inQueue);
    });

    socket.on('champ_match_found', ({ code, players }) => {
        if (redirecting) return;
        redirecting = true;
        searching = false;
        stopTimer();
        currentCode = code;
        try { localStorage.setItem('qpc_champ_room', code); } catch (e) {}
        renderRoster(players || []);
        showStage('stage-found');
    });

    // Countdown émis par la séquence de départ partagée (comme l'amical)
    socket.on('champ_match_starting', ({ in: n }) => {
        $('cd-num').textContent = n;
        $('cd-overlay').classList.add('active');
    });
    socket.on('champ_countdown', ({ count }) => { $('cd-num').textContent = count; });

    // Bascule vers le jeu (championship/game.php). ELO en jeu → pas de &friendly.
    socket.on('champ_redirect_to_game', () => {
        try { localStorage.setItem('qpc_champ_room', currentCode); } catch (e) {}
        window.location.href = `game.php?code=${encodeURIComponent(currentCode)}`;
    });

    // Fallback si m1_started arrive avant la redirection
    socket.on('m1_started', () => {
        if (currentCode && window.location.pathname.includes('lobby-ranked.php')) {
            window.location.href = `game.php?code=${encodeURIComponent(currentCode)}`;
        }
    });
}

window.addEventListener('beforeunload', () => {
    if (searching && socket && socket.connected) {
        socket.emit('champ_cancel_ranked', { playerId: PLAYER_ID });
    }
});

showStage('connecting');
init();
})();
