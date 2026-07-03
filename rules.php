<?php require_once __DIR__ . '/csrf.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Règles — Question Champion</title>

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
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,900&family=Inter:wght@300;400;500;600;700;900&family=JetBrains+Mono:wght@400;700&family=Space+Grotesk:wght@300;400;500;600;700&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<style>
:root {
  --gold-light: #fcf6ba;
  --gold-base: #d4af37;
  --gold-dark: #8a6e2f;
  --gold-glow: rgba(212,175,55,0.4);
  --gold-faint: rgba(212,175,55,0.06);
  --gold-line: rgba(212,175,55,0.15);
  --gold-line-strong: rgba(212,175,55,0.4);
  --gold-tint: rgba(212,175,55,0.04);
  
  --bg: #040404;
  --bg2: #080808;
  --bg3: #0c0c0c;
  
  --ink: #ffffff;
  --ink-2: rgba(255,255,255,0.7);
  --ink-3: rgba(255,255,255,0.45);
  --ink-4: rgba(255,255,255,0.2);
  --ink-5: rgba(255,255,255,0.08);
  
  --line: rgba(255,255,255,0.06);
  --metallic: linear-gradient(135deg, var(--gold-dark), var(--gold-base) 40%, var(--gold-light) 60%, var(--gold-base) 80%, var(--gold-dark));
  
  --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
  --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
}

html.light {
  --bg: #f8f7f4;
  --bg2: #f0efe9;
  --bg3: #e8e7e1;
  --ink: #0a0a0a;
  --ink-2: rgba(10,10,10,0.7);
  --ink-3: rgba(10,10,10,0.45);
  --ink-4: rgba(10,10,10,0.2);
  --ink-5: rgba(10,10,10,0.08);
  --line: rgba(10,10,10,0.06);
  --gold-line: rgba(138,110,47,0.2);
  --gold-line-strong: rgba(138,110,47,0.5);
  --gold-faint: rgba(212,175,55,0.08);
}

/* ════ Transition douce pendant le switch de thème ════ */
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
  font-family: 'Inter', sans-serif;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
  cursor: auto;
}

@media (hover: hover) and (pointer: fine) {
  body.custom-cursor-active { cursor: none; }
  body.custom-cursor-active a,
  body.custom-cursor-active button,
  body.custom-cursor-active [data-hover] { cursor: none; }
}

/* Custom Cursor */
.cursor {
  position: fixed;
  width: 8px; height: 8px;
  background: var(--gold-base);
  border-radius: 50%;
  pointer-events: none;
  z-index: 10000;
  mix-blend-mode: difference;
  transition: transform 0.15s var(--ease-expo);
  display: none;
}
.cursor-follower {
  position: fixed;
  width: 40px; height: 40px;
  border: 1px solid var(--gold-line-strong);
  border-radius: 50%;
  pointer-events: none;
  z-index: 9999;
  transition: transform 0.3s var(--ease-expo), width 0.3s, height 0.3s, border-color 0.3s;
  display: none;
}
.cursor-follower.hovering {
  width: 60px; height: 60px;
  border-color: var(--gold-base);
  background: rgba(212,175,55,0.05);
}
.cursor-label {
  position: fixed;
  pointer-events: none;
  z-index: 9998;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--gold-base);
  opacity: 0;
  transition: opacity 0.3s;
  transform: translate(20px, -10px);
  display: none;
}

@media (hover: hover) and (pointer: fine) {
  body.custom-cursor-active .cursor,
  body.custom-cursor-active .cursor-follower,
  body.custom-cursor-active .cursor-label { display: block; }
}

/* WebGL Canvas */
#webgl-canvas {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 0;
  pointer-events: none;
}

/* Atmospheric overlays */
.grain-overlay {
  position: fixed;
  inset: 0;
  z-index: 9998;
  pointer-events: none;
  opacity: 0.025;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='5' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  mix-blend-mode: soft-light;
}

.vignette {
  position: fixed;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background: radial-gradient(ellipse at 50% 50%, transparent 50%, rgba(0,0,0,0.3) 100%);
}

.fog-layer {
  position: fixed;
  inset: 0;
  z-index: 2;
  pointer-events: none;
  background: 
    radial-gradient(ellipse at 20% 80%, rgba(212,175,55,0.03) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(212,175,55,0.02) 0%, transparent 50%);
  animation: fogDrift 20s ease-in-out infinite;
}

@keyframes fogDrift {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(2%, -1%); }
}

/* Scroll Progress */
.scroll-progress {
  position: fixed;
  top: 0; left: 0;
  height: 2px;
  background: var(--metallic);
  z-index: 1000;
  transform-origin: left;
  transform: scaleX(0);
}

/* HEADER */
/* ════════════════════════════
   HEADER — Standard HESTIM (grid 30% / 50% / 20%)
   ════════════════════════════ */
header.qpc-header {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 100;
  display: grid;
  grid-template-columns: 30% 50% 20%;
  align-items: center;
  padding: 0 40px;
  height: 72px;
  border-bottom: 1px solid var(--gold-line);
  background: rgba(4,4,4,0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  opacity: 0;
  transition: background 0.4s var(--ease-expo);
}

html.light header.qpc-header {
  background: rgba(248,247,244,0.9);
}

.qpc-header .logo {
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

.qpc-header nav ul {
  display: flex;
  list-style: none;
  gap: 28px;
  align-items: center;
  justify-content: center;
  margin: 0;
  padding: 0;
}

.qpc-header nav a {
  text-decoration: none;
  color: var(--ink-2);
  font-family: 'Montserrat', sans-serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  position: relative;
  transition: color 0.3s;
}
.qpc-header nav a:hover { color: var(--gold-light); }
.qpc-header nav a::after {
  content: '';
  position: absolute;
  width: 0; height: 1px;
  bottom: -4px; left: 0;
  background: var(--metallic);
  transition: width 0.3s;
}
.qpc-header nav a:hover::after { width: 100%; }

.qpc-header .btn-play {
  background: var(--metallic);
  color: #000 !important;
  -webkit-text-fill-color: #000 !important;
  padding: 7px 22px;
  border-radius: 30px;
  font-weight: 900;
  border: 1px solid var(--gold-base);
  box-shadow: 0 0 12px var(--gold-glow);
  transition: transform 0.2s, box-shadow 0.2s;
}
.qpc-header .btn-play:hover {
  transform: scale(1.05);
  box-shadow: 0 0 22px rgba(212,175,55,0.7);
}
.qpc-header .btn-play::after { display: none; }

.qpc-header .header-right {
  justify-self: end;
  display: flex;
  align-items: center;
  gap: 12px;
}

.qpc-header .icon-btn {
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
  padding: 0;
}
.qpc-header .icon-btn:hover {
  border-color: var(--gold-base);
  color: var(--gold-light);
  background: var(--gold-faint);
}
.qpc-header .icon-btn:active { transform: scale(0.95); }
.qpc-header .icon-btn svg { width: 15px; height: 15px; }

#theme-toggle .theme-moon { display: none; }
#theme-toggle .theme-sun  { display: block; }
html.light #theme-toggle .theme-moon { display: block; }
html.light #theme-toggle .theme-sun  { display: none; }

.qpc-header .btn-connexion {
  background: transparent;
  border: 1px solid var(--gold-line-strong);
  color: var(--gold-light);
  padding: 7px 22px;
  border-radius: 30px;
  font-family: 'Montserrat', sans-serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-decoration: none;
  transition: all 0.3s;
  white-space: nowrap;
}
.qpc-header .btn-connexion:hover {
  background: var(--metallic);
  color: #000;
  -webkit-text-fill-color: #000;
  border-color: transparent;
  box-shadow: 0 0 18px var(--gold-glow);
}

html.light .qpc-header .btn-connexion { color: var(--gold-dark); }

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
  color: var(--gold-light);
  margin-bottom: 18px;
}
html.light .drawer-section-label { color: var(--gold-dark); }

.drawer-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 0;
  border-bottom: 1px solid var(--ink-5);
  text-decoration: none;
  color: var(--ink);
  font-family: 'Montserrat', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 1px;
  transition: color 0.25s, padding-left 0.25s;
}
.drawer-link:hover {
  color: var(--gold-light);
  padding-left: 6px;
}
html.light .drawer-link:hover { color: var(--gold-dark); }
.drawer-link svg {
  width: 16px; height: 16px;
  color: var(--ink-4);
  transition: color 0.25s, transform 0.25s;
}
.drawer-link:hover svg {
  color: var(--gold-base);
  transform: translateX(4px);
}

