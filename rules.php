<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Règles — Question Champion</title>

<!-- ════ ANTI-FLASH ════ -->
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
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
<style>
/* ════════════════════════════════════════════════════════════
   TOKENS — Dark mode (par défaut)
═══════════════════════════════════════════════════════════ */
:root {
  --gold-light: #fcf6ba;
  --gold-base: #d4af37;
  --gold-dark: #8a6e2f;
  --gold-glow: rgba(212,175,55,0.35);
  --metallic: linear-gradient(to right, var(--gold-dark), var(--gold-base) 30%, var(--gold-light) 50%, var(--gold-base) 70%, var(--gold-dark));

  --bg: #060606;
  --bg2: #0d0d0d;
  --header-bg: rgba(6,6,6,0.85);
  --card: #0e0e0e;

  --ink: #ffffff;
  --ink-2: rgba(255,255,255,0.55);
  --ink-3: rgba(255,255,255,0.35);
  --ink-4: rgba(255,255,255,0.2);
  --ink-5: rgba(255,255,255,0.1);

  --line: rgba(255,255,255,0.1);
  --line-soft: rgba(255,255,255,0.05);

  --gold-line: rgba(212,175,55,0.15);
  --gold-line-strong: rgba(212,175,55,0.35);
  --gold-tint: rgba(212,175,55,0.05);
  --gold-tint-2: rgba(212,175,55,0.1);
  --gold-text: var(--gold-light);
  --on-gold: #000;

  --noise-opacity: 0.025;
  --shadow-deep: rgba(0,0,0,0.5);
}

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
  --gold-text: var(--gold-dark);

  --noise-opacity: 0.02;
  --shadow-deep: rgba(0,0,0,0.08);
}

.theme-transitioning,
.theme-transitioning * {
  transition: background-color 0.25s ease,
              border-color 0.25s ease,
              color 0.25s ease,
              fill 0.25s ease,
              stroke 0.25s ease !important;
}

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--ink);
  font-family: 'Montserrat', sans-serif;
  overflow-x: hidden;
}

/* noise */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  opacity: var(--noise-opacity);
  pointer-events: none;
  z-index: 0;
  mix-blend-mode: multiply;
}
html.light body::before { opacity: 0.04; }

/* lignes verticales décoratives */
.line-left, .line-right {
  position: fixed;
  top: 0; bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom, transparent, var(--gold-line) 30%, var(--gold-line) 70%, transparent);
  z-index: 0;
}
.line-left { left: 60px; }
.line-right { right: 60px; }

/* ════════════════════════════
   HEADER (identique à index.php)
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

.header-right {
  justify-self: end;
  display: flex;
  align-items: center;
  gap: 12px;
}

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

#burger-trigger { display: none; }

/* ════════════════════════════
   MOBILE DRAWER (identique à index.php)
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
   PAGE HERO
════════════════════════════ */
.page-hero {
  position: relative;
  z-index: 1;
  padding-top: 160px;
  padding-bottom: 60px;
  text-align: center;
  opacity: 0;
  animation: fadeIn 1s ease 0.5s forwards;
}

.page-hero .label {
  font-size: 0.7rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  color: var(--gold-base);
  margin-bottom: 16px;
}

.page-hero h1 {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: clamp(3rem, 7vw, 6rem);
  letter-spacing: 4px;
  text-transform: uppercase;
  line-height: 1;
  color: var(--ink);
}

.page-hero h1 em {
  font-style: normal;
  font-family: 'Great Vibes', cursive;
  font-size: clamp(3.5rem, 8vw, 7rem);
  letter-spacing: 0;
  text-transform: none;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 10px var(--gold-glow));
}

.divider {
  width: 60px;
  height: 1px;
  background: var(--metallic);
  margin: 28px auto 0;
  opacity: 0.6;
}

/* ════════════════════════════
   CARDS STACK
════════════════════════════ */
.spacer { height: 40px; }

.cards-wrap {
  position: relative;
  z-index: 1;
  padding-bottom: 240px;
}

.card {
  position: sticky;
  top: 90px;
  width: min(860px, 92vw);
  margin: 0 auto 0;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid var(--gold-line);
  display: grid;
  grid-template-columns: 1fr 320px;
  min-height: 340px;
  background: var(--card);
  transition: border-color 0.4s, box-shadow 0.4s;
  box-shadow: 0 10px 40px var(--shadow-deep);
}
.card:hover {
  border-color: var(--gold-line-strong);
}

