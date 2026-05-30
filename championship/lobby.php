<?php
// ============================================================================
//  QPC - Championship Lobby (4 joueurs)
//  Inspiré de lobby-1v1.php : auth PHP + injection user + lobby HTML
// ============================================================================
session_start();
require __DIR__ . "/../db.php";   // remonte d'un niveau (championship/ -> racine)

// ── Auth ────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: ../connexion.php");
    exit;
}

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

// ELO depuis player_stats
$elo = 1200;
$res = $conn->prepare("SELECT elo FROM player_stats WHERE user_id = ?");
$res->bind_param("i", $user_id);
$res->execute();
$row = $res->get_result()->fetch_assoc();
if ($row && isset($row['elo'])) {
    $elo = (int) $row['elo'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QPC — Championnat</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="lobby.css">
</head>
<body>

<a href="../dashboard.php" class="back-btn"><span>←</span><span>Retour</span></a>
<div class="logo-wrap">QPC</div>

<!-- ─── Écran "connexion au serveur" ─── -->
<div class="connecting-wrap" id="connecting">
    <div class="spinner"></div>
    <div class="connecting-text">Connexion au serveur…</div>
</div>

<!-- ─── Écran principal (créer / rejoindre) ─── -->
<div class="container" id="main-screen" style="display:none;">
    <div class="title-wrap">
        <div class="vs-badge">
            <div class="vs-line"></div>
            <div class="vs-text">CHAMPIONNAT</div>
            <div class="vs-line"></div>
        </div>
        <div class="title-sub">4 joueurs · 3 manches · Le grand titre</div>
    </div>

    <div class="elo-display">
        <div>
            <div class="elo-label">Votre ELO</div>
            <div class="elo-value" id="my-elo"><?= (int) $elo ?></div>
        </div>
        <div class="elo-division">Joueur QPC</div>
    </div>

    <div class="card">
        <div class="card-title"><span>🏆</span> Créer un championnat</div>
        <div class="input-group">
            <div class="input-label">Votre pseudo</div>
            <input class="input-field" type="text" id="create-name" value="<?= htmlspecialchars($username) ?>" readonly>
        </div>
        <button class="btn btn-primary" id="btn-create">CRÉER LA PARTIE</button>
    </div>

    <div class="divider">
        <div class="divider-line"></div>
        <div class="divider-text">ou</div>
        <div class="divider-line"></div>
    </div>

    <div class="card">
        <div class="card-title"><span>🎯</span> Rejoindre un championnat</div>
        <div class="input-group">
            <div class="input-label">Votre pseudo</div>
            <input class="input-field" type="text" id="join-name" value="<?= htmlspecialchars($username) ?>" readonly>
        </div>
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
window.QPC_USER = {
    id:       <?= (int) $user_id ?>,
    username: <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>,
    elo:      <?= (int) $elo ?>
};
// Sync vers localStorage pour cohérence avec le système 1v1
try {
    localStorage.setItem('qpc_name', window.QPC_USER.username);
    localStorage.setItem('qpc_elo',  String(window.QPC_USER.elo));
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
} catch (e) {}
</script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="lobby.js"></script>

</body>
</html>