.drawer-footer {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-top: 1px solid var(--ink-5);
}
.drawer-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 24px;
  border-radius: 40px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 900;
  font-size: 0.8rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-decoration: none;
  transition: transform 0.2s, box-shadow 0.2s;
}
.drawer-cta.primary {
  background: var(--metallic);
  color: #000;
  border: 1px solid var(--gold-base);
  box-shadow: 0 0 12px var(--gold-glow);
}
.drawer-cta.primary:hover { transform: translateY(-2px); }
.drawer-cta.secondary {
  background: transparent;
  border: 1px solid var(--gold-line-strong);
  color: var(--gold-light);
}
html.light .drawer-cta.secondary { color: var(--gold-dark); }
.drawer-cta.secondary:hover {
  background: var(--gold-faint);
  border-color: var(--gold-base);
}
.drawer-copy {
  text-align: center;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.65rem;
  color: var(--ink-4);
  letter-spacing: 2px;
  margin-top: 8px;
}

/* HERO */
.hero {
  position: relative;
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 0 24px;
  z-index: 10;
  overflow: hidden;
}

.hero-content {
  position: relative;
  z-index: 10;
  background: rgba(4,4,4,0.4);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  padding: 40px 32px;
  border-radius: 16px;
  border: 1px solid var(--gold-line);
}

html.light .hero-content {
  background: rgba(248,247,244,0.7);
  border-color: var(--gold-line);
}

.hero-label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.65rem;
  letter-spacing: 8px;
  text-transform: uppercase;
  color: var(--gold-base);
  margin-bottom: 40px;
  opacity: 0;
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 16px;
}

.hero-label::before,
.hero-label::after {
  content: '';
  width: 40px; height: 1px;
  background: var(--gold-line-strong);
}

.hero-title {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: clamp(4rem, 10vw, 10rem);
  line-height: 0.95;
  letter-spacing: -4px;
  color: var(--ink);
  max-width: 1000px;
  opacity: 0;
  position: relative;
}

.hero-title .line {
  display: block;
  overflow: hidden;
}

.hero-title .line-inner {
  display: block;
  transform: translateY(100%);
}

.hero-title em {
  font-style: italic;
  font-weight: 400;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 0 30px var(--gold-glow));
}

.hero-subtitle {
  font-family: 'Inter', sans-serif;
  font-size: clamp(1rem, 1.5vw, 1.25rem);
  font-weight: 300;
  color: var(--ink-3);
  max-width: 500px;
  margin: 48px auto 0;
  line-height: 1.8;
  opacity: 0;
}

.hero-hud {
  position: absolute;
  bottom: 60px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  opacity: 0;
}

.hero-hud-text {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--ink-4);
}

.hero-hud-line {
  width: 1px;
  height: 60px;
  background: linear-gradient(to bottom, var(--gold-base), transparent);
  position: relative;
  overflow: hidden;
}

.hero-hud-line::after {
  content: '';
  position: absolute;
  top: -100%; left: 0;
  width: 100%; height: 100%;
  background: var(--gold-base);
  animation: scrollPulse 2s ease-in-out infinite;
}

.hero-coords {
  position: absolute;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.55rem;
  letter-spacing: 2px;
  color: var(--ink-5);
  text-transform: uppercase;
}

.hero-coords.tl { top: 120px; left: 40px; }
.hero-coords.tr { top: 120px; right: 40px; text-align: right; }
.hero-coords.bl { bottom: 120px; left: 40px; }
.hero-coords.br { bottom: 120px; right: 40px; text-align: right; }

/* RULES */
.rules-immersive {
  position: relative;
  z-index: 10;
}

.rule-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.rule-bg-number {
  position: absolute;
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: clamp(15rem, 35vw, 40rem);
  color: var(--gold-faint);
  line-height: 0.8;
  user-select: none;
  z-index: 0;
  opacity: 0.08;
  top: 50%;
  transform: translateY(-50%);
  transition: all 1.5s var(--ease-expo);
  pointer-events: none;
}

.rule-section:nth-child(odd) .rule-bg-number {
  right: -10%;
  text-align: right;
}

.rule-section:nth-child(even) .rule-bg-number {
  left: -10%;
  text-align: left;
}

.rule-content {
  position: relative;
  z-index: 2;
  max-width: 1400px;
  margin: 0 auto;
  width: 100%;
  padding: 80px 60px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 80px;
  align-items: center;
}

.rule-section:nth-child(even) .rule-content {
  direction: rtl;
}

.rule-section:nth-child(even) .rule-content > * {
  direction: ltr;
}

.rule-visual {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}

.rule-visual-icon {
  font-size: clamp(8rem, 15vw, 20rem);
  position: relative;
  z-index: 2;
  filter: drop-shadow(0 0 60px var(--gold-glow)) drop-shadow(0 0 120px rgba(212,175,55,0.2));
  transition: all 0.8s var(--ease-expo);
  animation: iconFloat 6s ease-in-out infinite;
}

.rule-section:hover .rule-visual-icon {
  filter: drop-shadow(0 0 80px var(--gold-glow)) drop-shadow(0 0 160px rgba(212,175,55,0.3));
  transform: scale(1.05);
}

.rule-visual::before {
  content: '';
  position: absolute;
  width: 300px; height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
  filter: blur(80px);
  opacity: 0.3;
  animation: glowPulse 4s ease-in-out infinite;
}

@keyframes iconFloat {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(2deg); }
}

@keyframes glowPulse {
  0%, 100% { opacity: 0.2; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(1.2); }
}

.rule-text {
  position: relative;
  background: rgba(4,4,4,0.3);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  padding: 32px;
  border-radius: 12px;
  border: 1px solid rgba(212,175,55,0.08);
}

html.light .rule-text {
  background: rgba(248,247,244,0.5);
  border-color: rgba(138,110,47,0.1);
}

.rule-overline {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  color: var(--gold-base);
  margin-bottom: 32px;
  display: block;
  opacity: 0.7;
}

.rule-title {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: clamp(2.5rem, 4vw, 4.5rem);
  line-height: 1.05;
  letter-spacing: -2px;
  color: var(--ink);
  margin-bottom: 32px;
}

.rule-title em {
  font-style: italic;
  font-weight: 400;
  color: var(--gold-base);
}

.rule-desc {
  font-size: 1.1rem;
  font-weight: 300;
  color: var(--ink-3);
  line-height: 1.9;
  margin-bottom: 40px;
  max-width: 480px;
}

.rule-details {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.rule-details li {
  font-size: 0.9rem;
  color: var(--ink-2);
  font-family: 'Inter', sans-serif;
  position: relative;
  padding-left: 24px;
  opacity: 1;
  transition: opacity 0.3s;
}

.rule-details li:hover {
  opacity: 1;
}

.rule-details li::before {
  content: '';
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 12px; height: 1px;
  background: var(--gold-base);
  transition: width 0.4s var(--ease-expo);
}

.rule-details li:hover::before {
  width: 20px;
}

.rule-divider {
  position: relative;
  height: 1px;
  background: linear-gradient(to right, transparent, var(--gold-line), transparent);
  margin: 0 15%;
}

.rule-divider::before {
  content: '';
  position: absolute;
  left: 50%; top: 50%;
  transform: translate(-50%, -50%);
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--gold-base);
  box-shadow: 0 0 20px var(--gold-glow);
}

/* DIVIDER */
.section-divider {
  position: relative;
  padding: 80px 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  z-index: 10;
}

.divider-line {
  flex: 1;
  max-width: 200px;
  height: 1px;
  background: linear-gradient(to right, transparent, var(--gold-line), transparent);
  position: relative;
  overflow: hidden;
}

.divider-line::after {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, var(--gold-base), transparent);
  animation: shimmer 3s ease-in-out infinite;
}

.divider-number {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.65rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  color: var(--gold-base);
  white-space: nowrap;
  position: relative;
}

.divider-number::before {
  content: '';
  position: absolute;
  inset: -8px -16px;
  border: 1px solid var(--gold-line);
  border-radius: 4px;
  opacity: 0.5;
}

/* FEATURED */
.featured-section {
  position: relative;
  padding: 120px 40px;
  max-width: 1400px;
  margin: 0 auto;
  z-index: 10;
}

.featured-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 100px;
  align-items: center;
  margin-bottom: 160px;
  position: relative;
}

.featured-row:nth-child(even) {
  direction: rtl;
}

.featured-row:nth-child(even) > * {
  direction: ltr;
}

.featured-visual {
  position: relative;
  aspect-ratio: 4/3;
  border-radius: 2px;
  overflow: hidden;
  background: var(--bg2);
}

.featured-visual::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, var(--gold-tint) 0%, transparent 50%);
  z-index: 1;
  pointer-events: none;
}

