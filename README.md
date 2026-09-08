# Classroom Star — Sistem Gamifikasi & Leaderboard Bintang Kelas

Platform web gamifikasi edukasi modern berbasis poin dan bintang (*stars*) yang dirancang untuk mendorong keaktifan, kedisiplinan, dan motivasi belajar siswa di kelas. Aplikasi ini dilengkapi sistem penilaian berjenjang (Level 1–6), audit trail transaksi bintang, leaderboard interaktif dengan podium 3 besar, panduan dinamis "Cara Kerja Bintang", sistem pembuatan akun siswa massal otomatis, modal pengumuman login ramah perangkat seluler, serta modul impor/ekspor data berbasis spreadsheet OpenXML (`.xlsx`).

---

## Daftar Isi

- [Classroom Star — Sistem Gamifikasi \& Leaderboard Bintang Kelas](#classroom-star--sistem-gamifikasi--leaderboard-bintang-kelas)
  - [Daftar Isi](#daftar-isi)
  - [Teknologi yang Digunakan](#teknologi-yang-digunakan)
  - [Sistem Autentikasi \& Akun Pengguna](#sistem-autentikasi--akun-pengguna)
    - [1. Konsep Keamanan \& Proteksi Sesi](#1-konsep-keamanan--proteksi-sesi)
    - [2. Kredensial Akun Login (Admin, Guru, Siswa)](#2-kredensial-akun-login-admin-guru-siswa)
    - [3. Role-Based Access Control (RBAC)](#3-role-based-access-control-rbac)
    - [4. Alur Autentikasi \& Pengalihan Halaman](#4-alur-autentikasi--pengalihan-halaman)
  - [Fitur Utama Sistem](#fitur-utama-sistem)
    - [1. Pemberian Bintang (Give Stars)](#1-pemberian-bintang-give-stars)
    - [2. Leaderboard \& Podium Interaktif](#2-leaderboard--podium-interaktif)
    - [3. Sistem Level \& Predikat Gamifikasi](#3-sistem-level--predikat-gamifikasi)
    - [4. Kelola Panduan Dinamis "Cara Kerja Bintang"](#4-kelola-panduan-dinamis-cara-kerja-bintang)
    - [5. Fitur Modal Panduan Login Murid (Mobile-First)](#5-fitur-modal-panduan-login-murid-mobile-first)
    - [6. Impor \& Ekspor Siswa (.xlsx OpenXML)](#6-impor--ekspor-siswa-xlsx-openxml)
    - [7. Manajemen Master Data \& Generator Akun Siswa](#7-manajemen-master-data--generator-akun-siswa)
  - [Struktur Direktori Proyek](#struktur-direktori-proyek)
  - [Panduan Instalasi \& Menjalankan di Lokal](#panduan-instalasi--menjalankan-di-lokal)
    - [Prasyarat](#prasyarat)
    - [Langkah Instalasi](#langkah-instalasi)
  - [Panduan Deployment ke Shared Hosting (cPanel / Hostinger)](#panduan-deployment-ke-shared-hosting-cpanel--hostinger)
  - [Daftar Endpoint API](#daftar-endpoint-api)

---

## Teknologi yang Digunakan

| Komponen | Teknologi | Keterangan |
| --- | --- | --- |
| **Backend** | PHP 8.1+ (Native OOP) | Arsitektur modular MVC ringan tanpa dependensi framework berat |
| **Database** | MySQL 5.7+ / MariaDB 10.4+ | InnoDB engine, foreign keys, prepared statements PDO terproteksi |
| **Frontend** | Vanilla HTML5, CSS3, ES6+ JS | Desain dark-mode modern, glassmorphism, responsive mobile-first |
| **Spreadsheet Engine** | PhpOffice/PhpSpreadsheet 2.x | Pustaka pemrosesan file OpenXML `.xlsx` di sisi server |
| **Client Sheet Parser** | SheetJS (xlsx.full.min.js) | Validasi dan pratinjau data spreadsheet langsung di peramban |
| **Ikon & Tipografi** | Google Fonts & Inline SVG | Tipografi Inter font dengan rendering SVG modern dan ringan |
| **Manajemen Paket** | Composer | Untuk manajemen library pihak ketiga (`phpoffice/phpspreadsheet`) |

---

## Sistem Autentikasi & Akun Pengguna

Modul autentikasi dikelola secara terpusat oleh kelas `Auth` pada [`app/core/Auth.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/app/core/Auth.php).

### 1. Konsep Keamanan & Proteksi Sesi

- **Sesi Aman (Hardened Sessions)**:
  - Cookie sesi dikonfigurasi dengan atribut `HttpOnly` (mencegah akses skrip jahat melalui serangan XSS).
  - Parameter `SameSite=Strict` untuk memitigasi serangan Cross-Site Request Forgery (CSRF).
  - Atribut `Secure` otomatis aktif saat aplikasi berjalan melalui protokol HTTPS.
  - Sesi diregenerasi menggunakan `session_regenerate_id(true)` saat proses login berhasil untuk mencegah serangan *session fixation*.
- **Enkripsi Kata Sandi (Password Hashing)**:
  - Seluruh password dienkripsi menggunakan algoritma standar industri `PASSWORD_BCRYPT` dengan *cost factor* 12 melalui fungsi `password_hash()` dan diverifikasi dengan `password_verify()`.
- **Proteksi CSRF (Cross-Site Request Forgery)**:
  - Token kriptografi acak berukuran 32-byte (`bin2hex(random_bytes(32))`) dibuat per sesi pengguna.
  - Setiap request POST dan mutasi data diverifikasi menggunakan pembandingan waktu-konstan `hash_equals()`.
- **Pencegahan SQL Injection**:
  - Semua kueri database menggunakan PDO wrapper (`Database::query`, `Database::queryOne`, `Database::execute`) dengan *prepared statements* dan *parameter binding*.
- **Pembersihan Masukan & Keluaran (XSS Defense)**:
  - Seluruh teks input pengguna dibersihkan dan di-escape menggunakan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` sebelum dirender ke HTML.

---

### 2. Kredensial Akun Login (Admin, Guru, Siswa)

Sistem mendukung login multi-role menggunakan **Username**, **Nomor Induk Siswa (NIS)**, atau **Email**.

| Role | Username / Login ID | Password Default | Hak Akses & Keterangan |
| --- | --- | --- | --- |
| **Admin** | `admin` | `Admin@123` | Akses penuh ke seluruh menu: manajemen user, guru, siswa, kelas, semester, konfigurasi level, generator akun siswa, dan pelaporan global. |
| **Guru (Teacher)** | `Wandabow` *(atau `guru_budi`, `guru_siti`)* | *Password Guru* *(Demo: `Admin@123` / `Guru@123`)* | Akses menu pemberian bintang (`give-stars.php`), leaderboard kelas yang diampu, pelaporan bintang, dan editor panduan *Cara Kerja Bintang*. |
| **Siswa / Murid (Student)** | **`[NIS Siswa]`** *(contoh: `2526100149`)* | **`pplg123`** *(huruf kecil)* | Akses dashboard siswa, leaderboard kelas dengan tombol *"Rank Saya"*, panduan *Cara Kerja Bintang*, dan riwayat rekap bintang pribadi. |

> [!TIP]
> **Pembuatan Akun Siswa Otomatis**:
>
> 1. **Saat Impor Excel**: Setiap kali file `.xlsx` data siswa diimpor melalui menu Admin Siswa, akun login siswa baru otomatis dibuat di database dengan username berupa **NIS** dan password default **`pplg123`**.
> 2. **Generator Massal**: Tombol **`⚡ Buat Akun Siswa`** tersedia pada panel [`public/admin/students.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/public/admin/students.php) untuk meng-generate akun secara instan bagi seluruh siswa yang belum memiliki login.

---

### 3. Role-Based Access Control (RBAC)

Aplikasi membagi otorisasi ke dalam 3 peran (*role*):

1. **`admin`**:
   - Mengelola master pengguna (tambah, edit, aktifkan/nonaktifkan akun).
   - Mengelola master guru, siswa, kelas, dan penugasan guru-kelas.
   - Mengatur semester aktif dan status periode.
   - Mengonfigurasi batas nilai level dan persentase perolehan bintang.
   - Mengimpor dan mengekspor data siswa format `.xlsx` murni.
   - Membuat akun login massal untuk seluruh siswa.
2. **`teacher`**:
   - Memberikan bintang kepada siswa di kelas yang diajar secara interaktif.
   - Memberi bintang secara individu maupun banyak siswa sekaligus (*multi-select*).
   - Membatalkan pemberian bintang (*undo award*) dalam batas waktu yang ditentukan.
   - Memantau laporan dan grafik progres bintang siswa.
   - Mengelola teks panduan dinamis **Cara Kerja Bintang** dan melihat pratinjaunya.
3. **`student`**:
   - Melihat total perolehan bintang pribadi pada semester aktif.
   - Mengetahui status predikat level gamifikasi (Level 1–6) dan persentase capaian.
   - Melihat leaderboard kelas dengan fitur loncat cepat *"Rank Saya"*.
   - Membaca panduan resmi *Cara Kerja Bintang* yang ditetapkan oleh guru.

Setiap controller dan halaman web dilindungi middleware terstandar:

```php
// Proteksi halaman khusus Admin
Auth::requireRole(['admin']);

// Proteksi halaman yang dapat diakses Admin dan Guru
Auth::requireRole(['admin', 'teacher']);

// Proteksi khusus Siswa
Auth::requireRole(['student']);
```

---

### 4. Alur Autentikasi & Pengalihan Halaman

```text
Pengguna Mengakses Halaman
         │
         ▼
Sudah Login? (Auth::check())
    ├── Tidak ──► Redirect ke /login.php
    └── Ya
         │
         ▼
Peran Sesuai Halaman? (Auth::hasRole($roles))
    ├── Ya  ──► Render Halaman yang Dituju
    └── Tidak ──► Redirect Otomatis ke Dashboard Peran Masing-Masing:
                   ├── 'admin'   ──► /admin/dashboard.php
                   ├── 'teacher' ──► /teacher/dashboard.php
                   └── 'student' ──► /student/dashboard.php
```

---

## Fitur Utama Sistem

### 1. Pemberian Bintang (Give Stars)

- Antarmuka khusus guru dengan wizard 4 langkah intuitif:
  1. **Pilih Kelas**: Menampilkan daftar kelas yang ditugaskan kepada guru tersebut.
  2. **Pilih Jumlah Bintang**: Pilihan cepat `+1` hingga `+5` bintang dengan tombol sentuh ramah perangkat seluler.
  3. **Pilih Siswa**: Mode pemilihan siswa tunggal (*single*) atau kelompok (*multi-select*).
  4. **Konfirmasi & Selebrasi**: Ringkasan aksi pemberian dengan animasi selebrasi visual instan.
- Pencatatan transaksi bersifat audit trail (*immutable*) pada tabel `star_transactions`.

### 2. Leaderboard & Podium Interaktif

- **Susunan Podium Modern**:
  - **Rank 1 (Kiri / Gold)**: Menggunakan lencana emas bersinar dengan watermark medali emas di latar belakang.
  - **Rank 2 (Tengah / Silver)**: Menggunakan lencana perak elegan.
  - **Rank 3 (Kanan / Bronze)**: Menggunakan lencana perunggu.
- **Tabel Peringkat Lengkap**:
  - Badge nomor peringkat menggunakan styling *lighting background* dan *dark text* untuk kontras tinggi dan keterbacaan optimal.
  - Filter instan berdasarkan Kelas dan Semester dengan pembaruan otomatis (*live reload*).
- **Tombol "Rank Saya" (Khusus Akun Siswa)**:
  - Tombol cepat di header [`public/student/leaderboard.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/public/student/leaderboard.php) yang langsung menggulirkan layar (*smooth scroll*) ke baris peringkat siswa yang sedang login dan memberikan efek animasi *golden pulse highlight*.

### 3. Sistem Level & Predikat Gamifikasi

Konfigurasi level dinamis tersimpan pada tabel `levels`:

| Level | Predikat | Rentang Persentase Bintang | Tambahan Poin |
| :---: | --- | :---: | :---: |
| **Level 1** | Very Low | 0.00% – 19.99% | +0 |
| **Level 2** | Low | 20.00% – 39.99% | +1 |
| **Level 3** | Fair | 40.00% – 59.99% | +2 |
| **Level 4** | Good | 60.00% – 74.99% | +3 |
| **Level 5** | Very Good | 75.00% – 89.99% | +4 |
| **Level 6** | Outstanding | 90.00% – 100.00% | +5 |

### 4. Kelola Panduan Dinamis "Cara Kerja Bintang"

- **Editor Guru ([`public/teacher/rules.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/public/teacher/rules.php))**:
  - Form editor teks terfokus dengan toolbar penyisip template cepat (*+ Keaktifan, + Tugas Tepat Waktu, + Sikap & Disiplin, + Predikat Level*).
  - Teks disimpan secara dinamis ke tabel `settings` (`key = 'star_rules'`).
- **Pratinjau Siswa Guru ([`public/teacher/rules-preview.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/public/teacher/rules-preview.php))**:
  - Submenu khusus di bawah menu *Panduan* untuk melihat simulasi tampilan 1:1 seperti yang dilihat oleh murid.
- **Halaman Tampilan Siswa ([`public/student/rules.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/public/student/rules.php))**:
  - Kartu panduan berdesain glassmorphic dengan badge tanggal pembaruan yang rapi di pojok kanan kartu.

### 5. Fitur Modal Panduan Login Murid (Mobile-First)

- Pada halaman login ([`public/login.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/public/login.php)), terdapat tombol banner:
  **`📢 Cara Login Murid / Siswa`** *(Petunjuk NIS & password default)*.
- Membuka modal interaktif yang ramah smartphone dengan:
  - **Header Diam (Fixed/Sticky Header)**: Judul modal dan tombol tutup (✕) selalu diam di atas.
  - **Body Scrollable**: Konten panduan 3 langkah dapat digulirkan secara vertikal dengan lancar.
  - **Footer Tetap**: Tombol *Mengerti & Tutup*, tombol salin password `pplg123`, dan tombol coba akun siswa demo.

### 6. Impor & Ekspor Siswa (.xlsx OpenXML)

- **Ekspor Data Siswa**: Menghasilkan dokumen OpenXML murni (`.xlsx`) lengkap dengan NIS, Nama Siswa, Kelas, dan Total Bintang menggunakan header standar RFC 6266 (`filename*="UTF-8''..."`).
- **Download Template**: Menyediakan template resmi `.xlsx` siap isi dengan kolom NIS, Nama, dan Nama Kelas.
- **Validasi Impor**: Menerima file upload `.xlsx`, memvalidasi batas ukuran (maksimal 10MB), serta mengekstrak baris data secara aman via `PhpOffice\PhpSpreadsheet\Reader\Xlsx`.

### 7. Manajemen Master Data & Generator Akun Siswa

- Pengelolaan lengkap Data Pengguna (Users), Data Guru (Teachers), Data Siswa (Students), Data Kelas (Classes), dan Semester.
- Pemetaan relasi pengajaran guru terhadap kelas dan semester terkait.
- Pembuatan akun login otomatis untuk seluruh siswa terdaftar.

---

## Struktur Direktori Proyek

```text
Classroom Star Leaderboard/
│
├── app/                              ← Logika inti aplikasi (terproteksi, non-public)
│   ├── config/
│   │   ├── app.php                   ← Konfigurasi aplikasi, sesi, timezone
│   │   └── database.php              ← Konfigurasi koneksi MySQL PDO
│   ├── core/
│   │   ├── Auth.php                  ← Manajemen session, RBAC, dan CSRF token
│   │   ├── Database.php              ← PDO Database wrapper & query helper
│   │   └── Response.php              ← Helper response JSON & HTTP status
│   ├── helpers/
│   │   ├── ValidationHelper.php      ← Sanitasi & validasi data input
│   │   └── XlsxHelper.php            ← Pustaka export/import/template .xlsx
│   └── models/
│       ├── ClassModel.php            ← Model master kelas
│       ├── LevelModel.php            ← Model konfigurasi level predikat
│       ├── SemesterModel.php         ← Model master semester/periode
│       ├── StarTransactionModel.php  ← Model pencatatan mutasi bintang
│       ├── StudentModel.php          ← Model data siswa & perolehan bintang
│       ├── TeacherModel.php          ← Model data guru & penugasan kelas
│       └── UserModel.php             ← Model akun & kredensial pengguna
│
├── database/
│   ├── schema.sql                    ← Definisi DDL tabel MySQL & foreign keys
│   └── seed.sql                      ← Data inisialisasi akun demo & level baseline
│
├── public/                           ← Document root web server (terbuka untuk publik)
│   ├── index.php                     ← Router pengarah awal (login / dashboard)
│   ├── login.php                     ← Halaman antarmuka login & modal panduan murid
│   ├── logout.php                    ← Script terminasi sesi pengguna
│   ├── download_template.php         ← Endpoint unduh template impor siswa .xlsx
│   ├── admin/                        ← Halaman khusus role Admin
│   │   ├── classes.php
│   │   ├── dashboard.php
│   │   ├── leaderboard.php
│   │   ├── levels.php
│   │   ├── report.php
│   │   ├── semesters.php
│   │   ├── settings.php
│   │   ├── students.php              ← Manajemen siswa, generator akun, import/export .xlsx
│   │   ├── teachers.php
│   │   └── users.php
│   ├── teacher/                      ← Halaman khusus role Guru
│   │   ├── dashboard.php
│   │   ├── give-stars.php            ← Fitur utama pemberian bintang kelas
│   │   ├── leaderboard.php
│   │   ├── progress.php
│   │   ├── report.php
│   │   ├── rules.php                 ← Kelola panduan dinamis "Cara kerja bintang"
│   │   ├── rules-preview.php         ← Submenu pratinjau tampilan panduan siswa
│   │   └── students.php
│   ├── student/                      ← Halaman khusus role Siswa
│   │   ├── dashboard.php
│   │   ├── leaderboard.php           ← Leaderboard siswa dengan tombol "Rank Saya"
│   │   ├── report.php
│   │   └── rules.php                 ← Halaman panduan "Cara kerja bintang" tampilan murid
│   ├── api/                          ← RESTful AJAX JSON Endpoints
│   │   ├── auth.php
│   │   ├── classes.php
│   │   ├── import.php                ← Endpoint upload XLSX & auto-create akun login
│   │   ├── leaderboard.php
│   │   ├── semesters.php
│   │   ├── stars.php                 ← Endpoint transaksi bintang
│   │   ├── students.php              ← Endpoint data siswa & generator akun
│   │   ├── teachers.php
│   │   └── users.php
│   └── assets/                       ← Aset statis frontend
│       ├── css/                      ← File styling modular vanilla CSS
│       ├── js/                       ← Script perilaku frontend interaktif
│       └── img/                      ← Ikon, gambar ilustrasi, logo
│
├── vendor/                           ← Dependensi library Composer
├── composer.json                     ← Definisi dependensi PHP
├── CLASSROOM_STAR_PROJECT_BASELINE.md
└── README.md
```

---

## Panduan Instalasi & Menjalankan di Lokal

### Prasyarat

- **PHP 8.1 atau lebih tinggi** (dengan ekstensi `pdo_mysql`, `mbstring`, `zip`, `xml`, `gd` aktif)
- **MySQL 5.7+** atau **MariaDB 10.4+** (misal via Laragon, XAMPP, atau MySQL Server standalone)
- **Composer** (untuk mengelola pustaka `phpoffice/phpspreadsheet`)

### Langkah Instalasi

1. **Clone atau Buka Direktori Proyek**:

   ```bash
   cd "d:/Website/Classroom Star Leaderboard"
   ```

2. **Pasang Dependensi Composer**:

   ```bash
   composer install
   ```

3. **Konfigurasi Database**:
   - Buka file [`app/config/database.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/app/config/database.php) dan sesuaikan kredensial MySQL lokal Anda:

   ```php
   return [
       'host'     => '127.0.0.1',
       'port'     => 3306,
       'database' => 'classroom_star',
       'username' => 'root',
       'password' => '',
       'charset'  => 'utf8mb4',
   ];
   ```

4. **Impor Struktur & Data Database**:
   - Buat database baru bernama `classroom_star`.
   - Jalankan file SQL secara berurutan pada database tersebut:
     1. [`database/schema.sql`](file:///d:/Website/Classroom%20Star%20Leaderboard/database/schema.sql) (Struktur tabel)
     2. [`database/seed.sql`](file:///d:/Website/Classroom%20Star%20Leaderboard/database/seed.sql) (Data default akun & master)

5. **Jalankan Web Server Lokal**:
   - Menggunakan PHP built-in server dengan target folder `public/`:

   ```bash
   php -S localhost:8080 -t public/
   ```

6. **Akses Aplikasi**:
   - Buka peramban (browser) dan akses alamat `http://localhost:8080/login.php`.
   - Masuk menggunakan salah satu kredensial akun bawaan di tabel atas.

---

## Panduan Deployment ke Shared Hosting (cPanel / Hostinger)

Aplikasi Classroom Star telah dirancang siap pakai (*production-ready*) untuk lingkungan Hostinger maupun cPanel Shared Hosting.

### Opsi A: Rekomendasi Keamanan Tinggi (Pemisahan Direktori)

1. **Struktur Penempatan File**:
   - Unggah seluruh isi folder `public/` langsung ke dalam folder `public_html/`.
   - Tempatkan folder `app/`, `database/`, `vendor/`, dan `composer.json` **di luar folder `public_html/`** (sejajar dengan `public_html/`) agar kode sumber backend tidak dapat diakses langsung oleh pengunjung peramban.
2. **Path Autoload**:
   - Struktur bawaan aplikasi sudah menggunakan `dirname(__DIR__) . '/app/bootstrap.php'` yang langsung cocok dengan pemisahan ini.

### Opsi B: Penempatan Root Direktori Bersama (Single Folder)

Jika seluruh proyek diunggah langsung ke dalam `public_html/`:

1. File [`.htaccess`](file:///d:/Website/Classroom%20Star%20Leaderboard/.htaccess) di root direktori akan otomatis aktif.
2. Aturan rewrite akan mengarahkan semua pengunjung ke folder `/public/` secara transparan.
3. Akses langsung peramban ke folder `app/`, `database/`, `vendor/`, serta file `.env`, `.sql`, `.json`, `.lock` secara otomatis diblokir (*403 Forbidden*).

---

### Checklist Deployment Produksi

- [x] **Versi PHP**: Atur PHP Selector di cPanel ke **PHP 8.1** atau **PHP 8.2**.
- [x] **Ekstensi PHP**: Pastikan ekstensi `pdo_mysql`, `mbstring`, `zip`, `xml`, `gd`, `fileinfo` aktif.
- [x] **Basis Data**:
  1. Buat database MySQL baru dan user database di menu *cPanel MySQL Databases*.
  2. Berikan hak akses penuh (*All Privileges*) user ke database tersebut.
  3. Buka *phpMyAdmin*, pilih database baru, lalu impor file [`database/schema.sql`](file:///d:/Website/Classroom%20Star%20Leaderboard/database/schema.sql) kemudian [`database/seed.sql`](file:///d:/Website/Classroom%20Star%20Leaderboard/database/seed.sql).
- [x] **Konfigurasi Kredensial**:
  - Buka [`app/config/database.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/app/config/database.php) pada hosting dan masukkan nama DB, user, serta password yang telah dibuat.
  - Buka [`app/config/app.php`](file:///d:/Website/Classroom%20Star%20Leaderboard/app/config/app.php), ubah `'env' => 'production'` untuk menonaktifkan pesan error mentah ke pengguna umum.
- [x] **Hak Akses Folder**:
  - Berikan izin direktori `755` untuk folder dan `644` untuk berkas file PHP.

---

## Daftar Endpoint API

Endpoint REST internal mengembalikan format response JSON (`Content-Type: application/json`):

| Endpoint | Method | Role Akses | Fungsi |
| --- | :---: | --- | --- |
| `/api/auth.php?action=login` | POST | Publik | Proses verifikasi login dan pembuatan sesi |
| `/api/auth.php?action=logout` | POST | Autentikasi | Mengakhiri sesi pengguna aktif |
| `/api/stars.php` | POST | Guru, Admin | Memberikan bintang kepada siswa / sekelompok siswa |
| `/api/stars.php?action=undo` | POST | Guru, Admin | Membatalkan transaksi bintang sebelumnya |
| `/api/leaderboard.php` | GET | Siswa, Guru, Admin | Mengambil data peringkat per kelas / global |
| `/api/students.php` | GET | Guru, Admin | Mengambil data siswa beserta total bintangnya |
| `/api/students.php?action=generate_accounts` | POST | Admin | Meng-generate akun login massal untuk seluruh siswa |
| `/api/teachers.php` | GET | Admin | Mengambil daftar akun guru dan penugasan kelas |
| `/api/classes.php` | GET | Guru, Admin | Mengambil daftar kelas aktif |
| `/api/semesters.php` | GET | Guru, Admin | Mengambil daftar semester dan statusnya |
| `/api/import.php` | POST | Admin | Memproses unggahan berkas spreadsheet siswa `.xlsx` & auto-create login |
| `/download_template.php` | GET | Admin | Mengunduh berkas template impor siswa `.xlsx` |
| `/admin/students.php?export=1` | GET | Admin | Mengunduh ekspor berkas data siswa format `.xlsx` |
