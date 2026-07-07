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
  } catch (e) {}
})();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo:wght@400;700&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  /* ════════════════════════════════════════════════════════════
    CONCEPT — "LE PLATEAU"
    Direction plateau TV / régie de diffusion :
    typo affiche (Anton), données en mono (Space Mono),
    faisceaux de projecteurs, ticker prompteur, buzzer central.
    Palette : identique au site (or / noir + light mode).
  ═══════════════════════════════════════════════════════════ */

  /* ════ TOKENS — Dark (défaut) ════ */
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
    --beam: rgba(212,175,55,0.10);

    --invert: #ffffff;
    --on-invert: #0a0a0a;

    /* TYPO */
    --f-display: 'Anton', sans-serif;
    --f-mono: 'Space Mono', monospace;
    --f-body: 'Archivo', sans-serif;
  }

  /* ════ TOKENS — Light ════ */
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
    --beam: rgba(212,175,55,0.14);

    --invert: #0a0a0a;
    --on-invert: #ffffff;
  }

  /* ════ Transition douce au switch de thème ════ */
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
    font-family: var(--f-body);
    overflow-x: hidden;
  }

  ::selection { background: rgba(212,175,55,0.35); }

  a:focus-visible, button:focus-visible {
    outline: 2px solid var(--gold-base);
    outline-offset: 3px;
  }

  /* ── Grain ── */
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

  /* ── Colonnes éditoriales : fines lignes verticales fixes ── */
  .grid-lines {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background-image: linear-gradient(to right, var(--line-soft) 1px, transparent 1px);
    background-size: calc(100% / 6) 100%;
    -webkit-mask-image: linear-gradient(to bottom, transparent, #000 10%, #000 90%, transparent);
            mask-image: linear-gradient(to bottom, transparent, #000 10%, #000 90%, transparent);
  }

  .page { position: relative; z-index: 1; }

  /* ════════════════════════════
    BOUTONS — angles francs (4px), mono
  ════════════════════════════ */
  .btn-solid, .btn-ghost {
    display: inline-block;
    font-family: var(--f-mono);
    font-weight: 700;
    font-size: 0.72rem;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    text-decoration: none;
    padding: 15px 28px;
    border-radius: 4px;
    transition: transform 0.2s, box-shadow 0.2s, background 0.25s, color 0.25s, border-color 0.25s;
    position: relative;
    overflow: hidden;
  }
  .btn-solid {
    background: var(--metallic);
    color: var(--on-gold);
    border: 1px solid var(--gold-base);
    box-shadow: 0 0 18px var(--gold-glow);
  }
  .btn-solid:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 30px rgba(212,175,55,0.6), 0 10px 26px -12px var(--shadow-deep);
  }
  .btn-ghost {
    background: transparent;
    color: var(--ink-2);
    border: 1px solid var(--line);
  }
  .btn-ghost:hover { border-color: var(--gold-base); color: var(--gold-text); }

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
    transition: height 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
  }
  /* Header compact après scroll (nouveau) */
  header.scrolled {
    height: 60px;
    border-bottom-color: var(--gold-line-strong);
    box-shadow: 0 12px 32px -14px var(--shadow-deep);
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
    position: relative;
    overflow: hidden;
    display: inline-block;
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
    position: relative;
    overflow: hidden;
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
    HERO — "LE PLATEAU"
    2 colonnes : titre affiche / buzzer sous projecteurs
  ════════════════════════════ */
  .hero {
    position: relative;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    align-items: center;
    gap: 40px;
    max-width: 1240px;
    margin: 0 auto;
    padding: 120px 40px 120px;
    overflow: visible;
  }

  /* Faisceaux de projecteurs qui balaient depuis le haut */
  .beams {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 0;
  }
  .beam {
    position: absolute;
    top: -46%;
    left: 50%;
    width: 1500px;
    height: 1500px;
    background: conic-gradient(from 180deg at 50% 0%,
                transparent 0 46%, var(--beam) 50%, transparent 54% 100%);
    transform: translateX(-50%) rotate(-8deg);
    transform-origin: 50% 0;
    animation: beamSweep 11s ease-in-out infinite alternate;
  }
  .beam.b2 {
    left: 38%;
    animation-duration: 15s;
    animation-delay: -5s;
    opacity: 0.8;
  }

  .hero-left { position: relative; z-index: 2; }

  .onair {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--gold-line-strong);
    border-radius: 4px;
    padding: 7px 14px;
    font-family: var(--f-mono);
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold-text);
    opacity: 0;
    animation: fadeUp 0.7s ease 0.25s forwards;
  }
  .onair i {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--gold-base);
    box-shadow: 0 0 10px var(--gold-glow);
    animation: blink 1.6s steps(1) infinite;
  }

  .headline {
    margin-top: 26px;
    font-family: var(--f-display);
    font-weight: 400;
    text-transform: uppercase;
    line-height: 0.94;
    letter-spacing: 0.5px;
  }
  .headline .hl {
    display: block;
    font-size: clamp(3.2rem, 9.5vw, 7.6rem);
    clip-path: inset(0 100% 0 0);
    animation: wipeIn 0.85s cubic-bezier(0.2,0.8,0.2,1) forwards;
  }
  .headline .hl.ghost {
    color: transparent;
    -webkit-text-stroke: 2px var(--gold-line-strong);
    animation-delay: 0.45s;
  }
  .headline .hl.gold {
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 0 14px var(--gold-glow));
    animation-delay: 0.7s;
  }

  .hero-sub {
    margin-top: 24px;
    max-width: 46ch;
    font-size: 0.95rem;
    line-height: 1.75;
    color: var(--ink-2);
    opacity: 0;
    animation: fadeUp 0.8s ease 1.15s forwards;
  }
  .hero-sub b { color: var(--gold-text); font-weight: 700; }

  .hero-ctas {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    margin-top: 34px;
    opacity: 0;
    animation: fadeUp 0.8s ease 1.35s forwards;
  }

  /* ── LA PIÈCE MAÎTRESSE : LE BUZZER ── */
  .buzzer-stage {
    position: relative;
    z-index: 2;
    display: grid;
    place-items: center;
    opacity: 0;
    animation: fadeUp 0.9s cubic-bezier(0.2,0.8,0.2,1) 0.6s forwards;
  }
  .buzzer-stage > * { grid-area: 1 / 1; }

  /* Texte circulaire qui tourne autour du buzzer */
  .orbit {
    width: 380px;
    height: 380px;
    animation: spinSlow 26s linear infinite;
    pointer-events: none;
  }
  .orbit text {
    font-family: var(--f-mono);
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 5px;
    fill: var(--gold-base);
    opacity: 0.55;
    text-transform: uppercase;
  }

  /* Socle */
  .pedestal {
    width: 315px;
    height: 315px;
    border-radius: 50%;
    border: 1px solid var(--gold-line);
    display: grid;
    place-items: center;
    pointer-events: none;
  }
  .pedestal::before {
    content:'';
    width: 268px;
    height: 268px;
    border-radius: 50%;
    background: var(--gold-tint);
    border: 1px solid var(--gold-line);
  }

  /* Le buzzer lui-même : un vrai bouton qui s'enfonce */
  a.buzzer {
    width: 226px;
    height: 226px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    text-decoration: none;
    background: radial-gradient(circle at 32% 28%,
                var(--gold-light) 0%, var(--gold-base) 44%, var(--gold-dark) 100%);
    box-shadow:
      0 16px 0 -4px rgba(0,0,0,0.45),
      0 34px 60px -18px var(--gold-glow),
      inset 0 -14px 24px rgba(0,0,0,0.28),
      inset 0 10px 18px rgba(255,255,255,0.35);
    transition: transform 0.12s ease, box-shadow 0.12s ease;
    position: relative;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
  }
  a.buzzer:hover {
    box-shadow:
      0 16px 0 -4px rgba(0,0,0,0.45),
      0 34px 80px -14px rgba(212,175,55,0.6),
      inset 0 -14px 24px rgba(0,0,0,0.28),
      inset 0 10px 18px rgba(255,255,255,0.4);
  }
  a.buzzer:active {
    transform: translateY(11px);
    box-shadow:
      0 5px 0 -4px rgba(0,0,0,0.45),
      0 18px 40px -14px var(--gold-glow),
      inset 0 -8px 16px rgba(0,0,0,0.32),
      inset 0 6px 12px rgba(255,255,255,0.3);
  }
  a.buzzer span {
    font-family: var(--f-display);
    font-size: 2rem;
    letter-spacing: 3px;
    color: var(--on-gold);
    text-transform: uppercase;
    transform: translateY(2px);
  }
  a.buzzer small {
    position: absolute;
    bottom: 44px;
    font-family: var(--f-mono);
    font-size: 0.55rem;
    font-weight: 700;
    letter-spacing: 2.5px;
    color: rgba(0,0,0,0.55);
    text-transform: uppercase;
  }

  /* Ondes qui émanent du buzzer */
  .buzzer-rings { pointer-events: none; }
  .buzzer-rings i {
    position: absolute;
    inset: 0;
    margin: auto;
    width: 226px;
    height: 226px;
    border-radius: 50%;
    border: 1px solid var(--gold-base);
    opacity: 0;
    animation: ringPulse 3.4s ease-out infinite;
  }
  .buzzer-rings i:nth-child(2) { animation-delay: 1.7s; }

  /* ── PROMPTEUR : la question se tape en direct ── */
  .prompter {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 54px;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 0 40px;
    border-top: 1px solid var(--gold-line);
    background: var(--header-bg);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    font-family: var(--f-mono);
    font-size: 0.76rem;
    z-index: 3;
  }
  .prompter .p-label {
    color: var(--gold-text);
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    font-size: 0.62rem;
    white-space: nowrap;
  }
  .prompter .p-line {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 2px;
    overflow: hidden;
    white-space: nowrap;
    color: var(--ink-2);
  }
  .prompter .p-cursor {
    display: inline-block;
    width: 8px;
    height: 15px;
    background: var(--gold-base);
    animation: blink 1s steps(1) infinite;
    flex-shrink: 0;
  }
  .prompter .p-cat {
    color: var(--ink-4);
    font-size: 0.62rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    white-space: nowrap;
  }

  /* ════════════════════════════
    BANDEAU DÉFILANT — catégories
  ════════════════════════════ */
  .marquee {
    border-bottom: 1px solid var(--gold-line);
    overflow: hidden;
    padding: 18px 0;
    background: var(--bg2);
  }
  .marquee-track {
    display: flex;
    align-items: center;
    gap: 30px;
    width: max-content;
    animation: marqueeMove 30s linear infinite;
    will-change: transform;
  }
  .marquee:hover .marquee-track { animation-play-state: paused; }
  .marquee-track b {
    font-family: var(--f-display);
    font-weight: 400;
    font-size: 1.3rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    white-space: nowrap;
    color: transparent;
    -webkit-text-stroke: 1px var(--gold-line-strong);
  }
  .marquee-track b.fill {
    color: var(--ink-3);
    -webkit-text-stroke: 0;
  }
  .marquee-track i {
    font-style: normal;
    font-family: var(--f-mono);
    color: var(--gold-base);
    opacity: 0.6;
    font-size: 0.8rem;
  }

  /* ════════════════════════════
    AUDIMAT — panneau de scores (stats)
  ════════════════════════════ */
  .scoreboard {
    max-width: 1240px;
    margin: 90px auto 0;
    padding: 0 40px;
  }
  .scoreboard-panel {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border: 1px solid var(--gold-line);
    border-radius: 4px;
    background: var(--bg2);
    overflow: hidden;
  }
  .sb-cell {
    padding: 30px 24px;
    border-left: 1px solid var(--gold-line);
    text-align: center;
  }
  .sb-cell:first-child { border-left: none; }
  .sb-value {
    font-family: var(--f-mono);
    font-weight: 700;
    font-size: 2.4rem;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-variant-numeric: tabular-nums;
    line-height: 1;
  }
  .sb-label {
    margin-top: 10px;
    font-family: var(--f-mono);
    font-size: 0.6rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--ink-3);
  }

  /* ════════════════════════════
    SÉQUENCES — structure commune
  ════════════════════════════ */
  .seq {
    max-width: 1240px;
    margin: 0 auto;
    padding: 110px 40px 0;
  }

  /* Chyron façon synthé TV (bandeau-titre) */
  .chyron {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: 1px solid var(--gold-line-strong);
    border-radius: 4px;
    padding: 7px 13px;
    font-family: var(--f-mono);
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold-text);
  }
  .chyron b {
    width: 8px; height: 8px;
    background: var(--gold-base);
    flex-shrink: 0;
  }

  .sec-title {
    margin: 20px 0 48px;
    font-family: var(--f-display);
    font-weight: 400;
    font-size: clamp(2rem, 4.8vw, 3.4rem);
    letter-spacing: 1px;
    text-transform: uppercase;
    line-height: 1.05;
    color: var(--ink);
  }
  .sec-title span {
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* ════════════════════════════
    LE PROGRAMME — les modes en lignes (grille TV)
  ════════════════════════════ */
  .prog { border-top: 1px solid var(--gold-line); }

  .prog-row {
    display: grid;
    grid-template-columns: 90px 1fr auto 60px;
    align-items: center;
    gap: 24px;
    padding: 34px 24px;
    border-bottom: 1px solid var(--gold-line);
    text-decoration: none;
    position: relative;
    overflow: hidden;
    transition: background 0.3s;
  }
  .prog-row:hover { background: var(--gold-tint); }

  /* Nom fantôme géant qui glisse au survol */
  .prog-row::after {
    content: attr(data-ghost);
    position: absolute;
    right: 40px;
    top: 50%;
    transform: translateY(-50%) translateX(30px);
    font-family: var(--f-display);
    font-size: 6.5rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: transparent;
    -webkit-text-stroke: 1px var(--gold-line);
    opacity: 0;
    transition: opacity 0.4s ease, transform 0.4s ease;
    pointer-events: none;
    white-space: nowrap;
  }
  .prog-row:hover::after { opacity: 0.6; transform: translateY(-50%) translateX(0); }

  .prog-num {
    font-family: var(--f-mono);
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ink-4);
    transition: color 0.3s;
  }
  .prog-row:hover .prog-num { color: var(--gold-text); }

  .prog-main { position: relative; z-index: 1; }
  .prog-main h3 {
    font-family: var(--f-display);
    font-weight: 400;
    font-size: clamp(1.7rem, 3.4vw, 2.5rem);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--ink);
    transition: color 0.3s;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }
  .prog-row:hover .prog-main h3 { color: var(--gold-text); }
  .prog-main p {
    margin-top: 8px;
    font-family: var(--f-mono);
    font-size: 0.74rem;
    color: var(--ink-3);
    line-height: 1.6;
  }

  .prog-main p b { color: var(--gold-text); font-weight: 700; }

  .seq-note {
    max-width: 64ch;
    margin: -20px 0 46px;
    font-family: var(--f-mono);
    font-size: 0.82rem;
    line-height: 1.7;
    color: var(--ink-2);
  }
  .seq-note b { color: var(--gold-text); font-weight: 700; }

  .chip-prime {
    font-family: var(--f-mono);
    font-size: 0.52rem;
    font-weight: 700;
    letter-spacing: 2px;
    background: var(--metallic);
    color: var(--on-gold);
    padding: 4px 9px;
    border-radius: 3px;
    text-transform: uppercase;
    transform: translateY(-2px);
  }

  .prog-tags {
    font-family: var(--f-mono);
    font-size: 0.62rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--gold-text);
    opacity: 0.85;
    position: relative;
    z-index: 1;
    text-align: right;
  }

  .prog-arrow {
    font-family: var(--f-display);
    font-size: 1.5rem;
    color: var(--ink-4);
    text-align: right;
    transition: color 0.3s, transform 0.3s;
    position: relative;
    z-index: 1;
  }
  .prog-row:hover .prog-arrow { color: var(--gold-text); transform: translateX(8px); }

  /* ════════════════════════════
    RÉGIE TECHNIQUE — features en grille hairline
  ════════════════════════════ */
  .regie {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: var(--gold-line);
    border: 1px solid var(--gold-line);
    border-radius: 4px;
    overflow: hidden;
  }
  .regie-cell {
    background: var(--bg);
    padding: 32px 26px 36px;
    position: relative;
    transition: background 0.3s;
  }
  .regie-cell:hover { background: var(--bg2); }

  /* Coins viseur caméra au survol */
  .regie-cell::before,
  .regie-cell::after {
    content:'';
    position: absolute;
    width: 14px; height: 14px;
    border: 0 solid var(--gold-base);
    opacity: 0;
    transition: opacity 0.3s;
  }
  .regie-cell::before { top: 10px; left: 10px; border-top-width: 1px; border-left-width: 1px; }
  .regie-cell::after  { bottom: 10px; right: 10px; border-bottom-width: 1px; border-right-width: 1px; }
  .regie-cell:hover::before,
  .regie-cell:hover::after { opacity: 1; }

  .regie-num {
    font-family: var(--f-mono);
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 2px;
    color: var(--gold-text);
  }
  .regie-title {
    margin-top: 16px;
    font-family: var(--f-body);
    font-weight: 700;
    font-size: 0.92rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--ink);
  }
  .regie-desc {
    margin-top: 10px;
    font-size: 0.8rem;
    line-height: 1.7;
    color: var(--ink-2);
  }

  /* ════════════════════════════
    TABLEAU DES SCORES — leaderboard
  ════════════════════════════ */
  .board {
    border: 1px solid var(--gold-line);
    border-radius: 4px;
    background: var(--card);
    overflow: hidden;
  }
  .bd-head {
    display: grid;
    grid-template-columns: 64px 1fr 130px 110px;
    padding: 14px 26px;
    background: var(--gold-tint);
    border-bottom: 1px solid var(--gold-line);
  }
  .bd-head span {
    font-family: var(--f-mono);
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--ink-3);
  }
  .bd-row {
    display: grid;
    grid-template-columns: 64px 1fr 130px 110px;
    align-items: center;
    padding: 19px 26px;
    border-bottom: 1px solid var(--line-soft);
    transition: background 0.2s;
    position: relative;
  }
  .bd-row:hover { background: var(--gold-tint); }
  .bd-row:last-of-type { border-bottom: none; }

  .bd-rank {
    font-family: var(--f-mono);
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--ink-4);
  }
  .bd-rank.gold {
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .bd-rank.silver { color: #9a9a9a; }
  .bd-rank.bronze { color: #c47b3a; }

  .bd-name {
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--ink);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  .chip-leader {
    font-family: var(--f-mono);
    font-size: 0.5rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    background: var(--metallic);
    color: var(--on-gold);
    padding: 3px 8px;
    border-radius: 3px;
  }

  .bd-elo {
    font-family: var(--f-mono);
    font-weight: 700;
    font-size: 1rem;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-variant-numeric: tabular-nums;
  }
  .bd-games {
    font-family: var(--f-mono);
    font-size: 0.7rem;
    color: var(--ink-3);
  }

  /* Rang 1 : barre dorée + balayage lumineux */
  .bd-row.leader { overflow: hidden; }
  .bd-row.leader::before {
    content:'';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--metallic);
  }
  .bd-row.leader::after {
    content:'';
    position: absolute;
    top: 0; bottom: 0;
    left: -45%;
    width: 40%;
    background: linear-gradient(100deg, transparent, rgba(212,175,55,0.10), transparent);
    animation: shimmerSweep 3.6s ease-in-out infinite;
    pointer-events: none;
  }

  .bd-empty,
  .bd-link {
    display: block;
    text-align: center;
    padding: 16px;
    font-family: var(--f-mono);
    font-size: 0.64rem;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--ink-4);
    text-decoration: none;
    border-top: 1px solid var(--gold-tint);
    transition: color 0.3s, letter-spacing 0.3s;
  }
  a.bd-link:hover { color: var(--gold-text); letter-spacing: 3.5px; }

  /* ════════════════════════════
    FINALE — CTA encadré viseur
  ════════════════════════════ */
  .finale {
    text-align: center;
    padding: 130px 40px 140px;
  }
  .finale-frame {
    position: relative;
    display: inline-block;
    padding: 64px 72px;
  }
  .finale-frame i {
    position: absolute;
    width: 20px; height: 20px;
    border: 0 solid var(--gold-line-strong);
  }
  .finale-frame i:nth-child(1) { top: 0; left: 0; border-top-width: 1px; border-left-width: 1px; }
  .finale-frame i:nth-child(2) { top: 0; right: 0; border-top-width: 1px; border-right-width: 1px; }
  .finale-frame i:nth-child(3) { bottom: 0; left: 0; border-bottom-width: 1px; border-left-width: 1px; }
  .finale-frame i:nth-child(4) { bottom: 0; right: 0; border-bottom-width: 1px; border-right-width: 1px; }

  .finale h2 {
    font-family: var(--f-display);
    font-weight: 400;
    font-size: clamp(2.4rem, 6.5vw, 4.6rem);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    line-height: 1.05;
    color: var(--ink);
  }
  .finale h2 span {
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .finale p {
    margin: 18px 0 34px;
    font-family: var(--f-mono);
    font-size: 0.74rem;
    letter-spacing: 1.5px;
    color: var(--ink-3);
  }

  /* ════════════════════════════
    GÉNÉRIQUE — footer
  ════════════════════════════ */
  footer {
    border-top: 1px solid var(--gold-line);
    background: var(--bg2);
  }
  .credits {
    max-width: 1240px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 40px;
    padding: 52px 40px;
  }
  .credits-brand b {
    display: block;
    font-family: var(--f-display);
    font-weight: 400;
    font-size: 1.3rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .credits-brand span {
    display: block;
    margin-top: 8px;
    font-family: var(--f-mono);
    font-size: 0.6rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--ink-4);
  }
  .credits-nav {
    display: flex;
    gap: 24px;
    list-style: none;
  }
  .credits-nav a {
    font-family: var(--f-mono);
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    color: var(--ink-3);
    transition: color 0.3s;
  }
  .credits-nav a:hover { color: var(--gold-text); }
  .credits-cta { text-align: right; }

  .fin {
    text-align: center;
    padding: 16px;
    border-top: 1px solid var(--line-soft);
    font-family: var(--f-mono);
    font-size: 0.6rem;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--ink-4);
  }
  .footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 16px 40px;
    border-top: 1px solid var(--line-soft);
  }
  .footer-bottom span {
    font-family: var(--f-mono);
    font-size: 0.58rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--ink-4);
  }
  .footer-bottom span:last-child { color: var(--gold-text); opacity: 0.7; }

  /* ════════════════════════════
    ANIMATIONS
  ════════════════════════════ */
  @keyframes fadeUp {
    from { transform: translateY(26px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  @keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
  }
  @keyframes wipeIn {
    to { clip-path: inset(0 0 0 0); }
  }
  @keyframes blink {
    0%, 55% { opacity: 1; }
    56%, 100% { opacity: 0.15; }
  }
  @keyframes beamSweep {
    from { transform: translateX(-50%) rotate(-8deg); }
    to   { transform: translateX(-50%) rotate(8deg); }
  }
  @keyframes spinSlow { to { transform: rotate(360deg); } }
  @keyframes ringPulse {
    0%   { transform: scale(1);    opacity: 0.7; }
    100% { transform: scale(1.65); opacity: 0; }
  }
  @keyframes marqueeMove { to { transform: translateX(-50%); } }
  @keyframes shimmerSweep {
    0%        { left: -45%; }
    60%, 100% { left: 110%; }
  }

  /* ════════════════════════════
    RÉVÉLATION AU SCROLL (une seule fois)
  ════════════════════════════ */
  .rv {
    opacity: 0;
    transform: translateY(26px);
    transition: opacity 0.7s ease, transform 0.7s cubic-bezier(0.2,0.8,0.2,1);
    transition-delay: var(--d, 0s);
  }
  .rv.visible { opacity: 1; transform: translateY(0); }

  /* ════════════════════════════
    REDUCED MOTION
  ════════════════════════════ */
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
      animation-duration: 0.001s !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.001s !important;
    }
    .marquee-track { animation: none; transform: none; }
    .beam, .orbit, .buzzer-rings i, .bd-row.leader::after { animation: none !important; }
    .onair, .headline .hl, .hero-sub, .hero-ctas, .buzzer-stage {
      opacity: 1 !important;
      clip-path: none !important;
      animation: none !important;
    }
    .rv { opacity: 1 !important; transform: none !important; transition: none !important; }
  }

  /* ════════════════════════════
    RESPONSIVE
  ════════════════════════════ */
  @media (max-width: 1100px) {
    header { grid-template-columns: auto 1fr auto; padding: 0 28px; gap: 20px; }
    .hero { gap: 24px; padding: 110px 32px 120px; }
    .orbit { width: 330px; height: 330px; }
    .pedestal { width: 280px; height: 280px; }
    .pedestal::before { width: 238px; height: 238px; }
    a.buzzer { width: 200px; height: 200px; }
    .buzzer-rings i { width: 200px; height: 200px; }
    .seq { padding: 96px 32px 0; }
    .scoreboard { padding: 0 32px; }
    .regie { grid-template-columns: repeat(2, 1fr); }
    .prog-row::after { font-size: 5rem; }
  }

  @media (max-width: 900px) {
    header { padding: 0 20px; grid-template-columns: 1fr auto; }
    header > nav,
    .header-right .btn-connexion { display: none; }
    #burger-trigger { display: inline-flex; }

    .hero {
      grid-template-columns: 1fr;
      text-align: center;
      padding: 104px 20px 130px;
      gap: 12px;
    }
    .hero-left { display: flex; flex-direction: column; align-items: center; }
    .hero-sub { font-size: 0.88rem; }
    .hero-ctas { justify-content: center; }
    .buzzer-stage { margin-top: 34px; }
    .headline .hl.ghost { -webkit-text-stroke-width: 1.5px; }

    .prompter { padding: 0 18px; }
    .prompter .p-cat { display: none; }

    .prog-row { grid-template-columns: 54px 1fr 44px; }
    .prog-tags { display: none; }
    .prog-row::after { display: none; }

    .scoreboard { margin-top: 70px; }
    .scoreboard-panel { grid-template-columns: repeat(2, 1fr); }
    .sb-cell { border-top: 1px solid var(--gold-line); }
    .sb-cell:nth-child(1), .sb-cell:nth-child(2) { border-top: none; }
    .sb-cell:nth-child(3) { border-left: none; }

    .credits { grid-template-columns: 1fr; text-align: center; gap: 26px; padding: 42px 24px; }
    .credits-nav { justify-content: center; flex-wrap: wrap; gap: 14px 20px; }
    .credits-cta { text-align: center; }
  }

  @media (max-width: 600px) {
    header { padding: 0 16px; height: 64px; }
    header.scrolled { height: 56px; }
    .logo { font-size: 0.95rem; letter-spacing: 2px; }
    .icon-btn { width: 34px; height: 34px; }

    .hero { padding: 96px 16px 128px; }
    .orbit { display: none; }
    .pedestal { width: 246px; height: 246px; }
    .pedestal::before { width: 208px; height: 208px; }
    a.buzzer { width: 176px; height: 176px; }
    a.buzzer span { font-size: 1.6rem; }
    a.buzzer small { bottom: 34px; }
    .buzzer-rings i { width: 176px; height: 176px; }

    .prompter { height: 50px; font-size: 0.68rem; gap: 10px; }

    .marquee { padding: 13px 0; }
    .marquee-track b { font-size: 1.05rem; }
    .marquee-track { gap: 20px; }

    .seq { padding: 76px 16px 0; }
    .scoreboard { padding: 0 16px; margin-top: 60px; }
    .sb-cell { padding: 22px 14px; }
    .sb-value { font-size: 1.7rem; }
    .sb-label { font-size: 0.52rem; letter-spacing: 2px; }

    .sec-title { margin: 16px 0 36px; }

    .prog-row { padding: 26px 8px; gap: 14px; }
    .prog-main p { font-size: 0.68rem; }

    .regie { grid-template-columns: 1fr; }

    .bd-head, .bd-row { grid-template-columns: 48px 1fr 92px; padding: 15px 16px; }
    .bd-games { display: none; }
    .bd-head span:nth-child(4) { display: none; }
    .bd-name { font-size: 0.84rem; }
    .bd-elo { font-size: 0.9rem; }

    .finale { padding: 96px 16px 104px; }
    .finale-frame { padding: 40px 22px; display: block; }

    .footer-bottom { flex-direction: column; text-align: center; padding: 14px 16px; }
  }

  @media (max-width: 380px) {
    .hero-ctas { flex-direction: column; width: 100%; }
    .hero-ctas .btn-solid, .hero-ctas .btn-ghost { width: 100%; text-align: center; }
  }
