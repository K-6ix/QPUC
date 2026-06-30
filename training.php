<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entraînement — Question Champion</title>

<!-- ════ ANTI-FLASH : applique le thème global avant le render ════ -->
<script>
(function () {
  try {
    var stored = localStorage.getItem('qpc-theme');
    if (stored === 'light') {
      document.documentElement.classList.add('light');
    }
  } catch (e) {}
})();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Cinzel+Decorative:wght@700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════
   TOKENS — Dark mode (par défaut)
══════════════════════════════════════════════════ */
:root {
    /* Or — identité */
    --gold:       #d4af37;
    --gold-light: #fcf6ba;
    --gold-dark:  #8a6e2f;
    --gold-dim:   rgba(212,175,55,0.15);
    --gold-text:  var(--gold);   /* doré lisible sur fond bg */

    /* Surfaces */
    --bg:         #060606;
    --bg2:        #0d0d0d;
    --bg3:        #141414;
    --surface:    #1a1a1a;

    /* Bordures */
    --border:     rgba(212,175,55,0.2);
    --border2:    rgba(212,175,55,0.4);

    /* Texte */
    --text1:      #f0e8cc;
    --text2:      rgba(240,232,204,0.6);
    --text3:      rgba(240,232,204,0.35);

    /* Sémantique */
    --success:    #4caf78;
    --danger:     #e05555;

    /* Misc */
    --bar-bg:     rgba(6,6,6,0.95);
    --overlay-bg: rgba(6,6,6,0.92);
    --on-gold:    #060606;
    --r:          10px;
}

/* ══════════════════════════════════════════════════
   TOKENS — Light mode (override via html.light)
══════════════════════════════════════════════════ */
html.light {
    --bg:         #ffffff;
    --bg2:        #f7f7f5;
    --bg3:        #eeeee9;
    --surface:    #ffffff;

    --border:     rgba(138,110,47,0.3);
    --border2:    rgba(138,110,47,0.55);

    --text1:      #1a1a1a;
    --text2:      rgba(10,10,10,0.65);
    --text3:      rgba(10,10,10,0.4);

    --gold-dim:   rgba(212,175,55,0.2);
    --gold-text:  var(--gold-dark);

    --bar-bg:     rgba(255,255,255,0.92);
    --overlay-bg: rgba(245,245,243,0.95);
}

/* Transition douce pendant le switch */
.theme-transitioning,
.theme-transitioning * {
    transition: background-color 0.25s ease,
                border-color 0.25s ease,
                color 0.25s ease !important;
}

*{margin:0;padding:0;box-sizing:border-box;}

body {
    font-family: 'Raleway', sans-serif;
    background: var(--bg);
    color: var(--text1);
    min-height: 100vh;
    overflow-x: hidden;
}

/* ── BACKGROUND EFFECT ── */
body::before {
    content:'';
    position:fixed;
    inset:0;
    background:
        radial-gradient(ellipse 60% 40% at 20% 20%, rgba(212,175,55,0.04) 0%, transparent 60%),
        radial-gradient(ellipse 40% 30% at 80% 80%, rgba(212,175,55,0.03) 0%, transparent 60%);
    pointer-events:none;
    z-index:0;
}

/* ── LAYOUT ── */
.game-wrap {
    position:relative;
    z-index:1;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

/* ── TOP BAR ── */
.topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:16px 32px;
    border-bottom:1px solid var(--border);
    background:var(--bar-bg);
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);
    position:sticky;
    top:0;
    z-index:100;
}
.topbar-logo {
    font-family:'Cinzel Decorative', serif;
    font-size:14px;
    color:var(--gold);
    letter-spacing:2px;
    text-decoration:none;
}
.topbar-meta {
    display:flex;
    align-items:center;
    gap:24px;
}
.meta-item {
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:2px;
}
.meta-label {
    font-size:9px;
    letter-spacing:2px;
    color:var(--text3);
    text-transform:uppercase;
}
.meta-value {
    font-family:'Cinzel', serif;
    font-size:15px;
    color:var(--gold);
    font-weight:600;
}
.topbar-score {
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:2px;
}
.score-label { font-size:9px; letter-spacing:2px; color:var(--text3); text-transform:uppercase; }
.score-value {
    font-family:'Cinzel', serif;
    font-size:20px;
    color:var(--gold);
    font-weight:700;
    transition:all .3s;
}
.score-value.bump {
    transform:scale(1.3);
    color:var(--gold-light);
}

/* ── PROGRESS BAR ── */
.progress-wrap {
    height:3px;
    background:var(--bg3);
    position:relative;
    overflow:hidden;
}
.progress-fill {
    height:100%;
    background:linear-gradient(90deg, var(--gold-dark), var(--gold), var(--gold-light));
    transition:width .6s ease;
    width:0%;
}
.progress-glow {
    position:absolute;
    right:0;
    top:-2px;
    width:20px;
    height:7px;
    background:var(--gold-light);
    filter:blur(4px);
    opacity:.8;
}

/* ── MAIN CONTENT ── */
.game-content {
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:32px 16px;
    gap:32px;
    max-width:820px;
    margin:0 auto;
    width:100%;
}

/* ── QUESTION NUMBER + CATEGORY ── */
.q-meta {
    display:flex;
    align-items:center;
    justify-content:space-between;
    width:100%;
}
.q-number {
    font-size:11px;
    letter-spacing:3px;
    color:var(--text3);
    text-transform:uppercase;
}
.q-category {
    display:flex;
    align-items:center;
    gap:6px;
    padding:4px 14px;
    border:1px solid var(--border);
    border-radius:20px;
    font-size:11px;
    letter-spacing:1px;
    color:var(--text2);
    background:var(--gold-dim);
}
.q-category-icon { font-size:14px; }

