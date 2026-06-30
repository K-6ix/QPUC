<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- ════ ANTI-FLASH : applique le thème global avant le render ════ -->
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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ════════════════════════════════════════════════════════════
           TOKENS — Dark mode (par défaut)
        ═══════════════════════════════════════════════════════════ */
        :root {
            /* OR — identité, immuable */
            --gold-light: #fcf6ba;
            --gold-base:  #d4af37;
            --gold-dark:  #8a6e2f;
            --gold-glow:  rgba(212,175,55,0.35);
            --metallic:   linear-gradient(to right, var(--gold-dark), var(--gold-base) 30%, var(--gold-light) 50%, var(--gold-base) 70%, var(--gold-dark));

            /* SURFACES */
            --bg:        #000000;
            --bg2:       #0a0a0a;
            --surface:   #0d0d0d;

            /* INK */
            --text:      #ffffff;
            --text-2:    rgba(255,255,255,0.75);
            --text-3:    rgba(255,255,255,0.5);

            /* INPUTS */
            --input-border: rgba(255,255,255,0.6);
            --input-text:   #fcf6ba;

            /* DIVERS */
            --wrapper-shadow:    0 0 30px var(--gold-base);
            --wrapper-border:    var(--gold-light);
            --info-text-on-gold: #060606;
            --on-gold:           #060606;
            --autofill-bg:       #000000;
        }

        /* ════════════════════════════════════════════════════════════
           TOKENS — Light mode
        ═══════════════════════════════════════════════════════════ */
        html.light {
            --bg:       #f7f7f5;
            --bg2:      #ffffff;
            --surface:  #ffffff;

            --text:     #1a1a1a;
            --text-2:   rgba(10,10,10,0.7);
            --text-3:   rgba(10,10,10,0.5);

            --input-border: rgba(10,10,10,0.35);
            --input-text:   #8a6e2f;

            --wrapper-shadow:    0 10px 50px rgba(212,175,55,0.25), 0 4px 20px rgba(0,0,0,0.08);
            --wrapper-border:    var(--gold-base);
            --autofill-bg:       #ffffff;
        }

        /* Transition douce pendant le switch */
        .theme-transitioning,
        .theme-transitioning * {
            transition: background-color 0.25s ease,
                        border-color 0.25s ease,
                        color 0.25s ease !important;
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        html, body { font-family:'Montserrat', sans-serif; }

        body {
            display:flex;
            justify-content:center;
            align-items:center;
            min-height:100vh;
            background:var(--bg);
            color:var(--text);
            padding:20px;
            position:relative;
            overflow-x:hidden;
        }

        /* ═══ Flash messages ═══ */
        .flash-message {
            position:fixed;
            top:20px;
            left:50%;
            transform:translateX(-50%);
            padding:12px 24px;
            border-radius:8px;
            font-size:14px;
            font-weight:600;
            z-index:9999;
            animation:fadeOut 4s forwards;
            box-shadow:0 4px 20px rgba(0,0,0,0.2);
        }
        .flash-message.error   { background:#ff4444; color:#fff; border:1px solid #cc0000; }
        .flash-message.success { background:#28a745; color:#fff; border:1px solid #1e7e34; }
        @keyframes fadeOut {
            0%   { opacity:1; }
            70%  { opacity:1; }
            100% { opacity:0; visibility:hidden; }
        }

        /* ═══ Wrapper (desktop) ═══ */
        .wrapper {
            position:relative;
            width:750px;
            height:450px;
            background:var(--bg);
            border:2px solid var(--wrapper-border);
            box-shadow:var(--wrapper-shadow);
            overflow:hidden;
            border-radius:6px;
        }

        .wrapper .form-box {
            position:absolute;
            top:0;
            width:50%;
            height:100%;
            display:flex;
            flex-direction:column;
            justify-content:center;
        }
        .wrapper .form-box.login    { left:0;  padding:0 60px 0 40px; }
        .wrapper .form-box.nouveau  { right:0; padding:0 40px 0 60px; }

        .wrapper .form-box.login .animation {
            transform:translateX(0);
            transition:1s;
            opacity:1;
            transition-delay:calc(.2s * var(--j));
        }
        .wrapper.active .form-box.login .animation {
            transform:translateX(-120%);
            opacity:0;
            filter:blur(10px);
            transition-delay:calc(.2s * var(--i));
        }
        .wrapper .form-box.nouveau .animation {
            transform:translateX(120%);
            opacity:0;
            filter:blur(10px);
            transition:1s ease;
            transition-delay:calc(.2s * var(--j));
        }
        .wrapper.active .form-box.nouveau .animation {
            transform:translateX(0);
            opacity:1;
            filter:blur(0);
            transition-delay:calc(.2s * var(--i));
        }

        /* Titres formulaires */
        .form-box h2 {
            font-family:'Kanit', sans-serif;
            font-size:32px;
            font-weight:900;
            letter-spacing:2px;
            text-transform:uppercase;
            color:var(--text);
            text-align:center;
        }

        /* ═══ Inputs ═══ */
        .form-box .input-box {
            position:relative;
            width:100%;
            height:50px;
            margin:25px 0;
        }
        .input-box input {
            width:100%;
            height:100%;
            background:transparent;
            border:none;
            outline:none;
            font-family:'Montserrat', sans-serif;
            font-size:15px;
            color:var(--input-text);
            font-weight:600;
            border-bottom:2px solid var(--input-border);
            transition:border-color .4s ease-in-out;
            padding-right:25px;
        }
        .input-box label {
            position:absolute;
            top:50%;
            left:0;
            transform:translateY(-50%);
            font-family:'Montserrat', sans-serif;
            font-size:15px;
            color:var(--text-2);
            pointer-events:none;
            transition:.4s ease-in-out;
            letter-spacing:.5px;
        }
        .input-box input:focus,
        .input-box input:valid       { border-bottom-color:var(--gold-base); }
        .input-box input:focus ~ label,
        .input-box input:valid ~ label {
            top:-5px;
            color:var(--gold-base);
            font-size:12px;
            letter-spacing:1px;
        }
        .input-box i {
            position:absolute;
            top:50%;
            right:0;
            transform:translateY(-50%);
            font-size:18px;
            color:var(--text-2);
            transition:.4s ease-in-out;
        }
        .input-box input:focus ~ i,
        .input-box input:valid ~ i { color:var(--gold-base); }

        /* ═══ Bouton submit ═══ */
        .btn {
            position:relative;
            width:100%;
            height:48px;
            background:var(--metallic);
            border:2px solid var(--gold-light);
            outline:none;
            border-radius:40px;
            cursor:pointer;
            font-family:'Kanit', sans-serif;
            font-size:14px;
            color:var(--on-gold);
            font-weight:900;
            letter-spacing:3px;
            text-transform:uppercase;
            transition:transform .25s, box-shadow .25s;
            box-shadow:0 0 18px var(--gold-glow);
        }
        .btn:hover {
            transform:translateY(-2px);
            box-shadow:0 0 32px rgba(212,175,55,0.6);
        }
        .btn:active { transform:translateY(0); }

        /* ═══ End link ═══ */
        .form-box .end-link {
            font-size:13.5px;
            color:var(--text-2);
            text-align:center;
            margin:20px 0 10px;
            line-height:1.5;
        }
        .end-link a {
            color:var(--gold-base);
            text-decoration:none;
            font-weight:700;
        }
        .end-link a:hover { text-decoration:underline; }

        /* ═══ Info texte (panneau doré sur le côté) ═══ */
        .wrapper .info-texte {
            position:absolute;
            top:0;
            width:50%;
            height:100%;
            display:flex;
            flex-direction:column;
            justify-content:center;
        }
        .wrapper .info-texte.login {
            right:0;
            text-align:right;
            padding:0 48px 60px 150px;
        }
        .wrapper .info-texte.login .animation {
            transform:translateX(0);
            transition:1s ease-in-out;
            opacity:1;
            filter:blur(0);
            transition-delay:calc(.2s * var(--j));
        }
        .wrapper.active .info-texte.login .animation {
            transform:translateX(120%);
            transition:1s ease;
            filter:blur(10px);
            opacity:0;
            transition-delay:calc(.2s * var(--i));
        }
        .wrapper .info-texte.nouveau {
            left:0;
            text-align:left;
            padding:0 150px 60px 50px;
            pointer-events:none;
        }
        .wrapper .info-texte.nouveau .animation {
            transform:translateX(-130%);
            transition:1s ease-in-out;
            opacity:0;
            filter:blur(10px);
            transition-delay:calc(.2s * var(--j));
        }
        .wrapper.active .info-texte.nouveau .animation {
            transform:translateX(0);
            opacity:1;
            filter:blur(0);
            transition-delay:calc(.2s * var(--i));
        }
        .info-texte h1 {
            font-family:'Kanit', sans-serif;
            font-size:42px;
            font-weight:900;
            letter-spacing:2px;
            text-transform:uppercase;
            color:var(--info-text-on-gold);
            line-height:1.1;
        }
        .info-texte p {
            font-size:16px;
            color:var(--info-text-on-gold);
            margin-top:14px;
            line-height:1.5;
            font-weight:500;
        }

        /* ═══ Backgrounds animés dorés ═══ */
        .wrapper .bg-animate {
            position:absolute;
            top:-4px;
            right:0;
            width:850px;
            height:600px;
            background:linear-gradient(45deg, var(--gold-light), var(--gold-base));
            background-size:200% 200%;
            border-bottom:3px solid var(--wrapper-border);
            transform:rotate(10deg) skewY(40deg);
            transform-origin:bottom right;
            transition:2s ease-in-out;
            transition-delay:1.6s;
            animation:gradientMove 5s ease-in-out infinite;
        }
        @keyframes gradientMove {
            0%   { background-position:0% 50%; }
            50%  { background-position:100% 50%; }
            100% { background-position:0% 50%; }
        }
        .wrapper.active .bg-animate {
            transform:rotate(0) skewY(0);
            transition-delay:1s;
        }
        .wrapper .bg-animate2 {
            position:absolute;
            top:100%;
            left:250px;
            width:850px;
            height:700px;
            background:var(--bg);
            border-top:3px solid var(--wrapper-border);
            transform:rotate(0) skewY(0);
            transform-origin:bottom left;
            transition:2s ease-in-out;
            transition-delay:.5s;
        }
        .wrapper.active .bg-animate2 {
            transform:rotate(-11deg) skewY(-41deg);
            transition-delay:2.3s;
        }

        /* ═══ Home button ═══ */
        .home-btn {
            position:fixed;
            bottom:20px;
            left:20px;
            padding:10px 20px;
            background:var(--metallic);
            color:var(--on-gold);
            border:2px solid var(--gold-light);
            border-radius:24px;
            font-family:'Kanit', sans-serif;
            font-weight:900;
            font-size:12px;
            letter-spacing:2px;
            text-transform:uppercase;
            cursor:pointer;
            z-index:1000;
            transition:transform .2s, box-shadow .2s;
            box-shadow:0 0 18px var(--gold-glow);
        }
        .home-btn:hover {
            transform:scale(1.05) translateY(-2px);
            box-shadow:0 0 30px rgba(212,175,55,0.55);
        }

        /* ═══ Theme toggle (en haut à droite) ═══ */
        .theme-toggle {
            position:fixed;
            top:20px;
            right:20px;
            width:40px;
            height:40px;
            border-radius:50%;
            background:var(--surface);
            border:1px solid var(--gold-base);
            color:var(--text);
            cursor:pointer;
            z-index:1000;
            display:flex;
            align-items:center;
            justify-content:center;
            transition:border-color .25s, transform .2s, background .25s;
        }
        .theme-toggle:hover {
            border-color:var(--gold-light);
            background:rgba(212,175,55,0.1);
            transform:scale(1.05);
        }
        .theme-toggle:active { transform:scale(0.95); }
        .theme-toggle svg { width:16px; height:16px; }
        .theme-toggle .theme-moon { display:none; }
        .theme-toggle .theme-sun  { display:block; }
        html.light .theme-toggle .theme-moon { display:block; }
        html.light .theme-toggle .theme-sun  { display:none; }

        /* ═══ Autofill (Chrome) ═══ */
        input:-webkit-autofill {
            -webkit-box-shadow:0 0 0px 1000px var(--autofill-bg) inset !important;
            -webkit-text-fill-color:var(--input-text) !important;
            transition:background-color 5000s ease-in-out 0s;
        }
        input:-webkit-autofill:focus {
            -webkit-box-shadow:0 0 0px 1000px var(--autofill-bg) inset !important;
            -webkit-text-fill-color:var(--gold-base) !important;
        }

        /* ════════════════════════════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════════════════════════ */

        /* Tablette + : on garde le split mais wrapper s'adapte */
        @media (max-width: 950px) {
            body { padding:60px 20px; }

            .wrapper {
                width:100%;
                max-width:420px;
                height:auto;
                min-height:auto;
                flex-direction:column;
                border:1px solid var(--gold-base);
                box-shadow:0 10px 40px rgba(0,0,0,0.4);
                border-radius:14px;
                padding:32px 24px;
            }
            html.light .wrapper { box-shadow:0 10px 40px rgba(0,0,0,0.1); }

            /* Empile : login form / nouveau form (info-texte cachés sur mobile) */
            .wrapper .form-box,
            .wrapper .info-texte {
                position:relative;
                width:100%;
                height:auto;
                padding:0;
                text-align:center;
            }

            /* Désactive les transitions translateX qui poussent hors écran */
            .wrapper .form-box.login .animation,
            .wrapper .form-box.nouveau .animation,
            .wrapper .info-texte.login .animation,
            .wrapper .info-texte.nouveau .animation {
                transform:none !important;
                filter:none !important;
                opacity:1 !important;
                transition:opacity .3s ease !important;
                transition-delay:0s !important;
            }

            /* Au lieu de translateX → masquer/afficher selon .active */
            .wrapper .form-box.nouveau,
            .wrapper .info-texte.nouveau,
            .wrapper .info-texte.login { display:none; }

            .wrapper.active .form-box.login { display:none; }
            .wrapper.active .form-box.nouveau { display:flex; }

            /* Info-texte mobile (caché par défaut pour la simplicité) */
            .wrapper .info-texte { display:none; }

            /* Backgrounds animés cachés en mobile */
            .bg-animate, .bg-animate2 { display:none; }

            .form-box h2 { font-size:26px; letter-spacing:1.5px; margin-bottom:8px; }
            .input-box   { margin:20px 0; height:46px; }
            .input-box input, .input-box label { font-size:14px; }
            .btn         { height:46px; font-size:13px; letter-spacing:2.5px; }
            .end-link    { font-size:12.5px; margin:18px 0 6px; }
        }

        /* Mobile */
        @media (max-width: 500px) {
            body { padding:60px 12px; }
            .wrapper { padding:26px 20px; border-radius:12px; max-width:100%; }
            .form-box h2 { font-size:23px; }
            .input-box { margin:18px 0; height:44px; }
            .input-box input, .input-box label { font-size:13.5px; }
            .input-box i { font-size:16px; }
            .btn { height:44px; font-size:12px; letter-spacing:2px; }
            .end-link { font-size:12px; }
            .home-btn { bottom:12px; left:12px; padding:8px 16px; font-size:11px; }
            .theme-toggle { top:12px; right:12px; width:36px; height:36px; }
            .flash-message { padding:10px 18px; font-size:13px; top:14px; max-width:90%; }
        }

        /* Très petits écrans */
        @media (max-width: 360px) {
            .wrapper { padding:22px 16px; }
            .form-box h2 { font-size:20px; letter-spacing:1px; }
            .input-box { margin:16px 0; }
        }
    </style>
</head>
<body>

<?php
// ✅ Affichage des messages flash (erreurs / succès)
// session_start() est déjà appelé en haut du fichier
if (!empty($_SESSION['error'])): ?>
    <div class="flash-message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="flash-message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="wrapper">
    <span class="bg-animate"></span>
    <span class="bg-animate2"></span>

    <!-- FORMULAIRE LOGIN -->
    <div class="form-box login animation">
        <h2 style="--i:0; --j:18;" class="animation">Connexion</h2>
        <form action="login.php" method="POST">
            <div class="input-box animation" style="--i:1; --j:19;">
                <input type="text" name="username" placeholder="" required>
                <label>Username</label>
                <i class='bx bx-user bx-flip-horizontal'></i>
            </div>
            <div class="input-box animation" style="--i:2; --j:20;">
                <input type="password" name="password" required>
                <label>Password</label>
                <i class='bx bx-lock bx-flip-horizontal'></i>
            </div>
            <button type="submit" class="btn animation" style="--i:3; --j:21;">Login</button>
            <div class="end-link animation" style="--i:4; --j:22;">
                <p>
                    Vous n'avez pas de compte ?<br>
                    <a href="#" class="nouv-link">Créez-en un</a>
                </p>
            </div>
        </form>
    </div>

    <div class="info-texte login">
        <h1 style="--i:0; --j:20;" class="animation">Bienvenue</h1>
        <p style="--i:3; --j:21;" class="animation">Connectez-vous pour accéder à votre espace et jouer à Question pour un Champion !</p>
    </div>

    <!-- FORMULAIRE INSCRIPTION -->
    <div class="form-box nouveau">
        <h2 class="animation" style="--i:20; --j:0;">Nouveau ?</h2>
        <form action="signup.php" method="POST">
            <div class="input-box animation" style="--i:21; --j:1;">
                <input type="text" name="username" placeholder="" required>
                <label>Username</label>
                <i class='bx bx-user bx-flip-horizontal'></i>
            </div>
            <div class="input-box animation" style="--i:22; --j:2;">
                <input type="email" name="email" placeholder="" required>
                <label>Email</label>
                <i class='bx bx-envelope bx-flip-horizontal'></i>
            </div>
            <div class="input-box animation" style="--i:23; --j:3;">
                <input type="password" name="password" required>
                <label>Password</label>
                <i class='bx bx-lock bx-flip-horizontal'></i>
            </div>
            <button type="submit" class="btn animation" style="--i:24; --j:4;">Débuter</button>
            <div class="end-link animation" style="--i:25; --j:5;">
                <p>
                    Vous avez déjà un compte ?<br>
                    <a href="#" class="login-link">Connectez-vous</a>
                </p>
            </div>
        </form>
    </div>

    <div class="info-texte nouveau animation">
        <h1 class="animation" style="--i:16; --j:0;">Bienvenue</h1>
        <p class="animation" style="--i:17; --j:1;">Créez votre compte et rejoignez l'aventure Question pour un Champion !</p>
    </div>
</div>

<!-- Theme toggle (en haut à droite) -->
<button id="theme-toggle" class="theme-toggle" aria-label="Basculer le thème" type="button">
    <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
    </svg>
    <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
    </svg>
</button>

<button class="home-btn" onclick="window.location.href='index.php'">Home</button>

<script>
    /* ═══════════ Toggle login / inscription (existant) ═══════════ */
    const wrapper      = document.querySelector(".wrapper");
    const nouveauLink  = document.querySelector(".nouv-link");
    const connexionLink = document.querySelector(".login-link");

    nouveauLink.onclick = () => wrapper.classList.add("active");
    connexionLink.onclick = () => wrapper.classList.remove("active");

    /* ═══════════ Theme toggle ═══════════ */
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
</script>
</body>
</html>
