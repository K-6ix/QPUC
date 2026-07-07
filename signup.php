<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ── Redirection avec erreur (affichée inline sur connexion.php) ──
// error_form = 'signup' → connexion.php ouvre direct le panneau inscription
function redirect_error($msg) {
    $_SESSION['error']        = $msg;
    $_SESSION['error_form']   = 'signup';
    $_SESSION['old_username'] = trim($_POST['username'] ?? '');
    $_SESSION['old_email']    = trim($_POST['email'] ?? '');
    header("Location: connexion.php");
    exit;
}

// ── Protection CSRF ─────────────────────────────────────────
if (!csrf_verify()) {
    logError("signup.php - CSRF token invalide");
    redirect_error("Session expirée, veuillez réessayer.");
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

// ── Validation ──────────────────────────────────────────────
if (empty($username) || empty($email) || empty($password)) {
    redirect_error("Tous les champs sont obligatoires.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_error("Adresse email invalide.");
}

if (strlen($password) < 6) {
    redirect_error("Le mot de passe doit contenir au moins 6 caractères.");
}

// ── Vérifier si username/email déjà utilisés ─────────────────
$check = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
if (!$check) {
    logError("signup.php - prepare() check failed: " . $conn->error);
    redirect_error("Une erreur est survenue. Réessayez.");
}
$check->bind_param("ss", $username, $email);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    if (strcasecmp($existing['username'], $username) === 0) {
        redirect_error("Ce nom d'utilisateur est déjà pris.");
    }
    redirect_error("Un compte existe déjà avec cet email.");
}

// ── Insertion utilisateur ────────────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
if (!$stmt) {
    logError("signup.php - prepare() insert failed: " . $conn->error);
    redirect_error("Une erreur est survenue. Réessayez.");
}
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if (!$stmt->execute()) {
    logError("signup.php - execute() failed pour '$username': " . $stmt->error);
    redirect_error("Une erreur est survenue. Veuillez réessayer.");
}

$new_user_id = $conn->insert_id;

// ── Créer la ligne player_stats ──────────────────────────────
// On crée une ligne vide dès l'inscription pour éviter les LEFT JOIN vides plus tard
$ps = $conn->prepare("INSERT INTO player_stats (user_id) VALUES (?)");
if ($ps) {
    $ps->bind_param("i", $new_user_id);
    $ps->execute();
} else {
    logError("signup.php - prepare() player_stats failed: " . $conn->error);
}

// ── funnel_analytics : inscription réussie ───────────────────
$f = $conn->prepare("INSERT INTO funnel_analytics (user_id, etape) VALUES (?, 'inscription_reussie')");
if ($f) {
    $f->bind_param("i", $new_user_id);
    $f->execute();
}

// ── Session ──────────────────────────────────────────────────
session_regenerate_id(true);
$_SESSION['user_id'] = $new_user_id;

// session_analytics : début de session
$hour      = (int) date('G');
$dayOfWeek = (int) date('N') - 1;
$sa = $conn->prepare(
    "INSERT INTO session_analytics (user_id, hour_of_day, day_of_week) VALUES (?, ?, ?)"
);
if ($sa) {
    $sa->bind_param("iii", $new_user_id, $hour, $dayOfWeek);
    $sa->execute();
    $_SESSION['analytics_id']  = $conn->insert_id;
    $_SESSION['session_start'] = time();
}

$_SESSION['success'] = "Bienvenue " . htmlspecialchars($username) . " ! Votre compte a été créé.";
header("Location: dashboard.php");
exit;
?>
