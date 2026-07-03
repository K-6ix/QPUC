<?php
// ════════════════════════════════════════════════════════════════
//  csrf.php — Sécurité centralisée : session durcie + protection CSRF
//
//  À inclure EN PREMIER dans chaque page (remplace session_start()) :
//      require_once __DIR__ . '/csrf.php';
//  Depuis un sous-dossier (championship/) :
//      require_once __DIR__ . '/../csrf.php';
//
//  Ce que ça fait :
//   1. Durcit le cookie de session :
//        - HttpOnly  → le cookie n'est PAS lisible en JavaScript (anti-vol via XSS)
//        - SameSite=Lax → le navigateur n'envoie pas le cookie sur les
//                         requêtes POST cross-site (bloque le CSRF en amont)
//        - Secure    → activé automatiquement si on est en HTTPS (prod),
//                      désactivé en local (http://localhost) pour ne rien casser
//   2. Démarre la session proprement (une seule fois)
//   3. Fournit les helpers CSRF : csrf_token(), csrf_field(), csrf_verify()
// ════════════════════════════════════════════════════════════════

// ── 1. Cookie de session durci (AVANT session_start) ──────────────
if (session_status() === PHP_SESSION_NONE) {

    $secure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['SERVER_PORT'] ?? '') == 443)
    );

    // PHP 7.3+ : tableau d'options (permet SameSite)
    session_set_cookie_params([
        'lifetime' => 0,          // cookie de session (expire à la fermeture du navigateur)
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,    // true seulement en HTTPS
        'httponly' => true,       // inaccessible au JS
        'samesite' => 'Lax',      // barrière anti-CSRF côté navigateur
    ]);

    session_start();
}

// ── 1b. En-têtes de sécurité (appliqués à toutes les pages) ───────
//   X-Frame-Options    → interdit d'afficher le site dans une iframe
//                        d'un autre domaine (anti-clickjacking)
//   X-Content-Type-... → empêche le navigateur de "deviner" le type MIME
//   Referrer-Policy    → ne transmet pas tes URLs internes à l'extérieur
//   (CSP volontairement NON ajoutée : casserait les scripts/styles inline
//    du projet — à faire dans une passe dédiée avec tests)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

// ── 2. Helpers CSRF ───────────────────────────────────────────────

/**
 * Retourne le token CSRF de la session (le crée à la première demande).
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renvoie le champ caché à insérer dans un <form>.
 *   echo csrf_field();
 */
function csrf_field(): string {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

/**
 * Vérifie le token sur une requête POST.
 * Accepte le token soit via le champ POST `csrf_token` (formulaires),
 * soit via l'en-tête `X-CSRF-Token` (appels fetch/AJAX).
 *
 * @return bool  true si la requête est légitime (ou si ce n'est pas un POST)
 */
function csrf_verify(): bool {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return true; // rien à vérifier hors POST
    }

    $sent = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';

    return !empty($_SESSION['csrf_token'])
        && is_string($sent)
        && hash_equals($_SESSION['csrf_token'], $sent); // comparaison anti-timing
}