</style>
</head>
<body>

<div class="grid-lines" aria-hidden="true"></div>

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

<!-- ════ HERO — LE PLATEAU ════ -->
<section class="hero">
  <div class="beams" aria-hidden="true">
    <span class="beam"></span>
    <span class="beam b2"></span>
  </div>

  <div class="hero-left">
    <p class="onair"><i aria-hidden="true"></i>En direct &middot; Saison 2026</p>

    <h1 class="headline">
      <span class="hl ghost">Une question.</span>
      <span class="hl gold">Un champion.</span>
    </h1>

    <p class="hero-sub">
      Le quiz multijoueur de culture générale, en <b>temps réel</b>.
      Solo contre la montre, duel 1v1 au buzzer, tournoi à élimination —
      montez au classement ELO et prenez la première place.
    </p>

    <div class="hero-ctas">
      <a href="game.php" class="btn-solid">Entrer sur le plateau</a>
      <a href="rules.php" class="btn-ghost">Lire les règles</a>
    </div>
  </div>

  <div class="buzzer-stage" aria-hidden="false">
    <svg class="orbit" viewBox="0 0 380 380" aria-hidden="true">
      <defs>
        <path id="orb" d="M190,190 m-166,0 a166,166 0 1,1 332,0 a166,166 0 1,1 -332,0"/>
      </defs>
      <text><textPath href="#orb">Appuyez pour jouer &middot; QPC &middot; Appuyez pour jouer &middot; QPC &middot; Appuyez pour jouer &middot; QPC &middot;&nbsp;</textPath></text>
    </svg>

    <span class="buzzer-rings" aria-hidden="true"><i></i><i></i></span>

    <span class="pedestal" aria-hidden="true"></span>

    <a href="game.php" class="buzzer" aria-label="Jouer maintenant">
      <span>Jouer</span>
      <small>Buzzer</small>
    </a>
  </div>

  <!-- Prompteur : la question se tape en direct -->
  <div class="prompter" aria-hidden="true">
    <span class="p-label">Prochaine question /</span>
    <span class="p-line"><span id="typed"></span><span class="p-cursor"></span></span>
    <span class="p-cat">Cat &middot; aléatoire — 500+ en base</span>
  </div>
