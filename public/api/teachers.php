<?php
/**
 * API: Teachers — read, update NIP, assign/remove classes
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);     // teacher_id
$action = $_GET['action'] ?? '';

try {
    // GET /api/teachers.php?id=N&action=classes
    if ($method === 'GET' && $action === 'classes' && $id) {
        Response::json(['classes' => TeacherModel::getClasses($id)]);
    }

    // POST /api/teachers.php?id=N&action=assign
    if ($method === 'POST' && $action === 'assign' && $id) {
        $d = Response::jsonBody();
        if (empty($d['class_id']) || empty($d['semester_id'])) Response::error('class_id dan semester_id wajib.', 400);
        $result = TeacherModel::assignClass($id, (int)$d['class_id'], (int)$d['semester_id']);
        if (!$result['ok']) Response::error($result['message'], 409);
        Response::json(['message' => 'Kelas berhasil di-assign.']);
    }

    // DELETE /api/teachers.php?id=N&action=remove_class&assignment_id=X
    if ($method === 'DELETE' && $action === 'remove_class' && $id) {
        $assignId = (int)($_GET['assignment_id'] ?? 0);
        if (!$assignId) Response::error('assignment_id tidak valid.', 400);
        TeacherModel::removeClassAssignment($assignId, $id);
        Response::json(['message' => 'Assignment dihapus.']);
    }

    switch ($method) {
        case 'GET':
            Response::json([
                'teachers' => TeacherModel::all(),
                'classes'  => ClassModel::forDropdown(),
                'semesters'=> SemesterModel::forDropdown(),
            ]);

        case 'PUT':
            if (!$id) Response::error('ID tidak valid.', 400);
            $d = Response::jsonBody();
            TeacherModel::update($id, $d);
            // Update user info juga
            $teacher = TeacherModel::find($id);
            if ($teacher && !empty($d['name'])) {
                UserModel::update($teacher['user_id'], array_merge($d, ['role' => 'teacher']));
            }
            Response::json(['message' => 'Teacher berhasil diperbarui.']);

        default:
            Response::error('Method not allowed.', 405);
    }
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