/* ── TIMER ── */
.timer-wrap {
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    width:100%;
}
.timer-bar-bg {
    width:100%;
    height:6px;
    background:var(--surface);
    border-radius:3px;
    overflow:hidden;
    position:relative;
}
.timer-bar-fill {
    height:100%;
    background:linear-gradient(90deg, var(--gold-dark), var(--gold));
    border-radius:3px;
    transition:width 1s linear, background .5s;
    width:100%;
    transform-origin:left;
}
.timer-bar-fill.danger {
    background:linear-gradient(90deg, #a32d2d, var(--danger));
    animation:pulse-danger .5s ease-in-out infinite alternate;
}
@keyframes pulse-danger {
    from { opacity:.8; }
    to   { opacity:1; }
}
.timer-number {
    position:absolute;
    right:0;
    top:-22px;
    font-family:'Cinzel', serif;
    font-size:13px;
    color:var(--gold);
    transition:color .3s;
    min-width:28px;
    text-align:right;
}
.timer-number.danger { color:var(--danger); }

/* ── QUESTION CARD ── */
.question-card {
    width:100%;
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:16px;
    padding:36px 40px;
    position:relative;
    overflow:hidden;
}
.question-card::before {
    content:'';
    position:absolute;
    top:0;left:0;right:0;
    height:2px;
    background:linear-gradient(90deg, transparent, var(--gold), transparent);
}
.question-text {
    font-family:'Cinzel', serif;
    font-size:20px;
    font-weight:400;
    line-height:1.6;
    color:var(--text1);
    text-align:center;
}

/* ── OPTIONS ── */
.options-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    width:100%;
}
.option-btn {
    background:var(--bg3);
    border:1px solid var(--border);
    border-radius:var(--r);
    padding:18px 24px;
    cursor:pointer;
    transition:all .2s;
    display:flex;
    align-items:center;
    gap:14px;
    text-align:left;
    color:var(--text1);
    font-family:'Raleway', sans-serif;
    font-size:14px;
    font-weight:500;
    letter-spacing:.3px;
    position:relative;
    overflow:hidden;
}
.option-btn::before {
    content:'';
    position:absolute;
    inset:0;
    background:var(--gold-dim);
    opacity:0;
    transition:opacity .2s;
}
.option-btn:hover:not(:disabled)::before { opacity:1; }
.option-btn:hover:not(:disabled) {
    border-color:var(--gold);
    transform:translateY(-2px);
}
.option-btn:active:not(:disabled) { transform:translateY(0); }

.option-letter {
    min-width:32px;
    height:32px;
    border-radius:50%;
    border:1.5px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:'Cinzel', serif;
    font-size:12px;
    font-weight:600;
    color:var(--gold);
    transition:all .2s;
    flex-shrink:0;
}
.option-btn:hover:not(:disabled) .option-letter {
    background:var(--gold);
    color:var(--on-gold);
    border-color:var(--gold);
}

/* ── OPTION STATES ── */
.option-btn.correct {
    border-color:var(--success) !important;
    background:rgba(76,175,120,0.1) !important;
}
.option-btn.correct .option-letter {
    background:var(--success);
    border-color:var(--success);
    color:#fff;
}
.option-btn.wrong {
    border-color:var(--danger) !important;
    background:rgba(224,85,85,0.1) !important;
}
.option-btn.wrong .option-letter {
    background:var(--danger);
    border-color:var(--danger);
    color:#fff;
}
.option-btn.reveal {
    border-color:rgba(76,175,120,0.4) !important;
}
.option-btn:disabled { cursor:not-allowed; transform:none !important; }

/* ── FEEDBACK BADGE ── */
.feedback-badge {
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%) scale(0);
    background:var(--bg);
    border:2px solid var(--gold);
    border-radius:16px;
    padding:20px 40px;
    text-align:center;
    z-index:200;
    transition:transform .25s cubic-bezier(.34,1.56,.64,1);
    pointer-events:none;
}
.feedback-badge.show { transform:translate(-50%,-50%) scale(1); }
.feedback-badge.correct-fb { border-color:var(--success); }
.feedback-badge.wrong-fb   { border-color:var(--danger); }
.feedback-icon { font-size:32px; margin-bottom:6px; }
.feedback-text {
    font-family:'Cinzel', serif;
    font-size:18px;
    color:var(--text1);
    font-weight:600;
}
.feedback-pts {
    font-size:13px;
    color:var(--gold);
    margin-top:4px;
    font-weight:600;
}

/* ── STREAK BADGE ── */
.streak-wrap {
    display:flex;
    align-items:center;
    gap:8px;
}
.streak-dot {
    width:10px;
    height:10px;
    border-radius:50%;
    background:var(--border);
    transition:all .3s;
}
.streak-dot.active {
    background:var(--gold);
    box-shadow:0 0 6px var(--gold);
}

/* ── DIFFICULTY BADGE ── */
.diff-badge {
    padding:3px 10px;
    border-radius:20px;
    font-size:10px;
    font-weight:600;
    letter-spacing:1.5px;
    text-transform:uppercase;
}
.diff-badge.facile   { background:rgba(76,175,120,0.15); color:var(--success); border:1px solid rgba(76,175,120,0.3); }
.diff-badge.moyen    { background:rgba(212,175,55,0.15); color:var(--gold); border:1px solid var(--border); }
.diff-badge.difficile{ background:rgba(224,85,85,0.15); color:var(--danger); border:1px solid rgba(224,85,85,0.3); }

/* ── BADGE ADAPTATIF (mode entraînement) ── */
.adaptive-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 4px;
    font-family: 'Cinzel', serif; font-size: 10px; font-weight: 600;
    letter-spacing: 1.5px; text-transform: uppercase;
    background: linear-gradient(135deg, rgba(212,175,55,0.08), rgba(212,175,55,0.18));
    border: 1px solid var(--border2);
    color: var(--text1);
    transition: all 0.3s ease;
}
.adaptive-badge .adaptive-label { color: var(--text2); font-size: 9px; }
.adaptive-badge .adaptive-level { color: var(--gold); font-weight: 700; }
.adaptive-badge .adaptive-arrow { font-size: 14px; color: var(--text2); transition: transform 0.4s ease, color 0.3s ease; }
.adaptive-badge.up   .adaptive-arrow { color: var(--success); transform: translateY(-1px); }
.adaptive-badge.down .adaptive-arrow { color: var(--danger);  transform: translateY(1px); }
.adaptive-badge.bump { animation: adaptive-pulse 0.6s ease; }
@keyframes adaptive-pulse {
    0%   { transform: scale(1);   box-shadow: 0 0 0 0 var(--border2); }
    50%  { transform: scale(1.08); box-shadow: 0 0 0 8px rgba(212,175,55,0); }
    100% { transform: scale(1);   box-shadow: 0 0 0 0 rgba(212,175,55,0); }
}

