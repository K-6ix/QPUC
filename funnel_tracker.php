<?php
/**
 * funnel_tracker.php
 * À inclure en haut de chaque page pour tracker les visites dans funnel_analytics.
 *
 * Usage :
 *   require_once "funnel_tracker.php";
 *   trackFunnel('visite_accueil');
 *
 * Étapes disponibles (ENUM dans la table) :
 *   visite_accueil | visite_login | visite_regles | visite_apropos
 *   login_reussi | login_echoue | inscription_reussie
 *   lance_partie | partie_terminee | partie_abandonnee
 */

if (!function_exists('trackFunnel')) {
    function trackFunnel(string $etape): void {
        global $conn;
        if (!$conn) return;

        $user_id = $_SESSION['user_id'] ?? null;

        $stmt = $conn->prepare(
            "INSERT INTO funnel_analytics (user_id, etape) VALUES (?, ?)"
        );
        if (!$stmt) return;

        $stmt->bind_param("is", $user_id, $etape);
        $stmt->execute();
    }
}
?>