<?php
// ═══════════════════════════════════════════════════════════
// get_recent_opponents.php
// ═══════════════════════════════════════════════════════════
// Retourne les N derniers adversaires distincts du user connecté.
// On corrèle les rows de game_sessions par ended_at proche (<=15s)
// et user_id différent — pas besoin de schéma dédié.
//
// Endpoint AJAX, appelé par lobby-friendly.js
// Réponse JSON : { ok:true, opponents:[{id, username, elo, last_played, mode}] }
// ═══════════════════════════════════════════════════════════
require_once __DIR__ . '/csrf.php';
require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['ok' => true, 'opponents' => []]);
    exit;
}

$mode  = ($_GET['mode'] ?? 'duel') === 'tournoi' ? 'championnat' : 'tournoi';
$limit = 5;

/*
 * On récupère jusqu'à 25 candidats via la self-join, puis on dédup
 * par opponent_id côté PHP en gardant la row la plus récente. Ça
 * évite un GROUP BY qui casserait sur MySQL strict.
 */
$sql = "
    SELECT
        u.id           AS opp_id,
        u.username     AS opp_name,
        COALESCE(ps.elo, 1200) AS opp_elo,
        gs2.ended_at   AS last_played
    FROM game_sessions gs1
    JOIN game_sessions gs2
      ON gs2.user_id != gs1.user_id
     AND gs2.game_mode = gs1.game_mode
     AND ABS(TIMESTAMPDIFF(SECOND, gs1.ended_at, gs2.ended_at)) <= 15
    JOIN users u ON u.id = gs2.user_id
    LEFT JOIN player_stats ps ON ps.user_id = u.id
    WHERE gs1.user_id  = ?
      AND gs1.game_mode = ?
      AND gs1.ended_at IS NOT NULL
    ORDER BY gs2.ended_at DESC
    LIMIT 25
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $mode);
    $stmt->execute();
    $res = $stmt->get_result();

    $seen = [];
    $opponents = [];
    while ($row = $res->fetch_assoc()) {
        $oid = (int) $row['opp_id'];
        if (isset($seen[$oid])) continue;   // on garde la 1re occurrence = la + récente
        $seen[$oid] = true;

        $opponents[] = [
            'id'          => $oid,
            'username'    => $row['opp_name'],
            'elo'         => (int) $row['opp_elo'],
            'last_played' => $row['last_played'],
            'mode'        => $mode,
        ];

        if (count($opponents) >= $limit) break;
    }

    echo json_encode(['ok' => true, 'opponents' => $opponents]);
} catch (Throwable $e) {
    // On log mais on renvoie une liste vide au front (UX > erreur)
    error_log('[get_recent_opponents] ' . $e->getMessage());
    echo json_encode(['ok' => true, 'opponents' => []]);
}
