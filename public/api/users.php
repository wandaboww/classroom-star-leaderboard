<?php
/**
 * API: Users — CRUD
 * GET    /api/users.php          → list
 * POST   /api/users.php          → create
 * PUT    /api/users.php?id=N     → update
 * DELETE /api/users.php?id=N     → delete
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

try {
    switch ($method) {

        case 'GET':
            $search = trim($_GET['search'] ?? '');
            $users  = $search ? UserModel::search($search) : UserModel::all();
            Response::json(['users' => $users, 'total' => count($users)]);

        case 'POST':
            $d = Response::jsonBody();
            // Validasi
            $errors = [];
            if (empty($d['name']))     $errors['name']     = 'Nama wajib diisi.';
            if (empty($d['username'])) $errors['username'] = 'Username wajib diisi.';
            if (empty($d['email']))    $errors['email']    = 'Email wajib diisi.';
            if (empty($d['password'])) $errors['password'] = 'Password wajib diisi.';
            if (empty($d['role']))     $errors['role']     = 'Role wajib dipilih.';
            if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
            if (UserModel::existsUsername($d['username'] ?? ''))       $errors['username'] = 'Username sudah dipakai.';
            if (UserModel::existsEmail($d['email'] ?? ''))             $errors['email']    = 'Email sudah dipakai.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            $newId = UserModel::create($d);
            Response::json(['id' => $newId, 'message' => 'User berhasil dibuat.']);

        case 'PUT':
            if (!$id) Response::error('ID tidak valid.', 400);
            $d = Response::jsonBody();
            $errors = [];
            if (empty($d['name']))     $errors['name']     = 'Nama wajib diisi.';
            if (empty($d['username'])) $errors['username'] = 'Username wajib diisi.';
            if (empty($d['email']))    $errors['email']    = 'Email wajib diisi.';
            if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
            if (UserModel::existsUsername($d['username'] ?? '', $id))  $errors['username'] = 'Username sudah dipakai.';
            if (UserModel::existsEmail($d['email'] ?? '', $id))         $errors['email']   = 'Email sudah dipakai.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            UserModel::update($id, $d);
            Response::json(['message' => 'User berhasil diperbarui.']);

        case 'DELETE':
            if (!$id) Response::error('ID tidak valid.', 400);
            if ($id === Auth::user()['id']) Response::error('Tidak bisa menghapus akun sendiri.', 403);
            UserModel::delete($id);
            Response::json(['message' => 'User berhasil dihapus.']);

        default:
            Response::error('Method not allowed.', 405);
    }
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
