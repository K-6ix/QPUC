<?php require_once __DIR__ . '/csrf.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — Question Champion</title>

<!-- ════ ANTI-FLASH : applique le thème avant le render ════ -->
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
    /* GOLD — identité, immuable */
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
    --parallax-bg: #000;

    /* INK */
    --ink: #ffffff;
    --ink-2: rgba(255,255,255,0.55);
    --ink-3: rgba(255,255,255,0.35);
    --ink-4: rgba(255,255,255,0.2);
    --ink-5: rgba(255,255,255,0.1);

    /* LIGNES */
    --line: rgba(255,255,255,0.1);
    --line-soft: rgba(255,255,255,0.05);

    /* DORÉS d'accent */
    --gold-line: rgba(212,175,55,0.15);
    --gold-line-strong: rgba(212,175,55,0.35);
    --gold-tint: rgba(212,175,55,0.05);
    --gold-tint-2: rgba(212,175,55,0.1);
    --gold-text: var(--gold-light);
    --on-gold: #000;

    /* MISC */
    --noise-opacity: 0.03;
    --shadow-deep: rgba(0,0,0,0.5);
  }

  /* ════════════════════════════════════════════════════════════
    TOKENS — Light mode
  ═══════════════════════════════════════════════════════════ */
  html.light {
    --bg: #ffffff;
    --bg2: #f5f5f3;
    --header-bg: rgba(255,255,255,0.88);
    --card: #ffffff;
    --parallax-bg: #1a1a1a;   /* On garde un fond sombre pour la parallax (lisibilité photos) */

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

  /* ════ TRANSITION DOUCE pendant le switch ════ */
  .theme-transitioning,
  .theme-transitioning * {
    transition: background-color 0.25s ease,
                border-color 0.25s ease,
                color 0.25s ease,
                fill 0.25s ease,
                stroke 0.25s ease !important;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  /* scroll-behavior: auto — Lenis gère le smooth scroll lui-même.
    Surtout pas smooth ici sinon conflit avec Lenis et scroll qui rame. */
  html { scroll-behavior: auto; }

  body {
    background: var(--bg);
    color: var(--ink);
    font-family: 'Montserrat', sans-serif;
    overflow-x: hidden;
  }

  /* noise overlay */
  body::before {
    content:'';
    position:fixed; inset:0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
    opacity: var(--noise-opacity);
    pointer-events: none;
    z-index: 9999;
    mix-blend-mode: multiply;
  }
  html.light body::before { opacity: 0.04; }

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

  /* Cluster droite */
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
    min-height: 52vh;
    padding-top: calc(72px + 10px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .page-hero::before {
    content:'';
    position:absolute;
    top:50%; left:50%;
    transform:translate(-50%,-60%);
    width:600px; height:600px;
    background:radial-gradient(circle, rgba(212,175,55,0.1) 0%, transparent 70%);
    pointer-events:none;
  }

  .hero-ring {
    position:absolute;
    border-radius:50%;
    border:1px solid var(--gold-tint-2);
    top:50%; left:50%;
    transform:translate(-50%,-50%);
    pointer-events:none;
  }
  .hero-ring:nth-child(1){ width:400px; height:400px; }
  .hero-ring:nth-child(2){ width:650px; height:650px; animation: spin 30s linear infinite; }
  .hero-ring:nth-child(3){ width:900px; height:900px; animation: spin 50s linear infinite reverse; }

  @keyframes spin { to { transform: translate(-50%,-50%) rotate(360deg); } }

  .hero-eyebrow {
    font-size:0.7rem; letter-spacing:6px; text-transform:uppercase;
    color:var(--gold-base); margin-bottom:20px;
    opacity:0; animation:fadeUp 0.8s ease 0.3s forwards;
  }
  .hero-title {
    font-family:'Kanit', sans-serif;
    font-weight:900;
    font-size: clamp(3rem, 8vw, 6rem);
    letter-spacing:4px; text-transform:uppercase;
    line-height:1;
    color: var(--ink);
    opacity:0; animation:fadeUp 0.9s ease 0.5s forwards;
  }
  .hero-title em {
    font-style:normal;
    background:var(--metallic);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip: text;
    filter:drop-shadow(0 0 10px var(--gold-glow));
  }
  .hero-sub {
    margin-top:18px;
    font-size:0.85rem; color:var(--ink-3);
    letter-spacing:2px; max-width:500px;
    opacity:0; animation:fadeUp 0.8s ease 0.7s forwards;
  }
  .hero-divider {
    width:60px; height:2px;
    background:var(--metallic);
    margin:32px auto 0;
    opacity:0; animation:fadeUp 0.8s ease 0.9s forwards;
  }

  /* ════════════════════════════
    INTRO BAND
  ════════════════════════════ */
  .intro-band {
    border-top:1px solid var(--gold-line);
    border-bottom:1px solid var(--gold-line);
    background:var(--gold-tint);
    padding:60px 40px;
    text-align:center;
    position: relative;
    overflow: hidden;
  }
  .intro-band::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at center, rgba(212,175,55,0.05) 0%, transparent 70%);
    pointer-events: none;
  }
  .intro-band p {
    max-width:680px;
    margin:0 auto;
    font-size:1.05rem;
    line-height:1.9;
    color:var(--ink-2);
    font-weight:400;
    letter-spacing:0.5px;
    position: relative;
  }
  .intro-band strong {
    color:var(--gold-text);
    font-weight:700;
  }

    /* ════════════════════════════
    TEAM SECTION — Hashgraph VC inspired
  ════════════════════════════ */
  .team-section {
    padding: 100px 40px;
    max-width: 1280px;
    margin: 0 auto;
  }
  .team-panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: start;
  }
  .team-left {
    padding-top: 20px;
  }
  .team-label {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.7rem;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--gold-base);
    margin-bottom: 24px;
    opacity: 0.8;
  }
  .team-headline {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: clamp(2.2rem, 4vw, 3.6rem);
    line-height: 1.05;
    text-transform: uppercase;
    color: var(--ink);
    margin-bottom: 60px;
    font-style: italic;
  }
  .team-headline em {
    font-style: italic;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .team-list {
    display: flex;
    flex-direction: column;
  }
  .team-row {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 26px 0;
    border-bottom: 1px solid var(--gold-line);
    cursor: pointer;
    transition: background 0.3s ease, padding-left 0.3s ease;
    position: relative;
    flex-wrap: wrap;
  }
  .team-row:first-child {
    border-top: 1px solid var(--gold-line);
  }
  .team-row:hover,
  .team-row.active {
    background: var(--gold-tint);
    padding-left: 12px;
  }
  .team-row-num {
    font-family: 'Kanit', sans-serif;
    font-size: 1.1rem;
    font-weight: 900;
    color: var(--ink-3);
    letter-spacing: 1px;
    min-width: 36px;
    flex-shrink: 0;
  }
  .team-row:hover .team-row-num,
  .team-row.active .team-row-num {
    color: var(--gold-base);
  }
  .team-row-role {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--ink-3);
    font-variant: small-caps;
    min-width: 150px;
    flex-shrink: 0;
  }
  .team-row:hover .team-row-role,
  .team-row.active .team-row-role {
    color: var(--gold-text);
  }
  .team-row-name {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1.15rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    flex: 1;
    white-space: nowrap;
  }
  .team-row-skills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .team-row-skill {
    font-size: 0.6rem;
    letter-spacing: 1px;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid var(--gold-line);
    color: var(--gold-text);
    text-transform: uppercase;
    font-family: 'Montserrat', sans-serif;
  }
  .team-right {
    background: #04080f;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
    min-height: 520px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--gold-line);
  }
  .team-orbit {
    position: relative;
    width: 70%;
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .team-orbe {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, rgba(100, 180, 255, 0.25), rgba(40, 100, 180, 0.15) 40%, transparent 70%);
    box-shadow: 0 0 80px rgba(60, 130, 220, 0.2), inset 0 0 60px rgba(80, 160, 255, 0.1);
    animation: teamPulse 4s ease-in-out infinite;
    position: relative;
  }
  .team-orbe::after {
    content: '';
    position: absolute;
    inset: -20%;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(60, 130, 220, 0.08) 0%, transparent 60%);
    filter: blur(20px);
  }
  @keyframes teamPulse {
    0%, 100% { transform: scale(1); opacity: 0.9; }
    50% { transform: scale(1.06); opacity: 1; }
  }
  .team-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: clamp(2rem, 5vw, 4rem);
    letter-spacing: 6px;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.06);
    pointer-events: none;
    user-select: none;
    text-align: center;
  }
  .team-preview {
    position: absolute;
    bottom: 28px;
    left: 28px;
    display: flex;
    align-items: center;
    gap: 16px;
    background: rgba(4, 8, 15, 0.7);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid var(--gold-line);
    border-radius: 14px;
    padding: 14px 20px;
    transition: opacity 0.3s ease, transform 0.3s ease;
  }
  .team-preview-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid var(--gold-line-strong);
    background: var(--gold-tint-2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1rem;
    color: var(--gold-base);
    flex-shrink: 0;
  }
  .team-preview-role {
    font-size: 0.6rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--ink-3);
    margin-bottom: 4px;
    font-variant: small-caps;
  }
  .team-preview-name {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 0.95rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  @media (max-width: 860px) {
    .team-panels {
      grid-template-columns: 1fr;
    }
    .team-right {
      height: 55vw;
      min-height: auto;
    }
    .team-orbit {
      width: 50%;
    }
    .team-headline {
      margin-bottom: 40px;
    }
    .team-row {
      gap: 12px;
      padding: 20px 0;
    }
    .team-row-role {
      min-width: auto;
      width: 100%;
      order: 3;
    }
    .team-row-name {
      order: 2;
      width: 100%;
    }
    .team-row-skills {
      order: 4;
      width: 100%;
      margin-top: 4px;
    }
  }

  /* ════════════════════════════
    SECTION TAG / TITLE (shared)
  ════════════════════════════ */
.section-tag {
    font-size:0.65rem; letter-spacing:5px; text-transform:uppercase;
    color:var(--gold-base);
    display:flex; align-items:center; gap:12px;
    margin-bottom:16px;
  }
  .section-tag::before, .section-tag::after {
    content:''; height:1px; width:40px;
    background:var(--gold-base); opacity:0.4;
  }
  
.section-title {
    font-family:'Kanit', sans-serif; font-weight:900;
    font-size:clamp(2rem, 5vw, 3.2rem);
    letter-spacing:2px; text-transform:uppercase;
    line-height:1.1; margin-bottom:70px;
    color: var(--ink);
  }
  .section-title em {
    font-style:normal;
    background:var(--metallic);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip: text;
  }

  /* ════════════════════════════
    PROJECT BAND
  ════════════════════════════ */
  .project-band {
    background:var(--bg2);
    border-top:1px solid var(--gold-line);
    border-bottom:1px solid var(--gold-line);
    padding:80px 40px;
  }
  .project-inner {
    max-width:1200px;
    margin:0 auto;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:60px;
    align-items:center;
  }
  .project-title {
    font-family:'Great Vibes', cursive;
    font-size:3.5rem;
    background:var(--metallic);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip: text;
    filter:drop-shadow(0 0 8px var(--gold-glow));
    margin-bottom:20px;
    line-height:1.2;
  }
  .project-body {
    font-size:0.85rem;
    color:var(--ink-2);
    line-height:1.9;
  }
  .project-body strong { color: var(--gold-text); font-weight:700; }

  .project-stats {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
  }
  .pstat {
    background:var(--gold-tint);
    border:1px solid var(--gold-line);
    border-radius:14px;
    padding:28px 24px;
    text-align:center;
    transition:border-color 0.3s, transform 0.3s;
  }
  .pstat:hover {
    border-color:var(--gold-line-strong);
    transform:translateY(-4px);
  }
  .pstat-num {
    font-family:'Kanit', sans-serif;
    font-weight:900;
    font-size:2.2rem;
    background:var(--metallic);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip: text;
    line-height:1;
    margin-bottom:6px;
  }
  .pstat-label {
    font-size:0.65rem;
    letter-spacing:3px;
    text-transform:uppercase;
    color:var(--ink-3);
  }

  /* ════════════════════════════
    HESTIM SECTION
  ════════════════════════════ */
  .hestim-section {
    padding: 100px 40px;
    border-top: 1px solid var(--gold-line);
  }
  .hestim-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: start;
  }
  .hestim-eyebrow {
    font-size: 0.65rem;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--gold-base);
    margin-bottom: 16px;
  }
  .hestim-title {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: clamp(2.5rem, 5vw, 4rem);
    letter-spacing: 2px;
    text-transform: uppercase;
    line-height: 1.05;
    margin-bottom: 28px;
    color: var(--ink);
  }
  .hestim-title em {
    font-style: normal;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .hestim-body {
    font-size: 0.85rem;
    color: var(--ink-2);
    line-height: 1.9;
  }
  .hestim-body strong { color: var(--ink); font-weight: 700; }

  .hestim-right {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .hestim-card {
    background: var(--card);
    border: 1px solid var(--gold-line);
    border-radius: 14px;
    padding: 22px 24px;
    display: flex;
    align-items: flex-start;
    gap: 18px;
    transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
  }
  .hestim-card:hover {
    border-color: var(--gold-line-strong);
    transform: translateX(8px);
    box-shadow: -4px 0 20px rgba(212,175,55,0.06);
  }
  .hestim-card-icon { font-size: 1.5rem; flex-shrink: 0; margin-top: 2px; }
  .hestim-card-title {
    font-size: 0.85rem;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--gold-text);
    margin-bottom: 5px;
  }
  .hestim-card-desc {
    font-size: 0.78rem;
    color: var(--ink-2);
    line-height: 1.6;
  }

  /* ════════════════════════════
    FOOTER
  ════════════════════════════ */
  footer {
    position:relative;
    background:var(--bg2);
    border-top:1px solid var(--gold-line);
    overflow:hidden;
  }
  footer::before {
    content:'';
    position:absolute;
    top:0; left:-100%;
    width:100%; height:2px;
    background:var(--metallic);
    animation:footerLine 3s ease-in-out infinite;
  }
  @keyframes footerLine {
    0%   { left:-100%; opacity:0; }
    20%  { opacity:1; }
    80%  { opacity:1; }
    100% { left:100%; opacity:0; }
  }
  .footer-top {
    display:grid;
    grid-template-columns:1fr auto 1fr;
    align-items:center;
    padding:48px 60px 32px;
    gap:40px;
    border-bottom:1px solid var(--gold-tint);
  }
  .footer-logo {
    font-family:'Kanit', sans-serif;
    font-weight:900; font-size:1.4rem;
    letter-spacing:4px;
    background:var(--metallic);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip: text;
    text-transform:uppercase;
    display:block; margin-bottom:8px;
  }
  .footer-tagline {
    font-size:0.7rem; letter-spacing:3px;
    color:var(--ink-4); text-transform:uppercase;
  }
  .footer-nav {
    display:flex; flex-direction:column;
    align-items:center; gap:14px; list-style:none;
  }
  .footer-nav a {
    text-decoration:none; font-size:0.75rem;
    letter-spacing:3px; color:var(--ink-3);
    text-transform:uppercase; font-weight:700;
    transition:color 0.3s; position:relative;
  }
  .footer-nav a::after {
    content:''; position:absolute;
    width:0; height:1px; bottom:-3px; left:50%;
    transform:translateX(-50%);
    background:var(--gold-base); transition:width 0.3s;
  }
  .footer-nav a:hover { color:var(--gold-text); }
  .footer-nav a:hover::after { width:100%; }
  .footer-cta-col { display:flex; justify-content:flex-end; }
  .footer-play-btn {
    display:inline-flex; align-items:center; gap:10px;
    background:var(--metallic); color:var(--on-gold);
    padding:12px 28px; border-radius:40px;
    font-weight:900; font-size:0.8rem;
    letter-spacing:2px; text-transform:uppercase;
    text-decoration:none;
    box-shadow:0 0 20px var(--gold-glow);
    transition:transform 0.2s, box-shadow 0.2s;
  }
  .footer-play-btn:hover {
    transform:scale(1.05) translateY(-2px);
    box-shadow:0 0 35px rgba(212,175,55,0.6);
  }
  .footer-play-icon {
    width:28px; height:28px;
    background:rgba(0,0,0,0.2);
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:0.7rem;
  }
  .footer-bottom {
    display:flex; justify-content:space-between; align-items:center;
    padding:20px 60px; flex-wrap:wrap; gap:12px;
  }
  .footer-copy {
    font-size:0.65rem; letter-spacing:2px;
    color:var(--ink-4); text-transform:uppercase;
  }
  .footer-school {
    font-size:0.65rem; letter-spacing:2px;
    color:var(--gold-text); text-transform:uppercase;
    opacity: 0.6;
  }

  /* ════════════════════════════
    ANIMATIONS
  ════════════════════════════ */
  @keyframes slideDown {
    from { transform:translateY(-100%); opacity:0; }
    to   { transform:translateY(0);     opacity:1; }
  }
  @keyframes fadeUp {
    from { transform:translateY(30px); opacity:0; }
    to   { transform:translateY(0);    opacity:1; }
  }

  .reveal {
    opacity:0;
    transform:translateY(40px);
    transition:opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal.visible { opacity:1; transform:translateY(0); }

  .team-card:nth-child(1) { transition-delay: 0s; }
  .team-card:nth-child(2) { transition-delay: 0.1s; }
  .team-card:nth-child(3) { transition-delay: 0.2s; }

  @keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .pstat-num.counted { animation: countUp 0.5s ease forwards; }

  /* ════════════════════════════
    PARALLAX (intro / container / sticky / el / outro)
  ════════════════════════════ */
  .intro, .outro {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--parallax-bg);
    color: var(--ink);
    font-family: 'Helvetica Neue', sans-serif;
    font-size: 0.85rem;
    letter-spacing: 6px;
    text-transform: uppercase;
    opacity: 0.6;
    position: relative;
    z-index: 2;
  }
  html.light .intro,
  html.light .outro { color: #ffffff; }

  .container {
    height: 300vh;
    position: relative;
    background: var(--parallax-bg);
  }

  .sticky {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow: hidden;
    background: var(--parallax-bg);
  }

  .el {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    will-change: transform;
    transform-origin: center center;
  }

  .img-wrap {
    position: absolute;
    overflow: hidden;
  }
  .img-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
  }

  /* positions exactes du parallax (préservées du fichier original) */
  .el:nth-child(1) .img-wrap {
    width: 25vw; height: 25vh;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
  }
  .el:nth-child(2) .img-wrap {
    width: 35vw; height: 30vh;
    top: calc(50% - 30vh);
    left: calc(50% + 5vw);
    transform: translate(-50%, -50%);
  }
  .el:nth-child(3) .img-wrap {
    width: 20vw; height: 45vh;
    top: calc(50% - 10vh);
    left: calc(50% - 25vw);
    transform: translate(-50%, -50%);
  }
  .el:nth-child(4) .img-wrap {
    width: 25vw; height: 25vh;
    top: 50%;
    left: calc(50% + 27.5vw);
    transform: translate(-50%, -50%);
  }
  .el:nth-child(5) .img-wrap {
    width: 20vw; height: 25vh;
    top: calc(50% + 27.5vh);
    left: calc(50% + 5vw);
    transform: translate(-50%, -50%);
  }
  .el:nth-child(6) .img-wrap {
    width: 30vw; height: 25vh;
    top: calc(50% + 27.5vh);
    left: calc(50% - 22.5vw);
    transform: translate(-50%, -50%);
  }
  .el:nth-child(7) .img-wrap {
    width: 15vw; height: 15vh;
    top: calc(50% + 22.5vh);
    left: calc(50% + 25vw);
    transform: translate(-50%, -50%);
  }

  /* ════════════════════════════
    RESPONSIVE
  ════════════════════════════ */
  @media (max-width: 1024px) {
    header { grid-template-columns: auto 1fr auto; padding: 0 28px; gap: 20px; }
    .team-section { padding: 80px 28px; }
    .project-band { padding: 70px 28px; }
    .hestim-section { padding: 80px 28px; }
    .footer-top { padding: 40px 32px 28px; gap: 28px; }
    .footer-bottom { padding: 18px 32px; }
  }

  @media (max-width: 900px) {
    header { padding: 0 20px; grid-template-columns: 1fr auto; }
    header > nav,
    .header-right .btn-connexion { display: none; }
    #burger-trigger { display: inline-flex; }

    .team-grid { grid-template-columns: 1fr; max-width: 480px; margin-left: auto; margin-right: auto; }
    .team-card.lead { grid-column: span 1; }
    .project-inner { grid-template-columns: 1fr; gap: 40px; }
    .hestim-inner { grid-template-columns: 1fr; gap: 40px; }
    .footer-top { grid-template-columns: 1fr; text-align: center; padding: 40px 24px 24px; }
    .footer-cta-col { justify-content: center; }
    .footer-nav { flex-direction: row; flex-wrap: wrap; justify-content: center; }
    .footer-bottom { flex-direction: column; text-align: center; padding: 16px 24px; }

    .team-section, .project-band { padding: 70px 20px; }
    .hestim-section { padding: 70px 20px; }
    .intro-band { padding: 50px 20px; }
  }

  @media (max-width: 600px) {
    header { padding: 0 16px; height: 64px; }
    .logo { font-size: 0.95rem; letter-spacing: 2px; }
    .icon-btn { width: 34px; height: 34px; }

    .page-hero { padding-top: calc(64px + 20px); }
    .hero-ring:nth-child(1){ width:280px; height:280px; }
    .hero-ring:nth-child(2){ width:420px; height:420px; }
    .hero-ring:nth-child(3){ width:560px; height:560px; }
    .hero-sub { font-size: 0.75rem; letter-spacing: 1.5px; padding: 0 16px; }

    .project-stats { grid-template-columns: 1fr 1fr; gap: 12px; }
    .pstat { padding: 20px 16px; }
    .pstat-num { font-size: 1.8rem; }

    .team-section, .project-band { padding: 56px 16px; }
    .hestim-section { padding: 56px 16px; }
    .intro-band { padding: 40px 16px; }
    .intro-band p { font-size: 0.92rem; line-height: 1.7; }

    .project-title { font-size: 2.6rem; }
    .section-title { margin-bottom: 40px; }

    .team-card { padding: 28px 22px; }
    .hestim-card { padding: 18px 18px; gap: 14px; }

    /* Parallax mobile : intro/outro avec font + letter spacing réduit */
    .intro, .outro { font-size: 0.7rem; letter-spacing: 4px; padding: 0 24px; text-align: center; }
  }
/* ════════════════════════════════════════════════════════════
   TEAM SCENE — Section fullscreen façon Hashgraph
═══════════════════════════════════════════════════════════ */
.scene {
  position: relative;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: transparent;
}
#team-canvas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}
.scene-fade-left {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(5,4,3,0.85) 0%, rgba(5,4,3,0.6) 28%, rgba(5,4,3,0.2) 45%, transparent 60%);
  z-index: 2;
  pointer-events: none;
}
.scene-text {
  position: absolute;
  top: 50%;
  left: 56px;
  transform: translateY(-50%);
  z-index: 5;
  max-width: 540px;
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.scene-text.swapping {
  opacity: 0;
  transform: translateY(-50%) translateX(-12px);
}
.scene-headline {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-style: italic;
  font-size: clamp(2rem, 3.3vw, 2.9rem);
  line-height: 1;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--ink);
  margin-bottom: 48px;
  text-shadow: 0 2px 30px rgba(0,0,0,0.6);
}
.scene-headline .l2 {
  display: block;
  padding-left: 1.4em;
  margin-top: 8px;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.scene-paragraph {
  font-size: 0.82rem;
  color: var(--ink-2);
  line-height: 1.85;
  max-width: 360px;
}
.scene-paragraph p + p { margin-top: 16px; }
.scene-member {
  position: absolute;
  bottom: 36px;
  right: 56px;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 20px;
}
.member-arrows {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.arrow-btn {
  width: 34px;
  height: 32px;
  border: 1px solid var(--ink-3);
  background: transparent;
  color: var(--ink-2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.2s, color 0.2s, background 0.2s, transform 0.15s;
  border-radius: 2px;
}
.arrow-btn:hover {
  border-color: var(--gold-base);
  color: var(--gold-text);
  background: var(--gold-tint);
}
.arrow-btn:active { transform: scale(0.92); }
.arrow-btn svg { width: 13px; height: 13px; }
.member-text {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
  transition: opacity 0.35s ease, transform 0.35s ease;
}
.member-text.swapping {
  opacity: 0;
  transform: translateX(8px);
}
.member-name {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: 1.7rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--ink);
  line-height: 1;
  white-space: nowrap;
}
.member-dots {
  display: flex;
  gap: 8px;
  align-items: center;
}
.member-dots span {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  border: 1px solid var(--ink-3);
  background: transparent;
  cursor: pointer;
  transition: background 0.3s ease, border-color 0.3s ease, transform 0.25s ease, box-shadow 0.3s ease;
}
.member-dots span:hover { border-color: var(--gold-base); transform: scale(1.15); }
.member-dots span.active {
  background: var(--gold-base);
  border-color: var(--gold-base);
  transform: scale(1.25);
  box-shadow: 0 0 10px var(--gold-glow);
  animation: dotPulse 2.5s ease-in-out infinite;
}
@keyframes dotPulse {
  0%, 100% { box-shadow: 0 0 8px var(--gold-glow); }
  50%      { box-shadow: 0 0 14px var(--gold-glow), 0 0 4px var(--gold-base); }
}
.scene-indicator {
  position: absolute;
  right: 20px;
  top: 30%;
  bottom: 25%;
  width: 1px;
  background: linear-gradient(to bottom, transparent, var(--gold-line-strong) 40%, var(--gold-line-strong) 60%, transparent);
  z-index: 4;
}
.scene-indicator::before {
  content: '';
  position: absolute;
  left: -1px;
  top: 50%;
  width: 3px;
  height: 60px;
  background: var(--gold-base);
  box-shadow: 0 0 12px var(--gold-glow);
  transform: translateY(-50%);
  animation: sceneIndicatorPulse 2.5s ease-in-out infinite;
}
@keyframes sceneIndicatorPulse {
  0%, 100% { opacity: 0.5; }
  50%      { opacity: 1; }
}
@media (max-width: 700px) {
  .scene-fade-left {
    background: linear-gradient(180deg,
      rgba(5,4,3,0.9) 0%,
      rgba(5,4,3,0.55) 15%,
      transparent 35%,
      transparent 55%,
      rgba(5,4,3,0.7) 72%,
      rgba(5,4,3,0.95) 100%);
  }
  .scene-text {
    position: absolute;
    top: 0; bottom: 0; left: 0; right: 0;
    transform: none; margin: 0; max-width: none;
  }
  .scene-text.swapping { transform: translateX(-12px); }
  .scene-headline {
    position: absolute;
    top: 70px; left: 24px; right: 24px; margin: 0;
    font-size: clamp(1.6rem, 5.5vw, 2.1rem);
  }
  .scene-headline .l2 { padding-left: 1.1em; }
  .scene-paragraph {
    position: absolute;
    bottom: 110px; left: 24px; right: 24px;
    max-width: none;
    font-size: 0.88rem; line-height: 1.7;
  }
  .scene-paragraph p + p { margin-top: 14px; }
  .scene-member { bottom: 22px; right: 24px; gap: 12px; }
  .member-name { font-size: 1.1rem; letter-spacing: 1.5px; }
  .arrow-btn { width: 28px; height: 26px; }
  .arrow-btn svg { width: 11px; height: 11px; }
  .scene-indicator { display: none; }
}
@media (max-width: 480px) {
  .scene-headline { top: 55px; font-size: 1.45rem; }
  .scene-paragraph { bottom: 95px; font-size: 0.82rem; line-height: 1.65; }
  .scene-paragraph p:nth-child(n+2) { display: none; }
  .member-name { font-size: 0.95rem; letter-spacing: 1px; }
}

/* ════════════════════════════════════════════════════════════
   GLOBAL SMOKE BACKGROUND
   Canvas fixé derrière toute la page (fumée gold animée)
═══════════════════════════════════════════════════════════ */
#global-smoke-bg {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  z-index: -10;
  pointer-events: none;
}
/* Sections opaques deviennent transparentes pour laisser passer la fumée */
body { background: transparent !important; }
html { background: #020100; }
html.light { background: #f6f1e6; }
.project-band { background: transparent; }
footer { background: transparent; }
/* Parallax : retire le fond noir partout pour laisser passer la fumée */
.container,
.sticky,
.intro,
.outro { background: transparent !important; }
/* HESTIM cards : semi-transparentes avec blur pour rester lisibles tout en laissant voir la fumée */
.hestim-card {
  background: rgba(13, 13, 13, 0.45) !important;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}
html.light .hestim-card {
  background: rgba(255, 255, 255, 0.55) !important;
}

/* ════ LIGHT MODE — Overrides team scene ════ */
html.light .scene-fade-left {
  background: linear-gradient(90deg, rgba(246,241,230,0.9) 0%, rgba(246,241,230,0.6) 28%, rgba(246,241,230,0.2) 45%, transparent 60%);
}
@media (max-width: 700px) {
  html.light .scene-fade-left {
    background: linear-gradient(180deg,
      rgba(246,241,230,0.92) 0%,
      rgba(246,241,230,0.55) 15%,
      transparent 35%,
      transparent 55%,
      rgba(246,241,230,0.7) 72%,
      rgba(246,241,230,0.95) 100%);
  }
}
html.light .scene-headline { text-shadow: 0 2px 24px rgba(255,255,255,0.5); }

/* ════ LISIBILITÉ — ombres + teinte argentée pour les textes de la team scene ════ */
/* Dark mode : argenté (champagne) au lieu de blanc pur, avec ombre dark pour sortir du bg gold */
.scene .scene-headline .l1,
.scene .member-name {
  color: #f0ece4;  /* champagne très clair, plus chaleureux que blanc pur */
  text-shadow: 0 2px 24px rgba(0,0,0,0.8), 0 0 6px rgba(0,0,0,0.5);
}
.scene .scene-paragraph {
  color: rgba(240, 236, 228, 0.85);  /* champagne légèrement transparent */
  text-shadow: 0 1px 10px rgba(0,0,0,0.7);
}
/* Le rôle en gradient gold a besoin d'un drop-shadow filter (text-shadow ne marche pas avec background-clip) */
.scene .scene-headline .l2 {
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.7)) drop-shadow(0 0 4px rgba(0,0,0,0.4));
}

/* Light mode : noir pour les textes + halo blanc subtil pour les détacher du bg crème */
html.light .scene .scene-headline .l1,
html.light .scene .member-name {
  color: #050403;
  text-shadow: 0 1px 10px rgba(255,255,255,0.55);
}
html.light .scene .scene-paragraph {
  color: #1a1410;
  text-shadow: 0 1px 6px rgba(255,255,255,0.45);
}
html.light .scene .scene-headline .l2 {
  filter: drop-shadow(0 1px 5px rgba(255,255,255,0.7)) drop-shadow(0 0 2px rgba(255,255,255,0.4));
}

/* ════════════════════════════════════════════════════════════
   TEAM SCENE — Apparition cascadée des textes au scroll
═══════════════════════════════════════════════════════════ */
.scene .scene-headline .l1,
.scene .scene-headline .l2,
.scene .scene-paragraph,
.scene .scene-member,
.scene .scene-indicator {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.8s ease, transform 0.8s ease;
  will-change: opacity, transform;
}
.scene .scene-headline .l1 { display: block; }

.scene.in-view .scene-headline .l1 { opacity: 1; transform: translateY(0); transition-delay: 0.15s; }
.scene.in-view .scene-headline .l2 { opacity: 1; transform: translateY(0); transition-delay: 0.30s; }
.scene.in-view .scene-paragraph    { opacity: 1; transform: translateY(0); transition-delay: 0.45s; }
.scene.in-view .scene-member       { opacity: 1; transform: translateY(0); transition-delay: 0.55s; }
.scene.in-view .scene-indicator    { opacity: 1; transform: translateY(0); transition-delay: 0.70s; }

</style>
<base target="_blank">
</head>
<body>
<!-- ════ FOND GLOBAL : fumée gold animée ════ -->
<canvas id="global-smoke-bg"></canvas>

<!-- ════ HEADER ════ -->
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
      <a href="game.php" data-close class="drawer-cta primary">▶ Jouer</a>
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
<section class="page-hero">
  <div class="hero-ring"></div>
  <div class="hero-ring"></div>
  <div class="hero-ring"></div>
  <p class="hero-eyebrow">Derrière le projet</p>
  <h1 class="hero-title">L'<em>équipe</em></h1>
  <p class="hero-sub">3 étudiants en 1ère année informatique à HESTIM, unis par la passion du code.</p>
  <div class="hero-divider"></div>
</section>

<!-- ════ INTRO BAND ════ -->
<div class="intro-band reveal">
  <p>
    Dans le cadre du <strong>Cycle Ingénieur 2025/2026</strong>, nous avons conçu et développé
    <strong>Question pour un Champion</strong> — un jeu de culture générale multijoueur complet.
    Ce projet est le reflet de nos compétences, de notre travail d'équipe et de notre envie
    de livrer quelque chose dont on est <strong>vraiment fiers</strong>.
  </p>
</div>

<!-- ════ PARALLAX ════ -->
 <div class="intro">Scroll ↓</div>

<div class="container" id="container">
  <div class="sticky">
    <div class="el" id="el0"><div class="img-wrap"><img src="1.jpeg" alt=""></div></div>
    <div class="el" id="el1"><div class="img-wrap"><img src="2.jpeg" alt=""></div></div>
    <div class="el" id="el2"><div class="img-wrap"><img src="3.jpg"  alt=""></div></div>
    <div class="el" id="el3"><div class="img-wrap"><img src="4.jpg"  alt=""></div></div>
    <div class="el" id="el4"><div class="img-wrap"><img src="5.jpg"  alt=""></div></div>
    <div class="el" id="el5"><div class="img-wrap"><img src="6.jpg"  alt=""></div></div>
    <div class="el" id="el6"><div class="img-wrap"><img src="7.jpeg" alt=""></div></div>
  </div>
</div>

<div class="outro">↑ Scroll up</div> 

<!-- ════ HESTIM SECTION ════ -->
<div class="hestim-section reveal">
  <div class="hestim-inner">
    <div class="hestim-left">
      <p class="hestim-eyebrow">Notre école</p>
      <h2 class="hestim-title"><em>HESTIM</em><br>Casablanca</h2>
      <p class="hestim-body">
        <strong>HESTIM</strong> — Hautes Études en Sciences, Technologies, Ingénierie et Management —
        est une grande école d'ingénieurs basée à <strong>Casablanca, Maroc</strong>.<br><br>
        Fondée avec pour mission de former les ingénieurs et managers de demain,
        HESTIM propose des programmes alliant rigueur académique, innovation technologique
        et immersion professionnelle. <strong>Question pour un Champion</strong> est l'un des projets
        phares du Cycle Ingénieur 2025/2026, conçu pour mettre en pratique nos compétences
        en développement web full-stack.
      </p>
    </div>
    <div class="hestim-right">
      <div class="hestim-card">
        <span class="hestim-card-icon">🎓</span>
        <div>
          <div class="hestim-card-title">Grande École d'Ingénieurs</div>
          <p class="hestim-card-desc">Formation Bac+5 en ingénierie informatique, management et technologies avancées.</p>
        </div>
      </div>
      <div class="hestim-card">
        <span class="hestim-card-icon">📍</span>
        <div>
          <div class="hestim-card-title">Casablanca, Maroc</div>
          <p class="hestim-card-desc">Au cœur de la capitale économique du Maroc, un écosystème propice à l'innovation.</p>
        </div>
      </div>
      <div class="hestim-card">
        <span class="hestim-card-icon">💻</span>
        <div>
          <div class="hestim-card-title">Projets Pratiques</div>
          <p class="hestim-card-desc">Approche par projets réels dès la 1ère année — code, design, architecture logicielle.</p>
        </div>
      </div>
      <div class="hestim-card">
        <span class="hestim-card-icon">🌍</span>
        <div>
          <div class="hestim-card-title">Ouverture Internationale</div>
          <p class="hestim-card-desc">Une vision globale du développement et du management, tournée vers le monde.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ════ TEAM (Hashgraph-style fullscreen scene) ════ -->
<div class="scene" id="team-scene">
  <canvas id="team-canvas"></canvas>
  <div class="scene-fade-left"></div>

  <!-- TEXTE PRINCIPAL -->
  <div class="scene-text" id="scene-text">
    <h1 class="scene-headline" id="headline">
      <span class="l1">LOREM IPSUM</span>
      <span class="l2">RÉDACTEUR TECHNIQUE</span>
    </h1>
    <div class="scene-paragraph" id="paragraph">
      <p>Initialisation…</p>
    </div>
  </div>

  <!-- INDICATEUR DROITE -->
  <div class="scene-indicator"></div>

  <!-- MEMBRE ACTIF -->
  <div class="scene-member">
    <div class="member-arrows">
      <button class="arrow-btn" id="nav-prev" aria-label="Précédent">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="18 15 12 9 6 15"/>
        </svg>
      </button>
      <button class="arrow-btn" id="nav-next" aria-label="Suivant">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </button>
    </div>
    <div class="member-text" id="member-text">
      <div class="member-name" id="member-name">MAXIME BANG-KERA</div>
      <div class="member-dots" id="member-dots"></div>
    </div>
  </div>
</div>

<!-- Three.js pour la tête en particules de la team section -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script>
/* ════════════════════════════════════════════
   TEAM SECTION — Données + UI + Three.js
════════════════════════════════════════════ */
(function(){
const team = [
  {
    name: 'MAXIME BANG-KERA',
    role: 'RÉDACTEUR TECHNIQUE',
    headlineTop: 'LOREM IPSUM',
    paragraph: [
      'Pas de jargon inutile. Pas de docs bâclées. Chaque ligne écrite a un but : rendre le complexe lisible. Quand le code parle aux développeurs, la documentation parle à tout le reste.',
      'Un projet sans documentation, c\'est une fusée sans plan de vol. Transformer l\'architecture en récit clair, structuré, accessible à tous ceux qui croisent QPC.'
    ]
  },
  {
    name: 'OUSMANE NIASSE',
    role: 'DEV FULL STACK',
    headlineTop: 'CONSECTETUR',
    paragraph: [
      'Front, back, base de données. Pas de cloisonnement, pas de hand-off entre équipes. Le code part de l\'idée et arrive au pixel — en une chaîne ininterrompue.',
      'PHP, Node.js, JavaScript, MySQL — l\'outil suit le besoin, jamais l\'inverse. Construire vite, construire propre, construire pour que ça tienne dans le temps.'
    ]
  },
  {
    name: '3E MEMBRE',
    role: '— À RENSEIGNER —',
    headlineTop: 'PLACEHOLDER',
    paragraph: [
      '— Explications à ajouter ici —',
      'Présentation, rôle et contributions du 3e membre à compléter une fois les informations confirmées.'
    ]
  }
];

let activeIndex = 0;
let isTransitioning = false;

const sceneText  = document.getElementById('scene-text');
const memberText = document.getElementById('member-text');
const headlineEl = document.getElementById('headline');
const paragraphEl= document.getElementById('paragraph');
const memberNameEl = document.getElementById('member-name');
const dotsEl    = document.getElementById('member-dots');
const navPrev = document.getElementById('nav-prev');
const navNext = document.getElementById('nav-next');

/* ── Génère un dot par membre ── */
team.forEach((_, i) => {
  const dot = document.createElement('span');
  dot.addEventListener('click', () => {
    changeTo(i);
    resetAutoRotate();
  });
  dotsEl.appendChild(dot);
});

function renderMember(i) {
  const m = team[i];
  headlineEl.innerHTML = '<span class="l1">' + m.headlineTop + '</span><span class="l2">' + m.role + '</span>';
  paragraphEl.innerHTML = m.paragraph.map(p => '<p>' + p + '</p>').join('');
  memberNameEl.textContent = m.name;
  // sync dots active state
  dotsEl.querySelectorAll('span').forEach((d, idx) => {
    d.classList.toggle('active', idx === i);
  });
}

function changeTo(newIndex) {
  if (isTransitioning) return;
  if (newIndex < 0 || newIndex >= team.length) return;
  if (newIndex === activeIndex) return;

  isTransitioning = true;
  activeIndex = newIndex;

  sceneText.classList.add('swapping');
  memberText.classList.add('swapping');
  burstParticles();

  setTimeout(() => {
    renderMember(newIndex);
    sceneText.classList.remove('swapping');
    memberText.classList.remove('swapping');
    setTimeout(() => { isTransitioning = false; }, 400);
  }, 350);
}

navPrev.addEventListener('click', () => {
  changeTo((activeIndex - 1 + team.length) % team.length);
  resetAutoRotate();
});
navNext.addEventListener('click', () => {
  changeTo((activeIndex + 1) % team.length);
  resetAutoRotate();
});

window.addEventListener('keydown', e => {
  // n'active les flèches que si la team-scene est visible à l'écran
  const sceneRect = document.getElementById('team-scene').getBoundingClientRect();
  const isVisible = sceneRect.top < window.innerHeight && sceneRect.bottom > 0;
  if (!isVisible) return;
  if (e.key === 'ArrowUp')   { e.preventDefault(); changeTo((activeIndex - 1 + team.length) % team.length); resetAutoRotate(); }
  if (e.key === 'ArrowDown') { e.preventDefault(); changeTo((activeIndex + 1) % team.length); resetAutoRotate(); }
});

/* ── Auto-rotation toutes les 10s ── */
const AUTO_DELAY = 10000;
let autoTimer = null;
function autoNext() {
  if (isTransitioning) { autoTimer = setTimeout(autoNext, 500); return; }
  changeTo((activeIndex + 1) % team.length);
  scheduleAutoRotate();
}
function scheduleAutoRotate() {
  if (autoTimer) clearTimeout(autoTimer);
  autoTimer = setTimeout(autoNext, AUTO_DELAY);
}
function resetAutoRotate() { scheduleAutoRotate(); }

renderMember(0);
scheduleAutoRotate();

/* ── Observer : ajoute .in-view quand la scene entre dans le viewport
       (déclenche l'animation en cascade des textes) ── */
const teamSceneEl = document.getElementById('team-scene');
const teamObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) e.target.classList.add('in-view');
    else                  e.target.classList.remove('in-view');
  });
}, { threshold: 0.25 });
teamObs.observe(teamSceneEl);