.featured-visual .placeholder {
  width: 100%; height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 100px;
  position: relative;
  overflow: hidden;
}

.featured-visual .placeholder::before {
  content: '';
  position: absolute;
  inset: -50%;
  background: conic-gradient(from 0deg, transparent, var(--gold-faint), transparent);
  animation: rotate 8s linear infinite;
}

.featured-visual .placeholder span {
  position: relative;
  z-index: 2;
  filter: drop-shadow(0 0 20px var(--gold-glow));
}

.featured-visual .overlay-number {
  position: absolute;
  bottom: 24px; right: 28px;
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: 100px;
  color: var(--gold-faint);
  line-height: 1;
  z-index: 2;
  user-select: none;
  transition: all 0.6s var(--ease-expo);
}

.featured-visual:hover .overlay-number {
  color: var(--gold-glow);
  transform: translateY(-10px);
}

.featured-content {
  position: relative;
}

.featured-content .overline {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--gold-base);
  margin-bottom: 24px;
  display: block;
  position: relative;
  padding-left: 20px;
}

.featured-content .overline::before {
  content: '';
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 8px; height: 1px;
  background: var(--gold-base);
}

.featured-content h3 {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: clamp(2.2rem, 3.5vw, 3.5rem);
  line-height: 1.1;
  letter-spacing: -1px;
  color: var(--ink);
  margin-bottom: 28px;
}

.featured-content p {
  font-size: 1.05rem;
  font-weight: 400;
  color: var(--ink-2);
  line-height: 1.9;
  margin-bottom: 32px;
}

.featured-content .feature-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.featured-content .feature-list li {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 0.9rem;
  color: var(--ink-2);
  font-family: 'Inter', sans-serif;
  position: relative;
  padding-left: 24px;
}

.featured-content .feature-list li::before {
  content: '';
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 12px; height: 1px;
  background: var(--gold-base);
  transition: width 0.4s var(--ease-expo);
}

.featured-content .feature-list li:hover::before {
  width: 20px;
}

/* STATS */
.stats-section {
  position: relative;
  padding: 100px 40px;
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  background: var(--bg2);
  overflow: hidden;
  z-index: 10;
}

.stats-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 50%, var(--gold-faint) 0%, transparent 70%);
  pointer-events: none;
}

.stats-grid {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 60px;
  position: relative;
  z-index: 1;
}

.stat-item {
  text-align: center;
  position: relative;
}

.stat-item::before {
  content: '';
  position: absolute;
  top: -20px; left: 50%;
  transform: translateX(-50%);
  width: 1px; height: 40px;
  background: linear-gradient(to bottom, transparent, var(--gold-line));
  opacity: 0.5;
}

.stat-number {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: clamp(3rem, 5vw, 4.5rem);
  line-height: 1;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 16px;
  position: relative;
  display: inline-block;
}

.stat-number::after {
  content: '';
  position: absolute;
  bottom: -8px; left: 0;
  width: 100%; height: 1px;
  background: var(--gold-line);
  transform: scaleX(0.3);
}

.stat-label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.65rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--ink-4);
}

/* CTA */
.cta-section {
  position: relative;
  padding: 200px 40px;
  text-align: center;
  overflow: hidden;
  z-index: 10;
}

.cta-section::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 50%, var(--gold-faint) 0%, transparent 60%);
  pointer-events: none;
}

.cta-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
  /* ── Panneau glassmorphism : floute le gem derrière → effet fumée (même rendu que le hero) ── */
  background: rgba(4,4,4,0.4);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  padding: 56px 48px;
  border-radius: 16px;
  border: 1px solid var(--gold-line);
}

html.light .cta-content {
  background: rgba(248,247,244,0.7);
  border-color: var(--gold-line);
}

.cta-content .overline {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.65rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  color: var(--gold-base);
  margin-bottom: 32px;
  display: block;
}

.cta-content h2 {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: clamp(3rem, 6vw, 5.5rem);
  line-height: 1.05;
  letter-spacing: -2px;
  color: var(--ink);
  margin-bottom: 32px;
}

.cta-content h2 em {
  font-style: italic;
  font-weight: 400;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.cta-content p {
  font-size: 1.15rem;
  font-weight: 300;
  color: var(--ink-3);
  line-height: 1.9;
  margin-bottom: 48px;
}

.cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 16px;
  padding: 20px 48px;
  background: var(--metallic);
  color: var(--bg);
  font-family: 'Inter', sans-serif;
  font-size: 0.8rem;
  font-weight: 900;
  letter-spacing: 4px;
  text-transform: uppercase;
  text-decoration: none;
  border-radius: 50px;
  border: 1px solid var(--gold-base);
  box-shadow: 0 0 40px var(--gold-glow);
  transition: all 0.5s var(--ease-expo);
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.cta-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transform: translateX(-100%);
  transition: transform 0.6s var(--ease-expo);
}

.cta-btn:hover,
.cta-btn:focus-visible {
  transform: translateY(-4px);
  box-shadow: 0 15px 50px rgba(212,175,55,0.5);
}

.cta-btn:hover::before,
.cta-btn:focus-visible::before {
  transform: translateX(100%);
}

.cta-btn:focus-visible {
  outline: 2px solid var(--gold-base);
  outline-offset: 4px;
}

.cta-btn svg {
  width: 18px; height: 18px;
  transition: transform 0.4s var(--ease-expo);
}

.cta-btn:hover svg,
.cta-btn:focus-visible svg {
  transform: translateX(6px);
}

/* FOOTER */
.page-footer {
  border-top: 1px solid var(--line);
  padding: 48px 40px;
  text-align: center;
  position: relative;
  z-index: 10;
}

.page-footer p {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--ink-5);
}

