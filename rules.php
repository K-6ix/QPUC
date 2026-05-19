<?php
session_start();
require_once "db.php";
require_once "funnel_tracker.php";
trackFunnel('visite_regles');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Règles — Question Champion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Rajdhani:wght@300;500;700&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&family=Great+Vibes&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold-light: #fcf6ba;
            --gold-base: #d4af37;
            --gold-dark: #8a6e2f;
            --gold-muted: rgba(212,175,55,0.12);
            --bg: #060606;
            --card-bg: #0e0e0e;
            --text: #ffffff;
            --metallic: linear-gradient(135deg, #8a6e2f 0%, #d4af37 30%, #fcf6ba 50%, #d4af37 70%, #8a6e2f 100%);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Rajdhani', sans-serif;
            overflow-x: hidden;
        }

        /* noise */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
            opacity: 0.025;
            pointer-events: none;
            z-index: 0;
        }

        .line-left, .line-right {
            position: fixed;
            top: 0; bottom: 0;
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(212,175,55,0.15) 30%, rgba(212,175,55,0.15) 70%, transparent);
            z-index: 0;
        }
        .line-left { left: 60px; }
        .line-right { right: 60px; }

        /* ── HEADER ────────────────────────────────── */
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
            -webkit-backdrop-filter: blur(12px);
            opacity: 0;
            animation: fadeDown 0.8s cubic-bezier(0.2,0.8,0.2,1) 0.2s forwards;
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
            font-family: 'Montserrat', sans-serif;
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
            content: '';
            position: absolute;
            width: 0; height: 1px;
            bottom: -4px; left: 0;
            background: var(--metallic);
            transition: width 0.3s;
        }
        nav a:hover::after { width: 100%; }

        .btn-play {
            font-family: 'Montserrat', sans-serif;
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
        .btn-play::after { display: none; }

        .header-right {
            display: flex;
            justify-content: flex-end;
        }

        .btn-connexion {
            font-family: 'Montserrat', sans-serif;
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

        /* ── PAGE HERO ──────────────────────────────── */
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
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: clamp(3rem, 7vw, 6rem);
            line-height: 1;
            color: #fff;
        }

        .page-hero h1 em {
            font-style: italic;
            background: var(--metallic);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .divider {
            width: 60px;
            height: 1px;
            background: var(--metallic);
            margin: 28px auto 0;
            opacity: 0.6;
        }

        /* ── CARDS STACK ────────────────────────────── */
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
            border: 1px solid rgba(212,175,55,0.15);
            display: grid;
            grid-template-columns: 1fr 320px;
            min-height: 340px;
            background: var(--card-bg);
            transition: border-color 0.4s;
        }
        .card:hover { border-color: rgba(212,175,55,0.35); }

        .card:nth-child(1) { z-index: 1; top: 90px; }
        .card:nth-child(2) { z-index: 2; top: 106px; }
        .card:nth-child(3) { z-index: 3; top: 122px; }
        .card:nth-child(4) { z-index: 4; top: 138px; }
        .card:nth-child(5) { z-index: 5; top: 154px; }
        .card:nth-child(6) { z-index: 6; top: 170px; }

        /* left: text */
        .card-body {
            padding: 48px 48px 48px 52px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
            border-right: 1px solid rgba(212,175,55,0.1);
        }

        .card-number {
            font-size: 0.65rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--gold-dark);
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.1;
        }

        .card-text {
            font-size: 1rem;
            font-weight: 300;
            color: rgba(255,255,255,0.6);
            line-height: 1.75;
            max-width: 420px;
        }

        /* right: visual panel */
        .card-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(212,175,55,0.04);
            position: relative;
            overflow: hidden;
        }

        /* large numeral decoration */
        .card-visual::before {
            content: attr(data-num);
            position: absolute;
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 180px;
            color: rgba(212,175,55,0.05);
            line-height: 1;
            user-select: none;
        }

        /* icon */
        .card-icon {
            font-size: 64px;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 0 20px rgba(212,175,55,0.2));
        }

        /* card accent strip */
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

        /* tag badges */
        .card-badge {
            display: inline-block;
            font-size: 0.65rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 4px 12px;
            border: 1px solid rgba(212,175,55,0.3);
            color: var(--gold-base);
            border-radius: 2px;
            align-self: flex-start;
        }

        /* ── ANIMATIONS ─────────────────────────────── */
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ── RESPONSIVE ─────────────────────────────── */
        @media (max-width: 768px) {
            header { grid-template-columns: 1fr auto; padding: 0 20px; }
            .line-left, .line-right { display: none; }
            .card { grid-template-columns: 1fr; }
            .card-visual { display: none; }
            .card-body { padding: 32px 28px; }
        }
        @media (max-width: 500px) {
            header { grid-template-columns: 1fr; height: auto; padding: 14px 18px; }
            nav ul { flex-wrap: wrap; gap: 12px; justify-content: center; }
            .header-right { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="line-left"></div>
    <div class="line-right"></div>

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
        <div class="header-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn-connexion">Dashboard</a>
            <?php else: ?>
                <a href="connexion.php" class="btn-connexion">Connexion</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-hero">
        <p class="label">Manuel du joueur</p>
        <h1>Les <em>Règles</em> du jeu</h1>
        <div class="divider"></div>
    </div>

    <div class="spacer"></div>

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
                <span class="card-number">04 — Mode 1v1</span>
                <h2 class="card-title">Duel individuel</h2>
                <p class="card-text">
                    Affrontez un adversaire en tête-à-tête. Les deux joueurs répondent aux mêmes questions simultanément. Celui qui répond correctement le plus vite remporte la manche.
                </p>
                <span class="card-badge">Compétitif</span>
            </div>
            <div class="card-visual" data-num="04">
                <span class="card-icon">⚔️</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <span class="card-number">05 — Mode 2v2</span>
                <h2 class="card-title">Jeu en équipe</h2>
                <p class="card-text">
                    Formez une équipe de deux joueurs et affrontez un autre duo. La stratégie et la communication sont clés : chaque coéquipier apporte ses connaissances pour maximiser le score.
                </p>
                <span class="card-badge">Coopératif</span>
            </div>
            <div class="card-visual" data-num="05">
                <span class="card-icon">🤝</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <span class="card-number">06 — Mode Solo</span>
                <h2 class="card-title">Défi personnel</h2>
                <p class="card-text">
                    Jouez à votre rythme et battez votre propre record. Une série de questions dans un temps imparti — l'objectif est d'obtenir le meilleur score possible et d'entrer dans le classement.
                </p>
                <span class="card-badge">Solo</span>
            </div>
            <div class="card-visual" data-num="06">
                <span class="card-icon">🧠</span>
            </div>
        </div>

    </section>

</body>
</html>
