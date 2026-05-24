<?php
session_start();
require "db.php";

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

// ── Validation ──────────────────────────────────────────────
if (empty($username) || empty($email) || empty($password)) {
    $_SESSION['error'] = "Tous les champs sont obligatoires.";
    header("Location: connexion.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Adresse email invalide.";
    header("Location: connexion.php");
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = "Le mot de passe doit contenir au moins 6 caractères.";
    header("Location: connexion.php");
    exit;
}

// ── Vérifier si username/email déjà utilisés ─────────────────
$check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
if (!$check) {
    logError("signup.php - prepare() check failed: " . $conn->error);
    $_SESSION['error'] = "Une erreur est survenue. Réessayez.";
    header("Location: connexion.php");
    exit;
}
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['error'] = "Ce nom d'utilisateur ou cet email est déjà utilisé.";
    header("Location: connexion.php");
    exit;
}

// ── Insertion utilisateur ────────────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
if (!$stmt) {
    logError("signup.php - prepare() insert failed: " . $conn->error);
    $_SESSION['error'] = "Une erreur est survenue. Réessayez.";
    header("Location: connexion.php");
    exit;
}
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if (!$stmt->execute()) {
    logError("signup.php - execute() failed pour '$username': " . $stmt->error);
    $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer.";
    header("Location: connexion.php");
    exit;
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
