<?php
/**
 * Admin — Report Star Activity
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Report Star Activity';
$activeNav = 'report';
$role      = 'admin';

$semesters = SemesterModel::forDropdown();
$classes   = ClassModel::forDropdown();

// Filters
$filterSemId   = (int)($_GET['semester_id'] ?? 0);
$filterClassId = (int)($_GET['class_id'] ?? 0);
$filterDate    = trim($_GET['date'] ?? '');
$search        = trim($_GET['search'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = 50;
$offset        = ($page - 1) * $limit;

$where  = ['st.type = "award"'];
$params = [];
if ($filterSemId)   { $where[] = 'st.semester_id = ?'; $params[] = $filterSemId; }
if ($filterClassId) { $where[] = 'st.class_id = ?';    $params[] = $filterClassId; }
if ($filterDate)    { $where[] = 'DATE(st.awarded_at) = ?'; $params[] = $filterDate; }
if ($search)        { $where[] = '(s.name LIKE ? OR s.nis LIKE ? OR u.name LIKE ?)'; $like = '%'.$search.'%'; $params[] = $like; $params[] = $like; $params[] = $like; }

$whereStr = 'WHERE ' . implode(' AND ', $where);

$total = (int)(Database::queryOne(
    "SELECT COUNT(*) as n FROM star_transactions st
     JOIN students s ON st.student_id = s.id
     JOIN teachers t ON st.teacher_id = t.id
     JOIN users    u ON t.user_id     = u.id
     $whereStr",
    $params
)['n'] ?? 0);

$transactions = Database::query(
    "SELECT st.id, st.stars, st.awarded_at, st.note,
            s.name AS student_name, s.nis,
            c.name AS class_name,
            u.name AS teacher_name,
            sem.name AS semester_name
     FROM star_transactions st
     JOIN students  s   ON st.student_id  = s.id
     JOIN classes   c   ON st.class_id    = c.id
     JOIN teachers  t   ON st.teacher_id  = t.id
     JOIN users     u   ON t.user_id      = u.id
     JOIN semesters sem ON st.semester_id = sem.id
     $whereStr
     ORDER BY st.awarded_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

$totalPages = ceil($total / $limit);

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>📊 Report Star Activity</h1>
        <p>Riwayat semua pemberian bintang</p>
    </div>
    <div class="badge badge-muted" style="font-size:var(--fs-sm);"><?= number_format($total) ?> transaksi</div>
</div>

<!-- Filter -->
<div class="card card-sm mb-6">
    <form method="GET" class="flex gap-3 flex-wrap" style="align-items:flex-end;">
        <div class="form-group" style="margin:0; flex:1; min-width:180px;">
            <label class="form-label">Cari Siswa / Guru</label>
            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama atau NIS...">
        </div>
        <div class="form-group" style="margin:0; min-width:170px;">
            <label class="form-label">Semester</label>
            <select class="form-control" name="semester_id">
                <option value="0">Semua</option>
                <?php foreach ($semesters as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filterSemId == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0; min-width:170px;">
            <label class="form-label">Kelas</label>
            <select class="form-control" name="class_id">
                <option value="0">Semua</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $filterClassId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tanggal</label>
            <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($filterDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Filter</button>
        <a href="/admin/report.php" class="btn btn-secondary" style="align-self:flex-end;">Reset</a>
    </form>
</div>

<!-- Table -->
<div class="card">
    <?php if (empty($transactions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <div class="empty-state-title">Tidak ada data</div>
            <div class="empty-state-desc">Coba ubah filter pencarian</div>
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
                    <th>Semester</th>
                    <th class="text-right">Bintang</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td class="text-muted text-xs" style="white-space:nowrap;"><?= date('d M Y H:i', strtotime($tx['awarded_at'])) ?></td>
                    <td>
                        <div class="student-cell">
                            <div class="avatar avatar-sm"><?= strtoupper(substr($tx['student_name'], 0, 1)) ?></div>
                            <div>
                                <div class="student-cell-name"><?= htmlspecialchars($tx['student_name']) ?></div>
                                <div class="student-cell-nis"><?= htmlspecialchars($tx['nis']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($tx['class_name']) ?></span></td>
                    <td class="text-muted text-sm"><?= htmlspecialchars($tx['teacher_name']) ?></td>
                    <td class="text-muted text-xs"><?= htmlspecialchars($tx['semester_name']) ?></td>
                    <td class="text-right">
                        <span class="fw-bold text-gold" style="font-size:var(--fs-md);">⭐ <?= $tx['stars'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex-between" style="padding:var(--space-4) var(--space-6); border-top:1px solid var(--clr-border);">
        <div class="text-sm text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= number_format($total) ?> transaksi)</div>
        <div class="pagination">
            <?php
            $queryBase = http_build_query(array_filter(['semester_id'=>$filterSemId,'class_id'=>$filterClassId,'date'=>$filterDate,'search'=>$search]));
            for ($p = 1; $p <= $totalPages; $p++):
                if ($totalPages > 10 && abs($p - $page) > 2 && $p !== 1 && $p !== $totalPages) continue;
            ?>
            <a href="?<?= $queryBase ?>&page=<?= $p ?>" class="pagination-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
