<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ════════════════════════════════════════════════════════════
// LOBBY CLASSÉ — matchmaking anonyme (ELO en jeu)
// Contrairement au salon amical (lobby-1v1.php), le classé EXIGE
// un compte : l'ELO est sauvegardé et le classement en dépend.
// ════════════════════════════════════════════════════════════
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

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

// ELO depuis player_stats (défaut 1200 si pas encore de stats)
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
<title>QPC — Match classé</title>

<!-- Anti-flash thème (convention projet : pages de jeu sans toggle visible) -->
<script>
    try { if (localStorage.getItem('qpc-theme') === 'light') document.documentElement.classList.add('light'); } catch (e) {}
</script>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cinzel+Decorative:wght@700;900&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="lobby-ranked.css">
</head>
<body>

<a href="game.php" class="back-btn"><span>←</span><span>Retour</span></a>
<div class="logo-wrap">QPC</div>

<!-- ─── Connexion serveur ─── -->
<div class="connecting-wrap active" id="connecting">
    <div class="spinner"></div>
    <div class="connecting-text">Connexion au serveur…</div>
</div>

<!-- ═══ ÉCRAN 1 — PRÊT ═══ -->
<div class="stage" id="stage-ready">
    <div class="mm-head">
        <div class="mm-title">MATCH <span class="clash">CLASSÉ</span></div>
        <div class="mm-sub">Adversaire au niveau · ELO en jeu</div>
        <div class="mm-divider"></div>
    </div>

    <div class="ready-card-wrap">
        <div class="pcard gold-edge">
            <div class="pcard-avatar" id="ready-avatar"></div>
            <div class="pcard-name" id="ready-name">—</div>
            <div class="pcard-elo" id="ready-elo">—<small> ELO</small></div>
            <div class="pcard-div" id="ready-div">—</div>
        </div>
    </div>

    <div class="mm-note">
        Le matchmaking te place face à un joueur d'un <b>niveau proche</b>.
        Victoire ou défaite, ton <b>ELO bouge</b> et ton classement avec.
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
        <div class="radar-core">⚔️</div>
    </div>

    <div class="search-status">Recherche d'un adversaire<span class="dots"></span></div>

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
            <div class="meta-val" id="search-queue">1</div>
            <div class="meta-lbl">En file</div>
        </div>
    </div>

    <button class="btn btn-secondary" id="btn-cancel">Annuler</button>
</div>

<!-- ═══ ÉCRAN 3 — ADVERSAIRE TROUVÉ ═══ -->
<div class="stage" id="stage-found">
    <div class="mm-head">
        <div class="mm-title">ADVERSAIRE <span class="clash">TROUVÉ</span></div>
        <div class="mm-divider"></div>
    </div>

    <div class="versus">
        <div class="pcard you gold-edge">
            <div class="pcard-avatar" id="found-you-avatar"></div>
            <div class="pcard-name" id="found-you-name">—</div>
            <div class="pcard-elo" id="found-you-elo">—<small> ELO</small></div>
            <div class="pcard-div" id="found-you-div">—</div>
        </div>

        <div class="vs-clash">VS</div>

        <div class="pcard opp blue-edge">
            <div class="pcard-avatar" id="found-opp-avatar"></div>
            <div class="pcard-name" id="found-opp-name">—</div>
            <div class="pcard-elo" id="found-opp-elo">—<small> ELO</small></div>
            <div class="pcard-div" id="found-opp-div">—</div>
        </div>
    </div>

    <div class="found-status">Préparation du duel…</div>
</div>

<!-- ─── Injection user → localStorage (identité stable côté serveur) ─── -->
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
    // PlayerId stable basé sur user_id BDD (= 'u' + id) — jamais un UUID guest.
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
} catch (e) {}
</script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="qpc-config.js"></script>
<script src="lobby-ranked.js"></script>

</body>
</html>