/* ── Swipe gauche/droite sur mobile pour naviguer entre membres ── */
let touchStartX = 0;
let touchStartY = 0;
teamSceneEl.addEventListener('touchstart', e => {
  touchStartX = e.touches[0].clientX;
  touchStartY = e.touches[0].clientY;
}, { passive: true });
teamSceneEl.addEventListener('touchend', e => {
  const dx = e.changedTouches[0].clientX - touchStartX;
  const dy = e.changedTouches[0].clientY - touchStartY;
  // swipe horizontal uniquement (ignorer scroll vertical)
  if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
    if (dx > 0) {
      // swipe vers la droite → membre précédent
      changeTo((activeIndex - 1 + team.length) % team.length);
    } else {
      // swipe vers la gauche → membre suivant
      changeTo((activeIndex + 1) % team.length);
    }
    resetAutoRotate();
  }
}, { passive: true });

/* ════════════════════════════════════════════
   THREE.JS — Tête en particules + fumée
════════════════════════════════════════════ */
const canvas = document.getElementById('team-canvas');
const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.setClearColor(0x000000, 0);

const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 1000);

function setCameraPosition() {
  const isMobile = window.innerWidth < 700;
  camera.position.set(0, 0, isMobile ? 7.0 : 4.5);
  camera.lookAt(0, 0, 0);
}
setCameraPosition();

