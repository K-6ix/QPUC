<?php
session_start();
require __DIR__ . "/db.php";

// Si pas connecté, on continue quand même (l'utilisateur sera redirigé au lobby ou en login)
$user_id  = $_SESSION['user_id'] ?? null;
$username = null;
$elo      = 1200;

if ($user_id) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u) $username = $u['username'];

    $stmt2 = $conn->prepare("SELECT elo FROM player_stats WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $row = $stmt2->get_result()->fetch_assoc();
    if ($row && isset($row['elo'])) $elo = (int)$row['elo'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Choisis ton mode — Question Champion</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ════════════════════════════════════════════════════════════
   GAME.PHP - HUB DE SÉLECTION DE MODE
   Style cinematic noir/or avec cards 3D
════════════════════════════════════════════════════════════ */

:root {
    --gold-light:  #fcf6ba;
    --gold:        #d4af37;
    --gold-dim:    #8a7124;
    --gold-glow:   rgba(212, 175, 55, 0.4);
    --bg:          #050505;
    --bg2:         #0c0c0c;
    --bg-card:     #131313;
    --text:        #f5f5f5;
    --text-dim:    #888;
    --border:      rgba(212, 175, 55, 0.12);
    --metallic:    linear-gradient(135deg, #8a6e2f 0%, #d4af37 30%, #fcf6ba 50%, #d4af37 70%, #8a6e2f 100%);
    --ease:        cubic-bezier(0.4, 0, 0.2, 1);
}

* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; }

body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Raleway', sans-serif;
    overflow-x: hidden;
    min-height: 100vh;
}

/* ─── Background animé ─── */
.bg-grid {
    position: fixed;
    inset: 0;
    background-image:
        radial-gradient(ellipse at top, rgba(212, 175, 55, 0.08) 0%, transparent 60%),
        radial-gradient(ellipse at bottom, rgba(212, 175, 55, 0.04) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
}
.bg-particles {
    position: fixed;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
}
.particle {
    position: absolute;
    width: 3px;
    height: 3px;
    background: var(--gold);
    border-radius: 50%;
    opacity: 0;
    animation: float-up 8s linear infinite;
}
@keyframes float-up {
    0%   { transform: translateY(100vh) scale(0); opacity: 0; }
    10%  { opacity: 0.6; }
    90%  { opacity: 0.3; }
    100% { transform: translateY(-10vh) scale(1); opacity: 0; }
}

/* ─── Header (top bar) ─── */
.top-bar {
    position: fixed;
    top: 0; left: 0; right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.2rem 2rem;
    z-index: 50;
    backdrop-filter: blur(8px);
    background: rgba(5, 5, 5, 0.5);
    border-bottom: 1px solid var(--border);
}
.back-link {
    color: var(--text-dim);
    text-decoration: none;
    font-size: 0.9rem;
    letter-spacing: 0.05em;
    transition: color 200ms;
}
.back-link:hover { color: var(--gold); }

.brand-logo {
    font-family: 'Cinzel Decorative', serif;
    font-size: 1.3rem;
    color: var(--gold);
    letter-spacing: 0.3em;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: var(--text-dim);
    font-size: 0.85rem;
}
.user-chip strong { color: var(--gold); }

/* ─── Hero header ─── */
.hub-container {
    position: relative;
    z-index: 10;
    max-width: 1400px;
    margin: 0 auto;
    padding: 7rem 2rem 4rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3rem;
    min-height: 100vh;
}

.hub-header {
    text-align: center;
    opacity: 0;
    animation: fadeUp 0.8s var(--ease) 0.2s forwards;
}
.hub-tag {
    color: var(--text-dim);
    font-size: 0.75rem;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    margin-bottom: 0.8rem;
}
.hub-title {
    font-family: 'Cinzel Decorative', serif;
    font-size: clamp(2.5rem, 6vw, 4.5rem);
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.1;
    margin-bottom: 0.5rem;
    background-size: 200% 100%;
    animation: shimmer 4s linear infinite;
}
@keyframes shimmer {
    0%, 100% { background-position: 0% 50%; }
    50%      { background-position: 100% 50%; }
}
.hub-sub {
    color: var(--text-dim);
    font-size: 1.1rem;
    letter-spacing: 0.1em;
    max-width: 500px;
    margin: 0 auto;
    line-height: 1.5;
}
.hub-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin: 1rem auto;
}
.hub-divider::before,
.hub-divider::after {
    content: '';
    width: 80px;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
}
.hub-divider-icon {
    color: var(--gold);
    font-size: 0.8rem;
}

/* ─── Grille de modes (3 cards) ─── */
.modes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.8rem;
    width: 100%;
    max-width: 1300px;
    perspective: 1500px;
}

