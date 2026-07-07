<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ── Vérification connexion ──────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ── Données utilisateur ─────────────────────────────────────
$stmt = $conn->prepare("SELECT username, email, profile_pic, pays, age FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ── Stats globales ──────────────────────────────────────────
$stmt2 = $conn->prepare("SELECT * FROM player_stats WHERE user_id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$stats = $stmt2->get_result()->fetch_assoc();

// Valeurs par défaut si pas encore de stats
$total_games  = $stats['total_games']    ?? 0;
$victories    = $stats['victories']      ?? 0;
$best_score   = $stats['best_score']     ?? 0;
$winrate      = $stats['winrate']        ?? 0;
$avg_time     = $stats['average_time_answer'] ?? 0;
$best_streak  = $stats['best_streak']    ?? 0;
$total_time   = $stats['total_time_played'] ?? 0;

// ── Stats championship ──────────────────────────────────────
$champ_stmt = $conn->prepare("
    SELECT
        COUNT(*) AS champ_played,
        SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) AS champ_won,
        SUM(CASE WHEN winner_id = ? OR p2_id = ? THEN 1 ELSE 0 END) AS champ_finals,
        SUM(
            CASE
                WHEN winner_id = ? THEN p1_elo_delta
                WHEN p2_id = ?     THEN p2_elo_delta
                WHEN p3_id = ?     THEN p3_elo_delta
                WHEN p4_id = ?     THEN p4_elo_delta
                ELSE 0
            END
        ) AS champ_elo_total
    FROM championship_matches
    WHERE p1_id = ? OR p2_id = ? OR p3_id = ? OR p4_id = ?
");
$champ_stmt->bind_param("iiiiiiiiiii",
    $user_id, $user_id, $user_id,
    $user_id, $user_id, $user_id, $user_id,
    $user_id, $user_id, $user_id, $user_id
);
$champ_stmt->execute();
$champ_stats = $champ_stmt->get_result()->fetch_assoc();

$champ_played   = (int)($champ_stats['champ_played']   ?? 0);
$champ_won      = (int)($champ_stats['champ_won']      ?? 0);
$champ_finals   = (int)($champ_stats['champ_finals']   ?? 0);
$champ_elo_total= (int)($champ_stats['champ_elo_total']?? 0);
$champ_winrate  = $champ_played > 0 ? round($champ_won * 100 / $champ_played, 1) : 0;

// ── Stats GLOBALES fusionnées (1v1 + champ) ─────────────────
$total_games_all = $total_games + $champ_played;
$victories_all   = $victories + $champ_won;
$winrate_all     = $total_games_all > 0
                 ? round($victories_all * 100 / $total_games_all, 1)
                 : 0;

// ── Rang global + ELO ───────────────────────────────────────
// (Remplace la vue `leaderboard` — incompatible avec MySQL 5.7 InfinityFree)
// Rang = 1 + nombre de joueurs qui ont un ELO strictement supérieur
$rank_stmt = $conn->prepare("
    SELECT
        COALESCE(ps.elo, 1200) AS elo,
        (
            SELECT COUNT(*) + 1
            FROM users u2
            LEFT JOIN player_stats ps2 ON ps2.user_id = u2.id
            WHERE COALESCE(ps2.elo, 1200) > COALESCE(ps.elo, 1200)
        ) AS `rank`
    FROM users u
    LEFT JOIN player_stats ps ON ps.user_id = u.id
    WHERE u.id = ?
");
$rank_stmt->bind_param("i", $user_id);
$rank_stmt->execute();
$rank_row = $rank_stmt->get_result()->fetch_assoc();
$global_rank = $rank_row['rank'] ?? '—';
$user_elo    = (int)($rank_row['elo'] ?? 1200);

// ── Stats par catégorie (pour le radar) ─────────────────────
$cat_stmt = $conn->prepare("
    SELECT c.nom, p.success_rate
    FROM player_stats_by_category p
    JOIN categories c ON c.id = p.category_id
    WHERE p.user_id = ?
    ORDER BY p.success_rate DESC
");
$cat_stmt->bind_param("i", $user_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
$cat_labels = [];
$cat_values = [];
while ($row = $cat_result->fetch_assoc()) {
    $cat_labels[] = $row['nom'];
    $cat_values[] = round($row['success_rate'], 1);
}

// ── Historique des 20 dernières parties (1v1 + championship) ──
$hist_stmt = $conn->prepare("
    (SELECT
        'classic'        AS source,
        score,
        status,
        game_mode,
        time_played,
        correct_answers,
        total_questions,
        difficulty,
        started_at,
        NULL             AS champ_rank,
        NULL             AS champ_elo_delta,
        NULL             AS room_code
     FROM game_sessions
     WHERE user_id = ? AND status != 'active')
    UNION ALL
    (SELECT
        'champ'          AS source,
        NULL             AS score,
        'finished'       AS status,
        'championship'   AS game_mode,
        NULL             AS time_played,
        NULL             AS correct_answers,
        NULL             AS total_questions,
        NULL             AS difficulty,
        started_at,
        CASE
            WHEN winner_id = ? THEN 1
            WHEN p2_id = ?     THEN 2
            WHEN p3_id = ?     THEN 3
            WHEN p4_id = ?     THEN 4
            ELSE 0
        END              AS champ_rank,
        CASE
            WHEN winner_id = ? THEN p1_elo_delta
            WHEN p2_id = ?     THEN p2_elo_delta
            WHEN p3_id = ?     THEN p3_elo_delta
            WHEN p4_id = ?     THEN p4_elo_delta
            ELSE 0
        END              AS champ_elo_delta,
        room_code
     FROM championship_matches
     WHERE p1_id = ? OR p2_id = ? OR p3_id = ? OR p4_id = ?)
    ORDER BY started_at DESC
    LIMIT 20
");
$hist_stmt->bind_param("iiiiiiiiiiiii",
    $user_id,                                          // classic
    $user_id, $user_id, $user_id, $user_id,            // rank champ
    $user_id, $user_id, $user_id, $user_id,            // elo champ
    $user_id, $user_id, $user_id, $user_id             // WHERE champ
);
$hist_stmt->execute();
$history = $hist_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Graphe de performance — 7 derniers jours ─────────────────
$perf_week = $conn->prepare("
    SELECT DATE(started_at) AS jour,
           COUNT(*) AS parties,
           SUM(correct_answers) AS bonnes,
           MAX(score) AS meilleur_score
    FROM game_sessions
    WHERE user_id = ? AND status = 'finished'
      AND started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(started_at)
    ORDER BY jour ASC
");
$perf_week->bind_param("i", $user_id);
$perf_week->execute();
$perf_week_rows = $perf_week->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Graphe de performance — 4 semaines ───────────────────────
$perf_month = $conn->prepare("
    SELECT WEEK(started_at) AS semaine,
           MIN(DATE(started_at)) AS debut,
           COUNT(*) AS parties,
           SUM(correct_answers) AS bonnes,
           MAX(score) AS meilleur_score
    FROM game_sessions
    WHERE user_id = ? AND status = 'finished'
      AND started_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
    GROUP BY WEEK(started_at)
    ORDER BY semaine ASC
");
$perf_month->bind_param("i", $user_id);
$perf_month->execute();
$perf_month_rows = $perf_month->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Graphe de performance — 6 derniers mois ──────────────────
$perf_season = $conn->prepare("
    SELECT DATE_FORMAT(started_at, '%Y-%m') AS mois,
           COUNT(*) AS parties,
           SUM(correct_answers) AS bonnes,
           MAX(score) AS meilleur_score
    FROM game_sessions
    WHERE user_id = ? AND status = 'finished'
      AND started_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois
    ORDER BY mois ASC
");
$perf_season->bind_param("i", $user_id);
$perf_season->execute();
$perf_season_rows = $perf_season->get_result()->fetch_all(MYSQLI_ASSOC);

// Formatage pour Chart.js
$mois_fr = ['01'=>'Jan','02'=>'Fév','03'=>'Mar','04'=>'Avr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Aoû','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Déc'];
$jours_fr = ['Monday'=>'Lun','Tuesday'=>'Mar','Wednesday'=>'Mer','Thursday'=>'Jeu','Friday'=>'Ven','Saturday'=>'Sam','Sunday'=>'Dim'];

$chart_week = ['labels'=>[], 'parties'=>[], 'scores'=>[]];
foreach ($perf_week_rows as $r) {
    $chart_week['labels'][]  = $jours_fr[date('l', strtotime($r['jour']))] ?? $r['jour'];
    $chart_week['parties'][] = (int)$r['parties'];
    $chart_week['scores'][]  = (int)$r['meilleur_score'];
}

$chart_month = ['labels'=>[], 'parties'=>[], 'scores'=>[]];
foreach ($perf_month_rows as $r) {
    $chart_month['labels'][]  = 'S' . date('W', strtotime($r['debut']));
    $chart_month['parties'][] = (int)$r['parties'];
    $chart_month['scores'][]  = (int)$r['meilleur_score'];
}

$chart_season = ['labels'=>[], 'parties'=>[], 'scores'=>[]];
foreach ($perf_season_rows as $r) {
    $parts = explode('-', $r['mois']);
    $chart_season['labels'][]  = $mois_fr[$parts[1]] ?? $r['mois'];
    $chart_season['parties'][] = (int)$r['parties'];
    $chart_season['scores'][]  = (int)$r['meilleur_score'];
}

// ── Top 10 leaderboard ───────────────────────────────────────
// (Remplace la vue `leaderboard` — incompatible avec MySQL 5.7 InfinityFree)
// Astuce compatible 5.7 : variable utilisateur @r incrémentée par la sous-requête ordonnée
$conn->query("SET @r := 0");
$lb_stmt = $conn->query("
    SELECT
        id,
        username,
        score_total,
        (@r := @r + 1) AS `rank`
    FROM (
        SELECT u.id, u.username, u.score_total, COALESCE(ps.elo, 1200) AS elo
        FROM users u
        LEFT JOIN player_stats ps ON ps.user_id = u.id
        ORDER BY elo DESC, u.score_total DESC
        LIMIT 10
    ) t
");
$leaderboard = $lb_stmt->fetch_all(MYSQLI_ASSOC);

// ── Messages flash ──────────────────────────────────────────
$flash_error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$flash_success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<script>try{if(localStorage.getItem('qpc-theme')==='light')document.documentElement.classList.add('light');}catch(e){}</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HESTIM — Champion Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Cinzel:wght@400;600;700&family=Raleway:wght@300;400;500;600;700&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
  /* ═══════════════════════════════════════
    TOKENS
  ═══════════════════════════════════════ */
  :root {
    --g100: #fff8e0;
    --g300: #f0d060;
    --g400: #d4af37;
    --g500: #b8902a;
    --g700: #7a5c14;
    --g900: #3a2a06;
    --grad: linear-gradient(120deg, #3a2a06 0%, #b8902a 30%, #fff8e0 50%, #b8902a 70%, #3a2a06 100%);
    --grad-subtle: linear-gradient(135deg, rgba(212,175,55,0.15) 0%, rgba(212,175,55,0.03) 100%);
    --bg:       #060606;
    --bg1:      #0d0d0d;
    --bg2:      #131313;
    --bg3:      #1a1a1a;
    --border:   rgba(212,175,55,0.14);
    --border2:  rgba(212,175,55,0.30);
    --text:     #f0e8cc;
    --text2:    rgba(240,232,204,0.55);
    --text3:    rgba(240,232,204,0.30);
    --red:      #e05555;
    --green:    #4caf78;
    --blue:     #5599dd;
    --r: 12px;
    --r2: 20px;
    --ease: cubic-bezier(0.4,0,0.2,1);

    /* ── Tokens header (portés depuis index.php) ── */
    --gold-light: #fcf6ba;
    --gold-base:  #d4af37;
    --gold-dark:  #8a6e2f;
    --gold-glow:  rgba(212,175,55,0.35);
    --metallic:   linear-gradient(to right, var(--gold-dark), var(--gold-base) 30%, var(--gold-light) 50%, var(--gold-base) 70%, var(--gold-dark));
    --header-bg:  rgba(6,6,6,0.85);
    --ink:        #ffffff;
    --ink-2:      rgba(255,255,255,0.55);
    --ink-4:      rgba(255,255,255,0.2);
    --line-soft:  rgba(255,255,255,0.05);
    --gold-line:  rgba(212,175,55,0.15);
    --gold-line-strong: rgba(212,175,55,0.35);
    --gold-tint:  rgba(212,175,55,0.05);
    --gold-text:  var(--gold-light);
    --on-gold:    #000;
  }

  /* ═══════════════════════════════════════
    MODE CLAIR (clé partagée qpc-theme)
  ═══════════════════════════════════════ */
  html.light {
    --bg:       #ffffff;
    --bg2:      #f4f2ec;
    --border:   rgba(138,110,47,0.22);
    --border2:  rgba(138,110,47,0.42);
    --text:     #1a1408;
    --text2:    rgba(26,20,8,0.62);
    --text3:    rgba(26,20,8,0.52);
    --bg1:      #faf9f5;
    --bg3:      #eae7df;                 /* fond des champs (était #1a1a1a → noir) */
    --g100:     #5f4a12;                 /* gros chiffres/titres (était #fff8e0) */
    --g300:     #9a7815;                 /* or lisible sur blanc (était #f0d060) */
    --grad:     linear-gradient(120deg, #5a4410 0%, #8a6e2f 38%, #b8902a 50%, #8a6e2f 62%, #5a4410 100%);
    --metallic: linear-gradient(to right, #6b5410, #8a6e2f 38%, #b8902a 50%, #8a6e2f 62%, #6b5410);

    --header-bg:  rgba(255,255,255,0.88);
    --ink:        #0a0a0a;
    --ink-2:      rgba(10,10,10,0.6);
    --ink-4:      rgba(10,10,10,0.3);
    --line-soft:  rgba(10,10,10,0.06);
    --gold-line:  rgba(138,110,47,0.3);
    --gold-line-strong: rgba(138,110,47,0.55);
    --gold-tint:  rgba(212,175,55,0.08);
    --gold-text:  var(--gold-dark);
  }
  html.light body::before {
    background:
      radial-gradient(ellipse 600px 300px at 70% 10%, rgba(212,175,55,0.08) 0%, transparent 70%),
      radial-gradient(ellipse 400px 400px at 10% 90%, rgba(212,175,55,0.05) 0%, transparent 70%);
  }
  html.light #cursor { mix-blend-mode: normal; }
  html.theme-transitioning, html.theme-transitioning * {
    transition: background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease !important;
  }
  /* Exceptions mode clair : textes posés sur un fond qui reste sombre */
  html.light .avatar-edit-overlay { color: #fff8e0; }   /* overlay noir au survol avatar */
  html.light .welcome-greeting    { color: var(--gold-dark); } /* eyebrow plus lisible */

  /* ═══════════════════════════════════════
    RESET & BASE
  ═══════════════════════════════════════ */
  *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
  html { height:100%; scroll-behavior:smooth; }
  body {
    font-family: 'Raleway', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    overflow-x: hidden;
    cursor: default;
  }

  /* Custom cursor */
  body { cursor: none; }
  #cursor {
    position: fixed;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--g400);
    pointer-events: none;
    z-index: 9999;
    transform: translate(-50%,-50%);
    transition: transform 0.08s var(--ease), width 0.2s, height 0.2s, opacity 0.2s;
    mix-blend-mode: screen;
  }
  #cursor-ring {
    position: fixed;
    width: 32px; height: 32px;
    border-radius: 50%;
    border: 1px solid rgba(212,175,55,0.5);
    pointer-events: none;
    z-index: 9998;
    transform: translate(-50%,-50%);
    transition: transform 0.18s var(--ease), width 0.25s, height 0.25s, opacity 0.3s;
  }

  /* Ambient BG glow */
  body::before {
    content:'';
    position:fixed; inset:0;
    background:
      radial-gradient(ellipse 600px 300px at 70% 10%, rgba(212,175,55,0.05) 0%, transparent 70%),
      radial-gradient(ellipse 400px 400px at 10% 90%, rgba(212,175,55,0.03) 0%, transparent 70%);
    pointer-events:none; z-index:0;
  }

  /* Noise texture overlay */
  body::after {
    content:'';
    position:fixed; inset:0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
    opacity: 0.4;
    pointer-events:none; z-index:0;
  }

  /* ═══════════════════════════════════════
    MAIN LAYOUT
  ═══════════════════════════════════════ */
  .main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    padding-top: 72px; /* header fixe */
    position: relative;
    z-index: 1;
  }

  /* ════════════════════════════
    HEADER (même header que index.php)
  ════════════════════════════ */
  header {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    display: grid;
    grid-template-columns: 30% 50% 20%;
    align-items: center;
    padding: 0 40px;
    height: 72px;
    border-bottom: 1px solid var(--gold-line);
    background: var(--header-bg);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    opacity: 0;
    animation: slideDown 0.8s cubic-bezier(0.2,0.8,0.2,1) 0.2s forwards;
  }

  .logo {
    font-family: 'Kanit', sans-serif;
    font-weight: 900;
    font-size: 1.1rem;
    letter-spacing: 3px;
    background: var(--metallic);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-transform: uppercase;
    filter: drop-shadow(0 0 6px var(--gold-glow));
    text-decoration: none;
    justify-self: start;
  }

  header nav ul {
    display: flex;
    list-style: none;
    gap: 28px;
    align-items: center;
    justify-content: center;
  }

  header nav a {
    text-decoration: none;
    color: var(--ink-2);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    position: relative;
    transition: color 0.3s;
    font-family: 'Montserrat', sans-serif;
  }
  header nav a:hover { color: var(--gold-text); }
  header nav a::after {
    content:'';
    position:absolute;
    width:0; height:1px;
    bottom:-4px; left:0;
    background: var(--metallic);
    transition: width 0.3s;
  }
  header nav a:hover::after { width:100%; }

  .btn-play {
    background: var(--metallic);
    color: var(--on-gold) !important;
    -webkit-text-fill-color: var(--on-gold) !important;
    padding: 7px 22px;
    border-radius: 30px;
    font-weight: 900;
    border: 1px solid var(--gold-base);
    box-shadow: 0 0 12px var(--gold-glow);
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .btn-play:hover { transform: scale(1.05); box-shadow: 0 0 22px rgba(212,175,55,0.7); }
  .btn-play::after { display: none; }

  .header-right {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 1px solid var(--gold-line-strong);
    background: transparent;
    color: var(--ink);
    cursor: pointer;
    transition: border-color 0.25s, color 0.25s, transform 0.2s, background 0.25s;
    flex-shrink: 0;
  }
  .icon-btn:hover {
    border-color: var(--gold-base);
    color: var(--gold-text);
    background: var(--gold-tint);
  }
  .icon-btn:active { transform: scale(0.95); }
  .icon-btn svg { width: 15px; height: 15px; }
  #theme-toggle .theme-moon { display: none; }
  #theme-toggle .theme-sun  { display: block; }
  html.light #theme-toggle .theme-moon { display: block; }
  html.light #theme-toggle .theme-sun  { display: none; }

  /* ── Déconnexion discrète (bas de page) ── */
  .logout-corner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 90;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    border: 1px solid var(--gold-line-strong);
    border-radius: 30px;
    background: rgba(6,6,6,0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: var(--gold-text);
    font-family: 'Montserrat', sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    opacity: 0.5;
    transition: opacity 0.3s, box-shadow 0.3s, border-color 0.3s;
  }
  .logout-corner:hover {
    opacity: 1;
    border-color: var(--gold-base);
    box-shadow: 0 0 18px var(--gold-glow);
  }

  #burger-trigger { display: none; }

  @keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
  }

  /* ════════════════════════════
    MOBILE DRAWER MENU
  ════════════════════════════ */
  #mobile-menu {
    position: fixed;
    inset: 0;
    z-index: 200;
    visibility: hidden;
    pointer-events: none;
  }
  #mobile-menu.is-open {
    visibility: visible;
    pointer-events: auto;
  }

  #mobile-menu-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  #mobile-menu.is-open #mobile-menu-backdrop { opacity: 1; }

  #mobile-menu-panel {
    position: absolute;
    right: 0; top: 0;
    height: 100%;
    width: 75%;
    max-width: 360px;
    background: var(--bg);
    border-left: 1px solid var(--gold-line);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -10px 0 40px rgba(0,0,0,0.4);
  }
  #mobile-menu.is-open #mobile-menu-panel { transform: translateX(0); }

  .drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    height: 72px;
    border-bottom: 1px solid var(--gold-line);
  }

  .drawer-nav {
    flex: 1;
    padding: 32px 24px;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .drawer-section-label {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--gold-text);
    margin-bottom: 18px;
  }

  .drawer-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 0;
    border-bottom: 1px solid var(--line-soft);
    text-decoration: none;
    color: var(--ink);
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 1px;
    transition: color 0.25s, padding-left 0.25s;
    font-family: 'Montserrat', sans-serif;
  }
  .drawer-link:hover {
    color: var(--gold-text);
    padding-left: 6px;
  }
  .drawer-link svg {
    width: 16px;
    height: 16px;
    color: var(--ink-4);
    transition: color 0.25s, transform 0.25s;
  }
  .drawer-link:hover svg {
    color: var(--gold-text);
    transform: translateX(4px);
  }

  .drawer-footer {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-top: 1px solid var(--line-soft);
  }
  .drawer-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border-radius: 40px;
    font-weight: 900;
    font-size: 0.8rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    font-family: 'Montserrat', sans-serif;
  }
  .drawer-cta.primary {
    background: var(--metallic);
    color: var(--on-gold);
    border: 1px solid var(--gold-base);
    box-shadow: 0 0 12px var(--gold-glow);
  }
  .drawer-cta.primary:hover { transform: translateY(-2px); }
  .drawer-cta.secondary {
    background: transparent;
    border: 1px solid var(--gold-line-strong);
    color: var(--gold-text);
  }
  .drawer-cta.secondary:hover {
    background: var(--gold-tint);
    border-color: var(--gold-base);
  }
  .drawer-copy {
    text-align: center;
    font-size: 0.65rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--ink-4);
    margin-top: 8px;
  }

  /* ── CONTENT ── */
  .content {
    padding: 36px;
    display: flex;
    flex-direction: column;
    gap: 28px;
    animation: fadeUp 0.7s var(--ease) 0.1s both;
  }

  @keyframes fadeUp {
    from { transform: translateY(20px); opacity:0; }
    to   { transform: translateY(0);    opacity:1; }
  }

  /* ── WELCOME BANNER ── */
  .welcome-banner {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    padding: 28px 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }

  .welcome-banner::before {
    content:'';
    position:absolute; inset:0;
    background: linear-gradient(90deg, rgba(212,175,55,0.06) 0%, transparent 60%);
  }

  .welcome-banner::after {
    content:'';
    position:absolute; right:-60px; top:-60px;
    width:200px; height:200px;
    border-radius:50%;
    border: 1px solid rgba(212,175,55,0.12);
  }

  .welcome-text { position:relative; }
  .welcome-greeting {
    font-size: 0.68rem; letter-spacing: 3px;
    color: var(--g400); text-transform: uppercase;
    margin-bottom: 6px;
  }
  .welcome-name {
    font-family: 'Cinzel', serif;
    font-size: 1.6rem; font-weight: 700;
    background: var(--grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  }
  .welcome-status {
    font-size: 0.78rem; color: var(--text2); margin-top: 6px;
  }
  .welcome-status span { color: var(--green); font-weight: 600; }

  .rank-badge {
    position: relative;
    display: flex; flex-direction: column; align-items: center;
    padding: 20px 32px;
    background: rgba(212,175,55,0.06);
    border: 1px solid var(--border2);
    border-radius: var(--r2);
    text-align: center;
  }
  .rank-badge-label {
    font-size: 0.6rem; letter-spacing: 3px;
    color: var(--text3); text-transform: uppercase; margin-bottom: 6px;
  }
  .rank-badge-value {
    font-family: 'Cinzel', serif;
    font-size: 2.2rem; font-weight: 900;
    background: var(--grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    line-height: 1;
  }
  .rank-badge-sub {
    font-size: 0.68rem; color: var(--g500); margin-top: 4px; letter-spacing: 1px;
  }

  /* ── STAT CARDS ── */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }

  .stat-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    padding: 24px 22px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.25s var(--ease), transform 0.25s var(--ease);
    cursor: default;
  }

  .stat-card::before {
    content:'';
    position:absolute; top:0; left:0; right:0;
    height:2px;
    background: var(--grad);
    opacity:0;
    transition: opacity 0.25s;
  }

  .stat-card:hover { border-color: var(--border2); transform: translateY(-2px); }
  .stat-card:hover::before { opacity:1; }

  .stat-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: rgba(212,175,55,0.1);
    border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
    font-size: 1rem;
  }

  .stat-label {
    font-size: 0.65rem; letter-spacing: 2px;
    color: var(--text3); text-transform: uppercase;
    margin-bottom: 6px;
  }

  .stat-value {
    font-family: 'Cinzel', serif;
    font-size: 1.9rem; font-weight: 700;
    color: var(--g100);
    line-height: 1;
  }

  .stat-change {
    font-size: 0.72rem; font-weight: 600;
    margin-top: 8px;
  }
  .stat-change.up { color: var(--green); }
  .stat-change.down { color: var(--red); }
  .stat-change.neutral { color: var(--text2); }

  /* ── MAIN GRID ── */
  .main-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
  }

  /* ── CHART PANEL ── */
  .panel {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    overflow: hidden;
  }

  .panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
  }

  .panel-title {
    font-family: 'Cinzel', serif;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 2px;
    color: var(--g100);
  }

  .panel-tabs {
    display: flex; gap: 4px;
  }

  .tab {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--text3);
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
  }
  .tab.active {
    color: var(--g300);
    background: rgba(212,175,55,0.1);
    border-color: var(--border2);
  }
  .tab:hover:not(.active) { color: var(--text2); }

  .panel-body { padding: 24px; }

  .chart-wrap { position: relative; height: 240px; }

  /* ── RADAR CHART ── */
  .radar-wrap { position: relative; height: 280px; display:flex; align-items:center; justify-content:center; }

  /* ── PROFILE RIGHT PANEL ── */
  .profile-panel {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .profile-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--r2);
    overflow: hidden;
  }

  /* Gold header strip */
  .profile-card-header {
    height: 80px;
    background: linear-gradient(135deg, var(--g900), var(--g700));
    position: relative;
    overflow: hidden;
  }

  .profile-card-header::before {
    content:'';
    position:absolute; inset:0;
    background: repeating-linear-gradient(
      45deg,
      transparent, transparent 10px,
      rgba(255,255,255,0.02) 10px, rgba(255,255,255,0.02) 11px
    );
  }

  .profile-card-header::after {
    content:'';
    position:absolute; bottom:0; left:0; right:0;
    height:1px;
    background: var(--grad);
    opacity:0.5;
  }

  .profile-avatar-wrap {
    display: flex;
    justify-content: center;
    margin-top: -45px;
    position: relative; z-index:2;
  }

  .avatar-lg {
    width: 90px; height: 90px;
    border-radius: 50%;
    border: 3px solid var(--bg2);
    background: var(--bg3);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.25s var(--ease);
  }
  .avatar-lg:hover { transform: scale(1.06); }

  .avatar-ring-lg {
    position: absolute; inset:-3px;
    border-radius:50%;
    background: var(--grad);
    animation: spin 8s linear infinite;
    z-index:0;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .avatar-inner-lg {
    position:absolute; inset:3px;
    border-radius:50%;
    background: var(--bg3);
    overflow:hidden; z-index:1;
  }
  .avatar-inner-lg img {
    width:100%; height:100%; object-fit:cover;
  }

  .avatar-edit-overlay {
    position:absolute; inset:3px; border-radius:50%;
    background: rgba(0,0,0,0.6);
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity 0.2s; z-index:2;
    font-size:0.58rem; letter-spacing:1px;
    color: var(--g100); text-transform:uppercase;
  }
  .avatar-lg:hover .avatar-edit-overlay { opacity:1; }

  .profile-card-body { padding: 16px 24px 24px; }

  .profile-name {
    text-align: center;
    font-family: 'Cinzel', serif;
    font-size: 1.1rem; font-weight: 700;
    color: var(--g100);
    margin-bottom: 2px;
  }
  .profile-email {
    text-align: center;
    font-size: 0.72rem; color: var(--text3);
    margin-bottom: 16px;
  }

  .profile-divider {
    border:none; border-top:1px solid var(--border);
    margin: 16px 0;
  }

  /* Edit form */
  .form-field { margin-bottom: 14px; }
  .form-label {
    display: block;
    font-size: 0.62rem; letter-spacing: 2px;
    color: var(--g500); text-transform: uppercase;
    margin-bottom: 6px;
  }
  .form-input {
    width: 100%;
    padding: 10px 14px;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--r);
    color: var(--text);
    font-family: 'Raleway', sans-serif;
    font-size: 0.85rem;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-input:focus {
    border-color: var(--g400);
    box-shadow: 0 0 0 3px rgba(212,175,55,0.1);
  }

  .btn-save {
    width: 100%;
    padding: 12px;
    background: var(--grad);
    color: #000;
    font-family: 'Cinzel', serif;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    border: none;
    border-radius: var(--r);
    cursor: pointer;
    transition: box-shadow 0.25s var(--ease), transform 0.2s;
    margin-top: 4px;
  }
  .btn-save:hover {
    box-shadow: 0 6px 28px rgba(212,175,55,0.35);
    transform: translateY(-1px);
  }
  .btn-save:active { transform: translateY(0); }

  /* ── MATCH HISTORY ── */
  .matches { display:flex; flex-direction:column; gap:0; }

  .match-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
  }
  .match-row:last-child { border-bottom:none; }
  .match-row:hover { background: rgba(212,175,55,0.03); }

  .match-result {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items:center; justify-content:center;
    font-family:'Cinzel',serif; font-size:0.75rem; font-weight:700;
    flex-shrink:0;
  }
  .match-result.win  { background:rgba(76,175,120,0.15); color:var(--green); border:1px solid rgba(76,175,120,0.25); }
  .match-result.loss { background:rgba(224,85,85,0.12);  color:var(--red);   border:1px solid rgba(224,85,85,0.2); }
  .match-result.draw { background:rgba(85,153,221,0.12); color:var(--blue);  border:1px solid rgba(85,153,221,0.2); }

  .match-info { flex:1; min-width:0; }
  .match-vs {
    font-size:0.82rem; font-weight:600; color:var(--text);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  .match-map {
    font-size:0.65rem; color:var(--text3); margin-top:2px;
    text-transform:uppercase; letter-spacing:1px;
  }

  .match-score {
    font-family:'Cinzel',serif; font-size:0.9rem; font-weight:600;
    color:var(--g300); flex-shrink:0;
  }

  .match-kda {
    font-size:0.65rem; color:var(--text2);
    flex-shrink:0; text-align:right; min-width:55px;
  }

  /* ── BOTTOM GRID ── */
  .bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }

  /* ── LEADERBOARD ── */
  .leader-row {
    display:flex; align-items:center; gap:14px;
    padding:12px 20px;
    border-bottom:1px solid var(--border);
    transition:background 0.2s;
  }
  .leader-row:hover { background:rgba(212,175,55,0.03); }
  .leader-row:last-child { border-bottom:none; }

  .leader-rank {
    width:28px; text-align:center;
    font-family:'Cinzel',serif; font-size:0.8rem; font-weight:700;
  }
  .leader-rank.gold { color:var(--g300); }
  .leader-rank.silver { color:#aaa; }
  .leader-rank.bronze { color:#c87941; }
  .leader-rank.other { color:var(--text3); }

  .leader-avatar {
    width:34px; height:34px; border-radius:50%;
    background: var(--bg3); border:1px solid var(--border);
    display:flex; align-items:center; justify-content:center;
    font-family:'Cinzel',serif; font-size:0.75rem; font-weight:700;
    color:var(--g400); flex-shrink:0;
  }

  .leader-name { flex:1; font-size:0.82rem; font-weight:500; }
  .leader-self { color:var(--g300); }

  .leader-pts {
    font-family:'Cinzel',serif; font-size:0.85rem;
    color:var(--g400); font-weight:700;
  }

  /* ── PROGRESS BAR ── */
  .prog-row { margin-bottom:14px; }
  .prog-label-row {
    display:flex; justify-content:space-between;
    font-size:0.7rem; margin-bottom:6px;
  }
  .prog-name { color:var(--text2); }
  .prog-val { color:var(--g400); font-weight:600; }

  .prog-track {
    height:6px; background:var(--bg3);
    border-radius:3px; overflow:hidden;
    border:1px solid var(--border);
  }

  .prog-fill {
    height:100%; border-radius:3px;
    background: var(--grad);
    transition: width 1.2s cubic-bezier(0.4,0,0.2,1);
  }

  /* ── LOGOUT ── */
  .btn-logout {
    display:block;
    text-align:center;
    padding:10px;
    color:var(--text3);
    font-size:0.65rem; letter-spacing:2px;
    text-transform:uppercase;
    text-decoration:none;
    border-radius:var(--r);
    transition:color 0.2s, background 0.2s;
    cursor:pointer;
    border:none;
    background:none;
    width:100%;
    font-family:'Raleway',sans-serif;
  }
  .btn-logout:hover { color:#e05555; background:rgba(224,85,85,0.07); }

  /* ── SCROLLBAR ── */
  ::-webkit-scrollbar { width:4px; }
  ::-webkit-scrollbar-track { background:var(--bg); }
  ::-webkit-scrollbar-thumb { background:var(--g700); border-radius:2px; }

  /* ── RESPONSIVE ── */
  @media(max-width:1400px) {
    .stats-row { grid-template-columns:repeat(2,1fr); }
  }
  @media(max-width:1100px) {
    .stats-row { grid-template-columns:repeat(2,1fr); }
    .main-grid { grid-template-columns:1fr; }
    .bottom-grid { grid-template-columns:1fr; }
  }

  @media (max-width: 1024px) {
    header { grid-template-columns: auto 1fr auto; padding: 0 28px; gap: 20px; }
  }
  @media (max-width: 900px) {
    header { padding: 0 20px; grid-template-columns: 1fr auto; }
    header > nav { display: none; }
    .logout-corner { display: none; } /* mobile → Déconnexion dans le drawer */
    #burger-trigger { display: inline-flex; }
  }
  @media (max-width: 600px) {
    header { padding: 0 16px; height: 64px; }
    .logo { font-size: 0.95rem; letter-spacing: 2px; }
    .icon-btn { width: 34px; height: 34px; }
    .main { padding-top: 64px; }
  }

  @media(max-width:768px) {
    .content { padding:20px; }
    .stats-row { grid-template-columns:1fr 1fr; }
    .welcome-banner { flex-direction:column; gap:20px; text-align:center; }
  }

  /* Animations stagger */
  .stat-card:nth-child(1) { animation: fadeUp 0.6s var(--ease) 0.15s both; }
  .stat-card:nth-child(2) { animation: fadeUp 0.6s var(--ease) 0.22s both; }
  .stat-card:nth-child(3) { animation: fadeUp 0.6s var(--ease) 0.29s both; }
  .stat-card:nth-child(4) { animation: fadeUp 0.6s var(--ease) 0.36s both; }
</style>
</head>
<body>

<?php if ($flash_error): ?>
<div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#e05555;color:#fff;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:600;z-index:9999;border:1px solid #cc0000;animation:fadeOut 4s forwards;"><?= htmlspecialchars($flash_error) ?></div>
<?php endif; ?>
<?php if ($flash_success): ?>
<div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#28a745;color:#fff;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:600;z-index:9999;border:1px solid #1e7e34;animation:fadeOut 4s forwards;"><?= htmlspecialchars($flash_success) ?></div>
<?php endif; ?>

<!-- ════ HEADER (même header que index.php) ════ -->
<header>
  <a href="index.php" class="logo">HESTIM</a>

  <nav>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="rules.php">Rules</a></li>
      <li><a href="game.php" class="btn-play">▶ Play</a></li>
      <li><a href="classement.php">Classement</a></li>
      <li><a href="aboutus.php">About Us</a></li>
    </ul>
  </nav>

  <div class="header-right">
    <button id="theme-toggle" class="icon-btn" aria-label="Basculer le thème" type="button">
      <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
      </svg>
      <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
      </svg>
    </button>

    <button id="burger-trigger" class="icon-btn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobile-menu" type="button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>
  </div>
</header>

<!-- ════ MOBILE DRAWER ════ -->
<div id="mobile-menu" aria-hidden="true">
  <div id="mobile-menu-backdrop"></div>
  <aside id="mobile-menu-panel" role="dialog" aria-modal="true" aria-label="Menu principal">
    <div class="drawer-header">
      <span class="logo">HESTIM</span>
      <button id="burger-close" class="icon-btn" aria-label="Fermer le menu" type="button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <nav class="drawer-nav">
      <span class="drawer-section-label">Navigation</span>

      <a href="index.php" data-close class="drawer-link">
        <span>Home</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="rules.php" data-close class="drawer-link">
        <span>Rules</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="classement.php" data-close class="drawer-link">
        <span>Classement</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
      <a href="aboutus.php" data-close class="drawer-link">
        <span>About Us</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </nav>

    <div class="drawer-footer">
      <a href="game.php" data-close class="drawer-cta primary">
        ▶ Jouer
      </a>
      <a href="logout.php" data-close class="drawer-cta secondary">Déconnexion</a>
      <p class="drawer-copy">&copy; 2025 &middot; HESTIM</p>
    </div>
  </aside>
</div>

<!-- ════ DÉCONNEXION (discrète, bas gauche) ════ -->
<a href="logout.php" class="logout-corner">↩ Déconnexion</a>

<!-- CUSTOM CURSOR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>


<!-- ═══ MAIN ═══ -->
<div class="main">

  <!-- CONTENT -->
  <div class="content">

    <!-- WELCOME BANNER -->
    <div class="welcome-banner">
      <div class="welcome-text">
        <div class="welcome-greeting">Bienvenue de retour</div>
        <div class="welcome-name" id="display-name"><?= htmlspecialchars($user['username']) ?></div>
        <div class="welcome-status">Statut : <span>En ligne</span> · <?= $total_games_all ?> parties jouées</div>
      </div>
      <div class="rank-badge">
        <div class="rank-badge-label">Rang Global</div>
        <div class="rank-badge-value">#<?= $global_rank ?></div>
        <div class="rank-badge-sub">Meilleur ELO : <?= number_format($user_elo) ?></div>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon">⚔</div>
        <div class="stat-label">Parties jouées</div>
        <div class="stat-value" id="cnt-games"><?= $total_games_all ?></div>
        <div class="stat-change neutral">
          <?= $total_games ?> 1v1 · <?= $champ_played ?> champ
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🏆</div>
        <div class="stat-label">Victoires</div>
        <div class="stat-value" id="cnt-wins"><?= $victories_all ?></div>
        <div class="stat-change <?= $winrate_all >= 50 ? 'up' : 'down' ?>">
          <?= $winrate_all >= 50 ? '↑' : '↓' ?> <?= $winrate_all ?>% winrate
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">💎</div>
        <div class="stat-label">Meilleur ELO</div>
        <div class="stat-value" id="cnt-elo"><?= number_format($user_elo) ?></div>
        <div class="stat-change neutral">Temps total : <?= gmdate('H\hi', $total_time) ?></div>
      </div>
      <!-- ─── CARTE CHAMPIONNAT (NEW) ─── -->
      <div class="stat-card" style="border-color: rgba(212,175,55,0.35); background: linear-gradient(135deg, rgba(212,175,55,0.04), transparent);">
        <div class="stat-icon">👑</div>
        <div class="stat-label">Championnats</div>
        <div class="stat-value" style="color:#d4af37"><?= $champ_won ?> / <?= $champ_played ?></div>
        <div class="stat-change neutral">
          <?php if ($champ_played > 0): ?>
            <?= $champ_winrate ?>% winrate ·
            <?= $champ_elo_total >= 0 ? '+' : '' ?><?= $champ_elo_total ?> ELO
          <?php else: ?>
            Aucune partie · <a href="championship/lobby.php" style="color:#d4af37">Jouer</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- MAIN GRID -->
    <div class="main-grid">

      <!-- LEFT: CHARTS + HISTORY -->
      <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Performance Chart -->
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Performance</div>
            <div class="panel-tabs">
              <div class="tab active" onclick="switchTab(this,'week')">7J</div>
              <div class="tab" onclick="switchTab(this,'month')">30J</div>
              <div class="tab" onclick="switchTab(this,'season')">Saison</div>
            </div>
          </div>
          <div class="panel-body">
            <div class="chart-wrap">
              <canvas id="perfChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Radar: Champion Skills -->
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Profil de Champion</div>
            <span style="font-size:0.68rem;color:var(--text3);letter-spacing:1px;">STATISTIQUES</span>
          </div>
          <div class="panel-body" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:center;">
            <div class="radar-wrap">
              <canvas id="radarChart"></canvas>
            </div>
            <div>
              <?php if (empty($cat_labels)): ?>
                <div style="text-align:center;color:var(--text3);font-size:0.8rem;padding:16px 0;">
                  Jouez des parties pour voir<br>vos stats par catégorie.
                </div>
              <?php else: ?>
                <?php foreach ($cat_labels as $i => $cat_name):
                  $val = $cat_values[$i] ?? 0;
                ?>
                <div class="prog-row">
                  <div class="prog-label-row">
                    <span class="prog-name"><?= htmlspecialchars($cat_name) ?></span>
                    <span class="prog-val"><?= round($val) ?>%</span>
                  </div>
                  <div class="prog-track">
                    <div class="prog-fill" style="width:0%" data-w="<?= round($val) ?>"></div>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Match History -->
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Historique des Parties</div>
            <a href="historique.php" style="font-size:0.68rem;color:var(--g500);letter-spacing:1px;text-decoration:none;">Voir tout →</a>
          </div>
          <div class="matches">
            <?php if (empty($history)): ?>
              <div style="padding:32px;text-align:center;color:var(--text3);font-size:0.82rem;letter-spacing:1px;">
                Aucune partie jouée pour l'instant.<br>
                <a href="game.php" style="color:var(--g400);text-decoration:none;margin-top:8px;display:inline-block;">⚔ Lancer une partie</a>
              </div>
            <?php else: ?>
              <?php foreach ($history as $match):
                $source = $match['source'] ?? 'classic';

                if ($source === 'champ') {
                    // ─── Ligne championnat ─────────────────
                    $rank        = (int)($match['champ_rank'] ?? 0);
                    $elo_d       = (int)($match['champ_elo_delta'] ?? 0);
                    $is_win      = ($rank === 1);
                    $result_class= $is_win ? 'win' : 'loss';
                    $rank_label  = $rank === 1 ? '1er 🏆' : ($rank === 2 ? '2ème' : ($rank === 3 ? '3ème' : '4ème'));
                    $result_label= $is_win ? 'V' : 'D';
                    $date        = date('d/m H\hi', strtotime($match['started_at']));
                    $mode_disp   = 'Championnat · ' . $rank_label;
                    $score_disp  = ($elo_d >= 0 ? '+' : '') . $elo_d . ' ELO';
                    $extra_label = 'Rang';
                    $extra_value = '#' . $rank;
                ?>
                <div class="match-row" style="border-left: 3px solid #d4af37;">
                  <div class="match-result <?= $result_class ?>"><?= $result_label ?></div>
                  <div class="match-info">
                    <div class="match-vs">👑 <?= $mode_disp ?></div>
                    <div class="match-map"><?= $date ?> · Room <?= htmlspecialchars($match['room_code'] ?? '-') ?></div>
                  </div>
                  <div class="match-score" style="color:#d4af37"><?= $score_disp ?></div>
                  <div class="match-kda"><?= $extra_label ?><br><b><?= $extra_value ?></b></div>
                </div>
                <?php } else {
                    // ─── Ligne 1v1/solo (comportement original) ─────
                    $is_win      = $match['status'] === 'finished' && $match['correct_answers'] >= ($match['total_questions'] / 2);
                    $is_abandoned= $match['status'] === 'abandoned';
                    $result_class= $is_abandoned ? 'loss' : ($is_win ? 'win' : 'loss');
                    $result_label= $is_abandoned ? 'A' : ($is_win ? 'V' : 'D');
                    $accuracy    = $match['total_questions'] > 0
                                   ? round($match['correct_answers'] * 100 / $match['total_questions'])
                                   : 0;
                    $duration    = gmdate('i\ms\s', (int)$match['time_played']);
                    $date        = date('d/m H\hi', strtotime($match['started_at']));
                    $mode_labels = ['solo'=>'Solo','tournoi'=>'Tournoi','rapidite'=>'Rapidité','buzz'=>'Buzz','1v1'=>'1v1'];
                    $mode        = $mode_labels[$match['game_mode']] ?? $match['game_mode'];
                ?>
                <div class="match-row">
                  <div class="match-result <?= $result_class ?>"><?= $result_label ?></div>
                  <div class="match-info">
                    <div class="match-vs"><?= $mode ?> · <?= htmlspecialchars($match['difficulty'] ?? '') ?></div>
                    <div class="match-map"><?= $date ?> · <?= $duration ?></div>
                  </div>
                  <div class="match-score"><?= number_format($match['score']) ?> pts</div>
                  <div class="match-kda">Précision<br><b><?= $accuracy ?>%</b></div>
                </div>
                <?php } ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- RIGHT: PROFILE + LEADERBOARD -->
      <div class="profile-panel">

        <!-- Profile Card -->
        <div class="profile-card">
          <div class="profile-card-header"></div>
          <div class="profile-avatar-wrap">
            <label for="file-input" class="avatar-lg" title="Changer la photo">
              <div class="avatar-ring-lg"></div>
              <div class="avatar-inner-lg">
                <?php if ($user['profile_pic']): ?>
                  <img id="preview" src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="avatar">
                <?php else: ?>
                  <img id="preview" src="https://api.dicebear.com/7.x/adventurer/svg?seed=<?= urlencode($user['username']) ?>" alt="avatar">
                <?php endif; ?>
              </div>
              <div class="avatar-edit-overlay">Modifier</div>
            </label>
            <input type="file" id="file-input" accept="image/*" style="display:none">
          </div>
          <div class="profile-card-body">
            <div class="profile-name" id="show-name"><?= htmlspecialchars($user['username']) ?></div>
            <div class="profile-email" id="show-email"><?= htmlspecialchars($user['email']) ?></div>
            <hr class="profile-divider">
            <form action="update_profile.php" method="POST" enctype="multipart/form-data" id="profile-form">
              <?= csrf_field() ?>
              <input type="file" name="profile_pic" id="file-input-hidden" style="display:none">
              <div class="form-field">
                <label class="form-label">Nom d'utilisateur</label>
                <input class="form-input" type="text" name="username" id="inp-username"
                       value="<?= htmlspecialchars($user['username']) ?>" autocomplete="username">
              </div>
              <div class="form-field">
                <label class="form-label">Adresse e-mail</label>
                <input class="form-input" type="email" name="email" id="inp-email"
                       value="<?= htmlspecialchars($user['email']) ?>" autocomplete="email">
              </div>
              <div class="form-field">
                <label class="form-label">Pays</label>
                <input class="form-input" type="text" name="pays" id="inp-pays"
                       value="<?= htmlspecialchars($user['pays'] ?? '') ?>" placeholder="Ex: Maroc">
              </div>
              <div class="form-field">
                <label class="form-label">Âge <span style="color:var(--text3);font-size:0.6rem;">(optionnel)</span></label>
                <input class="form-input" type="number" name="age" id="inp-age" min="8" max="120"
                       value="<?= htmlspecialchars($user['age'] ?? '') ?>" placeholder="—">
              </div>
              <button type="submit" class="btn-save">Enregistrer</button>
            </form>
            <hr class="profile-divider">
            <a href="logout.php" class="btn-logout">↩ Se déconnecter</a>
          </div>
        </div>

        <!-- Leaderboard -->
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Classement</div>
            <span style="font-size:0.68rem;color:var(--g500);letter-spacing:1px;">TOP 10</span>
          </div>
          <?php
          $rank_classes = [1 => 'gold', 2 => 'silver', 3 => 'bronze'];
          $user_in_top10 = false;
          foreach ($leaderboard as $lb):
            $rc = $rank_classes[$lb['rank']] ?? 'other';
            $initials = strtoupper(substr($lb['username'], 0, 2));
            $is_me = $lb['id'] == $user_id;
            if ($is_me) $user_in_top10 = true;
          ?>
          <div class="leader-row" <?= $is_me ? 'style="background:rgba(212,175,55,0.04);border:1px solid var(--border2);border-radius:var(--r);margin:4px 10px;"' : '' ?>>
            <div class="leader-rank <?= $rc ?>"><?= $lb['rank'] ?></div>
            <div class="leader-avatar" <?= $is_me ? 'style="background:linear-gradient(135deg,var(--g700),var(--g400));color:#000;"' : '' ?>><?= $initials ?></div>
            <div class="leader-name <?= $is_me ? 'leader-self' : '' ?>">
              <?= htmlspecialchars($lb['username']) ?>
              <?php if ($is_me): ?><span style="color:var(--text3);font-size:0.65rem;"> (vous)</span><?php endif; ?>
            </div>
            <div class="leader-pts"><?= number_format($lb['score_total']) ?></div>
          </div>
          <?php endforeach; ?>

          <?php if (!$user_in_top10 && $global_rank !== '—'): ?>
          <div class="leader-row" style="background:rgba(212,175,55,0.04);border:1px solid var(--border2);border-radius:var(--r);margin:4px 10px;">
            <div class="leader-rank other"><?= $global_rank ?></div>
            <div class="leader-avatar" style="background:linear-gradient(135deg,var(--g700),var(--g400));color:#000;"><?= strtoupper(substr($user['username'], 0, 2)) ?></div>
            <div class="leader-name leader-self"><?= htmlspecialchars($user['username']) ?> <span style="color:var(--text3);font-size:0.65rem;">(vous)</span></div>
            <div class="leader-pts"><?= number_format($best_score) ?></div>
          </div>
          <?php endif; ?>

          <a href="classement.php" style="display:block;text-align:center;padding:14px 10px 6px;font-size:0.72rem;letter-spacing:2px;text-transform:uppercase;color:var(--g400);text-decoration:none;font-weight:600;">Voir le classement complet →</a>
        </div>

      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<script>
/* ── CUSTOM CURSOR ── */
const cur = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove', e => { mx=e.clientX; my=e.clientY; cur.style.left=mx+'px'; cur.style.top=my+'px'; });
(function animRing(){
  rx+=(mx-rx)*0.12; ry+=(my-ry)*0.12;
  ring.style.left=rx+'px'; ring.style.top=ry+'px';
  requestAnimationFrame(animRing);
})();
document.querySelectorAll('a,button,.tab').forEach(el=>{
  el.addEventListener('mouseenter',()=>{ cur.style.width='16px'; cur.style.height='16px'; ring.style.width='48px'; ring.style.height='48px'; ring.style.opacity='0.4'; });
  el.addEventListener('mouseleave',()=>{ cur.style.width='10px'; cur.style.height='10px'; ring.style.width='32px'; ring.style.height='32px'; ring.style.opacity='1'; });
});


/* ── COUNTER ANIMATION ── */
function animCount(el, target, duration, decimals=0, suffix='') {
  if (!el) return;
  let start=0, step=target/((duration/16));
  let t=setInterval(()=>{
    start+=step;
    if(start>=target){ start=target; clearInterval(t); }
    el.textContent = decimals>0 ? start.toFixed(decimals)+suffix : Math.floor(start).toLocaleString()+suffix;
  },16);
}
setTimeout(()=>{
  animCount(document.getElementById('cnt-games'), <?= $total_games_all ?>, 1200);
  animCount(document.getElementById('cnt-wins'),  <?= $victories_all ?>, 1200);
  animCount(document.getElementById('cnt-elo'),   <?= $user_elo ?>, 1400);
},300);

/* ── PROGRESS BARS ── */
setTimeout(()=>{
  document.querySelectorAll('.prog-fill').forEach(bar=>{
    bar.style.width = bar.dataset.w + '%';
  });
},600);

/* ── AVATAR PREVIEW + sync avec le formulaire ── */
document.getElementById('file-input').addEventListener('change', e=>{
  const f = e.target.files[0];
  if (!f) return;
  document.getElementById('preview').src = URL.createObjectURL(f);
  // Transférer le fichier vers l'input caché du vrai formulaire
  const dt = new DataTransfer();
  dt.items.add(f);
  document.getElementById('file-input-hidden').files = dt.files;
});

/* ── CHART.JS SETUP ── */
Chart.defaults.color = 'rgba(240,232,204,0.45)';
Chart.defaults.font.family = "'Raleway', sans-serif";
Chart.defaults.font.size = 11;

const goldPlugin = {
  id:'goldGrad',
  beforeDraw(chart){
    const ctx=chart.ctx, area=chart.chartArea;
    if(!area) return;
    const grad=ctx.createLinearGradient(0,area.top,0,area.bottom);
    grad.addColorStop(0,'rgba(212,175,55,0.35)');
    grad.addColorStop(0.5,'rgba(212,175,55,0.10)');
    grad.addColorStop(1,'rgba(212,175,55,0.01)');
    chart.data.datasets[0]._gradFill = grad;
  }
};

/* Performance Line Chart — données réelles */
const perfData = {
  week: {
    labels: <?= json_encode(count($chart_week['labels'])  > 0 ? $chart_week['labels']  : ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim']) ?>,
    wins:   <?= json_encode(count($chart_week['parties']) > 0 ? $chart_week['parties'] : [0,0,0,0,0,0,0]) ?>,
    elo:    <?= json_encode(count($chart_week['scores'])  > 0 ? $chart_week['scores']  : [0,0,0,0,0,0,0]) ?>
  },
  month: {
    labels: <?= json_encode(count($chart_month['labels'])  > 0 ? $chart_month['labels']  : ['S1','S2','S3','S4']) ?>,
    wins:   <?= json_encode(count($chart_month['parties']) > 0 ? $chart_month['parties'] : [0,0,0,0]) ?>,
    elo:    <?= json_encode(count($chart_month['scores'])  > 0 ? $chart_month['scores']  : [0,0,0,0]) ?>
  },
  season: {
    labels: <?= json_encode(count($chart_season['labels'])  > 0 ? $chart_season['labels']  : ['Jan','Fév','Mar','Avr','Mai','Jun']) ?>,
    wins:   <?= json_encode(count($chart_season['parties']) > 0 ? $chart_season['parties'] : [0,0,0,0,0,0]) ?>,
    elo:    <?= json_encode(count($chart_season['scores'])  > 0 ? $chart_season['scores']  : [0,0,0,0,0,0]) ?>
  }
};

const perfCtx = document.getElementById('perfChart').getContext('2d');
const perfChart = new Chart(perfCtx, {
  type:'line',
  plugins:[goldPlugin],
  data:{
    labels: perfData.week.labels,
    datasets:[
      {
        label:'Parties jouées',
        data: perfData.week.wins,
        borderColor: '#d4af37',
        borderWidth: 2.5,
        pointBackgroundColor: '#d4af37',
        pointBorderColor: '#060606',
        pointBorderWidth: 2,
        pointRadius: 5,
        tension: 0.4,
        fill: true,
        backgroundColor: ctx => {
          const grad = ctx.chart.ctx.createLinearGradient(0,0,0,ctx.chart.height);
          grad.addColorStop(0,'rgba(212,175,55,0.3)');
          grad.addColorStop(1,'rgba(212,175,55,0.01)');
          return grad;
        },
        yAxisID:'y'
      },
      {
        label:'Meilleur score',
        data: perfData.week.elo,
        borderColor: '#5599dd',
        borderWidth: 2,
        pointBackgroundColor: '#5599dd',
        pointBorderColor: '#060606',
        pointBorderWidth: 2,
        pointRadius: 4,
        tension: 0.4,
        fill: false,
        yAxisID:'y1',
        borderDash:[5,3]
      }
    ]
  },
  options:{
    responsive:true, maintainAspectRatio:false,
    interaction:{ mode:'index', intersect:false },
    plugins:{
      legend:{
        display:true,
        labels:{ color:'rgba(240,232,204,0.5)', boxWidth:12, padding:16, font:{size:11} }
      },
      tooltip:{
        backgroundColor:'rgba(13,13,13,0.95)',
        borderColor:'rgba(212,175,55,0.3)',
        borderWidth:1,
        titleColor:'#f0e8cc',
        bodyColor:'rgba(240,232,204,0.7)',
        padding:12,
        callbacks:{
          title: items => items[0].label,
          label: item => item.datasetIndex===0
            ? ` Parties : ${item.raw}`
            : ` Meilleur score : ${item.raw.toLocaleString()} pts`
        }
      }
    },
    scales:{
      x:{ grid:{ color:'rgba(212,175,55,0.06)' }, ticks:{ color:'rgba(240,232,204,0.4)' } },
      y:{
        position:'left',
        grid:{ color:'rgba(212,175,55,0.06)' },
        ticks:{ color:'rgba(240,232,204,0.4)', stepSize:2 }
      },
      y1:{
        position:'right',
        grid:{ drawOnChartArea:false },
        ticks:{ color:'rgba(85,153,221,0.6)' }
      }
    }
  }
});

/* Tab switching */
function switchTab(el, key) {
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  const d = perfData[key];
  perfChart.data.labels = d.labels;
  perfChart.data.datasets[0].data = d.wins;
  perfChart.data.datasets[1].data = d.elo;
  perfChart.update('active');
}

/* Radar Chart — données réelles par catégorie */
const radarCtx = document.getElementById('radarChart').getContext('2d');
const radarLabels = <?= json_encode(count($cat_labels) > 0 ? $cat_labels : ['Histoire','Géographie','Sciences','Sport','Culture','Technologie']) ?>;
const radarData   = <?= json_encode(count($cat_values) > 0 ? $cat_values : [0,0,0,0,0,0]) ?>;
const avgLine     = new Array(radarLabels.length).fill(50); // ligne de référence à 50%

const radarChart = new Chart(radarCtx,{
  type:'radar',
  data:{
    labels: radarLabels,
    datasets:[{
      label:'<?= addslashes($user['username']) ?>',
      data: radarData,
      borderColor:'rgba(212,175,55,0.9)',
      borderWidth:2,
      backgroundColor:'rgba(212,175,55,0.12)',
      pointBackgroundColor:'#d4af37',
      pointBorderColor:'#060606',
      pointRadius:5,
      pointBorderWidth:2,
    },{
      label:'Référence 50%',
      data: avgLine,
      borderColor:'rgba(85,153,221,0.4)',
      borderWidth:1.5,
      backgroundColor:'rgba(85,153,221,0.05)',
      pointRadius:0,
      borderDash:[4,4],
    }]
  },
  options:{
    responsive:true, maintainAspectRatio:false,
    plugins:{
      legend:{
        display:true,
        labels:{ color:'rgba(240,232,204,0.45)', boxWidth:10, padding:10, font:{size:10} }
      }
    },
    scales:{
      r:{
        grid:{ color:'rgba(212,175,55,0.1)' },
        angleLines:{ color:'rgba(212,175,55,0.1)' },
        ticks:{ display:false, stepSize:20 },
        pointLabels:{ color:'rgba(240,232,204,0.55)', font:{size:10,family:"'Raleway',sans-serif"} },
        min:0, max:100
      }
    }
  }
});

/* ── THÈME CLAIR/SOMBRE + adaptation des graphs ── */
function themeColors(){
  const light = document.documentElement.classList.contains('light');
  return {
    text:        light ? 'rgba(26,20,8,0.62)'   : 'rgba(240,232,204,0.5)',
    textSoft:    light ? 'rgba(26,20,8,0.5)'    : 'rgba(240,232,204,0.4)',
    grid:        light ? 'rgba(138,110,47,0.14)': 'rgba(212,175,55,0.06)',
    gridR:       light ? 'rgba(138,110,47,0.2)' : 'rgba(212,175,55,0.1)',
    pointBorder: light ? '#ffffff'              : '#060606',
    tipBg:       light ? 'rgba(255,255,255,0.97)':'rgba(13,13,13,0.95)',
    tipTitle:    light ? '#1a1408'              : '#f0e8cc',
    tipBody:     light ? 'rgba(26,20,8,0.7)'    : 'rgba(240,232,204,0.7)',
    blueTick:    light ? 'rgba(60,110,170,0.85)': 'rgba(85,153,221,0.6)',
  };
}
function applyChartTheme(){
  const c = themeColors();
  Chart.defaults.color = c.text;
  if (typeof perfChart !== 'undefined' && perfChart){
    const o = perfChart.options;
    o.plugins.legend.labels.color = c.text;
    o.plugins.tooltip.backgroundColor = c.tipBg;
    o.plugins.tooltip.titleColor = c.tipTitle;
    o.plugins.tooltip.bodyColor  = c.tipBody;
    o.scales.x.grid.color = c.grid;  o.scales.x.ticks.color = c.textSoft;
    o.scales.y.grid.color = c.grid;  o.scales.y.ticks.color = c.textSoft;
    o.scales.y1.ticks.color = c.blueTick;
    perfChart.data.datasets[0].pointBorderColor = c.pointBorder;
    perfChart.data.datasets[1].pointBorderColor = c.pointBorder;
    perfChart.update('none');
  }
  if (typeof radarChart !== 'undefined' && radarChart){
    const o = radarChart.options;
    o.plugins.legend.labels.color = c.text;
    o.scales.r.grid.color        = c.gridR;
    o.scales.r.angleLines.color  = c.gridR;
    o.scales.r.pointLabels.color = c.text;
    radarChart.data.datasets[0].pointBorderColor = c.pointBorder;
    radarChart.update('none');
  }
}
window.applyChartTheme = applyChartTheme;
applyChartTheme();

(function(){
  const root = document.documentElement;
  const toggle = document.getElementById('theme-toggle');
  if(!toggle) return;
  toggle.addEventListener('click', ()=>{
    root.classList.add('theme-transitioning');
    const isLight = root.classList.toggle('light');
    try{ localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); }catch(e){}
    applyChartTheme();
    setTimeout(()=>root.classList.remove('theme-transitioning'), 300);
  });
})();

/* ── BURGER DRAWER (même comportement que index.php) ── */
(function () {
  const trigger  = document.getElementById('burger-trigger');
  const closeBtn = document.getElementById('burger-close');
  const menu     = document.getElementById('mobile-menu');
  const backdrop = document.getElementById('mobile-menu-backdrop');
  if (!trigger || !menu) return;

  function openMenu() {
    menu.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    trigger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    menu.classList.remove('is-open');
    menu.setAttribute('aria-hidden', 'true');
    trigger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  trigger.addEventListener('click', openMenu);
  closeBtn.addEventListener('click', closeMenu);
  backdrop.addEventListener('click', closeMenu);
  menu.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeMenu));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) closeMenu();
  });
})();
</script>
</body>
</html>