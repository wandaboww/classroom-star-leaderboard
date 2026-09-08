<?php
/**
 * Auth — Session management & role-based access control
 */

class Auth
{
    /**
     * Inisialisasi session dengan konfigurasi aman
     */
    public static function startSession(): void
    {
        $appConfig = require dirname(__DIR__) . '/config/app.php';

        if (session_status() === PHP_SESSION_NONE) {
            session_name($appConfig['session_name']);
            session_set_cookie_params([
                'lifetime' => $appConfig['session_lifetime'],
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    /**
     * Login: verifikasi kredensial, buat session
     */
    public static function login(string $username, string $password): array|false
    {
        $user = Database::queryOne(
            'SELECT id, name, username, email, password_hash, role, is_active
             FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $username]
        );

        if (!$user) return false;
        if (!$user['is_active']) return false;
        if (!password_verify($password, $user['password_hash'])) return false;

        // Buat session
        self::startSession();
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_at']  = time();

        return $user;
    }

    /**
     * Logout: hapus session
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Cek apakah sudah login
     */
    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    /**
     * Dapatkan user yang sedang login
     */
    public static function user(): array|null
    {
        self::startSession();
        if (!self::check()) return null;
        return [
            'id'       => $_SESSION['user_id'],
            'name'     => $_SESSION['user_name'],
            'username' => $_SESSION['username'],
            'role'     => $_SESSION['role'],
        ];
    }

    /**
     * Dapatkan role user saat ini
     */
    public static function role(): string|null
    {
        self::startSession();
        return $_SESSION['role'] ?? null;
    }

    /**
     * Cek apakah user memiliki salah satu dari role yang diizinkan
     */
    public static function hasRole(array $roles): bool
    {
        $current = self::role();
        return $current !== null && in_array($current, $roles, true);
    }

    /**
     * Wajibkan login — redirect ke login jika tidak
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: ' . self::basePath('/login.php'));
            exit;
        }
    }

    /**
     * Wajibkan role tertentu — redirect / forbidden jika tidak sesuai
     */
    public static function requireRole(array $roles): void
    {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            // Redirect ke dashboard masing-masing
            self::redirectToDashboard();
        }
    }

    /**
     * Redirect ke dashboard sesuai role
     */
    public static function redirectToDashboard(): never
    {
        $role = self::role();
        $destinations = [
            'admin'   => '/admin/dashboard.php',
            'teacher' => '/teacher/dashboard.php',
            'student' => '/student/dashboard.php',
        ];
        $path = $destinations[$role] ?? '/login.php';
        header('Location: ' . self::basePath($path));
        exit;
    }

    /**
     * Helper: construct base path (untuk subfolder deploy)
     */
    public static function basePath(string $path = ''): string
    {
        // Deteksi base dari SCRIPT_NAME jika tidak di root
        $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
        return $base . $path;
    }

    /**
     * Generate CSRF token
     */
    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validasi CSRF token
     */
    public static function verifyCsrf(string $token): bool
    {
        self::startSession();
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
