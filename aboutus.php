<<<<<<< HEAD
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — Question Champion</title>
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

*{ margin:0; padding:0; box-sizing:border-box; }
/* scroll-behavior: smooth retiré — Lenis gère le smooth scroll lui-même,
   les deux ensemble causent des conflits et ralentissent le scroll */
html { scroll-behavior: auto; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Montserrat', sans-serif;
  overflow-x: hidden;
}

/* noise */
body::before {
  content:'';
  position:fixed; inset:0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  opacity: 0.03;
  pointer-events: none;
  z-index: 9999;
}

/* ════ HEADER ════ */
header {
  position: fixed;
  top:0; left:0; right:0;
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
nav ul { display:flex; list-style:none; gap:28px; align-items:center; justify-content:center; }
nav a {
  text-decoration:none;
  color:rgba(255,255,255,0.75);
  font-size:0.78rem; font-weight:700;
  letter-spacing:2px; text-transform:uppercase;
  position:relative; transition:color 0.3s;
}
nav a:hover { color: var(--gold-light); }
nav a::after {
  content:''; position:absolute;
  width:0; height:1px; bottom:-4px; left:0;
  background: var(--metallic); transition: width 0.3s;
}
nav a:hover::after { width:100%; }
.btn-play {
  background: var(--metallic);
  color:#000 !important; -webkit-text-fill-color:#000 !important;
  padding:7px 22px; border-radius:30px; font-weight:900;
  border:1px solid var(--gold-base);
  box-shadow:0 0 12px var(--gold-glow);
  transition:transform 0.2s, box-shadow 0.2s;
}
.btn-play:hover { transform:scale(1.05); box-shadow:0 0 22px rgba(212,175,55,0.7); }
.btn-play::after { display:none; }
.btn-connexion {
  justify-self:end;
  background:transparent;
  border:1px solid rgba(212,175,55,0.5);
  color:var(--gold-light) !important;
  -webkit-text-fill-color:var(--gold-light) !important;
  padding:7px 22px; border-radius:30px;
  font-size:0.78rem; font-weight:700;
  letter-spacing:2px; text-transform:uppercase;
  text-decoration:none; transition:all 0.3s; white-space:nowrap;
}
.btn-connexion:hover {
  background:var(--metallic);
  -webkit-text-fill-color:#000 !important;
  border-color:transparent;
  box-shadow:0 0 18px var(--gold-glow);
}

/* ════ HERO ════ */
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

/* cercles déco */
.hero-ring {
  position:absolute;
  border-radius:50%;
  border:1px solid rgba(212,175,55,0.07);
  top:50%; left:50%;
  transform:translate(-50%,-50%);
  pointer-events:none;
}
.hero-ring:nth-child(1){ width:400px; height:400px; }
.hero-ring:nth-child(2){ width:650px; height:650px; animation: spin 30s linear infinite; }
.hero-ring:nth-child(3){ width:900px; height:900px; animation: spin 50s linear infinite reverse; }

@keyframes spin {
  to { transform: translate(-50%,-50%) rotate(360deg); }
}

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
  opacity:0; animation:fadeUp 0.9s ease 0.5s forwards;
}
.hero-title em {
  font-style:normal;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  filter:drop-shadow(0 0 10px var(--gold-glow));
}
.hero-sub {
  margin-top:18px;
  font-size:0.85rem; color:rgba(255,255,255,0.35);
  letter-spacing:2px; max-width:500px;
  opacity:0; animation:fadeUp 0.8s ease 0.7s forwards;
}
.hero-divider {
  width:60px; height:2px;
  background:var(--metallic);
  margin:32px auto 0;
  opacity:0; animation:fadeUp 0.8s ease 0.9s forwards;
}

/* ════ INTRO BAND ════ */
.intro-band {
  border-top:1px solid rgba(212,175,55,0.1);
  border-bottom:1px solid rgba(212,175,55,0.1);
  background:rgba(212,175,55,0.03);
  padding:60px 40px;
  text-align:center;
}
.intro-band p {
  max-width:680px;
  margin:0 auto;
  font-size:1.05rem;
  line-height:1.9;
  color:rgba(255,255,255,0.5);
  font-weight:400;
  letter-spacing:0.5px;
}
.intro-band strong {
  color:var(--gold-light);
  font-weight:700;
}

/* ════ TEAM SECTION ════ */
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
}
.section-title em {
  font-style:normal;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}

/* grille équipe */
.team-grid {
  display:grid;
  grid-template-columns: repeat(3, 1fr);
  gap:24px;
}

.team-card {
  background:var(--bg2);
  border:1px solid rgba(212,175,55,0.1);
  border-radius:20px;
  padding:36px 32px;
  position:relative;
  overflow:hidden;
  transition:border-color 0.4s, transform 0.4s;
  cursor:default;
}
.team-card::before {
  content:'';
  position:absolute; inset:0;
  background:radial-gradient(ellipse at 50% 0%, rgba(212,175,55,0.08) 0%, transparent 65%);
  opacity:0; transition:opacity 0.4s;
}
.team-card:hover {
  border-color:rgba(212,175,55,0.4);
  transform:translateY(-6px);
}
.team-card:hover::before { opacity:1; }

