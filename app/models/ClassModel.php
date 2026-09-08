<?php
/**
 * ClassModel — CRUD untuk tabel classes
 */
class ClassModel
{
    public static function all(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return Database::query(
            "SELECT c.*, COUNT(s.id) as student_count
             FROM classes c
             LEFT JOIN students s ON s.class_id = c.id AND s.is_active = 1
             $where
             GROUP BY c.id
             ORDER BY c.academic_year DESC, c.name ASC"
        );
    }

    public static function find(int $id): array|false
    {
        return Database::queryOne('SELECT * FROM classes WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO classes (name, academic_year, is_active) VALUES (?, ?, ?)',
            [$data['name'], $data['academic_year'], $data['is_active'] ?? 1]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): int
    {
        return Database::execute(
            'UPDATE classes SET name = ?, academic_year = ?, is_active = ? WHERE id = ?',
            [$data['name'], $data['academic_year'], $data['is_active'] ?? 1, $id]
        );
    }

    public static function delete(int $id): array
    {
        // Cek apakah ada siswa di kelas ini
        $students = Database::queryOne('SELECT COUNT(*) as n FROM students WHERE class_id = ?', [$id])['n'] ?? 0;
        if ($students > 0) {
            return ['ok' => false, 'message' => "Tidak bisa hapus: kelas masih memiliki $students siswa."];
        }
        Database::execute('DELETE FROM classes WHERE id = ?', [$id]);
        return ['ok' => true];
    }

    public static function exists(string $name, string $academicYear, int $excludeId = 0): bool
    {
        $row = Database::queryOne(
            'SELECT id FROM classes WHERE name = ? AND academic_year = ? AND id != ?',
            [$name, $academicYear, $excludeId]
        );
        return (bool)$row;
    }

    public static function findByName(string $name): array|false
    {
        return Database::queryOne('SELECT * FROM classes WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1', [$name]);
    }

    public static function getOrCreate(string $name, string $academicYear = ''): int
    {
        $name = trim($name);
        if (!$name) return 0;
        $existing = self::findByName($name);
        if ($existing) return (int)$existing['id'];

        if (!$academicYear) {
            $sem = SemesterModel::active();
            $academicYear = $sem ? $sem['academic_year'] : date('Y') . '/' . (date('Y') + 1);
        }

        return self::create(['name' => $name, 'academic_year' => $academicYear, 'is_active' => 1]);
    }

    public static function forDropdown(): array
    {
        return Database::query('SELECT id, name, academic_year FROM classes WHERE is_active = 1 ORDER BY name');
    }
}