/* ── La fumée procédurale n'est PLUS dans le team canvas :
       elle tourne sur le canvas global derrière toute la page (cf. script suivant). ── */

/* ── Tête en particules ── */
function buildHeadParticles() {
  const N = 7000;
  const positions = new Float32Array(N * 3);
  const sizes     = new Float32Array(N);
  const drifts    = new Float32Array(N * 3);
  const seeds     = new Float32Array(N);
  let count = 0; let attempt = 0;
  const maxAttempts = N * 300;
  while (count < N && attempt < maxAttempts) {
    attempt++;
    const theta = Math.random() * Math.PI * 2;
    const phi   = Math.acos(2 * Math.random() - 1);
    const r     = 0.78 + Math.random() * 0.22;
    let x = r * Math.sin(phi) * Math.cos(theta);
    let y = r * Math.sin(phi) * Math.sin(theta) * 1.32;
    let z = r * Math.cos(phi) * 0.65;
    if (z < -0.35) continue;
    if (y < -0.7 && Math.abs(x) > 0.55 + (y + 0.7) * 0.5) continue;
    if (y < -0.95) {
      const neckR = Math.max(0.1, 0.22 * (1 - (y + 0.95) * 1.2));
      if (x * x + z * z > neckR * neckR) continue;
    }
    const isUpper = y > 0.1;
    if (!isUpper && Math.random() < 0.15) continue;
    positions[count * 3]     = x;
    positions[count * 3 + 1] = y;
    positions[count * 3 + 2] = z;
    const distCenter = Math.sqrt(x * x + y * y * 0.5);
    sizes[count] = 1.8 + (1.0 - Math.min(distCenter, 1.0)) * 3.5 + Math.random() * 1.8;
    drifts[count * 3]     = (Math.random() - 0.5) * 0.10;
    drifts[count * 3 + 1] = (Math.random() - 0.5) * 0.10;
    drifts[count * 3 + 2] = (Math.random() - 0.5) * 0.10;
    seeds[count] = Math.random() * 100;
    count++;
  }
  const geo = new THREE.BufferGeometry();
  geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  geo.setAttribute('size',     new THREE.BufferAttribute(sizes, 1));
  geo.setAttribute('drift',    new THREE.BufferAttribute(drifts, 3));
  geo.setAttribute('seed',     new THREE.BufferAttribute(seeds, 1));
  return geo;
}

