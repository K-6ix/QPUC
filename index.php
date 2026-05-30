<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Question pour un Champion</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
<style>
:root {
  --gold-light: #fcf6ba;
  --gold-base: #d4af37;
  --gold-dark: #8a6e2f;
  --gold-glow: rgba(212,175,55,0.35);
  --bg: #060606;
  --bg2: #0d0d0d;
  --text: #ffffff;
  --metallic: linear-gradient(to right, var(--gold-dark), var(--gold-base) 30%, var(--gold-light) 50%, var(--gold-base) 70%, var(--gold-dark));
}

*{margin:0;padding:0;box-sizing:border-box;}

html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Montserrat', sans-serif;
  overflow-x: hidden;
}

/* ── NOISE OVERLAY ── */
body::before {
  content:'';
  position:fixed;
  inset:0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  opacity: 0.03;
  pointer-events: none;
  z-index: 9999;
}

/* ════════════════════════════
   HEADER
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
  border-bottom: 1px solid rgba(212,175,55,0.2);
  background: rgba(6,6,6,0.85);
  backdrop-filter: blur(12px);
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
  text-transform: uppercase;
  filter: drop-shadow(0 0 6px var(--gold-glow));
}

nav ul {
  display: flex;
  list-style: none;
  gap: 28px;
  align-items: center;
  justify-content: center;
}

nav a {
  text-decoration: none;
  color: rgba(255,255,255,0.75);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  position: relative;
  transition: color 0.3s;
}
nav a:hover { color: var(--gold-light); }
nav a::after {
  content:'';
  position:absolute;
  width:0; height:1px;
  bottom:-4px; left:0;
  background: var(--metallic);
  transition: width 0.3s;
}
nav a:hover::after { width:100%; }

.btn-play {
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
.btn-play:hover { transform: scale(1.05); box-shadow: 0 0 22px rgba(212,175,55,0.7); }
.btn-play::after { display:none; }

.btn-connexion {
  justify-self: end;
  background: transparent;
  border: 1px solid rgba(212,175,55,0.5);
  color: var(--gold-light) !important;
  -webkit-text-fill-color: var(--gold-light) !important;
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
  -webkit-text-fill-color: #000 !important;
  border-color: transparent;
  box-shadow: 0 0 18px var(--gold-glow);
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
  border: 1px solid rgba(212,175,55,0.07);
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
  color: #fff;
  text-transform: uppercase;
}
.hero-title .script {
  font-family: 'Great Vibes', cursive;
  font-size: clamp(3.5rem, 9vw, 7rem);
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 0 10px var(--gold-glow));
  margin-top: -40px;
}

.hero-sub {
  margin-top: 20px;
  font-size: 0.9rem;
  color: rgba(255,255,255,0.45);
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
}

.cta-primary {
  background: var(--metallic);
  color: #000;
  padding: 14px 40px;
  border-radius: 40px;
  font-weight: 900;
  font-size: 0.9rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-decoration: none;
  border: none;
  cursor: pointer;
  box-shadow: 0 0 20px var(--gold-glow), 0 4px 20px rgba(0,0,0,0.5);
  transition: transform 0.2s, box-shadow 0.2s;
  display: inline-block;
}
.cta-primary:hover { transform: scale(1.06) translateY(-2px); box-shadow: 0 0 35px rgba(212,175,55,0.7), 0 8px 30px rgba(0,0,0,0.5); }

.cta-secondary {
  background: transparent;
  color: rgba(255,255,255,0.7);
  padding: 14px 40px;
  border-radius: 40px;
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-decoration: none;
  border: 1px solid rgba(255,255,255,0.15);
  transition: all 0.3s;
  display: inline-block;
}
.cta-secondary:hover { border-color: var(--gold-base); color: var(--gold-light); }

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
.scroll-hint span { font-size: 0.65rem; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,0.3); }
.scroll-arrow {
  width: 20px; height: 20px;
  border-right: 1px solid rgba(212,175,55,0.4);
  border-bottom: 1px solid rgba(212,175,55,0.4);
  transform: rotate(45deg);
  animation: bounce 1.5s ease infinite;
}

/* ════════════════════════════
   STATS BAR
════════════════════════════ */
.stats-bar {
  border-top: 1px solid rgba(212,175,55,0.15);
  border-bottom: 1px solid rgba(212,175,55,0.15);
  background: rgba(212,175,55,0.04);
  padding: 32px 40px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  text-align: center;
  gap: 20px;
}
.stat-item {}
.stat-num {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: 2.5rem;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.stat-label {
  font-size: 0.7rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.35);
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
}
.section-title em {
  font-style: normal;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.modes-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.mode-card {
  background: var(--bg2);
  border: 1px solid rgba(212,175,55,0.12);
  border-radius: 16px;
  padding: 36px 28px;
  position: relative;
  overflow: hidden;
  transition: border-color 0.3s, transform 0.3s;
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
.mode-card:hover { border-color: rgba(212,175,55,0.4); transform: translateY(-4px); }
.mode-card:hover::before { opacity:1; }

.mode-card.featured {
  border-color: rgba(212,175,55,0.35);
  background: linear-gradient(135deg, rgba(212,175,55,0.07), var(--bg2));
}
.mode-card.featured::after {
  content: 'POPULAIRE';
  position:absolute;
  top: 16px; right: 16px;
  background: var(--metallic);
  color: #000;
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
  margin-bottom: 12px;
}
.mode-desc {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.45);
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
  border: 1px solid rgba(212,175,55,0.25);
  color: rgba(212,175,55,0.7);
  text-transform: uppercase;
}

/* ════════════════════════════
   FEATURES SECTION
════════════════════════════ */
.features-section {
  padding: 100px 40px;
  background: var(--bg2);
  border-top: 1px solid rgba(212,175,55,0.1);
  border-bottom: 1px solid rgba(212,175,55,0.1);
}
.features-inner {
  max-width: 1200px;
  margin: 0 auto;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2px;
  border: 1px solid rgba(212,175,55,0.1);
  border-radius: 16px;
  overflow: hidden;
}

.feature-item {
  padding: 40px 36px;
  background: var(--bg2);
  border: 1px solid rgba(212,175,55,0.06);
  transition: background 0.3s;
  position: relative;
}
.feature-item:hover { background: rgba(212,175,55,0.04); }

.feature-num {
  font-family: 'Kanit', sans-serif;
  font-size: 3rem;
  font-weight: 900;
  color: rgba(212,175,55,0.08);
  position:absolute;
  top: 20px; right: 24px;
  line-height:1;
}
.feature-title {
  font-size: 1rem;
  font-weight: 900;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--gold-light);
  margin-bottom: 10px;
}
.feature-desc {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.4);
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
  background: var(--bg2);
  border: 1px solid rgba(212,175,55,0.12);
  border-radius: 16px;
  overflow: hidden;
}