/* ANIMATIONS */
@keyframes slideDown {
  from { transform: translateY(-100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes revealUp {
  from { opacity: 0; transform: translateY(60px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes scrollPulse {
  0%, 100% { transform: translateY(-100%); }
  50% { transform: translateY(200%); }
}

@keyframes shimmer {
  0% { left: -100%; }
  100% { left: 100%; }
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

/* SVG ICONS */
.rule-svg {
  width: clamp(160px, 22vw, 360px);
  height: auto;
  color: var(--gold-base);
  display: block;
  filter: drop-shadow(0 0 30px var(--gold-glow)) drop-shadow(0 0 80px rgba(212,175,55,0.25));
}
.rule-visual-icon {
  font-size: inherit;
}

.placeholder .rule-svg {
  width: 60%;
  max-width: 200px;
}

.rule-svg path,
.rule-svg line,
.rule-svg circle,
.rule-svg polyline {
  stroke-dasharray: 2000;
  stroke-dashoffset: 2000;
  transition: stroke-dashoffset 1.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.rule-svg.icon-drawn path,
.rule-svg.icon-drawn line,
.rule-svg.icon-drawn circle,
.rule-svg.icon-drawn polyline {
  stroke-dashoffset: 0;
}

/* CHECKLIST ANIMATION */
.rule-details.checklist-anim {
  list-style: none !important;
  padding: 0 !important;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.rule-details.checklist-anim .check-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid var(--ink-5);
  opacity: 0.35;
  transform: translateX(-12px);
  transition: opacity 0.5s var(--ease-expo), transform 0.5s var(--ease-expo), background 0.4s;
  list-style: none !important;
}
.rule-details.checklist-anim .check-item::before {
  display: none !important;
}
.rule-details.checklist-anim .check-item.checked {
  opacity: 1;
  transform: translateX(0);
  background: linear-gradient(90deg, var(--gold-faint), transparent);
}
.check-box {
  width: 22px; height: 22px;
  border: 1px solid var(--gold-line-strong);
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.3s, background 0.3s;
}
.check-item.checked .check-box {
  border-color: var(--gold-base);
  background: var(--gold-faint);
}
.check-svg {
  width: 12px; height: 12px;
  color: var(--gold-base);
  stroke-dasharray: 20;
  stroke-dashoffset: 20;
  transition: stroke-dashoffset 0.5s var(--ease-expo);
}
.check-item.checked .check-svg { stroke-dashoffset: 0; }
.check-text {
  font-size: 0.95rem;
  color: var(--ink);
  flex: 1;
}
.check-item:not(.checked) .check-text { color: var(--ink-3); }

/* RESPONSIVE */
@media (max-width: 1200px) {
  .rule-content { gap: 80px; padding: 100px 60px; }
  .rule-visual-icon { font-size: clamp(6rem, 12vw, 15rem); }
  .stats-grid { gap: 40px; }
  .featured-row { gap: 60px; }
}

@media (max-width: 900px) {
  body { cursor: auto !important; }
  .cursor, .cursor-follower, .cursor-label { display: none !important; }
  header.qpc-header {
    padding: 0 24px;
    grid-template-columns: auto 1fr auto;
  }
  .qpc-header nav { display: none; }
  .qpc-header .btn-connexion { display: none; }
  #burger-trigger { display: inline-flex; }
  .hero-coords { display: none; }
  
  .rule-content {
    grid-template-columns: 1fr;
    gap: 48px;
    padding: 80px 40px;
  }
  .rule-visual { min-height: 250px; order: -1; }
  .rule-visual-icon { font-size: clamp(5rem, 20vw, 10rem); }
  .rule-bg-number { font-size: 15rem; opacity: 0.1; }
  .rule-section:nth-child(odd) .rule-bg-number { right: -10%; }
  .rule-section:nth-child(even) .rule-bg-number { left: -10%; }
  
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 40px; }
  .featured-row { grid-template-columns: 1fr; gap: 48px; }
  .featured-row:nth-child(even) { direction: ltr; }
  .rules-section, .featured-section { padding: 0 24px 100px; }
  .cta-section { padding: 120px 24px; }
  .rule-divider { margin: 0 10%; }
}

@media (max-width: 600px) {
  header.qpc-header { height: 64px; padding: 0 16px; }
  .qpc-header .logo { font-size: 0.9rem; letter-spacing: 2px; }
  .hero-title { font-size: clamp(2.5rem, 14vw, 4rem); letter-spacing: -2px; }
  .stats-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
  .stat-number { font-size: 2.5rem; }
  .rule-content { padding: 60px 24px; }
  .rule-title { font-size: clamp(2rem, 8vw, 3rem); }
  .rule-visual-icon { font-size: 5rem; }
  .rule-divider { margin: 0 5%; }
}

@media (max-width: 400px) {
  .hero-title { font-size: 2.2rem; letter-spacing: -1px; }
  .hero-content { padding: 24px 16px; }
  .rule-content { padding: 40px 16px; gap: 24px; }
  .rule-title { font-size: 1.8rem; }
  .rule-visual { min-height: 180px; }
  .rule-visual-icon { font-size: 4rem; }
  .rule-bg-number { font-size: 10rem; opacity: 0.05; }
  header.qpc-header { padding: 0 12px; height: 56px; }
  .qpc-header .logo { font-size: 0.8rem; letter-spacing: 2px; }
  .featured-section { padding: 60px 16px; }
  .featured-row { gap: 32px; margin-bottom: 80px; }
  .stats-section { padding: 60px 16px; }
  .stats-grid { gap: 24px; }
  .stat-number { font-size: 2rem; }
  .cta-section { padding: 80px 16px; }
  .cta-content { padding: 36px 24px; }
  .cta-content h2 { font-size: 2rem; }
  .cta-btn { padding: 16px 32px; font-size: 0.7rem; }
}

@media (max-width: 1024px) {
  .rule-content { gap: 60px; padding: 80px 40px; }
  .featured-row { gap: 48px; }
}

@media (hover: none) and (pointer: coarse) {
  .cursor, .cursor-follower, .cursor-label { display: none !important; }
  body { cursor: auto !important; }
  a, button { cursor: pointer !important; }
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  #webgl-canvas { display: none; }
  .fog-layer { display: none; }
}

html, body {
  overflow-x: hidden;
  max-width: 100vw;
}

/* Page loader */
.page-loader {
  position: fixed;
  inset: 0;
  z-index: 20000;
  background: var(--bg);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.6s var(--ease-expo), visibility 0.6s;
}
.page-loader.done {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
}
.page-loader-inner {
  width: 40px;
  height: 40px;
  border: 2px solid var(--gold-line);
  border-top-color: var(--gold-base);
  border-radius: 50%;
  animation: rotate 0.8s linear infinite;
}

/* Scroll-hint for gem */
.gem-scroll-hint {
  position: fixed;
  bottom: 100px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 50;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.6rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--gold-base);
  opacity: 0;
  transition: opacity 0.8s;
  pointer-events: none;
}
.gem-scroll-hint.visible {
  opacity: 0.6;
}
</style>
</head>
<body>

<!-- Page Loader -->
<div class="page-loader" id="page-loader">
  <div class="page-loader-inner"></div>
</div>

<!-- Custom Cursor -->
<div class="cursor"></div>
<div class="cursor-follower"></div>
<div class="cursor-label">Explore</div>

<!-- WebGL Canvas -->
<canvas id="webgl-canvas"></canvas>

<!-- Atmospheric Overlays -->
<div class="grain-overlay"></div>
<div class="vignette"></div>
<div class="fog-layer"></div>

<!-- Scroll Progress -->
<div class="scroll-progress"></div>

<!-- Scroll hint -->
<div class="gem-scroll-hint" id="gem-hint">Scroll to open the core</div>

<!-- ════ HEADER — Standard HESTIM ════ -->
<header class="qpc-header">
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

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-label">Manuel du joueur</div>
    <h1 class="hero-title">
      <span class="line"><span class="line-inner">Les <em>Règles</em></span></span>
      <span class="line"><span class="line-inner">du jeu</span></span>
    </h1>
    <p class="hero-subtitle">
      Découvrez les mécaniques, les modes de jeu et les stratégies pour devenir le champion ultime de la culture générale.
    </p>
  </div>
  
  <div class="hero-hud">
    <span class="hero-hud-text">Scroll to explore</span>
    <div class="hero-hud-line"></div>
  </div>
  
  <div class="hero-coords tl">LAT: 46.2044° N</div>
  <div class="hero-coords tr">LNG: 6.1432° E</div>
  <div class="hero-coords bl">ALT: 1,234m</div>
  <div class="hero-coords br">TEMP: 21°C</div>
</section>

<!-- RULES (compact: 6 sections fusionnées) -->
<div class="rules-immersive">

  <!-- ═══ 01 — Bienvenue & comment jouer ═══ -->
  <section class="rule-section" data-hover>
    <div class="rule-bg-number">01</div>
    <div class="rule-content">
      <div class="rule-visual">
        <span class="rule-visual-icon"><svg class="rule-svg" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M30 130 L 55 55 L 100 95 L 100 35 L 100 95 L 145 55 L 170 130 Z"/><line x1="30" y1="145" x2="170" y2="145"/><line x1="40" y1="165" x2="160" y2="165"/><circle cx="55" cy="55" r="5"/><circle cx="100" cy="35" r="6"/><circle cx="145" cy="55" r="5"/></svg></span>
      </div>
      <div class="rule-text">
        <span class="rule-overline">Guide de démarrage</span>
        <h2 class="rule-title">Bienvenue dans<br>l'<em>arène</em></h2>
        <p class="rule-desc">
          Question Champion est un jeu de culture générale compétitif. Affrontez d'autres joueurs en temps réel, testez vos connaissances et grimpez dans le classement mondial. Trois modes, huit catégories, cinq divisions ELO — la profondeur d'un jeu de stratégie, le rythme d'un quiz.
        </p>
        <ul class="rule-details checklist-anim">
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>Le principe :</strong> chaque question affichée propose plusieurs réponses, vous sélectionnez la bonne avant la fin du timer.</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>Les points :</strong> 1 point par bonne réponse en règle générale, multiplié en cas de pari ou bonus selon le mode.</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>L'objectif :</strong> dépasser le score cible (variable selon le mode) ou battre l'adversaire avant la fin.</span></li>
        </ul>
      </div>
    </div>
  </section>

  <div class="rule-divider"></div>

  <!-- ═══ 02 — Mode Entraînement ═══ -->
  <section class="rule-section" data-hover>
    <div class="rule-bg-number">02</div>
    <div class="rule-content">
      <div class="rule-visual">
        <span class="rule-visual-icon"><svg class="rule-svg" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="100" cy="60" r="25"/><path d="M55 175 Q 60 110 100 110 Q 140 110 145 175"/><circle cx="100" cy="105" r="75" stroke-dasharray="3 7" opacity="0.5"/><circle cx="100" cy="105" r="92" stroke-dasharray="2 10" opacity="0.3"/></svg></span>
      </div>
      <div class="rule-text">
        <span class="rule-overline">Solo · Hors classement</span>
        <h2 class="rule-title">Mode <em>Entraînement</em></h2>
        <p class="rule-desc">
          Jouez seul, à votre rythme, sans risque pour votre ELO. Idéal pour découvrir le jeu, réviser une catégorie ou simplement enchaîner les questions sans pression.
        </p>
        <ul class="rule-details checklist-anim">
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Choisissez une catégorie ou jouez en mode mixte (toutes catégories)</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Timer adaptatif par question, score affiché en fin de partie</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Aucun impact sur l'ELO ni le classement mondial</span></li>
        </ul>
      </div>
    </div>
  </section>

  <div class="rule-divider"></div>

  <!-- ═══ 03 — Mode 1v1 ═══ -->
  <section class="rule-section" data-hover>
    <div class="rule-bg-number">03</div>
    <div class="rule-content">
      <div class="rule-visual">
        <span class="rule-visual-icon"><svg class="rule-svg" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="35" y1="35" x2="165" y2="165"/><line x1="25" y1="45" x2="45" y2="25"/><circle cx="35" cy="35" r="4"/><line x1="165" y1="155" x2="175" y2="165"/><line x1="165" y1="35" x2="35" y2="165"/><line x1="155" y1="25" x2="175" y2="45"/><circle cx="165" cy="35" r="4"/><line x1="25" y1="155" x2="35" y2="165"/></svg></span>
      </div>
      <div class="rule-text">
        <span class="rule-overline">Duel · Classé ou amical</span>
        <h2 class="rule-title">Mode <em>1v1</em></h2>
        <p class="rule-desc">
          Affrontez un adversaire en temps réel sur les mêmes questions. La précision compte autant que la vitesse. En version classée, votre ELO bouge à chaque match.
        </p>
        <ul class="rule-details checklist-anim">
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Matchmaking par lobby (code à partager) ou file d'attente classée</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Questions et timing strictement identiques pour les deux joueurs</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Gain/perte ELO calculé selon la zone du joueur (voir section 06)</span></li>
        </ul>
      </div>
    </div>
  </section>

  <div class="rule-divider"></div>

  <!-- ═══ 04 — Mode Championnat (avec les 3 manches) ═══ -->
  <section class="rule-section" data-hover>
    <div class="rule-bg-number">04</div>
    <div class="rule-content">
      <div class="rule-visual">
        <span class="rule-visual-icon"><svg class="rule-svg" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="20" y="40" width="40" height="120"/><rect x="80" y="60" width="40" height="100"/><rect x="140" y="80" width="40" height="80"/><line x1="15" y1="160" x2="185" y2="160"/><text x="40" y="32" font-family="JetBrains Mono" font-size="14" stroke-width="1.5" text-anchor="middle">M1</text><text x="100" y="52" font-family="JetBrains Mono" font-size="14" stroke-width="1.5" text-anchor="middle">M2</text><text x="160" y="72" font-family="JetBrains Mono" font-size="14" stroke-width="1.5" text-anchor="middle">M3</text><circle cx="40" cy="100" r="3" fill="currentColor"/><circle cx="100" cy="110" r="3" fill="currentColor"/><circle cx="160" cy="120" r="3" fill="currentColor"/></svg></span>
      </div>
      <div class="rule-text">
        <span class="rule-overline">Élimination · 4 joueurs · 3 manches</span>
        <h2 class="rule-title">Mode <em>Championnat</em></h2>
        <p class="rule-desc">
          Quatre joueurs, trois manches aux mécaniques distinctes, un seul champion. À chaque manche, le plus faible est éliminé. Le format le plus exigeant de QPC.
        </p>
        <ul class="rule-details checklist-anim">
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>M1 — Qualification (4→3) :</strong> jusqu'à 15 questions parallèles, 15s/question, premier à 9 points. Le dernier sort.</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>M2 — Pari secret (3→2) :</strong> 8 questions, sélection cachée de catégorie (20s) + mise secrète (1/2/3 pts) sur une question. Le plus faible sort.</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>M3 — Finale buzz (2→1) :</strong> les 2 finalistes choisissent 4 catégories (20s), placent une mise (15s), s'affrontent au buzz partagé sur 7 questions. Objectif 8 pts, mort subite si égalité.</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>Récompenses ELO :</strong> +50 (1er) / +30 (2e) / 0 (3e) / −20 (4e)</span></li>
        </ul>
      </div>
    </div>
  </section>

  <div class="rule-divider"></div>

  <!-- ═══ 05 — Catégories ═══ -->
  <section class="rule-section" data-hover>
    <div class="rule-bg-number">05</div>
    <div class="rule-content">
      <div class="rule-visual">
        <span class="rule-visual-icon"><svg class="rule-svg" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="100" cy="100" r="80"/><line x1="100" y1="20" x2="100" y2="180"/><line x1="20" y1="100" x2="180" y2="100"/><line x1="44" y1="44" x2="156" y2="156"/><line x1="156" y1="44" x2="44" y2="156"/><circle cx="100" cy="100" r="18"/><circle cx="100" cy="50" r="6"/><circle cx="150" cy="100" r="6"/><circle cx="100" cy="150" r="6"/><circle cx="50" cy="100" r="6"/><circle cx="135" cy="65" r="5"/><circle cx="135" cy="135" r="5"/><circle cx="65" cy="135" r="5"/><circle cx="65" cy="65" r="5"/></svg></span>
      </div>
      <div class="rule-text">
        <span class="rule-overline">Huit thèmes · 499 questions</span>
        <h2 class="rule-title">Les <em>catégories</em></h2>
        <p class="rule-desc">
          Les questions sont réparties en huit grands thèmes (une soixantaine chacun). La culture générale se gagne en largeur autant qu'en profondeur — impossible d'esquiver tous ses points faibles.
        </p>
        <ul class="rule-details checklist-anim">
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Sciences · Informatique · Histoire · Géographie</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">Mathématiques · Culture générale · Sport · Art &amp; Littérature</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text">La difficulté des questions s'adapte à votre zone ELO</span></li>
        </ul>
      </div>
    </div>
  </section>

  <div class="rule-divider"></div>

  <!-- ═══ 06 — ELO, Classement & Fair-play ═══ -->
  <section class="rule-section" data-hover>
    <div class="rule-bg-number">06</div>
    <div class="rule-content">
      <div class="rule-visual">
        <span class="rule-visual-icon"><svg class="rule-svg" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20,160 50,130 80,140 110,90 140,100 180,50"/><line x1="20" y1="180" x2="180" y2="180"/><line x1="20" y1="180" x2="20" y2="40"/><circle cx="50" cy="130" r="4" fill="currentColor"/><circle cx="80" cy="140" r="4" fill="currentColor"/><circle cx="110" cy="90" r="4" fill="currentColor"/><circle cx="140" cy="100" r="4" fill="currentColor"/><circle cx="180" cy="50" r="5" fill="currentColor"/><path d="M 165 65 L 180 50 L 195 65" stroke-width="2"/><text x="100" y="30" font-family="JetBrains Mono" font-size="14" stroke-width="1.5" text-anchor="middle">ELO</text></svg></span>
      </div>
      <div class="rule-text">
        <span class="rule-overline">Classement · Fair-play</span>
        <h2 class="rule-title">ELO &amp; <em>Classement</em></h2>
        <p class="rule-desc">
          Votre ELO démarre à 1200 et reflète votre niveau réel. Plus vous montez, plus il devient difficile de gagner — mais vous ne pouvez jamais tomber sous 1100, peu importe la série de défaites.
        </p>
        <ul class="rule-details checklist-anim">
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>5 zones :</strong> FILET (&lt;1200) · DIV3 (1200-1499) · DIV2 (1500-1799) · DIV1 (1800-1999) · ELITE (≥2000)</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>Gains 1v1 :</strong> +30 (DIV3) → +25 (DIV2) → +15 (DIV1) → +10 (ELITE). Zone FILET : défaite gratuite (perte = 0).</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>Classé vs Entre amis :</strong> seules les parties classées font évoluer l'ELO. Les parties amicales sont jouables en invité (pseudo auto-assigné).</span></li>
          <li class="check-item"><span class="check-box"><svg class="check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4,12 10,18 20,6"/></svg></span><span class="check-text"><strong>Déconnexion :</strong> fenêtre de 10s pour revenir. Au-delà, DQ automatique. Quitter volontairement une partie classée pénalise l'ELO.</span></li>
        </ul>
      </div>
    </div>
  </section>

</div>

<!-- STATS -->
<section class="stats-section section-reveal" id="classement">
  <div class="stats-grid">
    <div class="stat-item">
      <div class="stat-number" data-count="499">0</div>
      <div class="stat-label">Questions</div>
    </div>
    <div class="stat-item">
      <div class="stat-number" data-count="8">0</div>
      <div class="stat-label">Catégories</div>
    </div>
    <div class="stat-item">
      <div class="stat-number" data-count="3">0</div>
      <div class="stat-label">Modes de jeu</div>
    </div>
    <div class="stat-item">
      <div class="stat-number" data-count="5">0</div>
      <div class="stat-label">Divisions ELO</div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section section-reveal">
  <div class="cta-content">
    <span class="overline">Prêt à jouer ?</span>
    <h2>Devenez le<br><em>prochain champion</em></h2>
    <p>Rejoignez des milliers de joueurs et prouvez que vous êtes le maître de la culture générale.</p>
    <a href="game.php" class="cta-btn" data-hover>
      <span>Commencer à jouer</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="5" y1="12" x2="19" y2="12"></line>
        <polyline points="12 5 19 12 12 19"></polyline>
      </svg>
    </a>
  </div>
</section>

<!-- FOOTER -->
<footer class="page-footer">
  <p>&copy; 2025 — HESTIM — Question Champion</p>
</footer>

<script>
// ══════════════════════════════════════════════════════════
// THREE.JS — 3D GEM SCROLL-CONTROLLED
// ══════════════════════════════════════════════════════════
(function() {
  const canvas = document.getElementById('webgl-canvas');
  if (!canvas) return;

  const isMobile = window.matchMedia('(hover: none) and (pointer: coarse)').matches || window.innerWidth < 900;
  const detailLevel = isMobile ? 0 : 1;
  const particleCount = isMobile ? 15 : 40;

  const W = () => window.innerWidth;
  const H = () => window.innerHeight;

  const scene = new THREE.Scene();
  scene.fog = new THREE.Fog(0x040404, 7, 18);

  const camera = new THREE.PerspectiveCamera(45, W()/H(), 0.1, 100);
  camera.position.set(0, 0, 6);

  const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: !isMobile });
  renderer.setSize(W(), H());
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
  renderer.setClearColor(0x000000, 0);

  // === GEM ===
  const gemGroup = new THREE.Group();
  const shardMat = new THREE.MeshStandardMaterial({
    color: 0xd4af37,
    metalness: 0.95,
    roughness: 0.22,
    flatShading: true,
    side: THREE.DoubleSide,
    emissive: 0xd4af37,
    emissiveIntensity: 0
  });

  const baseGeo = new THREE.IcosahedronGeometry(1.3, detailLevel);
  const nonIndexed = baseGeo.toNonIndexed();
  const posArr = nonIndexed.attributes.position.array;
  const faceCount = posArr.length / 9;

  const shards = [];
  for (let f = 0; f < faceCount; f++) {
    const i = f * 9;
    const cx = (posArr[i]   + posArr[i+3] + posArr[i+6]) / 3;
    const cy = (posArr[i+1] + posArr[i+4] + posArr[i+7]) / 3;
    const cz = (posArr[i+2] + posArr[i+5] + posArr[i+8]) / 3;

    const faceGeo = new THREE.BufferGeometry();
    const verts = new Float32Array([
      posArr[i]   - cx, posArr[i+1] - cy, posArr[i+2] - cz,
      posArr[i+3] - cx, posArr[i+4] - cy, posArr[i+5] - cz,
      posArr[i+6] - cx, posArr[i+7] - cy, posArr[i+8] - cz,
    ]);
    faceGeo.setAttribute('position', new THREE.BufferAttribute(verts, 3));
    faceGeo.computeVertexNormals();

    const mesh = new THREE.Mesh(faceGeo, shardMat);
    mesh.position.set(cx, cy, cz);
    const dir = new THREE.Vector3(cx, cy, cz).normalize();
    mesh.userData = {
      basePos: new THREE.Vector3(cx, cy, cz),
      dir: dir,
      explodeAmount: 0.8 + Math.random() * 1.2,
      spinAxis: new THREE.Vector3(
        Math.random() - 0.5,
        Math.random() - 0.5,
        Math.random() - 0.5
      ).normalize(),
      spinAccum: 0,
      delay: Math.random() * 0.35,
      velocity: new THREE.Vector3()
    };
    shards.push(mesh);
    gemGroup.add(mesh);
  }

  const coreMesh = new THREE.Mesh(
    new THREE.SphereGeometry(0.55, 32, 32),
    new THREE.MeshBasicMaterial({ color: 0xfcf6ba, transparent: true, opacity: 0.1 })
  );
  gemGroup.add(coreMesh);

  const haloMesh = new THREE.Mesh(
    new THREE.SphereGeometry(1.0, 32, 32),
    new THREE.MeshBasicMaterial({
      color: 0xd4af37, transparent: true, opacity: 0.04, blending: THREE.AdditiveBlending
    })
  );
  gemGroup.add(haloMesh);

  const coreLight = new THREE.PointLight(0xfcf6ba, 0.2, 8);
  gemGroup.add(coreLight);

  scene.add(gemGroup);

  // === LIGHTING ===
  scene.add(new THREE.AmbientLight(0xffffff, 0.12));
  const keyLight = new THREE.DirectionalLight(0xd4af37, 1.0);
  keyLight.position.set(3, 4, 5);
  scene.add(keyLight);
  const fillLight = new THREE.DirectionalLight(0xfcf6ba, 0.35);
  fillLight.position.set(-4, -1, 2);
  scene.add(fillLight);
  const rimLight = new THREE.DirectionalLight(0xffffff, 0.65);
  rimLight.position.set(0, 3, -5);
  scene.add(rimLight);

  // === ATMOSPHERIC PARTICLES ===
  const particleGeo = new THREE.BufferGeometry();
  const pPos = new Float32Array(particleCount * 3);
  const pVel = new Float32Array(particleCount * 3);
  for (let i = 0; i < particleCount; i++) {
    const r = 2.5 + Math.random() * 4;
    const theta = Math.random() * Math.PI * 2;
    const phi = Math.acos(2 * Math.random() - 1);
    pPos[i*3]   = r * Math.sin(phi) * Math.cos(theta);
    pPos[i*3+1] = r * Math.sin(phi) * Math.sin(theta);
    pPos[i*3+2] = r * Math.cos(phi);
    pVel[i*3]   = (Math.random() - 0.5) * 0.0008;
    pVel[i*3+1] = (Math.random() - 0.5) * 0.0008;
    pVel[i*3+2] = (Math.random() - 0.5) * 0.0008;
  }
  particleGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
  const particles = new THREE.Points(
    particleGeo,
    new THREE.PointsMaterial({
      color: 0xd4af37, size: 0.03, transparent: true, opacity: 0.5,
      blending: THREE.AdditiveBlending, sizeAttenuation: true
    })
  );
  scene.add(particles);

  // === SCROLL-CONTROLLED OPEN STATE ===
  let scrollOpenProgress = 0;
  let targetScrollOpen = 0;
  let mouseX = 0, mouseY = 0;

  document.addEventListener('mousemove', (e) => {
    mouseX = (e.clientX / W()) * 2 - 1;
    mouseY = -(e.clientY / H()) * 2 + 1;
  });

  // === ANIMATION ===
  let time = 0;
  let frameSkip = 0;

  function animate() {
    requestAnimationFrame(animate);
    if (document.hidden) return;
    if (isMobile) {
      frameSkip = (frameSkip + 1) % 2;
      if (frameSkip !== 0) return;
    }

    time += 0.01;

    // Smooth lerp to target scroll open state
    scrollOpenProgress += (targetScrollOpen - scrollOpenProgress) * 0.06;
    if (scrollOpenProgress < 0.001) scrollOpenProgress = 0;

    // Auto rotation (always spinning)
    gemGroup.rotation.y += 0.0015 + scrollOpenProgress * 0.003;
    gemGroup.rotation.x = Math.sin(time * 0.3) * 0.1 * (1 - scrollOpenProgress * 0.5);

    // Mouse parallax
    camera.position.x += (mouseX * 0.7 - camera.position.x) * 0.03;
    camera.position.y += (mouseY * 0.4 - camera.position.y) * 0.03;
    camera.lookAt(0, 0, 0);

    // Bobbing
    gemGroup.position.y = Math.sin(time * 0.7) * 0.15;

    // Mouse in gem-local space for repulsion
    const mouseVec = new THREE.Vector3(mouseX, mouseY, 0.5);
    mouseVec.unproject(camera);
    const rayDir = mouseVec.sub(camera.position).normalize();
    const distToPlane = (-camera.position.z) / (rayDir.z || -0.001);
    const mouseWorld = camera.position.clone().add(rayDir.multiplyScalar(distToPlane));
    gemGroup.updateMatrixWorld();
    const localMouse = mouseWorld.clone().applyMatrix4(
      new THREE.Matrix4().copy(gemGroup.matrixWorld).invert()
    );

    // Shards: open based on scroll + repulsion from mouse
    shards.forEach(shard => {
      const ud = shard.userData;
      const localOpen = Math.max(0, (scrollOpenProgress - ud.delay)) / Math.max(0.0001, 1 - ud.delay);
      const easedOpen = localOpen * localOpen * (3 - 2 * localOpen);
      const off = easedOpen * ud.explodeAmount;

      const tx = ud.basePos.x + ud.dir.x * off;
      const ty = ud.basePos.y + ud.dir.y * off;
      const tz = ud.basePos.z + ud.dir.z * off;

      ud.velocity.x += (tx - shard.position.x) * 0.06;
      ud.velocity.y += (ty - shard.position.y) * 0.06;
      ud.velocity.z += (tz - shard.position.z) * 0.06;

      // Mouse repulsion when open
      if (scrollOpenProgress > 0.15) {
        const dx = shard.position.x - localMouse.x;
        const dy = shard.position.y - localMouse.y;
        const dz = shard.position.z - localMouse.z;
        const dist = Math.sqrt(dx*dx + dy*dy + dz*dz + 0.001);
        const repulsionRadius = 1.7;
        if (dist < repulsionRadius) {
          const force = (1 - dist/repulsionRadius) * 0.12 * scrollOpenProgress;
          ud.velocity.x += (dx / dist) * force;
          ud.velocity.y += (dy / dist) * force;
          ud.velocity.z += (dz / dist) * force * 0.4;
        }
      }

      ud.velocity.multiplyScalar(0.85);
      shard.position.x += ud.velocity.x;
      shard.position.y += ud.velocity.y;
      shard.position.z += ud.velocity.z;

      if (localOpen > 0.02) {
        ud.spinAccum += 0.022 * localOpen;
      } else {
        ud.spinAccum *= 0.92;
      }
      shard.quaternion.setFromAxisAngle(ud.spinAxis, ud.spinAccum);
    });

    // Core/halo grow with open state
    const corePulse = 1 + Math.sin(time * 3) * 0.05;
    coreMesh.scale.setScalar(corePulse * (0.5 + 0.5 * scrollOpenProgress));
    coreMesh.material.opacity = 0.1 + 0.6 * scrollOpenProgress;
    haloMesh.scale.setScalar((1 + Math.sin(time * 2) * 0.05) * (0.6 + 0.6 * scrollOpenProgress));
    haloMesh.material.opacity = 0.04 + 0.25 * scrollOpenProgress;
    coreLight.intensity = 0.2 + 2.5 * scrollOpenProgress;
    shardMat.emissiveIntensity = scrollOpenProgress * 0.3;

    // Atmospheric particles
    const pArr = particleGeo.attributes.position.array;
    for (let i = 0; i < particleCount; i++) {
      pArr[i*3]   += pVel[i*3];
      pArr[i*3+1] += pVel[i*3+1];
      pArr[i*3+2] += pVel[i*3+2];
      const d = Math.sqrt(pArr[i*3]**2 + pArr[i*3+1]**2 + pArr[i*3+2]**2);
      if (d > 6) {
        pVel[i*3]   *= -0.5;
        pVel[i*3+1] *= -0.5;
        pVel[i*3+2] *= -0.5;
      }
    }
    particleGeo.attributes.position.needsUpdate = true;
    particles.rotation.y += 0.0004;

    renderer.render(scene, camera);
  }
  animate();

  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      camera.aspect = W() / H();
      camera.updateProjectionMatrix();
      renderer.setSize(W(), H());
    }, 100);
  });

  // Expose scroll control to GSAP
  window.gemScrollState = { target: targetScrollOpen };
  Object.defineProperty(window.gemScrollState, 'progress', {
    get: () => targetScrollOpen,
    set: (v) => { targetScrollOpen = v; }
  });
})();