const particleMat = new THREE.ShaderMaterial({
  transparent: true, depthWrite: false, blending: THREE.AdditiveBlending,
  uniforms: {
    uTime: { value: 0 }, uBurst: { value: 0 }, uHover: { value: 0 },
    uScrollPower: { value: 0 },
    uTheme: { value: document.documentElement.classList.contains('light') ? 1 : 0 },
    uPixelRatio: { value: Math.min(window.devicePixelRatio, 2) }
  },
  vertexShader: `
    attribute float size;
    attribute vec3  drift;
    attribute float seed;
    uniform   float uTime, uBurst, uHover, uScrollPower, uPixelRatio;
    varying   float vAlpha, vDepth;
    void main() {
      float driftAmp = 1.0 + uHover * 3.5;
      vec3 pos = position + drift * sin(uTime * 0.55 + seed * 6.28) * driftAmp;
      pos += normalize(position) * uBurst * 0.45;
      pos += normalize(position) * uScrollPower * 0.9;
      vDepth = pos.z;
      vAlpha = 0.4 + (pos.z + 0.4) * 0.5;
      vAlpha *= mix(1.0, 0.15, uScrollPower);
      vec4 mvPos = modelViewMatrix * vec4(pos, 1.0);
      gl_Position  = projectionMatrix * mvPos;
      gl_PointSize = size * uPixelRatio * (3.6 / -mvPos.z);
    }
  `,
  fragmentShader: `
    varying float vAlpha, vDepth;
    uniform float uTheme;
    void main() {
      vec2 uv = gl_PointCoord - 0.5;
      float d = length(uv);
      if (d > 0.5) discard;
      float soft = 1.0 - smoothstep(0.15, 0.5, d);
      // dark mode : crème claire à l'avant, doré profond à l'arrière
      vec3 colorFrontD = vec3(0.98, 0.95, 0.78);
      vec3 colorBackD  = vec3(0.65, 0.48, 0.16);
      // light mode : brun sombre à l'avant, doré dark à l'arrière (visible sur fond clair)
      vec3 colorFrontL = vec3(0.16, 0.12, 0.05);
      vec3 colorBackL  = vec3(0.55, 0.42, 0.18);
      vec3 colorFront = mix(colorFrontD, colorFrontL, uTheme);
      vec3 colorBack  = mix(colorBackD, colorBackL, uTheme);
      float mixF = clamp((vDepth + 0.4) * 1.2, 0.0, 1.0);
      vec3 color = mix(colorBack, colorFront, mixF);
      gl_FragColor = vec4(color, soft * vAlpha);
    }
  `
});

