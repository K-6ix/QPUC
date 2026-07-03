<?php
// ============================================================================
// save_championship.php — Persistance résultats + ELO après un championnat
//
// Appelé par server.js (Node) via HTTP POST en fin de partie.
// Inspiré de save_elo.php (même pattern : server_key + POST JSON)
//
// Met à jour :
//  1. championship_matches (historique de la partie)
//  2. championship_rounds  (détail par manche)
//  3. championship_bets    (paris placés)
//  4. player_stats.elo     (variation ELO)
//  5. player_stats         (compteurs games/victories)
//  6. users.score_total    (score global)
// ============================================================================
require __DIR__ . "/../db.php";

header("Content-Type: application/json; charset=utf-8");

// ── Vérification d'enveloppe signée (HMAC) ──────────────────
// Même mécanisme que save_elo.php (voir qpc_hmac.php pour le détail).
require __DIR__ . "/../qpc_hmac.php";
$data = qpc_verify_envelope($conn);

// ── Données attendues ──────────────────────────────────────
// ranking: [{ user_id, rank, score_m3, eliminated_in, correct, wrong, total_q }]
// room_code: string
// sudden_death: bool
// total_questions: int
// bets: [{ user_id, round, question_index, amount, result, points_delta }]
$ranking          = $data['ranking']          ?? [];
$room_code        = $data['room_code']        ?? '';
$sudden_death     = !empty($data['sudden_death']);
$total_questions  = max(0, (int)($data['total_questions'] ?? 0));
$bets             = $data['bets']             ?? [];

if (!is_array($ranking) || count($ranking) < 2) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Ranking invalide (min 2 joueurs)"]);
    exit;
}

// ── ELO deltas par rang ────────────────────────────────────
$ELO_DELTAS = [
    1 => +50,   // Champion
    2 => +30,   // Finaliste perdant
    3 =>   0,   // Éliminé M2
    4 => -20    // Éliminé M1
];

