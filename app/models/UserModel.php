<?php
/**
 * UserModel — CRUD untuk tabel users
 */
class UserModel
{
    public static function all(int $limit = 100, int $offset = 0): array
    {
        return Database::query(
            'SELECT id, name, username, email, role, is_active, created_at
             FROM users ORDER BY role, name LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function count(): int
    {
        return (int)(Database::queryOne('SELECT COUNT(*) as n FROM users')['n'] ?? 0);
    }

    public static function find(int $id): array|false
    {
        return Database::queryOne(
            'SELECT id, name, username, email, role, is_active, created_at FROM users WHERE id = ?',
            [$id]
        );
    }

    public static function findByUsername(string $username): array|false
    {
        return Database::queryOne(
            'SELECT id, name, username, email, role, is_active FROM users WHERE username = ?',
            [$username]
        );
    }

    public static function findByEmail(string $email): array|false
    {
        return Database::queryOne(
            'SELECT id, name, username, email, role, is_active FROM users WHERE email = ?',
            [$email]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO users (name, username, email, password_hash, role, is_active)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['role'],
                $data['is_active'] ?? 1,
            ]
        );
        $id = (int)Database::lastInsertId();

        // Auto-create teacher/student profile
        if ($data['role'] === 'teacher') {
            Database::execute(
                'INSERT INTO teachers (user_id, nip) VALUES (?, ?)',
                [$id, $data['nip'] ?? null]
            );
        }
        return $id;
    }

    public static function update(int $id, array $data): int
    {
        $fields = ['name = ?', 'username = ?', 'email = ?', 'role = ?', 'is_active = ?'];
        $params = [$data['name'], $data['username'], $data['email'], $data['role'], $data['is_active'] ?? 1];

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $params[] = $id;
        return Database::execute(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $params
        );
    }

    public static function delete(int $id): int
    {
        // Cegah hapus diri sendiri — dicek di API
        return Database::execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function toggleActive(int $id): void
    {
        Database::execute('UPDATE users SET is_active = NOT is_active WHERE id = ?', [$id]);
    }

    public static function existsUsername(string $username, int $excludeId = 0): bool
    {
        $row = Database::queryOne(
            'SELECT id FROM users WHERE username = ? AND id != ?',
            [$username, $excludeId]
        );
        return (bool)$row;
    }

    public static function existsEmail(string $email, int $excludeId = 0): bool
    {
        $row = Database::queryOne(
            'SELECT id FROM users WHERE email = ? AND id != ?',
            [$email, $excludeId]
        );
        return (bool)$row;
    }

    public static function search(string $q): array
    {
        $like = '%' . $q . '%';
        return Database::query(
            'SELECT id, name, username, email, role, is_active FROM users
             WHERE name LIKE ? OR username LIKE ? OR email LIKE ?
             ORDER BY name LIMIT 50',
            [$like, $like, $like]
        );
    }
}
