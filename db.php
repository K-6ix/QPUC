<?php
// ✅ Désactiver l'affichage des erreurs en production
// En développement local, tu peux remettre ces deux lignes :
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "qpcTest_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    // En prod : ne jamais afficher le détail de l'erreur à l'utilisateur
    error_log("Erreur de connexion : " . $conn->connect_error);
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}

$conn->set_charset("utf8mb4");
?>
