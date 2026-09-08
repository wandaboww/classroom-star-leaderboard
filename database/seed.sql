-- ============================================================
-- CLASSROOM STAR — Seed Data
-- Run AFTER schema.sql
-- Default accounts:
--   Admin:   admin       / Admin@123
--   Teacher: guru_budi   / Guru@123
--   Teacher: guru_siti   / Guru@123
--   Student: siswa_citra / Siswa@123
-- ============================================================

SET NAMES utf8mb4;

-- 1. Default Admin User
INSERT INTO `users` (`name`, `username`, `email`, `password_hash`, `role`) VALUES
('Administrator', 'admin', 'admin@classroomstar.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 2. Default Level Configuration (baseline dari PRD)
INSERT INTO `levels` (`level_number`, `predicate`, `min_percent`, `max_percent`, `additional_score`) VALUES
(1, 'Very Low',    0.00,  19.99, 0),
(2, 'Low',        20.00,  39.99, 1),
(3, 'Fair',       40.00,  59.99, 2),
(4, 'Good',       60.00,  74.99, 3),
(5, 'Very Good',  75.00,  89.99, 4),
(6, 'Outstanding',90.00, 100.00, 5);

-- 3. Default System Settings
INSERT INTO `settings` (`key`, `value`, `label`) VALUES
('app_name',              'Classroom Star',     'Nama Aplikasi'),
('max_stars_per_award',   '5',                  'Maksimum Bintang per Pemberian'),
('min_stars_per_award',   '1',                  'Minimum Bintang per Pemberian'),
('max_star_opportunity',  '100',                'Maksimum Peluang Bintang per Semester'),
('leaderboard_public',    '1',                  'Leaderboard Publik (siswa bisa lihat sesama)'),
('semester_active_id',    '1',                  'ID Semester Aktif'),
('star_rules',            '⭐ Sistem Bintang & Level Kelas\n\n1. Aktivitas & Keaktifan: Siswa yang aktif menjawab atau bertanya mendapatkan 1-2 bintang.\n2. Tugas & Proyek: Penyelesaian tugas tepat waktu dengan hasil memuaskan mendapatkan 3-5 bintang.\n3. Disiplin & Sikap: Membantu teman, menjaga kebersihan, dan kerapihan kelas mendapatkan bintang apresiasi.', 'Panduan Cara Kerja Bintang');

-- 4. Demo Semester
INSERT INTO `semesters` (`id`, `name`, `academic_year`, `status`, `started_at`, `ended_at`) VALUES
(1, 'Semester Ganjil 2026/2027', '2026/2027', 'active', '2026-07-15', '2026-12-20');

-- 5. Demo Classes
INSERT INTO `classes` (`id`, `name`, `academic_year`) VALUES
(1, 'XI IPA 1', '2026/2027'),
(2, 'XI IPA 2', '2026/2027'),
(3, 'XI IPS 1', '2026/2027');

-- 6. Demo Teacher Users & Profiles
-- Password: Guru@123
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `role`) VALUES
(2, 'Budi Santoso, S.Pd.',   'guru_budi', 'budi@classroomstar.local', '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'teacher'),
(3, 'Siti Rahmawati, M.Pd.', 'guru_siti', 'siti@classroomstar.local', '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'teacher');

INSERT INTO `teachers` (`id`, `user_id`, `nip`) VALUES
(1, 2, '198501012010011001'),
(2, 3, '198802022011012002');

INSERT INTO `teacher_classes` (`teacher_id`, `class_id`, `semester_id`) VALUES
(1, 1, 1),
(1, 2, 1),
(2, 3, 1);

-- 7. Demo Student Users & Profiles
-- Password: Siswa@123
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `role`) VALUES
(4,  'Citra Lestari',  'siswa_citra',  'citra@classroomstar.local',  '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(5,  'Andi Pratama',   'siswa_andi',   'andi@classroomstar.local',   '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(6,  'Farhan Nugraha', 'siswa_farhan', 'farhan@classroomstar.local', '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(7,  'Dewi Anggraini', 'siswa_dewi',   'dewi@classroomstar.local',   '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(8,  'Bima Sakti',     'siswa_bima',   'bima@classroomstar.local',   '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(9,  'Alya Putri',     'siswa_alya',   'alya@classroomstar.local',   '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(10, 'Rian Hidayat',   'siswa_rian',   'rian@classroomstar.local',   '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(11, 'Dina Marlina',   'siswa_dina',   'dina@classroomstar.local',   '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student'),
(12, 'Eko Prasetyo',   'siswa_eko',    'eko@classroomstar.local',    '$2y$12$ZqH858yCjZ0qZtHkM37dDuw3a0Gv3hYy6lA9K8x5k1u4a3c2b1d0e', 'student');

INSERT INTO `students` (`id`, `user_id`, `class_id`, `nis`, `name`) VALUES
(1, 4,  1, '1001', 'Citra Lestari'),
(2, 5,  1, '1002', 'Andi Pratama'),
(3, 6,  1, '1003', 'Farhan Nugraha'),
(4, 7,  1, '1004', 'Dewi Anggraini'),
(5, 8,  1, '1005', 'Bima Sakti'),
(6, 9,  2, '1006', 'Alya Putri'),
(7, 10, 2, '1007', 'Rian Hidayat'),
(8, 11, 3, '1008', 'Dina Marlina'),
(9, 12, 3, '1009', 'Eko Prasetyo');
