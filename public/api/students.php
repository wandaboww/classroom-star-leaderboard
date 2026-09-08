<?php
/**
 * API: Students — CRUD
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin', 'teacher']);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

try {
    switch ($method) {
        case 'GET':
            $classId = (int)($_GET['class_id'] ?? 0);
            $search  = trim($_GET['search'] ?? '');
            $page    = max(1, (int)($_GET['page'] ?? 1));
            $limit   = (int)($_GET['limit'] ?? 50);
            if ($limit <= 0 || $limit > 500) $limit = 50;
            $offset  = ($page - 1) * $limit;

            $filterClass = $classId;

            // Teacher: batasi hanya kelas yang diajar
            if (Auth::role() === 'teacher') {
                $teacher   = TeacherModel::findByUserId(Auth::user()['id']);
                $myClasses = $teacher ? TeacherModel::getClasses($teacher['id']) : [];
                $classIds  = array_column($myClasses, 'class_id');

                if (empty($classIds)) {
                    Response::json(['students' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'pages' => 0]);
                }

                if ($classId === 0) {
                    $filterClass = $classIds;
                } else {
                    if (!in_array($classId, array_map('intval', $classIds), true)) {
                        Response::json(['students' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'pages' => 0]);
                    }
                }
            }

            $students = StudentModel::all($filterClass, $search, $limit, $offset);
            $total    = StudentModel::count($filterClass, $search);
            $pages    = (int)ceil($total / $limit);
            Response::json([
                'students'    => $students,
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'pages'       => $pages,
                'total_pages' => $pages
            ]);

        case 'POST':
            if (isset($_GET['action']) && $_GET['action'] === 'generate_accounts') {
                Auth::requireRole(['admin']);
                $body = Response::jsonBody();
                $pass = !empty($body['default_password']) ? trim($body['default_password']) : 'pplg123';
                $res  = StudentModel::generateAccounts($pass);
                Response::json([
                    'ok'      => true,
                    'created' => $res['created'],
                    'message' => $res['created'] > 0 
                        ? "Berhasil membuat/menautkan {$res['created']} akun siswa dengan password default '{$pass}'." 
                        : "Semua siswa sudah memiliki akun login."
                ]);
            }

            $d = Response::jsonBody();
            $errors = [];
            if (empty($d['nis']))      $errors['nis']      = 'NIS wajib diisi.';
            if (empty($d['name']))     $errors['name']     = 'Nama wajib diisi.';
            if (empty($d['class_id'])) $errors['class_id'] = 'Kelas wajib dipilih.';
            if (!$errors && StudentModel::existsNis($d['nis'] ?? ''))
                $errors['nis'] = 'NIS sudah terdaftar.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            $newId = StudentModel::create($d);
            // Otomatis buat akun untuk siswa baru
            StudentModel::generateAccounts('pplg123');
            Response::json(['id' => $newId, 'message' => 'Siswa berhasil ditambahkan dan akun login telah dibuat.']);

        case 'PUT':
            if (!$id) Response::error('ID tidak valid.', 400);
            $d = Response::jsonBody();
            $errors = [];
            if (empty($d['nis']))      $errors['nis']      = 'NIS wajib diisi.';
            if (empty($d['name']))     $errors['name']     = 'Nama wajib diisi.';
            if (empty($d['class_id'])) $errors['class_id'] = 'Kelas wajib dipilih.';
            if (!$errors && StudentModel::existsNis($d['nis'] ?? '', $id))
                $errors['nis'] = 'NIS sudah dipakai siswa lain.';
            if ($errors) Response::error('Validasi gagal.', 422, $errors);

            StudentModel::update($id, $d);
            Response::json(['message' => 'Data siswa berhasil diperbarui.']);

        case 'DELETE':
            if (!$id) Response::error('ID tidak valid.', 400);
            $result = StudentModel::delete($id);
            Response::json(['message' => $result['message'] ?? 'Siswa berhasil dihapus.', 'soft' => $result['soft'] ?? false]);

        default:
            Response::error('Method not allowed.', 405);
    }
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
