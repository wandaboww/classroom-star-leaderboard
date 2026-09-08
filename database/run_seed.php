<?php
/**
 * Run Seed Script
 */
require_once dirname(__DIR__) . '/app/bootstrap.php';

echo "Running Database Seeder...\n";

$passHash = password_hash('Admin@123', PASSWORD_BCRYPT);
$teacherPassHash = password_hash('Guru@123', PASSWORD_BCRYPT);
$studentPassHash = password_hash('Siswa@123', PASSWORD_BCRYPT);

try {
    // 1. Admin
    $admin = Database::queryOne("SELECT id FROM users WHERE username = 'admin'");
    if (!$admin) {
        Database::execute(
            "INSERT INTO users (name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)",
            ['Administrator', 'admin', 'admin@classroomstar.local', $passHash, 'admin']
        );
        echo "Created admin user.\n";
    }

    // 2. Levels
    $levelCount = Database::queryOne("SELECT COUNT(*) as n FROM levels")['n'] ?? 0;
    if ($levelCount == 0) {
        $levels = [
            [1, 'Very Low',    0.00,  19.99, 0],
            [2, 'Low',        20.00,  39.99, 1],
            [3, 'Fair',       40.00,  59.99, 2],
            [4, 'Good',       60.00,  74.99, 3],
            [5, 'Very Good',  75.00,  89.99, 4],
            [6, 'Outstanding',90.00, 100.00, 5]
        ];
        foreach ($levels as $l) {
            Database::execute(
                "INSERT INTO levels (level_number, predicate, min_percent, max_percent, additional_score) VALUES (?, ?, ?, ?, ?)",
                $l
            );
        }
        echo "Inserted levels.\n";
    }

    // 3. Settings
    $settings = [
        ['app_name',              'Classroom Star',     'Nama Aplikasi'],
        ['max_stars_per_award',   '5',                  'Maksimum Bintang per Pemberian'],
        ['min_stars_per_award',   '1',                  'Minimum Bintang per Pemberian'],
        ['max_star_opportunity',  '100',                'Maksimum Peluang Bintang per Semester'],
        ['leaderboard_public',    '1',                  'Leaderboard Publik (siswa bisa lihat sesama)'],
        ['semester_active_id',    '1',                  'ID Semester Aktif']
    ];
    foreach ($settings as [$k, $v, $lbl]) {
        Database::execute(
            "INSERT INTO settings (`key`, value, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = ?",
            [$k, $v, $lbl, $lbl]
        );
    }

    // 4. Semesters
    $semCount = Database::queryOne("SELECT COUNT(*) as n FROM semesters")['n'] ?? 0;
    if ($semCount == 0) {
        Database::execute(
            "INSERT INTO semesters (name, academic_year, status, started_at, ended_at) VALUES (?, ?, ?, ?, ?)",
            ['Semester Ganjil 2026/2027', '2026/2027', 'active', '2026-07-15', '2026-12-20']
        );
        echo "Inserted semester.\n";
    }
    $activeSemester = Database::queryOne("SELECT id FROM semesters WHERE status = 'active' LIMIT 1");
    $semesterId = $activeSemester ? (int)$activeSemester['id'] : 1;
    Database::execute("UPDATE settings SET value = ? WHERE `key` = 'semester_active_id'", [$semesterId]);

    // 5. Classes
    $classCount = Database::queryOne("SELECT COUNT(*) as n FROM classes")['n'] ?? 0;
    if ($classCount == 0) {
        Database::execute("INSERT INTO classes (name, academic_year) VALUES ('XI IPA 1', '2026/2027'), ('XI IPA 2', '2026/2027'), ('XI IPS 1', '2026/2027')");
        echo "Inserted classes.\n";
    }
    $class1 = Database::queryOne("SELECT id FROM classes WHERE name = 'XI IPA 1'")['id'] ?? 1;
    $class2 = Database::queryOne("SELECT id FROM classes WHERE name = 'XI IPA 2'")['id'] ?? 2;
    $class3 = Database::queryOne("SELECT id FROM classes WHERE name = 'XI IPS 1'")['id'] ?? 3;

    // 6. Teachers
    $t1User = Database::queryOne("SELECT id FROM users WHERE username = 'guru_budi'");
    if (!$t1User) {
        Database::execute("INSERT INTO users (name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)",
            ['Budi Santoso, S.Pd.', 'guru_budi', 'budi@classroomstar.local', $teacherPassHash, 'teacher']);
        $t1UserId = Database::lastInsertId();
    } else {
        $t1UserId = $t1User['id'];
    }
    
    $t1Exist = Database::queryOne("SELECT id FROM teachers WHERE user_id = ?", [$t1UserId]);
    if (!$t1Exist) {
        Database::execute("INSERT INTO teachers (user_id, nip) VALUES (?, ?)",
            [$t1UserId, '198501012010011001']);
        $t1Id = Database::lastInsertId();
        Database::execute("INSERT IGNORE INTO teacher_classes (teacher_id, class_id, semester_id) VALUES (?, ?, ?), (?, ?, ?)",
            [$t1Id, $class1, $semesterId, $t1Id, $class2, $semesterId]);
        echo "Linked teacher 1 (Budi Santoso).\n";
    }

    $t2User = Database::queryOne("SELECT id FROM users WHERE username = 'guru_siti'");
    if (!$t2User) {
        Database::execute("INSERT INTO users (name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)",
            ['Siti Rahmawati, M.Pd.', 'guru_siti', 'siti@classroomstar.local', $teacherPassHash, 'teacher']);
        $t2UserId = Database::lastInsertId();
    } else {
        $t2UserId = $t2User['id'];
    }
    $t2Exist = Database::queryOne("SELECT id FROM teachers WHERE user_id = ?", [$t2UserId]);
    if (!$t2Exist) {
        Database::execute("INSERT INTO teachers (user_id, nip) VALUES (?, ?)",
            [$t2UserId, '198802022011012002']);
        $t2Id = Database::lastInsertId();
        Database::execute("INSERT IGNORE INTO teacher_classes (teacher_id, class_id, semester_id) VALUES (?, ?, ?)",
            [$t2Id, $class3, $semesterId]);
        echo "Linked teacher 2 (Siti Rahmawati).\n";
    }

    // 7. Students
    $demoStudents = [
        ['Citra Lestari',  'siswa_citra',  '1001', 'citra@classroomstar.local',  $class1, 76],
        ['Andi Pratama',   'siswa_andi',   '1002', 'andi@classroomstar.local',   $class1, 68],
        ['Farhan Nugraha', 'siswa_farhan', '1003', 'farhan@classroomstar.local', $class1, 61],
        ['Dewi Anggraini', 'siswa_dewi',   '1004', 'dewi@classroomstar.local',   $class1, 45],
        ['Bima Sakti',     'siswa_bima',   '1005', 'bima@classroomstar.local',   $class1, 30],
        ['Alya Putri',     'siswa_alya',   '1006', 'alya@classroomstar.local',   $class2, 52],
        ['Rian Hidayat',   'siswa_rian',   '1007', 'rian@classroomstar.local',   $class2, 38],
        ['Dina Marlina',   'siswa_dina',   '1008', 'dina@classroomstar.local',   $class3, 55],
        ['Eko Prasetyo',   'siswa_eko',    '1009', 'eko@classroomstar.local',    $class3, 40],
    ];

    $teacher1 = Database::queryOne("SELECT id FROM teachers WHERE nip = '198501012010011001'");
    $teacher2 = Database::queryOne("SELECT id FROM teachers WHERE nip = '198802022011012002'");
    $t1Id = $teacher1 ? (int)$teacher1['id'] : 1;
    $t2Id = $teacher2 ? (int)$teacher2['id'] : 2;

    foreach ($demoStudents as [$name, $uname, $nis, $email, $cId, $targetStars]) {
        $exist = Database::queryOne("SELECT id FROM users WHERE username = ?", [$uname]);
        if (!$exist) {
            Database::execute("INSERT INTO users (name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)",
                [$name, $uname, $email, $studentPassHash, 'student']);
            $uId = Database::lastInsertId();
            Database::execute("INSERT INTO students (user_id, class_id, nis, name) VALUES (?, ?, ?, ?)",
                [$uId, $cId, $nis, $name]);
            $sId = Database::lastInsertId();

            // Insert star transactions to reach target stars
            $assignedTeacher = ($cId == $class3) ? $t2Id : $t1Id;
            $remaining = $targetStars;
            $dayOffset = 20;
            while ($remaining > 0) {
                $starsToGive = min($remaining, rand(2, 5));
                $remaining -= $starsToGive;
                $awardedAt = date('Y-m-d H:i:s', strtotime("-$dayOffset days -" . rand(1, 10) . " hours"));
                Database::execute(
                    "INSERT INTO star_transactions (student_id, teacher_id, class_id, semester_id, stars, type, awarded_at) VALUES (?, ?, ?, ?, ?, 'award', ?)",
                    [$sId, $assignedTeacher, $cId, $semesterId, $starsToGive, $awardedAt]
                );
                $dayOffset = max(0, $dayOffset - 2);
            }
            echo "Inserted student $name ($nis) with $targetStars stars.\n";
        }
    }

    echo "✅ Database seeded successfully!\n";
} catch (Throwable $e) {
    echo "❌ Error during seeding: " . $e->getMessage() . "\n";
}