/* ── BOTTOM BAR ── */
.bottom-bar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:16px 32px;
    border-top:1px solid var(--border);
    background:var(--bar-bg);
    backdrop-filter:blur(10px);
    -webkit-backdrop-filter:blur(10px);
}
.hint-btn, .abandon-btn {
    display:flex;
    align-items:center;
    gap:8px;
    padding:10px 20px;
    border-radius:8px;
    font-family:'Raleway', sans-serif;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    transition:all .2s;
    letter-spacing:.5px;
}
.hint-btn {
    background:var(--gold-dim);
    border:1px solid var(--border);
    color:var(--gold);
}
.hint-btn:hover { background:rgba(212,175,55,0.25); border-color:var(--gold); }
.hint-btn:disabled { opacity:.4; cursor:not-allowed; }
.abandon-btn {
    background:transparent;
    border:1px solid rgba(224,85,85,0.3);
    color:rgba(224,85,85,0.7);
}
.abandon-btn:hover { background:rgba(224,85,85,0.1); border-color:var(--danger); color:var(--danger); }
.hint-icon, .abandon-icon { font-size:16px; }

/* ── HINT BOX ── */
.hint-box {
    width:100%;
    background:var(--gold-dim);
    border:1px solid var(--border);
    border-radius:var(--r);
    padding:14px 20px;
    font-size:13px;
    color:var(--text2);
    display:none;
    align-items:center;
    gap:10px;
}
.hint-box.visible { display:flex; }
.hint-box-icon { font-size:16px; color:var(--gold); }

/* ── SCREEN : START ── */
.screen {
    position:fixed;
    inset:0;
    background:var(--bg);
    z-index:500;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:32px;
    padding:32px;
    transition:opacity .5s, transform .5s;
}
.screen.hidden {
    opacity:0;
    pointer-events:none;
    transform:scale(0.97);
}

.start-title {
    font-family:'Cinzel Decorative', serif;
    font-size:clamp(22px, 4vw, 36px);
    color:var(--gold);
    text-align:center;
    letter-spacing:3px;
    line-height:1.4;
}
.start-sub {
    font-size:14px;
    color:var(--text2);
    text-align:center;
    max-width:480px;
    line-height:1.8;
}

/* Note : .mode-grid / .mode-card / .cat-grid / .cat-chip retirés
   (remplacés par .training-cat-grid / .training-cat-card en fin de fichier) */

.start-btn {
    padding:16px 48px;
    background:linear-gradient(135deg, var(--gold-dark), var(--gold));
    border:none;
    border-radius:40px;
    font-family:'Cinzel', serif;
    font-size:15px;
    font-weight:700;
    color:var(--on-gold);
    cursor:pointer;
    letter-spacing:2px;
    transition:all .3s;
    box-shadow:0 0 30px rgba(212,175,55,0.3);
}
.start-btn:hover {
    transform:translateY(-3px);
    box-shadow:0 0 50px rgba(212,175,55,0.5);
}

.section-title {
    font-size:10px;
    letter-spacing:3px;
    color:var(--text3);
    text-transform:uppercase;
    text-align:center;
}

/* ── SCREEN : END ── */
.end-screen {
    position:fixed;
    inset:0;
    background:var(--bg);
    z-index:500;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:28px;
    padding:32px;
    opacity:0;
    pointer-events:none;
    transition:opacity .5s;
}
.end-screen.visible {
    opacity:1;
    pointer-events:all;
}
.end-title {
    font-family:'Cinzel Decorative', serif;
    font-size:clamp(20px, 3vw, 30px);
    color:var(--gold);
    text-align:center;
    letter-spacing:2px;
}
.end-score-big {
    font-family:'Cinzel', serif;
    font-size:clamp(48px, 8vw, 80px);
    font-weight:700;
    color:var(--gold);
    line-height:1;
}
.end-score-label { font-size:12px; letter-spacing:3px; color:var(--text3); text-transform:uppercase; }
.end-stats {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    width:100%;
    max-width:480px;
}
.end-stat {
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--r);
    padding:16px;
    text-align:center;
}
.end-stat-val {
    font-family:'Cinzel', serif;
    font-size:22px;
    font-weight:700;
    color:var(--gold);
}
.end-stat-label { font-size:10px; letter-spacing:2px; color:var(--text3); text-transform:uppercase; margin-top:4px; }
.end-btns { display:flex; gap:14px; flex-wrap:wrap; justify-content:center; }
.end-btn-primary {
    padding:14px 36px;
    background:linear-gradient(135deg, var(--gold-dark), var(--gold));
    border:none;
    border-radius:40px;
    font-family:'Cinzel', serif;
    font-size:13px;
    font-weight:700;
    color:var(--on-gold);
    cursor:pointer;
    letter-spacing:2px;
    transition:all .3s;
}
.end-btn-primary:hover { transform:translateY(-2px); box-shadow:0 0 30px rgba(212,175,55,0.4); }
.end-btn-secondary {
    padding:14px 36px;
    background:transparent;
    border:1px solid var(--border);
    border-radius:40px;
    font-family:'Cinzel', serif;
    font-size:13px;
    color:var(--text2);
    cursor:pointer;
    letter-spacing:2px;
    transition:all .2s;
}
.end-btn-secondary:hover { border-color:var(--gold); color:var(--gold); }

/* ── RESPONSIVE ── */
/* ══════════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════════ */
@media (max-width: 900px) {
    .topbar { padding: 14px 22px; }
    .topbar-meta { gap: 16px; }
    .bottom-bar { padding: 14px 22px; }
    .game-content { padding: 28px 16px; max-width: 700px; }
    .question-card { padding: 30px 28px; }
    .start-screen-inner { padding: 0 24px; }
}

