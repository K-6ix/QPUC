<?php
// ════════════════════════════════════════════════════════════════
// qpc_hmac.php — Vérification des enveloppes signées (relais navigateur)
//
// POURQUOI CE FICHIER EXISTE :
// En production, l'hébergeur PHP (InfinityFree) bloque les requêtes
// venant directement du serveur Node (système anti-bot). Les résultats
// de partie transitent donc par le NAVIGATEUR des joueurs :
//
//   Node (Koyeb) ──signe──▶ enveloppe {payload, signature}
//        │ Socket.io
//        ▼
//   Navigateur joueur ──POST──▶ save_elo.php / save_championship.php
//        (simple facteur : ne peut PAS modifier le contenu)
//
// Le PHP vérifie ici que l'enveloppe :
//  1. porte une signature HMAC-SHA256 valide (clé partagée QPC_SERVER_KEY)
//  2. est fraîche (émise il y a < 10 minutes → anti-rejeu tardif)
//  3. n'a pas déjà été traitée (table processed_matches → idempotence :
//     les 2 ou 4 joueurs livrent la même enveloppe, une seule compte)
//
// Utilisation dans un endpoint (APRÈS require db.php) :
//   require __DIR__ . "/qpc_hmac.php";
//   $data = qpc_verify_envelope($conn);   // exit automatique si invalide
// ════════════════════════════════════════════════════════════════
require_once __DIR__ . '/qpc_secret.php';

function qpc_verify_envelope(mysqli $conn): array {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    $payload   = $body['payload']   ?? null;
    $signature = $body['signature'] ?? null;

    if (!is_string($payload) || !is_string($signature)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Enveloppe invalide"]);
        exit;
    }

    // ── 1. Signature (comparaison en temps constant, anti-timing) ──
    $expected = hash_hmac('sha256', $payload, QPC_SERVER_KEY);
    if (!hash_equals($expected, $signature)) {
        http_response_code(403);
        echo json_encode(["ok" => false, "error" => "Signature invalide"]);
        exit;
    }

    $data = json_decode($payload, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Payload invalide"]);
        exit;
    }

    // ── 2. Fraîcheur (issued_at est en millisecondes côté Node) ──
    $issued = (int) ($data['issued_at'] ?? 0);
    if (abs(time() * 1000 - $issued) > 10 * 60 * 1000) {
        http_response_code(403);
        echo json_encode(["ok" => false, "error" => "Enveloppe expirée"]);
        exit;
    }

    // ── 3. Déduplication (idempotence) ──
    // La même enveloppe = le même payload = le même sha1. Le premier arrivé
    // insère la référence ; les suivants tombent sur la contrainte UNIQUE
    // et repartent avec un 200 "déjà traité" sans toucher aux stats.
    $ref  = sha1($payload);
    $dup  = false;
    $stmt = $conn->prepare("INSERT INTO processed_matches (match_ref) VALUES (?)");
    $stmt->bind_param("s", $ref);
    try {
        if (!$stmt->execute()) {
            $dup = ($stmt->errno === 1062);
            if (!$dup) {
                http_response_code(500);
                echo json_encode(["ok" => false, "error" => "Erreur interne"]);
                exit;
            }
        }
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            $dup = true;
        } else {
            http_response_code(500);
            echo json_encode(["ok" => false, "error" => "Erreur interne"]);
            exit;
        }
    }
    $stmt->close();

    if ($dup) {
        echo json_encode(["ok" => true, "already_processed" => true]);
        exit;
    }

    return $data;
}
