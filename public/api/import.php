<?php
/**
 * API: Import XLSX + Export siswa
 *
 * GET  /api/import.php?template=1          → download template XLSX
 * GET  /api/import.php?export=1&class_id=N → download export XLSX
 * POST /api/import.php                     → upload & import file XLSX
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin', 'teacher']);

$method   = $_SERVER['REQUEST_METHOD'];
$template = isset($_GET['template']);
$export   = isset($_GET['export']);

// ── Download template ──────────────────────────────────────
if ($method === 'GET' && $template) {
    $spreadsheet = XlsxHelper::generateImportTemplate();
    XlsxHelper::download($spreadsheet, 'template_import_siswa.xlsx');
}

// ── Export siswa ───────────────────────────────────────────
if ($method === 'GET' && $export) {
    $classId  = (int)($_GET['class_id'] ?? 0);
    $students = StudentModel::all($classId, '', 9999, 0);

    // Teacher: cek hak akses
    if (Auth::role() === 'teacher') {
        $teacher   = TeacherModel::findByUserId(Auth::user()['id']);
        $myClasses = $teacher ? array_column(TeacherModel::getClasses($teacher['id']), 'class_id') : [];
        if ($classId && !in_array($classId, $myClasses)) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Akses ditolak.']));
        }
    }

    $spreadsheet = XlsxHelper::generateStudentExport($students);
    $filename    = 'students_export_' . date('Ymd_His') . '.xlsx';
    XlsxHelper::download($spreadsheet, $filename);
}

// ── Import XLSX ────────────────────────────────────────────
if ($method === 'POST') {
    header('Content-Type: application/json');

    $fallbackClassId = (int)($_POST['class_id'] ?? 0);

    // Validasi file upload
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErr = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errMsg = match ($uploadErr) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file terlalu besar (maks 10MB).',
            UPLOAD_ERR_PARTIAL                        => 'File hanya terunggah sebagian.',
            UPLOAD_ERR_NO_FILE                        => 'Tidak ada file yang dipilih.',
            default                                   => 'File upload gagal diproses server.',
        };
        Response::error($errMsg, 400);
    }

    $file = $_FILES['file'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validasi ekstensi strictly .xlsx
    if ($ext !== 'xlsx') {
        Response::error('Format file harus berekstensi .xlsx (Microsoft Excel OpenXML).', 400);
    }

    // Validasi ukuran berkas (maks 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        Response::error('Ukuran file melebihi batas 10MB.', 400);
    }

    $tmpPath = $file['tmp_name'];

    try {
        $rows = XlsxHelper::readImportFile($tmpPath);
        if (empty($rows)) {
            Response::error('File Excel kosong atau format kolom tidak sesuai template.', 400);
        }

        $result = StudentModel::bulkImport($rows, $fallbackClassId);
        // Otomatis buatkan akun login siswa baru dengan password default pplg123
        $accResult = StudentModel::generateAccounts('pplg123');
        $result['accounts_created'] = $accResult['created'];
        Response::json($result);
    } catch (Throwable $e) {
        Response::error('Gagal memproses file Excel: ' . $e->getMessage(), 500);
    }
}

// Fallback
header('Content-Type: application/json');
Response::error('Method not allowed.', 405);