const headGeo = buildHeadParticles();
const head = new THREE.Points(headGeo, particleMat);
head.rotation.y = 0.18;
head.rotation.x = -0.05;
scene.add(head);

function updateHeadPosition() {
  const w = window.innerWidth;
  if (w < 700)       head.position.x = 0;
  else if (w < 1100) head.position.x = 0.6;
  else               head.position.x = 1.22;
}
updateHeadPosition();

/* ── Poussière ambiante ── */
const dustN = 600;
const dustPos = new Float32Array(dustN * 3);
for (let i = 0; i < dustN; i++) {
  dustPos[i*3]   = (Math.random() - 0.5) * 6;
  dustPos[i*3+1] = (Math.random() - 0.5) * 5;
  dustPos[i*3+2] = (Math.random() - 0.5) * 3 - 0.5;
}
const dustGeo = new THREE.BufferGeometry();
dustGeo.setAttribute('position', new THREE.BufferAttribute(dustPos, 3));
const dustMat = new THREE.PointsMaterial({
  color: 0xc4a04a, size: 0.014, transparent: true, opacity: 0.3,
  blending: THREE.AdditiveBlending, depthWrite: false
});
const dust = new THREE.Points(dustGeo, dustMat);
scene.add(dust);

/* ── Burst au changement de membre ── */
let burstStart = -1;
function burstParticles() { burstStart = performance.now(); }
function updateBurst(nowMs) {
  if (burstStart < 0) return;
  const t = (nowMs - burstStart) / 700;
  if (t >= 1) { particleMat.uniforms.uBurst.value = 0; burstStart = -1; return; }
  const peak = t < 0.3 ? (t / 0.3) : (1 - (t - 0.3) / 0.7);
  particleMat.uniforms.uBurst.value = peak * 0.6;
}

