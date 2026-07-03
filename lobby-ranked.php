<?php
require_once __DIR__ . '/../csrf.php';
require __DIR__ . "/../db.php";

// ════════════════════════════════════════════════════════════
// LOBBY TOURNOI CLASSÉ — matchmaking à 4 (ELO en jeu)
// Comme le 1v1 classé : compte obligatoire, adversaires trouvés
// automatiquement selon l'ELO. L'amical passe par lobby.php (code).
// ════════════════════════════════════════════════════════════
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
<title>QPC — Tournoi classé</title>

<script>
    try { if (localStorage.getItem('qpc-theme') === 'light') document.documentElement.classList.add('light'); } catch (e) {}
</script>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cinzel+Decorative:wght@700;900&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="lobby-ranked.css">
</head>
<body>

<a href="../game.php" class="back-btn"><span>←</span><span>Retour</span></a>
<div class="logo-wrap">QPC</div>

<!-- ─── Connexion serveur ─── -->
<div class="connecting-wrap active" id="connecting">
    <div class="spinner"></div>
    <div class="connecting-text">Connexion au serveur…</div>
</div>

<!-- ═══ ÉCRAN 1 — PRÊT ═══ -->
<div class="stage" id="stage-ready">
    <div class="mm-head">
        <div class="mm-title">TOURNOI <span class="clash">CLASSÉ</span></div>
        <div class="mm-sub">4 joueurs · niveaux proches · ELO en jeu</div>
        <div class="mm-divider"></div>
    </div>

    <div class="pcard you">
        <div class="pcard-avatar" id="ready-avatar"></div>
        <div class="pcard-name" id="ready-name">—</div>
        <div class="pcard-elo" id="ready-elo">—<small> ELO</small></div>
        <div class="pcard-div" id="ready-div">—</div>
    </div>

    <div class="mm-note">
        Le matchmaking rassemble <b>4 joueurs</b> d'un niveau proche, puis lance le
        championnat (Manche 1 → 2 → 3). Ton <b>ELO bouge</b> selon ton classement final.
    </div>

    <button class="btn btn-primary" id="btn-search">🔍 LANCER LA RECHERCHE</button>
</div>

<!-- ═══ ÉCRAN 2 — RECHERCHE ═══ -->
<div class="stage" id="stage-search">
    <div class="mm-head">
        <div class="mm-title">RECHERCHE</div>
        <div class="mm-divider"></div>
    </div>

    <div class="radar">
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <div class="radar-ring"></div>
        <div class="radar-sweep"></div>
        <div class="radar-core">🏆</div>
    </div>

    <div class="search-status">Recherche de 4 joueurs<span class="dots"></span></div>

    <div class="slot-dots" id="slot-dots">
        <div class="slot-dot on"></div>
        <div class="slot-dot"></div>
        <div class="slot-dot"></div>
        <div class="slot-dot"></div>
    </div>

    <div class="search-meta">
        <div class="meta-block">
            <div class="meta-val" id="search-timer">0:00</div>
            <div class="meta-lbl">Temps</div>
        </div>
        <div class="meta-block">
            <div class="meta-val" id="search-elo"><?= (int)$elo ?></div>
            <div class="meta-lbl">Votre ELO</div>
        </div>
        <div class="meta-block">
            <div class="meta-val" id="search-queue">1/4</div>
            <div class="meta-lbl">En file</div>
        </div>
    </div>

    <button class="btn btn-secondary" id="btn-cancel">Annuler</button>
</div>

<!-- ═══ ÉCRAN 3 — TOURNOI FORMÉ ═══ -->
<div class="stage" id="stage-found">
    <div class="mm-head">
        <div class="mm-title">TOURNOI <span class="clash">FORMÉ</span></div>
        <div class="mm-divider"></div>
    </div>
    <div class="roster" id="roster"></div>
    <div class="found-status">Préparation du championnat…</div>
</div>

<!-- ═══ Countdown ═══ -->
<div class="cd-overlay" id="cd-overlay"><div class="cd-num" id="cd-num">3</div></div>

<!-- ─── Injection user → localStorage ─── -->
<script>
window.QPC_USER = {
    id:       <?= (int) $user_id ?>,
    username: <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>,
    elo:      <?= (int) $elo ?>,
    pic:      <?= json_encode($profile_pic, JSON_UNESCAPED_UNICODE) ?>
};
try {
    localStorage.setItem('qpc_name', window.QPC_USER.username);
    localStorage.setItem('qpc_elo',  String(window.QPC_USER.elo));
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
} catch (e) {}
</script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="../qpc-config.js"></script>
<script src="lobby-ranked.js"></script>

</body>
</html>
