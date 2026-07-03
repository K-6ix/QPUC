<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ── Protection CSRF ─────────────────────────────────────────
if (!csrf_verify()) {
    logError("update_profile.php - CSRF token invalide pour user_id " . ($_SESSION['user_id'] ?? 'inconnu'));
    $_SESSION['error'] = "Session expirée, veuillez réessayer.";
    header("Location: dashboard.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$pays     = trim($_POST['pays']     ?? '');
$age      = !empty($_POST['age']) ? (int)$_POST['age'] : null;

// ── Validation ──────────────────────────────────────────────
if (empty($username) || empty($email)) {
    $_SESSION['error'] = "Les champs nom et email sont obligatoires.";
    header("Location: dashboard.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Adresse email invalide.";
    header("Location: dashboard.php");
    exit;
}

if ($age !== null && ($age < 8 || $age > 120)) {
    $_SESSION['error'] = "Âge invalide.";
    header("Location: dashboard.php");
    exit;
}

// ── Upload photo de profil ───────────────────────────────────
$profile_pic = null;

if (!empty($_FILES['profile_pic']['name'])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo        = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType     = finfo_file($finfo, $_FILES['profile_pic']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        logError("update_profile.php - MIME refusé '$mimeType' pour user_id $user_id");
        $_SESSION['error'] = "Type de fichier non autorisé. Utilisez JPG, PNG, WEBP ou GIF.";
        header("Location: dashboard.php");
        exit;
    }

    if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = "L'image ne doit pas dépasser 2 Mo.";
        header("Location: dashboard.php");
        exit;
    }

    $ext        = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
    $fileName   = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
    $targetDir  = "uploads/";
    $targetFile = $targetDir . $fileName;

    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
        $profile_pic = $fileName;
    } else {
        logError("update_profile.php - move_uploaded_file() échoué pour user_id $user_id");
        $_SESSION['error'] = "Erreur lors de l'upload de l'image.";
        header("Location: dashboard.php");
        exit;
    }
}

// ── Mise à jour en BDD ───────────────────────────────────────
// On utilise une seule requête propre selon si photo uploadée ou non
if ($profile_pic) {
    // Avec photo : username, email, pays, age, profile_pic, id
    // Types      : s       , s    , s   , i  , s          , i
    $stmt = $conn->prepare(
        "UPDATE users SET username=?, email=?, pays=?, age=?, profile_pic=? WHERE id=?"
    );
    if (!$stmt) {
        logError("update_profile.php - prepare() failed: " . $conn->error);
        $_SESSION['error'] = "Une erreur est survenue.";
        header("Location: dashboard.php");
        exit;
    }
    $stmt->bind_param("sssisi", $username, $email, $pays, $age, $profile_pic, $user_id);
} else {
    // Sans photo : username, email, pays, age, id
    // Types      : s       , s    , s   , i  , i
    $stmt = $conn->prepare(
        "UPDATE users SET username=?, email=?, pays=?, age=? WHERE id=?"
    );
    if (!$stmt) {
        logError("update_profile.php - prepare() failed: " . $conn->error);
        $_SESSION['error'] = "Une erreur est survenue.";
        header("Location: dashboard.php");
        exit;
    }
    $stmt->bind_param("sssii", $username, $email, $pays, $age, $user_id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Profil mis à jour avec succès.";
} else {
    logError("update_profile.php - execute() failed pour user_id $user_id: " . $stmt->error);
    $_SESSION['error'] = "Erreur lors de la mise à jour.";
}

header("Location: dashboard.php");
exit;
?>