// ═══════════════════════════════════════════════════════════
// GSAP — SCROLL ANIMATIONS + GEM CONTROL
// ═══════════════════════════════════════════════════════════
gsap.registerPlugin(ScrollTrigger);

// Hero entrance
const heroTl = gsap.timeline({ delay: 0.3 });
heroTl
  .to('.hero-label', { opacity: 1, y: 0, duration: 0.8, ease: 'expo.out' })
  .to('.hero-title', { opacity: 1, duration: 0.01 }, '-=0.4')
  .to('.hero-title .line-inner', { 
    y: 0, 
    duration: 1.2, 
    stagger: 0.15, 
    ease: 'expo.out' 
  }, '-=0.01')
  .to('.hero-subtitle', { opacity: 1, y: 0, duration: 0.8, ease: 'expo.out' }, '-=0.6')
  .to('.hero-hud', { opacity: 1, duration: 0.6, ease: 'power2.out' }, '-=0.4')
  .to('.qpc-header', { opacity: 1, duration: 0.6 }, '-=0.8');

// Hero parallax on scroll
gsap.fromTo('.hero-title',
  { y: 0, opacity: 1 },
  {
    y: -150,
    opacity: 0,
    ease: 'none',
    scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true }
  }
);

gsap.fromTo('.hero-subtitle',
  { y: 0, opacity: 1 },
  {
    y: -80,
    opacity: 0,
    ease: 'none',
    scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true }
  }
);

