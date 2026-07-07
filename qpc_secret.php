<?php
// ════════════════════════════════════════════════════════════════
// qpc_secret.php — Clé secrète partagée avec le serveur Node
//
// Cette clé signe/vérifie les enveloppes de résultats (HMAC-SHA256).
// Elle doit être STRICTEMENT IDENTIQUE à la variable d'environnement
// SERVER_KEY configurée sur Koyeb (ou Render).
//
// ⚠️ EN PRODUCTION : remplacez la valeur par une clé longue et aléatoire.
//    Pour en générer une : https://www.php.net → ou simplement exécuter
//    en local :  php -r "echo bin2hex(random_bytes(32));"
//    → 64 caractères hexadécimaux à coller ici ET dans SERVER_KEY sur Koyeb.
//
// Ce fichier ne doit JAMAIS être commité sur un GitHub public avec la
// vraie clé (mettez-le dans .gitignore, gardez un modèle avec placeholder).
// ════════════════════════════════════════════════════════════════
define('QPC_SERVER_KEY', '03ec81ec1225f4b48fdc19c01a2175df'); // ← À CHANGER EN PROD
