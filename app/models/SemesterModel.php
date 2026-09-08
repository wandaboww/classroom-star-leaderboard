<?php
/**
 * SemesterModel — CRUD untuk tabel semesters
 */
class SemesterModel
{
    public static function all(): array
    {
        return Database::query(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM star_transactions st WHERE st.semester_id = s.id AND st.type = "award") as tx_count
             FROM semesters s
             ORDER BY s.academic_year DESC, s.id DESC'
        );
    }

    public static function find(int $id): array|false
    {
        return Database::queryOne('SELECT * FROM semesters WHERE id = ?', [$id]);
    }

    public static function active(): array|false
    {
        return Database::queryOne('SELECT * FROM semesters WHERE status = "active" LIMIT 1');
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO semesters (name, academic_year, status, started_at, ended_at)
             VALUES (?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['academic_year'],
                $data['status'] ?? 'draft',
                $data['started_at'] ?: null,
                $data['ended_at'] ?: null,
            ]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): int
    {
        return Database::execute(
            'UPDATE semesters SET name = ?, academic_year = ?, status = ?, started_at = ?, ended_at = ? WHERE id = ?',
            [
                $data['name'],
                $data['academic_year'],
                $data['status'],
                $data['started_at'] ?: null,
                $data['ended_at'] ?: null,
                $id,
            ]
        );
    }

    /**
     * Aktifkan satu semester, nonaktifkan yang lain
     */
    public static function activate(int $id): void
    {
        Database::execute('UPDATE semesters SET status = "closed" WHERE status = "active" AND id != ?', [$id]);
        Database::execute('UPDATE semesters SET status = "active" WHERE id = ?', [$id]);
        Database::execute("UPDATE settings SET value = ? WHERE `key` = 'semester_active_id'", [$id]);
    }

    public static function delete(int $id): array
    {
        $txCount = Database::queryOne('SELECT COUNT(*) as n FROM star_transactions WHERE semester_id = ?', [$id])['n'] ?? 0;
        if ($txCount > 0) {
            return ['ok' => false, 'message' => "Tidak bisa hapus: semester memiliki $txCount transaksi bintang."];
        }
        Database::execute('DELETE FROM semesters WHERE id = ?', [$id]);
        return ['ok' => true];
    }

    public static function forDropdown(): array
    {
        return Database::query('SELECT id, name, academic_year, status FROM semesters ORDER BY academic_year DESC, id DESC');
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'draft'    => 'Draft',
            'active'   => 'Active',
            'closed'   => 'Closed',
            'archived' => 'Archived',
            default    => $status,
        };
    }
}