/* numéro déco */
.card-num {
  position:absolute;
  top:20px; right:24px;
  font-family:'Kanit', sans-serif;
  font-size:5rem; font-weight:900;
  color:rgba(212,175,55,0.05);
  line-height:1;
  pointer-events:none;
}

/* avatar */
.card-avatar {
  width:72px; height:72px;
  border-radius:50%;
  border:2px solid rgba(212,175,55,0.3);
  background:rgba(212,175,55,0.08);
  display:flex; align-items:center; justify-content:center;
  font-family:'Kanit', sans-serif;
  font-weight:900; font-size:1.4rem;
  color:var(--gold-base);
  margin-bottom:20px;
  position:relative;
  overflow:hidden;
  transition:border-color 0.3s;
}
.team-card:hover .card-avatar {
  border-color:var(--gold-base);
}
.card-avatar img {
  width:100%; height:100%;
  object-fit:cover;
  border-radius:50%;
}

/* lead avatar plus grand */
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
  margin-bottom:14px;
  line-height:1;
}
.team-card.lead .card-name {
  font-size:2rem;
}
.card-desc {
  font-size:0.8rem;
  color:rgba(255,255,255,0.38);
  line-height:1.8;
}
.card-line {
  width:28px; height:2px;
  background:var(--gold-base);
  margin-bottom:14px;
  opacity:0.4;
}

/* skills tags */
.card-skills {
  display:flex; flex-wrap:wrap; gap:6px;
  margin-top:18px;
}
.skill-tag {
  font-size:0.6rem;
  letter-spacing:1px;
  padding:3px 10px;
  border-radius:20px;
  border:1px solid rgba(212,175,55,0.2);
  color:rgba(212,175,55,0.6);
  text-transform:uppercase;
}

/* lead badge */
.lead-badge {
  position:absolute;
  top:20px; left:32px;
  background:var(--metallic);
  color:#000;
  font-size:0.55rem;
  font-weight:900;
  letter-spacing:2px;
  padding:3px 12px;
  border-radius:20px;
  text-transform:uppercase;
}