// Scroll progress bar
gsap.to('.scroll-progress', {
  scaleX: 1,
  ease: 'none',
  scrollTrigger: { trigger: 'body', start: 'top top', end: 'bottom bottom', scrub: true }
});

// ═══════════════════════════════════════════════════════════
// GEM SCROLL TIMELINE — Bidirectional (up/down)
// ═══════════════════════════════════════════════════════════
const gemHint = document.getElementById('gem-hint');

// Show hint briefly after hero loads
setTimeout(() => {
  if (gemHint) gemHint.classList.add('visible');
  setTimeout(() => {
    if (gemHint) gemHint.classList.remove('visible');
  }, 3000);
}, 2500);

// Single timeline that handles both directions perfectly
const gemScrollTl = gsap.timeline({
  scrollTrigger: {
    trigger: 'body',
    start: 'top top',
    end: 'bottom bottom',
    scrub: 1.5
  }
});

// 0% → 1% : CLOSED (very brief plateau)
gemScrollTl.fromTo(window.gemScrollState, 
  { progress: 0 }, 
  { progress: 0, duration: 0.01, ease: 'none' }
);

// 1% → 8% : OPENING (0 → 1) — quick reveal
gemScrollTl.to(window.gemScrollState, { 
  progress: 1, 
  duration: 0.07, 
  ease: 'none' 
});

