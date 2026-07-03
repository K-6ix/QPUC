<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ════════════════════════════════════════════════════════════
// LOBBY AMICAL — duel par code, ELO figé
// Le mode CLASSÉ passe désormais par lobby-ranked.php (matchmaking
// anonyme). Ici on ne choisit pas son adversaire via l'ELO : c'est
// du jeu entre amis (code de room), donc l'ELO ne bouge JAMAIS et le
// jeu en invité est autorisé (aucune auth requise).
// ════════════════════════════════════════════════════════════
$is_friendly = true;

// ── Defaults guest ──────────────────────────────────────────
$user_id     = null;
$username    = '';
$profile_pic = '';
$elo         = 1200;
$is_guest    = !isset($_SESSION['user_id']);

if (!$is_guest) {
    // Utilisateur connecté : on récupère ses infos depuis la BDD
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        session_destroy();
        header("Location: connexion.php");
        exit;
    }

    $username    = $user['username'];
    $profile_pic = $user['profile_pic'] ?? '';

    // ELO depuis player_stats
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
<title>QPC — 1v1<?= $is_friendly ? ' (Amical)' : '' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="lobby-1v1.css">

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
</style>
</head>
<body>

<a href="<?= $is_guest ? 'game.php' : 'dashboard.php' ?>" class="back-btn"><span>←</span><span>Retour</span></a>
<div class="logo-wrap">QPC</div>

<!-- ─── Écran "connexion au serveur" ─── -->
<div class="connecting-wrap" id="connecting">
    <div class="spinner"></div>
    <div class="connecting-text">Connexion au serveur…</div>
</div>

<!-- ─── Écran principal (créer / rejoindre) ─── -->
<div class="container" id="main-screen" style="display:none;">
    <?php if ($is_friendly): ?>
        <div class="friendly-badge">Salon amical · ELO non affecté</div>
    <?php endif; ?>
    <?php if ($is_guest): ?>
        <div class="guest-notice">
            Tu joues en invité — choisis un pseudo et lance la partie.
            <br>Tu préfères garder ton historique ? <a href="connexion.php">Connecte-toi</a>.
        </div>
    <?php endif; ?>
    <div class="title-wrap">
        <div class="vs-badge">
            <div class="vs-line"></div>
            <div class="vs-text">1 v 1<?= $is_friendly ? ' AMICAL' : '' ?></div>
            <div class="vs-line"></div>
        </div>
        <div class="title-sub"><?= $is_friendly ? 'Duel sans pression · L\'ELO ne bouge pas' : 'Duel en temps réel' ?></div>
    </div>

    <?php if (!$is_guest): ?>
    <div class="elo-display" style="width:100%;">
        <div>
            <div class="elo-label">Votre ELO</div>
            <div class="elo-value" id="my-elo"><?= (int)$elo ?></div>
        </div>
        <div class="elo-division" id="my-division">Division 3</div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title"><span>⚔️</span> Créer une room</div>
        <?php if (!$is_guest): ?>
        <div class="input-group">
            <div class="input-label">Votre pseudo</div>
            <input class="input-field" type="text" id="create-name" placeholder="Entrez votre pseudo" maxlength="20" value="<?= htmlspecialchars($username) ?>" readonly>
        </div>
        <?php else: ?>
        <input type="hidden" id="create-name" value="">
        <div class="guest-notice" style="margin: 0 0 0.8rem;">Tu seras nommé <strong style="color:#6db8ff;">Joueur 1</strong></div>
        <?php endif; ?>
        <button class="btn btn-primary" id="btn-create">CRÉER LA ROOM</button>
    </div>

    <div class="divider">
        <div class="divider-line"></div>
        <div class="divider-text">ou</div>
        <div class="divider-line"></div>
    </div>

    <div class="card">
        <div class="card-title"><span>🎯</span> Rejoindre une room</div>
        <?php if (!$is_guest): ?>
        <div class="input-group">
            <div class="input-label">Votre pseudo</div>
            <input class="input-field" type="text" id="join-name" placeholder="Entrez votre pseudo" maxlength="20" value="<?= htmlspecialchars($username) ?>" readonly>
        </div>
        <?php else: ?>
        <input type="hidden" id="join-name" value="">
        <div class="guest-notice" style="margin: 0 0 0.8rem;">Tu seras nommé <strong style="color:#6db8ff;">Joueur 2</strong></div>
        <?php endif; ?>
        <div class="input-group">
            <div class="input-label">Code de la room</div>
            <input class="input-field code-input" type="text" id="join-code" placeholder="A7K2" maxlength="4">
        </div>
        <div class="error-msg" id="error-msg">Room introuvable.</div>
        <button class="btn btn-primary" id="btn-join">REJOINDRE</button>
    </div>
</div>

<!-- ─── Écran lobby ─── -->
<div class="lobby-screen" id="lobby-screen">
    <div class="title-wrap">
        <div class="vs-badge">
            <div class="vs-line"></div>
            <div class="vs-text">LOBBY</div>
            <div class="vs-line"></div>
        </div>
    </div>

    <div class="lobby-code-wrap">
        <div class="lobby-code-label">Code de la room</div>
        <div class="lobby-code" id="lobby-code-display">—</div>
        <div class="copy-hint" id="copy-hint">📋 Copier le code</div>
    </div>

    <div class="players-wrap">
        <div class="player-slot filled" id="slot-1">
            <div class="player-avatar" id="avatar-1">?</div>
            <div class="player-name" id="name-1">—</div>
            <div class="player-elo" id="elo-1">— ELO</div>
        </div>
        <div class="player-slot" id="slot-2">
            <div class="player-avatar" id="avatar-2">?</div>
            <div class="player-name" id="name-2">En attente</div>
            <div class="waiting-dots">
                <div class="waiting-dot"></div>
                <div class="waiting-dot"></div>
                <div class="waiting-dot"></div>
            </div>
        </div>
        <div class="vs-sep">VS</div>
    </div>

    <div class="lobby-status" id="lobby-status">En attente du <span>2ème joueur</span>…</div>
    <button class="btn btn-primary" id="start-btn" disabled>LANCER LA PARTIE</button>
    <button class="btn btn-secondary" id="leave-btn">Quitter</button>
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
    // PlayerId stable basé sur user_id BDD (au lieu d'un UUID random)
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
} catch (e) {}
<?php else: ?>
// Mode invité : pas de QPC_USER, pas de player_id stable.
// Le lobby-1v1.js générera un UUID aléatoire au premier passage,
// et stockera le pseudo saisi dans localStorage côté JS.
window.QPC_USER = null;
try {
    // Force un nouvel UUID pour les guests (évite de réutiliser un ancien 'u123')
    if ((localStorage.getItem('qpc_player_id') || '').startsWith('u')) {
        localStorage.removeItem('qpc_player_id');
    }
    localStorage.removeItem('qpc_name');  // laisse l'utilisateur choisir
    localStorage.setItem('qpc_elo', '1200');
} catch (e) {}
<?php endif; ?>
</script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="qpc-config.js"></script>
<script src="lobby-1v1.js"></script>

</body>
</html>
