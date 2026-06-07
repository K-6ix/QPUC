<?php
// ============================================================================
//  QPC - Hub de sélection de mode (game.php) - V2 refonte
//  Hiérarchie 2 niveaux :
//    - 3 catégories principales : Entraînement / Classé / Entre amis
//    - Au clic sur Classé ou Entre amis : déploiement de 2 sous-modes
//      (Tournoi 4 joueurs / 1V1)
//  Auth gate sur Classé uniquement (Entre amis = sans compte requis,
//  l'ELO ne bouge pas → on s'en fiche de qui joue).
// ============================================================================
session_start();
require "db.php";

// Si pas connecté on continue quand même (modal s'affichera au besoin)
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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ════════════════════════════════════════════════════════════
   GAME.PHP V2 - HUB 2 NIVEAUX
   Catégories en haut, sous-modes en bas au clic
════════════════════════════════════════════════════════════ */

/* ── DARK MODE (défaut) ─────────────────────────────────── */
:root {
    --gold-light:  #fcf6ba;
    --gold:        #d4af37;
    --gold-dim:    #8a7124;
    --gold-glow:   rgba(212, 175, 55, 0.4);
    --gold-text:   var(--gold);

    --bg:          #050505;
    --bg2:         #0c0c0c;
    --bg-card:     #131313;
    --topbar-bg:   rgba(5, 5, 5, 0.5);
    --info-grad:   linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.4));

    --text:        #f5f5f5;
    --text-dim:    #888;

    --border:      rgba(212, 175, 55, 0.12);
    --border-strong: rgba(212, 175, 55, 0.35);

    --metallic:    linear-gradient(135deg, #8a6e2f 0%, #d4af37 30%, #fcf6ba 50%, #d4af37 70%, #8a6e2f 100%);
    --ease:        cubic-bezier(0.4, 0, 0.2, 1);

    --on-gold:     #0a0a0a;
    --card-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.8), 0 0 40px rgba(212, 175, 55, 0.15);
}

/* ── LIGHT MODE (override) ──────────────────────────────── */
html.light {
    --bg:          #ffffff;
    --bg2:         #f7f7f5;
    --bg-card:     #ffffff;
    --topbar-bg:   rgba(255, 255, 255, 0.78);
    --info-grad:   linear-gradient(180deg, transparent, rgba(212, 175, 55, 0.06));

    --text:        #1a1a1a;
    --text-dim:    rgba(10, 10, 10, 0.55);

    --border:      rgba(138, 110, 47, 0.22);
    --border-strong: rgba(138, 110, 47, 0.55);

    --gold-text:   #8a6e2f;

    --card-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.15), 0 0 40px rgba(212, 175, 55, 0.2);
}

.theme-transitioning,
.theme-transitioning * {
    transition: background-color 0.25s ease,
                border-color 0.25s ease,
                color 0.25s ease,
                fill 0.25s ease,
                stroke 0.25s ease !important;
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
html.light .bg-grid {
    background-image:
        radial-gradient(ellipse at top, rgba(212, 175, 55, 0.12) 0%, transparent 60%),
        radial-gradient(ellipse at bottom, rgba(212, 175, 55, 0.08) 0%, transparent 70%);
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
    -webkit-backdrop-filter: blur(8px);
    background: var(--topbar-bg);
    border-bottom: 1px solid var(--border);
}
.back-link {
    color: var(--text-dim);
    text-decoration: none;
    font-size: 0.9rem;
    letter-spacing: 0.05em;
    transition: color 200ms;
}
.back-link:hover { color: var(--gold-text); }

.brand-logo {
    font-family: 'Cinzel Decorative', serif;
    font-size: 1.3rem;
    color: var(--gold-text);
    letter-spacing: 0.3em;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: var(--text-dim);
    font-size: 0.85rem;
}
.user-chip strong { color: var(--gold-text); }

/* ─── Theme toggle ─── */
.theme-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px; height: 36px;
    border-radius: 50%;
    background: transparent;
    border: 1px solid var(--border-strong);
    color: var(--text);
    cursor: pointer;
    transition: border-color .2s, background .2s, color .2s, transform .15s;
    margin-left: 1rem;
    flex-shrink: 0;
}
.theme-toggle:hover {
    border-color: var(--gold);
    color: var(--gold-text);
    background: rgba(212, 175, 55, 0.08);
}
.theme-toggle:active { transform: scale(0.95); }
.theme-toggle svg { width: 15px; height: 15px; }
.theme-toggle .theme-moon { display: none; }
.theme-toggle .theme-sun  { display: block; }
html.light .theme-toggle .theme-moon { display: block; }
html.light .theme-toggle .theme-sun  { display: none; }

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
    max-width: 560px;
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
    color: var(--gold-text);
    font-size: 0.8rem;
}

