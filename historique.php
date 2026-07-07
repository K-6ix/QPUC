<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ════════════════════════════════════════════════════════════
// HISTORIQUE COMPLET — toutes les parties du joueur connecté
// game_sessions (classic : 1v1 / solo / etc.) + championship_matches
// Filtres : Tous · Classé · Amical · Championnat   |   Pagination
//
// ⚠️ Robustesse : la colonne game_sessions.is_ranked peut ne pas
// encore exister (flag friendly pas encore persisté côté server.js).
// On la détecte au runtime — la page marche AVANT et APRÈS l'ajout.
// ════════════════════════════════════════════════════════════

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

// ── Détection colonne is_ranked (voir note ci-dessus) ────────
$has_ranked_col = false;
$colCheck = $conn->query("SHOW COLUMNS FROM game_sessions LIKE 'is_ranked'");
if ($colCheck && $colCheck->num_rows > 0) {
    $has_ranked_col = true;
}
// Expression SQL sûre (jamais d'entrée utilisateur ici)
$ranked_expr = $has_ranked_col ? 'is_ranked' : '1';

// ── Filtre (whitelist stricte → pas d'injection) ─────────────
$filter = $_GET['filter'] ?? 'all';
$filter_map = [
    'all'      => '1=1',
    'ranked'   => 'h.is_ranked = 1',
    'friendly' => 'h.is_ranked = 0',
    'champ'    => "h.source = 'champ'",
];
if (!isset($filter_map[$filter])) $filter = 'all';
$filter_sql = $filter_map[$filter];

// ── Pagination ───────────────────────────────────────────────
$per_page = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// ── Helper bind_param dynamique (params par référence) ───────
function bind_all(mysqli_stmt $stmt, string $types, array $vals): void {
    $refs = [$types];
    foreach ($vals as $k => $v) { $refs[] = &$vals[$k]; }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

// UNION commun (13 fois user_id) — utilisé pour COUNT et pour les données
$union = "
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
        NULL             AS room_code,
        {$ranked_expr}   AS is_ranked
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
        room_code,
        1                AS is_ranked
     FROM championship_matches
     WHERE p1_id = ? OR p2_id = ? OR p3_id = ? OR p4_id = ?)
";

// ── Total (pour la pagination) ───────────────────────────────
$total_count = 0;
$count_sql = "SELECT COUNT(*) AS c FROM ({$union}) AS h WHERE {$filter_sql}";
$cstmt = $conn->prepare($count_sql);
if ($cstmt) {
    bind_all($cstmt, str_repeat('i', 13), array_fill(0, 13, $user_id));
    $cstmt->execute();
    $total_count = (int) ($cstmt->get_result()->fetch_assoc()['c'] ?? 0);
}
$total_pages = max(1, (int) ceil($total_count / $per_page));
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $per_page; }

// ── Données de la page ───────────────────────────────────────
$history = [];
$data_sql = "SELECT * FROM ({$union}) AS h WHERE {$filter_sql}
             ORDER BY started_at DESC LIMIT ? OFFSET ?";
