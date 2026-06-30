// ============================================================================
//  CHAMPIONNAT — Système audio (Web Audio API, aucun fichier externe)
//  Porté depuis le 1v1 (game-1v1-fx.js) pour avoir le même feedback sonore.
//  Expose : window.QPCAudio
// ============================================================================
window.QPCAudio = (function () {
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

    return {
        init, setMuted, toggleMuted, isMuted,

        // Countdown 3-2-1
        countdownTick() { beep(440, 0.12, 'sine', 0.18); },
        countdownGo()   { beep(660, 0.25, 'triangle', 0.22); beep(880, 0.35, 'sine', 0.15, 0.08); },

        // Question apparaît (whoosh montant + sparkle)
        questionAppear() {
            beep(180, 0.4, 'sine', 0.10, 0, 600);
            beep(880, 0.3, 'triangle', 0.07, 0.15);
            beep(1320, 0.4, 'sine', 0.05, 0.25);
        },

        // Options apparaissent (4 ticks staggered)
        optionsAppear() {
            [600, 700, 800, 900].forEach((f, i) => beep(f, 0.08, 'sine', 0.05, i * 0.08));
        },

        // Buzz devient possible / je buzze
        buzzOpen() {
            beep(880, 0.15, 'sine', 0.12);
            beep(1320, 0.2, 'triangle', 0.08, 0.05);
        },
        myBuzz() {
            beep(523, 0.08, 'square', 0.10);
            beep(784, 0.15, 'sine', 0.12, 0.05);
            beep(1047, 0.2, 'triangle', 0.10, 0.12);
        },
        oppBuzz() {
            beep(330, 0.15, 'square', 0.08);
            beep(247, 0.2, 'sine', 0.06, 0.08);
        },

        // Tick timer
        tick()   { beep(220, 0.04, 'sine', 0.035); },
        urgent() { beep(520, 0.06, 'square', 0.07); beep(780, 0.04, 'sine', 0.04, 0.02); },

        // Bonne / mauvaise réponse
        correctMe() {
            [523, 659, 784, 1047].forEach((f, i) => beep(f, 0.25, 'sine', 0.13, i * 0.07));
            beep(1568, 0.5, 'triangle', 0.07, 0.32);
        },
        correctOpp() {
            [330, 415, 494].forEach((f, i) => beep(f, 0.2, 'sine', 0.06, i * 0.08));
        },
        wrongMe()  { beep(200, 0.5, 'sawtooth', 0.12, 0, 80); beep(150, 0.4, 'square', 0.06, 0.1, 60); },
        wrongOpp() { beep(220, 0.3, 'triangle', 0.05, 0, 140); },

        // Temps écoulé
        timeOut() {
            beep(440, 0.15, 'square', 0.08);
            beep(330, 0.15, 'square', 0.08, 0.12);
            beep(220, 0.4, 'sawtooth', 0.10, 0.22, 110);
        },

        // Fin — victoire / défaite / élimination
        gameWin() {
            [523, 659, 784, 1047, 1318, 1568].forEach((f, i) => beep(f, 0.35, 'sine', 0.14, i * 0.10));
            beep(2093, 0.8, 'triangle', 0.08, 0.65);
        },
        gameLose() {
            [523, 466, 415, 349, 311].forEach((f, i) => beep(f, 0.4, 'sawtooth', 0.12, i * 0.15));
        },
        eliminated() {
            beep(392, 0.3, 'sawtooth', 0.10, 0, 180);
            beep(294, 0.4, 'square', 0.08, 0.15, 130);
        },

        // Petits dings
        scoreBump() { beep(880, 0.08, 'sine', 0.06); },
        betPlaced() { beep(660, 0.1, 'triangle', 0.08); beep(990, 0.12, 'sine', 0.06, 0.06); },
        mancheStart() { beep(330, 0.2, 'triangle', 0.10); beep(494, 0.25, 'sine', 0.10, 0.1); beep(659, 0.3, 'sine', 0.08, 0.2); }
    };
})();
