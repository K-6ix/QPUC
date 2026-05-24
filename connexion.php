<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --bg-color: #000000;
            --gold-light: #fcf6ba;
            --gold-base: #d4af37; 
            --gold-dark: #8a6e2f;
            --text-color: #ffffff;
            --metallic-gradient: linear-gradient(to right, var(--gold-dark), var(--gold-base) 30%, var(--gold-light) 50%, var(--gold-base) 70%, var(--gold-dark));
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #000000;
        }

        /* ✅ Message d'erreur / succès */
        .flash-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            z-index: 9999;
            animation: fadeOut 4s forwards;
        }
        .flash-message.error {
            background: #ff4444;
            color: #fff;
            border: 1px solid #cc0000;
        }
        .flash-message.success {
            background: #28a745;
            color: #fff;
            border: 1px solid #1e7e34;
        }
        @keyframes fadeOut {
            0%   { opacity: 1; }
            70%  { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }

        .wrapper {
            position: relative;
            width: 750px;
            height: 450px;
            background: transparent;
            border: 2px solid #fcf6ba;
            box-shadow: 0 0 30px #d4af37;
            overflow: hidden;
        }
        .wrapper .form-box {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .wrapper .form-box.login {
            left: 0;
            padding: 0 60px 0 40px;   
        }
        .wrapper .form-box.login .animation {
            transform: translateX(0);
            transition: 1s;
            opacity: 1;
            transition-delay: calc(.2s * var(--j));
        }
        .wrapper.active .form-box.login .animation {
            transform: translateX(-120%);
            opacity: 0;
            filter: blur(10px);
            transition-delay: calc(.2s * var(--i));
        }
        .wrapper .form-box.nouveau {
            right: 0;
            padding: 0 40px 0 60px;
        }
        .wrapper .form-box.nouveau .animation {
            transform: translateX(120%);
            opacity: 0;
            filter: blur(10px);
            transition: 1s ease;
            transition-delay: calc(.2s * var(--j)); 
        }
        .wrapper.active .form-box.nouveau .animation {
            transform: translateX(0);
            opacity: 1;
            filter: blur(0);
            transition-delay: calc(.2s * var(--i));
        }
        .form-box h2 {
            font-size: 32px;
            color: blanchedalmond;
            text-align: center;
        }
        .form-box .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            margin: 25px 0;
        }
        .input-box input {
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            outline: none;
            font-size: 16px;
            color: antiquewhite;
            font-weight: 600;
            border-bottom: 2px solid white;
            transition: 1s ease-in-out;
            padding-right: 25px;
        }
        .input-box label {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            font-size: 16px;
            color: white;
            pointer-events: none;
            transition: 1s ease-in-out;
        }
        .input-box input:focus,
        .input-box input:valid {
            border-bottom-color: #d4af37;
        }
        .input-box input:focus ~ label,
        .input-box input:valid ~ label {
            top: -5px;
            color: #d4af37;
            font-size: 13px;
        }
        .input-box i {
            position: absolute;
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            font-size: 18px;
            color: white;
            transition: 1s ease-in-out;
        }
        .input-box input:focus ~ i,
        .input-box input:valid ~ i {
            color: #d4af37;
        }
        .btn {
            position: relative;
            width: 100%;
            height: 45px;
            background: #d4af37;
            border: 2px solid #fcf6ba;
            outline: none;
            border-radius: 40px;
            cursor: pointer;
            font-size: 16px;
            color: blanchedalmond;
            font-weight: 600;
            transition: 0.5s ease-in-out;
        }
        .form-box .forget-link {
            font-size: 14.5px;
            color: white;
            text-align: center;
            margin: 20px 0 10px;
        }
        .forget-link p a {
            color: #fcf6ba;
            text-decoration: none;
            font-weight: 600;
        }
        .forget-link p a:hover {
            text-decoration: underline;
        }
        .wrapper .info-texte {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .wrapper .info-texte.login {
            right: 0;
            text-align: right;
            padding: 0 48px 60px 150px;
        }
        .wrapper .info-texte.login .animation {
            transform: translateX(0);
            transition: 1s ease-in-out;
            opacity: 1;
            filter: blur(0);
            transition-delay: calc(.2s * var(--j));
        }
        .wrapper.active .info-texte.login .animation {
            transform: translateX(120%);
            transition: 1s ease;
            filter: blur(10px);
            opacity: 0;
            transition-delay: calc(.2s * var(--i));
        }
        .wrapper .info-texte.nouveau {
            left: 0;
            text-align: left;
            padding: 0 150px 60px 50px;
            pointer-events: none;
        }
        .wrapper .info-texte.nouveau .animation {
            transform: translateX(-130%);
            transition: 1s ease-in-out;
            opacity: 0;
            filter: blur(10px);
            transition-delay: calc(.2s * var(--j));
        }
        .wrapper.active .info-texte.nouveau .animation {
            transform: translateX(0);
            opacity: 1;
            filter: blur(0);
            transition-delay: calc(.2s * var(--i));
        }
        .info-texte h1 {
            font-size: 40px;
            color: #000000;
        }
        .info-texte p {
            font-size: 20px;
            color: #000000;
        }
        .form-box .end-link {
            font-size: 14.5px;
            color: white;
            text-align: center;
            margin: 20px 0 10px;
        }
        .end-link a {
            color: #d4af37;
            text-decoration: none;
            font-weight: 600;
        }
        .end-link a:hover {
            text-decoration: underline;
        }
        .wrapper .bg-animate {
            position: absolute;
            top: -4px;
            right: 0;
            width: 850px;
            height: 600px;
            background: linear-gradient(45deg, #fcf6ba, #d4af37);
            background-size: 200% 200%;
            border-bottom: 3px solid white;
            transform: rotate(10deg) skewY(40deg);
            transform-origin: bottom right;
            transition: 2s ease-in-out;
            transition-delay: 1.6s;
            animation: gradientMove 5s ease-in-out infinite;
        }
        @keyframes gradientMove {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .wrapper.active .bg-animate {
            transform: rotate(0) skewY(0);
            transition-delay: 1s;
        }
        .wrapper .bg-animate2 {
            position: absolute;
            top: 100%;
            left: 250px;
            width: 850px;
            height: 700px;
            background: #000000;
            border-top: 3px solid white;
            transform: rotate(0) skewY(0);
            transform-origin: bottom left;
            transition: 2s ease-in-out;
            transition-delay: 0.5s;
        }
        .wrapper.active .bg-animate2 {
            transform: rotate(-11deg) skewY(-41deg);
            transition-delay: 2.3s;
        }
        .home-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            padding: 10px 20px;
            background: var(--gold-base);
            color: #000;
            border: 2px solid var(--gold-light);
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1000;
            transition: transform 0.2s, background 0.2s;
        }
        .home-btn:hover {
            transform: scale(1.05);
            background: var(--gold-dark);
            color: #fff;
        }
        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0px 1000px #000000 inset !important;
            -webkit-text-fill-color: #fcf6ba !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px #000000 inset !important;
            -webkit-text-fill-color: #d4af37 !important;
        }

        @media (max-width: 950px) {
            .wrapper {
                width: 90%;
                height: auto;
                flex-direction: column;
                border: none;
                box-shadow: none;
            }
            .wrapper .form-box,
            .wrapper .info-texte {
                position: relative;
                width: 100%;
                height: auto;
                padding: 30px 20px;
                text-align: center;
            }
            .wrapper .form-box.login,
            .wrapper .form-box.nouveau {
                padding: 20px;
            }
            .info-texte h1 { font-size: 28px; }
            .info-texte p  { font-size: 16px; }
            .form-box h2   { font-size: 26px; }
            .input-box     { margin: 15px 0; }
            .btn           { height: 50px; font-size: 16px; }
            .bg-animate, .bg-animate2 { display: none; }
        }
        @media (max-width: 500px) {
            body { padding: 10px; }
            .wrapper { width: 100%; }
            .input-box label { font-size: 14px; }
            .btn { font-size: 14px; }
            .home-btn { bottom: 10px; left: 10px; padding: 8px 15px; font-size: 14px; }
        }
    </style>
</head>
<body>

<?php
// ✅ Affichage des messages flash (erreurs / succès)
session_start();
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

<button class="home-btn" onclick="window.location.href='index.php'">Home</button>

<script>
    const wrapper      = document.querySelector(".wrapper");
    const nouveauLink  = document.querySelector(".nouv-link");
    const connexionLink = document.querySelector(".login-link");

    nouveauLink.onclick = () => wrapper.classList.add("active");
    connexionLink.onclick = () => wrapper.classList.remove("active");
</script>
</body>
</html>
