<?php
session_start();
require "db.php";

// ✅ Vérification que les champs ne sont pas vides
if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = "Veuillez remplir tous les champs.";
    header("Location: connexion.php");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        // ✅ Régénérer l'ID de session pour éviter les attaques de fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        // ✅ Message générique (ne pas indiquer si c'est le mot de passe ou l'username qui est faux)
        $_SESSION['error'] = "Identifiants incorrects.";
        header("Location: connexion.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Identifiants incorrects.";
    header("Location: connexion.php");
    exit;
}
?>
