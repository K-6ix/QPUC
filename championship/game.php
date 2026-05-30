<?php
// ============================================================================
//  QPC - Championship Game (M1 + M2 + M3)
//  Inspiré de game-1v1.php : auth + injection QPC_USER
// ============================================================================
session_start();
require __DIR__ . "/../db.php";

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

$username = $user['username'];

// ELO
$elo = 1200;
$res = $conn->prepare("SELECT elo FROM player_stats WHERE user_id = ?");
$res->bind_param("i", $user_id);
$res->execute();
$row = $res->get_result()->fetch_assoc();
if ($row && isset($row['elo'])) {
    $elo = (int) $row['elo'];
}

// Code de la room depuis URL (passé par lobby.php après lancement de M1)
$room_code = isset($_GET['code']) ? preg_replace('/[^A-Z0-9]/i', '', $_GET['code']) : '';
$room_code = strtoupper(substr($room_code, 0, 10));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QPC — Championnat en cours</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="game.css">
</head>
<body>

<!-- ===================== ECRAN CHARGEMENT ===================== -->
<section id="screen-loading" class="screen active">
    <div class="brand">
        <div class="brand-line"></div>
        <h1 class="brand-title">Championnat</h1>
        <p class="brand-subtitle">Connexion à la partie…</p>
        <div class="brand-line"></div>
    </div>
    <div class="loading-spinner"></div>
    <p class="hint" id="loading-msg">Rejoint la room <strong><?= htmlspecialchars($room_code ?: '...') ?></strong></p>
</section>

<!-- ===================== ECRAN COUNTDOWN ===================== -->
<section id="screen-countdown" class="screen">
    <div class="countdown-content">
        <p class="countdown-label">La partie démarre dans</p>
        <div class="countdown-number" id="countdown-number">3</div>
    </div>
</section>

<!-- ===================== ECRAN MANCHE 1 ===================== -->
<section id="screen-m1" class="screen">
    <header class="m1-header">
        <div class="manche-badge">
            <span class="manche-label">Manche 1</span>
            <span class="manche-title">Le 9 points gagnants</span>
        </div>
        <div class="m1-progress">
            <span id="m1-q-counter">Q1 / 15</span>
            <span class="m1-target">Cible : <strong>9 points</strong></span>
        </div>
    </header>
    <div class="m1-players" id="m1-players"></div>
    <div class="m1-question-area">
        <div class="m1-question-meta">
            <span class="m1-category" id="m1-category">—</span>
            <div class="m1-timer-bar"><div class="m1-timer-fill" id="m1-timer-fill"></div></div>
            <span class="m1-timer-text" id="m1-timer-text">15</span>
        </div>
        <h2 class="m1-question-text" id="m1-question-text">Question…</h2>
        <div class="m1-options" id="m1-options"></div>
        <p class="m1-status" id="m1-status"></p>
    </div>
</section>

<!-- ===================== ECRAN BARRAGE MORT SUBITE M1 ===================== -->
<section id="screen-tiebreak" class="screen">
    <header class="m1-header">
        <div class="manche-badge tiebreak">
            <span class="manche-label">⚡ Barrage</span>
            <span class="manche-title">Mort subite</span>
        </div>
        <div class="m1-progress"><span id="tb-round">Question 1</span></div>
    </header>
    <p class="tiebreak-info" id="tiebreak-info">Ex aequo. Le premier à rater est éliminé.</p>
    <div class="m1-question-area">
        <div class="m1-question-meta">
            <span class="m1-category" id="tb-category">—</span>
            <div class="m1-timer-bar"><div class="m1-timer-fill" id="tb-timer-fill"></div></div>
            <span class="m1-timer-text" id="tb-timer-text">12</span>
        </div>
        <h2 class="m1-question-text" id="tb-question-text">Question…</h2>
        <div class="m1-options" id="tb-options"></div>
        <p class="m1-status" id="tb-status"></p>
    </div>
</section>

