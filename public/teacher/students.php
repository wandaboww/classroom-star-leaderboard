<?php
/**
 * Teacher — Students
 * Teacher melihat daftar siswa di kelas yang diajar
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['teacher']);

$pageTitle = 'My Students';
$activeNav = 'students';
$role      = 'teacher';

$teacher   = TeacherModel::findByUserId(Auth::user()['id']);
$myClasses = $teacher ? TeacherModel::getClasses($teacher['id']) : [];
$classIds  = array_unique(array_column($myClasses, 'class_id'));
$classes   = $classIds ? Database::query(
    'SELECT id, name, academic_year FROM classes WHERE id IN (' . implode(',', array_fill(0, count($classIds), '?')) . ')',
    $classIds
) : [];
// SSR: Ambil data awal siswa untuk kelas guru dengan paginasi
$LIMIT      = 50;
$currPage   = max(1, (int)($_GET['page'] ?? 1));
$initData   = !empty($classIds) ? StudentModel::paginate($currPage, $LIMIT, $classIds, '') : ['students' => [], 'total' => 0, 'page' => 1, 'pages' => 0];
$students   = $initData['students'] ?? [];
$total      = (int)($initData['total'] ?? 0);
$totalPages = (int)($initData['pages'] ?? 1);

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Siswa Saya</h1>
        <p>Daftar siswa di kelas yang kamu ajar</p>
    </div>
    <a href="/teacher/give-stars.php" class="btn btn-primary btn-give-stars-header">
        <span class="btn-star-icon">⭐</span>
        <span>Give Stars</span>
    </a>
</div>

<!-- Filter -->
<div class="card card-sm mb-4">
    <div class="flex gap-3 flex-wrap" style="align-items:center;">
        <div class="input-group" style="flex:1; min-width:200px;">
            <span class="input-icon input-icon-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" class="form-control has-icon-left" id="search-input" placeholder="Cari nama atau NIS..." aria-label="Cari siswa">
        </div>
        <select class="form-control" id="filter-class" style="width:auto; min-width:180px;" aria-label="Filter kelas">
            <option value="0">Semua Kelas Saya (<?= $total ?> siswa)</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['academic_year']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <div id="students-count" class="text-muted text-sm fw-semibold"><?= $total ?> siswa</div>
    </div>
</div>

<!-- Table (SSR Initial Render) -->
<div class="card">
    <div class="table-wrapper" id="students-table-wrapper">
        <?php if (empty($students)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎓</div>
                <div class="empty-state-title">Belum ada siswa</div>
                <div class="empty-state-desc">Hubungi Admin untuk mendaftarkan siswa ke kelas yang kamu ajar.</div>
            </div>
        <?php else: ?>
            <table class="table" aria-label="Tabel Siswa">
                <thead>
                    <tr>
                        <th scope="col">NIS</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Kelas</th>
                        <th scope="col" class="text-right">Total ⭐</th>
                    </tr>
                </thead>
                <tbody id="students-tbody">
                    <?php foreach ($students as $s): ?>
                    <tr id="student-row-<?= $s['id'] ?>">
                        <td><span class="badge badge-muted text-xs"><?= htmlspecialchars($s['nis']) ?></span></td>
                        <td>
                            <div class="student-cell">
                                <div class="avatar avatar-sm"><?= strtoupper(substr($s['name'] ?? 'S', 0, 1)) ?></div>
                                <span class="student-cell-name"><?= htmlspecialchars($s['name']) ?></span>
                            </div>
                        </td>
                        <td><span class="badge badge-blue"><?= htmlspecialchars($s['class_name']) ?></span></td>
                        <td class="text-right fw-bold text-gold">⭐ <?= number_format($s['total_stars']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination Controls -->
    <div class="flex-between mt-4" id="pagination-wrapper" style="<?= $totalPages > 1 ? '' : 'display:none;' ?>">
        <div class="text-muted text-sm" id="pagination-info">
            Menampilkan <?= min(1, $total) ?>–<?= min($LIMIT, $total) ?> dari <?= $total ?> siswa
        </div>
        <div class="pagination" id="pagination-controls">
            <?php if ($currPage > 1): ?>
            <button class="pagination-btn" onclick="changePage(<?= $currPage - 1 ?>)">‹</button>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <button class="pagination-btn <?= $p === $currPage ? 'active' : '' ?>" onclick="changePage(<?= $p ?>)"><?= $p ?></button>
            <?php endfor; ?>
            <?php if ($currPage < $totalPages): ?>
            <button class="pagination-btn" onclick="changePage(<?= $currPage + 1 ?>)">›</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const LIMIT = 50;
let currentPage = <?= (int)$currPage ?>;
let totalStudents = <?= (int)$total ?>;

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
}

async function loadStudents() {
    const classId = document.getElementById('filter-class').value;
    const search  = document.getElementById('search-input').value.trim();
    const url     = `/api/students.php?class_id=${classId}&search=${encodeURIComponent(search)}&page=${currentPage}&limit=${LIMIT}`;
    const res     = await API.get(url);
    const { students = [], total = 0, page = 1 } = res.data ?? {};
    totalStudents = total;
    currentPage   = page;

    document.getElementById('students-count').textContent = `${total} siswa`;

    if (!students.length) {
        document.getElementById('students-table-wrapper').innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🎓</div>
                <div class="empty-state-title">Tidak ada siswa ditemukan</div>
                <div class="empty-state-desc">Coba sesuaikan pencarian atau filter kelas</div>
            </div>`;
        document.getElementById('pagination-wrapper').style.display = 'none';
        return;
    }

    const rows = students.map(s => `
        <tr id="student-row-${s.id}">
            <td><span class="badge badge-muted text-xs">${escHtml(s.nis)}</span></td>
            <td>
                <div class="student-cell">
                    <div class="avatar avatar-sm">${escHtml((s.name||'S')[0].toUpperCase())}</div>
                    <span class="student-cell-name">${escHtml(s.name)}</span>
                </div>
            </td>
            <td><span class="badge badge-blue">${escHtml(s.class_name)}</span></td>
            <td class="text-right fw-bold text-gold">⭐ ${Number(s.total_stars).toLocaleString()}</td>
        </tr>`).join('');

    document.getElementById('students-table-wrapper').innerHTML = `
        <table class="table" aria-label="Tabel Siswa">
            <thead>
                <tr>
                    <th scope="col">NIS</th>
                    <th scope="col">Nama</th>
                    <th scope="col">Kelas</th>
                    <th scope="col" class="text-right">Total ⭐</th>
                </tr>
            </thead>
            <tbody id="students-tbody">${rows}</tbody>
        </table>`;

    renderPagination(total, page);
}

function renderPagination(total, page) {
    const totalPages = Math.ceil(total / LIMIT);
    const wrapper    = document.getElementById('pagination-wrapper');
    if (totalPages <= 1) {
        wrapper.style.display = 'none';
        return;
    }
    wrapper.style.display = 'flex';

    const from = Math.min((page - 1) * LIMIT + 1, total);
    const to   = Math.min(page * LIMIT, total);
    document.getElementById('pagination-info').textContent = `Menampilkan ${from}–${to} dari ${total} siswa`;

    let btns = '';
    if (page > 1) {
        btns += `<button class="pagination-btn" onclick="changePage(${page - 1})">‹</button>`;
    }
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && Math.abs(p - page) > 2 && p !== 1 && p !== totalPages) {
            if (p === 2 || p === totalPages - 1) btns += `<span class="pagination-dots" style="color:var(--clr-text-muted); padding:0 4px;">…</span>`;
            continue;
        }
        btns += `<button class="pagination-btn ${p === page ? 'active' : ''}" onclick="changePage(${p})">${p}</button>`;
    }
    if (page < totalPages) {
        btns += `<button class="pagination-btn" onclick="changePage(${page + 1})">›</button>`;
    }
    document.getElementById('pagination-controls').innerHTML = btns;
}

function changePage(p) {
    currentPage = p;
    loadStudents();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

let debounceTimer;
document.getElementById('search-input').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { currentPage = 1; loadStudents(); }, 350);
});
document.getElementById('filter-class').addEventListener('change', () => {
    currentPage = 1;
    loadStudents();
});
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
