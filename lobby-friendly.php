<?php
// ═══════════════════════════════════════════════════════════
// lobby-friendly.php
// ═══════════════════════════════════════════════════════════
// Lobby AMICAL UNIFIÉ : gère le duel 1v1 ET le championnat 4 joueurs
// via un toggle en haut. Remplace lobby-1v1.php?friendly=1 et
// championship/lobby.php?friendly=1.
//
// Nouveautés :
//  - Match rapide (queue friendly) — bouton "Bientôt" tant que
//    server.js n'a pas la queue (activé à l'étape 2)
//  - Créer une room copie automatiquement un lien direct
//  - Rejoindre accepte code brut OU lien collé
//  - Adversaires récents (si connecté) via get_recent_opponents.php
//  - Auth non requise (guest OK), ELO figé
// ═══════════════════════════════════════════════════════════
require_once __DIR__ . '/csrf.php';
require __DIR__ . '/db.php';

// ── Mode : duel (1v1) ou tournoi (championnat 4p) ─────────
$mode = ($_GET['mode'] ?? 'duel') === 'tournoi' ? 'tournoi' : 'duel';

// ── Auto-join si l'URL contient ?join=CODE ─────────────────
$prefill_code = strtoupper(trim($_GET['join'] ?? ''));
$prefill_code = preg_replace('/[^A-Z0-9]/', '', $prefill_code);
if (strlen($prefill_code) > 5) $prefill_code = substr($prefill_code, 0, 5);

// ── User (optionnel) ───────────────────────────────────────
$user_id     = null;
$username    = '';
$profile_pic = '';
$elo         = 1200;
$is_guest    = !isset($_SESSION['user_id']);

if (!$is_guest) {
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) { session_destroy(); header("Location: connexion.php"); exit; }

    $username    = $user['username'];
    $profile_pic = $user['profile_pic'] ?? '';

    $res = $conn->prepare("SELECT elo FROM player_stats WHERE user_id = ?");
    $res->bind_param("i", $user_id);
    $res->execute();
    $row = $res->get_result()->fetch_assoc();
    if ($row && isset($row['elo'])) $elo = (int) $row['elo'];
}

// ── Longueur du code selon mode ────────────────────────────
$code_len = ($mode === 'tournoi') ? 5 : 4;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QPC — <?= $mode === 'tournoi' ? 'Championnat' : 'Duel' ?> amical</title>

<!-- Anti-flash thème -->
<script>(function(){try{if(localStorage.getItem('qpc-theme')==='light')document.documentElement.classList.add('light');}catch(e){}})();</script>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="lobby-friendly.css">
</head>
<body data-mode="<?= $mode ?>">

<!-- ═══════ HEADER PARTAGÉ ═══════ -->
<header class="top-bar">
    <a href="game.php" class="back-link">← Retour</a>

    <div style="display:flex; align-items:center;">
        <div class="brand-logo">QPC</div>
        <button id="theme-toggle" class="theme-toggle" aria-label="Basculer le thème" type="button">
            <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path></svg>
            <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        </button>
    </div>

    <div class="user-chip">
        <?php if ($user_id): ?>
            <span><?= htmlspecialchars($username) ?></span>
            <span>·</span>
            <span>ELO <strong><?= (int)$elo ?></strong></span>
        <?php else: ?>
            <a href="connexion.php" class="back-link">Connexion</a>
        <?php endif; ?>
    </div>
</header>

