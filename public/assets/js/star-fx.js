/**
 * Classroom Star — Gamification Audio & Particle FX
 * Pure Web Audio API + HTML5 Canvas Particles (0 external dependencies)
 */

const StarFX = {
    audioCtx: null,

    initAudio() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioCtx = new AudioContext();
            }
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    },

    // Play sparkling chime chord (C5 - E5 - G5 - C6)
    playChime(count = 3) {
        try {
            this.initAudio();
            if (!this.audioCtx) return;

            const now = this.audioCtx.currentTime;
            const freqs = [523.25, 659.25, 783.99, 1046.50, 1318.51]; // C5, E5, G5, C6, E6
            const notes = Math.min(count + 1, freqs.length);

            for (let i = 0; i < notes; i++) {
                const osc  = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(freqs[i], now + i * 0.08);

                gain.gain.setValueAtTime(0, now + i * 0.08);
                gain.gain.linearRampToValueAtTime(0.25, now + i * 0.08 + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.08 + 0.6);

                osc.connect(gain);
                gain.connect(this.audioCtx.destination);

                osc.start(now + i * 0.08);
                osc.stop(now + i * 0.08 + 0.65);
            }
        } catch (e) {
            console.warn('Audio FX not supported or blocked:', e);
        }
    },

    // Play Undo / Reversal subtle tone
    playUndo() {
        try {
            this.initAudio();
            if (!this.audioCtx) return;

            const now  = this.audioCtx.currentTime;
            const osc  = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(440, now);
            osc.frequency.exponentialRampToValueAtTime(220, now + 0.25);

            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);

            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            osc.start(now);
            osc.stop(now + 0.25);
        } catch (e) {}
    },

    // Confetti Star Burst on screen
    burst(x = window.innerWidth / 2, y = window.innerHeight / 2, count = 40) {
        const canvas = document.createElement('canvas');
        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100vw';
        canvas.style.height = '100vh';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '99999';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const particles = [];
        const colors = ['#ffd54f', '#ffc107', '#ff8f00', '#fff8e1', '#9c6dff', '#448aff'];

        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const speed = 4 + Math.random() * 8;
            particles.push({
                x: x,
                y: y,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed - 2,
                size: 8 + Math.random() * 12,
                color: colors[Math.floor(Math.random() * colors.length)],
                rotation: Math.random() * Math.PI * 2,
                vRot: (Math.random() - 0.5) * 0.2,
                alpha: 1,
                decay: 0.015 + Math.random() * 0.02,
                gravity: 0.18,
                isStar: Math.random() > 0.3
            });
        }

        function drawStar(ctx, cx, cy, spikes, outerRadius, innerRadius, color, alpha) {
            let rot = Math.PI / 2 * 3;
            let x = cx;
            let y = cy;
            const step = Math.PI / spikes;

            ctx.save();
            ctx.beginPath();
            ctx.moveTo(cx, cy - outerRadius);
            for (let i = 0; i < spikes; i++) {
                x = cx + Math.cos(rot) * outerRadius;
                y = cy + Math.sin(rot) * outerRadius;
                ctx.lineTo(x, y);
                rot += step;

                x = cx + Math.cos(rot) * innerRadius;
                y = cy + Math.sin(rot) * innerRadius;
                ctx.lineTo(x, y);
                rot += step;
            }
            ctx.lineTo(cx, cy - outerRadius);
            ctx.closePath();
            ctx.fillStyle = color;
            ctx.globalAlpha = alpha;
            ctx.fill();
            ctx.restore();
        }

        let animationFrame;
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            let active = 0;

            for (const p of particles) {
                if (p.alpha <= 0) continue;
                active++;

                p.x += p.vx;
                p.y += p.vy;
                p.vy += p.gravity;
                p.vx *= 0.98;
                p.rotation += p.vRot;
                p.alpha -= p.decay;

                if (p.alpha > 0) {
                    if (p.isStar) {
                        drawStar(ctx, p.x, p.y, 5, p.size, p.size / 2, p.color, Math.max(0, p.alpha));
                    } else {
                        ctx.save();
                        ctx.translate(p.x, p.y);
                        ctx.rotate(p.rotation);
                        ctx.fillStyle = p.color;
                        ctx.globalAlpha = Math.max(0, p.alpha);
                        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
                        ctx.restore();
                    }
                }
            }

            if (active > 0) {
                animationFrame = requestAnimationFrame(animate);
            } else {
                cancelAnimationFrame(animationFrame);
                canvas.remove();
            }
        }

        animate();
    }
};
