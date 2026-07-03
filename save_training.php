<?php
// ════════════════════════════════════════════════════════════════
// save_training.php
//
// Endpoint POST appelé en fin de session d'entraînement.
// Écrit STRICTEMENT dans training_sessions + training_stats.
// Ne touche JAMAIS game_sessions, player_stats, users.score_total
// ni aucune table compétitive — règle d'isolation training.
//
// Payload JSON attendu :
// {
//   "category_id":     int,
//   "score":           int,
//   "total_questions": int,
//   "correct":         int,
//   "best_streak":     int,
//   "avg_time":        int,            // secondes / question
//   "final_level":     "facile|moyen|difficile",
//   "abandoned":       bool,
//   "answer_times":    [int, ...]      // optionnel, pour calcul time_played
// }
//
// Réponse : { ok: true, session_id: int } | { ok: false, error: "..." }
// ════════════════════════════════════════════════════════════════
require_once __DIR__ . '/csrf.php';
require "db.php";

header("Content-Type: application/json; charset=utf-8");

// ── Auth gate ──
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

// ── Lecture payload ──
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Payload invalide"]);
    exit;
}

// ── Normalisation / validation ──
$cat_id  = (int)   ($data['category_id']     ?? 0);
$score   = max(0,  (int) ($data['score']           ?? 0));
$total_q = max(1,  (int) ($data['total_questions'] ?? 1));
$correct = max(0,  (int) ($data['correct']         ?? 0));
$correct = min($correct, $total_q);              // borne
$wrong   = $total_q - $correct;
$streak  = max(0,  (int) ($data['best_streak']     ?? 0));
$avg_t   = max(0,  (int) ($data['avg_time']        ?? 0));
$final_l = in_array($data['final_level'] ?? '', ['facile','moyen','difficile'], true)
    ? $data['final_level'] : 'moyen';
$aband   = !empty($data['abandoned']);
$status  = $aband ? 'abandoned' : 'finished';
$rate    = $total_q > 0 ? round(($correct / $total_q) * 100, 2) : 0.00;

// ── Temps total : préférer answer_times[] si fourni, sinon avg × total ──
$time_played = 0;
if (!empty($data['answer_times']) && is_array($data['answer_times'])) {
    foreach ($data['answer_times'] as $t) {
        $time_played += max(0, (int) $t);
    }
} else {
    $time_played = $avg_t * $total_q;
}

// ── Vérif catégorie valide ──
if ($cat_id <= 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "category_id manquant ou invalide"]);
    exit;
}

try {
    $conn->begin_transaction();

    // ── 1. training_sessions : INSERT ──────────────────────────
    $s1 = $conn->prepare(
        "INSERT INTO training_sessions
            (user_id, category_id, score, total_questions,
             correct_answers, wrong_answers, success_rate, avg_time,
             best_streak, final_level, started_at, ended_at, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)"
    );
    $s1->bind_param(
        "iiiiiidiiss",
        $uid, $cat_id, $score, $total_q,
        $correct, $wrong, $rate, $avg_t,
        $streak, $final_l, $status
    );
    $s1->execute();
    $session_id = $s1->insert_id;
    $s1->close();

    // ── 2. training_stats : UPSERT ────────────────────────────
    // Vérifier si une ligne existe pour ce user
    $chk = $conn->prepare("SELECT id, best_streak FROM training_stats WHERE user_id = ?");
    $chk->bind_param("i", $uid);
    $chk->execute();
    $res = $chk->get_result();
    $row = $res->fetch_assoc();
    $chk->close();

    if (!$row) {
        // Première session training : INSERT
        $ins = $conn->prepare(
            "INSERT INTO training_stats
                (user_id, total_sessions, total_questions, total_correct,
                 total_time, best_streak, last_played)
             VALUES (?, 1, ?, ?, ?, ?, NOW())"
        );
        $ins->bind_param("iiiii", $uid, $total_q, $correct, $time_played, $streak);
        $ins->execute();
        $ins->close();
    } else {
        // Sessions suivantes : UPDATE incrémental
        $best = max((int)$row['best_streak'], $streak);
        $upd = $conn->prepare(
            "UPDATE training_stats SET
                total_sessions  = total_sessions  + 1,
                total_questions = total_questions + ?,
                total_correct   = total_correct   + ?,
                total_time      = total_time      + ?,
                best_streak     = ?,
                last_played     = NOW()
             WHERE user_id = ?"
        );
        $upd->bind_param("iiiii", $total_q, $correct, $time_played, $best, $uid);
        $upd->execute();
        $upd->close();
    }

    $conn->commit();
    echo json_encode([
        "ok"         => true,
        "session_id" => $session_id,
        "status"     => $status,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    logError("save_training.php - " . $e->getMessage());  // loggé côté serveur, pas exposé au client
    http_response_code(500);
    echo json_encode([
        "ok"    => false,
        "error" => "Une erreur est survenue lors de la sauvegarde.",
    ]);
}
