-- ============================================================
-- CLASSROOM STAR — Database Schema
-- Version: 1.0
-- Engine: MySQL 5.7+ / MariaDB 10.4+
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USERS
-- Semua akun (admin, teacher, student) disimpan di sini
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(150) NOT NULL,
  `username`      VARCHAR(50)  NOT NULL UNIQUE,
  `email`         VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- SEMESTERS / PERIODS
-- Harus dibuat sebelum transaksi bintang dimasukkan
-- ------------------------------------------------------------
CREATE TABLE `semesters` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(100) NOT NULL,        -- e.g. "Semester Ganjil 2026/2027"
  `academic_year` VARCHAR(20)  NOT NULL,        -- e.g. "2026/2027"
  `status`        ENUM('draft', 'active', 'closed', 'archived') NOT NULL DEFAULT 'draft',
  `started_at`    DATE         NULL,
  `ended_at`      DATE         NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- CLASSES
-- ------------------------------------------------------------
CREATE TABLE `classes` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(50)  NOT NULL,        -- e.g. "XI IPA 1"
  `academic_year` VARCHAR(20)  NOT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TEACHERS
-- Profil guru (extends users)
-- ------------------------------------------------------------
CREATE TABLE `teachers` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL UNIQUE,
  `nip`        VARCHAR(30)  NULL UNIQUE,        -- Nomor Induk Pegawai
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_teacher_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TEACHER — CLASS ASSIGNMENTS
-- Guru dapat mengajar banyak kelas, kelas dapat diajar banyak guru
-- ------------------------------------------------------------
CREATE TABLE `teacher_classes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `teacher_id`  INT UNSIGNED NOT NULL,
  `class_id`    INT UNSIGNED NOT NULL,
  `semester_id` INT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_teacher_class_semester` (`teacher_id`, `class_id`, `semester_id`),
  CONSTRAINT `fk_tc_teacher`   FOREIGN KEY (`teacher_id`)  REFERENCES `teachers`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tc_class`     FOREIGN KEY (`class_id`)    REFERENCES `classes`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tc_semester`  FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- STUDENTS
-- Profil siswa (extends users, NIS sebagai ID sekolah)
-- ------------------------------------------------------------
CREATE TABLE `students` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NULL UNIQUE,        -- NULL jika belum punya akun login
  `nis`        VARCHAR(30)  NOT NULL UNIQUE,    -- Nomor Induk Siswa
  `name`       VARCHAR(150) NOT NULL,
  `class_id`   INT UNSIGNED NOT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_student_user`  FOREIGN KEY (`user_id`)  REFERENCES `users`   (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_student_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- STAR TRANSACTIONS (AUDIT TRAIL — jangan hapus, gunakan reversal)
-- ------------------------------------------------------------
CREATE TABLE `star_transactions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_id`  INT UNSIGNED NOT NULL,
  `class_id`    INT UNSIGNED NOT NULL,
  `teacher_id`  INT UNSIGNED NOT NULL,          -- Teacher yang memberi bintang
  `semester_id` INT UNSIGNED NOT NULL,
  `stars`       TINYINT UNSIGNED NOT NULL,      -- 1–5 (bisa negatif untuk reversal)
  `type`        ENUM('award', 'reversal') NOT NULL DEFAULT 'award',
  `ref_id`      INT UNSIGNED NULL,              -- FK ke transaksi asal (untuk reversal)
  `note`        VARCHAR(255) NULL,
  `awarded_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_st_student`  FOREIGN KEY (`student_id`)  REFERENCES `students`  (`id`),
  CONSTRAINT `fk_st_class`    FOREIGN KEY (`class_id`)    REFERENCES `classes`   (`id`),
  CONSTRAINT `fk_st_teacher`  FOREIGN KEY (`teacher_id`)  REFERENCES `teachers`  (`id`),
  CONSTRAINT `fk_st_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- LEVEL CONFIGURATION (dapat dikonfigurasi oleh Admin)
-- ------------------------------------------------------------
CREATE TABLE `levels` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `level_number`     TINYINT UNSIGNED NOT NULL UNIQUE,
  `predicate`        VARCHAR(50) NOT NULL,      -- e.g. "Outstanding"
  `min_percent`      DECIMAL(5,2) NOT NULL,     -- e.g. 90.00
  `max_percent`      DECIMAL(5,2) NOT NULL,     -- e.g. 100.00
  `additional_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- SYSTEM SETTINGS
-- Key-value store untuk konfigurasi app
-- ------------------------------------------------------------
CREATE TABLE `settings` (
  `key`        VARCHAR(100) NOT NULL PRIMARY KEY,
  `value`      TEXT NOT NULL,
  `label`      VARCHAR(150) NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
