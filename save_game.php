<?php
// ============================================================
// save_game.php — Sauvegarde de fin de partie (mode entraînement)
//
// Met à jour : game_sessions, player_stats, users.score_total
// ============================================================
require_once __DIR__ . '/csrf.php';
require "db.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Non authentifié"]);
    exit;
}
$uid = (int) $_SESSION['user_id'];

// ── Protection CSRF (token via en-tête X-CSRF-Token) ──
if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "Requête non autorisée (CSRF)"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Payload invalide"]);
    exit;
}

// ── Normalisation ────────────────────────────────────────────
$mode = in_array($data['mode'] ?? '', ['solo','tournoi','rapidite','buzz'], true)
    ? $data['mode'] : 'solo';
$diff = in_array($data['difficulty'] ?? '', ['facile','moyen','difficile'], true)
    ? $data['difficulty'] : 'moyen';

$score   = max(0, (int)($data['score']           ?? 0));
$correct = max(0, (int)($data['correct']         ?? 0));
$total_q = max(1, (int)($data['total_questions'] ?? 1));
$wrong   = max(0, $total_q - $correct);
$streak  = max(0, (int)($data['best_streak']     ?? 0));
$avg_t   = max(0, (int)($data['avg_time']        ?? 0));
$aband   = !empty($data['abandoned']);
$status  = $aband ? 'abandoned' : 'finished';

$tp = 0;
if (!empty($data['answer_times']) && is_array($data['answer_times'])) {
    foreach ($data['answer_times'] as $t) $tp += max(0, (int)$t);
} else {
    $tp = $avg_t * $total_q;
}

try {
    // ── 1. game_sessions ─────────────────────────────────────
    $s1 = $conn->prepare(
        "INSERT INTO game_sessions
            (user_id, score, status, game_mode, difficulty,
             ended_at, time_played, total_questions,
             correct_answers, wrong_answers, longest_streak)
         VALUES (?,?,?,?,?,NOW(),?,?,?,?,?)"
    );
    $s1->bind_param("iisssiiiii",
        $uid, $score, $status, $mode, $diff,
        $tp, $total_q, $correct, $wrong, $streak
    );
    $s1->execute();
    $sid = $s1->insert_id;

    // ── 2. player_stats ──────────────────────────────────────
    $win = (!$aband && $correct >= ceil($total_q / 2)) ? 1 : 0;
    $def = 1 - $win;
    $fin = $aband ? 0 : 1;
    $abn = $aband ? 1 : 0;

    // Vérifier si une ligne existe déjà
    $chk = $conn->prepare("SELECT id FROM player_stats WHERE user_id = ?");
    $chk->bind_param("i", $uid);
    $chk->execute();
    $exists = $chk->get_result()->num_rows > 0;

    if (!$exists) {
        // Première partie : INSERT
        $wr    = $win ? 100.0 : 0.0;
        $avg_s = (float) $score;
        $avg_a = (float) $avg_t;
        $ins = $conn->prepare(
            "INSERT INTO player_stats
                (user_id, total_games, games_finished, games_abandoned,
                 victories, defeats, best_score, average_score, winrate,
                 total_correct, total_wrong, best_streak,
                 total_time_played, average_time_answer)
             VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param("iiiiiiddiiiid",
            $uid,
            $fin, $abn, $win, $def,
            $score, $avg_s, $wr,
            $correct, $wrong, $streak,
            $tp, $avg_a
        );
        $ins->execute();
    } else {
        // Parties suivantes : UPDATE incrémental
        // Note : MySQL évalue les SET de gauche à droite,
        // donc total_games, total_correct, etc. ont déjà leur
        // nouvelle valeur quand average_score et winrate les lisent.
        $upd = $conn->prepare(
            "UPDATE player_stats SET
                total_games       = total_games + 1,
                games_finished    = games_finished + ?,
                games_abandoned   = games_abandoned + ?,
                victories         = victories + ?,
                defeats           = defeats + ?,
                best_score        = GREATEST(best_score, ?),
                total_correct     = total_correct + ?,
                total_wrong       = total_wrong + ?,
                best_streak       = GREATEST(best_streak, ?),
                total_time_played = total_time_played + ?,
                average_score     = ((average_score * (total_games - 1)) + ?) / total_games,
                winrate           = (victories / total_games) * 100,
                average_time_answer = CASE
                    WHEN (total_correct + total_wrong) > 0
                    THEN total_time_played / (total_correct + total_wrong)
                    ELSE 0
                END
             WHERE user_id = ?"
        );
        $upd->bind_param("iiiiiiiiiii",
            $fin, $abn, $win, $def,
            $score,
            $correct, $wrong, $streak, $tp,
            $score,
            $uid
        );
        $upd->execute();
    }

    // ── 3. player_stats_by_category (radar chart) ──────────────
    $cat_results = $data['category_results'] ?? [];
    if (is_array($cat_results) && count($cat_results) > 0) {
        // Agréger par catégorie
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

    // ── 4. users.score_total ─────────────────────────────────
    $u = $conn->prepare("UPDATE users SET score_total = score_total + ? WHERE id = ?");
    $u->bind_param("ii", $score, $uid);
    $u->execute();

    echo json_encode(["ok" => true, "session_id" => $sid, "score" => $score]);

} catch (Exception $e) {
    logError("save_game error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Erreur serveur"]);
}
