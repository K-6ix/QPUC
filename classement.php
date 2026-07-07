<?php
require_once __DIR__ . '/csrf.php';
require "db.php";

// ════════════════════════════════════════════════════════════
// CLASSEMENT GÉNÉRAL — liste plate classée par ELO
// Page publique : consultable sans compte. Si connecté, la ligne
// du joueur est mise en avant + chip "Ta position".
// Source : player_stats (elo, victoires, parties) JOIN users.
// Le rang est calculé ici (ordre ELO), indépendamment de la vue
// SQL `leaderboard` qui classe par score_total.
// ════════════════════════════════════════════════════════════

$viewer_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

$players = [];
$res = $conn->query("
    SELECT u.id, u.username, ps.elo, ps.total_games, ps.victories
    FROM player_stats ps
    JOIN users u ON u.id = ps.user_id
    ORDER BY ps.elo DESC, ps.victories DESC, u.username ASC
");
if ($res) {
    $players = $res->fetch_all(MYSQLI_ASSOC);
}

// Rang + V/D + winrate calculés côté PHP (formats maîtrisés)
$me = null;
foreach ($players as $i => &$p) {
    $p['rank']     = $i + 1;
    $p['elo']      = (int) $p['elo'];
    $p['games']    = (int) $p['total_games'];
    $p['wins']     = (int) $p['victories'];
    $p['losses']   = max(0, $p['games'] - $p['wins']);
    $p['winrate']  = $p['games'] > 0 ? (int) round($p['wins'] * 100 / $p['games']) : 0;
    $p['initials'] = mb_strtoupper(mb_substr($p['username'], 0, 2, 'UTF-8'), 'UTF-8');
    if ((int) $p['id'] === $viewer_id) $me = $p;
}
unset($p);

// Division (info par ligne — pas de regroupement)
function qpc_division(int $elo): array {
    if ($elo < 1200) return ['Filet',  'filet'];
    if ($elo < 1500) return ['Div 3',  'd3'];
    if ($elo < 1800) return ['Div 2',  'd2'];
    if ($elo < 2000) return ['Div 1',  'd1'];
    return               ['Élite 👑', 'elite'];
}

$podium = array_slice($players, 0, 3);
$total  = count($players);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QPC — Classement général</title>

<!-- Anti-flash thème (convention projet) -->
<script>
    try { if (localStorage.getItem('qpc-theme') === 'light') document.documentElement.classList.add('light'); } catch (e) {}
</script>

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cinzel+Decorative:wght@700;900&family=Raleway:wght@300;400;500;600;700&family=Kanit:ital,wght@1,900&family=Montserrat:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="classement.css">

<!-- ════ HEADER partagé (même header que index.php) — CSS scopé ════ -->
<style>
  /* Tokens scopés au header/drawer : aucun conflit avec classement.css */
  header, #mobile-menu {
    --gold-light: #fcf6ba;
    --gold-base:  #d4af37;
    --gold-dark:  #8a6e2f;
    --gold-glow:  rgba(212,175,55,0.35);
    --metallic:   linear-gradient(to right, var(--gold-dark), var(--gold-base) 30%, var(--gold-light) 50%, var(--gold-base) 70%, var(--gold-dark));
    --header-bg:  rgba(6,6,6,0.85);
    --drawer-bg:  #060606;
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
  html.light header, html.light #mobile-menu {
    --header-bg:  rgba(255,255,255,0.88);
    --drawer-bg:  #ffffff;
    --ink:        #0a0a0a;
    --ink-2:      rgba(10,10,10,0.65);
    --ink-4:      rgba(10,10,10,0.35);
    --line-soft:  rgba(10,10,10,0.06);
    --gold-line:  rgba(138,110,47,0.3);
    --gold-line-strong: rgba(138,110,47,0.55);
    --gold-tint:  rgba(212,175,55,0.07);
    --gold-text:  var(--gold-dark);
  }

  body { padding-top: 72px; }

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
    margin: 0; padding: 0;
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
    padding: 0;
  }
  .icon-btn:hover {
    border-color: var(--gold-base);
    color: var(--gold-text);
    background: var(--gold-tint);
  }
  .icon-btn:active { transform: scale(0.95); }
  .icon-btn svg { width: 15px; height: 15px; }

  /* SUN visible en dark (propose light), MOON visible en light (propose dark) */
  #theme-toggle .theme-moon { display: none; }
  #theme-toggle .theme-sun  { display: block; }
  html.light #theme-toggle .theme-moon { display: block; }
  html.light #theme-toggle .theme-sun  { display: none; }

  .btn-connexion {
    background: transparent;
    border: 1px solid var(--gold-line-strong);
    color: var(--gold-text);
    padding: 7px 22px;
    border-radius: 30px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s;
    white-space: nowrap;
    font-family: 'Montserrat', sans-serif;
  }
  .btn-connexion:hover {
    background: var(--metallic);
    color: var(--on-gold);
    -webkit-text-fill-color: var(--on-gold);
    border-color: transparent;
    box-shadow: 0 0 18px var(--gold-glow);
  }

  #burger-trigger { display: none; }

  @keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
  }

  /* ── Drawer mobile ── */
  #mobile-menu { position: fixed; inset: 0; z-index: 200; visibility: hidden; pointer-events: none; }
  #mobile-menu.is-open { visibility: visible; pointer-events: auto; }
  #mobile-menu-backdrop {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  #mobile-menu.is-open #mobile-menu-backdrop { opacity: 1; }
  #mobile-menu-panel {
    position: absolute; right: 0; top: 0;
    height: 100%; width: 75%; max-width: 360px;
    background: var(--drawer-bg);
    border-left: 1px solid var(--gold-line);
    display: flex; flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
    box-shadow: -10px 0 40px rgba(0,0,0,0.4);
  }
  #mobile-menu.is-open #mobile-menu-panel { transform: translateX(0); }
  .drawer-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px; height: 72px;
    border-bottom: 1px solid var(--gold-line);
  }
  .drawer-nav { flex: 1; padding: 32px 24px; display: flex; flex-direction: column; gap: 4px; }
  .drawer-section-label {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.6rem; font-weight: 700;
    letter-spacing: 4px; text-transform: uppercase;
    color: var(--gold-text);
    margin-bottom: 18px;
  }
  .drawer-link {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 0;
    border-bottom: 1px solid var(--line-soft);
    text-decoration: none;
    color: var(--ink);
    font-size: 1rem; font-weight: 700; letter-spacing: 1px;
    transition: color 0.25s, padding-left 0.25s;
    font-family: 'Montserrat', sans-serif;
  }
  .drawer-link:hover { color: var(--gold-text); padding-left: 6px; }
  .drawer-link svg { width: 16px; height: 16px; color: var(--ink-4); transition: color 0.25s, transform 0.25s; }
  .drawer-link:hover svg { color: var(--gold-text); transform: translateX(4px); }
  .drawer-footer { padding: 24px; display: flex; flex-direction: column; gap: 12px; border-top: 1px solid var(--line-soft); }
  .drawer-cta {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 14px 24px; border-radius: 40px;
    font-weight: 900; font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase;
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
  .drawer-cta.secondary { background: transparent; border: 1px solid var(--gold-line-strong); color: var(--gold-text); }
  .drawer-cta.secondary:hover { background: var(--gold-tint); border-color: var(--gold-base); }
  .drawer-copy { text-align: center; font-size: 0.65rem; letter-spacing: 2px; text-transform: uppercase; color: var(--ink-4); margin-top: 8px; }

  /* ── Responsive header ── */
  @media (max-width: 1024px) {
    header { grid-template-columns: auto 1fr auto; padding: 0 28px; gap: 20px; }
  }
  @media (max-width: 900px) {
    header { padding: 0 20px; grid-template-columns: 1fr auto; }
    header > nav,
    .header-right .btn-connexion { display: none; }
    #burger-trigger { display: inline-flex; }
  }
  @media (max-width: 600px) {
    header { padding: 0 16px; height: 64px; }
    .logo { font-size: 0.95rem; letter-spacing: 2px; }
    .icon-btn { width: 34px; height: 34px; }
    body { padding-top: 64px; }
  }
</style>
</head>
<body>

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
    <!-- Theme toggle -->
    <button id="theme-toggle" class="icon-btn" aria-label="Basculer le thème" type="button">
      <svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
      </svg>
      <svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
      </svg>
    </button>

    <?php if ($viewer_id): ?>
      <a href="dashboard.php" class="btn-connexion">Dashboard</a>
    <?php else: ?>
      <a href="connexion.php" class="btn-connexion">Connexion</a>
    <?php endif; ?>

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
      <?php if ($viewer_id): ?>
      <a href="dashboard.php" data-close class="drawer-cta secondary">Dashboard</a>
      <?php else: ?>
      <a href="connexion.php" data-close class="drawer-cta secondary">Connexion</a>
      <?php endif; ?>
      <p class="drawer-copy">&copy; 2025 &middot; HESTIM</p>
    </div>
  </aside>
</div>


<div class="wrap">

    <div class="hero">
        <div class="eyebrow">Saison 1 · 2026</div>
        <h1>Classement Général</h1>
        <div class="divider"></div>
    </div>

    <?php if ($total === 0): ?>
        <p class="empty-board">Aucun joueur classé pour l'instant — le classement se remplit dès les premiers duels classés.</p>
    <?php else: ?>

    <!-- ── Podium top 3 ── -->
    <div class="podium">
        <?php
        // Ordre visuel : 2 · 1 · 3
        $order = [];
        if (isset($podium[1])) $order[] = ['p' => $podium[1], 'cls' => 'p2'];
        if (isset($podium[0])) $order[] = ['p' => $podium[0], 'cls' => 'p1'];
        if (isset($podium[2])) $order[] = ['p' => $podium[2], 'cls' => 'p3'];
        foreach ($order as $o): $p = $o['p']; ?>
        <div class="pedestal <?= $o['cls'] ?>">
            <?php if ($o['cls'] === 'p1'): ?><div class="ped-crown">👑</div><?php endif; ?>
            <div class="ped-avatar"><?= htmlspecialchars($p['initials']) ?></div>
            <div class="ped-name"><?= htmlspecialchars($p['username']) ?></div>
            <div class="ped-elo"><?= $p['elo'] ?></div>
            <div class="ped-block"><?= $p['rank'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Recherche ── -->
    <div class="controls">
        <div class="board-count"><b id="shown-count"><?= $total ?></b> joueur<?= $total > 1 ? 's' : '' ?> classé<?= $total > 1 ? 's' : '' ?></div>
        <div class="search"><input type="text" id="search" placeholder="Chercher un joueur…" aria-label="Chercher un joueur"></div>
    </div>

    <!-- ── Classement (liste plate par ELO) ── -->
    <div id="board">
        <?php foreach ($players as $p):
            [$divLabel, $divCls] = qpc_division($p['elo']);
            $isMe    = ((int) $p['id'] === $viewer_id);
            $rankCls = $p['rank'] <= 3 ? ' r' . $p['rank'] : '';
        ?>
        <div class="row<?= $isMe ? ' me' : '' ?>" <?= $isMe ? 'id="row-me"' : '' ?> data-name="<?= htmlspecialchars(mb_strtolower($p['username'], 'UTF-8')) ?>">
            <div class="r-rank<?= $rankCls ?>"><?= $p['rank'] ?></div>
            <div class="r-player">
                <div class="r-avatar"><?= htmlspecialchars($p['initials']) ?></div>
                <div class="r-name"><?= htmlspecialchars($p['username']) ?><?= $isMe ? '<span class="me-tag">TOI</span>' : '' ?></div>
            </div>
            <div class="r-div"><span class="div-badge <?= $divCls ?>"><?= $divLabel ?></span></div>
            <div class="r-elo"><?= $p['elo'] ?><small>ELO</small></div>
            <div class="r-vd"><b><?= $p['wins'] ?>V</b> – <?= $p['losses'] ?>D</div>
            <div class="r-wr">
                <span class="wr-num"><?= $p['winrate'] ?>%</span>
                <div class="wr-bar"><div class="wr-fill" style="width:<?= $p['winrate'] ?>%"></div></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="empty" id="empty">Aucun joueur ne correspond.</div>

    <?php endif; ?>
</div>

<?php if ($viewer_id && $me): ?>
<button class="me-chip" id="me-chip">🎯 Ta position — #<?= $me['rank'] ?> · <?= $me['elo'] ?></button>
<?php elseif ($viewer_id): ?>
<a class="me-chip" href="lobby-ranked.php">🎯 Non classé — joue un duel classé</a>
<?php endif; ?>

<script>
// ── Recherche (filtre client, données déjà rendues) ──
const rows  = Array.from(document.querySelectorAll('#board .row'));
const empty = document.getElementById('empty');
const count = document.getElementById('shown-count');
const search = document.getElementById('search');

if (search) {
    search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        let shown = 0;
        rows.forEach(r => {
            const hit = r.dataset.name.includes(q);
            r.style.display = hit ? '' : 'none';
            if (hit) shown++;
        });
        if (empty) empty.style.display = shown ? 'none' : 'block';
        if (count) count.textContent = shown;
    });
}

// ── Chip "ta position" : scroll + flash ──
const chip = document.getElementById('me-chip');
if (chip && chip.tagName === 'BUTTON') {
    chip.addEventListener('click', () => {
        if (search) { search.value = ''; search.dispatchEvent(new Event('input')); }
        const row = document.getElementById('row-me');
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.remove('flash'); void row.offsetWidth; row.classList.add('flash');
        }
    });
}

// ── Theme toggle (même comportement que index.php) ──
(function () {
  const root = document.documentElement;
  const toggle = document.getElementById('theme-toggle');
  if (!toggle) return;
  toggle.addEventListener('click', () => {
    const isLight = root.classList.toggle('light');
    try { localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); } catch (e) {}
  });
})();

// ── Burger drawer (même comportement que index.php) ──
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
