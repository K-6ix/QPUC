<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Règles — Question Champion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Rajdhani:wght@300;500;700&family=Great+Vibes&display=swap" rel="stylesheet">
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
            grid-template-columns: 180px 1fr 180px;
            align-items: center;
            height: 72px;
            padding: 0 80px;
            border-bottom: 1px solid rgba(212,175,55,0.12);
            background: rgba(6,6,6,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            opacity: 0;
            animation: fadeDown 0.8s cubic-bezier(0.2,0.8,0.2,1) 0.1s forwards;
        }

        .logo {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            background: var(--metallic);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 40px;
            align-items: center;
        }

        nav a {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            color: rgba(255,255,255,0.55);
            transition: color 0.3s;
            position: relative;
        }
        nav a:hover { color: var(--gold-light); }
        nav a::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 0;
            width: 0; height: 1px;
            background: var(--metallic);
            transition: width 0.3s;
        }
        nav a:hover::after { width: 100%; }

        .btn-play {
            background: var(--metallic);
            color: #000 !important;
            -webkit-text-fill-color: #000 !important;
            padding: 7px 22px;
            border-radius: 2px;
            font-weight: 700;
            letter-spacing: 2px;
        }
        .btn-play::after { display: none; }
        .btn-play:hover { opacity: 0.9; }

        .header-right {
            display: flex;
            justify-content: flex-end;
        }

        .btn-connexion {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--gold-base);
            border: 1px solid rgba(212,175,55,0.35);
            padding: 7px 20px;
            border-radius: 2px;
            transition: background 0.3s, color 0.3s;
        }
        .btn-connexion:hover {
            background: var(--gold-muted);
            color: var(--gold-light);
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

        .card:nth-child(1)  { z-index: 1;  top: 90px;  }
        .card:nth-child(2)  { z-index: 2;  top: 106px; }
        .card:nth-child(3)  { z-index: 3;  top: 122px; }
        .card:nth-child(4)  { z-index: 4;  top: 138px; }
        .card:nth-child(5)  { z-index: 5;  top: 154px; }
        .card:nth-child(6)  { z-index: 6;  top: 170px; }
        .card:nth-child(7)  { z-index: 7;  top: 186px; }
        .card:nth-child(8)  { z-index: 8;  top: 202px; }
        .card:nth-child(9)  { z-index: 9;  top: 218px; }
        .card:nth-child(10) { z-index: 10; top: 234px; }
        .card:nth-child(11) { z-index: 11; top: 250px; }

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
            header { grid-template-columns: 1fr 1fr; padding: 0 20px; }
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
        <div class="logo">Hestim</div>
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

        <!-- ═══════════ MODE CHAMPIONNAT ═══════════ -->

        <div class="card">
            <div class="card-body">
                <span class="card-number">07 — Mode Championnat</span>
                <h2 class="card-title">Le grand titre</h2>
                <p class="card-text">
                    Le mode ultime : 4 joueurs, 3 manches éliminatoires successives, un seul champion à la fin. Chaque manche éprouve une compétence différente — vitesse, stratégie, sang-froid. À chaque fin de manche, le joueur le moins performant est éliminé. Les deux finalistes s'affrontent au buzz partagé pour le titre suprême.
                </p>
                <span class="card-badge">4 joueurs · 3 manches</span>
            </div>
            <div class="card-visual" data-num="07">
                <span class="card-icon">👑</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <span class="card-number">08 — Manche 1</span>
                <h2 class="card-title">Le 9 points gagnants</h2>
                <p class="card-text">
                    Format parallèle façon Kahoot : les 4 joueurs voient la même question et choisissent leur réponse de leur côté. <strong>+1 point</strong> par bonne réponse, <strong>−1 point</strong> par erreur. Le premier à atteindre <strong>9 points</strong> termine la manche, sinon limite à 15 questions. Le joueur avec le pire score est éliminé. En cas d'égalité au dernier rang : barrage en mort subite.
                </p>
                <span class="card-badge">Vitesse de réaction</span>
            </div>
            <div class="card-visual" data-num="08">
                <span class="card-icon">⚡</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <span class="card-number">09 — Manche 2</span>
                <h2 class="card-title">Le pari secret</h2>
                <p class="card-text">
                    3 joueurs restants. Avant le duel, chacun choisit 4 catégories : un pool de 4 catégories sera tiré au hasard parmi les choix communs. Puis chaque joueur place un <strong>pari secret</strong> sur une des 8 questions à venir : 1, 2 ou 3 points en jeu. La manche se joue en parallèle (style M1). Si tu trouves la question pariée : <strong>+1 + mise</strong>. Si tu rates : <strong>−1 − mise</strong>. Le pire score est éliminé.
                </p>
                <span class="card-badge">Stratégie + paris</span>
            </div>
            <div class="card-visual" data-num="09">
                <span class="card-icon">🎲</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <span class="card-number">10 — Manche 3 finale</span>
                <h2 class="card-title">Face-à-face au buzz</h2>
                <p class="card-text">
                    Les 2 derniers s'affrontent. Comme en M2, choix de catégories et pari secret. Puis 7 questions au <strong>buzz partagé</strong> : 3s pour lire, 3s pour voir les options, puis 12s pour buzzer le premier. Si tu réponds juste : <strong>+1</strong>. Si tu rates : <strong>−1</strong>, et ton adversaire voit ta mauvaise réponse en rouge avant son tour de buzzer. Le premier à <strong>8 points</strong> remporte le titre, sinon meilleur score après 7 questions. Égalité : mort subite.
                </p>
                <span class="card-badge">Buzz · Cible 8 pts</span>
            </div>
            <div class="card-visual" data-num="10">
                <span class="card-icon">🏆</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <span class="card-number">11 — ELO Championnat</span>
                <h2 class="card-title">Récompenses et classement</h2>
                <p class="card-text">
                    Chaque championnat impacte votre ELO selon votre rang final :
                    <br>🥇 <strong>1er (Champion)</strong> : <span style="color:#4caf50;">+50 ELO</span>
                    <br>🥈 <strong>2ème (Finaliste)</strong> : <span style="color:#4caf50;">+30 ELO</span>
                    <br>🥉 <strong>3ème (Éliminé M2)</strong> : <span style="color:#999;">0 ELO</span>
                    <br>4ème <strong>(Éliminé M1)</strong> : <span style="color:#e54848;">−20 ELO</span>
                    <br><br>Ces points s'ajoutent à votre ELO global. <strong>Un seul classement</strong> regroupe toutes les parties (1v1, championnat, etc.).
                </p>
                <span class="card-badge">Système ELO</span>
            </div>
            <div class="card-visual" data-num="11">
                <span class="card-icon">📊</span>
            </div>
        </div>

    </section>

</body>
</html>