@media (max-width: 600px) {
    .topbar { padding: 12px 14px; gap: 8px; flex-wrap: wrap; }
    .topbar-logo { font-size: 12px; letter-spacing: 1.5px; }
    .topbar-meta { gap: 10px; font-size: 11px; }

    .progress-bar { height: 3px; }

    .game-content { padding: 22px 12px; gap: 20px; }
    .options-grid { grid-template-columns: 1fr; gap: 10px; }
    .option-btn { padding: 14px 16px; font-size: 13px; }
    .option-letter { min-width: 26px; height: 26px; font-size: 11px; }

    .question-card { padding: 24px 18px; min-height: 80px; }
    .question-text { font-size: 15px; line-height: 1.55; }

    .q-meta { gap: 8px; }
    .q-category { padding: 3px 10px; font-size: 10px; }
    .diff-badge { padding: 3px 10px; font-size: 9px; letter-spacing: 1px; }

    .timer-wrap { padding-top: 22px; }
    .timer-number { font-size: 13px; }

    .bottom-bar { padding: 12px 14px; gap: 8px; }
    .hint-btn, .abandon-btn { padding: 9px 14px; font-size: 11px; }

    .start-screen-inner { padding: 0 16px; }
    .start-btn { padding: 14px 32px; font-size: 13px; }

    .end-screen { padding: 28px 16px; gap: 22px; }
    .end-trophy { font-size: 56px; }
    .end-btn-primary, .end-btn-secondary { padding: 12px 28px; font-size: 12px; }

    .feedback-badge { padding: 16px 26px; min-width: 180px; }
}

@media (max-width: 380px) {
    .topbar { padding: 10px 12px; }
    .topbar-logo { font-size: 11px; }
    .topbar-meta { font-size: 10px; gap: 8px; }

    .game-content { padding: 18px 10px; gap: 16px; }
    .question-text { font-size: 14px; }
    .question-card { padding: 20px 16px; }
    .option-btn { padding: 12px 14px; font-size: 12.5px; }

    .timer-number { font-size: 12px; }
    .diff-badge { padding: 2px 8px; font-size: 8.5px; }
}

/* Paysage smartphone (clavier d'iPhone par exemple) */
@media (max-height: 500px) and (orientation: landscape) {
    .topbar { padding: 8px 16px; }
    .game-content { padding: 16px; gap: 14px; }
    .question-card { padding: 18px 24px; min-height: 60px; }
    .question-text { font-size: 14px; line-height: 1.4; }
    .options-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
    .option-btn { padding: 10px 14px; font-size: 12px; }
    .bottom-bar { padding: 8px 16px; }
}

/* ── PARTICLES ── */
.particle {
    position:fixed;
    pointer-events:none;
    border-radius:50%;
    animation:particle-fly .8s ease-out forwards;
    z-index:300;
}
@keyframes particle-fly {
    from { transform:translate(0,0) scale(1); opacity:1; }
    to   { transform:translate(var(--dx), var(--dy)) scale(0); opacity:0; }
}

/* ══════════════════════════════════════════════════
   MODE ENTRAÎNEMENT — Start screen spécifique
══════════════════════════════════════════════════ */
.train-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border: 1px solid var(--gold);
    background: var(--gold-dim);
    border-radius: 30px;
    font-family: 'Cinzel', serif;
    font-size: 11px;
    font-weight: 700;
    color: var(--gold-text);
    letter-spacing: 2.5px;
    text-transform: uppercase;
    margin-bottom: 22px;
}
.train-badge span { font-size: 14px; }

.training-cat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    width: 100%;
    max-width: 780px;
    margin-bottom: 36px;
}

.training-cat-card {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--r);
    padding: 24px 18px;
    cursor: pointer;
    text-align: center;
    transition: border-color .25s, background .25s, transform .25s, box-shadow .25s;
    position: relative;
    overflow: hidden;
}
.training-cat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    opacity: 0;
    transition: opacity .3s;
}
.training-cat-card:hover {
    border-color: var(--border2);
    background: var(--gold-dim);
    transform: translateY(-3px);
}
.training-cat-card:hover::before { opacity: 0.5; }

.training-cat-card.selected {
    border-color: var(--gold);
    background: var(--gold-dim);
    box-shadow: 0 0 22px rgba(212,175,55,0.18);
}
.training-cat-card.selected::before { opacity: 1; }

.training-cat-card.selected::after {
    content: '✓';
    position: absolute;
    top: 10px;
    right: 14px;
    font-family: 'Cinzel', serif;
    font-weight: 700;
    color: var(--gold-text);
    font-size: 14px;
}

.tc-icon {
    font-size: 34px;
    margin-bottom: 10px;
    line-height: 1;
    filter: drop-shadow(0 0 10px rgba(212,175,55,0.15));
}
.tc-name {
    font-family: 'Cinzel', serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--text1);
    letter-spacing: .5px;
    margin-bottom: 6px;
    line-height: 1.2;
}
.tc-desc {
    font-size: 11px;
    color: var(--text3);
    letter-spacing: .2px;
    line-height: 1.5;
}

.back-link {
    margin-top: 22px;
    color: var(--text3);
    text-decoration: none;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: color .2s;
}
.back-link:hover { color: var(--gold-text); }

/* Responsive training cards */
@media (max-width: 900px) {
    .training-cat-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; max-width: 600px; }
    .training-cat-card { padding: 20px 14px; }
}
@media (max-width: 600px) {
    .training-cat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .training-cat-card { padding: 18px 14px; }
    .tc-icon { font-size: 28px; margin-bottom: 8px; }
    .tc-name { font-size: 12.5px; }
    .tc-desc { font-size: 10.5px; }
    .train-badge { font-size: 10px; letter-spacing: 2px; padding: 5px 14px; margin-bottom: 18px; }
}
@media (max-width: 380px) {
    .training-cat-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
    .training-cat-card { padding: 14px 10px; }
    .tc-icon { font-size: 24px; }
    .tc-name { font-size: 11.5px; }
    .tc-desc { font-size: 10px; }
}
</style>
</head>
<body>

