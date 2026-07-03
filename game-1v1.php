<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ── Mode amical (passé via &friendly=1 depuis lobby-1v1.php) ─
$is_friendly = isset($_GET['friendly']) && $_GET['friendly'] === '1';

// ── Auth conditionnelle : obligatoire SAUF en mode amical ──
if (!$is_friendly && !isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

// ── Defaults guest ─────────────────────────────────────────
$user_id     = null;
$username    = '';
$elo         = 1200;
$profile_pic = null;
$is_guest    = !isset($_SESSION['user_id']);

if (!$is_guest) {
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
    $profile_pic = $user['profile_pic'] ?? null;

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
<title>QPC — 1v1 Duel</title>

<!-- Anti-flash : applique le thème AVANT le rendu pour éviter le flash blanc/noir.
     Convention projet : pages de jeu sans toggle visible, mais respectent le choix
     fait depuis index/dashboard/etc. (clé localStorage 'qpc-theme'). -->
<script>
    try {
        var stored = localStorage.getItem('qpc-theme');
        if (stored === 'light') {
            document.documentElement.classList.add('light');
        }
        // dark = défaut, aucune classe à ajouter
    } catch (e) {}
</script>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cinzel+Decorative:wght@700;900&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="game-1v1.css">
<link rel="stylesheet" href="game-1v1-fx.css">
</head>
<body>

<!-- ── BACKGROUND LAYERS (CSS pur + SVG composités GPU) ── -->
<div class="bg-layer"></div>
<div class="bg-gems" id="bg-gems" aria-hidden="true"></div>
<div class="vignette"></div>

<!-- ── FX : confettis sur canvas unique (one-shot, pas continu) ── -->
<canvas class="fx-confetti-canvas" id="fx-confetti-canvas"></canvas>

<div class="game-wrap" id="game-wrap">

    <!-- ── TOPBAR ── -->
    <div class="topbar">
        <div class="logo">Q P C</div>

        <div class="topbar-center">
            <div class="round-ind">
                <span class="lbl">Manche</span>
                <span class="val" id="q-counter">1/10</span>
            </div>
            <div class="target-score" id="target-score">→ 1000 pts</div>
        </div>

        <div class="right-ctrls">
            <!-- Croix d'abandon retirée : on passe désormais par le bouton "Abandonner"
                 en bas + la modal personnalisée (#abandon-modal). -->
        </div>
    </div>

    <!-- ── SCORE BAR ── -->
    <div class="score-bar-wrap">
        <div class="score-bar-p1" id="score-bar-p1"></div>
        <div class="score-bar-sep"></div>
        <div class="score-bar-p2" id="score-bar-p2"></div>
    </div>

    <!-- ── ARENA ── -->
    <div class="arena">

        <!-- Joueur 1 (moi) -->
        <div class="panel p1" id="player1-info">
            <div class="hex-frame p1">
                <div class="hex-inner" id="p1-avatar">K</div>
            </div>
            <div class="player-name" id="p1-name">Joueur 1</div>
            <div class="player-elo-tag p1">ELO <span id="p1-elo">1200</span></div>
            <div class="player-score p1" id="p1-score">0</div>
            <div class="streak" id="p1-streak"></div>
        </div>

        <!-- Centre : gem SVG (CSS-animated, GPU-composited) + timer ring -->
        <div class="center">
            <div class="gem-wrap">
                <div class="gem-3d" aria-hidden="true">
                    <!-- Diamant doré plein (lent) -->
                    <svg class="gem-solid" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="gemGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#fcf6ba"/>
                                <stop offset="55%" stop-color="#d4af37"/>
                                <stop offset="100%" stop-color="#8a6e2f"/>
                            </linearGradient>
                        </defs>
                        <polygon points="50,8 92,50 50,92 8,50" fill="url(#gemGrad)" opacity="0.92"/>
                        <polygon points="50,8 65,50 50,92 35,50" fill="rgba(252,246,186,0.25)"/>
                    </svg>
                    <!-- Filaire doré clair par-dessus (tourne plus vite, sens inverse) -->
                    <svg class="gem-wire" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <g fill="none" stroke="currentColor" stroke-width="1.4" opacity="0.7" stroke-linejoin="round">
                            <polygon points="50,6 94,50 50,94 6,50"/>
                            <line x1="50" y1="6"  x2="50" y2="94"/>
                            <line x1="6"  y1="50" x2="94" y2="50"/>
                            <line x1="50" y1="6"  x2="35" y2="50"/>
                            <line x1="50" y1="6"  x2="65" y2="50"/>
                            <line x1="50" y1="94" x2="35" y2="50"/>
                            <line x1="50" y1="94" x2="65" y2="50"/>
                        </g>
                    </svg>
                </div>
                <svg class="timer-ring" viewBox="0 0 240 240">
                    <circle class="track" cx="120" cy="120" r="110"/>
                    <circle class="prog"  cx="120" cy="120" r="110" id="ring-progress"
                            stroke-dasharray="691.15" stroke-dashoffset="0"/>
                </svg>
                <div class="timer-number" id="timer-num">—</div>
            </div>
            <div class="vs-label">Versus</div>
        </div>

        <!-- Joueur 2 (adversaire) -->
        <div class="panel p2" id="player2-info">
            <div class="hex-frame p2">
                <div class="hex-inner" id="p2-avatar">O</div>
            </div>
            <div class="player-name" id="p2-name">Joueur 2</div>
            <div class="player-elo-tag p2">ELO <span id="p2-elo">1200</span></div>
            <div class="player-score p2" id="p2-score">0</div>
            <div class="streak" id="p2-streak"></div>
        </div>

    </div>

    <!-- ── MAIN ── -->
    <div class="game-content">

        <!-- Q Meta -->
        <div class="q-meta">
            <div class="q-category">
                <span id="cat-icon">🎲</span>
                <span id="cat-label">—</span>
            </div>
            <span class="diff-badge" id="diff-badge">—</span>
        </div>

        <!-- Legacy timer bar (caché en visuel mais conservé pour compat JS) -->
        <div class="timer-wrap legacy">
            <div class="timer-bar-bg">
                <div class="timer-bar-fill" id="timer-fill" style="width:0%"></div>
            </div>
        </div>

        <!-- Question -->
        <div class="question-card reading-phase" id="question-card">
            <p class="question-text" id="question-text">En attente de la partie…</p>
        </div>

        <!-- Options -->
        <div class="options-grid" id="options-grid">
            <button class="option-btn"><span class="option-letter">A</span><span>—</span></button>
            <button class="option-btn"><span class="option-letter">B</span><span>—</span></button>
            <button class="option-btn"><span class="option-letter">C</span><span>—</span></button>
            <button class="option-btn"><span class="option-letter">D</span><span>—</span></button>
        </div>

        <!-- Buzz -->
        <div class="buzz-zone">
            <button class="buzz-btn locked" id="buzz-btn">
                <span class="buzz-icon">🎯</span>
                <span class="buzz-label">Buzz</span>
            </button>
            <div class="buzz-status locked-msg" id="buzz-status">En attente…</div>
        </div>

    </div>

    <!-- ── BOTTOM BAR ── -->
    <div class="bottom-bar">
        <button class="abandon-btn-text" id="abandon-btn-text">Abandonner</button>
        <div class="connection-status">
            <div class="status-dot" id="status-dot"></div>
            <span id="status-text">Connecté</span>
        </div>
    </div>

</div>

<!-- ── COUNTDOWN OVERLAY ── -->
<div class="overlay" id="countdown-overlay">
    <div class="countdown-number" id="countdown-number">3</div>
    <div class="countdown-sub">La partie commence</div>
</div>

<!-- ── FEEDBACK BADGE ── -->
<div class="feedback-badge" id="feedback-badge">
    <div class="feedback-icon" id="fb-icon">✓</div>
    <div class="feedback-who"  id="fb-who">Joueur 1</div>
    <div class="feedback-text" id="fb-text">Bonne réponse !</div>
    <div class="feedback-pts"  id="fb-pts">+100 pts</div>
</div>

<!-- ── ABANDON CONFIRMATION MODAL ── -->
<div class="abandon-modal" id="abandon-modal" aria-hidden="true">
    <div class="abandon-modal-backdrop" id="abandon-modal-backdrop"></div>
    <div class="abandon-modal-card" role="dialog" aria-labelledby="abandon-modal-title">
        <div class="abandon-modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9"  x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <h2 class="abandon-modal-title" id="abandon-modal-title">Déclarer forfait ?</h2>
        <p class="abandon-modal-desc" id="abandon-modal-desc">
            Tu vas quitter le duel en cours.
        </p>
        <div class="slide-confirm" id="abandon-slide">
            <div class="slide-confirm-fill" id="abandon-slide-fill"></div>
            <div class="slide-confirm-track" id="abandon-slide-track">Glisser pour abandonner →</div>
            <div class="slide-confirm-handle" id="abandon-slide-handle" role="button" tabindex="0" aria-label="Glisser pour abandonner">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6l6 6-6 6"/></svg>
            </div>
        </div>
        <button class="abandon-modal-cancel" id="abandon-modal-cancel" type="button" style="width:100%;margin-top:0.6rem;">Rester dans la partie</button>
    </div>
</div>

<!-- ── END SCREEN (analytique) ── -->
<div class="end-screen" id="end-screen">

    <div class="eg-head eg-seq">
        <div class="eg-head-left">
            <div class="eg-verdict" id="eg-verdict">—</div>
            <div class="eg-sub" id="eg-sub">—</div>
        </div>
        <div class="eg-chip" id="eg-chip" style="display:none">—</div>
    </div>

    <div class="eg-grid">
        <div class="eg-panel eg-seq">
            <div class="eg-title">Comparaison</div>
            <div id="eg-cmp"></div>
        </div>
        <div class="eg-panel eg-seq">
            <div class="eg-title">Ta partie</div>
            <div class="eg-stats" id="eg-stats"></div>
        </div>
        <div class="eg-panel eg-full eg-seq" id="eg-film-panel">
            <div class="eg-title eg-film-title">
                <span>Le film du match</span>
                <span class="eg-legend">
                    <span class="eg-lg me"><i></i>Toi</span>
                    <span class="eg-lg op"><i></i>Adversaire</span>
                    <span class="eg-lg no"><i></i>Personne</span>
                </span>
            </div>
            <div class="eg-dots" id="eg-dots"></div>
        </div>
    </div>

    <div class="rematch-status" id="rematch-status"></div>

    <div class="end-btns eg-seq">
        <button class="end-btn-primary"   id="rematch-btn">↺ Revanche</button>
        <a class="end-btn-secondary" id="newduel-btn" href="lobby-1v1.php">Nouveau duel</a>
        <a class="end-btn-secondary" href="classement.php">🏆 Classement</a>
        <button class="end-btn-secondary" id="dashboard-btn">Dashboard</button>
    </div>
</div>

<!-- Scripts -->
<script>
// ── Flag amical ──
window.QPC_FRIENDLY = <?= $is_friendly ? 'true' : 'false' ?>;

<?php if (!$is_guest): ?>
window.QPC_USER = {
    id:          <?= (int) $user_id ?>,
    username:    <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>,
    elo:         <?= (int) $elo ?>,
    profile_pic: <?= json_encode($profile_pic) ?>
};
try {
    localStorage.setItem('qpc_name', window.QPC_USER.username);
    localStorage.setItem('qpc_elo',  String(window.QPC_USER.elo));
    localStorage.setItem('qpc_player_id', 'u' + window.QPC_USER.id);
    if (window.QPC_USER.profile_pic) {
        localStorage.setItem('qpc_pic', window.QPC_USER.profile_pic);
    }
} catch (e) {}
<?php else: ?>
window.QPC_USER = null;
<?php endif; ?>
</script>
<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script src="qpc-config.js"></script>
<script src="game-1v1-fx.js"></script>
<script src="game-1v1.js"></script>

</body>
</html>
