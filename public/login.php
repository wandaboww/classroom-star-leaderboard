<?php
/**
 * Login Page — Classroom Star
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Redirect ke dashboard jika sudah login
if (Auth::check()) {
    Auth::redirectToDashboard();
}

$error = '';
$success = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $user = Auth::login($username, $password);
        if ($user) {
            Auth::redirectToDashboard();
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}

$appConfig = require dirname(__DIR__) . '/app/config/app.php';
$appName = $appConfig['name'];
$csrfToken = Auth::csrfToken();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Login ke Classroom Star — Aplikasi Gamifikasi Partisipasi Kelas">
    <title>Login — <?= htmlspecialchars($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        /* ── Login Page Mobile-First Layout ─── */
        .auth-container {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .auth-card-clean {
            background: rgba(18, 22, 34, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem 1.75rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.6), 0 0 35px rgba(255, 215, 0, 0.05);
        }

        @media (max-width: 480px) {
            .auth-card-clean {
                padding: 1.5rem 1.25rem;
                border-radius: 20px;
            }
        }

        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: var(--space-5);
            text-align: center;
        }

        .login-logo-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-xl);
            background: var(--grad-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.9rem;
            margin-bottom: var(--space-3);
            box-shadow: var(--shadow-gold), 0 8px 30px rgba(255, 193, 7, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        .login-logo-name {
            font-size: var(--fs-xl);
            font-weight: 900;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #fff 0%, var(--clr-gold-400) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-logo-sub {
            font-size: var(--fs-xs);
            color: var(--clr-text-muted);
            margin-top: 2px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .login-title {
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2px;
        }

        .login-subtitle {
            text-align: center;
            color: var(--clr-text-muted);
            font-size: var(--fs-xs);
            margin-bottom: var(--space-4);
        }

        /* ── Student Guide Interactive Button ─── */
        .student-guide-btn {
            width: 100%;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.18) 0%, rgba(59, 130, 246, 0.1) 100%);
            border: 1px solid rgba(59, 130, 246, 0.35);
            border-radius: 14px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: var(--space-5);
            color: inherit;
        }

        .student-guide-btn:hover {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.28) 0%, rgba(59, 130, 246, 0.18) 100%);
            border-color: rgba(96, 165, 250, 0.6);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.25);
        }

        .student-guide-btn:active {
            transform: scale(0.98);
        }

        .student-guide-btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .student-guide-btn-text {
            flex: 1;
            min-width: 0;
        }

        .student-guide-btn-title {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: #93c5fd;
            line-height: 1.2;
        }

        .student-guide-btn-sub {
            display: block;
            font-size: 0.72rem;
            color: #cbd5e1;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-guide-btn-arrow {
            color: #93c5fd;
            display: flex;
            align-items: center;
            opacity: 0.75;
            transition: transform 0.2s;
        }

        .student-guide-btn:hover .student-guide-btn-arrow {
            transform: translateX(3px);
            opacity: 1;
        }

        /* Password toggle */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: var(--space-3);
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--clr-text-muted);
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            transition: var(--transition-fast);
            border-radius: var(--radius-sm);
        }

        .password-toggle:hover {
            color: var(--clr-text-primary);
        }

        #password {
            padding-right: 2.8rem;
        }

        /* Stars floating decoration */
        .star-deco {
            position: absolute;
            font-size: 1.4rem;
            opacity: 0.12;
            pointer-events: none;
            animation: float 4s ease-in-out infinite;
        }

        /* ── Modal Custom Mobile Optimized with Sticky Header & Scrollable Body ─── */
        .guide-modal {
            max-width: 480px;
            width: 100%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            border: 1.5px solid rgba(59, 130, 246, 0.35);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 35px rgba(59, 130, 246, 0.2);
            border-radius: 22px;
            overflow: hidden;
            background: #111728;
            padding: 0;
            margin: auto;
            animation: fadeIn 0.25s ease;
        }

        @media (max-width: 480px) {
            .guide-modal {
                border-radius: 18px;
                max-height: 88vh;
            }
        }

        .guide-modal-header {
            background: linear-gradient(135deg, rgba(30, 41, 69, 0.98) 0%, rgba(17, 24, 39, 0.98) 100%);
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .guide-modal-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .guide-modal-body {
            padding: 18px;
            overflow-y: auto;
            flex: 1 1 auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        .guide-modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .guide-modal-body::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        .guide-modal-body::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.35);
            border-radius: 4px;
        }

        .guide-modal-body::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.6);
        }

        .guide-modal-footer {
            padding: 12px 18px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(17, 24, 39, 0.95);
            flex-shrink: 0;
        }

        .guide-step-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .guide-step-card:hover {
            background: rgba(59, 130, 246, 0.06);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .guide-step-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.4);
            margin-top: 1px;
        }

        .guide-step-body {
            flex: 1;
            font-size: 0.84rem;
            color: #cbd5e1;
            line-height: 1.45;
        }

        .guide-step-body strong {
            color: #f8fafc;
            display: block;
            margin-bottom: 2px;
            font-size: 0.88rem;
        }

        .guide-pill {
            display: inline-block;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 215, 0, 0.35);
            color: #ffd700;
            padding: 1px 7px;
            border-radius: 6px;
            font-family: monospace;
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 0.02em;
        }

        .guide-action-box {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.25);
            border-radius: 14px;
            padding: 12px 14px;
            margin-top: 12px;
        }

        .btn-modal-action {
            width: 100%;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-modal-action-primary {
            background: #2563eb;
            color: #fff;
            border: none;
            margin-bottom: 8px;
        }

        .btn-modal-action-primary:hover {
            background: #1d4ed8;
        }

        .btn-modal-action-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-modal-action-secondary:hover {
            background: rgba(255, 255, 255, 0.14);
        }
    </style>
</head>

<body>
    <div class="auth-layout">
        <!-- Floating star decorations -->
        <span class="star-deco" style="top:10%;left:8%;animation-delay:0s;">⭐</span>
        <span class="star-deco" style="top:20%;right:10%;animation-delay:1s;font-size:1rem;">⭐</span>
        <span class="star-deco" style="bottom:15%;left:12%;animation-delay:2s;font-size:1.8rem;">⭐</span>
        <span class="star-deco" style="bottom:25%;right:8%;animation-delay:0.5s;font-size:0.9rem;">⭐</span>
        <span class="star-deco" style="top:50%;left:5%;animation-delay:1.5s;font-size:1.2rem;">⭐</span>

        <div class="auth-container">
            <div class="auth-card-clean">
                <!-- Logo -->
                <div class="login-logo">
                    <div class="login-logo-icon">⭐</div>
                    <div class="login-logo-name">Classroom Star</div>
                    <div class="login-logo-sub">Participation Leaderboard</div>
                </div>

                <h1 class="login-title">Selamat Datang</h1>
                <p class="login-subtitle">Masuk ke akun Anda untuk melanjutkan</p>

                <!-- Error alert -->
                <?php if ($error): ?>
                    <div class="alert alert-error mb-4" role="alert" id="login-alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Button Pengumuman Cara Login Murid -->
                <button type="button" class="student-guide-btn" id="btn-open-student-guide"
                    aria-label="Buka panduan login murid">
                    <div class="student-guide-btn-icon">📢</div>
                    <div class="student-guide-btn-text">
                        <span class="student-guide-btn-title">Cara Login Murid / Siswa</span>
                        <span class="student-guide-btn-sub">Petunjuk NIS & password default</span>
                    </div>
                    <div class="student-guide-btn-arrow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </div>
                </button>

                <!-- Login Form -->
                <form id="login-form" method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="form-group mb-3">
                        <label class="form-label form-label-required" for="username"
                            style="font-size: 0.82rem;">Username / NIS / Email</label>
                        <div class="input-group">
                            <span class="input-icon input-icon-left">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <input class="form-control has-icon-left" type="text" id="username" name="username"
                                placeholder="Masukkan username atau NIS"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username"
                                required autofocus>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label form-label-required" for="password"
                            style="font-size: 0.82rem;">Password</label>
                        <div class="input-group password-wrapper">
                            <span class="input-icon input-icon-left">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </span>
                            <input class="form-control has-icon-left" type="password" id="password" name="password"
                                placeholder="Masukkan password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" id="toggle-password"
                                aria-label="Toggle password visibility">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full btn-lg" id="login-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" y1="12" x2="3" y2="12" />
                        </svg>
                        <span class="btn-text">Masuk</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Modal Pengumuman & Panduan Login Murid ─── -->
    <div class="modal-backdrop" id="modal-student-guide" style="display: none;" role="dialog" aria-modal="true"
        aria-labelledby="modal-guide-title">
        <div class="modal guide-modal">
            <div class="guide-modal-header">
                <h3 class="guide-modal-title" id="modal-guide-title">
                    <span>📢 Panduan Login Murid</span>
                </h3>
                <button type="button" class="modal-close" id="btn-close-modal-x" aria-label="Tutup modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="guide-modal-body">
                <p class="text-sm text-muted mb-3 text-center" style="font-size: 0.82rem; line-height: 1.4;">
                    Gunakan akun sekolah Anda untuk masuk dan memantau skor bintang serta posisi peringkat kelas Anda:
                </p>

                <!-- Step 1 -->
                <div class="guide-step-card">
                    <div class="guide-step-num">1</div>
                    <div class="guide-step-body">
                        <strong>Username</strong>
                        Gunakan <span class="guide-pill">Nomor Induk Siswa (NIS)</span> yang terdaftar di sekolah (bisa
                        dilihat di buku absen / rapor).
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="guide-step-card">
                    <div class="guide-step-num">2</div>
                    <div class="guide-step-body">
                        <strong>Password Default</strong>
                        Password awal: <span class="guide-pill">pplg123</span> (huruf kecil semua tanpa spasi).
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="guide-step-card">
                    <div class="guide-step-num">3</div>
                    <div class="guide-step-body">
                        <strong>Ranking & Bintang</strong>
                        Pantau perolehan bintang dan peringkat kelasmu. Pastikan jumlah bintang kamu <strong>tidak
                            0</strong>!
                    </div>
                </div>

                <!-- Quick Action Box -->
                <div class="guide-action-box">
                    <button type="button" class="btn-modal-action btn-modal-action-secondary" id="btn-copy-pass"
                        onclick="copyPassword()">
                        📋 Salin Password: pplg123
                    </button>
                    <div class="mt-2 text-center text-xs text-muted" style="font-size: 0.74rem;">
                        💡 Tingkatkan terus softskill kalian agar menjadi <br>bintang di sekolah! </div>
                </div>
            </div>

            <div class="guide-modal-footer">
                <button type="button" class="btn btn-secondary btn-full" id="btn-close-modal"
                    style="font-size: 0.88rem; font-weight: 600;">
                    Mengerti & Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');

        const eyeOpen = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
        const eyeClosed = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;

        let visible = false;
        toggleBtn.addEventListener('click', () => {
            visible = !visible;
            passwordInput.type = visible ? 'text' : 'password';
            eyeIcon.innerHTML = visible ? eyeClosed : eyeOpen;
        });

        // Modal Logic
        const modal = document.getElementById('modal-student-guide');
        const btnOpen = document.getElementById('btn-open-student-guide');
        const btnClose = document.getElementById('btn-close-modal');
        const btnCloseX = document.getElementById('btn-close-modal-x');

        function openModal() {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        btnOpen.addEventListener('click', openModal);
        btnClose.addEventListener('click', closeModal);
        btnCloseX.addEventListener('click', closeModal);

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });

        function copyPassword() {
            const pass = 'pplg123';
            const btn = document.getElementById('btn-copy-pass');

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(pass).then(showCopied).catch(fallbackCopy);
            } else {
                fallbackCopy();
            }

            function showCopied() {
                const origText = btn.innerHTML;
                btn.innerHTML = '✅ Berhasil Disalin!';
                btn.style.borderColor = '#22c55e';
                btn.style.color = '#86efac';
                setTimeout(() => {
                    btn.innerHTML = origText;
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }, 2000);
            }

            function fallbackCopy() {
                const temp = document.createElement('textarea');
                temp.value = pass;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                showCopied();
            }
        }

        // Form loading state
        document.getElementById('login-form').addEventListener('submit', function (e) {
            const btn = document.getElementById('login-btn');
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) {
                e.preventDefault();
                return;
            }

            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>

</html>