<!-- ═══ START SCREEN ═══ -->
<div class="screen" id="start-screen">
    <div class="train-badge"><span>🎯</span> Mode Entraînement</div>
    <div class="start-title">Choisissez votre<br>terrain d'entraînement</div>
    <p class="start-sub">Sélectionnez une catégorie pour commencer. Aucun classement, aucune pression — juste de la pratique pour progresser.</p>

    <div class="training-cat-grid">
        <div class="training-cat-card selected" data-cat="all" onclick="selectCat(this)">
            <div class="tc-icon">🎲</div>
            <div class="tc-name">Toutes catégories</div>
            <div class="tc-desc">Mélange de toutes les disciplines</div>
        </div>
        <div class="training-cat-card" data-cat="sciences" onclick="selectCat(this)">
            <div class="tc-icon">🔬</div>
            <div class="tc-name">Sciences &amp; Nature</div>
            <div class="tc-desc">Physique, chimie, biologie</div>
        </div>
        <div class="training-cat-card" data-cat="informatique" onclick="selectCat(this)">
            <div class="tc-icon">💻</div>
            <div class="tc-name">Informatique</div>
            <div class="tc-desc">Tech, code, algorithmes</div>
        </div>
        <div class="training-cat-card" data-cat="histoire" onclick="selectCat(this)">
            <div class="tc-icon">🏛️</div>
            <div class="tc-name">Histoire</div>
            <div class="tc-desc">Civilisations, dates, guerres</div>
        </div>
        <div class="training-cat-card" data-cat="geographie" onclick="selectCat(this)">
            <div class="tc-icon">🌍</div>
            <div class="tc-name">Géographie</div>
            <div class="tc-desc">Pays, capitales, continents</div>
        </div>
        <div class="training-cat-card" data-cat="mathematiques" onclick="selectCat(this)">
            <div class="tc-icon">📐</div>
            <div class="tc-name">Mathématiques</div>
            <div class="tc-desc">Calcul, géométrie, logique</div>
        </div>
        <div class="training-cat-card" data-cat="culture_generale" onclick="selectCat(this)">
            <div class="tc-icon">🧠</div>
            <div class="tc-name">Culture Générale</div>
            <div class="tc-desc">Connaissances variées</div>
        </div>
        <div class="training-cat-card" data-cat="sport" onclick="selectCat(this)">
            <div class="tc-icon">⚽</div>
            <div class="tc-name">Sport</div>
            <div class="tc-desc">Compétitions, joueurs, records</div>
        </div>
        <div class="training-cat-card" data-cat="art_litterature" onclick="selectCat(this)">
            <div class="tc-icon">🎨</div>
            <div class="tc-name">Art &amp; Littérature</div>
            <div class="tc-desc">Œuvres, peintres, écrivains</div>
        </div>
    </div>

    <button class="start-btn" onclick="startGame()">COMMENCER L'ENTRAÎNEMENT ▶</button>
    <a href="dashboard.php" class="back-link">← Retour au dashboard</a>
</div>

<!-- ═══ GAME WRAP ═══ -->
<div class="game-wrap" id="game-wrap" style="display:none;">

    <!-- TOP BAR -->
    <div class="topbar">
        <a href="dashboard.php" class="topbar-logo">QPC</a>
        <div class="topbar-meta">
            <div class="meta-item">
                <span class="meta-label">Question</span>
                <span class="meta-value" id="q-counter">1/10</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Mode</span>
                <span class="meta-value" id="mode-label">Entraînement</span>
            </div>
            <div class="meta-item">
                <div class="streak-wrap" id="streak-dots">
                    <div class="streak-dot"></div>
                    <div class="streak-dot"></div>
                    <div class="streak-dot"></div>
                    <div class="streak-dot"></div>
                    <div class="streak-dot"></div>
                </div>
            </div>
        </div>
        <div class="topbar-score">
            <span class="score-label">Score</span>
            <span class="score-value" id="score-display">0</span>
        </div>
    </div>

    <!-- PROGRESS -->
    <div class="progress-wrap">
        <div class="progress-fill" id="progress-fill"></div>
        <div class="progress-glow"></div>
    </div>

    <!-- MAIN -->
    <div class="game-content">

        <!-- Q META -->
        <div class="q-meta">
            <span class="q-number" id="q-number">Question 1 sur 10</span>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="q-category">
                    <span class="q-category-icon" id="cat-icon">🎲</span>
                    <span id="cat-label">Culture</span>
                </span>
                <span class="diff-badge" id="diff-badge">—</span>
                <span class="adaptive-badge" id="adaptive-badge" title="Le niveau s'ajuste en temps réel selon vos performances">
                    <span class="adaptive-label">Niveau IA</span>
                    <span class="adaptive-level" id="adaptive-level">moyen</span>
                    <span class="adaptive-arrow" id="adaptive-arrow">→</span>
                </span>
            </div>
        </div>

        <!-- TIMER -->
        <div class="timer-wrap">
            <span class="timer-number" id="timer-num">15</span>
            <div class="timer-bar-bg" style="margin-top:28px;">
                <div class="timer-bar-fill" id="timer-fill"></div>
            </div>
        </div>

        <!-- QUESTION -->
        <div class="question-card">
            <p class="question-text" id="question-text">Chargement de la question…</p>
        </div>

        <!-- HINT BOX -->
        <div class="hint-box" id="hint-box">
            <span class="hint-box-icon">💡</span>
            <span id="hint-text">Un indice apparaîtra ici.</span>
        </div>

        <!-- OPTIONS -->
        <div class="options-grid" id="options-grid"></div>

    </div>

    <!-- BOTTOM BAR -->
    <div class="bottom-bar">
        <button class="hint-btn" id="hint-btn" onclick="showHint()">
            <span class="hint-icon">💡</span> Indice (−50 pts)
        </button>
        <button class="abandon-btn" onclick="confirmAbandon()">
            <span class="abandon-icon">✕</span> Abandonner
        </button>
    </div>

</div>

<!-- ═══ END SCREEN ═══ -->
<div class="end-screen" id="end-screen">
    <div class="end-title" id="end-title">Entraînement terminé !</div>
    <div>
        <div class="end-score-big" id="end-score">0</div>
        <div class="end-score-label">Points</div>
    </div>
    <div class="end-stats">
        <div class="end-stat">
            <div class="end-stat-val" id="end-correct">0</div>
            <div class="end-stat-label">Bonnes réponses</div>
        </div>
        <div class="end-stat">
            <div class="end-stat-val" id="end-accuracy">0%</div>
            <div class="end-stat-label">Précision</div>
        </div>
        <div class="end-stat">
            <div class="end-stat-val" id="end-time">0s</div>
            <div class="end-stat-label">Temps moyen</div>
        </div>
    </div>
    <div class="end-btns">
        <button class="end-btn-primary" onclick="location.reload()">▶ Nouvel entraînement</button>
        <button class="end-btn-secondary" onclick="location.href='dashboard.php'">Dashboard</button>
    </div>
