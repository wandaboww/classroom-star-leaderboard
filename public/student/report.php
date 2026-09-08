<?php
/**
 * Student — Report Star Activity (own only)
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['student']);

$pageTitle = 'Report Star Activity';
$activeNav = 'report';
$role      = 'student';

$user    = Auth::user();
$student = Database::queryOne('SELECT s.* FROM students s WHERE s.user_id = ?', [$user['id']]);

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$total        = 0;
$transactions = [];

if ($student) {
    $total = (int)(Database::queryOne(
        'SELECT COUNT(*) as n FROM star_transactions WHERE student_id = ? AND type = "award"',
        [$student['id']]
    )['n'] ?? 0);

    $transactions = Database::query(
        "SELECT st.stars, st.awarded_at, c.name AS class_name, u.name AS teacher_name, sem.name AS semester_name
         FROM star_transactions st
         JOIN classes   c   ON st.class_id    = c.id
         JOIN teachers  t   ON st.teacher_id  = t.id
         JOIN users     u   ON t.user_id      = u.id
         JOIN semesters sem ON st.semester_id = sem.id
         WHERE st.student_id = ? AND st.type = 'award'
         ORDER BY st.awarded_at DESC LIMIT ? OFFSET ?",
        [$student['id'], $limit, $offset]
    );
}

$totalPages = $total > 0 ? ceil($total / $limit) : 1;

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div><h1>📊 Report Star Activity</h1><p>Semua bintang yang kamu terima</p></div>
    <div class="badge badge-gold"><?= number_format($total) ?> transaksi</div>
</div>

<div class="card">
    <?php if (empty($transactions)): ?>
        <div class="empty-state"><div class="empty-state-icon">⭐</div><div class="empty-state-title">Belum ada bintang</div><div class="empty-state-desc">Aktif berpartisipasi di kelas untuk mendapat bintang!</div></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Guru</th><th>Semester</th><th class="text-right">⭐</th></tr></thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td class="text-muted text-xs"><?= date('d M Y H:i', strtotime($tx['awarded_at'])) ?></td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($tx['class_name']) ?></span></td>
                    <td class="text-muted"><?= htmlspecialchars($tx['teacher_name']) ?></td>
                    <td class="text-muted text-xs"><?= htmlspecialchars($tx['semester_name']) ?></td>
                    <td class="text-right fw-bold text-gold" style="font-size:var(--fs-md);">⭐ <?= $tx['stars'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="flex-between" style="padding:var(--space-4) var(--space-6); border-top:1px solid var(--clr-border);">
        <div class="text-sm text-muted">Halaman <?= $page ?> dari <?= $totalPages ?></div>
        <div class="pagination">
            <?php for ($p=1; $p<=$totalPages; $p++): ?>
            <a href="?page=<?= $p ?>" class="pagination-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
