<?php
/**
 * API: Auth
 * POST /api/auth.php  — login via AJAX (opsional, login form menggunakan POST biasa)
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi.']);
    exit;
}

$user = Auth::login($username, $password);
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
    exit;
}

$dashboards = [
    'admin'   => '/admin/dashboard.php',
    'teacher' => '/teacher/dashboard.php',
    'student' => '/student/dashboard.php',
];
echo json_encode([
    'success'   => true,
    'role'      => $user['role'],
    'redirect'  => $dashboards[$user['role']] ?? '/login.php',
    'user_name' => $user['name'],
]);