</section>

<!-- ════ BANDEAU CATÉGORIES ════ -->
<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <b>Histoire</b><i>/</i><b class="fill">Géographie</b><i>/</i><b>Sciences</b><i>/</i><b class="fill">Sport</b><i>/</i><b>Cinéma</b><i>/</i><b class="fill">Musique</b><i>/</i><b>Littérature</b><i>/</i><b class="fill">Technologie</b><i>/</i>
    <b>Histoire</b><i>/</i><b class="fill">Géographie</b><i>/</i><b>Sciences</b><i>/</i><b class="fill">Sport</b><i>/</i><b>Cinéma</b><i>/</i><b class="fill">Musique</b><i>/</i><b>Littérature</b><i>/</i><b class="fill">Technologie</b><i>/</i>
  </div>
</div>

<!-- ════ AUDIMAT — chiffres clés ════ -->
<div class="scoreboard rv">
  <div class="scoreboard-panel">
    <div class="sb-cell">
      <div class="sb-value" data-count="500" data-suffix="+">500+</div>
      <div class="sb-label">Questions en base</div>
    </div>
    <div class="sb-cell">
      <div class="sb-value" data-count="4">4</div>
      <div class="sb-label">Modes de jeu</div>
    </div>
    <div class="sb-cell">
      <div class="sb-value" data-count="3">3</div>
      <div class="sb-label">Niveaux</div>
    </div>
    <div class="sb-cell">
      <div class="sb-value">&infin;</div>
      <div class="sb-label">Championnats</div>
    </div>
  </div>
