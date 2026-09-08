<?php
/**
 * Teacher Dashboard
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['teacher']);

$pageTitle = 'Teacher Dashboard';
$activeNav = 'dashboard';
$role      = 'teacher';
$user      = Auth::user();

// Dapatkan teacher_id
$teacher = Database::queryOne('SELECT id FROM teachers WHERE user_id = ?', [$user['id']]);
$teacherId = $teacher['id'] ?? null;

$myClasses   = 0;
$myStudents  = 0;
$todayStars  = 0;
$myTotalStars = 0;
$recentTx    = [];

if ($teacherId) {
    // Kelas yang diajar
    $myClasses = Database::queryOne(
        'SELECT COUNT(DISTINCT class_id) as n FROM teacher_classes WHERE teacher_id = ?',
        [$teacherId]
    )['n'] ?? 0;

    // Total siswa di kelas saya
    $myStudents = Database::queryOne(
        'SELECT COUNT(DISTINCT s.id) as n
         FROM students s
         JOIN teacher_classes tc ON s.class_id = tc.class_id
         WHERE tc.teacher_id = ?',
        [$teacherId]
    )['n'] ?? 0;

    // Bintang yang saya beri hari ini
    $todayStars = Database::queryOne(
        'SELECT COALESCE(SUM(stars), 0) as n FROM star_transactions WHERE teacher_id = ? AND DATE(awarded_at) = CURDATE() AND type = "award"',
        [$teacherId]
    )['n'] ?? 0;

    // Total bintang yang pernah saya beri
    $myTotalStars = Database::queryOne(
        'SELECT COALESCE(SUM(stars), 0) as n FROM star_transactions WHERE teacher_id = ? AND type = "award"',
        [$teacherId]
    )['n'] ?? 0;

    // Transaksi terbaru saya
    $recentTx = Database::query(
        'SELECT st.*, s.name AS student_name, c.name AS class_name
         FROM star_transactions st
         JOIN students s ON st.student_id = s.id
         JOIN classes  c ON st.class_id   = c.id
         WHERE st.teacher_id = ?
         ORDER BY st.awarded_at DESC LIMIT 8',
        [$teacherId]
    );
}

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Dashboard</h1>
        <p>Halo, <?= htmlspecialchars($user['name']) ?>! Siap memberi bintang hari ini? ⭐</p>
    </div>
    <a href="/teacher/give-stars.php" class="btn btn-primary btn-lg btn-give-stars-header" id="btn-give-stars">
        <span class="btn-star-icon">⭐</span>
        <span class="btn-text">Give Stars</span>
    </a>
</div>

<!-- Stat Cards -->
<div class="grid grid-4 mb-8">
    <div class="card stat-card card-hover animate-fade-in">
        <div class="stat-card-header">
            <span class="stat-card-label">Kelas Saya</span>
            <div class="stat-card-icon stat-card-icon-purple">🏫</div>
        </div>
        <div class="stat-card-value"><?= $myClasses ?></div>
        <div class="stat-card-sub">Kelas yang diajar</div>
    </div>

    <div class="card stat-card card-hover animate-fade-in" style="animation-delay:0.05s">
        <div class="stat-card-header">
            <span class="stat-card-label">Total Siswa</span>
            <div class="stat-card-icon stat-card-icon-blue">🎓</div>
        </div>
        <div class="stat-card-value"><?= $myStudents ?></div>
        <div class="stat-card-sub">Dari semua kelas</div>
    </div>

    <div class="card stat-card card-hover animate-fade-in" style="animation-delay:0.1s">
        <div class="stat-card-header">
            <span class="stat-card-label">Bintang Hari Ini</span>
            <div class="stat-card-icon stat-card-icon-gold">⭐</div>
        </div>
        <div class="stat-card-value text-gold"><?= $todayStars ?></div>
        <div class="stat-card-sub">Bintang yang kamu beri hari ini</div>
    </div>

    <div class="card stat-card card-hover animate-fade-in" style="animation-delay:0.15s">
        <div class="stat-card-header">
            <span class="stat-card-label">Total Bintang</span>
            <div class="stat-card-icon stat-card-icon-gold">🌟</div>
        </div>
        <div class="stat-card-value text-gold"><?= number_format($myTotalStars) ?></div>
        <div class="stat-card-sub">Sepanjang waktu</div>
    </div>
</div>

<!-- CTA: Give Stars -->
<div class="card card-gold card-cta-stars mb-6 animate-fade-in" style="animation-delay:0.2s;">
    <div class="cta-stars-inner">
        <div class="cta-stars-content">
            <div class="cta-stars-badge">⚡ Fast Action</div>
            <h3 class="cta-stars-title">⭐ Award Stars Now</h3>
            <p class="cta-stars-desc">Pilih kelas → pilih bintang → pilih siswa → beri reward partisipasi!</p>
        </div>
        <a href="/teacher/give-stars.php" class="btn btn-primary btn-lg btn-start-stars" id="btn-start-stars">
            <span class="btn-star-icon">🌟</span>
            <span>Mulai Beri Bintang</span>
            <span class="btn-arrow">→</span>
        </a>
    </div>
</div>

<!-- Recent -->
<div class="card animate-fade-in" style="animation-delay:0.25s">
    <div class="flex-between mb-6">
        <h3 style="margin:0;">Riwayat Pemberian Terbaru</h3>
        <a href="/teacher/report.php" class="btn btn-ghost btn-sm">Lihat Semua →</a>
    </div>

    <?php if (empty($recentTx)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">⭐</div>
            <div class="empty-state-title">Belum ada riwayat</div>
            <div class="empty-state-desc">Bintang yang kamu berikan akan muncul di sini</div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Siswa</th>
                        <th>Kelas</th>
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
                        <td class="text-right"><span class="text-gold fw-bold">⭐ <?= $tx['stars'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
