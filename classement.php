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

<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Cinzel+Decorative:wght@700;900&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="classement.css">
</head>
<body>

<a href="<?= $viewer_id ? 'dashboard.php' : 'index.php' ?>" class="back-btn"><span>←</span><span>Retour</span></a>
<div class="logo-wrap">QPC</div>

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
</script>
</body>
</html>