</div>

<!-- ════ SÉQ. 01 — LE PROGRAMME ════ -->
<section class="seq">
  <p class="chyron rv"><b aria-hidden="true"></b>Séq. 01 &middot; Le programme</p>
  <h2 class="sec-title rv" style="--d:.08s">Trois façons de <span>monter sur le plateau</span></h2>
  <p class="seq-note rv" style="--d:.12s">Le duel et le tournoi se jouent en <b>amical</b> — juste pour le fun, invités bienvenus, l'ELO ne bouge pas — ou en <b>classé</b>, où chaque manche fait grimper ou chuter ton classement.</p>

  <div class="prog">
    <a href="game.php" class="prog-row rv" data-ghost="Solo">
      <span class="prog-num">01</span>
      <div class="prog-main">
        <h3>Solo</h3>
        <p>Contre la montre. Trois niveaux de difficulté, un record personnel à battre.</p>
      </div>
      <span class="prog-tags">Chrono &middot; Adaptatif &middot; Record</span>
      <span class="prog-arrow" aria-hidden="true">&rarr;</span>
    </a>

    <a href="game.php" class="prog-row rv" style="--d:.08s" data-ghost="Duel">
      <span class="prog-num">02</span>
      <div class="prog-main">
        <h3>Duel 1vs1 <span class="chip-prime">Prime time</span></h3>
        <p>Buzz synchronisé et paris de points. En <b>amical</b> pour t'entraîner, ou en <b>classé</b> quand l'ELO est en jeu. Zéro excuse.</p>
      </div>
      <span class="prog-tags">Amical ou classé &middot; Temps réel &middot; Buzzer</span>
      <span class="prog-arrow" aria-hidden="true">&rarr;</span>
    </a>

    <a href="game.php" class="prog-row rv" style="--d:.16s" data-ghost="Tournoi">
      <span class="prog-num">03</span>
      <div class="prog-main">
        <h3>Tournoi</h3>
        <p>Quatre joueurs, trois manches, un seul champion. En <b>amical</b> entre potes ou en <b>classé</b> pour la gloire du classement.</p>
      </div>
      <span class="prog-tags">4 joueurs &middot; 3 manches &middot; Amical ou classé</span>
      <span class="prog-arrow" aria-hidden="true">&rarr;</span>
    </a>
  </div>
