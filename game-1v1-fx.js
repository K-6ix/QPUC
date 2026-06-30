/* ══════════════════════════════════════════════════
   QPC — Game 1v1 — Couche FX (audio + effets visuels)
   À charger AVANT game-1v1.js dans game-1v1.php
   Expose : window.QPCAudio + window.QPCFx
══════════════════════════════════════════════════ */

'use strict';

// ══════════════════════════════════════════════════
// AUDIO MODULE (Web Audio API, pas de fichiers externes)
// ══════════════════════════════════════════════════
window.QPCAudio = (function() {
    let ctx = null;
    let master = null;
    let muted = localStorage.getItem('qpc_muted') === '1';

    function init() {
        if (ctx) return;
        try {
            ctx = new (window.AudioContext || window.webkitAudioContext)();
            master = ctx.createGain();
            master.gain.value = 0.35;
            master.connect(ctx.destination);
        } catch (e) {
            console.warn('Web Audio API non supportée :', e);
        }
    }

    // Beep générique. slideTo : fréquence finale pour glissando.
    function beep(freq, dur, type = 'sine', gain = 0.15, delay = 0, slideTo = null) {
        if (!ctx || muted) return;
        const t = ctx.currentTime + delay;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, t);
        if (slideTo) osc.frequency.exponentialRampToValueAtTime(Math.max(20, slideTo), t + dur);
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(gain, t + 0.015);
        g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
        osc.connect(g);
        g.connect(master);
        osc.start(t);
        osc.stop(t + dur + 0.05);
    }

    function setMuted(m) {
        muted = m;
        try { localStorage.setItem('qpc_muted', m ? '1' : '0'); } catch (e) {}
    }
    function toggleMuted() { setMuted(!muted); return muted; }
    function isMuted() { return muted; }

    // === SONS DE JEU ===
    return {
        init, setMuted, toggleMuted, isMuted,

        // Countdown 3-2-1
        countdownTick() { beep(440, 0.12, 'sine', 0.18); },
        countdownGo()   { beep(660, 0.25, 'triangle', 0.22); beep(880, 0.35, 'sine', 0.15, 0.08); },

        // Question apparait (whoosh montant + sparkle)
        questionAppear() {
            beep(180, 0.4, 'sine', 0.10, 0, 600);
            beep(880, 0.3, 'triangle', 0.07, 0.15);
            beep(1320, 0.4, 'sine', 0.05, 0.25);
        },

        // Options apparaissent (4 ticks staggered)
        optionsAppear() {
            [600, 700, 800, 900].forEach((f, i) => beep(f, 0.08, 'sine', 0.05, i * 0.08));
        },

        // Buzz devient possible (ding alerte)
        buzzOpen() {
            beep(880, 0.15, 'sine', 0.12);
            beep(1320, 0.2, 'triangle', 0.08, 0.05);
        },

        // J'ai buzzé en premier
        myBuzz() {
            beep(523, 0.08, 'square', 0.10);
            beep(784, 0.15, 'sine', 0.12, 0.05);
            beep(1047, 0.2, 'triangle', 0.10, 0.12);
        },

        // L'adversaire a buzzé
        oppBuzz() {
            beep(330, 0.15, 'square', 0.08);
            beep(247, 0.2, 'sine', 0.06, 0.08);
        },

        // Tick subtil du timer (chaque seconde, sauf danger)
        tick() { beep(220, 0.04, 'sine', 0.035); },

        // Tick urgent (5 dernières secondes)
        urgent() {
            beep(520, 0.06, 'square', 0.07);
            beep(780, 0.04, 'sine', 0.04, 0.02);
        },

        // Bonne réponse (arpège majeur ascendant C-E-G-C + sparkle)
        correctMe() {
            [523, 659, 784, 1047].forEach((f, i) => beep(f, 0.25, 'sine', 0.13, i * 0.07));
            beep(1568, 0.5, 'triangle', 0.07, 0.32);
        },
        correctOpp() {
            [330, 415, 494].forEach((f, i) => beep(f, 0.2, 'sine', 0.06, i * 0.08));
        },

        // Mauvaise réponse (buzz descendant)
        wrongMe()  { beep(200, 0.5, 'sawtooth', 0.12, 0, 80); beep(150, 0.4, 'square', 0.06, 0.1, 60); },
        wrongOpp() { beep(220, 0.3, 'triangle', 0.05, 0, 140); },

        // Temps écoulé
        timeOut() {
            beep(440, 0.15, 'square', 0.08);
            beep(330, 0.15, 'square', 0.08, 0.12);
            beep(220, 0.4, 'sawtooth', 0.10, 0.22, 110);
        },

        // Fin de partie — victoire
        gameWin() {
            [523, 659, 784, 1047, 1318, 1568].forEach((f, i) =>
                beep(f, 0.35, 'sine', 0.14, i * 0.10));
            beep(2093, 0.8, 'triangle', 0.08, 0.65);
        },

        // Fin de partie — défaite
        gameLose() {
            [523, 466, 415, 349, 311].forEach((f, i) =>
                beep(f, 0.4, 'sawtooth', 0.12, i * 0.15));
        },

        // Score bump (petit ding)
        scoreBump() { beep(880, 0.08, 'sine', 0.06); }
    };
})();

