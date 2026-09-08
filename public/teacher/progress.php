<?php
/**
 * Teacher — Student Progress
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['teacher']);

$pageTitle = 'Student Progress';
$activeNav = 'progress';
$role      = 'teacher';

$teacher   = TeacherModel::findByUserId(Auth::user()['id']);
$teacherId = $teacher ? (int)$teacher['id'] : 0;
$myClasses = $teacher ? TeacherModel::getClasses($teacherId) : [];
$classIds  = array_unique(array_column($myClasses, 'class_id'));

$filterClassId = (int)($_GET['class_id'] ?? ($classIds[0] ?? 0));
$semester      = SemesterModel::active();
$semesterId    = $semester ? (int)$semester['id'] : 0;
$maxOpp        = (int)(Database::queryOne("SELECT value FROM settings WHERE `key` = 'max_star_opportunity'")['value'] ?? 100);

$students = [];
if ($filterClassId && in_array($filterClassId, $classIds)) {
    $students = Database::query(
        "SELECT s.id, s.name, s.nis,
                COALESCE(SUM(CASE WHEN st.semester_id = $semesterId THEN st.stars ELSE 0 END), 0) AS semester_stars,
                COALESCE(SUM(st.stars), 0) AS total_stars,
                COUNT(st.id) AS tx_count
         FROM students s
         LEFT JOIN star_transactions st ON st.student_id = s.id AND st.type = 'award'
         WHERE s.class_id = ? AND s.is_active = 1
         GROUP BY s.id
         ORDER BY semester_stars DESC",
        [$filterClassId]
    );
}

$classes = $classIds ? Database::query('SELECT id, name FROM classes WHERE id IN (' . implode(',', array_fill(0, count($classIds), '?')) . ')', $classIds) : [];

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div><h1>📈 Student Progress</h1><p>Progres bintang tiap siswa di kelasmu</p></div>
</div>

<div class="card card-sm mb-6">
    <form method="GET" class="flex gap-3" style="align-items:flex-end;">
        <div class="form-group" style="margin:0; min-width:200px;">
            <label class="form-label">Pilih Kelas</label>
            <select class="form-control" name="class_id" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $filterClassId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="card">
    <?php if (empty($students)): ?>
        <div class="empty-state"><div class="empty-state-icon">📈</div><div class="empty-state-title">Pilih kelas untuk melihat progress</div></div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>Rank</th><th>Siswa</th><th>Bintang Semester</th><th>Progress</th><th class="text-right">Total ⭐</th><th class="text-right">Tx</th></tr></thead>
            <tbody>
                <?php foreach ($students as $i => $s):
                    $pct = $maxOpp > 0 ? min(100, round(($s['semester_stars'] / $maxOpp) * 100)) : 0;
                    $level = Database::queryOne('SELECT predicate FROM levels WHERE min_percent <= ? AND max_percent >= ? LIMIT 1', [$pct, $pct]);
                ?>
                <tr>
                    <td><div class="rank-badge <?= match(true){ $i===0=>'rank-badge-1',$i===1=>'rank-badge-2',$i===2=>'rank-badge-3',default=>'rank-badge-n' } ?>" style="margin:auto;"><?= $i+1 ?></div></td>
                    <td><div class="student-cell"><div class="avatar avatar-sm <?= $i<3?'avatar-gold':'' ?>"><?= strtoupper(substr($s['name'],0,1)) ?></div><div><div class="student-cell-name"><?= htmlspecialchars($s['name']) ?></div><div class="student-cell-nis"><?= htmlspecialchars($s['nis']) ?></div></div></div></td>
                    <td><span class="fw-bold text-gold">⭐ <?= $s['semester_stars'] ?></span><?php if ($level): ?> <span class="badge badge-gold text-xs"><?= htmlspecialchars($level['predicate']) ?></span><?php endif; ?></td>
                    <td style="min-width:160px;">
                        <div class="flex gap-2" style="align-items:center;">
                            <div class="progress-bar-wrapper" style="flex:1;"><div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div></div>
                            <span class="text-xs text-muted"><?= $pct ?>%</span>
                        </div>
                    </td>
                    <td class="text-right text-muted">⭐ <?= $s['total_stars'] ?></td>
                    <td class="text-right text-muted text-xs"><?= $s['tx_count'] ?>×</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
