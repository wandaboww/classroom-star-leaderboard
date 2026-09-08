<?php
/**
 * Admin — Semesters Management
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Semesters Management';
$activeNav = 'semesters';
$role      = 'admin';

// SSR: Ambil data semester langsung
$semesters = SemesterModel::all();

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Semester / Periode</h1>
        <p>Kelola periode akademik dan aktifkan semester berjalan</p>
    </div>
    <button class="btn btn-primary" id="btn-add-semester" aria-haspopup="dialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Semester
    </button>
</div>

<!-- Table (SSR Initial Render) -->
<div class="card">
    <div class="table-wrapper" id="semesters-table-wrapper">
        <?php if (empty($semesters)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📅</div>
                <div class="empty-state-title">Belum ada semester</div>
            </div>
        <?php else: ?>
            <table class="table" aria-label="Tabel Semester">
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Tahun Ajaran</th>
                        <th scope="col">Status</th>
                        <th scope="col">Periode</th>
                        <th scope="col" class="text-right">Transaksi</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="semesters-tbody">
                    <?php foreach ($semesters as $s): ?>
                    <tr id="semester-row-<?= $s['id'] ?>">
                        <td><span class="fw-semibold" style="color:var(--clr-text-primary);"><?= htmlspecialchars($s['name']) ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($s['academic_year']) ?></td>
                        <td>
                            <?php
                            $st = $s['status'];
                            if ($st === 'active') echo '<span class="badge badge-success">🟢 Active</span>';
                            elseif ($st === 'closed') echo '<span class="badge badge-purple">Closed</span>';
                            elseif ($st === 'draft') echo '<span class="badge badge-muted">Draft</span>';
                            else echo '<span class="badge badge-muted">Archived</span>';
                            ?>
                        </td>
                        <td class="text-muted text-sm"><?= htmlspecialchars($s['started_at'] ?? '—') ?> → <?= htmlspecialchars($s['ended_at'] ?? '—') ?></td>
                        <td class="text-right">
                            <span class="badge badge-muted text-xs"><?= (int)($s['tx_count'] ?? 0) ?> transaksi</span>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-2" style="justify-content:flex-end;">
                                <?php if ($s['status'] !== 'active'): ?>
                                <button class="btn btn-sm btn-secondary" onclick="activateSemester(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')" aria-label="Aktifkan <?= htmlspecialchars($s['name']) ?>">✅ Aktifkan</button>
                                <?php endif; ?>
                                <button class="btn btn-ghost btn-icon-sm" onclick="editSemester(<?= $s['id'] ?>)" title="Edit" aria-label="Edit <?= htmlspecialchars($s['name']) ?>">✏️</button>
                                <?php if (($s['tx_count'] ?? 0) == 0): ?>
                                <button class="btn btn-ghost btn-icon-sm text-danger" onclick="deleteSemester(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')" title="Hapus" aria-label="Hapus <?= htmlspecialchars($s['name']) ?>">🗑️</button>
                                <?php endif; ?>
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
<div class="modal-backdrop" id="semester-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Tambah Semester</h3>
            <button class="modal-close" id="modal-close" aria-label="Tutup modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="form-error" class="alert alert-error" style="display:none;"></div>
            <form id="semester-form">
                <input type="hidden" id="semester-id">
                <div class="form-group">
                    <label class="form-label form-label-required" for="f-name">Nama Semester</label>
                    <input type="text" class="form-control" id="f-name" placeholder="Contoh: Semester Ganjil 2026/2027">
                    <span class="form-error" id="err-name"></span>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="f-academic-year">Tahun Ajaran</label>
                    <input type="text" class="form-control" id="f-academic-year" placeholder="Contoh: 2026/2027">
                    <span class="form-error" id="err-academic_year"></span>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="f-started-at">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="f-started-at">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="f-ended-at">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="f-ended-at">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="f-status">Status</label>
                    <select class="form-control" id="f-status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="closed">Closed</option>
                        <option value="archived">Archived</option>
                    </select>
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
let allSemesters = <?= json_encode($semesters, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let editingId = 0;

const STATUS_BADGE = {
    draft:    '<span class="badge badge-muted">Draft</span>',
    active:   '<span class="badge badge-success">🟢 Active</span>',
    closed:   '<span class="badge badge-purple">Closed</span>',
    archived: '<span class="badge badge-muted">Archived</span>',
};

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
}

async function refreshSemesters() {
    const res = await API.get('/api/semesters.php');
    allSemesters = res.data?.semesters ?? [];
    renderTable();
}

function renderTable() {
    if (!allSemesters.length) {
        document.getElementById('semesters-table-wrapper').innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📅</div>
                <div class="empty-state-title">Belum ada semester</div>
            </div>`;
        return;
    }
    const rows = allSemesters.map(s => `
        <tr id="semester-row-${s.id}">
            <td><span class="fw-semibold" style="color:var(--clr-text-primary);">${escHtml(s.name)}</span></td>
            <td class="text-muted">${escHtml(s.academic_year)}</td>
            <td>${STATUS_BADGE[s.status] ?? escHtml(s.status)}</td>
            <td class="text-muted text-sm">${escHtml(s.started_at ?? '—')} → ${escHtml(s.ended_at ?? '—')}</td>
            <td class="text-right">
                <span class="badge badge-muted text-xs">${s.tx_count} transaksi</span>
            </td>
            <td class="text-right">
                <div class="flex gap-2" style="justify-content:flex-end;">
                    ${s.status !== 'active' ? `<button class="btn btn-sm btn-secondary" onclick="activateSemester(${s.id},'${escHtml(s.name).replace(/'/g,"\\'")}')" aria-label="Aktifkan ${escHtml(s.name)}">✅ Aktifkan</button>` : ''}
                    <button class="btn btn-ghost btn-icon-sm" onclick="editSemester(${s.id})" title="Edit" aria-label="Edit ${escHtml(s.name)}">✏️</button>
                    ${s.tx_count == 0 ? `<button class="btn btn-ghost btn-icon-sm text-danger" onclick="deleteSemester(${s.id},'${escHtml(s.name).replace(/'/g,"\\'")}')" title="Hapus" aria-label="Hapus ${escHtml(s.name)}">🗑️</button>` : ''}
                </div>
            </td>
        </tr>`).join('');

    document.getElementById('semesters-table-wrapper').innerHTML = `
        <table class="table" aria-label="Tabel Semester">
            <thead><tr><th scope="col">Nama</th><th scope="col">Tahun Ajaran</th><th scope="col">Status</th><th scope="col">Periode</th><th scope="col" class="text-right">Transaksi</th><th scope="col" class="text-right">Aksi</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
}

function openModal(title) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('semester-modal').style.display = 'flex';
    document.getElementById('form-error').style.display = 'none';
    ['name','academic_year'].forEach(f => { const el=document.getElementById('err-'+f); if(el) el.textContent=''; });
    document.getElementById('f-name').focus();
}

function closeModal() {
    document.getElementById('semester-modal').style.display = 'none';
    document.getElementById('semester-form').reset();
    editingId = 0;
}

document.getElementById('btn-add-semester').addEventListener('click', () => { editingId = 0; openModal('Tambah Semester'); });

function editSemester(id) {
    const s = allSemesters.find(x => x.id == id);
    if (!s) return;
    editingId = id;
    document.getElementById('f-name').value          = s.name;
    document.getElementById('f-academic-year').value = s.academic_year;
    document.getElementById('f-started-at').value    = s.started_at ?? '';
    document.getElementById('f-ended-at').value      = s.ended_at ?? '';
    document.getElementById('f-status').value        = s.status;
    openModal('Edit Semester');
}

async function activateSemester(id, name) {
    if (!confirm(`Aktifkan semester "${name}"? Semester yang sedang aktif saat ini akan ditutup.`)) return;
    try {
        const r = await fetch(`/api/semesters.php?id=${id}&action=activate`, { method: 'POST' });
        const data = await r.json();
        if (data.success) {
            Toast.gold('Semester berhasil diaktifkan! 🎉');
            refreshSemesters();
        } else {
            Toast.error(data.message ?? 'Gagal mengaktifkan semester.');
        }
    } catch (e) {
        Toast.error('Gagal: ' + e.message);
    }
}

async function deleteSemester(id, name) {
    if (!confirm(`Hapus semester "${name}"?`)) return;
    try {
        const r = await fetch('/api/semesters.php?id=' + id, { method: 'DELETE' });
        const data = await r.json();
        if (data.success) {
            Toast.success('Semester dihapus.');
            refreshSemesters();
        } else {
            Toast.error(data.message ?? 'Gagal menghapus semester.');
        }
    } catch (e) {
        Toast.error('Gagal: ' + e.message);
    }
}

document.getElementById('modal-save').addEventListener('click', async () => {
    const payload = {
        name:          document.getElementById('f-name').value.trim(),
        academic_year: document.getElementById('f-academic-year').value.trim(),
        started_at:    document.getElementById('f-started-at').value,
        ended_at:      document.getElementById('f-ended-at').value,
        status:        document.getElementById('f-status').value,
    };
    const btn = document.getElementById('modal-save');
    btn.classList.add('loading'); btn.disabled = true;

    try {
        const url    = editingId ? `/api/semesters.php?id=${editingId}` : '/api/semesters.php';
        const method = editingId ? 'PUT' : 'POST';
        const res    = await fetch(url, { method, headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const data   = await res.json();
        btn.classList.remove('loading'); btn.disabled = false;

        if (!data.success) {
            const el = document.getElementById('form-error');
            el.textContent = data.message || 'Gagal menyimpan semester.';
            el.style.display = 'flex';
            return;
        }
        Toast.success(data.data?.message ?? 'Semester berhasil disimpan.');
        closeModal();
        refreshSemesters();
    } catch (e) {
        btn.classList.remove('loading'); btn.disabled = false;
        Toast.error('Gagal: ' + e.message);
    }
});

document.getElementById('modal-close').addEventListener('click', closeModal);
document.getElementById('modal-cancel').addEventListener('click', closeModal);
document.getElementById('semester-modal').addEventListener('click', e => { if(e.target===document.getElementById('semester-modal')) closeModal(); });
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
