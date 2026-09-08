<?php
/**
 * Admin — Classes Management
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Classes Management';
$activeNav = 'classes';
$role      = 'admin';

// SSR: Ambil data kelas langsung
$classes = ClassModel::all();

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Kelas</h1>
        <p>Kelola daftar kelas dan tahun ajaran</p>
    </div>
    <button class="btn btn-primary" id="btn-add-class" aria-haspopup="dialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Kelas
    </button>
</div>

<!-- Table (SSR Initial Render) -->
<div class="card">
    <div class="table-wrapper" id="classes-table-wrapper">
        <?php if (empty($classes)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🏫</div>
                <div class="empty-state-title">Belum ada kelas</div>
            </div>
        <?php else: ?>
            <table class="table" aria-label="Tabel Kelas">
                <thead>
                    <tr>
                        <th scope="col">Nama Kelas</th>
                        <th scope="col">Tahun Ajaran</th>
                        <th scope="col">Siswa</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="classes-tbody">
                    <?php foreach ($classes as $c): ?>
                    <tr id="class-row-<?= $c['id'] ?>">
                        <td><span class="fw-semibold" style="color:var(--clr-text-primary);"><?= htmlspecialchars($c['name']) ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($c['academic_year']) ?></td>
                        <td>
                            <span class="badge <?= ($c['student_count'] ?? 0) > 0 ? 'badge-blue' : 'badge-muted' ?>">
                                <?= (int)($c['student_count'] ?? 0) ?> Siswa
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= ($c['is_active'] ?? 1) == 1 ? 'badge-success' : 'badge-muted' ?>">
                                <?= ($c['is_active'] ?? 1) == 1 ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-2" style="justify-content:flex-end;">
                                <button class="btn btn-ghost btn-icon-sm" onclick="editClass(<?= $c['id'] ?>)" title="Edit" aria-label="Edit <?= htmlspecialchars($c['name']) ?>">✏️</button>
                                <button class="btn btn-ghost btn-icon-sm text-danger" onclick="deleteClass(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')" title="Hapus" aria-label="Hapus <?= htmlspecialchars($c['name']) ?>">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop" id="class-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Tambah Kelas</h3>
            <button class="modal-close" id="modal-close" aria-label="Tutup modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="form-error" class="alert alert-error" style="display:none;"></div>
            <form id="class-form">
                <input type="hidden" id="class-id">
                <div class="form-group">
                    <label class="form-label form-label-required" for="f-name">Nama Kelas</label>
                    <input type="text" class="form-control" id="f-name" placeholder="Contoh: XI IPA 1">
                    <span class="form-error" id="err-name"></span>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="f-academic-year">Tahun Ajaran</label>
                    <input type="text" class="form-control" id="f-academic-year" placeholder="Contoh: 2026/2027">
                    <span class="form-error" id="err-academic_year"></span>
                </div>
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
                        <input type="checkbox" id="f-active" checked style="width:16px; height:16px; accent-color:var(--clr-gold-500);">
                        <span class="text-sm">Kelas Aktif</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="modal-cancel">Batal</button>
            <button class="btn btn-primary" id="modal-save"><span class="btn-text">Simpan</span></button>
        </div>
    </div>
</div>

<script>
let allClasses = <?= json_encode($classes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let editingId  = 0;

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
}

async function refreshClasses() {
    const res = await API.get('/api/classes.php');
    allClasses = res.data?.classes ?? [];
    renderTable();
}

function renderTable() {
    if (!allClasses.length) {
        document.getElementById('classes-table-wrapper').innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🏫</div>
                <div class="empty-state-title">Belum ada kelas</div>
            </div>`;
        return;
    }
    const rows = allClasses.map(c => `
        <tr id="class-row-${c.id}">
            <td><span class="fw-semibold" style="color:var(--clr-text-primary);">${escHtml(c.name)}</span></td>
            <td class="text-muted">${escHtml(c.academic_year)}</td>
            <td><span class="badge ${(c.student_count||0) > 0 ? 'badge-blue' : 'badge-muted'}">${(c.student_count||0)} Siswa</span></td>
            <td><span class="badge ${(c.is_active||1)==1?'badge-success':'badge-muted'}">${(c.is_active||1)==1?'Aktif':'Nonaktif'}</span></td>
            <td class="text-right">
                <div class="flex gap-2" style="justify-content:flex-end;">
                    <button class="btn btn-ghost btn-icon-sm" onclick="editClass(${c.id})" title="Edit" aria-label="Edit ${escHtml(c.name)}">✏️</button>
                    <button class="btn btn-ghost btn-icon-sm text-danger" onclick="deleteClass(${c.id},'${escHtml(c.name).replace(/'/g,"\\'")}')" title="Hapus" aria-label="Hapus ${escHtml(c.name)}">🗑️</button>
                </div>
            </td>
        </tr>`).join('');

    document.getElementById('classes-table-wrapper').innerHTML = `
        <table class="table" aria-label="Tabel Kelas">
            <thead><tr><th scope="col">Nama Kelas</th><th scope="col">Tahun Ajaran</th><th scope="col">Siswa</th><th scope="col">Status</th><th scope="col" class="text-right">Aksi</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
}

function openModal(title) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('class-modal').style.display = 'flex';
    document.getElementById('form-error').style.display = 'none';
    ['name','academic_year'].forEach(f => { const el=document.getElementById('err-'+f); if(el) el.textContent=''; });
    document.getElementById('f-name').focus();
}

function closeModal() {
    document.getElementById('class-modal').style.display = 'none';
    document.getElementById('class-form').reset();
    editingId = 0;
}

document.getElementById('btn-add-class').addEventListener('click', () => { editingId = 0; openModal('Tambah Kelas'); });

function editClass(id) {
    const c = allClasses.find(x => x.id == id);
    if (!c) return;
    editingId = id;
    document.getElementById('f-name').value          = c.name;
    document.getElementById('f-academic-year').value = c.academic_year;
    document.getElementById('f-active').checked      = (c.is_active == 1);
    openModal('Edit Kelas');
}

async function deleteClass(id, name) {
    if (!confirm(`Hapus kelas "${name}"?`)) return;
    try {
        const r = await fetch('/api/classes.php?id=' + id, { method: 'DELETE' });
        const data = await r.json();
        if (data.success) {
            Toast.success('Kelas berhasil dihapus.');
            refreshClasses();
        } else {
            Toast.error(data.message ?? 'Gagal menghapus kelas.');
        }
    } catch (e) {
        Toast.error('Gagal: ' + e.message);
    }
}

document.getElementById('modal-save').addEventListener('click', async () => {
    const payload = {
        name:          document.getElementById('f-name').value.trim(),
        academic_year: document.getElementById('f-academic-year').value.trim(),
        is_active:     document.getElementById('f-active').checked ? 1 : 0,
    };
    const btn = document.getElementById('modal-save');
    btn.classList.add('loading'); btn.disabled = true;

    try {
        const url    = editingId ? `/api/classes.php?id=${editingId}` : '/api/classes.php';
        const method = editingId ? 'PUT' : 'POST';
        const res    = await fetch(url, { method, headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const data   = await res.json();
        btn.classList.remove('loading'); btn.disabled = false;

        if (!data.success) {
            if (data.errors) Object.entries(data.errors).forEach(([k,v]) => { const el=document.getElementById('err-'+k); if(el) el.textContent=v; });
            else { const el=document.getElementById('form-error'); el.textContent=data.message; el.style.display='flex'; }
            return;
        }
        Toast.success(data.data?.message ?? 'Kelas berhasil disimpan.');
        closeModal();
        refreshClasses();
    } catch (e) {
        btn.classList.remove('loading'); btn.disabled = false;
        Toast.error('Gagal: ' + e.message);
    }
});

document.getElementById('modal-close').addEventListener('click', closeModal);
document.getElementById('modal-cancel').addEventListener('click', closeModal);
document.getElementById('class-modal').addEventListener('click', e => { if(e.target===document.getElementById('class-modal')) closeModal(); });
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