</div>

<!-- FEEDBACK BADGE -->
<div class="feedback-badge" id="feedback-badge">
    <div class="feedback-icon" id="feedback-icon">✓</div>
    <div class="feedback-text" id="feedback-text">Bonne réponse !</div>
    <div class="feedback-pts" id="feedback-pts">+100 pts</div>
</div>

<script>
// ══════════════════════════════════════════════════
// DONNÉES (sera remplacé par PHP + JSON)
// ══════════════════════════════════════════════════
// ══════════════════════════════════════════════════
// DONNÉES — chargées depuis questions.json (499 questions)
// ══════════════════════════════════════════════════
let QUESTIONS_DATA = {};

async function loadQuestionsFromJSON() {
    try {
        const res = await fetch('questions.json', { cache: 'no-store' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();
        QUESTIONS_DATA = {};
        json.categories.forEach(cat => {
            QUESTIONS_DATA[cat.id] = {
                label: cat.label, icon: cat.icon,
                category_id: cat.category_id, questions: cat.questions
            };
        });
        console.log(`[QPC training] ${json.metadata?.total_questions || '?'} questions chargées`);
        return true;
    } catch (e) {
        console.error('[QPC training] Erreur chargement questions.json :', e);
        alert("Impossible de charger les questions. Vérifie que questions.json est accessible.");
        return false;
    }
}

const LETTERS = ['A','B','C','D'];
const TOTAL_Q = 15;   // 15 questions en entraînement (au lieu de 10) pour laisser l'adaptatif évoluer

// ── STATE ──
let state = {
    mode: 'training',
    selectedCat: 'all',
    questions: [],
    current: 0,
    score: 0,
    streak: 0,
    bestStreak: 0,         // pic atteint pendant la partie
    correctCount: 0,
    answered: false,
    timerInterval: null,
    timerLeft: 15,
    timerMax: 15,
    startTime: null,
    answerTimes: [],
    hintUsed: false,
    gameStartTime: null,
    pool: [],              // pool complet (tirage différé adaptatif)
    askedIds: new Set(),   // ids déjà posés (anti-doublon)
};

// ══════════════════════════════════════════════════
// MODULE DIFFICULTÉ ADAPTATIVE
// ══════════════════════════════════════════════════
// Fenêtre glissante des 5 dernières réponses → score ∈ [-0.3, +1.5] :
//   bonne réponse rapide : +1.5     bonne : +1.0
//   mauvaise : 0          timeout : -0.3
// Score → difficulté piochée (facile/moyen/difficile) + multiplicateur temps (0.8× à 1.2×)
//
const ADAPTIVE = {
    history: [],
    windowSize: 5,
    currentLevel: 'moyen',
    previousLevel: 'moyen',
    timeMultiplier: 1.0,

    reset() {
        this.history = [];
        this.currentLevel = 'moyen';
        this.previousLevel = 'moyen';
        this.timeMultiplier = 1.0;
    },

    record({ correct, timeRatio, timeout }) {
        this.history.push({ correct, timeRatio, timeout });
        if (this.history.length > this.windowSize) this.history.shift();
        this.previousLevel = this.currentLevel;
        this._recompute();
    },

    score() {
        if (this.history.length === 0) return 0.5;
        let total = 0;
        this.history.forEach(h => {
            if (h.timeout)       total -= 0.3;
            else if (h.correct)  total += 1 + (h.timeRatio > 0.5 ? 0.5 : 0);
        });
        return total / this.history.length;
    },

    _recompute() {
        const s = this.score();
        if (s < 0.4)       this.currentLevel = 'facile';
        else if (s < 0.85) this.currentLevel = 'moyen';
        else               this.currentLevel = 'difficile';
        if (s < 0.3)       this.timeMultiplier = 1.2;
        else if (s < 0.7)  this.timeMultiplier = 1.0;
        else if (s < 1.1)  this.timeMultiplier = 0.9;
        else               this.timeMultiplier = 0.8;
    },

    trend() {
        const order = { facile: 0, moyen: 1, difficile: 2 };
        return Math.sign(order[this.currentLevel] - order[this.previousLevel]);
    },

    pickNext(pool, alreadyAsked) {
        const target = this.currentLevel;
        const avail = pool.filter(q => q.difficulty === target && !alreadyAsked.has(q.id));
        if (avail.length > 0) return avail[Math.floor(Math.random() * avail.length)];
        // Fallback : niveaux adjacents
        const fallback = target === 'difficile' ? ['moyen', 'facile']
                       : target === 'facile'    ? ['moyen', 'difficile']
                       :                          ['facile', 'difficile'];
        for (const lvl of fallback) {
            const a = pool.filter(q => q.difficulty === lvl && !alreadyAsked.has(q.id));
            if (a.length > 0) return a[Math.floor(Math.random() * a.length)];
        }
        return null;
    }
};

// Mise à jour visuelle du badge "Niveau IA"
function updateAdaptiveBadge() {
    const badge  = document.getElementById('adaptive-badge');
    const levelEl = document.getElementById('adaptive-level');
    const arrowEl = document.getElementById('adaptive-arrow');
    if (!badge || !levelEl) return;
    const lvl = ADAPTIVE.currentLevel;
    const t = ADAPTIVE.trend();
    levelEl.textContent = lvl;
    badge.classList.remove('up', 'down', 'bump');
    if (t > 0)      { arrowEl.textContent = '↑'; badge.classList.add('up');   }
    else if (t < 0) { arrowEl.textContent = '↓'; badge.classList.add('down'); }
    else            { arrowEl.textContent = '→'; }
    if (t !== 0) requestAnimationFrame(() => badge.classList.add('bump'));
}

// ── START SCREEN ──
function selectCat(el) {
    document.querySelectorAll('.training-cat-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    state.selectedCat = el.dataset.cat;
}

function buildQuestions() {
    let pool = [];
    if (state.selectedCat === 'all') {
        Object.values(QUESTIONS_DATA).forEach(cat => {
            cat.questions.forEach(q => pool.push({...q, catId: cat.category_id, catLabel: cat.label, catIcon: cat.icon}));
        });
    } else {
        const cat = QUESTIONS_DATA[state.selectedCat];
        if (cat) cat.questions.forEach(q => pool.push({...q, catId: cat.category_id, catLabel: cat.label, catIcon: cat.icon}));
    }
    // Tirage différé : ADAPTIVE choisit chaque question juste avant qu'elle soit posée.
    state.pool = pool;
    state.askedIds = new Set();
    ADAPTIVE.reset();
    return [];
}

function startGame() {
    state.questions = buildQuestions();
    if (!state.pool || state.pool.length === 0) {
        alert("Aucune question disponible pour cette catégorie !");
        return;
    }
    state.current = 0;
    state.score = 0;
    state.streak = 0;
    state.bestStreak = 0;
    state.correctCount = 0;
    state.answered = false;
    state.answerTimes = [];
    state.gameStartTime = Date.now();

    document.getElementById('start-screen').classList.add('hidden');
    document.getElementById('game-wrap').style.display = 'flex';
    document.getElementById('mode-label').textContent = 'Entraînement';
    updateAdaptiveBadge();   // affiche état initial du badge

    loadQuestion();
}

// ── LOAD QUESTION ──
function loadQuestion() {
    // Pioche adaptative : ADAPTIVE choisit le niveau, puis on prend une question correspondante dans le pool
    const q = ADAPTIVE.pickNext(state.pool, state.askedIds);
    if (!q) {
        // Pool épuisé (catégorie trop petite) : on termine
        endGame(false);
        return;
    }
    state.askedIds.add(q.id);
    state.questions.push(q);  // append pour cohérence affichage

    state.answered = false;
    state.hintUsed = false;
    state.startTime = Date.now();

    // META
    document.getElementById('q-counter').textContent = `${state.current+1}/${TOTAL_Q}`;
    document.getElementById('q-number').textContent = `Question ${state.current+1} sur ${TOTAL_Q}`;
    document.getElementById('cat-icon').textContent = q.catIcon || '🎲';
    document.getElementById('cat-label').textContent = q.catLabel || '—';

    const diffEl = document.getElementById('diff-badge');
    diffEl.textContent = q.difficulty;
    diffEl.className = 'diff-badge ' + q.difficulty;

    document.getElementById('score-display').textContent = state.score.toLocaleString();
    document.getElementById('question-text').textContent = q.question;

    // PROGRESS
    const pct = (state.current / TOTAL_Q) * 100;
    document.getElementById('progress-fill').style.width = pct + '%';

    // STREAK DOTS
    const dots = document.querySelectorAll('.streak-dot');
    dots.forEach((d,i) => d.classList.toggle('active', i < Math.min(state.streak, 5)));

    // OPTIONS
    const grid = document.getElementById('options-grid');
    grid.innerHTML = '';
    const shuffled = [...q.options].sort(() => Math.random() - 0.5);
    shuffled.forEach((opt, i) => {
        const btn = document.createElement('button');
        btn.className = 'option-btn';
        btn.innerHTML = `<span class="option-letter">${LETTERS[i]}</span><span>${opt}</span>`;
        btn.onclick = () => answerQuestion(opt, btn, q);
        grid.appendChild(btn);
    });

    // HINT
    document.getElementById('hint-box').classList.remove('visible');
    const hintBtn = document.getElementById('hint-btn');
    hintBtn.disabled = false;

    // TIMER — multiplicateur adaptatif (0.8× à 1.2×)
    const timeForQ = Math.max(5, Math.round(q.time * ADAPTIVE.timeMultiplier));
    startTimer(timeForQ);
}

// ── TIMER ──
function startTimer(seconds) {
    clearInterval(state.timerInterval);
    state.timerLeft = seconds;
    state.timerMax  = seconds;
    updateTimerUI();

    state.timerInterval = setInterval(() => {
        state.timerLeft--;
        updateTimerUI();
        if (state.timerLeft <= 0) {
            clearInterval(state.timerInterval);
            if (!state.answered) timeOut();
        }
    }, 1000);
}

function updateTimerUI() {
    const pct = (state.timerLeft / state.timerMax) * 100;
    const fill = document.getElementById('timer-fill');
    const num  = document.getElementById('timer-num');
    fill.style.width = pct + '%';
    num.textContent  = state.timerLeft;
    const isDanger   = state.timerLeft <= 5;
    fill.classList.toggle('danger', isDanger);
    num.classList.toggle('danger', isDanger);
}

function timeOut() {
    state.answered = true;
    state.streak   = 0;
    ADAPTIVE.record({ correct: false, timeRatio: 0, timeout: true });
    updateAdaptiveBadge();
    revealAnswer(null);
    showFeedback(false, 0, true);
    setTimeout(nextQuestion, 2000);
}

// ── ANSWER ──
function answerQuestion(chosen, btn, q) {
    if (state.answered) return;
    state.answered = true;
    clearInterval(state.timerInterval);

    const timeTaken = Math.round((Date.now() - state.startTime) / 1000);
    state.answerTimes.push(timeTaken);
    const timeRatio = state.timerLeft / state.timerMax;  // 0 = pile à temps, 1 = instantané

    const isCorrect = chosen === q.answer;
    let pts = 0;

    if (isCorrect) {
        // Bonus rapidité actif en entraînement (incite à répondre vite, pas seulement bien)
        const bonus = Math.max(1, timeRatio * 2);
        pts = Math.round(q.points * bonus);
        if (state.hintUsed) pts = Math.max(0, pts - 50);
        state.score += pts;
        state.streak++;
        if (state.streak > state.bestStreak) state.bestStreak = state.streak;
        state.correctCount++;
        // Bonus streak
        if (state.streak >= 3) pts += 50 * (state.streak - 2);

        btn.classList.add('correct');
        spawnParticles(btn);
    } else {
        btn.classList.add('wrong');
        state.streak = 0;
    }

    // Record adaptatif (pilote le niveau de la PROCHAINE question)
    ADAPTIVE.record({ correct: isCorrect, timeRatio, timeout: false });
    updateAdaptiveBadge();

    revealAnswer(q.answer);
    animateScore();
    showFeedback(isCorrect, pts, false);

    setTimeout(nextQuestion, 2200);
}

function revealAnswer(correctAnswer) {
    document.querySelectorAll('.option-btn').forEach(b => {
        b.disabled = true;
        const text = b.querySelector('span:last-child').textContent;
        if (text === correctAnswer && !b.classList.contains('wrong')) {
            b.classList.add('reveal');
        }
    });
}

// ── FEEDBACK ──
function showFeedback(correct, pts, timeout) {
    const badge = document.getElementById('feedback-badge');
    badge.className = 'feedback-badge ' + (timeout ? 'wrong-fb' : correct ? 'correct-fb' : 'wrong-fb');
    document.getElementById('feedback-icon').textContent = timeout ? '⏱' : correct ? '✓' : '✗';
    document.getElementById('feedback-text').textContent = timeout ? 'Temps écoulé !' : correct ? 'Bonne réponse !' : 'Mauvaise réponse !';
    document.getElementById('feedback-pts').textContent  = correct ? `+${pts} pts` : '+0 pts';
    badge.classList.add('show');
    setTimeout(() => badge.classList.remove('show'), 1800);
}

// ── SCORE ANIMATION ──
function animateScore() {
    const el = document.getElementById('score-display');
    el.textContent = state.score.toLocaleString();
    el.classList.add('bump');
    setTimeout(() => el.classList.remove('bump'), 300);
}

// ── PARTICLES ──
function spawnParticles(el) {
    const rect = el.getBoundingClientRect();
    const cx = rect.left + rect.width/2;
    const cy = rect.top + rect.height/2;
    for (let i=0;i<8;i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = 4 + Math.random()*6;
        p.style.cssText = `width:${size}px;height:${size}px;background:${Math.random()>.5?'#d4af37':'#fcf6ba'};left:${cx}px;top:${cy}px;--dx:${(Math.random()-0.5)*120}px;--dy:${(Math.random()-1)*100}px;`;
        document.body.appendChild(p);
        setTimeout(()=>p.remove(),800);
    }
}

// ── HINT ──
function showHint() {
    const q = state.questions[state.current];
    state.hintUsed = true;
    document.getElementById('hint-btn').disabled = true;
    // Éliminer 1 mauvaise réponse
    const btns = [...document.querySelectorAll('.option-btn:not(:disabled)')];
    const wrongBtns = btns.filter(b => b.querySelector('span:last-child').textContent !== q.answer);
    if (wrongBtns.length > 0) {
        const toHide = wrongBtns[Math.floor(Math.random()*wrongBtns.length)];
        toHide.style.opacity = '0.25';
        toHide.disabled = true;
    }
    const hintBox = document.getElementById('hint-box');
    document.getElementById('hint-text').textContent = `Une mauvaise réponse a été éliminée. −50 pts si bonne réponse.`;
    hintBox.classList.add('visible');
}

// ── NEXT QUESTION ──
function nextQuestion() {
    state.current++;
    if (state.current >= TOTAL_Q) {
        endGame();
    } else {
        loadQuestion();
    }
}

// ── ABANDON ──
function confirmAbandon() {
    if (confirm('Abandonner la partie ? Votre score sera sauvegardé.')) {
        endGame(true);
    }
}

// ── END GAME ──
function endGame(abandoned = false) {
    clearInterval(state.timerInterval);

    const questionsAsked = state.questions.length;  // peut différer de TOTAL_Q si abandon
    const avgTime = state.answerTimes.length
        ? Math.round(state.answerTimes.reduce((a,b)=>a+b,0) / state.answerTimes.length)
        : 0;
    const accuracy = questionsAsked
        ? Math.round(state.correctCount * 100 / questionsAsked)
        : 0;

    // Seuils proportionnels (80% maîtrise, 50% belle progression)
    const masteryThreshold  = Math.ceil(questionsAsked * 0.8);
    const progressThreshold = Math.ceil(questionsAsked * 0.5);

    document.getElementById('end-title').textContent = abandoned
        ? 'Entraînement abandonné'
        : state.correctCount >= masteryThreshold  ? '🏆 Maîtrise totale !'
        : state.correctCount >= progressThreshold ? 'Belle progression !'
        :                                            'Entraînement terminé';
    document.getElementById('end-score').textContent    = state.score.toLocaleString();
    document.getElementById('end-correct').textContent  = `${state.correctCount}/${questionsAsked}`;
    document.getElementById('end-accuracy').textContent = accuracy + '%';
    document.getElementById('end-time').textContent     = avgTime + 's';

    document.getElementById('game-wrap').style.display = 'none';
    document.getElementById('end-screen').classList.add('visible');

    // ── Sauvegarde backend (training_sessions + training_stats) ──
    sendTrainingResults(abandoned, questionsAsked, avgTime);
}

// ──────────────────────────────────────────────
// Sauvegarde fin de session TRAINING
// POST vers save_training.php — n'écrit que dans training_*, jamais
// dans player_stats / game_sessions / users.score_total (règle d'isolation).
// ──────────────────────────────────────────────
function sendTrainingResults(abandoned, questionsAsked, avgTime) {
    const catEntry = QUESTIONS_DATA[state.selectedCat];
    const category_id = catEntry ? catEntry.category_id : 0;

    const payload = {
        category_id:     category_id,
        score:           state.score,
        total_questions: questionsAsked,
        correct:         state.correctCount,
        best_streak:     state.bestStreak,
        avg_time:        avgTime,
        final_level:     ADAPTIVE.currentLevel,
        abandoned:       !!abandoned,
        answer_times:    state.answerTimes,
    };

    fetch('save_training.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'same-origin',
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) console.log('[QPC training] Session sauvegardée — id =', data.session_id);
        else         console.warn('[QPC training] save_training.php a refusé :', data.error);
    })
    .catch(err => console.error('[QPC training] Erreur réseau :', err));
}

// ──────────────────────────────────────────────
// INIT : charger questions.json au boot
// ──────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', async () => {
    await loadQuestionsFromJSON();
});
</script>
</body>
</html>