/* ─── Grille de catégories (3 cards principales) ─── */
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
    transition: transform 500ms var(--ease), box-shadow 500ms var(--ease), border-color 500ms var(--ease), opacity 400ms var(--ease);
    text-decoration: none;
    color: inherit;
    transform-style: preserve-3d;
    opacity: 0;
    animation: fadeUp 0.8s var(--ease) forwards;
    display: flex;
    flex-direction: column;
    /* Reset bouton (car certaines cards sont des <button>) */
    font-family: inherit;
    font-size: inherit;
    text-align: left;
    width: 100%;
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
    box-shadow: var(--card-shadow);
}
.mode-card:hover::before { opacity: 1; }
.mode-card:hover::after  { opacity: 1; }

/* ─── NOUVEAU : état "active" (catégorie sélectionnée) ─── */
.mode-card.active {
    border-color: var(--gold);
    box-shadow: 0 0 50px rgba(212, 175, 55, 0.35);
}
.mode-card.active::after { opacity: 1; }
.mode-card.active::before { opacity: 0.7; }

/* ─── NOUVEAU : état "faded" (autres catégories quand une est active) ─── */
.mode-card.faded {
    opacity: 0.4;
    transform: scale(0.97);
}
.mode-card.faded:hover {
    opacity: 0.7;
    transform: scale(0.98);
}

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
html.light .mode-visual {
    background: radial-gradient(ellipse at center, rgba(212, 175, 55, 0.1), transparent 70%);
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
html.light .mode-ornament { opacity: 0.18; }
.mode-card:hover .mode-ornament { opacity: 0.28; }

.mode-visual::before {
    content: '';
    position: absolute;
    top: -50%; left: -100%;
    width: 50%; height: 200%;
    background: linear-gradient(90deg, transparent, rgba(252, 246, 186, 0.1), transparent);
    transform: rotate(20deg);
    transition: left 800ms var(--ease);
    z-index: 2;
}
.mode-card:hover .mode-visual::before { left: 150%; }

/* ─── Bas de carte (info) ─── */
.mode-info {
    padding: 1.8rem 1.5rem 1.5rem;
    background: var(--info-grad);
    border-top: 1px solid var(--border);
    position: relative;
    z-index: 3;
}
.mode-label {
    color: var(--gold-text);
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
html.light .mode-meta { border-top-color: rgba(138, 110, 47, 0.22); }

.mode-meta-tag {
    color: var(--gold-dim);
    font-size: 0.75rem;
    letter-spacing: 0.1em;
}
.mode-arrow {
    color: var(--gold-text);
    font-size: 1.2rem;
    transition: transform 400ms var(--ease);
}
.mode-card:hover .mode-arrow { transform: translateX(6px); }

/* Quand une carte est "active", la flèche se transforme en chevron bas */
.mode-card.active .mode-arrow { transform: rotate(90deg); }

/* ─── Variantes par catégorie ─── */
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
html.light .mode-card.champ {
    background: linear-gradient(135deg, var(--bg-card) 0%, rgba(212, 175, 55, 0.08) 100%);
}
.mode-card.champ .mode-icon {
    color: var(--gold-light);
    filter: drop-shadow(0 0 30px rgba(252, 246, 186, 0.6));
}
html.light .mode-card.champ .mode-icon {
    color: var(--gold);
    filter: drop-shadow(0 0 30px rgba(212, 175, 55, 0.6));
}
.mode-card.champ::after { opacity: 0.3; }
.mode-card.champ .mode-meta-tag {
    background: linear-gradient(90deg, var(--gold-dim), var(--gold), var(--gold-dim));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ═══════════════════════════════════════════════════════════
   NOUVEAU : Section sous-modes (drill-down)
   Cachée par défaut, se déploie avec .submenu.open
═══════════════════════════════════════════════════════════ */
.submenu {
    width: 100%;
    max-width: 1100px;
    opacity: 0;
    max-height: 0;
    overflow: hidden;
    transition: opacity 500ms var(--ease), max-height 600ms var(--ease);
    pointer-events: none;
}
.submenu.open {
    opacity: 1;
    max-height: 600px;
    pointer-events: auto;
}

.submenu-header {
    text-align: center;
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.6rem;
}
.submenu-tag {
    color: var(--gold-text);
    font-size: 0.7rem;
    letter-spacing: 0.4em;
    text-transform: uppercase;
}
.submenu-title {
    font-family: 'Cinzel', serif;
    font-size: 1.5rem;
    color: var(--text);
    letter-spacing: 0.05em;
}
.submenu-divider {
    width: 60px;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    margin-top: 0.4rem;
}

.submodes-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.4rem;
    width: 100%;
}

.submode-card {
    position: relative;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.6rem 1.4rem;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 1.2rem;
    transition: transform 400ms var(--ease), box-shadow 400ms var(--ease), border-color 400ms var(--ease);
    overflow: hidden;
    opacity: 0;
    transform: translateY(20px);
}
.submenu.open .submode-card { animation: fadeUp 0.6s var(--ease) forwards; }
.submenu.open .submode-card:nth-child(1) { animation-delay: 0.15s; }
.submenu.open .submode-card:nth-child(2) { animation-delay: 0.25s; }

.submode-card::after {
    content: '';
    position: absolute;
    inset: -1px;
    border-radius: 14px;
    padding: 1px;
    background: linear-gradient(135deg, var(--gold-dim), transparent 50%, var(--gold));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 400ms;
    pointer-events: none;
}
.submode-card:hover {
    transform: translateX(4px);
    box-shadow: 0 15px 40px -15px rgba(0, 0, 0, 0.6), 0 0 25px var(--gold-glow);
    border-color: var(--border-strong);
}
.submode-card:hover::after { opacity: 1; }

.submode-icon {
    font-size: 2.8rem;
    flex-shrink: 0;
    filter: drop-shadow(0 0 15px rgba(212, 175, 55, 0.4));
    transition: transform 400ms var(--ease);
}
.submode-card:hover .submode-icon { transform: scale(1.1) rotate(-5deg); }

.submode-info { flex: 1; min-width: 0; }
.submode-title {
    font-family: 'Cinzel', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.3rem;
}
.submode-desc {
    color: var(--text-dim);
    font-size: 0.82rem;
    line-height: 1.5;
}
.submode-arrow {
    color: var(--gold-text);
    font-size: 1.2rem;
    transition: transform 400ms var(--ease);
    flex-shrink: 0;
}
.submode-card:hover .submode-arrow { transform: translateX(4px); }

/* Badge ELO sur les sous-modes classés */
.submode-badge {
    display: inline-block;
    margin-top: 0.4rem;
    padding: 0.18rem 0.6rem;
    border-radius: 6px;
    font-size: 0.65rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    font-weight: 600;
}
.submode-badge.ranked {
    background: rgba(212, 175, 55, 0.15);
    color: var(--gold-text);
    border: 1px solid var(--border-strong);
}
.submode-badge.casual {
    background: rgba(109, 184, 255, 0.12);
    color: #6db8ff;
    border: 1px solid rgba(109, 184, 255, 0.3);
}

/* ═══════════════════════════════════════════════════════════
   NOUVEAU : Modal auth (verrou classé)
═══════════════════════════════════════════════════════════ */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 200;
    opacity: 0;
    pointer-events: none;
    transition: opacity 350ms var(--ease);
    padding: 1rem;
}
.modal-overlay.show {
    opacity: 1;
    pointer-events: auto;
}

.modal-box {
    background: var(--bg-card);
    border: 1px solid var(--gold);
    border-radius: 20px;
    padding: 2.5rem 2rem;
    max-width: 440px;
    width: 100%;
    text-align: center;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6), 0 0 60px rgba(212, 175, 55, 0.25);
    transform: scale(0.92);
    transition: transform 350ms var(--ease);
}
.modal-overlay.show .modal-box { transform: scale(1); }