.card:nth-child(1) { z-index: 1; top: 90px; }
.card:nth-child(2) { z-index: 2; top: 106px; }
.card:nth-child(3) { z-index: 3; top: 122px; }
.card:nth-child(4) { z-index: 4; top: 138px; }
.card:nth-child(5) { z-index: 5; top: 154px; }
.card:nth-child(6) { z-index: 6; top: 170px; }

.card-body {
  padding: 48px 48px 48px 52px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 20px;
  border-right: 1px solid var(--gold-line);
}

.card-number {
  font-family: 'Montserrat', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--gold-base);
  font-weight: 700;
}

.card-title {
  font-family: 'Kanit', sans-serif;
  font-size: 2rem;
  font-weight: 900;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--ink);
  line-height: 1.1;
}

.card-text {
  font-size: 1rem;
  font-weight: 400;
  color: var(--ink-2);
  line-height: 1.75;
  max-width: 420px;
}

.card-visual {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--gold-tint);
  position: relative;
  overflow: hidden;
}

.card-visual::before {
  content: attr(data-num);
  position: absolute;
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: 180px;
  color: rgba(212,175,55,0.08);
  line-height: 1;
  user-select: none;
}
html.light .card-visual::before { color: rgba(212,175,55,0.2); }

.card-icon {
  font-size: 64px;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 0 20px rgba(212,175,55,0.25));
}

.card::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 3px;
  background: var(--metallic);
  opacity: 0;
  transition: opacity 0.3s;
}
.card:hover::before { opacity: 1; }

.card-badge {
  display: inline-block;
  font-family: 'Montserrat', sans-serif;
  font-size: 0.65rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  font-weight: 700;
  padding: 4px 12px;
  border: 1px solid var(--gold-line-strong);
  color: var(--gold-text);
  border-radius: 20px;
  align-self: flex-start;
}

/* ════════════════════════════
   ANIMATIONS
════════════════════════════ */
@keyframes slideDown {
  from { transform: translateY(-100%); opacity: 0; }
  to   { transform: translateY(0);     opacity: 1; }
}
@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* ════════════════════════════
   RESPONSIVE
════════════════════════════ */
@media (max-width: 1024px) {
  header { grid-template-columns: auto 1fr auto; padding: 0 28px; gap: 20px; }
  .line-left { left: 30px; }
  .line-right { right: 30px; }
}

@media (max-width: 900px) {
  header { padding: 0 20px; grid-template-columns: 1fr auto; }
  header > nav,
  .header-right .btn-connexion { display: none; }
  #burger-trigger { display: inline-flex; }

  .line-left, .line-right { display: none; }
  .card { grid-template-columns: 1fr; min-height: auto; }
  .card-visual { display: none; }
  .card-body { padding: 36px 32px; border-right: none; }
  .card-title { font-size: 1.7rem; }
  .page-hero { padding-top: 130px; padding-bottom: 40px; }
}

@media (max-width: 600px) {
  header { padding: 0 16px; height: 64px; }
  .logo { font-size: 0.95rem; letter-spacing: 2px; }
  .icon-btn { width: 34px; height: 34px; }

  .page-hero { padding-top: 110px; }
  .page-hero .label { letter-spacing: 4px; font-size: 0.65rem; }

  .card { width: min(420px, 94vw); top: 80px; }
  .card:nth-child(1) { top: 80px; }
  .card:nth-child(2) { top: 92px; }
  .card:nth-child(3) { top: 104px; }
  .card:nth-child(4) { top: 116px; }
  .card:nth-child(5) { top: 128px; }
  .card:nth-child(6) { top: 140px; }
  .card-body { padding: 28px 24px; gap: 14px; }
  .card-title { font-size: 1.4rem; letter-spacing: 0.5px; }
  .card-text { font-size: 0.9rem; line-height: 1.65; }

  .cards-wrap { padding-bottom: 160px; }
}
</style>
</head>
<body>

<div class="line-left"></div>
<div class="line-right"></div>

