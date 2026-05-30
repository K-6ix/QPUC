<?php
// ============================================================
// save_elo.php — Persistance ELO + stats après un duel 1v1
// Appelé par server.js (Node) via HTTP POST après chaque endGame.
//
// Met à jour pour CHAQUE joueur :
//  1. player_stats.elo
//  2. player_stats (total_games, victories, defeats, etc.)
//  3. game_sessions (historique de la partie)
//  4. users.score_total
// ============================================================
require "db.php";

header("Content-Type: application/json; charset=utf-8");

$SERVER_KEY = 'qpc_server_2026';

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Payload invalide"]);
    exit;
}

if (($data['server_key'] ?? '') !== $SERVER_KEY) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Accès refusé"]);
    exit;
}

$game_results = $data['game_results'] ?? [];
if (!is_array($game_results) || count($game_results) === 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Aucune donnée"]);
    exit;
}

try {
    $results = [];

    foreach ($game_results as $gr) {
        $uid      = (int) ($gr['user_id']  ?? 0);
        $new_elo  = max(0, (int) ($gr['new_elo'] ?? 1200));
        $score    = max(0, (int) ($gr['score']   ?? 0));
        $correct  = max(0, (int) ($gr['correct'] ?? 0));
        $wrong    = max(0, (int) ($gr['wrong']   ?? 0));
        $total_q  = max(1, (int) ($gr['total_q'] ?? 10));
        $is_win   = !empty($gr['is_winner']) ? 1 : 0;
        $is_loss  = 1 - $is_win;

        if ($uid <= 0) continue;

        // ── 1. game_sessions ─────────────────────────────────
        $mode   = 'tournoi'; // 1v1 = mode tournoi dans l'enum
        $status = 'finished';
        $diff   = 'moyen';
        $s1 = $conn->prepare(
            "INSERT INTO game_sessions
                (user_id, score, status, game_mode, difficulty,
                 ended_at, total_questions, correct_answers, wrong_answers)
             VALUES (?,?,?,?,?,NOW(),?,?,?)"
        );
        $s1->bind_param("iisssiii",
            $uid, $score, $status, $mode, $diff,
            $total_q, $correct, $wrong
        );
        $s1->execute();

        // ── 2. player_stats (ELO + compteurs) ────────────────
        $chk = $conn->prepare("SELECT id FROM player_stats WHERE user_id = ?");
        $chk->bind_param("i", $uid);
        $chk->execute();
        $exists = $chk->get_result()->num_rows > 0;

        if (!$exists) {
            $wr = $is_win ? 100.0 : 0.0;
            $avg_s = (float) $score;
            $ins = $conn->prepare(
                "INSERT INTO player_stats
                    (user_id, elo, total_games, games_finished,
                     victories, defeats, best_score, average_score, winrate,
                     total_correct, total_wrong)
                 VALUES (?, ?, 1, 1, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param("iiiiiddii",
                $uid, $new_elo,
                $is_win, $is_loss,
                $score, $avg_s, $wr,
                $correct, $wrong
            );
            $ins->execute();
        } else {
            $upd = $conn->prepare(
                "UPDATE player_stats SET
                    elo             = ?,
                    total_games     = total_games + 1,
                    games_finished  = games_finished + 1,
                    victories       = victories + ?,
                    defeats         = defeats + ?,
                    best_score      = GREATEST(best_score, ?),
                    total_correct   = total_correct + ?,
                    total_wrong     = total_wrong + ?,
                    average_score   = ((average_score * (total_games - 1)) + ?) / total_games,
                    winrate         = (victories / total_games) * 100
                 WHERE user_id = ?"
            );
            $upd->bind_param("iiiiiiii",
                $new_elo,
                $is_win, $is_loss,
                $score,
                $correct, $wrong,
                $score,
                $uid
            );
            $upd->execute();
        }

        // ── 3. player_stats_by_category (radar) ─────────────────
        $cat_results = $gr['category_results'] ?? [];
        if (is_array($cat_results) && count($cat_results) > 0) {
            $by_cat = [];
            foreach ($cat_results as $cr) {
                $cid = (int) ($cr['catId'] ?? 0);
                if ($cid <= 0) continue;
                if (!isset($by_cat[$cid])) $by_cat[$cid] = ['correct' => 0, 'wrong' => 0, 'total' => 0];
                $by_cat[$cid]['total']++;
                if (!empty($cr['correct'])) $by_cat[$cid]['correct']++;
                else $by_cat[$cid]['wrong']++;
            }

            $cat_stmt = $conn->prepare(
                "INSERT INTO player_stats_by_category
                    (user_id, category_id, total_questions, correct, wrong, success_rate)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    total_questions = total_questions + VALUES(total_questions),
                    correct         = correct + VALUES(correct),
                    wrong           = wrong + VALUES(wrong),
                    success_rate    = (correct / total_questions) * 100"
            );

            foreach ($by_cat as $cid => $stats) {
                $rate = $stats['total'] > 0
                    ? round($stats['correct'] / $stats['total'] * 100, 1)
                    : 0;
                $cat_stmt->bind_param("iiiiid",
                    $uid, $cid, $stats['total'], $stats['correct'], $stats['wrong'], $rate
                );
                $cat_stmt->execute();
            }
        }

        // ── 4. users.score_total ─────────────────────────────
        $u = $conn->prepare("UPDATE users SET score_total = score_total + ? WHERE id = ?");
        $u->bind_param("ii", $score, $uid);
        $u->execute();

        $results[] = ["user_id" => $uid, "elo" => $new_elo, "score" => $score, "win" => $is_win];
    }

    echo json_encode(["ok" => true, "updated" => $results]);

} catch (Exception $e) {
    logError("save_elo error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Erreur serveur"]);
}