.lb-header {
  display: grid;
  grid-template-columns: 60px 1fr 120px 120px;
  padding: 16px 28px;
  background: rgba(212,175,55,0.06);
  border-bottom: 1px solid rgba(212,175,55,0.1);
}
.lb-header span {
  font-size: 0.65rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: rgba(255,255,255,0.3);
}

.lb-row {
  display: grid;
  grid-template-columns: 60px 1fr 120px 120px;
  padding: 20px 28px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  align-items: center;
  transition: background 0.2s;
}
.lb-row:hover { background: rgba(212,175,55,0.04); }
.lb-row:last-child { border-bottom: none; }

.lb-rank {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: 1.1rem;
}
.lb-rank.gold { background: var(--metallic); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.lb-rank.silver { color: #c0c0c0; }
.lb-rank.bronze { color: #cd7f32; }
.lb-rank.other  { color: rgba(255,255,255,0.25); }

.lb-player {
  display: flex;
  align-items: center;
  gap: 14px;
}
.lb-avatar {
  width: 38px; height: 38px;
  border-radius: 50%;
  background: rgba(212,175,55,0.15);
  border: 1px solid rgba(212,175,55,0.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--gold-base);
}
.lb-name { font-size: 0.9rem; font-weight: 700; }
.lb-badge { font-size: 0.6rem; letter-spacing: 1px; background: rgba(212,175,55,0.1); color: var(--gold-base); padding: 2px 8px; border-radius: 10px; margin-left: 8px; }

.lb-score {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: 1.1rem;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.lb-games { font-size: 0.8rem; color: rgba(255,255,255,0.3); }

.lb-soon {
  text-align: center;
  padding: 16px;
  font-size: 0.7rem;
  letter-spacing: 3px;
  color: rgba(255,255,255,0.2);
  text-transform: uppercase;
  border-top: 1px solid rgba(212,175,55,0.08);
}

/* ════════════════════════════
   CTA BANNER
════════════════════════════ */
.cta-banner {
  margin: 0 40px 100px;
  background: linear-gradient(135deg, rgba(212,175,55,0.08), rgba(212,175,55,0.03));
  border: 1px solid rgba(212,175,55,0.2);
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
}
.cta-banner p {
  color: rgba(255,255,255,0.4);
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
  border-top: 1px solid rgba(212,175,55,0.15);
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
  border-bottom: 1px solid rgba(212,175,55,0.07);
}

.footer-brand {}
.footer-logo {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: 1.4rem;
  letter-spacing: 4px;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-transform: uppercase;
  display: block;
  margin-bottom: 8px;
}
.footer-tagline {
  font-size: 0.7rem;
  letter-spacing: 3px;
  color: rgba(255,255,255,0.2);
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
  color: rgba(255,255,255,0.35);
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
.footer-nav a:hover { color: var(--gold-light); }
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
  color: #000;
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
  color: rgba(255,255,255,0.12);
  text-transform: uppercase;
}
.footer-school {
  font-size: 0.65rem;
  letter-spacing: 2px;
  color: rgba(212,175,55,0.25);
  text-transform: uppercase;
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
@media (max-width: 900px) {
  header { grid-template-columns: 1fr auto; }
  .btn-connexion { grid-column: span 0; }
  .modes-grid { grid-template-columns: 1fr; }
  .features-grid { grid-template-columns: 1fr; }
  .stats-bar { grid-template-columns: repeat(2, 1fr); }
  .lb-header, .lb-row { grid-template-columns: 50px 1fr 100px; }
  .lb-games { display: none; }
  .cta-banner { margin: 0 20px 60px; padding: 50px 30px; }
}

@media (max-width: 600px) {
  header { grid-template-columns: 1fr; justify-items: center; gap: 10px; height: auto; padding: 16px 20px; }
  nav ul { gap: 14px; flex-wrap: wrap; justify-content: center; }
  .btn-connexion { justify-self: center; }
  .section, .leaderboard-section { padding: 60px 20px; }
  .features-section { padding: 60px 20px; }
  .stats-bar { padding: 24px 20px; grid-template-columns: repeat(2,1fr); }
  .footer-top { grid-template-columns: 1fr; text-align: center; padding: 40px 24px 24px; }
  .footer-cta-col { justify-content: center; }
  .footer-nav { flex-direction: row; flex-wrap: wrap; justify-content: center; }
  .footer-bottom { flex-direction: column; text-align: center; padding: 16px 24px; }
}
</style>
</head>
<body>

<!-- ════ HEADER ════ -->
<header>
  <div class="logo">HESTIM</div>
  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="rules.php">Rules</a></li>
      <li><a href="game.php" class="btn-play">▶ Play</a></li>
      <li><a href="#classement">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
    </ul>
  </nav>
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="dashboard.php" class="btn-connexion">Dashboard</a>
  <?php else: ?>
    <a href="connexion.php" class="btn-connexion">Connexion</a>
  <?php endif; ?>
</header>

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
      <span class="mode-icon">🤝</span>
      <div class="mode-name">2 vs 2</div>
      <p class="mode-desc">La communication et la stratégie d'équipe font la différence. Combinés vos forces pour écraser l'adversaire.</p>
      <div class="mode-tags">
        <span class="tag">Travail d'équipe</span>
        <span class="tag">Stratégie</span>
        <span class="tag">Coopération</span>
      </div>
    </div>

    <div class="mode-card" style="grid-column: span 3;">
      <span class="mode-icon">🏆</span>
      <div class="mode-name">Tournoi</div>
      <p class="mode-desc" style="max-width:600px;">Le mode compétition ultime. Éliminations progressives, tableau dynamique, classement général et statistiques détaillées. Seul le meilleur survivra.</p>
      <div class="mode-tags">
        <span class="tag">Élimination progressive</span>
        <span class="tag">Tableau dynamique</span>
        <span class="tag">Classement général</span>
        <span class="tag">Statistiques</span>
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
      <span>Score</span>
      <span>Parties</span>
    </div>
    <div class="lb-row">
      <div class="lb-rank gold">01</div>
      <div class="lb-player">
        <div class="lb-avatar">OE</div>
        <div>
          <span class="lb-name">Othmane E.</span>
          <span class="lb-badge">Champion</span>
        </div>
      </div>
      <div class="lb-score">12 840</div>
      <div class="lb-games">47 parties</div>
    </div>
    <div class="lb-row">
      <div class="lb-rank silver">02</div>
      <div class="lb-player">
        <div class="lb-avatar">MB</div>
        <div><span class="lb-name">Maxime B.</span></div>
      </div>
      <div class="lb-score">11 200</div>
      <div class="lb-games">39 parties</div>
    </div>
    <div class="lb-row">
      <div class="lb-rank bronze">03</div>
      <div class="lb-player">
        <div class="lb-avatar">ON</div>
        <div><span class="lb-name">Ousmane N.</span></div>
      </div>
      <div class="lb-score">9 750</div>
      <div class="lb-games">33 parties</div>
    </div>
    <div class="lb-row">
      <div class="lb-rank other">04</div>
      <div class="lb-player">
        <div class="lb-avatar">AY</div>
        <div><span class="lb-name">Abdelghafour Y.</span></div>
      </div>
      <div class="lb-score">8 300</div>
      <div class="lb-games">28 parties</div>
    </div>
    <div class="lb-row">
      <div class="lb-rank other">05</div>
      <div class="lb-player">
        <div class="lb-avatar">BA</div>
        <div><span class="lb-name">Bamba A.</span></div>
      </div>
      <div class="lb-score">7 610</div>
      <div class="lb-games">24 parties</div>
    </div>
    <div class="lb-soon">Connectez-vous pour apparaître dans le classement</div>
  </div>
</div>

<!-- ════ CTA BANNER ════ -->
<div class="cta-banner reveal">
  <h2>Prêt à devenir <em style="background:var(--metallic);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Champion</em> ?</h2>
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
      <li><a href="#classement">Classement</a></li>
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
// Scroll reveal — rejoue l'animation à chaque fois que l'élément entre dans la vue
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      // reset puis relance l'animation
      e.target.classList.remove('visible');
      void e.target.offsetWidth; // force reflow
      e.target.classList.add('visible');
    } else {
      e.target.classList.remove('visible');
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
</script>
</body>
</html>