$dstmt = $conn->prepare($data_sql);
if ($dstmt) {
    bind_all(
        $dstmt,
        str_repeat('i', 15),
        array_merge(array_fill(0, 13, $user_id), [$per_page, $offset])
    );
    $dstmt->execute();
    $history = $dstmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Petit lien qui garde le filtre courant
function page_link(int $p, string $filter): string {
    return 'historique.php?filter=' . urlencode($filter) . '&page=' . $p;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<script>try{if(localStorage.getItem('qpc-theme')==='light')document.documentElement.classList.add('light');}catch(e){}</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QPC — Historique des parties</title>

<link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@400;700;900&family=Cinzel:wght@400;600;700&family=Raleway:wght@300;400;500;600;700&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">

<style>
  :root {
    --g300: #f0d060;
    --g400: #d4af37;
    --g500: #b8902a;
    --g700: #7a5c14;

    --bg:       #060606;
    --bg2:      #131313;
    --card:     #131313;
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

    /* Tokens header */
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

  /* ═══ MODE CLAIR (clé partagée qpc-theme) ═══ */
  html.light {
    --bg:       #ffffff;
    --bg2:      #f4f2ec;
    --card:     #f4f2ec;
    --border:   rgba(138,110,47,0.22);
    --border2:  rgba(138,110,47,0.42);
    --text:     #1a1408;
    --text2:    rgba(26,20,8,0.62);
    --text3:    rgba(26,20,8,0.5);
    --g300:     #9a7815;                 /* badges/scores or lisibles sur blanc */
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
  html.light .page-eyebrow { color: var(--gold-dark); }  /* eyebrow lisible sur blanc */
  html.light body::before {
    background:
      radial-gradient(ellipse at 20% 0%, rgba(212,175,55,0.09), transparent 55%),
      radial-gradient(ellipse at 80% 100%, rgba(212,175,55,0.06), transparent 55%);
  }
  html.theme-transitioning, html.theme-transitioning * {
    transition: background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease !important;
  }

  *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
  html { scroll-behavior:smooth; }
  body {
    font-family: 'Raleway', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    padding-top: 72px;
    overflow-x: hidden;
  }

  /* Fond doré discret */
  body::before {
    content:'';
    position: fixed;
    inset: 0;
    background:
      radial-gradient(ellipse at 20% 0%, rgba(212,175,55,0.06), transparent 55%),
      radial-gradient(ellipse at 80% 100%, rgba(212,175,55,0.04), transparent 55%);
    pointer-events: none;
    z-index: 0;
  }

  /* ════ HEADER (même header que le reste du site) ════ */
  header {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    display: grid; grid-template-columns: 30% 50% 20%; align-items: center;
    padding: 0 40px; height: 72px;
    border-bottom: 1px solid var(--gold-line);
    background: var(--header-bg);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    opacity: 0; animation: slideDown 0.8s cubic-bezier(0.2,0.8,0.2,1) 0.2s forwards;
  }
  .logo {
    font-family: 'Kanit', sans-serif; font-weight: 900; font-size: 1.1rem; letter-spacing: 3px;
    background: var(--metallic); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; text-transform: uppercase;
    filter: drop-shadow(0 0 6px var(--gold-glow)); text-decoration: none; justify-self: start;
  }
  header nav ul { display: flex; list-style: none; gap: 28px; align-items: center; justify-content: center; }
  header nav a {
    text-decoration: none; color: var(--ink-2); font-size: 0.78rem; font-weight: 700;
    letter-spacing: 2px; text-transform: uppercase; position: relative; transition: color 0.3s;
    font-family: 'Montserrat', sans-serif;
  }
  header nav a:hover { color: var(--gold-text); }
  header nav a::after { content:''; position:absolute; width:0; height:1px; bottom:-4px; left:0; background: var(--metallic); transition: width 0.3s; }
  header nav a:hover::after { width:100%; }
  .btn-play {
    background: var(--metallic); color: var(--on-gold) !important; -webkit-text-fill-color: var(--on-gold) !important;
    padding: 7px 22px; border-radius: 30px; font-weight: 900; border: 1px solid var(--gold-base);
    box-shadow: 0 0 12px var(--gold-glow); transition: transform 0.2s, box-shadow 0.2s;
  }
  .btn-play:hover { transform: scale(1.05); box-shadow: 0 0 22px rgba(212,175,55,0.7); }
  .btn-play::after { display: none; }
  .header-right { justify-self: end; display: flex; align-items: center; gap: 12px; }
  .icon-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px;
    border-radius: 50%; border: 1px solid var(--gold-line-strong); background: transparent; color: var(--ink);
    cursor: pointer; transition: border-color 0.25s, color 0.25s, transform 0.2s, background 0.25s; flex-shrink: 0; padding: 0;
  }
  .icon-btn:hover { border-color: var(--gold-base); color: var(--gold-text); background: var(--gold-tint); }
  .icon-btn:active { transform: scale(0.95); }
  .icon-btn svg { width: 15px; height: 15px; }
  #theme-toggle .theme-moon { display: none; }
  #theme-toggle .theme-sun  { display: block; }
  html.light #theme-toggle .theme-moon { display: block; }
  html.light #theme-toggle .theme-sun  { display: none; }
  .btn-connexion {
    background: transparent; border: 1px solid var(--gold-line-strong); color: var(--gold-text);
    padding: 7px 22px; border-radius: 30px; font-size: 0.78rem; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; text-decoration: none; transition: all 0.3s; white-space: nowrap; font-family: 'Montserrat', sans-serif;
  }
  .btn-connexion:hover {
    background: var(--metallic); color: var(--on-gold); -webkit-text-fill-color: var(--on-gold);
    border-color: transparent; box-shadow: 0 0 18px var(--gold-glow);
  }
  #burger-trigger { display: none; }
  @keyframes slideDown { from { transform: translateY(-100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

  /* Drawer mobile */
  #mobile-menu { position: fixed; inset: 0; z-index: 200; visibility: hidden; pointer-events: none; }
  #mobile-menu.is-open { visibility: visible; pointer-events: auto; }
  #mobile-menu-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.3s ease; }
  #mobile-menu.is-open #mobile-menu-backdrop { opacity: 1; }
  #mobile-menu-panel {
    position: absolute; right: 0; top: 0; height: 100%; width: 75%; max-width: 360px;
    background: var(--bg); border-left: 1px solid var(--gold-line); display: flex; flex-direction: column;
    transform: translateX(100%); transition: transform 0.35s cubic-bezier(0.4,0,0.2,1); box-shadow: -10px 0 40px rgba(0,0,0,0.4);
  }
  #mobile-menu.is-open #mobile-menu-panel { transform: translateX(0); }
  .drawer-header { display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 72px; border-bottom: 1px solid var(--gold-line); }
  .drawer-nav { flex: 1; padding: 32px 24px; display: flex; flex-direction: column; gap: 4px; }
  .drawer-section-label { font-family: 'Montserrat', sans-serif; font-size: 0.6rem; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; color: var(--gold-text); margin-bottom: 18px; }
  .drawer-link {
    display: flex; align-items: center; justify-content: space-between; padding: 18px 0; border-bottom: 1px solid var(--line-soft);
    text-decoration: none; color: var(--ink); font-size: 1rem; font-weight: 700; letter-spacing: 1px;
    transition: color 0.25s, padding-left 0.25s; font-family: 'Montserrat', sans-serif;
  }
  .drawer-link:hover { color: var(--gold-text); padding-left: 6px; }
  .drawer-link svg { width: 16px; height: 16px; color: var(--ink-4); transition: color 0.25s, transform 0.25s; }
  .drawer-link:hover svg { color: var(--gold-text); transform: translateX(4px); }
  .drawer-footer { padding: 24px; display: flex; flex-direction: column; gap: 12px; border-top: 1px solid var(--line-soft); }
  .drawer-cta { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 24px; border-radius: 40px; font-weight: 900; font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; font-family: 'Montserrat', sans-serif; }
  .drawer-cta.primary { background: var(--metallic); color: var(--on-gold); border: 1px solid var(--gold-base); box-shadow: 0 0 12px var(--gold-glow); }
  .drawer-cta.primary:hover { transform: translateY(-2px); }
  .drawer-cta.secondary { background: transparent; border: 1px solid var(--gold-line-strong); color: var(--gold-text); }
  .drawer-cta.secondary:hover { background: var(--gold-tint); border-color: var(--gold-base); }
  .drawer-copy { text-align: center; font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: var(--ink-4); margin-top: 8px; }

  /* ════ CONTENU ════ */
  .wrap { position: relative; z-index: 1; max-width: 860px; margin: 0 auto; padding: 48px 24px 80px; }

  .page-head { text-align: center; margin-bottom: 40px; }
  .page-eyebrow { font-family:'Montserrat',sans-serif; font-size: 0.62rem; font-weight: 700; letter-spacing: 5px; text-transform: uppercase; color: var(--g400); margin-bottom: 12px; }
  .page-title { font-family:'Cinzel Decorative', serif; font-size: clamp(1.7rem, 4vw, 2.6rem); font-weight: 900; background: var(--metallic); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  .page-sub { margin-top: 10px; font-size: 0.8rem; color: var(--text2); letter-spacing: 1px; }

  /* Filtres */
  .filters { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-bottom: 28px; }
  .filter-tab {
    padding: 9px 20px; border-radius: 30px; border: 1px solid var(--border);
    background: rgba(255,255,255,0.02); color: var(--text2);
    font-family:'Montserrat',sans-serif; font-size: 0.72rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
    text-decoration: none; transition: all 0.25s; white-space: nowrap;
  }
  .filter-tab:hover { border-color: var(--border2); color: var(--text); }
  .filter-tab.active { background: var(--metallic); color: var(--on-gold); border-color: transparent; box-shadow: 0 0 14px var(--gold-glow); }

  /* Panneau liste */
  .panel { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--r2); overflow: hidden; }

  .match-row { display: flex; align-items: center; gap: 14px; padding: 15px 22px; border-bottom: 1px solid var(--border); transition: background 0.2s; }
  .match-row:last-child { border-bottom:none; }
  .match-row:hover { background: rgba(212,175,55,0.03); }

  .match-result { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items:center; justify-content:center; font-family:'Cinzel',serif; font-size:0.75rem; font-weight:700; flex-shrink:0; }
  .match-result.win  { background:rgba(76,175,120,0.15); color:var(--green); border:1px solid rgba(76,175,120,0.25); }
  .match-result.loss { background:rgba(224,85,85,0.12);  color:var(--red);   border:1px solid rgba(224,85,85,0.2); }
  .match-result.draw { background:rgba(85,153,221,0.12); color:var(--blue);  border:1px solid rgba(85,153,221,0.2); }

  .match-info { flex:1; min-width:0; }
  .match-vs { font-size:0.86rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:flex; align-items:center; gap:8px; }
  .match-map { font-size:0.65rem; color:var(--text3); margin-top:3px; text-transform:uppercase; letter-spacing:1px; }

  .tag { font-family:'Montserrat',sans-serif; font-size:0.55rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; padding:2px 8px; border-radius:20px; flex-shrink:0; }
  .tag-ranked   { background:rgba(212,175,55,0.12); color:var(--g300); border:1px solid var(--border2); }
  .tag-friendly { background:rgba(240,232,204,0.06); color:var(--text2); border:1px solid var(--line-soft); }

  .match-score { font-family:'Cinzel',serif; font-size:0.92rem; font-weight:600; color:var(--g300); flex-shrink:0; }
  .match-kda { font-size:0.65rem; color:var(--text2); flex-shrink:0; text-align:right; min-width:58px; }
  .match-kda b { color: var(--text); }

  /* Vide */
  .empty-state { text-align:center; padding: 60px 24px; color: var(--text3); }
  .empty-state .ico { font-size: 2.4rem; margin-bottom: 14px; opacity: 0.5; }
  .empty-state p { font-size: 0.9rem; line-height: 1.6; color: var(--text2); }
  .empty-state small { display:block; margin-top:10px; font-size:0.72rem; color: var(--text3); }

  /* Pagination */
  .pager { display:flex; align-items:center; justify-content:center; gap:16px; margin-top: 28px; }
  .pager a, .pager span.disabled {
    display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:30px;
    border:1px solid var(--border); font-family:'Montserrat',sans-serif; font-size:0.72rem; font-weight:700;
    letter-spacing:1px; text-transform:uppercase; text-decoration:none; color:var(--text2); transition: all 0.25s;
  }
  .pager a:hover { border-color: var(--gold-base); color: var(--gold-text); background: var(--gold-tint); }
  .pager span.disabled { opacity: 0.3; cursor: not-allowed; }
  .pager .pos { border:none; color: var(--text3); letter-spacing:2px; }

  .back-dash { display:inline-flex; align-items:center; gap:8px; margin-bottom: 24px; color: var(--text2); text-decoration:none; font-size:0.78rem; font-weight:600; letter-spacing:1px; transition: color 0.25s; }
  .back-dash:hover { color: var(--gold-text); }

  @media (max-width: 1024px) { header { grid-template-columns: auto 1fr auto; padding: 0 28px; gap: 20px; } }
  @media (max-width: 900px) {
    header { padding: 0 20px; grid-template-columns: 1fr auto; }
    header > nav, .header-right .btn-connexion { display: none; }
    #burger-trigger { display: inline-flex; }
  }
  @media (max-width: 600px) {
    header { padding: 0 16px; height: 64px; }
    .logo { font-size: 0.95rem; letter-spacing: 2px; }
    .icon-btn { width: 34px; height: 34px; }
    body { padding-top: 64px; }
    .match-kda { display:none; }
    .match-row { padding: 14px 16px; gap: 12px; }
    .wrap { padding: 32px 16px 60px; }
  }
</style>
</head>
<body>

<!-- ════ HEADER ════ -->
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
    <a href="dashboard.php" class="btn-connexion">Dashboard</a>
    <button id="burger-trigger" class="icon-btn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobile-menu" type="button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line>
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
          <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <nav class="drawer-nav">
      <span class="drawer-section-label">Navigation</span>
      <a href="dashboard.php" data-close class="drawer-link"><span>Dashboard</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
      <a href="index.php" data-close class="drawer-link"><span>Home</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
      <a href="rules.php" data-close class="drawer-link"><span>Rules</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
      <a href="classement.php" data-close class="drawer-link"><span>Classement</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
      <a href="aboutus.php" data-close class="drawer-link"><span>About Us</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </nav>
    <div class="drawer-footer">
      <a href="game.php" data-close class="drawer-cta primary">▶ Jouer</a>
      <a href="logout.php" data-close class="drawer-cta secondary">Déconnexion</a>
      <p class="drawer-copy">&copy; 2025 &middot; HESTIM</p>
    </div>
  </aside>
</div>

<div class="wrap">
  <a href="dashboard.php" class="back-dash">← Retour au dashboard</a>

  <div class="page-head">
    <div class="page-eyebrow">Saison 1 · 2026</div>
    <h1 class="page-title">Historique des Parties</h1>
    <div class="page-sub"><?= $total_count ?> partie<?= $total_count > 1 ? 's' : '' ?> au total</div>
  </div>

  <!-- Filtres -->
  <div class="filters">
    <?php
    $tabs = ['all' => 'Tous', 'ranked' => 'Classé', 'friendly' => 'Amical', 'champ' => 'Championnat'];
    foreach ($tabs as $key => $label):
        $active = $filter === $key ? ' active' : '';
    ?>
      <a class="filter-tab<?= $active ?>" href="historique.php?filter=<?= $key ?>&page=1"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($history)): ?>
    <div class="panel">
      <div class="empty-state">
        <div class="ico">🎯</div>
        <?php if ($filter === 'friendly'): ?>
          <p>Aucune partie amicale enregistrée pour l'instant.</p>
          <?php if (!$has_ranked_col): ?>
            <small>Les parties amicales seront listées ici une fois l'enregistrement activé côté serveur.</small>
          <?php endif; ?>
        <?php elseif ($filter === 'champ'): ?>
          <p>Aucun championnat joué pour l'instant.</p>
          <small>Lance une partie en mode Championnat pour remplir cette liste.</small>
        <?php else: ?>
          <p>Aucune partie jouée pour l'instant.</p>
          <small>Tes duels et championnats apparaîtront ici.</small>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>

  <div class="panel">
    <?php foreach ($history as $match):
        $source    = $match['source'] ?? 'classic';
        $is_ranked = (int) ($match['is_ranked'] ?? 1);

        if ($source === 'champ') {
            $rank         = (int) ($match['champ_rank'] ?? 0);
            $elo_d        = (int) ($match['champ_elo_delta'] ?? 0);
            $is_win       = ($rank === 1);
            $result_class = $is_win ? 'win' : 'loss';
            $rank_label   = $rank === 1 ? '1er 🏆' : ($rank === 2 ? '2ème' : ($rank === 3 ? '3ème' : '4ème'));
            $result_label = $is_win ? 'V' : 'D';
            $date         = date('d/m/y · H\\hi', strtotime($match['started_at']));
            $score_disp   = ($elo_d >= 0 ? '+' : '') . $elo_d . ' ELO';
    ?>
      <div class="match-row" style="border-left: 3px solid #d4af37;">
        <div class="match-result <?= $result_class ?>"><?= $result_label ?></div>
        <div class="match-info">
          <div class="match-vs">👑 Championnat · <?= $rank_label ?><span class="tag tag-ranked">Classé</span></div>
          <div class="match-map"><?= $date ?> · Room <?= htmlspecialchars($match['room_code'] ?? '-') ?></div>
        </div>
        <div class="match-score" style="color:#d4af37"><?= $score_disp ?></div>
        <div class="match-kda">Rang<br><b>#<?= $rank ?></b></div>
      </div>
    <?php } else {
            $status       = $match['status'] ?? '';
            $total_q      = (int) ($match['total_questions'] ?? 0);
            $correct      = (int) ($match['correct_answers'] ?? 0);
            $is_abandoned = $status === 'abandoned';
            $is_win       = $status === 'finished' && $correct >= ($total_q / 2);
            $result_class = $is_abandoned ? 'loss' : ($is_win ? 'win' : 'loss');
            $result_label = $is_abandoned ? 'A' : ($is_win ? 'V' : 'D');
            $accuracy     = $total_q > 0 ? round($correct * 100 / $total_q) : 0;
            $duration     = gmdate('i\\ms\\s', (int) $match['time_played']);
            $date         = date('d/m/y · H\\hi', strtotime($match['started_at']));
            $mode_labels  = ['solo'=>'Solo','tournoi'=>'Tournoi','rapidite'=>'Rapidité','buzz'=>'Buzz','1v1'=>'1v1'];
            $mode         = $mode_labels[$match['game_mode']] ?? $match['game_mode'];
    ?>
      <div class="match-row">
        <div class="match-result <?= $result_class ?>"><?= $result_label ?></div>
        <div class="match-info">
          <div class="match-vs">
            <?= htmlspecialchars($mode) ?> · <?= htmlspecialchars($match['difficulty'] ?? '') ?>
            <?php if ($is_ranked): ?>
              <span class="tag tag-ranked">Classé</span>
            <?php else: ?>
              <span class="tag tag-friendly">Amical</span>
            <?php endif; ?>
          </div>
          <div class="match-map"><?= $date ?> · <?= $duration ?></div>
        </div>
        <div class="match-score"><?= number_format((int) $match['score']) ?> pts</div>
        <div class="match-kda">Précision<br><b><?= $accuracy ?>%</b></div>
      </div>
    <?php } ?>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?>
      <a href="<?= page_link($page - 1, $filter) ?>">← Précédent</a>
    <?php else: ?>
      <span class="disabled">← Précédent</span>
    <?php endif; ?>

    <span class="pos">Page <?= $page ?> / <?= $total_pages ?></span>

    <?php if ($page < $total_pages): ?>
      <a href="<?= page_link($page + 1, $filter) ?>">Suivant →</a>
    <?php else: ?>
      <span class="disabled">Suivant →</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<script>
// ── Thème clair/sombre (clé partagée qpc-theme) ──
(function(){
  const root = document.documentElement;
  const toggle = document.getElementById('theme-toggle');
  if(!toggle) return;
  toggle.addEventListener('click', ()=>{
    root.classList.add('theme-transitioning');
    const isLight = root.classList.toggle('light');
    try{ localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); }catch(e){}
    setTimeout(()=>root.classList.remove('theme-transitioning'), 300);
  });
})();

// ── Burger drawer ──
(function () {
  const trigger  = document.getElementById('burger-trigger');
  const closeBtn = document.getElementById('burger-close');
  const menu     = document.getElementById('mobile-menu');
  const backdrop = document.getElementById('mobile-menu-backdrop');
  if (!trigger || !menu) return;
  function openMenu() { menu.classList.add('is-open'); menu.setAttribute('aria-hidden','false'); trigger.setAttribute('aria-expanded','true'); document.body.style.overflow='hidden'; }
  function closeMenu() { menu.classList.remove('is-open'); menu.setAttribute('aria-hidden','true'); trigger.setAttribute('aria-expanded','false'); document.body.style.overflow=''; }
  trigger.addEventListener('click', openMenu);
  closeBtn.addEventListener('click', closeMenu);
  backdrop.addEventListener('click', closeMenu);
  menu.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeMenu));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && menu.classList.contains('is-open')) closeMenu(); });
})();
</script>
</body>
</html>