try {
    // ── Trier par rang ─────────────────────────────────────
    usort($ranking, function($a, $b) {
        return ($a['rank'] ?? 99) - ($b['rank'] ?? 99);
    });

    // Extraire les IDs par rang
    $p1_id = (int)($ranking[0]['user_id'] ?? 0);
    $p2_id = (int)($ranking[1]['user_id'] ?? 0);
    $p3_id = isset($ranking[2]) ? (int)($ranking[2]['user_id'] ?? 0) : null;
    $p4_id = isset($ranking[3]) ? (int)($ranking[3]['user_id'] ?? 0) : null;

    $p1_score = (int)($ranking[0]['score_m3'] ?? 0);
    $p2_score = (int)($ranking[1]['score_m3'] ?? 0);

    if ($p1_id <= 0 || $p2_id <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "IDs joueurs invalides"]);
        exit;
    }

    // Calculer les ELO deltas
    $elo_deltas = [];
    foreach ($ranking as $r) {
        $uid  = (int)($r['user_id'] ?? 0);
        $rank = (int)($r['rank'] ?? 99);
        $elo_deltas[$uid] = $ELO_DELTAS[$rank] ?? 0;
    }

    // ── 1. championship_matches ────────────────────────────

    $p1_elo_d = $elo_deltas[$p1_id] ?? 0;
    $p2_elo_d = $elo_deltas[$p2_id] ?? 0;
    $p3_elo_d = $p3_id ? ($elo_deltas[$p3_id] ?? 0) : 0;
    $p4_elo_d = $p4_id ? ($elo_deltas[$p4_id] ?? 0) : 0;
    $sd_int   = $sudden_death ? 1 : 0;

    // p3_id et p4_id peuvent être NULL (si moins de 4 joueurs ou déconnexion)
    // On construit le SQL dynamiquement pour insérer NULL plutôt que 0
    $p3_val = $p3_id ? (int)$p3_id : 'NULL';
    $p4_val = $p4_id ? (int)$p4_id : 'NULL';

    $sql = "INSERT INTO championship_matches
        (room_code, p1_id, p2_id, p3_id, p4_id,
         p1_score_m3, p2_score_m3,
         p1_elo_delta, p2_elo_delta, p3_elo_delta, p4_elo_delta,
         winner_id, sudden_death, total_questions, finished_at)
     VALUES (?, ?, ?, $p3_val, $p4_val, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    // 12 placeholders (14 - 2 pour p3/p4 qui sont en dur)
    $stmt->bind_param("siiiiiiiiiii",
        $room_code,
        $p1_id, $p2_id,
        $p1_score, $p2_score,
        $p1_elo_d, $p2_elo_d, $p3_elo_d, $p4_elo_d,
        $p1_id, $sd_int, $total_questions
    );
    $stmt->execute();
    $match_id = $stmt->insert_id;

    // ── 2. championship_rounds ─────────────────────────────
    $round_stmt = $conn->prepare(
        "INSERT INTO championship_rounds
            (match_id, round_number, player_id, final_score, eliminated,
             questions_answered, correct_answers, wrong_answers)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($ranking as $r) {
        $uid       = (int)($r['user_id'] ?? 0);
        if ($uid <= 0) continue;

        $rounds_data = $r['rounds'] ?? [];
        if (empty($rounds_data)) {
            // Pas de détail par manche, on crée une entrée résumée
            $elim_in = $r['eliminated_in'] ?? 0;
            $score   = (int)($r['score_m3'] ?? 0);
            $correct = (int)($r['correct'] ?? 0);
            $wrong   = (int)($r['wrong'] ?? 0);
            $total_q = (int)($r['total_q'] ?? 0);
            $elim    = ($elim_in > 0) ? 1 : 0;
            $rn      = max(1, (int)$elim_in);

            $round_stmt->bind_param("iiiiiiii",
                $match_id, $rn, $uid, $score, $elim,
                $total_q, $correct, $wrong
            );
            $round_stmt->execute();
        } else {
            foreach ($rounds_data as $rd) {
                $rn      = (int)($rd['round'] ?? 1);
                $score   = (int)($rd['score'] ?? 0);
                $elim    = !empty($rd['eliminated']) ? 1 : 0;
                $correct = (int)($rd['correct'] ?? 0);
                $wrong   = (int)($rd['wrong'] ?? 0);
                $total_q = (int)($rd['total_q'] ?? 0);

                $round_stmt->bind_param("iiiiiiii",
                    $match_id, $rn, $uid, $score, $elim,
                    $total_q, $correct, $wrong
                );
                $round_stmt->execute();
            }
        }
    }

    // ── 3. championship_bets ───────────────────────────────
    if (is_array($bets) && count($bets) > 0) {
        $bet_stmt = $conn->prepare(
            "INSERT INTO championship_bets
                (match_id, round_number, question_index, bettor_id, target_id,
                 bet_type, amount, result, points_delta)
             VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?)"
        );

        foreach ($bets as $b) {
            $rn     = (int)($b['round'] ?? 3);
            $qi     = (int)($b['question_index'] ?? 0);
            $uid    = (int)($b['user_id'] ?? 0);
            $type   = $b['bet_type'] ?? 'm3_secret';
            $amount = (int)($b['amount'] ?? 0);
            $result = $b['result'] ?? 'pending';
            $delta  = (int)($b['points_delta'] ?? 0);

            if ($uid <= 0 || $amount <= 0) continue;

            $bet_stmt->bind_param("iiiisisi",
                $match_id, $rn, $qi, $uid,
                $type, $amount, $result, $delta
            );
            $bet_stmt->execute();
        }
    }

    // ── 4. player_stats (ELO + compteurs) ──────────────────
    $results_output = [];

    foreach ($ranking as $r) {
        $uid       = (int)($r['user_id'] ?? 0);
        $rank      = (int)($r['rank'] ?? 99);
        $correct   = (int)($r['correct'] ?? 0);
        $wrong     = (int)($r['wrong'] ?? 0);
        $total_q   = max(1, (int)($r['total_q'] ?? 1));
        $elo_delta = $elo_deltas[$uid] ?? 0;
        $is_win    = ($rank === 1) ? 1 : 0;
        $is_loss   = ($rank === 1) ? 0 : 1;

        if ($uid <= 0) continue;

        // Lire l'ELO actuel
        $chk = $conn->prepare("SELECT id, elo FROM player_stats WHERE user_id = ?");
        $chk->bind_param("i", $uid);
        $chk->execute();
        $ps = $chk->get_result()->fetch_assoc();

        if (!$ps) {
            // Première partie : créer la ligne player_stats
            $new_elo = max(0, 1200 + $elo_delta);
            $wr      = $is_win ? 100.0 : 0.0;
            $ins = $conn->prepare(
                "INSERT INTO player_stats
                    (user_id, elo, total_games, games_finished,
                     victories, defeats, best_score, average_score, winrate,
                     total_correct, total_wrong)
                 VALUES (?, ?, 1, 1, ?, ?, 0, 0, ?, ?, ?)"
            );
            $ins->bind_param("iiiiidii",
                $uid, $new_elo,
                $is_win, $is_loss, $wr,
                $correct, $wrong
            );
            $ins->execute();
        } else {
            // Mettre à jour
            $new_elo = max(0, (int)$ps['elo'] + $elo_delta);
            $upd = $conn->prepare(
                "UPDATE player_stats SET
                    elo             = ?,
                    total_games     = total_games + 1,
                    games_finished  = games_finished + 1,
                    victories       = victories + ?,
                    defeats         = defeats + ?,
                    total_correct   = total_correct + ?,
                    total_wrong     = total_wrong + ?,
                    winrate         = (victories / total_games) * 100
                 WHERE user_id = ?"
            );
            $upd->bind_param("iiiiii",
                $new_elo,
                $is_win, $is_loss,
                $correct, $wrong,
                $uid
            );
            $upd->execute();
        }

        // ── 5. users.score_total ───────────────────────────
        // On ajoute les points ELO gagnés (si positifs) au score_total
        if ($elo_delta > 0) {
            $u = $conn->prepare("UPDATE users SET score_total = score_total + ? WHERE id = ?");
            $u->bind_param("ii", $elo_delta, $uid);
            $u->execute();
        }

        $results_output[] = [
            "user_id"   => $uid,
            "rank"      => $rank,
            "elo_delta" => $elo_delta,
            "new_elo"   => $new_elo,
            "correct"   => $correct,
            "wrong"     => $wrong
        ];
    }

    echo json_encode([
        "ok"       => true,
        "match_id" => $match_id,
        "updated"  => $results_output
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Erreur serveur : " . $e->getMessage()]);
}
