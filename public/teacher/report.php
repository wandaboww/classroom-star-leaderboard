<?php
/**
 * Teacher — Report Star Activity
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['teacher']);

$pageTitle = 'Report Star Activity';
$activeNav = 'report';
$role      = 'teacher';

$teacher   = TeacherModel::findByUserId(Auth::user()['id']);
$teacherId = $teacher ? (int)$teacher['id'] : 0;
$myClasses = $teacher ? TeacherModel::getClasses($teacherId) : [];
$classIds  = array_unique(array_column($myClasses, 'class_id'));

$filterClassId = (int)($_GET['class_id'] ?? 0);
$filterDate    = trim($_GET['date'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = 50;
$offset        = ($page - 1) * $limit;

$where  = ['st.type = "award"', 'st.teacher_id = ?'];
$params = [$teacherId];

if ($filterClassId && in_array($filterClassId, $classIds)) {
    $where[] = 'st.class_id = ?'; $params[] = $filterClassId;
} elseif ($classIds) {
    $placeholders = implode(',', array_fill(0, count($classIds), '?'));
    $where[] = "st.class_id IN ($placeholders)";
    $params  = array_merge($params, $classIds);
}
if ($filterDate) { $where[] = 'DATE(st.awarded_at) = ?'; $params[] = $filterDate; }

$whereStr = 'WHERE ' . implode(' AND ', $where);
$total    = (int)(Database::queryOne("SELECT COUNT(*) as n FROM star_transactions st $whereStr", $params)['n'] ?? 0);
$transactions = Database::query(
    "SELECT st.stars, st.awarded_at, s.name AS student_name, s.nis, c.name AS class_name, sem.name AS semester_name
     FROM star_transactions st
     JOIN students  s   ON st.student_id  = s.id
     JOIN classes   c   ON st.class_id    = c.id
     JOIN semesters sem ON st.semester_id = sem.id
     $whereStr ORDER BY st.awarded_at DESC LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

$classes = $classIds ? Database::query('SELECT id, name FROM classes WHERE id IN (' . implode(',', array_fill(0, count($classIds), '?')) . ')', $classIds) : [];

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div><h1>📊 Report Star Activity</h1><p>Riwayat bintang yang kamu berikan</p></div>
    <div class="badge badge-muted"><?= number_format($total) ?> transaksi</div>
</div>

<div class="card card-sm mb-6">
    <form method="GET" class="flex gap-3 flex-wrap" style="align-items:flex-end;">
        <div class="form-group" style="margin:0; min-width:160px;">
            <label class="form-label">Kelas</label>
            <select class="form-control" name="class_id">
                <option value="0">Semua Kelas</option>
                <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $filterClassId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tanggal</label>
            <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($filterDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Filter</button>
        <a href="/teacher/report.php" class="btn btn-secondary" style="align-self:flex-end;">Reset</a>
    </form>
</div>

<div class="card">
    <?php if (empty($transactions)): ?>
        <div class="empty-state"><div class="empty-state-icon">📊</div><div class="empty-state-title">Belum ada data</div></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Siswa</th><th>Kelas</th><th>Semester</th><th class="text-right">⭐</th></tr></thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td class="text-muted text-xs"><?= date('d M Y H:i', strtotime($tx['awarded_at'])) ?></td>
                    <td><div class="student-cell"><div class="avatar avatar-sm"><?= strtoupper(substr($tx['student_name'],0,1)) ?></div><div><div class="student-cell-name"><?= htmlspecialchars($tx['student_name']) ?></div><div class="student-cell-nis"><?= htmlspecialchars($tx['nis']) ?></div></div></div></td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($tx['class_name']) ?></span></td>
                    <td class="text-muted text-xs"><?= htmlspecialchars($tx['semester_name']) ?></td>
                    <td class="text-right fw-bold text-gold" style="font-size:var(--fs-md);">⭐ <?= $tx['stars'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