/* ── Resize : adapté à la taille du canvas (pas window) ── */
function resize() {
  const sceneEl = document.getElementById('team-scene');
  const w = sceneEl.clientWidth;
  const h = sceneEl.clientHeight;
  renderer.setSize(w, h, false);
  camera.aspect = w / h;
  setCameraPosition();
  camera.updateProjectionMatrix();
  updateHeadPosition();
}
resize();
window.addEventListener('resize', resize);

/* ── Mouse parallax + hover dispersion ── */
let mouseX = 0, mouseY = 0;
let rotY = 0, rotX = 0;
let currentHover = 0;
let currentScrollPower = 1; // démarre dispersé
let mouseInside = false;
window.addEventListener('mousemove', e => {
  mouseX = (e.clientX / window.innerWidth  - 0.5) * 2;
  mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
  mouseInside = true;
});
window.addEventListener('mouseleave', () => { mouseInside = false; });

/* ── Theme observer : crossfade smooth entre dark et light ── */
let targetTheme = document.documentElement.classList.contains('light') ? 1 : 0;
let currentTheme = targetTheme;
new MutationObserver(() => {
  targetTheme = document.documentElement.classList.contains('light') ? 1 : 0;
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

/* ── Calcul scroll : 0 = section centrée (formé), 1 = section loin (dispersé) ── */
function computeScrollPower() {
  const sceneEl = document.getElementById('team-scene');
  if (!sceneEl) return 1;
  const rect = sceneEl.getBoundingClientRect();
  const vh = window.innerHeight;
  const center = (rect.top + rect.bottom) / 2;
  // offset 0 = section centrée, 1 = section juste hors viewport
  const offset = Math.abs(center - vh / 2) / vh;
  // zone centrale (offset 0 → 0.5) : formé
  // zone bord (offset 0.5 → 1) : dispersion progressive
  return Math.min(1, Math.max(0, (offset - 0.45) / 0.55));
}

/* ── Render loop ── */
function animate(t) {
  requestAnimationFrame(animate);
  const time = t * 0.001;
  particleMat.uniforms.uTime.value = time;
  updateBurst(t);

  // hover dispersion (souris à droite)
  const isDesktop = window.innerWidth >= 700;
  const targetHover = (isDesktop && mouseInside) ? Math.max(0, mouseX) : 0;
  currentHover += (targetHover - currentHover) * 0.06;
  particleMat.uniforms.uHover.value = currentHover;

  // scroll dispersion (assemble/disperse selon position de la section)
  const targetScroll = computeScrollPower();
  currentScrollPower += (targetScroll - currentScrollPower) * 0.08;
  particleMat.uniforms.uScrollPower.value = currentScrollPower;

  // theme crossfade
  currentTheme += (targetTheme - currentTheme) * 0.08;
  particleMat.uniforms.uTheme.value = currentTheme;

  if (isDesktop) {
    rotY += (mouseX * 0.25 - rotY) * 0.04;
    rotX += (-mouseY * 0.12 - rotX) * 0.04;
    head.rotation.y = 0.18 + rotY;
    head.rotation.x = -0.05 + rotX;
  }
  dust.rotation.y = time * 0.03;
  renderer.render(scene, camera);
}
animate(0);
})();
</script>

