<?php
/**
 * TeacherModel — CRUD untuk teacher + teacher_classes
 */
class TeacherModel
{
    public static function all(): array
    {
        return Database::query(
            'SELECT t.id, t.nip, t.user_id, u.name, u.username, u.email, u.is_active,
                    COUNT(DISTINCT tc.class_id) as class_count
             FROM teachers t
             JOIN users u ON t.user_id = u.id
             LEFT JOIN teacher_classes tc ON tc.teacher_id = t.id
             GROUP BY t.id
             ORDER BY u.name'
        );
    }

    public static function find(int $id): array|false
    {
        return Database::queryOne(
            'SELECT t.*, u.name, u.username, u.email, u.is_active
             FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?',
            [$id]
        );
    }

    public static function findByUserId(int $userId): array|false
    {
        return Database::queryOne('SELECT * FROM teachers WHERE user_id = ?', [$userId]);
    }

    public static function update(int $id, array $data): void
    {
        Database::execute('UPDATE teachers SET nip = ? WHERE id = ?', [$data['nip'] ?? null, $id]);
    }

    public static function getClasses(int $teacherId): array
    {
        return Database::query(
            'SELECT tc.*, c.name AS class_name, c.academic_year, s.name AS semester_name
             FROM teacher_classes tc
             JOIN classes   c ON tc.class_id   = c.id
             JOIN semesters s ON tc.semester_id = s.id
             WHERE tc.teacher_id = ?
             ORDER BY s.academic_year DESC, c.name',
            [$teacherId]
        );
    }

    public static function assignClass(int $teacherId, int $classId, int $semesterId): array
    {
        // Cek duplikat
        $exists = Database::queryOne(
            'SELECT id FROM teacher_classes WHERE teacher_id = ? AND class_id = ? AND semester_id = ?',
            [$teacherId, $classId, $semesterId]
        );
        if ($exists) return ['ok' => false, 'message' => 'Assignment sudah ada.'];

        Database::execute(
            'INSERT INTO teacher_classes (teacher_id, class_id, semester_id) VALUES (?, ?, ?)',
            [$teacherId, $classId, $semesterId]
        );
        return ['ok' => true];
    }

    public static function removeClassAssignment(int $assignmentId, int $teacherId): void
    {
        Database::execute(
            'DELETE FROM teacher_classes WHERE id = ? AND teacher_id = ?',
            [$assignmentId, $teacherId]
        );
    }

    public static function forDropdown(): array
    {
        return Database::query(
            'SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.is_active = 1 ORDER BY u.name'
        );
    }
}
