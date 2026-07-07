<?php
// ============================================================
// LOGGING — stocke les erreurs dans /logs/app.log
// ============================================================
define('LOG_FILE', __DIR__ . '/../logs/app.log');

function logError(string $message): void {
    $date = date('Y-m-d H:i:s');
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $line = "[$date] [IP: $ip] ERROR : $message" . PHP_EOL;

    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// ============================================================
// CONNEXION BASE DE DONNÉES
// ============================================================
$servername = "sql301.infinityfree.com";
$username   = "if0_42332073";
$password   = "znrXdgDKtTcGHfM";
$dbname     = "if0_42332073_qpc";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    logError("DB connection failed: " . $conn->connect_error);
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}

$conn->set_charset("utf8mb4");