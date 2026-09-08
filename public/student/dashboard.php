<?php
/**
 * Student Dashboard
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['student']);

$pageTitle = 'My Dashboard';
$activeNav = 'dashboard';
$role      = 'student';
$user      = Auth::user();

// Dapatkan data student
$student = Database::queryOne(
    'SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?',
    [$user['id']]
);

$totalStars = 0;
$myLevel    = null;
$myRank     = null;
$recentTx   = [];
$maxOpportunity = (int)(Database::queryOne("SELECT value FROM settings WHERE `key` = 'max_star_opportunity'")['value'] ?? 100);

if ($student) {
    // Total bintang siswa
    $totalStars = Database::queryOne(
        'SELECT COALESCE(SUM(stars), 0) as n FROM star_transactions WHERE student_id = ? AND type = "award"',
        [$student['id']]
    )['n'] ?? 0;

    // Hitung level berdasarkan persentase
    $percent = $maxOpportunity > 0 ? ($totalStars / $maxOpportunity) * 100 : 0;
    $myLevel = Database::queryOne(
        'SELECT * FROM levels WHERE min_percent <= ? AND max_percent >= ? LIMIT 1',
        [$percent, $percent]
    );
    if (!$myLevel) {
        $myLevel = Database::queryOne('SELECT * FROM levels ORDER BY level_number ASC LIMIT 1');
    }

    // Rank di kelas
    $rankResult = Database::query(
        'SELECT student_id, SUM(stars) as total
         FROM star_transactions
         WHERE class_id = (SELECT class_id FROM students WHERE id = ?) AND type = "award"
         GROUP BY student_id
         ORDER BY total DESC',
        [$student['id']]
    );
    foreach ($rankResult as $i => $row) {
        if ($row['student_id'] == $student['id']) {
            $myRank = $i + 1;
            break;
        }
    }

    // Transaksi terbaru saya
    $recentTx = Database::query(
        'SELECT st.*, u.name AS teacher_name
         FROM star_transactions st
         JOIN teachers t ON st.teacher_id = t.id
         JOIN users    u ON t.user_id     = u.id
         WHERE st.student_id = ? AND st.type = "award"
         ORDER BY st.awarded_at DESC LIMIT 6',
        [$student['id']]
    );
}

$starPercent = $maxOpportunity > 0 ? min(100, round(($totalStars / $maxOpportunity) * 100)) : 0;

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header">
    <h1>Halo, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
    <p>Kelas: <strong><?= htmlspecialchars($student['class_name'] ?? '-') ?></strong> &nbsp;·&nbsp; NIS: <strong><?= htmlspecialchars($student['nis'] ?? '-') ?></strong></p>
</div>

<!-- Hero: My Stars -->
<div class="grid grid-3 mb-6">

    <!-- Stars Card -->
    <div class="card card-gold animate-fade-in" style="background: linear-gradient(135deg, rgba(255,193,7,0.1) 0%, rgba(255,143,0,0.05) 100%); grid-column: span 1;">
        <div class="text-center">
            <div style="font-size:3.5rem; margin-bottom: var(--space-3); animation: float 3s ease-in-out infinite;">⭐</div>
            <div style="font-size: var(--fs-4xl); font-weight:900; color: var(--clr-gold-400); letter-spacing:-0.04em; line-height:1;"><?= number_format($totalStars) ?></div>
            <div style="color: var(--clr-text-muted); font-size: var(--fs-sm); margin-top: var(--space-2);">Total Bintang Kamu</div>
        </div>
        <div class="progress-bar-wrapper mt-4">
            <div class="progress-bar-fill" style="width: <?= $starPercent ?>%;" title="<?= $starPercent ?>% dari <?= $maxOpportunity ?> bintang"></div>
        </div>
        <div class="flex-between mt-2">
            <span class="text-xs text-muted"><?= $starPercent ?>% pencapaian</span>
            <span class="text-xs text-muted">Maks: <?= $maxOpportunity ?>⭐</span>
        </div>
    </div>

    <!-- Level Card -->
    <div class="card animate-fade-in" style="animation-delay:0.05s;">
        <div class="stat-card-label mb-3">Level Saya</div>
        <?php if ($myLevel): ?>
            <div style="font-size: var(--fs-4xl); font-weight:900; letter-spacing:-0.04em; margin-bottom: var(--space-2);">
                Level <?= $myLevel['level_number'] ?>
            </div>
            <div class="badge badge-gold mb-3"><?= htmlspecialchars($myLevel['predicate']) ?></div>
            <div class="text-xs text-muted">Nilai Tambah: <strong style="color:var(--clr-gold-400)">+<?= $myLevel['additional_score'] ?></strong></div>
        <?php else: ?>
            <div class="text-muted">—</div>
        <?php endif; ?>
    </div>

    <!-- Rank Card -->
    <div class="card animate-fade-in" style="animation-delay:0.1s;">
        <div class="stat-card-label mb-3">Ranking di Kelas</div>
        <?php if ($myRank): ?>
            <div style="font-size: var(--fs-4xl); font-weight:900; letter-spacing:-0.04em; margin-bottom: var(--space-2);"
                 class="<?= $myRank === 1 ? 'rank-1st' : ($myRank === 2 ? 'rank-2nd' : ($myRank === 3 ? 'rank-3rd' : '')) ?>">
                #<?= $myRank ?>
            </div>
            <?php if ($myRank <= 3): ?>
                <div class="badge badge-gold"><?= ['🥇 Juara 1', '🥈 Juara 2', '🥉 Juara 3'][$myRank - 1] ?></div>
            <?php else: ?>
                <div class="text-xs text-muted">Terus semangat! ⭐</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-muted">Belum ada data</div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Links -->
<div class="grid grid-2 mb-6">
    <a href="/student/leaderboard.php" class="card card-hover animate-fade-in" style="animation-delay:0.15s; text-decoration:none;">
        <div class="flex gap-4" style="align-items:center;">
            <div class="stat-card-icon stat-card-icon-gold" style="font-size:1.5rem; width:48px; height:48px;">🏆</div>
            <div>
                <div class="fw-bold" style="color:var(--clr-text-primary);">Leaderboard</div>
                <div class="text-xs text-muted">Lihat ranking kelas</div>
            </div>
        </div>
    </a>
    <a href="/student/report.php" class="card card-hover animate-fade-in" style="animation-delay:0.2s; text-decoration:none;">
        <div class="flex gap-4" style="align-items:center;">
            <div class="stat-card-icon stat-card-icon-blue" style="font-size:1.5rem; width:48px; height:48px;">📊</div>
            <div>
                <div class="fw-bold" style="color:var(--clr-text-primary);">Report Star Activity</div>
                <div class="text-xs text-muted">Riwayat bintang kamu</div>
            </div>
        </div>
    </a>
</div>

<!-- Recent Star Activity -->
<div class="card animate-fade-in" style="animation-delay:0.25s">
    <div class="flex-between mb-6">
        <h3 style="margin:0;">Bintang Terbaru</h3>
        <a href="/student/report.php" class="btn btn-ghost btn-sm">Lihat Semua →</a>
    </div>

    <?php if (empty($recentTx)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">⭐</div>
            <div class="empty-state-title">Belum ada bintang</div>
            <div class="empty-state-desc">Aktif di kelas untuk mendapatkan bintang dari gurumu!</div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr><th>Tanggal</th><th>Guru</th><th class="text-right">Bintang</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTx as $tx): ?>
                    <tr>
                        <td class="text-muted text-xs"><?= date('d M Y H:i', strtotime($tx['awarded_at'])) ?></td>
                        <td><?= htmlspecialchars($tx['teacher_name']) ?></td>
                        <td class="text-right"><span class="text-gold fw-bold">⭐ <?= $tx['stars'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
