<?php
/**
 * Admin — Leaderboard
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Leaderboard';
$activeNav = 'leaderboard';
$role      = 'admin';

// Ambil semester aktif
$semester = SemesterModel::active();
$semesterId = $semester ? (int)$semester['id'] : 0;
$semesters  = SemesterModel::forDropdown();
$classes    = ClassModel::forDropdown();

// Filter params
$filterSemId   = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : $semesterId;
$filterClassId = (int)($_GET['class_id'] ?? 0);

// Query leaderboard
$whereStudent = ['s.is_active = 1'];
$studentParams = [];
if ($filterClassId) {
    $whereStudent[] = 's.class_id = ?';
    $studentParams[] = $filterClassId;
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

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>🏆 Leaderboard</h1>
        <p>Ranking siswa berdasarkan total bintang</p>
    </div>
</div>

<!-- Filter Dropdowns (Auto Filter) -->
<div class="card card-sm mb-6">
    <form method="GET" class="flex gap-6 flex-wrap" style="align-items:center;">
        <div class="form-group" style="margin:0; display:flex; align-items:center; gap:10px; flex:1; min-width:240px;">
            <label class="form-label" style="margin:0; white-space:nowrap; font-weight:600; color:var(--clr-text-secondary); font-size:var(--fs-sm);">Semester:</label>
            <select class="form-control" name="semester_id" onchange="this.form.submit()" style="flex:1;">
                <option value="0" <?= (isset($_GET['semester_id']) && $filterSemId === 0) ? 'selected' : '' ?>>Semua Semester</option>
                <?php foreach ($semesters as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filterSemId == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                    <?= $s['status'] === 'active' ? ' ★ (Aktif)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0; display:flex; align-items:center; gap:10px; flex:1; min-width:240px;">
            <label class="form-label" style="margin:0; white-space:nowrap; font-weight:600; color:var(--clr-text-secondary); font-size:var(--fs-sm);">Kelas:</label>
            <select class="form-control" name="class_id" onchange="this.form.submit()" style="flex:1;">
                <option value="0">Semua Kelas</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterClassId == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['academic_year']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<!-- Leaderboard Table -->
<div class="card">
    <?php if (empty($leaderboard)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🏆</div>
            <div class="empty-state-title">Belum ada data</div>
            <div class="empty-state-desc">Belum ada bintang yang diberikan di periode ini.</div>
        </div>
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
            <thead>
                <tr>
                    <th style="width:60px;" class="text-center">Rank</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th class="text-right">Total Bintang</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $s): ?>
                <tr <?= $i < 3 ? 'style="background:rgba(255,193,7,0.03);"' : '' ?>>
                    <td class="text-center">
                        <div class="rank-badge <?= match(true) { $i===0 => 'rank-badge-1', $i===1 => 'rank-badge-2', $i===2 => 'rank-badge-3', default => 'rank-badge-n' } ?>" style="margin:auto;">
                            <?= $i + 1 ?>
                        </div>
                    </td>
                    <td>
                        <div class="student-cell">
                            <div class="avatar avatar-sm <?= $i < 3 ? 'avatar-gold' : '' ?>"><?= strtoupper(substr($s['name'], 0, 1)) ?></div>
                            <div>
                                <div class="student-cell-name"><?= htmlspecialchars($s['name']) ?></div>
                                <div class="student-cell-nis"><?= htmlspecialchars($s['nis']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="white-space: nowrap;"><span class="badge badge-blue" style="white-space: nowrap;"><?= htmlspecialchars($s['class_name']) ?></span></td>
                    <td class="text-right">
                        <span class="star-count fw-bold <?= $i < 3 ? 'text-gold' : '' ?>" style="font-size:var(--fs-md);">
                            ⭐ <?= number_format($s['total_stars']) ?>
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