</section>

<!-- ════ SÉQ. 02 — RÉGIE TECHNIQUE ════ -->
<section class="seq">
  <p class="chyron rv"><b aria-hidden="true"></b>Séq. 02 &middot; Régie technique</p>
  <h2 class="sec-title rv" style="--d:.08s">Ce qui tourne <span>derrière le décor</span></h2>

  <div class="regie">
    <div class="regie-cell rv">
      <span class="regie-num">[ 01 ]</span>
      <h3 class="regie-title">Banque de questions</h3>
      <p class="regie-desc">500+ questions structurées par catégorie, mélangées dynamiquement — aucune répétition dans une même session.</p>
    </div>
    <div class="regie-cell rv" style="--d:.08s">
      <span class="regie-num">[ 02 ]</span>
      <h3 class="regie-title">Difficulté adaptative</h3>
      <p class="regie-desc">Chaque question porte un niveau ELO. La sélection se cale sur le niveau réel du match, ni trop facile, ni injouable.</p>
    </div>
    <div class="regie-cell rv" style="--d:.16s">
      <span class="regie-num">[ 03 ]</span>
      <h3 class="regie-title">Temps réel</h3>
      <p class="regie-desc">WebSockets pour le buzz, les scores et les paris. Déconnexion brève tolérée — la partie vous attend.</p>
    </div>
    <div class="regie-cell rv" style="--d:.24s">
      <span class="regie-num">[ 04 ]</span>
      <h3 class="regie-title">Progression</h3>
      <p class="regie-desc">Historique complet, statistiques détaillées et courbe ELO pour suivre votre montée vers le titre.</p>
    </div>
  </div>