.modal-icon {
    font-size: 3rem;
    margin-bottom: 0.8rem;
    filter: drop-shadow(0 0 20px rgba(212, 175, 55, 0.5));
}
.modal-title {
    font-family: 'Cinzel', serif;
    font-size: 1.4rem;
    color: var(--gold-text);
    margin-bottom: 0.6rem;
    letter-spacing: 0.05em;
}
.modal-text {
    color: var(--text-dim);
    font-size: 0.92rem;
    line-height: 1.55;
    margin-bottom: 1.8rem;
}
.modal-actions {
    display: flex;
    gap: 0.8rem;
    justify-content: center;
    flex-wrap: wrap;
}
.btn-modal {
    padding: 0.75rem 1.6rem;
    border-radius: 10px;
    font-family: 'Raleway', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
    text-decoration: none;
    transition: all 200ms var(--ease);
    border: none;
    display: inline-block;
}
.btn-modal.primary {
    background: var(--metallic);
    background-size: 200% 100%;
    color: var(--on-gold);
    animation: shimmer 4s linear infinite;
}
.btn-modal.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
}
.btn-modal.secondary {
    background: transparent;
    color: var(--text-dim);
    border: 1px solid var(--border-strong);
}
.btn-modal.secondary:hover {
    color: var(--text);
    border-color: var(--gold);
}