/* ════ PROJET BAND ════ */
.project-band {
  background:var(--bg2);
  border-top:1px solid rgba(212,175,55,0.1);
  border-bottom:1px solid rgba(212,175,55,0.1);
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
.project-text {}
.project-title {
  font-family:'Great Vibes', cursive;
  font-size:3.5rem;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  filter:drop-shadow(0 0 8px var(--gold-glow));
  margin-bottom:20px;
  line-height:1.2;
}
.project-body {
  font-size:0.85rem;
  color:rgba(255,255,255,0.4);
  line-height:1.9;
}
.project-body strong { color:rgba(255,255,255,0.7); font-weight:700; }

.project-stats {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
}
.pstat {
  background:rgba(212,175,55,0.04);
  border:1px solid rgba(212,175,55,0.1);
  border-radius:14px;
  padding:28px 24px;
  text-align:center;
  transition:border-color 0.3s, transform 0.3s;
}
.pstat:hover {
  border-color:rgba(212,175,55,0.35);
  transform:translateY(-4px);
}
.pstat-num {
  font-family:'Kanit', sans-serif;
  font-weight:900;
  font-size:2.2rem;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  line-height:1;
  margin-bottom:6px;
}
.pstat-label {
  font-size:0.65rem;
  letter-spacing:3px;
  text-transform:uppercase;
  color:rgba(255,255,255,0.25);
}

/* ════ HESTIM SECTION ════ */
.hestim-section {
  padding: 100px 40px;
  border-top: 1px solid rgba(212,175,55,0.1);
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
}
.hestim-title em {
  font-style: normal;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.hestim-body {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.4);
  line-height: 1.9;
}
.hestim-body strong { color: rgba(255,255,255,0.75); }
.hestim-right {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.hestim-card {
  background: var(--bg2);
  border: 1px solid rgba(212,175,55,0.1);
  border-radius: 14px;
  padding: 22px 24px;
  display: flex;
  align-items: flex-start;
  gap: 18px;
  transition: border-color 0.3s, transform 0.3s;
}
.hestim-card:hover {
  border-color: rgba(212,175,55,0.35);
  transform: translateX(6px);
}
.hestim-card-icon { font-size: 1.5rem; flex-shrink: 0; margin-top: 2px; }
.hestim-card-title {
  font-size: 0.85rem;
  font-weight: 900;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--gold-light);
  margin-bottom: 5px;
}
.hestim-card-desc {
  font-size: 0.78rem;
  color: rgba(255,255,255,0.35);
  line-height: 1.6;
}

/* ════ FOOTER ════ */
footer {
  position:relative;
  background:var(--bg2);
  border-top:1px solid rgba(212,175,55,0.15);
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
  border-bottom:1px solid rgba(212,175,55,0.07);
}
.footer-logo {
  font-family:'Kanit', sans-serif;
  font-weight:900; font-size:1.4rem;
  letter-spacing:4px;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  text-transform:uppercase;
  display:block; margin-bottom:8px;
}
.footer-tagline {
  font-size:0.7rem; letter-spacing:3px;
  color:rgba(255,255,255,0.2); text-transform:uppercase;
}
.footer-nav {
  display:flex; flex-direction:column;
  align-items:center; gap:14px; list-style:none;
}
.footer-nav a {
  text-decoration:none; font-size:0.75rem;
  letter-spacing:3px; color:rgba(255,255,255,0.35);
  text-transform:uppercase; font-weight:700;
  transition:color 0.3s; position:relative;
}
.footer-nav a::after {
  content:''; position:absolute;
  width:0; height:1px; bottom:-3px; left:50%;
  transform:translateX(-50%);
  background:var(--gold-base); transition:width 0.3s;
}
.footer-nav a:hover { color:var(--gold-light); }
.footer-nav a:hover::after { width:100%; }
.footer-cta-col { display:flex; justify-content:flex-end; }
.footer-play-btn {
  display:inline-flex; align-items:center; gap:10px;
  background:var(--metallic); color:#000;
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
  color:rgba(255,255,255,0.12); text-transform:uppercase;
}
.footer-school {
  font-size:0.65rem; letter-spacing:2px;
  color:rgba(212,175,55,0.25); text-transform:uppercase;
}

/* ════ ANIMATIONS ════ */
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

/* stagger enfants */
.team-card:nth-child(1) { transition-delay: 0s; }
.team-card:nth-child(2) { transition-delay: 0.1s; }
.team-card:nth-child(3) { transition-delay: 0.2s; }
.team-card:nth-child(4) { transition-delay: 0.1s; }
.team-card:nth-child(5) { transition-delay: 0.2s; }

/* ════ RESPONSIVE ════ */

/* Hamburger button — caché sur desktop */
.hamburger {
  display: none;
  flex-direction: column;
  gap: 5px;
  cursor: pointer;
  background: none;
  border: none;
  padding: 4px;
  z-index: 200;
}
.hamburger span {
  display: block;
  width: 24px;
  height: 2px;
  background: var(--gold-base);
  border-radius: 2px;
  transition: transform 0.3s, opacity 0.3s;
}
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

@media (max-width: 900px) {
  header {
    grid-template-columns: 1fr auto auto;
  }
  nav {
    display: none;
    position: fixed;
    inset: 72px 0 0 0;
    background: rgba(6,6,6,0.97);
    backdrop-filter: blur(20px);
    z-index: 99;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0;
  }
  nav.open { display: flex; }
  nav ul {
    flex-direction: column;
    gap: 32px;
    align-items: center;
  }
  nav a { font-size: 1rem; letter-spacing: 4px; }
  .hamburger { display: flex; }
  .btn-connexion { display: none; }
  .team-grid { grid-template-columns: 1fr 1fr; }
  .team-card.lead { grid-column: span 2; }
  .project-inner { grid-template-columns: 1fr; gap: 40px; }
  .hestim-inner { grid-template-columns: 1fr; gap: 40px; }
  .footer-top { grid-template-columns: 1fr; text-align: center; padding: 40px 24px 24px; }
  .footer-cta-col { justify-content: center; }
  .footer-nav { flex-direction: row; flex-wrap: wrap; justify-content: center; }
  .footer-bottom { flex-direction: column; text-align: center; padding: 16px 24px; }
}

@media (max-width: 600px) {
  header { grid-template-columns: 1fr auto; height: auto; padding: 16px 20px; }
  .team-grid { grid-template-columns: 1fr; }
  .team-card.lead { grid-column: span 1; }
  .project-stats { grid-template-columns: 1fr 1fr; }
  .team-section, .project-band { padding: 60px 20px; }
  .intro-band { padding: 40px 20px; }
  .hestim-section { padding: 60px 20px; }
}
/* ════ AMÉLIORATIONS POLISH ════ */

/* Glow or sur hover des cards team */
.team-card:hover {
  border-color: rgba(212,175,55,0.5);
  transform: translateY(-8px);
  box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 30px rgba(212,175,55,0.08);
}

/* Compteur animé */
@keyframes countUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.pstat-num.counted { animation: countUp 0.5s ease forwards; }

/* Intro/Outro scroll indicator améliorés */
.intro, .outro {
  position: relative;
  z-index: 2;
}

/* Intro band amélioration */
.intro-band {
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

/* Skill tag hover */
.skill-tag {
  transition: border-color 0.25s, color 0.25s, background 0.25s;
  cursor: default;
}
.skill-tag:hover {
  border-color: rgba(212,175,55,0.5);
  color: var(--gold-light);
  background: rgba(212,175,55,0.06);
}

/* Hestim card amélioration hover */
.hestim-card:hover {
  border-color: rgba(212,175,55,0.4);
  transform: translateX(8px);
  box-shadow: -4px 0 20px rgba(212,175,55,0.06);
}

/* effect react gsap  */
* { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
  background: #000;
  width: 100%;
}

.intro {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-family: 'Helvetica Neue', sans-serif;
  font-size: 0.85rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  opacity: 0.35;
}

/* 300vh comme l'original */
.container {
  height: 300vh;
  position: relative;
}

/* sticky plein écran, overflow hidden pour couper le zoom */
.sticky {
  position: sticky;
  top: 0;
  height: 100vh;
  overflow: hidden;
}

/* chaque el = absolute, plein écran, centré */
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
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* positions & tailles exactes du styles.module.scss */
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

.outro {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-family: 'Helvetica Neue', sans-serif;
  font-size: 0.85rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  opacity: 0.35;
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
  <a href="connexion.php" class="btn-connexion">Connexion</a>
  <button class="hamburger" id="hamburger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</header>

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

<!-- ════ INTRO ════ -->
<div class="intro-band reveal">
  <p>
    Dans le cadre du <strong>Cycle Ingénieur 2025/2026</strong>, nous avons conçu et développé
    <strong>Question pour un Champion</strong> — un jeu de culture générale multijoueur complet.
    Ce projet est le reflet de nos compétences, de notre travail d'équipe et de notre envie
    de livrer quelque chose dont on est <strong>vraiment fiers</strong>.
  </p>
</div>

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

<!-- ════ HESTIM PRÉSENTATION ════ -->
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
          <div class="hestim-card-title">Rayonnement International</div>
          <p class="hestim-card-desc">Partenariats avec des universités et entreprises à l'échelle internationale.</p>
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

    <!-- Membre 1 -->
    <div class="team-card">
      <div class="card-num">01</div>
      <div class="card-avatar">MB</div>
      <div class="card-role">Rédacteur Technique</div>
      <div class="card-name">Maxime Bang-Kera</div>
      <div class="card-line"></div>
      <p class="card-desc">
        Documentation, spécifications techniques et rédaction des rapports de projet.
      </p>
      <div class="card-skills">
        <span class="skill-tag">Documentation</span>
        <span class="skill-tag">Rédaction</span>
      </div>
    </div>

    <!-- Membre 2 -->
    <div class="team-card">
      <div class="card-num">02</div>
      <div class="card-avatar">ON</div>
      <div class="card-role">Développeur Front-end</div>
      <div class="card-name">Ousmane Niasse</div>
      <div class="card-line"></div>
      <p class="card-desc">
        Intégration des interfaces, animations CSS et expérience utilisateur.
      </p>
      <div class="card-skills">
        <span class="skill-tag">HTML / CSS</span>
        <span class="skill-tag">Animations</span>
      </div>
    </div>

    <!-- Membre 3 -->
    <div class="team-card">
      <div class="card-num">03</div>
      <div class="card-avatar">BA</div>
      <div class="card-role">Design & Styling</div>
      <div class="card-name">Bamba Amara</div>
      <div class="card-line"></div>
      <p class="card-desc">
        Identité visuelle, charte graphique et cohérence du design sur l'ensemble du site.
      </p>
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
        <div class="pstat-num">4</div>
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
// ── Reveal sections ──────────────────────────────────────────────
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    // void offsetWidth supprimé : forçait un reflow complet à chaque entrée/sortie
    if (e.isIntersecting) {
      e.target.classList.add('visible');
    } else {
      e.target.classList.remove('visible');
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Stagger cartes ───────────────────────────────────────────────
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

// ── Zoom parallax ────────────────────────────────────────────────
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
    // translate3d force le compositing GPU sans déclencher de layout
    el.style.transform = `translate3d(0,0,0) scale(${scale})`;
  });
}

// ── Lenis smooth scroll ──────────────────────────────────────────
const lenis = new Lenis();

function raf(time) {
  lenis.raf(time);
  // Une seule update par frame — plus de double déclenchement
  if (!rafPending) {
    rafPending = true;
    requestAnimationFrame(update);
  }
  requestAnimationFrame(raf);
}
requestAnimationFrame(raf);

// ── Hamburger menu ───────────────────────────────────────────────
const hamburger = document.getElementById('hamburger');
const navEl = document.querySelector('nav');
hamburger.addEventListener('click', () => {
  hamburger.classList.toggle('open');
  navEl.classList.toggle('open');
});
// Fermer le nav quand on clique sur un lien
navEl.querySelectorAll('a').forEach(a => {
  a.addEventListener('click', () => {
    hamburger.classList.remove('open');
    navEl.classList.remove('open');
  });
});

// ── Compteur animé des stats ─────────────────────────────────────
function animateCounter(el, target, suffix = '') {
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
</script>
</body>
=======
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — Question Champion</title>
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

*{ margin:0; padding:0; box-sizing:border-box; }
/* scroll-behavior: smooth retiré — Lenis gère le smooth scroll lui-même,
   les deux ensemble causent des conflits et ralentissent le scroll */
html { scroll-behavior: auto; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Montserrat', sans-serif;
  overflow-x: hidden;
}

/* noise */
body::before {
  content:'';
  position:fixed; inset:0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  opacity: 0.03;
  pointer-events: none;
  z-index: 9999;
}

/* ════ HEADER ════ */
header {
  position: fixed;
  top:0; left:0; right:0;
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
nav ul { display:flex; list-style:none; gap:28px; align-items:center; justify-content:center; }
nav a {
  text-decoration:none;
  color:rgba(255,255,255,0.75);
  font-size:0.78rem; font-weight:700;
  letter-spacing:2px; text-transform:uppercase;
  position:relative; transition:color 0.3s;
}
nav a:hover { color: var(--gold-light); }
nav a::after {
  content:''; position:absolute;
  width:0; height:1px; bottom:-4px; left:0;
  background: var(--metallic); transition: width 0.3s;
}
nav a:hover::after { width:100%; }
.btn-play {
  background: var(--metallic);
  color:#000 !important; -webkit-text-fill-color:#000 !important;
  padding:7px 22px; border-radius:30px; font-weight:900;
  border:1px solid var(--gold-base);
  box-shadow:0 0 12px var(--gold-glow);
  transition:transform 0.2s, box-shadow 0.2s;
}
.btn-play:hover { transform:scale(1.05); box-shadow:0 0 22px rgba(212,175,55,0.7); }
.btn-play::after { display:none; }
.btn-connexion {
  justify-self:end;
  background:transparent;
  border:1px solid rgba(212,175,55,0.5);
  color:var(--gold-light) !important;
  -webkit-text-fill-color:var(--gold-light) !important;
  padding:7px 22px; border-radius:30px;
  font-size:0.78rem; font-weight:700;
  letter-spacing:2px; text-transform:uppercase;
  text-decoration:none; transition:all 0.3s; white-space:nowrap;
}
.btn-connexion:hover {
  background:var(--metallic);
  -webkit-text-fill-color:#000 !important;
  border-color:transparent;
  box-shadow:0 0 18px var(--gold-glow);
}

/* ════ HERO ════ */
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

/* cercles déco */
.hero-ring {
  position:absolute;
  border-radius:50%;
  border:1px solid rgba(212,175,55,0.07);
  top:50%; left:50%;
  transform:translate(-50%,-50%);
  pointer-events:none;
}
.hero-ring:nth-child(1){ width:400px; height:400px; }
.hero-ring:nth-child(2){ width:650px; height:650px; animation: spin 30s linear infinite; }
.hero-ring:nth-child(3){ width:900px; height:900px; animation: spin 50s linear infinite reverse; }

@keyframes spin {
  to { transform: translate(-50%,-50%) rotate(360deg); }
}

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
  opacity:0; animation:fadeUp 0.9s ease 0.5s forwards;
}
.hero-title em {
  font-style:normal;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  filter:drop-shadow(0 0 10px var(--gold-glow));
}
.hero-sub {
  margin-top:18px;
  font-size:0.85rem; color:rgba(255,255,255,0.35);
  letter-spacing:2px; max-width:500px;
  opacity:0; animation:fadeUp 0.8s ease 0.7s forwards;
}
.hero-divider {
  width:60px; height:2px;
  background:var(--metallic);
  margin:32px auto 0;
  opacity:0; animation:fadeUp 0.8s ease 0.9s forwards;
}

/* ════ INTRO BAND ════ */
.intro-band {
  border-top:1px solid rgba(212,175,55,0.1);
  border-bottom:1px solid rgba(212,175,55,0.1);
  background:rgba(212,175,55,0.03);
  padding:60px 40px;
  text-align:center;
}
.intro-band p {
  max-width:680px;
  margin:0 auto;
  font-size:1.05rem;
  line-height:1.9;
  color:rgba(255,255,255,0.5);
  font-weight:400;
  letter-spacing:0.5px;
}
.intro-band strong {
  color:var(--gold-light);
  font-weight:700;
}

/* ════ TEAM SECTION ════ */
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
}
.section-title em {
  font-style:normal;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}

/* grille équipe */
.team-grid {
  display:grid;
  grid-template-columns: repeat(3, 1fr);
  gap:24px;
}

.team-card {
  background:var(--bg2);
  border:1px solid rgba(212,175,55,0.1);
  border-radius:20px;
  padding:36px 32px;
  position:relative;
  overflow:hidden;
  transition:border-color 0.4s, transform 0.4s;
  cursor:default;
}
.team-card::before {
  content:'';
  position:absolute; inset:0;
  background:radial-gradient(ellipse at 50% 0%, rgba(212,175,55,0.08) 0%, transparent 65%);
  opacity:0; transition:opacity 0.4s;
}
.team-card:hover {
  border-color:rgba(212,175,55,0.4);
  transform:translateY(-6px);
}
.team-card:hover::before { opacity:1; }

/* numéro déco */
.card-num {
  position:absolute;
  top:20px; right:24px;
  font-family:'Kanit', sans-serif;
  font-size:5rem; font-weight:900;
  color:rgba(212,175,55,0.05);
  line-height:1;
  pointer-events:none;
}

/* avatar */
.card-avatar {
  width:72px; height:72px;
  border-radius:50%;
  border:2px solid rgba(212,175,55,0.3);
  background:rgba(212,175,55,0.08);
  display:flex; align-items:center; justify-content:center;
  font-family:'Kanit', sans-serif;
  font-weight:900; font-size:1.4rem;
  color:var(--gold-base);
  margin-bottom:20px;
  position:relative;
  overflow:hidden;
  transition:border-color 0.3s;
}
.team-card:hover .card-avatar {
  border-color:var(--gold-base);
}
.card-avatar img {
  width:100%; height:100%;
  object-fit:cover;
  border-radius:50%;
}

/* lead avatar plus grand */
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
  margin-bottom:14px;
  line-height:1;
}
.team-card.lead .card-name {
  font-size:2rem;
}
.card-desc {
  font-size:0.8rem;
  color:rgba(255,255,255,0.38);
  line-height:1.8;
}
.card-line {
  width:28px; height:2px;
  background:var(--gold-base);
  margin-bottom:14px;
  opacity:0.4;
}