</section>

<!-- ════ SÉQ. 03 — TABLEAU DES SCORES ════ -->
<section class="seq" id="classement">
  <p class="chyron rv"><b aria-hidden="true"></b>Séq. 03 &middot; Tableau des scores</p>
  <h2 class="sec-title rv" style="--d:.08s">Les <span>candidats en tête</span></h2>

  <div class="board rv" style="--d:.12s">
    <div class="bd-head">
      <span>#</span>
      <span>Candidat</span>
      <span>ELO</span>
      <span>Parties</span>
    </div>
    <?php if (empty($lb_top)): ?>
    <div class="bd-empty">Le tableau se remplit dès les premiers duels classés — soyez le premier champion.</div>
    <?php else: foreach ($lb_top as $i => $p):
        $rank = $i + 1;
        $rc   = [1 => 'gold', 2 => 'silver', 3 => 'bronze'][$rank] ?? '';
        $ini  = mb_strtoupper(mb_substr($p['username'], 0, 2, 'UTF-8'), 'UTF-8');
    ?>
    <div class="bd-row<?= $rank === 1 ? ' leader' : '' ?>">
      <div class="bd-rank <?= $rc ?>"><?= str_pad($rank, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="bd-name">
        <?= htmlspecialchars($p['username']) ?>
        <?php if ($rank === 1): ?><span class="chip-leader">Leader</span><?php endif; ?>
      </div>
      <div class="bd-elo" data-count="<?= (int)$p['elo'] ?>"><?= number_format((int)$p['elo'], 0, ',', ' ') ?></div>
      <div class="bd-games"><?= (int)$p['total_games'] ?> partie<?= ((int)$p['total_games'] > 1 ? 's' : '') ?></div>
    </div>
    <?php endforeach; endif; ?>
    <a class="bd-link" href="classement.php">Tableau complet &rarr;</a>
  </div>
</section>

<!-- ════ FINALE ════ -->
<section class="finale rv">
  <div class="finale-frame">
    <i aria-hidden="true"></i><i aria-hidden="true"></i><i aria-hidden="true"></i><i aria-hidden="true"></i>
    <h2>Prêt à <span>buzzer</span> ?</h2>
    <p>Gratuit &middot; Sans installation &middot; Directement dans le navigateur</p>
    <a href="game.php" class="btn-solid">Commencer la partie</a>
  </div>
</section>

</main>

<!-- ════ GÉNÉRIQUE ════ -->
<footer>
  <div class="credits">
    <div class="credits-brand">
      <b>Question Champion</b>
      <span>Une question. Un champion.</span>
    </div>
    <ul class="credits-nav">
      <li><a href="index.php">Accueil</a></li>
      <li><a href="rules.php">Règles</a></li>
      <li><a href="classement.php">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
      <li><a href="connexion.php">Connexion</a></li>
    </ul>
    <div class="credits-cta">
      <a href="game.php" class="btn-solid">▶ Jouer</a>
    </div>
  </div>
  <p class="fin">— Fin de l'émission &middot; À vous de jouer —</p>
  <div class="footer-bottom">
    <span>&copy; 2025 QPC — Tous droits réservés</span>
    <span>HESTIM &middot; Projet Semestre 2</span>
  </div>
</footer>

<script>
/* ═══════════════════════════════════════════
   PRÉFÉRENCES
═══════════════════════════════════════════ */
const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

/* ═══════════════════════════════════════════
   HEADER — état "scrolled"
═══════════════════════════════════════════ */
(function () {
  const bar = document.querySelector('header');
  if (!bar) return;
  let ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      bar.classList.toggle('scrolled', document.documentElement.scrollTop > 40);
      ticking = false;
    });
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

/* ═══════════════════════════════════════════
   PROMPTEUR — questions tapées en direct
═══════════════════════════════════════════ */
(function () {
  const el = document.getElementById('typed');
  if (!el) return;

  const questions = [
    "Quelle est la capitale de l'Australie ?",
    "En quelle année a débuté la Révolution française ?",
    "Qui a peint La Nuit étoilée ?",
    "Combien d'os compte le corps humain adulte ?",
    "Quel fleuve traverse Bagdad ?",
    "Quel est l'élément chimique de symbole Au ?"
  ];

  if (prefersReduced) {
    el.textContent = questions[0];
    return;
  }

  let qi = 0, ci = 0, deleting = false;

  function step() {
    const q = questions[qi];

    if (!deleting) {
      ci++;
      el.textContent = q.slice(0, ci);
      if (ci === q.length) {
        deleting = true;
        setTimeout(step, 2200);           // pause, question affichée
        return;
      }
      setTimeout(step, 34 + Math.random() * 40);  // frappe
    } else {
      ci -= 3;
      if (ci <= 0) {
        ci = 0;
        el.textContent = '';
        deleting = false;
        qi = (qi + 1) % questions.length;
        setTimeout(step, 500);            // avant la question suivante
        return;
      }
      el.textContent = q.slice(0, ci);
      setTimeout(step, 18);               // effacement rapide
    }
  }

  setTimeout(step, 1600);
})();

/* ═══════════════════════════════════════════
   COMPTEURS [data-count] — un seul déclenchement
═══════════════════════════════════════════ */
(function () {
  const els = document.querySelectorAll('[data-count]');
  if (!els.length || !('IntersectionObserver' in window)) return;

  const fmt  = n => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  const ease = t => 1 - Math.pow(1 - t, 3);

  const io = new IntersectionObserver((entries) => {
    entries.forEach(en => {
      if (!en.isIntersecting) return;
      io.unobserve(en.target);

      const el = en.target;
      const target = parseInt(el.dataset.count, 10) || 0;
      const suffix = el.dataset.suffix || '';

      if (prefersReduced) { el.textContent = fmt(target) + suffix; return; }

      const dur = 1300;
      const t0 = performance.now();
      function tick(t) {
        const k = Math.min(1, (t - t0) / dur);
        el.textContent = fmt(Math.round(target * ease(k))) + suffix;
        if (k < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }, { threshold: 0.4 });

  els.forEach(el => io.observe(el));
})();

/* ═══════════════════════════════════════════
   RÉVÉLATION AU SCROLL — une seule fois
═══════════════════════════════════════════ */
(function () {
  const els = document.querySelectorAll('.rv');
  if (!els.length) return;

  if (prefersReduced || !('IntersectionObserver' in window)) {
    els.forEach(el => el.classList.add('visible'));
    return;
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach(en => {
      if (en.isIntersecting) {
        en.target.classList.add('visible');
        io.unobserve(en.target);
      }
    });
  }, { threshold: 0.14 });

  els.forEach(el => io.observe(el));
})();
</script>
</body>
</html>
