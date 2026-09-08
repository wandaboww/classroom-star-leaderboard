<?php
/**
 * Admin — Teachers Management
 * Menampilkan teacher list + class assignment
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Teachers Management';
$activeNav = 'teachers';
$role      = 'admin';

// SSR: Ambil data guru langsung
$teachers  = TeacherModel::all();
$classes   = ClassModel::forDropdown();
$semesters = SemesterModel::forDropdown();

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Teachers</h1>
        <p>Kelola akun guru dan assignment kelas</p>
    </div>
    <a href="/admin/users.php" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Teacher (via Users)
    </a>
</div>

<div class="alert alert-info mb-6" style="align-items:center; gap:var(--space-3);">
    ℹ️ Untuk menambah teacher baru, buat user dengan role <strong>Teacher</strong> di halaman <a href="/admin/users.php">Users</a>. Profil teacher otomatis dibuat.
</div>

<!-- Teachers Table (SSR Initial Render) -->
<div class="card mb-6">
    <div class="table-wrapper" id="teachers-table-wrapper">
        <?php if (empty($teachers)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">👨‍🏫</div>
                <div class="empty-state-title">Belum ada teacher</div>
                <p class="empty-state-desc">Buat user dengan role "Teacher" di halaman Users.</p>
            </div>
        <?php else: ?>
            <table class="table" aria-label="Tabel Guru">
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Email</th>
                        <th scope="col">NIP</th>
                        <th scope="col">Kelas</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="teachers-tbody">
                    <?php foreach ($teachers as $t): ?>
                    <tr id="teacher-row-<?= $t['id'] ?>">
                        <td>
                            <div class="student-cell">
                                <div class="avatar avatar-sm avatar-purple"><?= strtoupper(substr($t['name'] ?? 'T', 0, 1)) ?></div>
                                <div>
                                    <div class="student-cell-name"><?= htmlspecialchars($t['name']) ?></div>
                                    <div class="student-cell-nis">@<?= htmlspecialchars($t['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted text-sm"><?= htmlspecialchars($t['email']) ?></td>
                        <td class="text-muted text-sm"><?= htmlspecialchars($t['nip'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= ($t['class_count'] ?? 0) > 0 ? 'badge-purple' : 'badge-muted' ?>">
                                <?= (int)($t['class_count'] ?? 0) ?> Kelas
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= ($t['is_active'] ?? 1) == 1 ? 'badge-success' : 'badge-muted' ?>">
                                <?= ($t['is_active'] ?? 1) == 1 ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <button class="btn btn-sm btn-secondary" onclick="openAssignModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['name'])) ?>')" aria-label="Manage Kelas <?= htmlspecialchars($t['name']) ?>">
                                🏫 Manage Kelas
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Assign Class -->
<div class="modal-backdrop" id="assign-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="assign-teacher-name">
    <div class="modal" style="max-width:640px;">
        <div class="modal-header">
            <h3 class="modal-title">Assign Kelas — <span id="assign-teacher-name"></span></h3>
            <button class="modal-close" id="assign-modal-close" aria-label="Tutup modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <h4 class="mb-3" style="font-size:var(--fs-sm); color:var(--clr-text-muted); text-transform:uppercase; letter-spacing:0.06em;">Kelas yang Sudah Di-assign</h4>
            <div id="current-assignments" style="margin-bottom:var(--space-6);"></div>

            <h4 class="mb-3" style="font-size:var(--fs-sm); color:var(--clr-text-muted); text-transform:uppercase; letter-spacing:0.06em;">Tambah Assignment Baru</h4>
            <div id="assign-error" class="alert alert-error mb-4" style="display:none;"></div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label form-label-required" for="assign-class-id">Kelas</label>
                    <select class="form-control" id="assign-class-id">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['academic_year']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="assign-semester-id">Semester</label>
                    <select class="form-control" id="assign-semester-id">
                        <option value="">-- Pilih Semester --</option>
                        <?php foreach ($semesters as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="assign-modal-cancel">Tutup</button>
            <button class="btn btn-primary" id="btn-do-assign"><span class="btn-text">Assign Kelas</span></button>
        </div>
    </div>
</div>

<script>
let allTeachers = <?= json_encode($teachers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let activeTeacherId = 0;

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
}

async function refreshTeachers() {
    const res = await API.get('/api/teachers.php');
    allTeachers = res.data?.teachers ?? [];
    renderTable();
}

function renderTable() {
    if (!allTeachers.length) {
        document.getElementById('teachers-table-wrapper').innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">👨‍🏫</div>
                <div class="empty-state-title">Belum ada teacher</div>
                <p class="empty-state-desc">Buat user dengan role "Teacher" di halaman Users.</p>
            </div>`;
        return;
    }
    const rows = allTeachers.map(t => `
        <tr id="teacher-row-${t.id}">
            <td>
                <div class="student-cell">
                    <div class="avatar avatar-sm avatar-purple">${escHtml((t.name||'T')[0].toUpperCase())}</div>
                    <div>
                        <div class="student-cell-name">${escHtml(t.name)}</div>
                        <div class="student-cell-nis">@${escHtml(t.username)}</div>
                    </div>
                </div>
            </td>
            <td class="text-muted text-sm">${escHtml(t.email)}</td>
            <td class="text-muted text-sm">${escHtml(t.nip ?? '—')}</td>
            <td><span class="badge ${(t.class_count||0)>0?'badge-purple':'badge-muted'}">${(t.class_count||0)} Kelas</span></td>
            <td><span class="badge ${(t.is_active||1)==1?'badge-success':'badge-muted'}">${(t.is_active||1)==1?'Aktif':'Nonaktif'}</span></td>
            <td class="text-right">
                <button class="btn btn-sm btn-secondary" onclick="openAssignModal(${t.id},'${escHtml(t.name).replace(/'/g,"\\'")}')" aria-label="Manage Kelas ${escHtml(t.name)}">
                    🏫 Manage Kelas
                </button>
            </td>
        </tr>`).join('');

    document.getElementById('teachers-table-wrapper').innerHTML = `
        <table class="table" aria-label="Tabel Guru">
            <thead><tr><th scope="col">Nama</th><th scope="col">Email</th><th scope="col">NIP</th><th scope="col">Kelas</th><th scope="col">Status</th><th scope="col" class="text-right">Aksi</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
}

async function openAssignModal(teacherId, name) {
    activeTeacherId = teacherId;
    document.getElementById('assign-teacher-name').textContent = name;
    document.getElementById('assign-modal').style.display = 'flex';
    document.getElementById('assign-error').style.display = 'none';
    await loadCurrentAssignments();
}

function closeAssignModal() {
    document.getElementById('assign-modal').style.display = 'none';
    activeTeacherId = 0;
}

async function loadCurrentAssignments() {
    const res  = await API.get(`/api/teachers.php?id=${activeTeacherId}&action=classes`);
    const list = res.data?.classes ?? [];
    const el   = document.getElementById('current-assignments');

    if (!list.length) {
        el.innerHTML = `<div class="text-muted text-sm" style="padding:var(--space-3) 0;">Belum ada kelas yang di-assign.</div>`;
        return;
    }
    el.innerHTML = `
        <div style="display:flex; flex-direction:column; gap:var(--space-2);">
            ${list.map(a => `
                <div style="display:flex; align-items:center; justify-content:space-between; padding:var(--space-3) var(--space-4); background:var(--clr-bg-tertiary); border-radius:var(--radius-md); border:1px solid var(--clr-border);">
                    <div>
                        <span class="fw-semibold" style="color:var(--clr-text-primary);">${escHtml(a.class_name)}</span>
                        <span class="text-muted text-sm"> · ${escHtml(a.semester_name)}</span>
                    </div>
                    <button class="btn btn-ghost btn-sm" style="color:var(--clr-danger);" onclick="removeAssignment(${a.id})" aria-label="Hapus assignment ${escHtml(a.class_name)}">Hapus</button>
                </div>
            `).join('')}
        </div>`;
}

async function removeAssignment(assignId) {
    if (!confirm('Hapus assignment ini?')) return;
    try {
        const r = await fetch(`/api/teachers.php?id=${activeTeacherId}&action=remove_class&assignment_id=${assignId}`, { method:'DELETE' });
        const data = await r.json();
        if (data.success) {
            Toast.success('Assignment dihapus.');
            loadCurrentAssignments();
            refreshTeachers();
        } else {
            Toast.error(data.message ?? 'Gagal menghapus assignment.');
        }
    } catch (e) {
        Toast.error('Gagal: ' + e.message);
    }
}

document.getElementById('btn-do-assign').addEventListener('click', async () => {
    const classId    = document.getElementById('assign-class-id').value;
    const semesterId = document.getElementById('assign-semester-id').value;
    if (!classId || !semesterId) {
        document.getElementById('assign-error').textContent = 'Pilih kelas dan semester.';
        document.getElementById('assign-error').style.display = 'flex';
        return;
    }
    document.getElementById('assign-error').style.display = 'none';
    const btn = document.getElementById('btn-do-assign');
    btn.classList.add('loading'); btn.disabled = true;

    try {
        const res = await fetch(`/api/teachers.php?id=${activeTeacherId}&action=assign`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ class_id: parseInt(classId), semester_id: parseInt(semesterId) })
        });
        const data = await res.json();
        btn.classList.remove('loading'); btn.disabled = false;
        if (!data.success) {
            document.getElementById('assign-error').textContent = data.message;
            document.getElementById('assign-error').style.display = 'flex';
            return;
        }
        Toast.success('Kelas berhasil di-assign!');
        document.getElementById('assign-class-id').value = '';
        loadCurrentAssignments();
        refreshTeachers();
    } catch (e) {
        btn.classList.remove('loading'); btn.disabled = false;
        Toast.error('Gagal assign kelas: ' + e.message);
    }
});

document.getElementById('assign-modal-close').addEventListener('click', closeAssignModal);
document.getElementById('assign-modal-cancel').addEventListener('click', closeAssignModal);
document.getElementById('assign-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('assign-modal')) closeAssignModal();
});
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
