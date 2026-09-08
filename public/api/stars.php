<?php
/**
 * API: Stars — Award, Batch Award, Reversal & History
 *
 * Endpoints:
 * - GET    /api/stars.php?action=recent       → List recent star transactions by teacher
 * - POST   /api/stars.php                     → Award stars to 1 student or batch of students
 * - POST   /api/stars.php?action=reversal     → Auditable reversal (Undo) of an award
 * - DELETE /api/stars.php?id=N                → Hard delete (Admin only)
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin', 'teacher']);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$action = trim($_GET['action'] ?? '');
$user   = Auth::user();

try {
    // ── 1. GET RECENT TRANSACTIONS ──────────────────────────
    if ($method === 'GET' && $action === 'recent') {
        $teacherId = null;
        if ($user['role'] === 'teacher') {
            $t = Database::queryOne('SELECT id FROM teachers WHERE user_id = ?', [$user['id']]);
            if ($t) $teacherId = (int)$t['id'];
        }

        $params = [];
        $whereClause = "WHERE st.type IN ('award', 'reversal')";
        if ($teacherId) {
            $whereClause .= " AND st.teacher_id = ?";
            $params[] = $teacherId;
        }

        $recent = Database::query(
            "SELECT st.id, st.student_id, st.teacher_id, st.class_id, st.semester_id,
                    st.stars, st.type, st.ref_id, st.note, st.awarded_at,
                    s.name AS student_name, s.nis, c.name AS class_name,
                    u.name AS teacher_name,
                    (SELECT COUNT(*) FROM star_transactions rev WHERE rev.ref_id = st.id AND rev.type = 'reversal') AS is_reversed
             FROM star_transactions st
             JOIN students s ON st.student_id = s.id
             JOIN classes  c ON st.class_id   = c.id
             JOIN teachers t ON st.teacher_id = t.id
             JOIN users    u ON t.user_id     = u.id
             $whereClause
             ORDER BY st.awarded_at DESC
             LIMIT 15",
            $params
        );

        Response::json(['transactions' => $recent]);
    }

    // ── 2. REVERSAL / UNDO ──────────────────────────────────
    if ($method === 'POST' && $action === 'reversal') {
        $d    = Response::jsonBody();
        $txId = (int)($d['tx_id'] ?? 0);
        $note = trim($d['note'] ?? 'Dibatalkan oleh guru/admin');

        if (!$txId) Response::error('ID transaksi wajib diisi.', 400);

        // Ambil transaksi asal
        $tx = Database::queryOne('SELECT * FROM star_transactions WHERE id = ? AND type = "award"', [$txId]);
        if (!$tx) Response::error('Transaksi asal tidak ditemukan atau sudah dibatalkan.', 404);

        // Pastikan guru hanya bisa membatalkan transaksi miliknya sendiri
        if ($user['role'] === 'teacher') {
            $t = Database::queryOne('SELECT id FROM teachers WHERE user_id = ?', [$user['id']]);
            if (!$t || $t['id'] != $tx['teacher_id']) {
                Response::error('Kamu hanya bisa membatalkan transaksi yang kamu berikan sendiri.', 403);
            }
        }

        // Cek apakah sudah pernah di-reverse sebelumnya
        $alreadyReversed = Database::queryOne('SELECT id FROM star_transactions WHERE ref_id = ? AND type = "reversal"', [$txId]);
        if ($alreadyReversed) {
            Response::error('Transaksi ini sudah pernah dibatalkan sebelumnya.', 422);
        }

        // Insert reversal transaction (-stars)
        $teacherId = $tx['teacher_id'];
        Database::execute(
            'INSERT INTO star_transactions (student_id, teacher_id, class_id, semester_id, stars, type, ref_id, note, awarded_at)
             VALUES (?, ?, ?, ?, ?, "reversal", ?, ?, NOW())',
            [$tx['student_id'], $teacherId, $tx['class_id'], $tx['semester_id'], $tx['stars'], $txId, $note]
        );

        // Hitung total baru
        $newTotal = (int)(Database::queryOne(
            'SELECT COALESCE(SUM(CASE WHEN type="award" THEN stars WHEN type="reversal" THEN -stars ELSE 0 END), 0) as n
             FROM star_transactions WHERE student_id = ?',
            [$tx['student_id']]
        )['n'] ?? 0);

        $student = Database::queryOne('SELECT name FROM students WHERE id = ?', [$tx['student_id']]);

        Response::json([
            'message'      => "Pemberian bintang ({$tx['stars']}⭐) untuk " . ($student['name'] ?? 'siswa') . " berhasil dibatalkan.",
            'new_total'    => max(0, $newTotal),
            'reversed_id'  => $txId,
        ]);
    }

    // ── 3. AWARD STARS (Single & Batch) ──────────────────────
    if ($method === 'POST') {
        $d = Response::jsonBody();

        // Bisa single 'student_id' atau array 'student_ids'
        $studentIds = [];
        if (!empty($d['student_ids']) && is_array($d['student_ids'])) {
            $studentIds = array_map('intval', array_filter($d['student_ids']));
        } elseif (!empty($d['student_id'])) {
            $studentIds = [(int)$d['student_id']];
        }

        $classId    = (int)($d['class_id']     ?? 0);
        $semesterId = (int)($d['semester_id']  ?? 0);
        $stars      = (int)($d['stars']        ?? 0);
        $note       = trim($d['note']          ?? '');

        // Validasi input
        $errors = [];
        if (empty($studentIds)) $errors['student_ids'] = 'Minimal satu siswa wajib dipilih.';
        if (!$classId)          $errors['class_id']    = 'Kelas wajib dipilih.';
        if (!$semesterId)       $errors['semester_id'] = 'Semester wajib dipilih.';

        // Pengaturan batas bintang
        $settings    = Database::query("SELECT `key`, value FROM settings");
        $settingsMap = array_column($settings, 'value', 'key');
        $minStars    = max(1,  (int)($settingsMap['min_stars_per_award']  ?? 1));
        $maxStars    = min(10, (int)($settingsMap['max_stars_per_award']  ?? 5));
        $maxOpp      = max(1,  (int)($settingsMap['max_star_opportunity'] ?? 100));

        if ($stars < $minStars || $stars > $maxStars) {
            $errors['stars'] = "Bintang harus antara $minStars dan $maxStars.";
        }

        if ($errors) Response::error('Validasi gagal.', 422, $errors);

        // Dapatkan teacher_id & validasi hak akses
        if ($user['role'] === 'teacher') {
            $teacher = Database::queryOne('SELECT id FROM teachers WHERE user_id = ?', [$user['id']]);
            if (!$teacher) Response::error('Profil teacher tidak ditemukan.', 403);
            $teacherId = (int)$teacher['id'];

            $assigned = Database::queryOne(
                'SELECT id FROM teacher_classes WHERE teacher_id = ? AND class_id = ? AND semester_id = ?',
                [$teacherId, $classId, $semesterId]
            );
            if (!$assigned) {
                // Cek fallback jika semester di teacher_classes berbeda namun guru ditugaskan di kelas ini
                $anyAssigned = Database::queryOne(
                    'SELECT id FROM teacher_classes WHERE teacher_id = ? AND class_id = ?',
                    [$teacherId, $classId]
                );
                if (!$anyAssigned) Response::error('Kamu tidak memiliki akses ke kelas ini.', 403);
            }
        } else {
            // Admin: ambil profil teacher atau buat id dummy
            $teacher   = Database::queryOne('SELECT id FROM teachers WHERE user_id = ?', [$user['id']]);
            $teacherId = $teacher ? (int)$teacher['id'] : 1;
        }

        // Validasi siswa-siswa berada di kelas yang sesuai
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $validStudents = Database::query(
            "SELECT id, name, nis FROM students WHERE id IN ($placeholders) AND class_id = ? AND is_active = 1",
            array_merge($studentIds, [$classId])
        );

        if (empty($validStudents)) {
            Response::error('Siswa tidak ditemukan atau tidak aktif di kelas ini.', 404);
        }

        // Eksekusi insert transaksi
        $createdTxIds = [];
        $results = [];

        foreach ($validStudents as $st) {
            Database::execute(
                'INSERT INTO star_transactions (student_id, teacher_id, class_id, semester_id, stars, type, note, awarded_at)
                 VALUES (?, ?, ?, ?, ?, "award", ?, NOW())',
                [$st['id'], $teacherId, $classId, $semesterId, $stars, $note ?: null]
            );
            $txId = (int)Database::lastInsertId();
            $createdTxIds[] = $txId;

            // Hitung total bintang baru siswa
            $newTotal = (int)(Database::queryOne(
                'SELECT COALESCE(SUM(CASE WHEN type="award" THEN stars WHEN type="reversal" THEN -stars ELSE 0 END), 0) as n
                 FROM star_transactions WHERE student_id = ?',
                [$st['id']]
            )['n'] ?? 0);

            // Hitung level baru
            $pct = min(100, round(($newTotal / $maxOpp) * 100));
            $level = Database::queryOne('SELECT level_number, predicate, additional_score FROM levels WHERE min_percent <= ? AND max_percent >= ? LIMIT 1', [$pct, $pct]);

            $results[] = [
                'student_id'   => $st['id'],
                'student_name' => $st['name'],
                'tx_id'        => $txId,
                'new_total'    => max(0, $newTotal),
                'percent'      => $pct,
                'level'        => $level,
            ];
        }

        $count = count($results);
        $names = implode(', ', array_slice(array_column($results, 'student_name'), 0, 3));
        if ($count > 3) $names .= ' +' . ($count - 3) . ' siswa lainnya';

        Response::json([
            'message'      => "$stars ⭐ berhasil diberikan kepada $names!",
            'count'        => $count,
            'stars'        => $stars,
            'results'      => $results,
            'primary_result' => $results[0] ?? null,
        ]);
    }

    // ── 4. HARD DELETE (Admin only) ─────────────────────────
    if ($method === 'DELETE') {
        Auth::requireRole(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) Response::error('ID tidak valid.', 400);

        $tx = Database::queryOne('SELECT * FROM star_transactions WHERE id = ?', [$id]);
        if (!$tx) Response::error('Transaksi tidak ditemukan.', 404);

        Database::execute('DELETE FROM star_transactions WHERE id = ? OR ref_id = ?', [$id, $id]);
        Response::json(['message' => 'Transaksi bintang berhasil dihapus.']);
    }

    Response::error('Method not allowed.', 405);
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
