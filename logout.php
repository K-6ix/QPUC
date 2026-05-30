<?php
session_start();
require "db.php"; // logError() défini ici

// ============================================================
// Calcul du temps passé sur le site
// ============================================================
if (isset($_SESSION['user_id']) && isset($_SESSION['analytics_id'])) {

    $user_id      = $_SESSION['user_id'];
    $analytics_id = $_SESSION['analytics_id'];
    $session_start = $_SESSION['session_start'] ?? time();
    $time_on_site  = time() - $session_start;  // secondes passées sur le site

    // Mettre à jour session_analytics avec l'heure de déconnexion et le temps total
    $sa = $conn->prepare(
        "UPDATE session_analytics
         SET disconnected_at = NOW(),
             time_on_site    = ?
         WHERE id = ? AND user_id = ?"
    );

    if ($sa) {
        $sa->bind_param("iii", $time_on_site, $analytics_id, $user_id);
        $sa->execute();
    } else {
        logError("logout.php - prepare() UPDATE session_analytics failed: " . $conn->error);
    }
}

// ============================================================
// Destruction propre de la session
// ============================================================
$_SESSION = [];

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