<!-- ═══════ MAIN — LANDING ═══════ -->
<main class="landing" id="landing">

    <div class="landing-head">
        <div class="friendly-badge">🎉 MODE AMICAL · ELO FIGÉ</div>
        <h1 class="landing-title">Jouer entre amis</h1>
        <p class="landing-sub">Aucun classement en jeu. Juste du fun entre potes.</p>
    </div>

    <!-- Toggle mode -->
    <div class="mode-toggle" role="tablist" aria-label="Type de partie">
        <a href="lobby-friendly.php?mode=duel"
           class="mode-toggle-btn <?= $mode === 'duel' ? 'active' : '' ?>"
           role="tab" aria-selected="<?= $mode === 'duel' ? 'true' : 'false' ?>">
            ⚔️ Duel 1v1
        </a>
        <a href="lobby-friendly.php?mode=tournoi"
           class="mode-toggle-btn <?= $mode === 'tournoi' ? 'active' : '' ?>"
           role="tab" aria-selected="<?= $mode === 'tournoi' ? 'true' : 'false' ?>">
            👑 Championnat
        </a>
    </div>

    <!-- ─── Quick match hero ─── -->
    <button class="quick-match" id="btn-quick" disabled>
        <div class="quick-match-icon">⚡</div>
        <div class="quick-match-body">
            <div class="quick-match-title">Match rapide</div>
            <div class="quick-match-sub">On te matche avec un autre joueur en file amicale.</div>
        </div>
        <div class="quick-match-cta">
            <span class="quick-match-badge">Bientôt</span>
        </div>
    </button>

    <!-- Divider -->
    <div class="landing-divider">
        <span></span>
        <span class="landing-divider-label">OU JOUER AVEC QUELQU'UN DE PRÉCIS</span>
        <span></span>
    </div>

    <!-- ─── Create + Join en grid ─── -->
    <div class="landing-grid">

        <div class="card">
            <div class="card-title"><span>⚔️</span> Créer une room</div>
            <?php if (!$is_guest): ?>
                <div class="input-group">
                    <div class="input-label">Votre pseudo</div>
                    <input class="input-field" type="text" id="create-name" maxlength="20" value="<?= htmlspecialchars($username) ?>" readonly>
                </div>
            <?php else: ?>
                <input type="hidden" id="create-name" value="">
                <div class="guest-notice">Tu seras nommé <strong>Joueur 1</strong></div>
            <?php endif; ?>
            <button class="btn btn-primary" id="btn-create">CRÉER + COPIER LE LIEN</button>
            <div class="card-hint">Un code + un lien à partager sur WhatsApp / Discord.</div>
        </div>

        <div class="card">
            <div class="card-title"><span>🎯</span> Rejoindre</div>
            <?php if (!$is_guest): ?>
                <div class="input-group">
                    <div class="input-label">Votre pseudo</div>
                    <input class="input-field" type="text" id="join-name" maxlength="20" value="<?= htmlspecialchars($username) ?>" readonly>
                </div>
            <?php else: ?>
                <input type="hidden" id="join-name" value="">
                <div class="guest-notice">Tu seras nommé <strong>Joueur 2</strong> (ou 3/4 en champ.)</div>
            <?php endif; ?>
            <div class="input-group">
                <div class="input-label">Code ou lien reçu</div>
                <input class="input-field code-input"
                       type="text"
                       id="join-code"
                       placeholder="<?= $mode === 'tournoi' ? 'ABCDE' : 'A7K2' ?>"
                       maxlength="200"
                       value="<?= htmlspecialchars($prefill_code) ?>">
            </div>
            <div class="error-msg" id="error-msg"></div>
            <button class="btn btn-primary" id="btn-join">REJOINDRE</button>
        </div>

    </div>

    <!-- ─── Adversaires récents (only si loggué) ─── -->
    <?php if ($user_id): ?>
    <section class="recents" id="recents" hidden>
        <div class="recents-head">
            <div class="recents-title">Adversaires récents</div>
            <div class="recents-hint">5 derniers <?= $mode === 'tournoi' ? 'championnats' : 'duels' ?></div>
        </div>
        <div class="recents-list" id="recents-list"></div>
    </section>
    <?php endif; ?>

</main>

<!-- ═══════ LOBBY (2 ou 4 slots) ═══════ -->
<div class="lobby-screen" id="lobby-screen">
    <div class="title-wrap">
        <div class="vs-badge"><div class="vs-line"></div><div class="vs-text">LOBBY</div><div class="vs-line"></div></div>
        <div class="title-sub" id="lobby-title-sub">En attente <?= $mode === 'tournoi' ? 'des 4 joueurs' : 'du 2ème joueur' ?>…</div>
    </div>

    <div class="lobby-code-wrap">
        <div class="lobby-code-label">Code de la room</div>
        <div class="lobby-code" id="lobby-code-display">—</div>
        <div class="lobby-share-row">
            <button class="lobby-share-btn" id="copy-code-btn" type="button">📋 Copier le code</button>
            <button class="lobby-share-btn" id="copy-link-btn" type="button">🔗 Copier le lien</button>
        </div>
    </div>

    <div class="players-wrap" id="players-wrap" data-mode="<?= $mode ?>">
        <!-- Rempli dynamiquement en JS selon mode -->
    </div>

    <div class="lobby-status" id="lobby-status">En attente…</div>
    <div class="lobby-actions">
        <?php if ($mode === 'duel'): ?>
            <button class="btn btn-primary" id="start-btn" disabled>LANCER LA PARTIE</button>
        <?php else: ?>
            <button class="btn btn-primary" id="ready-btn">JE SUIS PRÊT</button>
        <?php endif; ?>
        <button class="btn btn-secondary" id="leave-btn">Quitter</button>
    </div>
</div>

<!-- ═══════ Toast ═══════ -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<!-- ═══════ Config JS injectée ═══════ -->
<script>
window.QPC_MODE     = <?= json_encode($mode) ?>;      // 'duel' | 'tournoi'
window.QPC_FRIENDLY = true;                           // toujours amical ici
window.QPC_CODE_LEN = <?= (int) $code_len ?>;         // 4 (duel) ou 5 (tournoi)
<?php if (!$is_guest): ?>
window.QPC_USER = {
    id: <?= (int) $user_id ?>,
    username: <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>,
    elo: <?= (int) $elo ?>
};
try {
    localStorage.setItem('qpc_name', window.QPC_USER.username);
    localStorage.setItem('qpc_elo', String(window.QPC_USER.elo));
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
} catch(e) {}
<?php else: ?>
window.QPC_USER = null;
try {
    if ((localStorage.getItem('qpc_player_id') || '').startsWith('u')) {
        localStorage.removeItem('qpc_player_id');
    }
    localStorage.removeItem('qpc_name');
    localStorage.setItem('qpc_elo', '1200');
} catch(e) {}
<?php endif; ?>
</script>

<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="qpc-config.js"></script>
<script src="lobby-friendly.js"></script>

<!-- Theme toggle -->
<script>
(function(){
    const root = document.documentElement;
    const btn  = document.getElementById('theme-toggle');
    if (!btn) return;
    btn.addEventListener('click', () => {
        root.classList.add('theme-transitioning');
        const isLight = root.classList.toggle('light');
        try { localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); } catch(e) {}
        setTimeout(() => root.classList.remove('theme-transitioning'), 300);
    });
})();
</script>

</body>
</html>