<!-- ════ HEADER ════ -->
<header>
  <a href="index.php" class="logo">HESTIM</a>

  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="rules.php">Rules</a></li>
      <li><a href="game.php" class="btn-play">▶ Play</a></li>
      <li><a href="#classement">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
    </ul>
  </nav>
 


  <div class="header-right">
    <button id="theme-toggle" class="icon-btn" aria-label="Basculer le thème" type="button">
      <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
      </svg>
      <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
      </svg>
    </button>

    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="dashboard.php" class="btn-connexion">Dashboard</a>
    <?php else: ?>
      <a href="connexion.php" class="btn-connexion">Connexion</a>
    <?php endif; ?>

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
      <a href="index.php#classement" data-close class="drawer-link">
        <span>Classement</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="aboutus.php" data-close class="drawer-link">
        <span>About Us</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </nav>
    <div class="drawer-footer">
      <a href="game.html" data-close class="drawer-cta primary">▶ Jouer</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php" data-close class="drawer-cta secondary">Dashboard</a>
      <?php else: ?>
        <a href="connexion.php" data-close class="drawer-cta secondary">Connexion</a>
      <?php endif; ?>
      <p class="drawer-copy">&copy; 2025 &middot; HESTIM</p>
    </div>
  </aside>
</div>

<!-- ════ PAGE HERO ════ -->
<div class="page-hero">
  <p class="label">Manuel du joueur</p>
  <h1>Les <em>Règles</em> du jeu</h1>
  <div class="divider"></div>
</div>

<div class="spacer"></div>

<!-- ════ CARDS STACK ════ -->
<section class="cards-wrap">

  <div class="card">
    <div class="card-body">
      <span class="card-number">01 — Introduction</span>
      <h2 class="card-title">Bienvenue dans l'arène</h2>
      <p class="card-text">
        Question Champion est un jeu de culture générale compétitif. Affrontez d'autres joueurs, testez vos connaissances et grimpez dans le classement mondial.
      </p>
      <span class="card-badge">Guide de démarrage</span>
    </div>
    <div class="card-visual" data-num="01">
      <span class="card-icon">🏆</span>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <span class="card-number">02 — Présentation</span>
      <h2 class="card-title">Comment jouer</h2>
      <p class="card-text">
        Répondez à des questions de culture générale, seul ou en équipe. Le but : accumuler un maximum de points pour remporter la victoire. Chaque partie est unique.
      </p>
      <span class="card-badge">Multijoueur</span>
    </div>
    <div class="card-visual" data-num="02">
      <span class="card-icon">🎯</span>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <span class="card-number">03 — Scoring</span>
      <h2 class="card-title">Les points</h2>
      <p class="card-text">
        Chaque bonne réponse vous rapporte des points selon deux critères : la difficulté de la question et votre rapidité. Plus vous êtes rapide, plus le bonus est élevé.
      </p>
      <span class="card-badge">Système de score</span>
    </div>
    <div class="card-visual" data-num="03">
      <span class="card-icon">⚡</span>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <span class="card-number">04 — Mode Solo</span>
      <h2 class="card-title">Défi personnel</h2>
      <p class="card-text">
        Jouez à votre rythme et battez votre propre record. Une série de questions dans un temps imparti — l'objectif est d'obtenir le meilleur score possible et d'entrer dans le classement.
      </p>
      <span class="card-badge">Solo</span>
    </div>
    <div class="card-visual" data-num="04">
      <span class="card-icon">🧠</span>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <span class="card-number">05 — Mode 1v1</span>
      <h2 class="card-title">Duel individuel</h2>
      <p class="card-text">
        Affrontez un adversaire en tête-à-tête. Les deux joueurs répondent aux mêmes questions simultanément. Celui qui répond correctement le plus vite remporte la manche.
      </p>
      <span class="card-badge">Compétitif</span>
    </div>
    <div class="card-visual" data-num="05">
      <span class="card-icon">⚔️</span>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <span class="card-number">06 — Mode Tournoi</span>
      <h2 class="card-title">L'ultime compétition</h2>
      <p class="card-text">
        Éliminations progressives, brackets dynamiques, classement général. Seul le meilleur survivra à toutes les manches pour décrocher le titre de champion.
      </p>
      <span class="card-badge">Élimination</span>
    </div>
    <div class="card-visual" data-num="06">
      <span class="card-icon">🏆</span>
    </div>
  </div>

</section>

<script>
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
    try { localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); } catch (e) {}
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

  menu.querySelectorAll('[data-close]').forEach(el => {
    el.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) closeMenu();
  });
})();
</script>
</body>
</html>
