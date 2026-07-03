<?php
require_once __DIR__ . '/csrf.php';
require "db.php"; // logError() défini ici

// ============================================================
// Protection CSRF
// ============================================================
if (!csrf_verify()) {
    logError("login.php - CSRF token invalide");
    $_SESSION['error'] = "Session expirée, veuillez réessayer.";
    header("Location: connexion.php");
    exit;
}

// ============================================================
// Vérification des champs
// ============================================================
if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = "Veuillez remplir tous les champs.";
    header("Location: connexion.php");
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// ============================================================
// Recherche de l'utilisateur
// ============================================================
$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");

if (!$stmt) {
    logError("login.php - prepare() failed: " . $conn->error);
    $_SESSION['error'] = "Une erreur est survenue. Réessayez.";
    header("Location: connexion.php");
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// ============================================================
// Vérification du mot de passe
// ============================================================
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {

        // ✅ Connexion réussie
        session_regenerate_id(true);
        $_SESSION['user_id']          = $user['id'];
        $_SESSION['session_start']    = time(); // pour calculer time_on_site au logout

        // --- funnel_analytics : login réussi ---
        $f = $conn->prepare("INSERT INTO funnel_analytics (user_id, etape) VALUES (?, 'login_reussi')");
        if ($f) {
            $f->bind_param("i", $user['id']);
            $f->execute();
        }

        // --- session_analytics : début de session ---
        $hour       = (int) date('G');       // heure 0-23
        $dayOfWeek  = (int) date('N') - 1;   // 0=lundi, 6=dimanche
        $sa = $conn->prepare(
            "INSERT INTO session_analytics (user_id, hour_of_day, day_of_week)
             VALUES (?, ?, ?)"
        );
        if ($sa) {
            $sa->bind_param("iii", $user['id'], $hour, $dayOfWeek);
            $sa->execute();
            // On stocke l'ID de la ligne pour la mettre à jour au logout
            $_SESSION['analytics_id'] = $conn->insert_id;
        }

        header("Location: dashboard.php");
        exit;

    } else {

        // ❌ Mauvais mot de passe
        logError("login.php - Tentative échouée pour username: " . $username);

        // --- funnel_analytics : login échoué ---
        $f = $conn->prepare("INSERT INTO funnel_analytics (user_id, etape) VALUES (NULL, 'login_echoue')");
        if ($f) $f->execute();

        $_SESSION['error'] = "Identifiants incorrects.";
        header("Location: connexion.php");
        exit;
    }

} else {

    // ❌ Utilisateur introuvable
    logError("login.php - Utilisateur introuvable: " . $username);

    // --- funnel_analytics : login échoué ---
    $f = $conn->prepare("INSERT INTO funnel_analytics (user_id, etape) VALUES (NULL, 'login_echoue')");
    if ($f) $f->execute();

    $_SESSION['error'] = "Identifiants incorrects.";
    header("Location: connexion.php");
    exit;
}
?>
