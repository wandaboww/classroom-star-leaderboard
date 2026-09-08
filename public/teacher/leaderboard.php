<?php
/**
 * Teacher — Leaderboard
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['teacher']);

$pageTitle = 'Leaderboard';
$activeNav = 'leaderboard';
$role      = 'teacher';

$teacher   = TeacherModel::findByUserId(Auth::user()['id']);
$myClasses = $teacher ? TeacherModel::getClasses($teacher['id']) : [];
$classIds  = array_unique(array_column($myClasses, 'class_id'));

$filterClassId = (int)($_GET['class_id'] ?? ($classIds[0] ?? 0));
$semester      = SemesterModel::active();
$semesterId    = $semester ? (int)$semester['id'] : 0;
$filterSemId   = (int)($_GET['semester_id'] ?? $semesterId);

$leaderboard = [];
if ($classIds) {
    $whereStudent = ['s.is_active = 1'];
    $studentParams = [];

    if ($filterClassId && in_array($filterClassId, $classIds)) {
        $whereStudent[] = 's.class_id = ?';
        $studentParams[] = $filterClassId;
    } else {
        $placeholders = implode(',', array_fill(0, count($classIds), '?'));
        $whereStudent[] = "s.class_id IN ($placeholders)";
        $studentParams = array_merge($studentParams, $classIds);
    }

    $joinConditions = ['st.student_id = s.id', 'st.type = "award"'];
    $joinParams = [];
    if ($filterSemId) {
        $joinConditions[] = 'st.semester_id = ?';
        $joinParams[] = $filterSemId;
    }

    $joinStr  = implode(' AND ', $joinConditions);
    $whereStr = 'WHERE ' . implode(' AND ', $whereStudent);

    $leaderboard = Database::query(
        "SELECT s.id, s.name, s.nis, c.name AS class_name,
                COALESCE(SUM(st.stars), 0) AS total_stars
         FROM students s
         JOIN classes c ON s.class_id = c.id
         LEFT JOIN star_transactions st ON $joinStr
         $whereStr
         GROUP BY s.id, s.name, s.nis, c.name
         ORDER BY total_stars DESC, s.name ASC",
        array_merge($joinParams, $studentParams)
    );
}

$semesters = SemesterModel::forDropdown();
$classes = $classIds ? Database::query(
    'SELECT id, name, academic_year FROM classes WHERE id IN (' . implode(',', array_fill(0, count($classIds), '?')) . ')',
    $classIds
) : [];

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>🏆 Leaderboard</h1>
        <p>Ranking siswa di kelas yang kamu ajar</p>
    </div>
    <div class="flex gap-3">
        <a href="/teacher/rules.php" class="btn btn-secondary" id="btn-star-rules">
            📖 Cara kerja bintang
        </a>
    </div>
</div>

<div class="card card-sm mb-6">
    <form method="GET" class="flex gap-5 flex-wrap" style="align-items:center;">
        <div class="form-group" style="margin:0; display:flex; align-items:center; gap:10px; min-width:220px;">
            <label class="form-label" style="margin:0; white-space:nowrap; font-weight:600; color:var(--clr-text-secondary); font-size:var(--fs-sm);">Kelas:</label>
            <select class="form-control" name="class_id" onchange="this.form.submit()">
                <option value="0">Semua Kelas</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterClassId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0; display:flex; align-items:center; gap:10px; min-width:220px;">
            <label class="form-label" style="margin:0; white-space:nowrap; font-weight:600; color:var(--clr-text-secondary); font-size:var(--fs-sm);">Semester:</label>
            <select class="form-control" name="semester_id" onchange="this.form.submit()">
                <option value="0">Semua</option>
                <?php foreach ($semesters as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filterSemId == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="card">
    <?php if (empty($leaderboard)): ?>
        <div class="empty-state"><div class="empty-state-icon">🏆</div><div class="empty-state-title">Belum ada data</div></div>
    <?php else: ?>

    <!-- Top 3 Podium (Rank 1 Kiri, Rank 2 Tengah, Rank 3 Kanan) -->
    <?php $top3 = array_slice($leaderboard, 0, 3); ?>
    <div class="podium-container">
        <?php
        $podiumOrder = [0, 1, 2]; // rank 1 (kiri), rank 2 (tengah), rank 3 (kanan)
        foreach ($podiumOrder as $idx):
            if (!isset($top3[$idx])) continue;
            $s = $top3[$idx];
            $rankNum = $idx + 1;
            $rankClass = ['rank-1st', 'rank-2nd', 'rank-3rd'][$idx];
            $medals = ['🥇', '🥈', '🥉'];
            $rankTitles = ['Rank 1', 'Rank 2', 'Rank 3'];
            $avatarClass = match($idx) {
                0 => 'avatar-gold',
                1 => 'avatar-silver',
                default => 'avatar-bronze',
            };
        ?>
        <div class="podium-card podium-rank-<?= $rankNum ?> animate-fade-in" style="animation-delay: <?= $idx * 0.1 ?>s;">
            <div class="podium-rank-pill"><?= $medals[$idx] ?> <?= $rankTitles[$idx] ?></div>
            <div class="avatar avatar-xl <?= $avatarClass ?>" style="margin:0 auto var(--space-3);">
                <?= strtoupper(substr($s['name'], 0, 1)) ?>
            </div>
            <div class="fw-bold" style="color:var(--clr-text-primary); font-size:var(--fs-md); margin-bottom: 2px;"><?= htmlspecialchars($s['name']) ?></div>
            <div class="text-muted text-xs mb-3"><?= htmlspecialchars($s['class_name']) ?></div>
            <div class="podium-medal <?= $rankClass ?> medal-rank-<?= $rankNum ?>"><?= $medals[$idx] ?></div>
            <div class="text-gold fw-bold" style="font-size:var(--fs-lg);">⭐ <?= number_format($s['total_stars']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th class="text-center" style="width:60px;">Rank</th><th>Siswa</th><th>Kelas</th><th class="text-right">Total ⭐</th></tr></thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $s): ?>
                <tr>
                    <td class="text-center">
                        <div class="rank-badge <?= match(true) { $i===0=>'rank-badge-1',$i===1=>'rank-badge-2',$i===2=>'rank-badge-3',default=>'rank-badge-n' } ?>" style="margin:auto;"><?= $i+1 ?></div>
                    </td>
                    <td>
                        <div class="student-cell">
                            <div class="avatar avatar-sm <?= $i<3?'avatar-gold':'' ?>"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                            <div><div class="student-cell-name"><?= htmlspecialchars($s['name']) ?></div><div class="student-cell-nis"><?= htmlspecialchars($s['nis']) ?></div></div>
                        </div>
                    </td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($s['class_name']) ?></span></td>
                    <td class="text-right fw-bold <?= $i<3?'text-gold':'' ?>" style="font-size:var(--fs-md);">⭐ <?= number_format($s['total_stars']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
