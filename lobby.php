<?php
// ============================================================================
//  QPC - Championship Lobby (4 joueurs)
//  Auth obligatoire SAUF en mode amical (?friendly=1 → guest play autorisé)
// ============================================================================
require_once __DIR__ . '/../csrf.php';
require __DIR__ . "/../db.php";

// ── Salon AMICAL uniquement ─────────────────────────────────
// Le mode CLASSÉ passe désormais par championship/lobby-ranked.php
// (matchmaking à 4). Ici l'ELO ne bouge jamais → invités autorisés.
$is_friendly = true;

// ── Defaults guest ──────────────────────────────────────────
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

    if (!$user) {
        session_destroy();
        header("Location: ../connexion.php");
        exit;
    }

    $username    = $user['username'];
    $profile_pic = $user['profile_pic'] ?? '';

    $res = $conn->prepare("SELECT elo FROM player_stats WHERE user_id = ?");
    $res->bind_param("i", $user_id);
    $res->execute();
    $row = $res->get_result()->fetch_assoc();
    if ($row && isset($row['elo'])) {
        $elo = (int) $row['elo'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QPC — Championnat<?= $is_friendly ? ' (Amical)' : '' ?></title>

<!-- ════ ANTI-FLASH : applique le thème global avant le render ════ -->
<script>
(function () {
  try {
    var stored = localStorage.getItem('qpc-theme');
    if (stored === 'light') {
      document.documentElement.classList.add('light');
    }
  } catch (e) {}
})();
</script>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="lobby.css">

<style>
/* Badge mode amical (visible uniquement quand ?friendly=1) */
.friendly-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(109, 184, 255, 0.12);
    border: 1px solid rgba(109, 184, 255, 0.4);
    color: #6db8ff;
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin: 0 auto 1rem;
}
.friendly-badge::before { content: '🎉'; font-size: 1rem; }

.guest-notice {
    color: rgba(255,255,255,0.6);
    font-size: 0.78rem;
    text-align: center;
    margin: 0.8rem auto 1rem;
    max-width: 380px;
    line-height: 1.5;
}
.guest-notice a {
    color: var(--gold, #d4af37);
    text-decoration: none;
    border-bottom: 1px dashed currentColor;
}
.guest-notice strong { color: #6db8ff; }
</style>
</head>
<body>

<a href="<?= $is_guest ? '../game.php' : '../dashboard.php' ?>" class="back-btn"><span>←</span><span>Retour</span></a>

<!-- Theme toggle (à gauche du logo) -->
<button id="theme-toggle" class="theme-toggle" aria-label="Basculer le thème" type="button">
    <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
    </svg>
    <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
    </svg>
</button>

<div class="logo-wrap">QPC</div>

<!-- ─── Écran "connexion au serveur" ─── -->
<div class="connecting-wrap" id="connecting">
    <div class="spinner"></div>
    <div class="connecting-text">Connexion au serveur…</div>
</div>

<!-- ─── Écran principal (créer / rejoindre) ─── -->
<div class="container" id="main-screen" style="display:none;">
    <?php if ($is_friendly): ?>
        <div class="friendly-badge">Championnat amical · ELO non affecté</div>
    <?php endif; ?>
    <?php if ($is_guest): ?>
        <div class="guest-notice">
            Tu joues en invité — le serveur t'attribuera <strong>Joueur 1/2/3/4</strong> selon ton ordre d'arrivée.
            <br>Tu préfères garder ton historique ? <a href="../connexion.php">Connecte-toi</a>.
        </div>
    <?php endif; ?>
    <div class="title-wrap">
        <div class="vs-badge">
            <div class="vs-line"></div>
            <div class="vs-text">CHAMPIONNAT<?= $is_friendly ? ' AMICAL' : '' ?></div>
            <div class="vs-line"></div>
        </div>
        <div class="title-sub"><?= $is_friendly ? '4 joueurs · 3 manches · Sans pression ELO' : '4 joueurs · 3 manches · Le grand titre' ?></div>
    </div>

    <?php if (!$is_guest): ?>
    <div class="elo-display">
        <div>
            <div class="elo-label">Votre ELO</div>
            <div class="elo-value" id="my-elo"><?= (int) $elo ?></div>
        </div>
        <div class="elo-division">Joueur QPC</div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title"><span>🏆</span> Créer un championnat</div>
        <?php if (!$is_guest): ?>
        <div class="input-group">
            <div class="input-label">Votre pseudo</div>
            <input class="input-field" type="text" id="create-name" value="<?= htmlspecialchars($username) ?>" readonly>
        </div>
        <?php else: ?>
        <input type="hidden" id="create-name" value="">
        <div class="guest-notice" style="margin:0 0 0.8rem;">Tu seras nommé <strong>Joueur 1</strong></div>
        <?php endif; ?>
        <button class="btn btn-primary" id="btn-create">CRÉER LA PARTIE</button>
    </div>

    <div class="divider">
        <div class="divider-line"></div>
        <div class="divider-text">ou</div>
        <div class="divider-line"></div>
    </div>

    <div class="card">
        <div class="card-title"><span>🎯</span> Rejoindre un championnat</div>
        <?php if (!$is_guest): ?>
        <div class="input-group">
            <div class="input-label">Votre pseudo</div>
            <input class="input-field" type="text" id="join-name" value="<?= htmlspecialchars($username) ?>" readonly>
        </div>
        <?php else: ?>
        <input type="hidden" id="join-name" value="">
        <div class="guest-notice" style="margin:0 0 0.8rem;">Tu seras nommé <strong>Joueur 2/3/4</strong> selon ton arrivée</div>
        <?php endif; ?>
        <div class="input-group">
            <div class="input-label">Code de la partie</div>
            <input class="input-field code-input" type="text" id="join-code" placeholder="ABCDE" maxlength="5">
        </div>
        <div class="error-msg" id="error-msg"></div>
        <button class="btn btn-primary" id="btn-join">REJOINDRE</button>
    </div>
</div>

<!-- ─── Écran lobby (4 places) ─── -->
<div class="lobby-screen" id="lobby-screen">
    <div class="title-wrap">
        <div class="vs-badge">
            <div class="vs-line"></div>
            <div class="vs-text">LOBBY</div>
            <div class="vs-line"></div>
        </div>
        <div class="title-sub">En attente des 4 joueurs</div>
    </div>

    <div class="lobby-code-wrap">
        <div class="lobby-code-label">Code de la partie</div>
        <div class="lobby-code" id="lobby-code-display">—</div>
        <div class="copy-hint" id="copy-hint">📋 Copier le code</div>
    </div>

    <div class="players-grid-4" id="players-grid"></div>

    <div class="lobby-status" id="lobby-status">En attente de joueurs…</div>
    <div class="lobby-actions">
        <button class="btn btn-primary" id="ready-btn">JE SUIS PRÊT</button>
        <button class="btn btn-secondary" id="leave-btn">Quitter</button>
    </div>
</div>

<!-- ─── Countdown overlay ─── -->
<div class="countdown-overlay" id="countdown-overlay">
    <div class="countdown-label">Lancement dans</div>
    <div class="countdown-number" id="countdown-number">3</div>
</div>

<!-- Scripts -->
<script>
// ── Flag amical (injecté depuis $_GET['friendly']) ──
window.QPC_FRIENDLY = <?= $is_friendly ? 'true' : 'false' ?>;

<?php if (!$is_guest): ?>
window.QPC_USER = {
    id:       <?= (int) $user_id ?>,
    username: <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>,
    elo:      <?= (int) $elo ?>
};
try {
    localStorage.setItem('qpc_name', window.QPC_USER.username);
    localStorage.setItem('qpc_elo',  String(window.QPC_USER.elo));
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
} catch (e) {}
<?php else: ?>
// ── Guest mode ──
window.QPC_USER = null;

try {
    let guestId = localStorage.getItem('qpc_player_id');

    // Si aucun guest ou ancien compte connecté → nouveau guest stable
    if (!guestId || guestId.startsWith('u')) {
        guestId = 'guest_' + crypto.randomUUID();
        localStorage.setItem('qpc_player_id', guestId);
    }

    // ── On EFFACE qpc_name à l'entrée du lobby : le serveur le réassignera
    //    via auto-naming "Joueur N" et nous le renverra dans champ_room_joined.
    //    Sans ce reset, un ancien 'Joueur 3' resté en localStorage écraserait
    //    la nouvelle assignation.
    localStorage.removeItem('qpc_name');

    localStorage.setItem('qpc_elo', '1200');
} catch (e) {}
<?php endif; ?>

/* ═══════════════════════════════════════════
   THEME TOGGLE (indépendant du JS du lobby)
═══════════════════════════════════════════ */
(function () {
    var root = document.documentElement;
    var toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    toggle.addEventListener('click', function () {
        root.classList.add('theme-transitioning');
        var isLight = root.classList.toggle('light');
        try { localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); } catch (e) {}
        setTimeout(function () { root.classList.remove('theme-transitioning'); }, 300);
    });
})();
</script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="../qpc-config.js"></script>
<script src="lobby.js"></script>

</body>
</html>