<!-- ===================== ECRAN FIN M1 ===================== -->
<section id="screen-m1-end" class="screen">
    <div class="m1-end-content">
        <h2 class="m1-end-title">Manche 1 terminée</h2>
        <div class="m1-end-ranking" id="m1-end-ranking"></div>
        <p class="m1-end-message" id="m1-end-message"></p>
        <p class="hint" id="m1-end-hint">Manche 2 dans quelques secondes…</p>
    </div>
</section>

<!-- ===================== ECRAN FIN M2 ===================== -->
<section id="screen-m2-simulated" class="screen">
    <div class="m1-end-content">
        <h2 class="m1-end-title">Manche 2 terminée</h2>
        <div id="m2-finalists" class="m1-end-ranking"></div>
        <p class="hint">La Manche 3 démarre dans quelques secondes…</p>
    </div>
</section>

<!-- ===================== ECRAN M3 CATEGORIES (réutilisé pour M2 aussi) ===================== -->
<section id="screen-m3-categories" class="screen">
    <header class="m1-header">
        <div class="manche-badge m3-badge">
            <span class="manche-label">Manche 3 · Phase 1</span>
            <span class="manche-title">Choix des catégories</span>
        </div>
        <div class="m1-progress">
            <span id="m3-cat-timer-text">15</span>
        </div>
    </header>
    <div class="m3-cat-area">
        <p class="m3-instruction" id="m3-cat-instruction">Choisis 4 catégories parmi les 8.</p>
        <div class="m3-categories-grid" id="m3-categories-grid"></div>
        <div class="m3-cat-footer">
            <span id="m3-cat-counter">0 / 4 sélectionnées</span>
            <button id="btn-m3-validate-cats" class="btn btn-primary" disabled>Valider</button>
        </div>
        <p class="m1-status" id="m3-cat-status"></p>
    </div>
</section>

<!-- ===================== ECRAN M3 PARI (réutilisé pour M2 aussi) ===================== -->
<section id="screen-m3-bet" class="screen">
    <header class="m1-header">
        <div class="manche-badge m3-badge">
            <span class="manche-label">Manche 3 · Phase 2</span>
            <span class="manche-title">Pari secret</span>
        </div>
        <div class="m1-progress">
            <span id="m3-bet-timer-text">10</span>
        </div>
    </header>
    <div class="m3-bet-area">
        <p class="m3-instruction">Choisis sur quelle question miser, et combien.</p>
        <div class="m3-pool-display" id="m3-pool-display"></div>
        <div class="m3-bet-config">
            <div class="m3-bet-section">
                <label class="m3-bet-label">Quelle question ?</label>
                <div class="m3-bet-options" id="m3-bet-questions">
                    <button class="m3-bet-chip" data-q="1">Q1</button>
                    <button class="m3-bet-chip" data-q="2">Q2</button>
                    <button class="m3-bet-chip" data-q="3">Q3</button>
                    <button class="m3-bet-chip" data-q="4">Q4</button>
                    <button class="m3-bet-chip" data-q="5">Q5</button>
                </div>
            </div>
            <div class="m3-bet-section">
                <label class="m3-bet-label">Combien ?</label>
                <div class="m3-bet-options" id="m3-bet-amounts">
                    <button class="m3-bet-chip" data-amt="1">1 pt</button>
                    <button class="m3-bet-chip" data-amt="2">2 pts</button>
                    <button class="m3-bet-chip" data-amt="3">3 pts</button>
                </div>
            </div>
        </div>
        <div class="m3-cat-footer">
            <button id="btn-m3-validate-bet" class="btn btn-primary" disabled>Verrouiller ma mise</button>
        </div>
        <p class="m1-status" id="m3-bet-status"></p>
    </div>
</section>