// 8% → 75% : OPEN MAX (plateau at 1)
gemScrollTl.to(window.gemScrollState, { 
  progress: 1, 
  duration: 0.67, 
  ease: 'none' 
});

// 75% → 100% : CLOSING (1 → 0)
gemScrollTl.to(window.gemScrollState, { 
  progress: 0, 
  duration: 0.25, 
  ease: 'none' 
});

// ═══════════════════════════════════════════════════════════
// REVERSE SCROLL BEHAVIOR (footer → header):
// 100%→75% : opens back up (inverse of closing)
// 75%→8%   : stays open max
// 8%→1%    : closes back down (inverse of opening)
// 1%→0%    : stays closed
// ═══════════════════════════════════════════════════════════

// Section reveals
gsap.utils.toArray('.section-reveal').forEach((section) => {
  gsap.from(section, {
    y: 60,
    opacity: 0,
    duration: 1,
    ease: 'expo.out',
    scrollTrigger: { trigger: section, start: 'top 85%', toggleActions: 'play none none none' }
  });
});

// ═══════════════════════════════════════════════════════════
// RULES SECTIONS
// ═══════════════════════════════════════════════════════════
gsap.utils.toArray('.rule-section').forEach((section, i) => {
  const bgNum = section.querySelector('.rule-bg-number');
  const visual = section.querySelector('.rule-visual');
  const text = section.querySelector('.rule-text');
  const icon = section.querySelector('.rule-visual-icon');
  
  gsap.fromTo(bgNum, 
    { x: i % 2 === 0 ? 200 : -200, opacity: 0 },
    {
      x: 0,
      opacity: 0.15,
      duration: 1.5,
      ease: 'expo.out',
      scrollTrigger: {
        trigger: section,
        start: 'top 90%',
        end: 'center center',
        scrub: true
      }
    }
  );
  
  gsap.to(bgNum, {
    y: -100,
    ease: 'none',
    scrollTrigger: {
      trigger: section,
      start: 'top bottom',
      end: 'bottom top',
      scrub: true
    }
  });
  
  gsap.from(visual, {
    x: i % 2 === 0 ? -100 : 100,
    opacity: 0,
    scale: 0.8,
    duration: 1.5,
    ease: 'expo.out',
    scrollTrigger: {
      trigger: section,
      start: 'top 75%',
      toggleActions: 'play none none none'
    }
  });
  
  gsap.from(text, {
    y: 80,
    opacity: 0,
    duration: 1.2,
    delay: 0.3,
    ease: 'expo.out',
    scrollTrigger: {
      trigger: section,
      start: 'top 70%',
      toggleActions: 'play none none none'
    }
  });
  
  gsap.to(icon, {
    y: -20,
    rotation: 3,
    duration: 4,
    ease: 'sine.inOut',
    yoyo: true,
    repeat: -1,
    scrollTrigger: {
      trigger: section,
      start: 'top bottom',
      toggleActions: 'play pause resume pause'
    }
  });
  
  section.addEventListener('mouseenter', () => {
    gsap.to(icon, {
      scale: 1.1,
      filter: 'drop-shadow(0 0 80px var(--gold-glow)) drop-shadow(0 0 160px rgba(212,175,55,0.3))',
      duration: 0.6,
      ease: 'expo.out'
    });
  });
  
  section.addEventListener('mouseleave', () => {
    gsap.to(icon, {
      scale: 1,
      filter: 'drop-shadow(0 0 60px var(--gold-glow)) drop-shadow(0 0 120px rgba(212,175,55,0.2))',
      duration: 0.6,
      ease: 'expo.out'
    });
  });
});