.mode-card {
    position: relative;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 18px;
    overflow: hidden;
    aspect-ratio: 3 / 4;
    cursor: pointer;
    transition: all 500ms var(--ease);
    text-decoration: none;
    color: inherit;
    transform-style: preserve-3d;
    opacity: 0;
    animation: fadeUp 0.8s var(--ease) forwards;
    display: flex;
    flex-direction: column;
}
.mode-card:nth-child(1) { animation-delay: 0.4s; }
.mode-card:nth-child(2) { animation-delay: 0.55s; }
.mode-card:nth-child(3) { animation-delay: 0.7s; }

.mode-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 40%, var(--gold-glow) 100%);
    opacity: 0;
    transition: opacity 500ms;
    pointer-events: none;
    z-index: 1;
}
.mode-card::after {
    content: '';
    position: absolute;
    inset: -1px;
    border-radius: 18px;
    padding: 1px;
    background: linear-gradient(135deg, var(--gold-dim), transparent 50%, var(--gold));
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 500ms;
    pointer-events: none;
    z-index: 2;
}
.mode-card:hover {
    transform: translateY(-8px) rotateX(2deg);
    box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.8),
                0 0 40px rgba(212, 175, 55, 0.15);
}
.mode-card:hover::before { opacity: 1; }
.mode-card:hover::after  { opacity: 1; }

/* ─── Visuel haut de carte ─── */
.mode-visual {
    position: relative;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: radial-gradient(ellipse at center, rgba(212, 175, 55, 0.06), transparent 70%);
}
.mode-icon {
    font-size: 5.5rem;
    filter: drop-shadow(0 0 25px rgba(212, 175, 55, 0.5));
    transition: transform 600ms var(--ease);
    z-index: 1;
}
.mode-card:hover .mode-icon {
    transform: scale(1.1) translateY(-5px);
}
.mode-ornament {
    position: absolute;
    font-family: 'Cinzel Decorative', serif;
    color: var(--gold-dim);
    opacity: 0.1;
    font-size: 8rem;
    font-weight: 700;
    pointer-events: none;
    z-index: 0;
    transition: opacity 500ms;
}
.mode-card:hover .mode-ornament {
    opacity: 0.18;
}

/* Effet "light scan" qui passe au hover */
.mode-visual::before {
    content: '';
    position: absolute;
    top: -50%; left: -100%;
    width: 50%; height: 200%;
    background: linear-gradient(90deg,
        transparent,
        rgba(252, 246, 186, 0.1),
        transparent);
    transform: rotate(20deg);
    transition: left 800ms var(--ease);
    z-index: 2;
}
.mode-card:hover .mode-visual::before {
    left: 150%;
}

/* ─── Bas de carte (info) ─── */
.mode-info {
    padding: 1.8rem 1.5rem 1.5rem;
    background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.4));
    border-top: 1px solid var(--border);
    position: relative;
    z-index: 3;
}
.mode-label {
    color: var(--gold);
    font-size: 0.7rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    margin-bottom: 0.4rem;
}
.mode-title {
    font-family: 'Cinzel', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.6rem;
    color: var(--text);
    letter-spacing: 0.02em;
}
.mode-description {
    color: var(--text-dim);
    font-size: 0.88rem;
    line-height: 1.5;
    margin-bottom: 1.2rem;
    min-height: 4em;
}
.mode-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px dashed rgba(212, 175, 55, 0.15);
}
.mode-meta-tag {
    color: var(--gold-dim);
    font-size: 0.75rem;
    letter-spacing: 0.1em;
}
.mode-arrow {
    color: var(--gold);
    font-size: 1.2rem;
    transition: transform 400ms var(--ease);
}
.mode-card:hover .mode-arrow {
    transform: translateX(6px);
}