/* ─── Animations ─── */
@keyframes fadeUp {
    0%   { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* ════════════════════════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════════════════════════ */
@media (max-width: 1100px) {
    .modes-grid { grid-template-columns: repeat(2, 1fr); gap: 1.4rem; }
    .mode-card:nth-child(3) { grid-column: span 2; max-width: 500px; margin: 0 auto; }
    .top-bar { padding: 1rem 1.5rem; }
}

@media (max-width: 800px) {
    .hub-container { padding: 6rem 1.5rem 3rem; gap: 2rem; }
    .top-bar { padding: 1rem 1.25rem; }
    .brand-logo { font-size: 1.15rem; letter-spacing: 0.25em; }
    .user-chip { font-size: 0.8rem; gap: 0.4rem; }
    .mode-icon { font-size: 5rem; }
    .mode-title { font-size: 1.4rem; }
}

@media (max-width: 700px) {
    .modes-grid { grid-template-columns: 1fr; gap: 1.2rem; }
    .mode-card:nth-child(3) { grid-column: span 1; max-width: 100%; }
    .submodes-grid { grid-template-columns: 1fr; gap: 1rem; }
    .hub-container { padding: 5.5rem 1rem 2.5rem; }
    .top-bar { padding: 0.9rem 1rem; }
    .mode-icon { font-size: 4.5rem; }
    .mode-title { font-size: 1.3rem; }
    .hub-sub { font-size: 0.95rem; }
    .mode-card { aspect-ratio: auto; min-height: 360px; }
}

@media (max-width: 480px) {
    .top-bar { padding: 0.75rem 0.85rem; }
    .back-link { font-size: 0.8rem; }
    .brand-logo { font-size: 1rem; letter-spacing: 0.2em; }
    .user-chip { font-size: 0.7rem; }
    .theme-toggle { width: 32px; height: 32px; margin-left: 0.6rem; }
    .theme-toggle svg { width: 13px; height: 13px; }

    .hub-container { padding: 4.75rem 0.85rem 2rem; }
    .hub-title { font-size: 2rem; line-height: 1.15; }
    .hub-tag { font-size: 0.65rem; letter-spacing: 0.3em; }
    .hub-divider::before, .hub-divider::after { width: 50px; }

    .mode-card { min-height: 320px; border-radius: 14px; }
    .mode-icon { font-size: 4rem; }
    .mode-info { padding: 1.4rem 1.2rem 1.2rem; }
    .mode-title { font-size: 1.2rem; }
    .mode-description { font-size: 0.82rem; min-height: auto; }

    .submode-card { padding: 1.2rem 1rem; gap: 0.9rem; }
    .submode-icon { font-size: 2.2rem; }
    .submode-title { font-size: 1rem; }
    .submode-desc { font-size: 0.78rem; }

    .modal-box { padding: 2rem 1.4rem; }
    .modal-title { font-size: 1.2rem; }
    .modal-text { font-size: 0.85rem; }
}

@media (max-width: 360px) {
    .top-bar { padding: 0.65rem 0.7rem; }
    .user-chip span:not(:first-child) { display: none; }
    .hub-title { font-size: 1.7rem; }
    .mode-icon { font-size: 3.6rem; }
}

/* Désactive le tilt 3D en tactile */
@media (hover: none) {
    .mode-card:hover { transform: none; box-shadow: none; }
    .mode-card:hover .mode-icon { transform: none; }
    .mode-card:hover::before { opacity: 0; }
    .mode-card:hover::after  { opacity: 0; }
    .mode-card:hover .mode-arrow { transform: none; }
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

    <div style="display:flex; align-items:center;">
        <div class="brand-logo">QPC</div>
        <button id="theme-toggle" class="theme-toggle" aria-label="Basculer le thème" type="button">
            <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4"></circle>
                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
            </svg>
            <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
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
</div>

<!-- ═══════════════════════════════════════════════════════════
     HUB CONTENT
═══════════════════════════════════════════════════════════ -->
<div class="hub-container">

    <div class="hub-header">
        <p class="hub-tag">Choisis ton arène</p>
        <h1 class="hub-title">Modes de jeu</h1>
        <div class="hub-divider"><span class="hub-divider-icon">◆</span></div>
        <p class="hub-sub">Trois catégories. Entraîne-toi en solo, joue sérieusement pour grimper au classement, ou affronte tes potes sans pression.</p>
    </div>

    <!-- ════ 3 catégories principales ════ -->
    <div class="modes-grid">

        <!-- Catégorie 1 : ENTRAÎNEMENT (lien direct, pas de sous-modes) -->
        <a href="training.php" class="mode-card solo" data-mode="training">
            <div class="mode-visual">
                <span class="mode-ornament">I</span>
                <span class="mode-icon">🎯</span>
            </div>
            <div class="mode-info">
                <p class="mode-label">Catégorie 01 · Solo</p>
                <h3 class="mode-title">Entraînement</h3>
                <p class="mode-description">Joue à ton rythme, sans pression. Améliore tes connaissances et prépare-toi pour le multijoueur.</p>
                <div class="mode-meta">
                    <span class="mode-meta-tag">Sans risque ELO</span>
                    <span class="mode-arrow">→</span>
                </div>
            </div>
        </a>

        <!-- Catégorie 2 : CLASSÉ (drill-down → Tournoi / 1V1, auth requise) -->
        <button class="mode-card duel" data-mode="classe" type="button">
            <div class="mode-visual">
                <span class="mode-ornament">II</span>
                <span class="mode-icon">⚔</span>
            </div>
            <div class="mode-info">
                <p class="mode-label">Catégorie 02 · Compétitif</p>
                <h3 class="mode-title">Classé</h3>
                <p class="mode-description">Tournoi à 4 ou duel 1V1, ELO en jeu. Grimpe au classement et décroche les titres de saison.</p>
                <div class="mode-meta">
                    <span class="mode-meta-tag">ELO ON · Compte requis</span>
                    <span class="mode-arrow">→</span>
                </div>
            </div>
        </button>

        <!-- Catégorie 3 : ENTRE AMIS (drill-down → Tournoi / 1V1 amicaux, sans ELO) -->
        <button class="mode-card champ" data-mode="amis" type="button">
            <div class="mode-visual">
                <span class="mode-ornament">III</span>
                <span class="mode-icon">🎉</span>
            </div>
            <div class="mode-info">
                <p class="mode-label">Catégorie 03 · Fun</p>
                <h3 class="mode-title">Entre amis</h3>
                <p class="mode-description">Salon privé avec code à partager. Tournoi à 4 ou 1V1, mais cette fois c'est juste pour le fun — l'ELO ne bouge pas.</p>
                <div class="mode-meta">
                    <span class="mode-meta-tag">Sans ELO · Code privé</span>
                    <span class="mode-arrow">→</span>
                </div>
            </div>
        </button>

    </div>

    <!-- ════ Section sous-modes (drill-down, cachée par défaut) ════ -->
    <div class="submenu" id="submenu">
        <div class="submenu-header">
            <span class="submenu-tag" id="submenu-tag">Sous-modes</span>
            <h2 class="submenu-title" id="submenu-title">Choisis ton format</h2>
            <div class="submenu-divider"></div>
        </div>

        <div class="submodes-grid">
            <a href="#" class="submode-card" id="sub-tournoi">
                <span class="submode-icon">👑</span>
                <div class="submode-info">
                    <div class="submode-title">Tournoi</div>
                    <div class="submode-desc">4 joueurs, 3 manches éliminatoires</div>
                    <span class="submode-badge ranked" id="badge-tournoi">+/− ELO</span>
                </div>
                <span class="submode-arrow">→</span>
            </a>

            <a href="#" class="submode-card" id="sub-1v1">
                <span class="submode-icon">⚔️</span>
                <div class="submode-info">
                    <div class="submode-title">Duel 1V1</div>
                    <div class="submode-desc">Affrontement direct, buzz partagé</div>
                    <span class="submode-badge ranked" id="badge-1v1">+/− ELO</span>
                </div>
                <span class="submode-arrow">→</span>
            </a>
        </div>
    </div>

</div>

<!-- ════ Modal verrou classé ════ -->
<div class="modal-overlay" id="auth-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-box">
        <div class="modal-icon">🔒</div>
        <h3 class="modal-title" id="modal-title">Mode classé verrouillé</h3>
        <p class="modal-text">Pour participer aux parties classées et grimper au classement ELO, il te faut un compte. C'est gratuit et ça prend 30 secondes.</p>
        <div class="modal-actions">
            <a href="connexion.php" class="btn-modal primary">Se connecter</a>
            <button class="btn-modal secondary" id="modal-close" type="button">Pas maintenant</button>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════
// État utilisateur (injecté côté serveur)
// ═══════════════════════════════════════════════════════════
const IS_LOGGED_IN = <?= $user_id ? 'true' : 'false' ?>;

// ═══════════════════════════════════════════════════════════
// Particules dorées (purement décoratif)
// ═══════════════════════════════════════════════════════════
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

// ═══════════════════════════════════════════════════════════
// Effet 3D tilt au survol des cartes principales
// ═══════════════════════════════════════════════════════════
document.querySelectorAll('.mode-card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
        // Pas de tilt sur une carte en état "active" (sélectionnée)
        if (card.classList.contains('active')) return;
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

// ═══════════════════════════════════════════════════════════
// Toggle thème dark/light
// ═══════════════════════════════════════════════════════════
(function () {
    const root = document.documentElement;
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', () => {
        root.classList.add('theme-transitioning');
        const isLight = root.classList.toggle('light');
        try { localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); } catch (e) {}
        setTimeout(() => root.classList.remove('theme-transitioning'), 300);
    });
})();

// ═══════════════════════════════════════════════════════════
// LOGIQUE DRILL-DOWN : 3 catégories → sous-modes
// ═══════════════════════════════════════════════════════════

// Références DOM
const modeCards    = document.querySelectorAll('.mode-card');
const submenu      = document.getElementById('submenu');
const submenuTag   = document.getElementById('submenu-tag');
const submenuTitle = document.getElementById('submenu-title');
const subTournoi   = document.getElementById('sub-tournoi');
const sub1v1       = document.getElementById('sub-1v1');
const badgeTournoi = document.getElementById('badge-tournoi');
const badge1v1     = document.getElementById('badge-1v1');
const authModal    = document.getElementById('auth-modal');
const modalClose   = document.getElementById('modal-close');

// Catégorie active actuellement (null, 'classe' ou 'amis')
let activeMode = null;

// Définition des URLs pour chaque catégorie ayant des sous-modes
const SUBMODES = {
    classe: {
        tag:   'Mode classé · ELO en jeu',
        title: 'Choisis ton format compétitif',
        urls: {
            tournoi: 'championship/lobby.php',
            duel:    'lobby-1v1.php'
        },
        badgeClass: 'ranked',
        badgeText:  '+/− ELO'
    },
    amis: {
        tag:   'Mode amis · ELO figé',
        title: 'Choisis ton format amical',
        urls: {
            tournoi: 'championship/lobby.php?friendly=1',
            duel:    'lobby-1v1.php?friendly=1'
        },
        badgeClass: 'casual',
        badgeText:  'Sans ELO'
    }
};

// Click sur les cartes principales
modeCards.forEach(card => {
    card.addEventListener('click', (e) => {
        const mode = card.dataset.mode;

        // Entraînement : on laisse le <a> naviguer normalement
        if (mode === 'training') return;

        e.preventDefault();

        // Classé : auth gate
        if (mode === 'classe' && !IS_LOGGED_IN) {
            openAuthModal();
            return;
        }

        // Drill-down ou toggle off si déjà actif
        if (activeMode === mode) {
            closeSubmenu();
        } else {
            openSubmenu(mode, card);
        }
    });
});

function openSubmenu(mode, clickedCard) {
    const config = SUBMODES[mode];
    if (!config) return;

    activeMode = mode;

    // États visuels des cartes principales
    modeCards.forEach(c => {
        c.classList.remove('active', 'faded');
        if (c === clickedCard) {
            c.classList.add('active');
        } else {
            c.classList.add('faded');
        }
        c.style.transform = ''; // reset le tilt
    });

    // Mise à jour du contenu du sous-menu
    submenuTag.textContent   = config.tag;
    submenuTitle.textContent = config.title;
    subTournoi.href          = config.urls.tournoi;
    sub1v1.href              = config.urls.duel;

    // Badges (ranked vs casual)
    [badgeTournoi, badge1v1].forEach(b => {
        b.className = 'submode-badge ' + config.badgeClass;
        b.textContent = config.badgeText;
    });

    // Re-trigger les animations des sous-cards (sinon l'animation ne rejoue pas
    // si on switch direct de classé à amis sans fermer)
    submenu.classList.remove('open');
    void submenu.offsetWidth; // force reflow
    submenu.classList.add('open');

    // Scroll doux vers le sous-menu après l'animation d'ouverture
    setTimeout(() => {
        submenu.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 350);
}

function closeSubmenu() {
    activeMode = null;
    modeCards.forEach(c => c.classList.remove('active', 'faded'));
    submenu.classList.remove('open');
}

// ═══════════════════════════════════════════════════════════
// MODAL AUTH (verrou classé)
// ═══════════════════════════════════════════════════════════
function openAuthModal()  { authModal.classList.add('show'); }
function closeAuthModal() { authModal.classList.remove('show'); }

modalClose.addEventListener('click', closeAuthModal);
authModal.addEventListener('click', (e) => {
    if (e.target === authModal) closeAuthModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (authModal.classList.contains('show')) closeAuthModal();
        else if (activeMode) closeSubmenu();
    }
});
</script>

</body>
</html>