<!-- ===================== ECRAN M3 DUEL (réutilisé pour M2 aussi) ===================== -->
<section id="screen-m3-duel" class="screen">
    <header class="m1-header">
        <div class="manche-badge m3-badge">
            <span class="manche-label">Manche 3 · Duel</span>
            <span class="manche-title">Face-à-face</span>
        </div>
        <div class="m1-progress">
            <span id="m3-q-counter">Q1 / 7</span>
            <span class="m1-target">Cible : <strong>8 pts</strong></span>
        </div>
    </header>
    <div class="m3-duel-players" id="m3-duel-players"></div>
    <div class="m3-bet-banner" id="m3-bet-banner" style="display:none;">
        <span class="m3-bet-banner-icon">💰</span>
        <span id="m3-bet-banner-text"></span>
    </div>
    <div class="m1-question-area">
        <div class="m1-question-meta">
            <span class="m1-category" id="m3-category">—</span>
            <div class="m1-timer-bar"><div class="m1-timer-fill" id="m3-timer-fill"></div></div>
            <span class="m1-timer-text" id="m3-timer-text">12</span>
        </div>
        <h2 class="m1-question-text" id="m3-question-text">Question…</h2>
        <div class="m3-buzz-zone">
            <button id="btn-m3-buzz" class="m3-buzz-btn">
                <span class="buzz-label">BUZZ</span>
                <span class="buzz-sub" id="m3-buzz-sub">Appuie pour répondre</span>
            </button>
        </div>
        <div class="m1-options m3-options" id="m3-options"></div>
        <p class="m1-status" id="m3-status"></p>
    </div>
</section>

<!-- ===================== ECRAN MORT SUBITE M3 ===================== -->
<section id="screen-m3-sudden" class="screen">
    <header class="m1-header">
        <div class="manche-badge tiebreak">
            <span class="manche-label">⚡ Mort subite</span>
            <span class="manche-title">Le titre se joue maintenant</span>
        </div>
        <div class="m1-progress"><span id="m3-sd-round">Question 1</span></div>
    </header>
    <div class="m3-duel-players" id="m3-sd-players"></div>
    <div class="m1-question-area">
        <div class="m1-question-meta">
            <span class="m1-category" id="m3-sd-category">—</span>
            <div class="m1-timer-bar"><div class="m1-timer-fill" id="m3-sd-timer-fill"></div></div>
            <span class="m1-timer-text" id="m3-sd-timer-text">12</span>
        </div>
        <h2 class="m1-question-text" id="m3-sd-question-text">Question…</h2>
        <div class="m3-buzz-zone">
            <button id="btn-m3-sd-buzz" class="m3-buzz-btn">
                <span class="buzz-label">BUZZ</span>
                <span class="buzz-sub">Appuie pour répondre</span>
            </button>
        </div>
        <div class="m1-options m3-options" id="m3-sd-options"></div>
        <p class="m1-status" id="m3-sd-status"></p>
    </div>
</section>

<!-- ===================== ECRAN FINAL ===================== -->
<section id="screen-final" class="screen">
    <div class="final-content">
        <div class="final-trophy">🏆</div>
        <h2 class="final-title">Champion</h2>
        <div class="final-winner-name" id="final-winner-name">—</div>
        <div class="final-scores" id="final-scores"></div>
        <p class="hint" id="final-message">Partie terminée. Bien joué !</p>
        <div class="final-actions">
            <button id="btn-back-lobby" class="btn btn-secondary">Nouvelle partie</button>
            <button id="btn-back-dashboard" class="btn btn-primary">Dashboard</button>
        </div>
    </div>
</section>

<!-- ===================== BANDEAU SPECTATEUR ===================== -->
<div id="spectator-banner" class="spectator-banner" style="display:none;">
    👁️ Mode spectateur
</div>

<!-- Scripts -->
<script>
window.QPC_USER = {
    id:       <?= (int) $user_id ?>,
    username: <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>,
    elo:      <?= (int) $elo ?>
};
window.QPC_ROOM_CODE = <?= json_encode($room_code, JSON_UNESCAPED_UNICODE) ?>;
try {
    localStorage.setItem('qpc_name', window.QPC_USER.username);
    localStorage.setItem('qpc_elo',  String(window.QPC_USER.elo));
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
} catch (e) {}
</script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="game.js"></script>

</body>
</html>