/* skills tags */
.card-skills {
  display:flex; flex-wrap:wrap; gap:6px;
  margin-top:18px;
}
.skill-tag {
  font-size:0.6rem;
  letter-spacing:1px;
  padding:3px 10px;
  border-radius:20px;
  border:1px solid rgba(212,175,55,0.2);
  color:rgba(212,175,55,0.6);
  text-transform:uppercase;
}

/* lead badge */
.lead-badge {
  position:absolute;
  top:20px; left:32px;
  background:var(--metallic);
  color:#000;
  font-size:0.55rem;
  font-weight:900;
  letter-spacing:2px;
  padding:3px 12px;
  border-radius:20px;
  text-transform:uppercase;
}

/* ════ PROJET BAND ════ */
.project-band {
  background:var(--bg2);
  border-top:1px solid rgba(212,175,55,0.1);
  border-bottom:1px solid rgba(212,175,55,0.1);
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
.project-text {}
.project-title {
  font-family:'Great Vibes', cursive;
  font-size:3.5rem;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  filter:drop-shadow(0 0 8px var(--gold-glow));
  margin-bottom:20px;
  line-height:1.2;
}
.project-body {
  font-size:0.85rem;
  color:rgba(255,255,255,0.4);
  line-height:1.9;
}
.project-body strong { color:rgba(255,255,255,0.7); font-weight:700; }

.project-stats {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
}
.pstat {
  background:rgba(212,175,55,0.04);
  border:1px solid rgba(212,175,55,0.1);
  border-radius:14px;
  padding:28px 24px;
  text-align:center;
  transition:border-color 0.3s, transform 0.3s;
}
.pstat:hover {
  border-color:rgba(212,175,55,0.35);
  transform:translateY(-4px);
}
.pstat-num {
  font-family:'Kanit', sans-serif;
  font-weight:900;
  font-size:2.2rem;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  line-height:1;
  margin-bottom:6px;
}
.pstat-label {
  font-size:0.65rem;
  letter-spacing:3px;
  text-transform:uppercase;
  color:rgba(255,255,255,0.25);
}

/* ════ HESTIM SECTION ════ */
.hestim-section {
  padding: 100px 40px;
  border-top: 1px solid rgba(212,175,55,0.1);
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
}
.hestim-title em {
  font-style: normal;
  background: var(--metallic);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.hestim-body {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.4);
  line-height: 1.9;
}
.hestim-body strong { color: rgba(255,255,255,0.75); }
.hestim-right {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.hestim-card {
  background: var(--bg2);
  border: 1px solid rgba(212,175,55,0.1);
  border-radius: 14px;
  padding: 22px 24px;
  display: flex;
  align-items: flex-start;
  gap: 18px;
  transition: border-color 0.3s, transform 0.3s;
}
.hestim-card:hover {
  border-color: rgba(212,175,55,0.35);
  transform: translateX(6px);
}
.hestim-card-icon { font-size: 1.5rem; flex-shrink: 0; margin-top: 2px; }
.hestim-card-title {
  font-size: 0.85rem;
  font-weight: 900;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--gold-light);
  margin-bottom: 5px;
}
.hestim-card-desc {
  font-size: 0.78rem;
  color: rgba(255,255,255,0.35);
  line-height: 1.6;
}

/* ════ FOOTER ════ */
footer {
  position:relative;
  background:var(--bg2);
  border-top:1px solid rgba(212,175,55,0.15);
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
  border-bottom:1px solid rgba(212,175,55,0.07);
}
.footer-logo {
  font-family:'Kanit', sans-serif;
  font-weight:900; font-size:1.4rem;
  letter-spacing:4px;
  background:var(--metallic);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  text-transform:uppercase;
  display:block; margin-bottom:8px;
}
.footer-tagline {
  font-size:0.7rem; letter-spacing:3px;
  color:rgba(255,255,255,0.2); text-transform:uppercase;
}
.footer-nav {
  display:flex; flex-direction:column;
  align-items:center; gap:14px; list-style:none;
}
.footer-nav a {
  text-decoration:none; font-size:0.75rem;
  letter-spacing:3px; color:rgba(255,255,255,0.35);
  text-transform:uppercase; font-weight:700;
  transition:color 0.3s; position:relative;
}
.footer-nav a::after {
  content:''; position:absolute;
  width:0; height:1px; bottom:-3px; left:50%;
  transform:translateX(-50%);
  background:var(--gold-base); transition:width 0.3s;
}
.footer-nav a:hover { color:var(--gold-light); }
.footer-nav a:hover::after { width:100%; }
.footer-cta-col { display:flex; justify-content:flex-end; }
.footer-play-btn {
  display:inline-flex; align-items:center; gap:10px;
  background:var(--metallic); color:#000;
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
  color:rgba(255,255,255,0.12); text-transform:uppercase;
}
.footer-school {
  font-size:0.65rem; letter-spacing:2px;
  color:rgba(212,175,55,0.25); text-transform:uppercase;
}

/* ════ ANIMATIONS ════ */
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

/* stagger enfants */
.team-card:nth-child(1) { transition-delay: 0s; }
.team-card:nth-child(2) { transition-delay: 0.1s; }
.team-card:nth-child(3) { transition-delay: 0.2s; }
.team-card:nth-child(4) { transition-delay: 0.1s; }
.team-card:nth-child(5) { transition-delay: 0.2s; }

/* ════ RESPONSIVE ════ */

/* Hamburger button — caché sur desktop */
.hamburger {
  display: none;
  flex-direction: column;
  gap: 5px;
  cursor: pointer;
  background: none;
  border: none;
  padding: 4px;
  z-index: 200;
}
.hamburger span {
  display: block;
  width: 24px;
  height: 2px;
  background: var(--gold-base);
  border-radius: 2px;
  transition: transform 0.3s, opacity 0.3s;
}
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

@media (max-width: 900px) {
  header {
    grid-template-columns: 1fr auto auto;
  }
  nav {
    display: none;
    position: fixed;
    inset: 72px 0 0 0;
    background: rgba(6,6,6,0.97);
    backdrop-filter: blur(20px);
    z-index: 99;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0;
  }
  nav.open { display: flex; }
  nav ul {
    flex-direction: column;
    gap: 32px;
    align-items: center;
  }
  nav a { font-size: 1rem; letter-spacing: 4px; }
  .hamburger { display: flex; }
  .btn-connexion { display: none; }
  .team-grid { grid-template-columns: 1fr 1fr; }
  .team-card.lead { grid-column: span 2; }
  .project-inner { grid-template-columns: 1fr; gap: 40px; }
  .hestim-inner { grid-template-columns: 1fr; gap: 40px; }
  .footer-top { grid-template-columns: 1fr; text-align: center; padding: 40px 24px 24px; }
  .footer-cta-col { justify-content: center; }
  .footer-nav { flex-direction: row; flex-wrap: wrap; justify-content: center; }
  .footer-bottom { flex-direction: column; text-align: center; padding: 16px 24px; }
}

@media (max-width: 600px) {
  header { grid-template-columns: 1fr auto; height: auto; padding: 16px 20px; }
  .team-grid { grid-template-columns: 1fr; }
  .team-card.lead { grid-column: span 1; }
  .project-stats { grid-template-columns: 1fr 1fr; }
  .team-section, .project-band { padding: 60px 20px; }
  .intro-band { padding: 40px 20px; }
  .hestim-section { padding: 60px 20px; }
}
/* ════ AMÉLIORATIONS POLISH ════ */

/* Glow or sur hover des cards team */
.team-card:hover {
  border-color: rgba(212,175,55,0.5);
  transform: translateY(-8px);
  box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 30px rgba(212,175,55,0.08);
}

/* Compteur animé */
@keyframes countUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.pstat-num.counted { animation: countUp 0.5s ease forwards; }

/* Intro/Outro scroll indicator améliorés */
.intro, .outro {
  position: relative;
  z-index: 2;
}

/* Intro band amélioration */
.intro-band {
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

/* Skill tag hover */
.skill-tag {
  transition: border-color 0.25s, color 0.25s, background 0.25s;
  cursor: default;
}
.skill-tag:hover {
  border-color: rgba(212,175,55,0.5);
  color: var(--gold-light);
  background: rgba(212,175,55,0.06);
}

/* Hestim card amélioration hover */
.hestim-card:hover {
  border-color: rgba(212,175,55,0.4);
  transform: translateX(8px);
  box-shadow: -4px 0 20px rgba(212,175,55,0.06);
}

/* effect react gsap  */
* { margin: 0; padding: 0; box-sizing: border-box; }

html, body {
  background: #000;
  width: 100%;
}

.intro {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-family: 'Helvetica Neue', sans-serif;
  font-size: 0.85rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  opacity: 0.35;
}

/* 300vh comme l'original */
.container {
  height: 300vh;
  position: relative;
}

/* sticky plein écran, overflow hidden pour couper le zoom */
.sticky {
  position: sticky;
  top: 0;
  height: 100vh;
  overflow: hidden;
}

/* chaque el = absolute, plein écran, centré */
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
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* positions & tailles exactes du styles.module.scss */
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

.outro {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-family: 'Helvetica Neue', sans-serif;
  font-size: 0.85rem;
  letter-spacing: 6px;
  text-transform: uppercase;
  opacity: 0.35;
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
      <li><a href="game.html" class="btn-play">▶ Play</a></li>
      <li><a href="#classement">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
    </ul>
  </nav>
  <a href="connexion.php" class="btn-connexion">Connexion</a>
  <button class="hamburger" id="hamburger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</header>

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

<!-- ════ INTRO ════ -->
<div class="intro-band reveal">
  <p>
    Dans le cadre du <strong>Cycle Ingénieur 2025/2026</strong>, nous avons conçu et développé
    <strong>Question pour un Champion</strong> — un jeu de culture générale multijoueur complet.
    Ce projet est le reflet de nos compétences, de notre travail d'équipe et de notre envie
    de livrer quelque chose dont on est <strong>vraiment fiers</strong>.
  </p>
</div>

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

<!-- ════ HESTIM PRÉSENTATION ════ -->
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
          <div class="hestim-card-title">Rayonnement International</div>
          <p class="hestim-card-desc">Partenariats avec des universités et entreprises à l'échelle internationale.</p>
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

    <!-- Membre 1 -->
    <div class="team-card">
      <div class="card-num">01</div>
      <div class="card-avatar">MB</div>
      <div class="card-role">Rédacteur Technique</div>
      <div class="card-name">Maxime Bang-Kera</div>
      <div class="card-line"></div>
      <p class="card-desc">
        Documentation, spécifications techniques et rédaction des rapports de projet.
      </p>
      <div class="card-skills">
        <span class="skill-tag">Documentation</span>
        <span class="skill-tag">Rédaction</span>
      </div>
    </div>

    <!-- Membre 2 -->
    <div class="team-card">
      <div class="card-num">02</div>
      <div class="card-avatar">ON</div>
      <div class="card-role">Développeur Front-end</div>
      <div class="card-name">Ousmane Niasse</div>
      <div class="card-line"></div>
      <p class="card-desc">
        Intégration des interfaces, animations CSS et expérience utilisateur.
      </p>
      <div class="card-skills">
        <span class="skill-tag">HTML / CSS</span>
        <span class="skill-tag">Animations</span>
      </div>
    </div>

    <!-- Membre 3 -->
    <div class="team-card">
      <div class="card-num">03</div>
      <div class="card-avatar">BA</div>
      <div class="card-role">Design & Styling</div>
      <div class="card-name">Bamba Amara</div>
      <div class="card-line"></div>
      <p class="card-desc">
        Identité visuelle, charte graphique et cohérence du design sur l'ensemble du site.
      </p>
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
        <div class="pstat-num">4</div>
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
// ── Reveal sections ──────────────────────────────────────────────
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    // void offsetWidth supprimé : forçait un reflow complet à chaque entrée/sortie
    if (e.isIntersecting) {
      e.target.classList.add('visible');
    } else {
      e.target.classList.remove('visible');
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Stagger cartes ───────────────────────────────────────────────
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

// ── Zoom parallax ────────────────────────────────────────────────
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
    // translate3d force le compositing GPU sans déclencher de layout
    el.style.transform = `translate3d(0,0,0) scale(${scale})`;
  });
}

// ── Lenis smooth scroll ──────────────────────────────────────────
const lenis = new Lenis();

function raf(time) {
  lenis.raf(time);
  // Une seule update par frame — plus de double déclenchement
  if (!rafPending) {
    rafPending = true;
    requestAnimationFrame(update);
  }
  requestAnimationFrame(raf);
}
requestAnimationFrame(raf);

// ── Hamburger menu ───────────────────────────────────────────────
const hamburger = document.getElementById('hamburger');
const navEl = document.querySelector('nav');
hamburger.addEventListener('click', () => {
  hamburger.classList.toggle('open');
  navEl.classList.toggle('open');
});
// Fermer le nav quand on clique sur un lien
navEl.querySelectorAll('a').forEach(a => {
  a.addEventListener('click', () => {
    hamburger.classList.remove('open');
    navEl.classList.remove('open');
  });
});

// ── Compteur animé des stats ─────────────────────────────────────
function animateCounter(el, target, suffix = '') {
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
</script>
</body>
>>>>>>> 479bca22f359248ed3a065f59a47ae2852c294ec
</html>