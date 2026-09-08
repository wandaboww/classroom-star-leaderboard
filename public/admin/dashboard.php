<?php
/**
 * Admin Dashboard
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';
$role      = 'admin';

// Ambil stats ringkas
$totalUsers    = Database::queryOne('SELECT COUNT(*) as n FROM users')['n'] ?? 0;
$totalStudents = Database::queryOne('SELECT COUNT(*) as n FROM students WHERE is_active = 1')['n'] ?? 0;
$totalClasses  = Database::queryOne('SELECT COUNT(*) as n FROM classes WHERE is_active = 1')['n'] ?? 0;

// Star transactions hari ini
$todayStars = Database::queryOne(
    'SELECT COALESCE(SUM(stars), 0) as n FROM star_transactions WHERE DATE(awarded_at) = CURDATE() AND type = "award"'
)['n'] ?? 0;

// Transaksi terbaru
$recentTx = Database::query(
    'SELECT st.*, s.name AS student_name, c.name AS class_name, u.name AS teacher_name
     FROM star_transactions st
     JOIN students s ON st.student_id = s.id
     JOIN classes  c ON st.class_id   = c.id
     JOIN teachers t ON st.teacher_id = t.id
     JOIN users    u ON t.user_id     = u.id
     ORDER BY st.awarded_at DESC LIMIT 10'
);

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Dashboard</h1>
        <p>Selamat datang kembali, <?= htmlspecialchars(Auth::user()['name']) ?> 👋</p>
    </div>
    <a href="/admin/leaderboard.php" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Lihat Leaderboard
    </a>
</div>

<!-- Stat Cards -->
<div class="grid grid-4 mb-8">
    <div class="card stat-card card-hover animate-fade-in" style="animation-delay:0s">
        <div class="stat-card-header">
            <span class="stat-card-label">Total Users</span>
            <div class="stat-card-icon stat-card-icon-gold">👥</div>
        </div>
        <div class="stat-card-value"><?= number_format($totalUsers) ?></div>
        <div class="stat-card-sub">Admin, Teacher, Student</div>
    </div>

    <div class="card stat-card card-hover animate-fade-in" style="animation-delay:0.05s">
        <div class="stat-card-header">
            <span class="stat-card-label">Total Siswa</span>
            <div class="stat-card-icon stat-card-icon-blue">🎓</div>
        </div>
        <div class="stat-card-value"><?= number_format($totalStudents) ?></div>
        <div class="stat-card-sub">Siswa aktif</div>
    </div>

    <div class="card stat-card card-hover animate-fade-in" style="animation-delay:0.1s">
        <div class="stat-card-header">
            <span class="stat-card-label">Total Kelas</span>
            <div class="stat-card-icon stat-card-icon-purple">🏫</div>
        </div>
        <div class="stat-card-value"><?= number_format($totalClasses) ?></div>
        <div class="stat-card-sub">Kelas aktif</div>
    </div>

    <div class="card stat-card card-hover animate-fade-in" style="animation-delay:0.15s">
        <div class="stat-card-header">
            <span class="stat-card-label">Bintang Hari Ini</span>
            <div class="stat-card-icon stat-card-icon-gold">⭐</div>
        </div>
        <div class="stat-card-value text-gold"><?= number_format($todayStars) ?></div>
        <div class="stat-card-sub">Bintang diberikan hari ini</div>
    </div>
</div>

<!-- Recent Star Transactions -->
<div class="card animate-fade-in" style="animation-delay:0.2s">
    <div class="flex-between mb-6">
        <h3 style="margin:0;">Transaksi Bintang Terbaru</h3>
        <a href="/admin/report.php" class="btn btn-ghost btn-sm">Lihat Semua →</a>
    </div>

    <?php if (empty($recentTx)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">⭐</div>
            <div class="empty-state-title">Belum ada transaksi</div>
            <div class="empty-state-desc">Bintang yang diberikan guru akan muncul di sini</div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Guru</th>
                        <th class="text-right">Bintang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTx as $tx): ?>
                    <tr>
                        <td class="text-muted text-xs"><?= date('d M Y H:i', strtotime($tx['awarded_at'])) ?></td>
                        <td>
                            <div class="student-cell">
                                <div class="avatar avatar-sm"><?= strtoupper(substr($tx['student_name'], 0, 1)) ?></div>
                                <span class="student-cell-name"><?= htmlspecialchars($tx['student_name']) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($tx['class_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($tx['teacher_name']) ?></td>
                        <td class="text-right">
                            <span class="star-count fw-bold text-gold">
                                ⭐ <?= $tx['stars'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
