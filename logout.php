<?php
session_start();

// ✅ Supprimer toutes les variables de session
$_SESSION = [];

// ✅ Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: connexion.php");
exit;
?>
