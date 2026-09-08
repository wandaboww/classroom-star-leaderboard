<?php
/**
 * StudentModel — CRUD untuk tabel students
 */
class StudentModel
{
    public static function all(int|array $classId = 0, string $search = '', int $limit = 100, int $offset = 0): array
    {
        $where  = ['s.is_active = 1'];
        $params = [];

        if (is_array($classId)) {
            if (!empty($classId)) {
                $where[] = 's.class_id IN (' . implode(',', array_fill(0, count($classId), '?')) . ')';
                $params  = array_merge($params, array_map('intval', array_values($classId)));
            } else {
                $where[] = '1 = 0';
            }
        } elseif ($classId > 0) {
            $where[]  = 's.class_id = ?';
            $params[] = $classId;
        }

        if ($search !== '') {
            $where[]  = '(s.name LIKE ? OR s.nis LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return Database::query(
            "SELECT s.*, c.name AS class_name,
                    COALESCE((SELECT SUM(st.stars) FROM star_transactions st WHERE st.student_id = s.id AND st.type='award'), 0) AS total_stars
             FROM students s
             JOIN classes c ON s.class_id = c.id
             $whereStr
             ORDER BY s.class_id, s.name
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public static function byClass(int $classId): array
    {
        return self::all($classId, '', 9999, 0);
    }

    public static function count(int|array $classId = 0, string $search = ''): int
    {
        $where  = ['is_active = 1'];
        $params = [];

        if (is_array($classId)) {
            if (!empty($classId)) {
                $where[] = 'class_id IN (' . implode(',', array_fill(0, count($classId), '?')) . ')';
                $params  = array_merge($params, array_map('intval', array_values($classId)));
            } else {
                $where[] = '1 = 0';
            }
        } elseif ($classId > 0) {
            $where[]  = 'class_id = ?';
            $params[] = $classId;
        }

        if ($search !== '') {
            $where[]  = '(name LIKE ? OR nis LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);
        return (int)(Database::queryOne("SELECT COUNT(*) as n FROM students $whereStr", $params)['n'] ?? 0);
    }

    public static function paginate(int $page = 1, int $limit = 50, int|array $classId = 0, string $search = ''): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $limit;
        $total  = self::count($classId, $search);
        $items  = self::all($classId, $search, $limit, $offset);

        return [
            'students'    => $items,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'pages'       => ceil($total / $limit),
            'total_pages' => ceil($total / $limit)
        ];
    }

    public static function find(int $id): array|false
    {
        return Database::queryOne(
            'SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.id = ?',
            [$id]
        );
    }

    public static function findByNis(string $nis): array|false
    {
        return Database::queryOne('SELECT * FROM students WHERE nis = ?', [$nis]);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO students (nis, name, class_id, is_active) VALUES (?, ?, ?, 1)',
            [$data['nis'], $data['name'], $data['class_id']]
        );
        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): int
    {
        return Database::execute(
            'UPDATE students SET nis = ?, name = ?, class_id = ? WHERE id = ?',
            [$data['nis'], $data['name'], $data['class_id'], $id]
        );
    }

    public static function delete(int $id): array
    {
        $txCount = Database::queryOne('SELECT COUNT(*) as n FROM star_transactions WHERE student_id = ?', [$id])['n'] ?? 0;
        if ($txCount > 0) {
            // Soft delete saja
            Database::execute('UPDATE students SET is_active = 0 WHERE id = ?', [$id]);
            return ['ok' => true, 'soft' => true, 'message' => 'Siswa dinonaktifkan (memiliki riwayat bintang).'];
        }
        Database::execute('DELETE FROM students WHERE id = ?', [$id]);
        return ['ok' => true, 'soft' => false];
    }

    public static function existsNis(string $nis, int $excludeId = 0): bool
    {
        return (bool)Database::queryOne(
            'SELECT id FROM students WHERE nis = ? AND id != ?',
            [$nis, $excludeId]
        );
    }

    public static function getTotalStars(int $studentId, int $semesterId = 0): int
    {
        $where  = 'student_id = ? AND type = "award"';
        $params = [$studentId];
        if ($semesterId > 0) { $where .= ' AND semester_id = ?'; $params[] = $semesterId; }
        return (int)(Database::queryOne("SELECT COALESCE(SUM(stars),0) as n FROM star_transactions WHERE $where", $params)['n'] ?? 0);
    }

    /**
     * Bulk insert dari XLSX — membaca kelas langsung dari kolom tiap baris
     */
    public static function bulkImport(array $rows, int $fallbackClassId = 0): array
    {
        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        $activeSemester = SemesterModel::active();
        $academicYear   = $activeSemester ? $activeSemester['academic_year'] : date('Y') . '/' . (date('Y') + 1);

        foreach ($rows as $i => $row) {
            $rowNum = $row['_row_num'] ?? ($i + 2);
            $nis    = trim((string)($row['nis'] ?? ''));
            $name   = trim((string)($row['name'] ?? ''));
            $cName  = trim((string)($row['class'] ?? ''));

            if (!$nis && !$name) {
                continue;
            }

            if (!$nis) {
                $skipped++;
                $errors[] = "Baris $rowNum: NIS wajib diisi (Nama: $name).";
                continue;
            }

            if (!$name) {
                $skipped++;
                $errors[] = "Baris $rowNum: Nama Siswa wajib diisi (NIS: $nis).";
                continue;
            }

            // Tentukan class_id
            $classId = 0;
            if ($cName !== '') {
                $classId = ClassModel::getOrCreate($cName, $academicYear);
            } elseif ($fallbackClassId > 0) {
                $classId = $fallbackClassId;
            }

            if (!$classId) {
                $skipped++;
                $errors[] = "Baris $rowNum ($name): Kolom Kelas kosong.";
                continue;
            }

            // Cek apakah NIS sudah ada
            $existing = self::findByNis($nis);
            if ($existing) {
                self::update((int)$existing['id'], [
                    'nis'      => $nis,
                    'name'     => $name,
                    'class_id' => $classId,
                ]);
                $updated++;
            } else {
                self::create([
                    'nis'      => $nis,
                    'name'     => $name,
                    'class_id' => $classId,
                ]);
                $inserted++;
            }
        }

        return [
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'total'    => $inserted + $updated,
            'errors'   => $errors,
        ];
    }

    /**
     * Hitung berapa banyak siswa aktif yang belum memiliki akun login
     */
    public static function countWithoutAccount(): int
    {
        return (int)(Database::queryOne('SELECT COUNT(*) as n FROM students WHERE user_id IS NULL AND is_active = 1')['n'] ?? 0);
    }

    /**
     * Generate akun login siswa massal di tabel users
     *
     * @param string $defaultPassword Password default untuk semua akun siswa baru
     * @return array [created => int, skipped => int, total => int]
     */
    public static function generateAccounts(string $defaultPassword = 'pplg123'): array
    {
        $students = Database::query('SELECT id, nis, name FROM students WHERE user_id IS NULL AND is_active = 1');
        $created = 0;
        $skipped = 0;
        $hash = password_hash($defaultPassword, PASSWORD_BCRYPT);

        foreach ($students as $s) {
            $username = trim((string)$s['nis']);
            if (empty($username)) {
                $skipped++;
                continue;
            }

            // Cek apakah user dengan username ini sudah ada di tabel users
            $existingUser = Database::queryOne('SELECT id FROM users WHERE username = ?', [$username]);
            if ($existingUser) {
                Database::execute('UPDATE students SET user_id = ? WHERE id = ?', [(int)$existingUser['id'], (int)$s['id']]);
                $created++;
            } else {
                $email = $username . '@student.local';
                Database::execute(
                    'INSERT INTO users (name, username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, "student", 1)',
                    [$s['name'], $username, $email, $hash]
                );
                $newUserId = (int)Database::lastInsertId();
                Database::execute('UPDATE students SET user_id = ? WHERE id = ?', [$newUserId, (int)$s['id']]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'total'   => count($students),
        ];
    }
}