gsap.utils.toArray('.rule-divider').forEach((div) => {
  gsap.from(div, {
    scaleX: 0,
    duration: 1,
    ease: 'expo.out',
    scrollTrigger: {
      trigger: div,
      start: 'top 90%',
      toggleActions: 'play none none none'
    }
  });
});

// Featured rows
gsap.utils.toArray('.featured-row').forEach((row, i) => {
  const visual = row.querySelector('.featured-visual');
  const content = row.querySelector('.featured-content');
  
  gsap.from(visual, {
    x: i % 2 === 0 ? -100 : 100,
    opacity: 0,
    duration: 1.2,
    ease: 'expo.out',
    scrollTrigger: { trigger: row, start: 'top 80%', toggleActions: 'play none none none' }
  });
  
  gsap.from(content, {
    x: i % 2 === 0 ? 100 : -100,
    opacity: 0,
    duration: 1.2,
    delay: 0.2,
    ease: 'expo.out',
    scrollTrigger: { trigger: row, start: 'top 80%', toggleActions: 'play none none none' }
  });
  
  gsap.to(visual, {
    y: -50,
    ease: 'none',
    scrollTrigger: { trigger: row, start: 'top bottom', end: 'bottom top', scrub: true }
  });
});

// Stats counter
gsap.utils.toArray('.stat-number[data-count]').forEach((stat) => {
  const target = parseInt(stat.dataset.count);
  const obj = { val: 0 };
  
  gsap.to(obj, {
    val: target,
    duration: 2,
    ease: 'power2.out',
    scrollTrigger: { trigger: stat, start: 'top 85%', toggleActions: 'play none none none' },
    onUpdate: () => {
      if (target >= 1000) {
        stat.textContent = Math.floor(obj.val / 1000) + 'K+';
      } else {
        stat.textContent = Math.floor(obj.val);
      }
    }
  });
});

// CTA reveal
gsap.from('.cta-content', {
  y: 80,
  opacity: 0,
  duration: 1.2,
  ease: 'expo.out',
  scrollTrigger: { trigger: '.cta-section', start: 'top 75%', toggleActions: 'play none none none' }
});

// ═══════════════════════════════════════════════════════════
// CHECKLIST + SVG DRAW
// ═══════════════════════════════════════════════════════════
gsap.utils.toArray('.rule-section').forEach((section) => {
  const items = section.querySelectorAll('.checklist-anim .check-item');
  const svg = section.querySelector('.rule-svg');

  ScrollTrigger.create({
    trigger: section,
    start: 'top 65%',
    once: true,
    onEnter: () => {
      if (svg) {
        setTimeout(() => svg.classList.add('icon-drawn'), 200);
      }
      items.forEach((item, i) => {
        setTimeout(() => item.classList.add('checked'), 400 + i * 280);
      });
    }
  });
});

gsap.utils.toArray('.featured-visual .rule-svg').forEach((svg) => {
  ScrollTrigger.create({
    trigger: svg,
    start: 'top 80%',
    once: true,
    onEnter: () => setTimeout(() => svg.classList.add('icon-drawn'), 150)
  });
});

// ═══════════════════════════════════════════════════════════
// CUSTOM CURSOR
// ═══════════════════════════════════════════════════════════
(function() {
  const isTouchDevice = window.matchMedia('(hover: none) and (pointer: coarse)').matches;
  const isMobileWidth = window.innerWidth < 900;

  if (isTouchDevice || isMobileWidth) {
    document.querySelectorAll('.cursor, .cursor-follower, .cursor-label').forEach(el => {
      if (el) el.style.display = 'none';
    });
    document.body.style.cursor = 'auto';
    return;
  }

  document.body.classList.add('custom-cursor-active');
  
  const cursor = document.querySelector('.cursor');
  const follower = document.querySelector('.cursor-follower');
  const label = document.querySelector('.cursor-label');
  
  if (!cursor || !follower) return;
  
  let mouseX = 0, mouseY = 0;
  let followerX = 0, followerY = 0;
  
  document.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;
    cursor.style.left = mouseX - 4 + 'px';
    cursor.style.top = mouseY - 4 + 'px';
  });
  
  function animateFollower() {
    followerX += (mouseX - followerX) * 0.15;
    followerY += (mouseY - followerY) * 0.15;
    follower.style.left = followerX - 20 + 'px';
    follower.style.top = followerY - 20 + 'px';
    if (label) {
      label.style.left = followerX + 20 + 'px';
      label.style.top = followerY - 10 + 'px';
    }
    requestAnimationFrame(animateFollower);
  }
  animateFollower();
  
  document.querySelectorAll('[data-hover]').forEach(el => {
    el.addEventListener('mouseenter', () => {
      follower.classList.add('hovering');
      if (label) label.style.opacity = '1';
    });
    el.addEventListener('mouseleave', () => {
      follower.classList.remove('hovering');
      if (label) label.style.opacity = '0';
    });
  });
})();

// ═══════════════════════════════════════════════════════════
// THEME TOGGLE
// ═══════════════════════════════════════════════════════════
(function() {
  const root = document.documentElement;
  const toggle = document.getElementById('theme-toggle');
  if (!toggle) return;
  
  try {
    const stored = localStorage.getItem('qpc-theme');
    if (stored === 'light') root.classList.add('light');
  } catch (e) {}
  
  toggle.addEventListener('click', () => {
    root.classList.add('theme-transitioning');
    const isLight = root.classList.toggle('light');
    try { localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); } catch (e) {}
    setTimeout(() => root.classList.remove('theme-transitioning'), 400);
  });
})();

// ═══════════════════════════════════════════════════════════
// MOBILE DRAWER (QPC standard pattern)
// ═══════════════════════════════════════════════════════════
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
  if (closeBtn) closeBtn.addEventListener('click', closeMenu);
  if (backdrop) backdrop.addEventListener('click', closeMenu);

  menu.querySelectorAll('[data-close]').forEach(el => {
    el.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) closeMenu();
  });
})();

// ═══════════════════════════════════════════════════════════
// COORDINATES UPDATE
// ═══════════════════════════════════════════════════════════
(function() {
  const coords = document.querySelectorAll('.hero-coords');
  if (!coords.length) return;
  
  setInterval(() => {
    coords.forEach(c => {
      if (c.classList.contains('tl')) {
        c.textContent = `LAT: ${(46.2 + Math.random() * 0.01).toFixed(4)}° N`;
      } else if (c.classList.contains('tr')) {
        c.textContent = `LNG: ${(6.14 + Math.random() * 0.01).toFixed(4)}° E`;
      } else if (c.classList.contains('bl')) {
        c.textContent = `ALT: ${(1234 + Math.floor(Math.random() * 10)).toLocaleString()}m`;
      } else if (c.classList.contains('br')) {
        c.textContent = `TEMP: ${(20 + Math.floor(Math.random() * 3))}°C`;
      }
    });
  }, 3000);
})();

// ═══════════════════════════════════════════════════════════
// PAGE LOADER
// ═══════════════════════════════════════════════════════════
window.addEventListener('load', () => {
  const loader = document.getElementById('page-loader');
  if (loader) {
    setTimeout(() => loader.classList.add('done'), 400);
  }
});

</script>
</body>
</html>