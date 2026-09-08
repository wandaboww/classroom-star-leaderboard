<?php
/**
 * API: Classes — CRUD
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

try {
    switch ($method) {
        case 'GET':
            Response::json(['classes' => ClassModel::all()]);

        case 'POST':
            $d = Response::jsonBody();
            $errors = [];
            if (empty($d['name']))          $errors['name']          = 'Nama kelas wajib diisi.';
            if (empty($d['academic_year'])) $errors['academic_year'] = 'Tahun ajaran wajib diisi.';
            if (!$errors && ClassModel::exists($d['name'] ?? '', $d['academic_year'] ?? ''))
                $errors['name'] = 'Kelas sudah ada di tahun ajaran ini.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            $newId = ClassModel::create($d);
            Response::json(['id' => $newId, 'message' => 'Kelas berhasil dibuat.']);

        case 'PUT':
            if (!$id) Response::error('ID tidak valid.', 400);
            $d = Response::jsonBody();
            $errors = [];
            if (empty($d['name']))          $errors['name']          = 'Nama kelas wajib diisi.';
            if (empty($d['academic_year'])) $errors['academic_year'] = 'Tahun ajaran wajib diisi.';
            if (!$errors && ClassModel::exists($d['name'] ?? '', $d['academic_year'] ?? '', $id))
                $errors['name'] = 'Kelas sudah ada di tahun ajaran ini.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            ClassModel::update($id, $d);
            Response::json(['message' => 'Kelas berhasil diperbarui.']);

        case 'DELETE':
            if (!$id) Response::error('ID tidak valid.', 400);
            $result = ClassModel::delete($id);
            if (!$result['ok']) Response::error($result['message'], 409);
            Response::json(['message' => 'Kelas berhasil dihapus.']);

        default:
            Response::error('Method not allowed.', 405);
    }
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
