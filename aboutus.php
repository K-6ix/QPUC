<?php session_start(); ?>
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
   TEAM SECTION
════════════════════════════ */
.team-section {
  padding:100px 40px;
  max-width:1200px;
  margin:0 auto;
}

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

.team-grid {
  display:grid;
  grid-template-columns: repeat(3, 1fr);
  gap:24px;
}

.team-card {
  background:var(--card);
  border:1px solid var(--gold-line);
  border-radius:20px;
  padding:36px 32px;
  position:relative;
  overflow:hidden;
  transition: border-color 0.4s, transform 0.4s, box-shadow 0.4s;
  cursor:default;
}
.team-card::before {
  content:'';
  position:absolute; inset:0;
  background:radial-gradient(ellipse at 50% 0%, rgba(212,175,55,0.08) 0%, transparent 65%);
  opacity:0; transition:opacity 0.4s;
}
.team-card:hover {
  border-color: var(--gold-line-strong);
  transform: translateY(-8px);
  box-shadow: 0 20px 60px var(--shadow-deep), 0 0 30px rgba(212,175,55,0.08);
}
.team-card:hover::before { opacity:1; }

.card-num {
  position:absolute;
  top:20px; right:24px;
  font-family:'Kanit', sans-serif;
  font-size:5rem; font-weight:900;
  color:rgba(212,175,55,0.05);
  line-height:1;
  pointer-events:none;
}
html.light .card-num { color: rgba(212,175,55,0.18); }

.card-avatar {
  width:72px; height:72px;
  border-radius:50%;
  border:2px solid var(--gold-line-strong);
  background:var(--gold-tint-2);
  display:flex; align-items:center; justify-content:center;
  font-family:'Kanit', sans-serif;
  font-weight:900; font-size:1.4rem;
  color:var(--gold-base);
  margin-bottom:20px;
  position:relative;
  overflow:hidden;
  transition:border-color 0.3s;
}
.team-card:hover .card-avatar { border-color: var(--gold-base); }
.card-avatar img {
  width:100%; height:100%;
  object-fit:cover;
  border-radius:50%;
}

.team-card.lead .card-avatar {
  width:90px; height:90px;
  font-size:1.8rem;
}

.card-role {
  font-size:0.6rem;
  letter-spacing:4px;
  text-transform:uppercase;
  color:var(--gold-base);
  opacity:0.8;
  margin-bottom:8px;
}
.card-name {
  font-family:'Kanit', sans-serif;
  font-weight:900;
  font-size:1.5rem;
  letter-spacing:1px;
  text-transform:uppercase;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip: text;
  margin-bottom:14px;
  line-height:1;
}
.team-card.lead .card-name { font-size:2rem; }

.card-desc {
  font-size:0.8rem;
  color:var(--ink-2);
  line-height:1.8;
}
.card-line {
  width:28px; height:2px;
  background:var(--gold-base);
  margin-bottom:14px;
  opacity:0.4;
}

.card-skills {
  display:flex; flex-wrap:wrap; gap:6px;
  margin-top:18px;
}
.skill-tag {
  font-size:0.6rem;
  letter-spacing:1px;
  padding:3px 10px;
  border-radius:20px;
  border:1px solid var(--gold-line);
  color:var(--gold-text);
  text-transform:uppercase;
  transition: border-color 0.25s, color 0.25s, background 0.25s;
  cursor: default;
}
.skill-tag:hover {
  border-color: var(--gold-base);
  color: var(--gold-text);
  background: var(--gold-tint);
}

.lead-badge {
  position:absolute;
  top:20px; left:32px;
  background:var(--metallic);
  color:var(--on-gold);
  font-size:0.55rem;
  font-weight:900;
  letter-spacing:2px;
  padding:3px 12px;
  border-radius:20px;
  text-transform:uppercase;
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
</style>
</head>
<body>

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
<!-- <div class="intro">Scroll ↓</div>

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

<div class="outro">↑ Scroll up</div> -->

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

<!-- ════ TEAM ════ -->
<div class="team-section">
  <p class="section-tag">Notre équipe</p>
  <h2 class="section-title">Les <em>champions</em> derrière le jeu</h2>

  <div class="team-grid reveal">
    <div class="team-card">
      <div class="card-num">01</div>
      <div class="card-avatar">MB</div>
      <div class="card-role">Rédacteur Technique</div>
      <div class="card-name">Maxime Bang-Kera</div>
      <div class="card-line"></div>
      <p class="card-desc">Documentation, spécifications techniques et rédaction des rapports de projet.</p>
      <div class="card-skills">
        <span class="skill-tag">Documentation</span>
        <span class="skill-tag">Rédaction</span>
      </div>
    </div>

    <div class="team-card">
      <div class="card-num">02</div>
      <div class="card-avatar">ON</div>
      <div class="card-role">Développeur Front-end</div>
      <div class="card-name">Ousmane Niasse</div>
      <div class="card-line"></div>
      <p class="card-desc">Intégration des interfaces, animations CSS et expérience utilisateur.</p>
      <div class="card-skills">
        <span class="skill-tag">HTML / CSS</span>
        <span class="skill-tag">Animations</span>
      </div>
    </div>

    <div class="team-card">
      <div class="card-num">03</div>
      <div class="card-avatar">BA</div>
      <div class="card-role">Design & Styling</div>
      <div class="card-name">Bamba Amara</div>
      <div class="card-line"></div>
      <p class="card-desc">Identité visuelle, charte graphique et cohérence du design sur l'ensemble du site.</p>
      <div class="card-skills">
        <span class="skill-tag">Design</span>
        <span class="skill-tag">CSS</span>
      </div>
    </div>
  </div>
</div>

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
      <li><a href="index.php#classement">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
      <li><a href="connexion.php">Connexion</a></li>
    </ul>
    <div class="footer-cta-col">
      <a href="game.html" class="footer-play-btn">
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
