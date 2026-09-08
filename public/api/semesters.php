<?php
/**
 * API: Semesters — CRUD + activate
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

try {
    // Special: activate
    if ($method === 'POST' && $action === 'activate') {
        if (!$id) Response::error('ID tidak valid.', 400);
        SemesterModel::activate($id);
        Response::json(['message' => 'Semester berhasil diaktifkan.']);
    }

    switch ($method) {
        case 'GET':
            Response::json(['semesters' => SemesterModel::all()]);

        case 'POST':
            $d = Response::jsonBody();
            $errors = [];
            if (empty($d['name']))          $errors['name']          = 'Nama semester wajib diisi.';
            if (empty($d['academic_year'])) $errors['academic_year'] = 'Tahun ajaran wajib diisi.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            $newId = SemesterModel::create($d);
            Response::json(['id' => $newId, 'message' => 'Semester berhasil dibuat.']);

        case 'PUT':
            if (!$id) Response::error('ID tidak valid.', 400);
            $d = Response::jsonBody();
            $errors = [];
            if (empty($d['name']))          $errors['name']          = 'Nama semester wajib diisi.';
            if (empty($d['academic_year'])) $errors['academic_year'] = 'Tahun ajaran wajib diisi.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            SemesterModel::update($id, $d);
            Response::json(['message' => 'Semester berhasil diperbarui.']);

        case 'DELETE':
            if (!$id) Response::error('ID tidak valid.', 400);
            $result = SemesterModel::delete($id);
            if (!$result['ok']) Response::error($result['message'], 409);
            Response::json(['message' => 'Semester berhasil dihapus.']);

        default:
            Response::error('Method not allowed.', 405);
    }
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