/* ─── Variantes par mode ─── */
.mode-card.solo .mode-icon {
    color: #6db8ff;
    filter: drop-shadow(0 0 25px rgba(109, 184, 255, 0.4));
}
.mode-card.solo .mode-label { color: #6db8ff; }
.mode-card.solo .mode-arrow { color: #6db8ff; }

.mode-card.duel .mode-icon {
    color: var(--gold);
    filter: drop-shadow(0 0 25px rgba(212, 175, 55, 0.5));
}

.mode-card.champ {
    background: linear-gradient(135deg, var(--bg-card) 0%, rgba(212, 175, 55, 0.03) 100%);
}
.mode-card.champ .mode-icon {
    color: var(--gold-light);
    filter: drop-shadow(0 0 30px rgba(252, 246, 186, 0.6));
}
.mode-card.champ::after { opacity: 0.3; }
.mode-card.champ .mode-meta-tag {
    background: linear-gradient(90deg, var(--gold-dim), var(--gold), var(--gold-dim));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ─── Animations ─── */
@keyframes fadeUp {
    0%   { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* ─── Responsive ─── */
@media (max-width: 1100px) {
    .modes-grid { grid-template-columns: repeat(2, 1fr); }
    .mode-card:nth-child(3) { grid-column: span 2; max-width: 500px; margin: 0 auto; }
}
@media (max-width: 700px) {
    .modes-grid { grid-template-columns: 1fr; }
    .mode-card:nth-child(3) { grid-column: span 1; }
    .hub-container { padding: 6rem 1rem 3rem; }
    .top-bar { padding: 1rem; }
    .mode-icon { font-size: 4.5rem; }
    .mode-title { font-size: 1.3rem; }
}

</style>
</head>
<body>

<!-- Backgrounds décoratifs -->
<div class="bg-grid"></div>
<div class="bg-particles" id="particles"></div>

<!-- Top bar -->
<div class="top-bar">
    <a href="<?= $user_id ? 'dashboard.php' : 'index.php' ?>" class="back-link">← Retour</a>
    <div class="brand-logo">QPC</div>
    <div class="user-chip">
        <?php if ($user_id): ?>
            <span><?= htmlspecialchars($username) ?></span>
            ·
            <span>ELO <strong><?= (int)$elo ?></strong></span>
        <?php else: ?>
            <a href="connexion.php" class="back-link">Connexion</a>
        <?php endif; ?>
    </div>
</div>

<!-- Hub content -->
<div class="hub-container">

    <div class="hub-header">
        <p class="hub-tag">Choisis ton arène</p>
        <h1 class="hub-title">Modes de jeu</h1>
        <div class="hub-divider">
            <span class="hub-divider-icon">◆</span>
        </div>
        <p class="hub-sub">Trois modes, trois défis. Que tu cherches à t'entraîner, à affronter un rival ou à décrocher le grand titre — tout commence ici.</p>
    </div>

    <div class="modes-grid">

        <!-- ════ SOLO / TRAINING ════ -->
        <a href="training.php" class="mode-card solo">
            <div class="mode-visual">
                <span class="mode-ornament">I</span>
                <span class="mode-icon">🎯</span>
            </div>
            <div class="mode-info">
                <p class="mode-label">Mode 01 · Solo</p>
                <h3 class="mode-title">Entraînement</h3>
                <p class="mode-description">Joue à ton rythme, sans pression. Améliore tes connaissances, bats tes propres records et prépare-toi pour le multijoueur.</p>
                <div class="mode-meta">
                    <span class="mode-meta-tag">Sans risque ELO</span>
                    <span class="mode-arrow">→</span>
                </div>
            </div>
        </a>

        <!-- ════ 1v1 ════ -->
        <a href="<?= $user_id ? 'lobby-1v1.php' : 'connexion.php' ?>" class="mode-card duel">
            <div class="mode-visual">
                <span class="mode-ornament">II</span>
                <span class="mode-icon">⚔</span>
            </div>
            <div class="mode-info">
                <p class="mode-label">Mode 02 · Multijoueur</p>
                <h3 class="mode-title">Duel 1v1</h3>
                <p class="mode-description">Affronte un adversaire en tête-à-tête. Buzz partagé, paris stratégiques et ELO en jeu — seul le plus rapide et le plus malin l'emporte.</p>
                <div class="mode-meta">
                    <span class="mode-meta-tag">ELO · Buzz</span>
                    <span class="mode-arrow">→</span>
                </div>
            </div>
        </a>

        <!-- ════ CHAMPIONNAT ════ -->
        <a href="<?= $user_id ? 'championship/lobby.php' : 'connexion.php' ?>" class="mode-card champ">
            <div class="mode-visual">
                <span class="mode-ornament">III</span>
                <span class="mode-icon">👑</span>
            </div>
            <div class="mode-info">
                <p class="mode-label">Mode 03 · Élite</p>
                <h3 class="mode-title">Championnat</h3>
                <p class="mode-description">Le tournoi suprême. 4 joueurs, 3 manches éliminatoires. Vitesse, stratégie, sang-froid — décroche le titre et 50 ELO d'un coup.</p>
                <div class="mode-meta">
                    <span class="mode-meta-tag">4 joueurs · 3 manches</span>
                    <span class="mode-arrow">→</span>
                </div>
            </div>
        </a>

    </div>

</div>

<script>
// ─── Génération de particules dorées ─────────────────────
(function() {
    const container = document.getElementById('particles');
    const count = 18;
    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDelay = (Math.random() * 8) + 's';
        p.style.animationDuration = (6 + Math.random() * 6) + 's';
        const size = 1 + Math.random() * 3;
        p.style.width = size + 'px';
        p.style.height = size + 'px';
        container.appendChild(p);
    }
})();

// ─── Effet 3D tilt au survol des cards (subtle) ─────────
document.querySelectorAll('.mode-card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const cx = rect.width / 2;
        const cy = rect.height / 2;
        const rotX = ((y - cy) / cy) * -4;
        const rotY = ((x - cx) / cx) * 4;
        card.style.transform = `translateY(-8px) rotateX(${rotX}deg) rotateY(${rotY}deg)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
    });
});
</script>

</body>
</html>