<!-- ════ FOND GLOBAL : fumée gold animée derrière toute la page ════ -->
<script>
(function(){
  if (typeof THREE === 'undefined') return;
  const canvas = document.getElementById('global-smoke-bg');
  if (!canvas) return;

  const renderer = new THREE.WebGLRenderer({ canvas, antialias: false, alpha: false });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
  renderer.setClearColor(0x020100, 1);

  const scene = new THREE.Scene();
  const camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

  const geo = new THREE.PlaneGeometry(2, 2);
  const mat = new THREE.ShaderMaterial({
    uniforms: { uTime: { value: 0 }, uAspect: { value: 1 }, uTheme: { value: document.documentElement.classList.contains('light') ? 1 : 0 } },
    depthWrite: false, depthTest: false,
    vertexShader: `
      varying vec2 vUv;
      void main() { vUv = uv; gl_Position = vec4(position, 1.0); }
    `,
    fragmentShader: `
      precision highp float;
      varying vec2 vUv;
      uniform float uTime;
      uniform float uAspect;
      uniform float uTheme;
      float hash(vec2 p) {
        p = fract(p * vec2(123.34, 456.21));
        p += dot(p, p + 45.32);
        return fract(p.x * p.y);
      }
      float noise(vec2 p) {
        vec2 i = floor(p); vec2 f = fract(p);
        vec2 u = f * f * (3.0 - 2.0 * f);
        float a = hash(i);
        float b = hash(i + vec2(1.0, 0.0));
        float c = hash(i + vec2(0.0, 1.0));
        float d = hash(i + vec2(1.0, 1.0));
        return mix(mix(a, b, u.x), mix(c, d, u.x), u.y);
      }
      float fbm(vec2 p) {
        float v = 0.0; float a = 0.55;
        mat2 rot = mat2(0.8, 0.6, -0.6, 0.8);
        for (int i = 0; i < 4; i++) { v += a * noise(p); p = rot * p * 2.0; a *= 0.5; }
        return v;
      }
      void main() {
        vec2 uv = vUv; uv.x *= uAspect;
        vec2 q = uv * 1.6 + vec2(uTime * 0.025, uTime * 0.015);
        float n1 = fbm(q);
        vec2  r  = uv * 2.2 + vec2(n1, fbm(q + 2.0 + uTime * 0.02)) * 1.2;
        float n  = fbm(r);
        // palette dark (nuit gold)
        vec3 deepD = vec3(0.012, 0.008, 0.004);
        vec3 midD  = vec3(0.14, 0.10, 0.035);
        vec3 hiD   = vec3(0.45, 0.32, 0.11);
        // palette light (crème + gold subtil)
        vec3 deepL = vec3(0.97, 0.94, 0.87);
        vec3 midL  = vec3(0.88, 0.81, 0.66);
        vec3 hiL   = vec3(0.62, 0.48, 0.20);
        vec3 deep = mix(deepD, deepL, uTheme);
        vec3 mid  = mix(midD,  midL,  uTheme);
        vec3 hi   = mix(hiD,   hiL,   uTheme);
        vec3 color  = mix(deep, mid, smoothstep(0.2, 0.7, n));
        color       = mix(color, hi, smoothstep(0.55, 0.85, n) * 0.55);
        vec2 c = vUv - vec2(0.5, 0.5);
        float vig = 1.0 - smoothstep(0.3, 1.0, length(c));
        color *= 0.4 + vig * 0.7;
        gl_FragColor = vec4(color, 1.0);
      }
    `
  });
  const plane = new THREE.Mesh(geo, mat);
  scene.add(plane);

  /* ── Theme observer ── */
  let tgtTheme = document.documentElement.classList.contains('light') ? 1 : 0;
  let curTheme = tgtTheme;
  new MutationObserver(() => {
    tgtTheme = document.documentElement.classList.contains('light') ? 1 : 0;
  }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  function resize() {
    const w = window.innerWidth;
    const h = window.innerHeight;
    renderer.setSize(w, h, false);
    mat.uniforms.uAspect.value = w / h;
  }
  resize();
  window.addEventListener('resize', resize);

  function animate(t) {
    requestAnimationFrame(animate);
    mat.uniforms.uTime.value = t * 0.001;
    curTheme += (tgtTheme - curTheme) * 0.08;
    mat.uniforms.uTheme.value = curTheme;
    renderer.render(scene, camera);
  }
  animate(0);
})();
</script>

<!-- ════ PROJECT INFO ════ -->
<div class="project-band reveal">
  <div class="project-inner">
    <div class="project-text">
      <div class="project-title">Le Projet</div>
      <p class="project-body">
        <strong>Question pour un Champion</strong> est né comme un prototype au Semestre 1,
        et se transforme au Semestre 2 en une application complète, stable et évolutive.<br><br>
        L'objectif : livrer un produit <strong>professionnel</strong>, jouable, et démontrable —
        avec une architecture modulaire, un système de score avancé, des modes multijoueurs
        synchronisés et une expérience utilisateur soignée sur tous les écrans.
      </p>
    </div>
    <div class="project-stats">
      <div class="pstat">
        <div class="pstat-num">3</div>
        <div class="pstat-label">Membres</div>
      </div>
      <div class="pstat">
        <div class="pstat-num">2</div>
        <div class="pstat-label">Semestres</div>
      </div>
      <div class="pstat">
        <div class="pstat-num">3</div>
        <div class="pstat-label">Modes de jeu</div>
      </div>
      <div class="pstat">
        <div class="pstat-num">500+</div>
        <div class="pstat-label">Questions</div>
      </div>
    </div>
  </div>
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

<script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>

<script>
/* ═══════════════════════════════════════════
   SCROLL REVEAL (sans void offsetWidth → préserve la perf avec Lenis)
═══════════════════════════════════════════ */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
    } else {
      e.target.classList.remove('visible');
    }
  });
}, { threshold: 0.1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

/* ═══════════════════════════════════════════
   STAGGER TEAM CARDS
═══════════════════════════════════════════ */
const cardObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.style.opacity = '1';
      e.target.style.transform = 'translateY(0)';
    } else {
      e.target.style.opacity = '0';
      e.target.style.transform = 'translateY(40px)';
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.team-card').forEach((card, i) => {
  card.style.opacity = '0';
  card.style.transform = 'translateY(40px)';
  card.style.transition = `opacity 0.6s ease ${i * 0.1}s, transform 0.6s ease ${i * 0.1}s`;
  cardObserver.observe(card);
});

