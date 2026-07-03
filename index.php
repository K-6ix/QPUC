<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ── Top 5 du classement (ELO) pour la section vitrine ────────
// Même logique que classement.php : ordre ELO (pas la vue
// `leaderboard`, qui classe par score_total). Requête gardée :
// si la table manque, la section affiche l'état vide proprement.
$lb_top = [];
if (isset($conn) && $conn instanceof mysqli) {
    $lb_res = @$conn->query("
        SELECT u.username, ps.elo, ps.total_games
        FROM player_stats ps
        JOIN users u ON u.id = ps.user_id
        ORDER BY ps.elo DESC, ps.victories DESC, u.username ASC
        LIMIT 5
    ");
    if ($lb_res) $lb_top = $lb_res->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Question pour un Champion</title>

<!-- ════ ANTI-FLASH : applique le thème avant le render ════ -->
<script>
(function () {
  try {
    var stored = localStorage.getItem('qpc-theme');
    if (stored === 'light') {
      document.documentElement.classList.add('light');
    }
    // Dark = défaut, pas de classe à ajouter
  } catch (e) {}
})();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
<style>
  /* ════════════════════════════════════════════════════════════
    TOKENS — Dark mode (par défaut)
  ═══════════════════════════════════════════════════════════ */
  :root {
    /* GOLD — identité, immuable entre les thèmes */
    --gold-light: #fcf6ba;
    --gold-base: #d4af37;
    --gold-dark: #8a6e2f;
    --gold-glow: rgba(212,175,55,0.35);
    --metallic: linear-gradient(to right, var(--gold-dark), var(--gold-base) 30%, var(--gold-light) 50%, var(--gold-base) 70%, var(--gold-dark));

    /* SURFACES */
    --bg: #060606;
    --bg2: #0d0d0d;
    --header-bg: rgba(6,6,6,0.85);
    --card: #0d0d0d;

    /* INK (texte) */
    --ink: #ffffff;
    --ink-2: rgba(255,255,255,0.55);
    --ink-3: rgba(255,255,255,0.35);
    --ink-4: rgba(255,255,255,0.2);
    --ink-5: rgba(255,255,255,0.1);

    /* LIGNES */
    --line: rgba(255,255,255,0.1);
    --line-soft: rgba(255,255,255,0.05);

    /* DORÉS d'accent (intensité ajustée pour rester visible) */
    --gold-line: rgba(212,175,55,0.15);
    --gold-line-strong: rgba(212,175,55,0.35);
    --gold-tint: rgba(212,175,55,0.05);
    --gold-tint-2: rgba(212,175,55,0.1);
    --gold-text: var(--gold-light);  /* texte doré sur fond bg */
    --on-gold: #000;                 /* texte sur fond doré */

    /* MISC */
    --noise-opacity: 0.03;
    --shadow-deep: rgba(0,0,0,0.5);

    /* Inversé (utilisé pour le bouton noir sur fond clair en light mode) */
    --invert: #ffffff;
    --on-invert: #0a0a0a;
  }

  /* ════════════════════════════════════════════════════════════
    TOKENS — Light mode (override via .light sur <html>)
  ═══════════════════════════════════════════════════════════ */
  html.light {
    --bg: #ffffff;
    --bg2: #f5f5f3;
    --header-bg: rgba(255,255,255,0.88);
    --card: #ffffff;

    --ink: #0a0a0a;
    --ink-2: rgba(10,10,10,0.65);
    --ink-3: rgba(10,10,10,0.5);
    --ink-4: rgba(10,10,10,0.35);
    --ink-5: rgba(10,10,10,0.15);

    --line: rgba(10,10,10,0.1);
    --line-soft: rgba(10,10,10,0.06);

    --gold-line: rgba(138,110,47,0.3);
    --gold-line-strong: rgba(138,110,47,0.55);
    --gold-tint: rgba(212,175,55,0.07);
    --gold-tint-2: rgba(212,175,55,0.14);
    --gold-text: var(--gold-dark);   /* doré foncé pour lisibilité sur blanc */

    --noise-opacity: 0.02;
    --shadow-deep: rgba(0,0,0,0.08);

    --invert: #0a0a0a;
    --on-invert: #ffffff;
  }

  /* ════ TRANSITION DOUCE pendant le switch de thème ════ */
  .theme-transitioning,
  .theme-transitioning * {
    transition: background-color 0.25s ease,
                border-color 0.25s ease,
                color 0.25s ease,
                fill 0.25s ease,
                stroke 0.25s ease !important;
  }

  *{margin:0;padding:0;box-sizing:border-box;}

  html { scroll-behavior: smooth; }

  body {
    background: var(--bg);
    color: var(--ink);
    font-family: 'Montserrat', sans-serif;
    overflow-x: hidden;
  }

  /* ── NOISE OVERLAY ── */
  body::before {
    content:'';
    position:fixed;
    inset:0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
    opacity: var(--noise-opacity);
    pointer-events: none;
    z-index: 9999;
    mix-blend-mode: multiply;
  }
  html.light body::before { mix-blend-mode: multiply; opacity: 0.04; }

  /* ════════════════════════════
    HEADER (format d'origine : grid 30% / 50% / 20%)
  ════════════════════════════ */
  header {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    display: grid;
    grid-template-columns: 30% 50% 20%;
    align-items: center;
    padding: 0 40px;
    height: 72px;
    border-bottom: 1px solid var(--gold-line);
    background: var(--header-bg);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    opacity: 0;
    animation: slideDown 0.8s cubic-bezier(0.2,0.8,0.2,1) 0.2s forwards;
  }

  .logo {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1.1rem;
    letter-spacing: 3px;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-transform: uppercase;
    filter: drop-shadow(0 0 6px var(--gold-glow));
    text-decoration: none;
    justify-self: start;
  }

  header nav ul {
    display: flex;
    list-style: none;
    gap: 28px;
    align-items: center;
    justify-content: center;
  }

  header nav a {
    text-decoration: none;
    color: var(--ink-2);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    position: relative;
    transition: color 0.3s;
  }
  header nav a:hover { color: var(--gold-text); }
  header nav a::after {
    content:'';
    position:absolute;
    width:0; height:1px;
    bottom:-4px; left:0;
    background: var(--metallic);
    transition: width 0.3s;
  }
  header nav a:hover::after { width:100%; }

  .btn-play {
    background: var(--metallic);
    color: var(--on-gold) !important;
    -webkit-text-fill-color: var(--on-gold) !important;
    padding: 7px 22px;
    border-radius: 30px;
    font-weight: 900;
    border: 1px solid var(--gold-base);
    box-shadow: 0 0 12px var(--gold-glow);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .btn-play:hover { transform: scale(1.05); box-shadow: 0 0 22px rgba(212,175,55,0.7); }
  .btn-play::after { display: none; }

  /* ── Cluster droite : theme + connexion + (burger en mobile) ── */
  .header-right {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  /* Bouton icône (theme toggle + burger) */
  .icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 1px solid var(--gold-line-strong);
    background: transparent;
    color: var(--ink);
    cursor: pointer;
    transition: border-color 0.25s, color 0.25s, transform 0.2s, background 0.25s;
    flex-shrink: 0;
  }
  .icon-btn:hover {
    border-color: var(--gold-base);
    color: var(--gold-text);
    background: var(--gold-tint);
  }
  .icon-btn:active { transform: scale(0.95); }
  .icon-btn svg { width: 15px; height: 15px; }

  /* Toggle theme : SUN visible en dark (propose light), MOON visible en light (propose dark) */
  #theme-toggle .theme-moon { display: none; }
  #theme-toggle .theme-sun  { display: block; }
  html.light #theme-toggle .theme-moon { display: block; }
  html.light #theme-toggle .theme-sun  { display: none; }

  .btn-connexion {
    background: transparent;
    border: 1px solid var(--gold-line-strong);
    color: var(--gold-text);
    padding: 7px 22px;
    border-radius: 30px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s;
    white-space: nowrap;
  }
  .btn-connexion:hover {
    background: var(--metallic);
    color: var(--on-gold);
    -webkit-text-fill-color: var(--on-gold);
    border-color: transparent;
    box-shadow: 0 0 18px var(--gold-glow);
  }

  /* Burger trigger — masqué en desktop */
  #burger-trigger { display: none; }

  /* ════════════════════════════
    MOBILE DRAWER MENU
  ════════════════════════════ */
  #mobile-menu {
    position: fixed;
    inset: 0;
    z-index: 200;
    visibility: hidden;
    pointer-events: none;
  }
  #mobile-menu.is-open {
    visibility: visible;
    pointer-events: auto;
  }

  #mobile-menu-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  #mobile-menu.is-open #mobile-menu-backdrop { opacity: 1; }

  #mobile-menu-panel {
    position: absolute;
    right: 0; top: 0;
    height: 100%;
    width: 75%;
    max-width: 360px;
    background: var(--bg);
    border-left: 1px solid var(--gold-line);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -10px 0 40px rgba(0,0,0,0.4);
  }
  #mobile-menu.is-open #mobile-menu-panel { transform: translateX(0); }

  .drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    height: 72px;
    border-bottom: 1px solid var(--gold-line);
  }

  .drawer-nav {
    flex: 1;
    padding: 32px 24px;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .drawer-section-label {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--gold-text);
    margin-bottom: 18px;
  }

  .drawer-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 0;
    border-bottom: 1px solid var(--line-soft);
    text-decoration: none;
    color: var(--ink);
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 1px;
    transition: color 0.25s, padding-left 0.25s;
  }
  .drawer-link:hover {
    color: var(--gold-text);
    padding-left: 6px;
  }
  .drawer-link svg {
    width: 16px;
    height: 16px;
    color: var(--ink-4);
    transition: color 0.25s, transform 0.25s;
  }
  .drawer-link:hover svg {
    color: var(--gold-text);
    transform: translateX(4px);
  }

  .drawer-footer {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-top: 1px solid var(--line-soft);
  }
  .drawer-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border-radius: 40px;
    font-weight: 900;
    font-size: 0.8rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .drawer-cta.primary {
    background: var(--metallic);
    color: var(--on-gold);
    border: 1px solid var(--gold-base);
    box-shadow: 0 0 12px var(--gold-glow);
  }
  .drawer-cta.primary:hover { transform: translateY(-2px); }
  .drawer-cta.secondary {
    background: transparent;
    border: 1px solid var(--gold-line-strong);
    color: var(--gold-text);
  }
  .drawer-cta.secondary:hover {
    background: var(--gold-tint);
    border-color: var(--gold-base);
  }
  .drawer-copy {
    text-align: center;
    font-size: 0.65rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--ink-4);
    margin-top: 8px;
  }

  /* ════════════════════════════
    HERO
  ════════════════════════════ */
  .hero {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    padding-top: calc(72px + 10px);
    overflow: hidden;
  }

  /* radial spotlight */
  .hero::before {
    content:'';
    position:absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -60%);
    width: 700px; height: 700px;
    background: radial-gradient(circle, rgba(212,175,55,0.12) 0%, transparent 70%);
    pointer-events: none;
  }

  /* decorative lines */
  .hero-lines {
    position:absolute;
    inset:0;
    pointer-events:none;
    overflow:hidden;
  }
  .hero-lines::before, .hero-lines::after {
    content:'';
    position:absolute;
    border: 1px solid var(--gold-tint-2);
    border-radius: 50%;
    top:50%; left:50%;
    transform: translate(-50%,-50%);
  }
  .hero-lines::before { width:600px; height:600px; }
  .hero-lines::after  { width:900px; height:900px; }

  .hero-eyebrow {
    font-size: 0.7rem;
    letter-spacing: 6px;
    text-transform: uppercase;
    color: var(--gold-base);
    margin-bottom: 24px;
    opacity: 0;
    animation: fadeUp 0.8s ease 0.4s forwards;
  }

  .hero-trophy {
    width: 280px;
    opacity: 0;
    animation: fadeUp 1.2s cubic-bezier(0.2,0.8,0.2,1) 0.5s forwards;
    filter: drop-shadow(0 0 40px rgba(212,175,55,0.4));
  }

  .hero-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 1;
    opacity: 0;
    animation: fadeUp 1s ease 0.7s forwards;
  }
  .hero-title .solid {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: clamp(3rem, 8vw, 6rem);
    letter-spacing: 4px;
    color: var(--ink);
    text-transform: uppercase;
  }
  .hero-title .script {
    font-family: 'Great Vibes', cursive;
    font-size: clamp(3.5rem, 9vw, 7rem);
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 0 10px var(--gold-glow));
    margin-top: -40px;
  }

  .hero-sub {
    margin-top: 20px;
    font-size: 0.9rem;
    color: var(--ink-3);
    letter-spacing: 2px;
    text-align: center;
    opacity: 0;
    animation: fadeUp 0.8s ease 0.9s forwards;
  }

  .hero-ctas {
    display: flex;
    gap: 16px;
    margin-top: 40px;
    opacity: 0;
    animation: fadeUp 0.8s ease 1.1s forwards;
    flex-wrap: wrap;
    justify-content: center;
  }

  .cta-primary {
    background: var(--metallic);
    color: var(--on-gold);
    padding: 14px 40px;
    border-radius: 40px;
    font-weight: 900;
    font-size: 0.9rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    border: none;
    cursor: pointer;
    box-shadow: 0 0 20px var(--gold-glow), 0 4px 20px var(--shadow-deep);
    transition: transform 0.2s, box-shadow 0.2s;
    display: inline-block;
  }
  .cta-primary:hover { transform: scale(1.06) translateY(-2px); box-shadow: 0 0 35px rgba(212,175,55,0.7), 0 8px 30px var(--shadow-deep); }

  .cta-secondary {
    background: transparent;
    color: var(--ink-2);
    padding: 14px 40px;
    border-radius: 40px;
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    border: 1px solid var(--line);
    transition: all 0.3s;
    display: inline-block;
  }
  .cta-secondary:hover { border-color: var(--gold-base); color: var(--gold-text); }

  .scroll-hint {
    position:absolute;
    bottom: 32px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    opacity: 0;
    animation: fadeUp 0.8s ease 1.5s forwards;
  }
  .scroll-hint span { font-size: 0.65rem; letter-spacing: 3px; text-transform: uppercase; color: var(--ink-3); }
  .scroll-arrow {
    width: 20px; height: 20px;
    border-right: 1px solid var(--gold-line-strong);
    border-bottom: 1px solid var(--gold-line-strong);
    transform: rotate(45deg);
    animation: bounce 1.5s ease infinite;
  }

  /* ════════════════════════════
    STATS BAR
  ════════════════════════════ */
  .stats-bar {
    border-top: 1px solid var(--gold-line);
    border-bottom: 1px solid var(--gold-line);
    background: var(--gold-tint);
    padding: 32px 40px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    text-align: center;
    gap: 20px;
  }
  .stat-num {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 2.5rem;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .stat-label {
    font-size: 0.7rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--ink-3);
    margin-top: 4px;
  }

  /* ════════════════════════════
    MODES SECTION
  ════════════════════════════ */
  .section {
    padding: 100px 40px;
    max-width: 1200px;
    margin: 0 auto;
  }

  .section-tag {
    font-size: 0.65rem;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--gold-base);
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
  }
  .section-tag::before, .section-tag::after {
    content:'';
    height:1px;
    width:40px;
    background: var(--gold-base);
    opacity: 0.4;
  }

  .section-title {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: clamp(2rem, 5vw, 3.5rem);
    letter-spacing: 2px;
    text-transform: uppercase;
    line-height: 1.1;
    margin-bottom: 60px;
    color: var(--ink);
  }
  .section-title em {
    font-style: normal;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .modes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    max-width: 1100px;
    margin: 0 auto;
  }

  .mode-card {
    background: var(--card);
    border: 1px solid var(--gold-tint-2);
    border-radius: 16px;
    padding: 36px 28px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.3s, transform 0.3s, background 0.3s;
    cursor: default;
  }
  .mode-card::before {
    content:'';
    position:absolute;
    inset:0;
    background: radial-gradient(circle at 50% 0%, rgba(212,175,55,0.06) 0%, transparent 70%);
    opacity:0;
    transition: opacity 0.4s;
  }
  .mode-card:hover { border-color: var(--gold-line-strong); transform: translateY(-4px); }
  .mode-card:hover::before { opacity:1; }

  .mode-card.featured {
    border-color: var(--gold-line-strong);
    background: linear-gradient(135deg, var(--gold-tint-2), var(--card));
  }
  .mode-card.featured::after {
    content: 'POPULAIRE';
    position:absolute;
    top: 16px; right: 16px;
    background: var(--metallic);
    color: var(--on-gold);
    font-size: 0.55rem;
    font-weight: 900;
    letter-spacing: 2px;
    padding: 3px 10px;
    border-radius: 20px;
  }

  .mode-icon {
    font-size: 2.5rem;
    margin-bottom: 20px;
    display: block;
  }
  .mode-name {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1.6rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 12px;
  }
  .mode-desc {
    font-size: 0.82rem;
    color: var(--ink-2);
    line-height: 1.7;
    margin-bottom: 24px;
  }
  .mode-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .tag {
    font-size: 0.65rem;
    letter-spacing: 1px;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid var(--gold-line);
    color: var(--gold-text);
    text-transform: uppercase;
  }

  /* ════════════════════════════
    FEATURES SECTION
  ════════════════════════════ */
  .features-section {
    padding: 100px 40px;
    background: var(--bg2);
    border-top: 1px solid var(--gold-line);
    border-bottom: 1px solid var(--gold-line);
  }
  .features-inner {
    max-width: 1200px;
    margin: 0 auto;
  }

  .features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2px;
    border: 1px solid var(--gold-line);
    border-radius: 16px;
    overflow: hidden;
  }

  .feature-item {
    padding: 40px 36px;
    background: var(--bg2);
    border: 1px solid var(--gold-tint);
    transition: background 0.3s;
    position: relative;
  }
  .feature-item:hover { background: var(--gold-tint); }

  .feature-num {
    font-family: 'Kanit', sans-serif;
    font-size: 3rem;
    font-weight: 900;
    color: var(--gold-tint-2);
    position:absolute;
    top: 20px; right: 24px;
    line-height:1;
  }
  html.light .feature-num { color: rgba(212,175,55,0.25); }

  .feature-title {
    font-size: 1rem;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--gold-text);
    margin-bottom: 10px;
  }
  .feature-desc {
    font-size: 0.82rem;
    color: var(--ink-2);
    line-height: 1.7;
  }
  .feature-line {
    width: 32px; height: 2px;
    background: var(--gold-base);
    margin-bottom: 16px;
    opacity: 0.5;
  }

  /* ════════════════════════════
    LEADERBOARD PREVIEW
  ════════════════════════════ */
  .leaderboard-section {
    padding: 100px 40px;
    max-width: 1200px;
    margin: 0 auto;
  }

  .lb-table {
    background: var(--card);
    border: 1px solid var(--gold-line);
    border-radius: 16px;
    overflow: hidden;
  }

  .lb-header {
    display: grid;
    grid-template-columns: 60px 1fr 120px 120px;
    padding: 16px 28px;
    background: var(--gold-tint);
    border-bottom: 1px solid var(--gold-line);
  }
  .lb-header span {
    font-size: 0.65rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--ink-3);
  }

  .lb-row {
    display: grid;
    grid-template-columns: 60px 1fr 120px 120px;
    padding: 20px 28px;
    border-bottom: 1px solid var(--line-soft);
    align-items: center;
    transition: background 0.2s;
  }
  .lb-row:hover { background: var(--gold-tint); }
  .lb-row:last-child { border-bottom: none; }

  .lb-rank {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1.1rem;
  }
  .lb-rank.gold { background: var(--metallic); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
  .lb-rank.silver { color: #9a9a9a; }
  .lb-rank.bronze { color: #c47b3a; }
  .lb-rank.other  { color: var(--ink-4); }

  .lb-player {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .lb-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: var(--gold-tint-2);
    border: 1px solid var(--gold-line);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--gold-base);
  }
  .lb-name { font-size: 0.9rem; font-weight: 700; color: var(--ink); }
  .lb-badge { font-size: 0.6rem; letter-spacing: 1px; background: var(--gold-tint-2); color: var(--gold-base); padding: 2px 8px; border-radius: 10px; margin-left: 8px; }

  .lb-score {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1.1rem;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .lb-games { font-size: 0.8rem; color: var(--ink-3); }

  .lb-soon {
    text-align: center;
    padding: 16px;
    font-size: 0.7rem;
    letter-spacing: 3px;
    color: var(--ink-4);
    text-transform: uppercase;
    border-top: 1px solid var(--gold-tint);
  }

  /* ════════════════════════════
    CTA BANNER
  ════════════════════════════ */
  .cta-banner {
    margin: 0 40px 100px;
    background: linear-gradient(135deg, var(--gold-tint-2), var(--gold-tint));
    border: 1px solid var(--gold-line);
    border-radius: 20px;
    padding: 80px 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .cta-banner::before {
    content:'';
    position:absolute;
    top:-1px; left:50%;
    transform: translateX(-50%);
    width: 200px; height: 2px;
    background: var(--metallic);
  }
  .cta-banner h2 {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: clamp(2rem, 5vw, 3rem);
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 16px;
    color: var(--ink);
  }
  .cta-banner p {
    color: var(--ink-2);
    font-size: 0.9rem;
    letter-spacing: 1px;
    margin-bottom: 36px;
  }

  /* ════════════════════════════
    FOOTER
  ════════════════════════════ */
  footer {
    position: relative;
    background: var(--bg2);
    border-top: 1px solid var(--gold-line);
    overflow: hidden;
  }

  /* ligne dorée animée en haut du footer */
  footer::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 2px;
    background: var(--metallic);
    animation: footerLine 3s ease-in-out infinite;
  }
  @keyframes footerLine {
    0%   { left: -100%; opacity: 0; }
    20%  { opacity: 1; }
    80%  { opacity: 1; }
    100% { left: 100%; opacity: 0; }
  }

  .footer-top {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    padding: 48px 60px 32px;
    gap: 40px;
    border-bottom: 1px solid var(--gold-tint);
  }

  .footer-logo {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1.4rem;
    letter-spacing: 4px;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-transform: uppercase;
    display: block;
    margin-bottom: 8px;
  }
  .footer-tagline {
    font-size: 0.7rem;
    letter-spacing: 3px;
    color: var(--ink-4);
    text-transform: uppercase;
  }

  .footer-nav {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    list-style: none;
  }
  .footer-nav a {
    text-decoration: none;
    font-size: 0.75rem;
    letter-spacing: 3px;
    color: var(--ink-3);
    text-transform: uppercase;
    font-weight: 700;
    transition: color 0.3s;
    position: relative;
  }
  .footer-nav a::after {
    content: '';
    position: absolute;
    width: 0; height: 1px;
    bottom: -3px; left: 50%;
    transform: translateX(-50%);
    background: var(--gold-base);
    transition: width 0.3s;
  }
  .footer-nav a:hover { color: var(--gold-text); }
  .footer-nav a:hover::after { width: 100%; }

  .footer-cta-col {
    display: flex;
    justify-content: flex-end;
  }
  .footer-play-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--metallic);
    color: var(--on-gold);
    padding: 12px 28px;
    border-radius: 40px;
    font-weight: 900;
    font-size: 0.8rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    box-shadow: 0 0 20px var(--gold-glow);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .footer-play-btn:hover {
    transform: scale(1.05) translateY(-2px);
    box-shadow: 0 0 35px rgba(212,175,55,0.6);
  }
  .footer-play-icon {
    width: 28px; height: 28px;
    background: rgba(0,0,0,0.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem;
  }

  .footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 60px;
    flex-wrap: wrap;
    gap: 12px;
  }
  .footer-copy {
    font-size: 0.65rem;
    letter-spacing: 2px;
    color: var(--ink-4);
    text-transform: uppercase;
  }
  .footer-school {
    font-size: 0.65rem;
    letter-spacing: 2px;
    color: var(--gold-text);
    text-transform: uppercase;
    opacity: 0.6;
  }

  /* ════════════════════════════
    ANIMATIONS
  ════════════════════════════ */
  @keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
  }
  @keyframes fadeUp {
    from { transform: translateY(30px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  @keyframes bounce {
    0%, 100% { transform: rotate(45deg) translateY(0); }
    50%       { transform: rotate(45deg) translateY(6px); }
  }

  /* scroll reveal */
  .reveal {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal.visible {
    opacity: 1;
    transform: translateY(0);
  }

  /* ════════════════════════════
    RESPONSIVE
  ════════════════════════════ */
  @media (max-width: 1024px) {
    header { grid-template-columns: auto 1fr auto; padding: 0 28px; gap: 20px; }
    .section { padding: 80px 28px; }
    .modes-grid { grid-template-columns: repeat(2, 1fr); max-width: 720px; }
    .features-section { padding: 80px 28px; }
    .features-grid { grid-template-columns: repeat(2, 1fr); }
    .leaderboard-section { padding: 80px 28px; }
    .footer-top { padding: 40px 32px 28px; gap: 28px; }
    .footer-bottom { padding: 18px 32px; }
  }

  @media (max-width: 900px) {
    header { padding: 0 20px; grid-template-columns: 1fr auto; }

    /* Masquer la nav desktop + bouton connexion → tout passe dans le burger */
    header > nav,
    .header-right .btn-connexion {
      display: none;
    }

    /* Afficher le burger trigger */
    #burger-trigger { display: inline-flex; }

    .modes-grid { grid-template-columns: 1fr; max-width: 480px; }
    .features-grid { grid-template-columns: 1fr; }
    .stats-bar { grid-template-columns: repeat(2, 1fr); padding: 28px 24px; }
    .lb-header, .lb-row { grid-template-columns: 50px 1fr 100px; padding: 16px 20px; }
    .lb-games { display: none; }
    .cta-banner { margin: 0 20px 80px; padding: 60px 28px; }

    /* Sections : padding plus serré, contenu centré */
    .section,
    .leaderboard-section { padding: 70px 20px; }
    .features-section { padding: 70px 20px; }

    /* Hero : trophée un peu plus petit */
    .hero-trophy { width: 220px; }
    .hero-eyebrow { letter-spacing: 4px; font-size: 0.65rem; }
  }

  @media (max-width: 600px) {
    header { padding: 0 16px; height: 64px; }
    .logo { font-size: 0.95rem; letter-spacing: 2px; }
    .icon-btn { width: 34px; height: 34px; }

    .hero { padding-top: calc(64px + 20px); padding-left: 16px; padding-right: 16px; }
    .hero-trophy { width: 180px; }
    .hero-sub { font-size: 0.78rem; letter-spacing: 1.5px; padding: 0 8px; }
    .hero-ctas { gap: 12px; }
    .hero-ctas .cta-primary,
    .hero-ctas .cta-secondary { padding: 12px 28px; font-size: 0.78rem; letter-spacing: 1.5px; }

    .stats-bar { padding: 24px 16px; gap: 14px; }
    .stat-num { font-size: 1.9rem; }
    .stat-label { font-size: 0.6rem; letter-spacing: 2px; }

    .section,
    .leaderboard-section,
    .features-section { padding: 56px 16px; }

    .section-title { margin-bottom: 40px; letter-spacing: 1.5px; }
    .section-tag::before, .section-tag::after { width: 24px; }

    .mode-card { padding: 28px 22px; }
    .feature-item { padding: 32px 24px; }

    .lb-header, .lb-row { grid-template-columns: 40px 1fr 90px; padding: 14px 16px; gap: 8px; }
    .lb-avatar { width: 32px; height: 32px; font-size: 0.7rem; }
    .lb-name { font-size: 0.8rem; }
    .lb-badge { display: none; }
    .lb-score { font-size: 0.95rem; }

    .cta-banner { margin: 0 16px 60px; padding: 50px 22px; border-radius: 16px; }
    .cta-banner h2 { letter-spacing: 1.5px; }

    .footer-top { grid-template-columns: 1fr; text-align: center; padding: 36px 24px 24px; gap: 28px; }
    .footer-cta-col { justify-content: center; }
    .footer-nav { flex-direction: row; flex-wrap: wrap; justify-content: center; gap: 12px 18px; }
    .footer-bottom { flex-direction: column; text-align: center; padding: 16px 24px; }
  }

  @media (max-width: 380px) {
    .hero-ctas { flex-direction: column; width: 100%; padding: 0 24px; }
    .hero-ctas .cta-primary,
    .hero-ctas .cta-secondary { width: 100%; text-align: center; }
  }
</style>
</head>
<body>

<!-- ════ HEADER (format d'origine + theme toggle) ════ -->
<header>
  <a href="index.php" class="logo">HESTIM</a>

  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="rules.php">Rules</a></li>
      <li><a href="game.php" class="btn-play">▶ Play</a></li>
      <li><a href="classement.php">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
    </ul>
  </nav>

  <div class="header-right">
    <!-- Theme toggle -->
    <button id="theme-toggle" class="icon-btn" aria-label="Basculer le thème" type="button">
      <!-- SUN (visible en dark : propose light) -->
      <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
      </svg>
      <!-- MOON (visible en light : propose dark) -->
      <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
      </svg>
    </button>

    <!-- Bouton Connexion / Dashboard -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="dashboard.php" class="btn-connexion">Dashboard</a>
    <?php else: ?>
      <a href="connexion.php" class="btn-connexion">Connexion</a>
    <?php endif; ?>

    <!-- Burger trigger (mobile uniquement) -->
    <button id="burger-trigger" class="icon-btn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobile-menu" type="button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>
  </div>
</header>

<!-- ════ MOBILE DRAWER ════ -->
<div id="mobile-menu" aria-hidden="true">
  <div id="mobile-menu-backdrop"></div>
  <aside id="mobile-menu-panel" role="dialog" aria-modal="true" aria-label="Menu principal">
    <div class="drawer-header">
      <span class="logo">HESTIM</span>
      <button id="burger-close" class="icon-btn" aria-label="Fermer le menu" type="button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <nav class="drawer-nav">
      <span class="drawer-section-label">Navigation</span>

      <a href="index.php" data-close class="drawer-link">
        <span>Home</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="rules.php" data-close class="drawer-link">
        <span>Rules</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="classement.php" data-close class="drawer-link">
        <span>Classement</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="aboutus.php" data-close class="drawer-link">
        <span>About Us</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </nav>

    <div class="drawer-footer">
      <a href="game.php" data-close class="drawer-cta primary">
        ▶ Jouer
      </a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php" data-close class="drawer-cta secondary">Dashboard</a>
      <?php else: ?>
        <a href="connexion.php" data-close class="drawer-cta secondary">Connexion</a>
      <?php endif; ?>
      <p class="drawer-copy">&copy; 2025 &middot; HESTIM</p>
    </div>
  </aside>
</div>

<!-- ════ HERO ════ -->
<section class="hero">
  <div class="hero-lines"></div>
  <p class="hero-eyebrow">Le jeu de culture générale ultime</p>
  <img class="hero-trophy"
    src="https://z-cdn-media.chatglm.cn/files/36311f54-97be-47f0-a4a5-64d74ba8a953.png?auth_key=1867542703-f4b5fe36334f4a51a241717276067712-0-b064606aa898dbf80ddda788551db499"
    alt="Trophée">
  <h1 class="hero-title">
    <span class="solid">QUESTION</span>
    <span class="script">Champion</span>
  </h1>
  <p class="hero-sub">Affronte tes amis · Domine le classement · Deviens la légende</p>
  <div class="hero-ctas">
    <a href="game.php" class="cta-primary">Jouer maintenant</a>
    <a href="rules.php" class="cta-secondary">Voir les règles</a>
  </div>
  <div class="scroll-hint">
    <span>Découvrir</span>
    <div class="scroll-arrow"></div>
  </div>
</section>

<!-- ════ STATS BAR ════ -->
<div class="stats-bar reveal">
  <div class="stat-item">
    <div class="stat-num">500+</div>
    <div class="stat-label">Questions</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">4</div>
    <div class="stat-label">Modes de jeu</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">3</div>
    <div class="stat-label">Niveaux</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">∞</div>
    <div class="stat-label">Championnats</div>
  </div>
</div>

<!-- ════ MODES ════ -->
<div class="section reveal">
  <p class="section-tag">Modes de jeu</p>
  <h2 class="section-title">Choisissez <em>votre combat</em></h2>
  <div class="modes-grid">

    <div class="mode-card">
      <span class="mode-icon">👤</span>
      <div class="mode-name">Solo</div>
      <p class="mode-desc">Testez vos connaissances seul contre la montre. Battez votre propre record et progressez dans les niveaux de difficulté.</p>
      <div class="mode-tags">
        <span class="tag">Chronomètre</span>
        <span class="tag">Difficulté adaptative</span>
        <span class="tag">Score personnel</span>
      </div>
    </div>

    <div class="mode-card featured">
      <span class="mode-icon">⚔️</span>
      <div class="mode-name">1 vs 1</div>
      <p class="mode-desc">Affrontez un adversaire en duel direct. Répondez plus vite, plus juste, et prenez la domination totale.</p>
      <div class="mode-tags">
        <span class="tag">Buzz synchronisé</span>
        <span class="tag">Paris de points</span>
        <span class="tag">Temps réel</span>
      </div>
    </div>

    <div class="mode-card">
      <span class="mode-icon">🏆</span>
      <div class="mode-name">Tournoi</div>
      <p class="mode-desc">Le mode compétition ultime. Éliminations progressives, tableau dynamique, classement général et statistiques détaillées. Seul le meilleur survivra.</p>
      <div class="mode-tags">
        <span class="tag">Élimination</span>
        <span class="tag">Tableau dynamique</span>
        <span class="tag">Multi-joueurs</span>
      </div>
    </div>

  </div>
</div>

<!-- ════ FEATURES ════ -->
<div class="features-section reveal">
  <div class="features-inner">
    <p class="section-tag">Fonctionnalités</p>
    <h2 class="section-title">Conçu pour <em>l'excellence</em></h2>
    <div class="features-grid">

      <div class="feature-item">
        <div class="feature-num">01</div>
        <div class="feature-line"></div>
        <div class="feature-title">Questions intelligentes</div>
        <p class="feature-desc">500+ questions mélangées dynamiquement, sans répétition, issues d'une base structurée couvrant toutes les catégories de culture générale.</p>
      </div>

      <div class="feature-item">
        <div class="feature-num">02</div>
        <div class="feature-line"></div>
        <div class="feature-title">Difficulté adaptative</div>
        <p class="feature-desc">Le jeu analyse vos performances en temps réel et ajuste automatiquement le niveau, le temps imparti et les points attribués.</p>
      </div>

      <div class="feature-item">
        <div class="feature-num">03</div>
        <div class="feature-line"></div>
        <div class="feature-title">Score en temps réel</div>
        <p class="feature-desc">Feedback visuel et sonore instantané. Bonus de rapidité, malus, paris de points — chaque seconde compte.</p>
      </div>

      <div class="feature-item">
        <div class="feature-num">04</div>
        <div class="feature-line"></div>
        <div class="feature-title">Historique & progression</div>
        <p class="feature-desc">Suivez votre évolution, consultez vos statistiques détaillées et recevez des recommandations automatiques pour progresser.</p>
      </div>

    </div>
  </div>
</div>

<!-- ════ LEADERBOARD PREVIEW ════ -->
<div class="leaderboard-section reveal" id="classement">
  <p class="section-tag">Classement</p>
  <h2 class="section-title">Les <em>meilleurs champions</em></h2>
  <div class="lb-table">
    <div class="lb-header">
      <span>#</span>
      <span>Joueur</span>
      <span>ELO</span>
      <span>Parties</span>
    </div>
    <?php if (empty($lb_top)): ?>
    <div class="lb-soon">Le classement se remplit dès les premiers duels classés — sois le premier champion.</div>
    <?php else: foreach ($lb_top as $i => $p):
        $rank = $i + 1;
        $rc   = [1 => 'gold', 2 => 'silver', 3 => 'bronze'][$rank] ?? 'other';
        $ini  = mb_strtoupper(mb_substr($p['username'], 0, 2, 'UTF-8'), 'UTF-8');
    ?>
    <div class="lb-row">
      <div class="lb-rank <?= $rc ?>"><?= str_pad($rank, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="lb-player">
        <div class="lb-avatar"><?= htmlspecialchars($ini) ?></div>
        <div>
          <span class="lb-name"><?= htmlspecialchars($p['username']) ?></span>
          <?php if ($rank === 1): ?><span class="lb-badge">Champion</span><?php endif; ?>
        </div>
      </div>
      <div class="lb-score"><?= number_format((int)$p['elo'], 0, ',', ' ') ?></div>
      <div class="lb-games"><?= (int)$p['total_games'] ?> partie<?= ((int)$p['total_games'] > 1 ? 's' : '') ?></div>
    </div>
    <?php endforeach; endif; ?>
    <a class="lb-soon" href="classement.php" style="display:block;text-decoration:none;">Voir le classement complet →</a>
  </div>
</div>

<!-- ════ CTA BANNER ════ -->
<div class="cta-banner reveal">
  <h2>Prêt à devenir <em style="background:var(--metallic);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Champion</em> ?</h2>
  <p>Rejoignez la compétition dès maintenant. Gratuit, sans téléchargement.</p>
  <a href="game.php" class="cta-primary">Commencer à jouer</a>
</div>

<!-- ════ FOOTER ════ -->
<footer>
  <div class="footer-top">
    <div class="footer-brand">
      <span class="footer-logo">Question Champion</span>
      <span class="footer-tagline">Par les étudiants HESTIM · Cycle Ingénieur 2025</span>
    </div>
    <ul class="footer-nav">
      <li><a href="index.php">Home</a></li>
      <li><a href="rules.php">Rules</a></li>
      <li><a href="classement.php">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
      <li><a href="connexion.php">Connexion</a></li>
    </ul>
    <div class="footer-cta-col">
      <a href="game.php" class="footer-play-btn">
        <span class="footer-play-icon">▶</span>
        Jouer maintenant
      </a>
    </div>
  </div>
  <div class="footer-bottom">
    <span class="footer-copy">© 2025 — Tous droits réservés</span>
    <span class="footer-school">HESTIM · Projet Semestre 2</span>
  </div>
</footer>

<script>
/* ═══════════════════════════════════════════
   SCROLL REVEAL (inchangé — rejoue à chaque pass)
═══════════════════════════════════════════ */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.remove('visible');
      // eslint-disable-next-line no-unused-expressions
      void e.target.offsetWidth; // force reflow
      e.target.classList.add('visible');
    } else {
      e.target.classList.remove('visible');
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

/* ═══════════════════════════════════════════
   THEME TOGGLE
═══════════════════════════════════════════ */
(function () {
  const root = document.documentElement;
  const toggle = document.getElementById('theme-toggle');
  if (!toggle) return;

  toggle.addEventListener('click', () => {
    root.classList.add('theme-transitioning');
    const isLight = root.classList.toggle('light');
    try {
      localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark');
    } catch (e) {}
    setTimeout(() => root.classList.remove('theme-transitioning'), 300);
  });
})();

/* ═══════════════════════════════════════════
   BURGER MENU
═══════════════════════════════════════════ */
(function () {
  const trigger  = document.getElementById('burger-trigger');
  const closeBtn = document.getElementById('burger-close');
  const menu     = document.getElementById('mobile-menu');
  const backdrop = document.getElementById('mobile-menu-backdrop');
  if (!trigger || !menu) return;

  function openMenu() {
    menu.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    trigger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    menu.classList.remove('is-open');
    menu.setAttribute('aria-hidden', 'true');
    trigger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  trigger.addEventListener('click', openMenu);
  closeBtn.addEventListener('click', closeMenu);
  backdrop.addEventListener('click', closeMenu);

  // Ferme au clic sur un lien
  menu.querySelectorAll('[data-close]').forEach(el => {
    el.addEventListener('click', closeMenu);
  });

  // Ferme avec Echap
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) closeMenu();
  });
})();
</script>
</body>
</html>
