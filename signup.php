<?php
session_start();
require "db.php";

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// ✅ Validation des champs
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

// ✅ Vérifier si l'utilisateur ou l'email existe déjà
$check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['error'] = "Ce nom d'utilisateur ou cet email est déjà utilisé.";
    header("Location: connexion.php");
    exit;
}

// ✅ Hash du mot de passe
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// ✅ Insertion
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['success'] = "Compte créé avec succès !";
    header("Location: dashboard.php");
    exit;
} else {
    error_log("Erreur signup : " . $stmt->error);
    $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer.";
    header("Location: connexion.php");
    exit;
}
?>