/* ═══════════════════════════════════════════
   ZOOM PARALLAX + LENIS
═══════════════════════════════════════════ */
const maxScales = [4, 5, 6, 5, 6, 8, 9];
const els = Array.from(document.querySelectorAll('.el'));
const container = document.getElementById('container');

let rafPending = false;
let lastProgress = -1;

function update() {
  rafPending = false;
  if (!container) return;
  const rect     = container.getBoundingClientRect();
  const total    = container.offsetHeight - window.innerHeight;
  const progress = Math.min(Math.max(-rect.top / total, 0), 1);

  if (Math.abs(progress - lastProgress) < 0.0001) return;
  lastProgress = progress;

  els.forEach((el, i) => {
    const scale = 1 + (maxScales[i] - 1) * progress;
    el.style.transform = `translate3d(0,0,0) scale(${scale})`;
  });
}

const lenis = new Lenis();
function raf(time) {
  lenis.raf(time);
  if (!rafPending) {
    rafPending = true;
    requestAnimationFrame(update);
  }
  requestAnimationFrame(raf);
}
requestAnimationFrame(raf);

/* ═══════════════════════════════════════════
   COMPTEUR ANIMÉ DES STATS
═══════════════════════════════════════════ */
function animateCounter(el, target) {
  const isPlus = String(target).includes('+');
  const num = parseInt(String(target).replace('+', ''));
  const duration = 1200;
  const start = performance.now();
  el.classList.add('counted');

  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    const current = Math.round(ease * num);
    el.textContent = current + (isPlus && progress >= 1 ? '+' : '');
    if (progress < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

const statsObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      const el = e.target;
      const raw = el.dataset.target;
      animateCounter(el, raw);
      statsObserver.unobserve(el);
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('.pstat-num').forEach(el => {
  el.dataset.target = el.textContent.trim();
  el.textContent = '0';
  statsObserver.observe(el);
});

update();

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