// ══════════════════════════════════════════════════
// FX MODULE (effets visuels)
// ══════════════════════════════════════════════════
window.QPCFx = (function() {
    function $(id) { return document.getElementById(id); }

    // ─── Reduced-motion check ───
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ─── Containers pré-créés (injectés au boot) ───
    let flashEl = null;
    let bigMarkEl = null;

    function ensureContainers() {
        if (!flashEl) {
            flashEl = document.createElement('div');
            flashEl.className = 'fx-screen-flash';
            document.body.appendChild(flashEl);
        }
        if (!bigMarkEl) {
            bigMarkEl = document.createElement('div');
            bigMarkEl.className = 'fx-big-mark';
            document.body.appendChild(bigMarkEl);
        }
    }

    // ─── Screen flash ───
    function screenFlash(color = 'gold', duration = 500) {
        ensureContainers();
        flashEl.className = 'fx-screen-flash ' + color;
        flashEl.style.opacity = '0.55';
        clearTimeout(flashEl._t);               // un seul timer, débouncé
        flashEl._t = setTimeout(() => { flashEl.style.opacity = '0'; }, Math.min(duration, 120));
    }

    // ─── Big mark (✓ / ✗ / ⏱) ───
    function bigMark(text, color = 'gold') {
        ensureContainers();
        if (reducedMotion) return;
        bigMarkEl.textContent = text;
        bigMarkEl.className = 'fx-big-mark ' + color;
        void bigMarkEl.offsetWidth;
        bigMarkEl.classList.add('show');
        setTimeout(() => bigMarkEl.classList.remove('show'), 1300);
    }

    // ─── Screen shake (sur .game-wrap) ───
    function screenShake() {
        if (reducedMotion) return;
        const wrap = $('game-wrap');
        if (!wrap) return;
        wrap.classList.remove('fx-shaking');
        void wrap.offsetWidth;
        wrap.classList.add('fx-shaking');
        setTimeout(() => wrap.classList.remove('fx-shaking'), 500);
    }

    // ─── Floating delta (+100 / -25 depuis un élément) ───
    function floatDelta(targetEl, value, color = null) {
        if (!targetEl) return;
        const rect = targetEl.getBoundingClientRect();
        const el = document.createElement('div');
        const cls = color || (value > 0 ? 'up' : 'down');
        el.className = 'fx-float-delta ' + cls;
        el.textContent = (value > 0 ? '+' : '') + value + ' pts';
        el.style.left = (rect.left + rect.width / 2) + 'px';
        el.style.top  = rect.top + 'px';
        document.body.appendChild(el);
        requestAnimationFrame(() => {
            el.classList.add(value > 0 ? 'anim-up' : 'anim-down');
        });
        setTimeout(() => el.remove(), 1500);
    }

    // ─── Shockwave depuis un point ou un élément ───
    function shockwave(xOrEl, y = null, color = 'gold') {
        let cx, cy;
        if (typeof xOrEl === 'number') {
            cx = xOrEl; cy = y;
        } else if (xOrEl && xOrEl.getBoundingClientRect) {
            const r = xOrEl.getBoundingClientRect();
            cx = r.left + r.width / 2;
            cy = r.top + r.height / 2;
        } else {
            return;
        }
        const el = document.createElement('div');
        el.className = 'fx-shockwave ' + color;
        el.style.left = cx + 'px';
        el.style.top  = cy + 'px';
        document.body.appendChild(el);
        requestAnimationFrame(() => el.classList.add('go'));
        setTimeout(() => el.remove(), 1100);
    }

    // ─── Confetti rain (canvas unique — perf, plus de 120 nœuds DOM) ───
    let _confCanvas = null, _confCtx = null;
    let _confParticles = [];
    let _confRunning = false;
    const _confColors = ['#d4af37', '#fcf6ba', '#f0c850', '#8a6e2f', '#f0e8cc', '#4caf78'];

    function _confResize() {
        if (!_confCanvas) return;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        _confCanvas.width  = window.innerWidth * dpr;
        _confCanvas.height = window.innerHeight * dpr;
        _confCanvas.style.width  = window.innerWidth + 'px';
        _confCanvas.style.height = window.innerHeight + 'px';
        _confCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function confettiRain(count = 60) {
        if (reducedMotion) return;
        if (!_confCanvas) {
            _confCanvas = $('fx-confetti-canvas');
            if (!_confCanvas) return;
            _confCtx = _confCanvas.getContext('2d');
            window.addEventListener('resize', _confResize);
        }
        _confResize();

        const W = window.innerWidth, H = window.innerHeight;
        // On AJOUTE au pool existant (rafale multiple OK sans empiler de boucles)
        for (let i = 0; i < count; i++) {
            _confParticles.push({
                x: W / 2 + (Math.random() - 0.5) * 80,
                y: H / 3,
                vx: (Math.random() - 0.5) * 12,
                vy: -Math.random() * 12 - 4,
                gravity: 0.25 + Math.random() * 0.15,
                size: 5 + Math.random() * 6,
                color: _confColors[(Math.random() * _confColors.length) | 0],
                rot: Math.random() * Math.PI * 2,
                vRot: (Math.random() - 0.5) * 0.3,
                rect: Math.random() > 0.5,
                life: 0,
                opacity: 1
            });
        }

        if (_confRunning) return;     // une seule boucle, jamais deux
        _confRunning = true;

        function tick() {
            const w = window.innerWidth, h = window.innerHeight;
            _confCtx.clearRect(0, 0, w, h);
            for (let i = _confParticles.length - 1; i >= 0; i--) {
                const p = _confParticles[i];
                p.x += p.vx; p.y += p.vy; p.vy += p.gravity; p.vx *= 0.99;
                p.rot += p.vRot; p.life++;
                if (p.life > 150) p.opacity -= 0.02;
                if (p.opacity <= 0 || p.y > h + 40) { _confParticles.splice(i, 1); continue; }
                _confCtx.save();
                _confCtx.globalAlpha = p.opacity;
                _confCtx.translate(p.x, p.y);
                _confCtx.rotate(p.rot);
                _confCtx.fillStyle = p.color;
                if (p.rect) _confCtx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
                else { _confCtx.beginPath(); _confCtx.arc(0, 0, p.size / 2, 0, Math.PI * 2); _confCtx.fill(); }
                _confCtx.restore();
            }
            if (_confParticles.length > 0) {
                requestAnimationFrame(tick);
            } else {
                _confCtx.clearRect(0, 0, w, h);
                _confRunning = false;       // boucle s'éteint quand le pool est vide
            }
        }
        requestAnimationFrame(tick);
    }

    // ─── Question text : révélation simple (1 élément, pas de span par mot) ───
    function splitWordsAnimate(el, text) {
        if (!el) return;
        el.textContent = text;                  // texte direct, aucun nœud par mot
        if (reducedMotion) return;
        el.classList.remove('fx-text-reveal');
        void el.offsetWidth;                     // restart de l'anim (1 reflow, 1×/question)
        el.classList.add('fx-text-reveal');
    }

    // ─── Hooks événements de jeu ───
    function onQuestionTextArrive(text, targetTextEl, cardEl) {
        if (cardEl) {
            cardEl.classList.remove('fx-arriving');
            void cardEl.offsetWidth;
            cardEl.classList.add('fx-arriving');
        }
        screenFlash('gold', 360);
        // (plus de shockwave par question : créait un anneau DOM + un getBoundingClientRect à chaque fois)
        splitWordsAnimate(targetTextEl, text);
        QPCAudio.questionAppear();
    }

    function onOptionsAppear() {
        // Entrée gérée par la transition .visible native (opacity + translateY, composité).
        // Plus de flip 3D rotateY : trop lourd sur des boutons en backdrop-filter.
        QPCAudio.optionsAppear();
    }

    function onBuzzOpen() {
        QPCAudio.buzzOpen();
        const btn = $('buzz-btn');
        if (btn) shockwave(btn, null, 'gold');
    }

    function onBuzzed(isMe) {
        const btn = $('buzz-btn');
        if (!btn) return;
        const cls = isMe ? 'fx-burst-gold' : 'fx-burst-blue';
        btn.classList.remove(cls);
        void btn.offsetWidth;
        btn.classList.add(cls);
        setTimeout(() => btn.classList.remove(cls), 800);

        screenFlash(isMe ? 'gold' : 'blue', 350);
        shockwave(btn, null, isMe ? 'gold' : 'blue');
        if (isMe) QPCAudio.myBuzz(); else QPCAudio.oppBuzz();
    }

    function onAnswerResult({ correct, isMe, timeout, pts, targetScoreEl }) {
        // Dim les options non-correctes
        if (correct || timeout) {
            document.querySelectorAll('.option-btn').forEach(b => {
                if (!b.classList.contains('correct') && !b.classList.contains('reveal')) {
                    b.classList.add('fx-dimmed');
                }
            });
        }
        // Pulse sur correct
        document.querySelectorAll('.option-btn.correct').forEach(b => {
            b.classList.add('fx-pulse');
            setTimeout(() => b.classList.remove('fx-pulse'), 2500);
        });
        // Shake sur wrong (mais uniquement pour mon erreur)
        document.querySelectorAll('.option-btn.wrong').forEach(b => {
            b.classList.add('fx-shake');
            setTimeout(() => b.classList.remove('fx-shake'), 500);
        });

        if (timeout) {
            bigMark('⏱', 'danger');
            screenFlash('red', 450);
            screenShake();
            QPCAudio.timeOut();
            return;
        }

        if (correct) {
            if (isMe) {
                bigMark('✓', 'success');
                screenFlash('green', 500);
                confettiRain(70);
                QPCAudio.correctMe();
                if (targetScoreEl && pts) floatDelta(targetScoreEl, pts, 'up');
            } else {
                screenFlash('blue', 350);
                QPCAudio.correctOpp();
                if (targetScoreEl && pts) floatDelta(targetScoreEl, pts, 'up');
            }
        } else {
            if (isMe) {
                bigMark('✗', 'danger');
                screenFlash('red', 400);
                screenShake();
                QPCAudio.wrongMe();
                if (targetScoreEl && pts) floatDelta(targetScoreEl, pts, 'down');
            } else {
                QPCAudio.wrongOpp();
                if (targetScoreEl && pts) floatDelta(targetScoreEl, pts, 'down');
            }
        }
    }

    function onTimerTick(timeLeft) {
        if (timeLeft <= 0) return;
        if (timeLeft <= 5) {
            QPCAudio.urgent();
            const num = $('timer-num');
            if (num) num.classList.add('fx-critical');
        } else {
            QPCAudio.tick();
            const num = $('timer-num');
            if (num) num.classList.remove('fx-critical');
        }
    }

    function onCountdown(count) {
        if (count > 0) QPCAudio.countdownTick();
        else QPCAudio.countdownGo();
    }

    function onGameOver(isWinner) {
        setTimeout(() => {
            if (isWinner) {
                confettiRain(120);
                bigMark('★', 'gold');
                QPCAudio.gameWin();
            } else {
                QPCAudio.gameLose();
            }
        }, 1400);
    }

    // ─── Audio toggle button (injecté dans .bottom-bar) ───
    function injectAudioButton() {
        const bar = document.querySelector('.bottom-bar .connection-status');
        if (!bar || document.querySelector('.fx-audio-btn')) return;
        const btn = document.createElement('button');
        btn.className = 'fx-audio-btn';
        btn.setAttribute('aria-label', 'Activer/couper le son');
        function render() {
            btn.classList.toggle('muted', QPCAudio.isMuted());
            btn.innerHTML = QPCAudio.isMuted()
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>';
        }
        btn.addEventListener('click', () => {
            QPCAudio.init();
            QPCAudio.toggleMuted();
            render();
        });
        render();
        bar.parentNode.insertBefore(btn, bar);
    }

    // ─── Theme toggle button (injecté à côté du bouton audio) ───
    function injectThemeButton() {
        const audioBtn = document.querySelector('.fx-audio-btn');
        if (!audioBtn || document.querySelector('.fx-theme-btn')) return;

        const btn = document.createElement('button');
        btn.className = 'fx-theme-btn';
        btn.setAttribute('aria-label', 'Basculer le thème clair/sombre');
        btn.setAttribute('type', 'button');
        // SVG soleil (visible en dark : propose light) + lune (visible en light : propose dark)
        btn.innerHTML =
            '<svg class="theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<circle cx="12" cy="12" r="4"></circle>' +
                '<path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>' +
            '</svg>' +
            '<svg class="theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>' +
            '</svg>';

        btn.addEventListener('click', () => {
            const root = document.documentElement;
            root.classList.add('theme-transitioning');
            const isLight = root.classList.toggle('light');
            try { localStorage.setItem('qpc-theme', isLight ? 'light' : 'dark'); } catch (e) {}
            setTimeout(() => root.classList.remove('theme-transitioning'), 300);
        });

        // On insère JUSTE avant le bouton audio (côté gauche de "Connecté", à droite du son)
        audioBtn.parentNode.insertBefore(btn, audioBtn);
    }

    // ─── Boot ───
    function boot() {
        ensureContainers();
        injectAudioButton();
        injectThemeButton();
        // Initialiser l'audio context au premier user gesture
        const initOnce = () => {
            QPCAudio.init();
            document.removeEventListener('click', initOnce);
            document.removeEventListener('keydown', initOnce);
        };
        document.addEventListener('click', initOnce);
        document.addEventListener('keydown', initOnce);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    return {
        boot,
        screenFlash, screenShake, bigMark, floatDelta, shockwave, confettiRain,
        splitWordsAnimate,
        onQuestionTextArrive, onOptionsAppear, onBuzzOpen, onBuzzed,
        onAnswerResult, onTimerTick, onCountdown, onGameOver
    };
})();
