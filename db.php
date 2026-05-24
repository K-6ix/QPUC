<?php
// ============================================================
// LOGGING — stocke les erreurs dans /logs/app.log
// Ce fichier est hors du dossier public, inaccessible depuis le web
// ============================================================
define('LOG_FILE', __DIR__ . '/../logs/app.log');

function logError(string $message): void {
    $date = date('Y-m-d H:i:s');
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $line = "[$date] [IP: $ip] ERROR : $message" . PHP_EOL;

    // Créer le dossier logs/ automatiquement s'il n'existe pas
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// ============================================================
// CONNEXION BASE DE DONNÉES
// ============================================================
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "qpcTest_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    logError("DB connection failed: " . $conn->connect_error);
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}

$conn->set_charset("utf8mb4");
?>
