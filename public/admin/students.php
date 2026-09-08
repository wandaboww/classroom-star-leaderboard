<?php
/**
 * Admin — Students Management
 * List siswa dengan filter kelas, search, pagination, add/edit/delete
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin', 'teacher']);

$pageTitle = 'Students Management';
$activeNav = 'students';
$role = Auth::role();

// SSR: Ambil data awal siswa & kelas
$classes = ClassModel::forDropdown();
$initData = StudentModel::paginate(1, 50, 0, '');
$students = $initData['students'] ?? [];
$total = $initData['total'] ?? 0;
$currPage = $initData['page'] ?? 1;
$totalPages = ceil($total / 50);

// Export XLSX Handler
if (isset($_GET['export']) && $_GET['export'] == '1') {
    $classFilter = (int) ($_GET['class_id'] ?? 0);
    $students    = StudentModel::all($classFilter, '', 9999, 0);
    $spreadsheet = XlsxHelper::generateStudentExport($students);

    $className = '';
    if ($classFilter > 0) {
        $cls = ClassModel::find($classFilter);
        if ($cls) {
            $className = '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cls['name']);
        }
    }
    $filename = 'data_siswa' . $className . '_' . date('Ymd_His') . '.xlsx';
    XlsxHelper::download($spreadsheet, $filename);
}

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Siswa</h1>
        <p>Kelola daftar siswa per kelas</p>
    </div>
    <div class="flex gap-3 flex-wrap">
        <a href="/admin/students.php?export=1" download="data_siswa.xlsx" class="btn btn-secondary" id="btn-export" title="Export XLSX">
            📥 Export XLSX
        </a>
        <button class="btn btn-secondary" id="btn-import">
            📤 Import XLSX
        </button>
        <button class="btn btn-secondary" id="btn-generate-accounts" title="Buat akun login untuk siswa yang belum memiliki akun">
            ⚡ Buat Akun Siswa
        </button>
        <button class="btn btn-primary" id="btn-add-student">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Tambah Siswa
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card card-sm mb-4">
    <div class="flex gap-3 flex-wrap" style="align-items:center;">
        <div class="input-group" style="flex:1; min-width:200px;">
            <span class="input-icon input-icon-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </span>
            <input type="text" class="form-control has-icon-left" id="search-input" placeholder="Cari nama atau NIS..."
                aria-label="Cari siswa">
        </div>
        <select class="form-control" id="filter-class" style="width:auto; min-width:180px;" aria-label="Filter kelas">
            <option value="0">Semua Kelas</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?>
                    (<?= htmlspecialchars($c['academic_year']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <div id="students-count" class="text-muted text-sm"><?= $total ?> siswa</div>
    </div>
</div>

<!-- Students Table (SSR Initial Render) -->
<div class="card">
    <div class="table-wrapper" id="students-table-wrapper">
        <?php if (empty($students)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎓</div>
                <div class="empty-state-title">Tidak ada siswa ditemukan</div>
            </div>
        <?php else: ?>
            <table class="table" aria-label="Tabel Siswa">
                <thead>
                    <tr>
                        <th scope="col">NIS</th>
                        <th scope="col">Nama Siswa</th>
                        <th scope="col">Kelas</th>
                        <th scope="col" class="text-right">Total ⭐</th>
                        <th scope="col" class="text-right">Aksi</th>
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
                            <td class="text-right"><span class="star-count text-gold">⭐
                                    <?= number_format($s['total_stars']) ?></span></td>
                            <td class="text-right">
                                <div class="flex gap-2" style="justify-content:flex-end;">
                                    <button class="btn btn-ghost btn-icon-sm"
                                        onclick="editStudent(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nis'])) ?>', '<?= htmlspecialchars(addslashes($s['name'])) ?>', <?= (int) $s['class_id'] ?>)"
                                        title="Edit" aria-label="Edit <?= htmlspecialchars($s['name']) ?>">✏️</button>
                                    <button class="btn btn-ghost btn-icon-sm text-danger"
                                        onclick="deleteStudent(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')"
                                        title="Hapus" aria-label="Hapus <?= htmlspecialchars($s['name']) ?>">🗑️</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div id="pagination-wrapper" class="flex-between"
        style="padding: var(--space-4) var(--space-6); border-top:1px solid var(--clr-border); <?= $totalPages <= 1 ? 'display:none;' : '' ?>">
        <div class="text-sm text-muted" id="pagination-info">Halaman <?= $currPage ?> dari <?= max(1, $totalPages) ?>
            (<?= $total ?> siswa)</div>
        <div class="pagination" id="pagination-btns">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <button class="pagination-btn <?= $p === $currPage ? 'active' : '' ?>"
                    onclick="goPage(<?= $p ?>)"><?= $p ?></button>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Student -->
<div class="modal-backdrop" id="student-modal" style="display:none;" role="dialog" aria-modal="true"
    aria-labelledby="modal-title">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Tambah Siswa</h3>
            <button class="modal-close" id="modal-close" aria-label="Tutup modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="form-error" class="alert alert-error" style="display:none;"></div>
            <form id="student-form">
                <input type="hidden" id="student-id">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="f-nis">NIS</label>
                        <input type="text" class="form-control" id="f-nis" placeholder="Nomor Induk Siswa">
                        <span class="form-error" id="err-nis"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="f-class">Kelas</label>
                        <select class="form-control" id="f-class">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-error" id="err-class_id"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label form-label-required" for="f-name">Nama Lengkap</label>
                    <input type="text" class="form-control" id="f-name" placeholder="Nama siswa">
                    <span class="form-error" id="err-name"></span>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="modal-cancel">Batal</button>
            <button class="btn btn-primary" id="modal-save"><span class="btn-text">Simpan</span></button>
        </div>
    </div>
</div>

<!-- Modal: Import XLSX -->
<div class="modal-backdrop" id="import-modal" style="display:none;" role="dialog" aria-modal="true"
    aria-labelledby="import-modal-title">
    <div class="modal" style="max-width:560px;">
        <div class="modal-header">
            <h3 class="modal-title" id="import-modal-title">Import Data Siswa (.xlsx)</h3>
            <button class="modal-close" id="import-modal-close" aria-label="Tutup modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info mb-4" style="display: block; line-height: 1.6;">
                <div style="font-weight: 600; margin-bottom: 6px; font-size: 0.95rem; color: #93c5fd;">📋 Petunjuk Import Data Siswa:</div>
                <div style="margin-bottom: 4px; color: var(--clr-text-secondary);">1. Format file harus <strong>.xlsx</strong> (Microsoft Excel OpenXML).</div>
                <div style="margin-bottom: 4px; color: var(--clr-text-secondary);">2. Kolom wajib: <strong>NIS</strong>, <strong>NAMA LENGKAP SISWA</strong>, dan <strong>KELAS</strong>.</div>
                <div style="margin-bottom: 10px; color: var(--clr-text-secondary);">3. Kelas pada file Excel akan otomatis disesuaikan dengan kelas di aplikasi.</div>
                <div style="margin-top: 10px;">
                    <a href="/download_template.php" download="template_import_siswa.xlsx"
                        class="btn btn-secondary btn-sm"
                        style="display:inline-flex; align-items:center; gap:6px; font-weight:600; background:rgba(255,215,0,0.18); border-color:rgba(255,215,0,0.4); color:var(--clr-gold-400);">
                        ⬇ Download Template Excel (.xlsx)
                    </a>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label form-label-required" for="import-file">Pilih File Excel (.xlsx)</label>
                <input type="file" class="form-control" id="import-file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                <span class="text-muted text-xs" style="margin-top:4px; display:block;">Pilih file .xlsx yang telah diisi sesuai format template di atas.</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="import-modal-cancel">Batal</button>
            <button class="btn btn-primary" id="btn-do-import" disabled><span class="btn-text">📤 Import Sekarang</span></button>
        </div>
    </div>
</div>

<script>
    let currentPage = <?= (int) $currPage ?>;
    let totalStudents = <?= (int) $total ?>;
    let editingId = 0;
    const LIMIT = 50;

    function escHtml(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
    }

    function getFilters() {
        return {
            class_id: document.getElementById('filter-class').value,
            search: document.getElementById('search-input').value.trim(),
            page: currentPage,
        };
    }

    async function loadStudents() {
        const f = getFilters();
        const url = `/api/students.php?class_id=${f.class_id}&search=${encodeURIComponent(f.search)}&page=${f.page}`;
        const res = await API.get(url);
        const { students = [], total = 0, page = 1 } = res.data ?? {};
        totalStudents = total;
        currentPage = page;
        renderTable(students, total);
        renderPagination(total, page);
    }

    function renderTable(students, total) {
        document.getElementById('students-count').textContent = `${total} siswa`;

        if (!students.length) {
            document.getElementById('students-table-wrapper').innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🎓</div>
                <div class="empty-state-title">Tidak ada siswa ditemukan</div>
            </div>`;
            document.getElementById('pagination-wrapper').style.display = 'none';
            return;
        }

        const rows = students.map(s => `
        <tr id="student-row-${s.id}">
            <td><span class="badge badge-muted text-xs">${escHtml(s.nis)}</span></td>
            <td>
                <div class="student-cell">
                    <div class="avatar avatar-sm">${escHtml((s.name || 'S')[0].toUpperCase())}</div>
                    <span class="student-cell-name">${escHtml(s.name)}</span>
                </div>
            </td>
            <td><span class="badge badge-blue">${escHtml(s.class_name)}</span></td>
            <td class="text-right"><span class="star-count text-gold">⭐ ${Number(s.total_stars).toLocaleString()}</span></td>
            <td class="text-right">
                <div class="flex gap-2" style="justify-content:flex-end;">
                    <button class="btn btn-ghost btn-icon-sm" onclick="editStudent(${s.id},'${escHtml(s.nis)}','${escHtml(s.name).replace(/'/g, "\\'")}',${s.class_id})" title="Edit" aria-label="Edit ${escHtml(s.name)}">✏️</button>
                    <button class="btn btn-ghost btn-icon-sm text-danger" onclick="deleteStudent(${s.id},'${escHtml(s.name).replace(/'/g, "\\'")}')" title="Hapus" aria-label="Hapus ${escHtml(s.name)}">🗑️</button>
                </div>
            </td>
        </tr>`).join('');

        document.getElementById('students-table-wrapper').innerHTML = `
        <table class="table" aria-label="Tabel Siswa">
            <thead><tr><th scope="col">NIS</th><th scope="col">Nama Siswa</th><th scope="col">Kelas</th><th scope="col" class="text-right">Total ⭐</th><th scope="col" class="text-right">Aksi</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>`;
    }

    function renderPagination(total, page) {
        const totalPages = Math.ceil(total / LIMIT);
        const wrapper = document.getElementById('pagination-wrapper');
        if (totalPages <= 1) { wrapper.style.display = 'none'; return; }
        wrapper.style.display = 'flex';
        document.getElementById('pagination-info').textContent = `Halaman ${page} dari ${totalPages} (${total} siswa)`;

        let btns = '';
        for (let i = 1; i <= totalPages; i++) {
            btns += `<button class="pagination-btn ${i === page ? 'active' : ''}" onclick="goPage(${i})">${i}</button>`;
        }
        document.getElementById('pagination-btns').innerHTML = btns;
    }

    function goPage(p) { currentPage = p; loadStudents(); }

    // Modal Add/Edit
    function openModal(title) {
        document.getElementById('modal-title').textContent = title;
        document.getElementById('student-modal').style.display = 'flex';
        document.getElementById('form-error').style.display = 'none';
        ['nis', 'name', 'class_id'].forEach(f => { const el = document.getElementById('err-' + f); if (el) el.textContent = ''; });
        document.getElementById('f-nis').focus();
    }

    function closeModal() {
        document.getElementById('student-modal').style.display = 'none';
        document.getElementById('student-form').reset();
        editingId = 0;
    }

    document.getElementById('btn-add-student').addEventListener('click', () => { editingId = 0; openModal('Tambah Siswa'); });

    function editStudent(id, nis, name, classId) {
        editingId = id;
        document.getElementById('f-nis').value = nis;
        document.getElementById('f-name').value = name;
        document.getElementById('f-class').value = classId;
        openModal('Edit Siswa');
    }

    async function deleteStudent(id, name) {
        if (!confirm(`Hapus/nonaktifkan siswa "${name}"?`)) return;
        try {
            const r = await fetch('/api/students.php?id=' + id, { method: 'DELETE' });
            const data = await r.json();
            if (data.success) {
                Toast.success(data.data?.message ?? 'Siswa dihapus.');
                loadStudents();
            } else Toast.error(data.message ?? 'Gagal menghapus siswa.');
        } catch (e) {
            Toast.error('Gagal: ' + e.message);
        }
    }

    document.getElementById('modal-save').addEventListener('click', async () => {
        const payload = {
            nis: document.getElementById('f-nis').value.trim(),
            name: document.getElementById('f-name').value.trim(),
            class_id: parseInt(document.getElementById('f-class').value)
        };
        const btn = document.getElementById('modal-save');
        btn.classList.add('loading'); btn.disabled = true;
        try {
            const url = editingId ? `/api/students.php?id=${editingId}` : '/api/students.php';
            const method = editingId ? 'PUT' : 'POST';
            const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await res.json();
            btn.classList.remove('loading'); btn.disabled = false;
            if (!data.success) {
                if (data.errors) Object.entries(data.errors).forEach(([k, v]) => { const el = document.getElementById('err-' + k); if (el) el.textContent = v; });
                else { const el = document.getElementById('form-error'); el.textContent = data.message; el.style.display = 'flex'; }
                return;
            }
            Toast.success(data.data?.message ?? 'Data siswa berhasil disimpan.');
            closeModal();
            loadStudents();
        } catch (e) {
            btn.classList.remove('loading'); btn.disabled = false;
            Toast.error('Gagal: ' + e.message);
        }
    });

    // Import Modal
    document.getElementById('btn-import').addEventListener('click', () => { document.getElementById('import-modal').style.display = 'flex'; });
    document.getElementById('import-modal-close').addEventListener('click', () => { document.getElementById('import-modal').style.display = 'none'; });
    document.getElementById('import-modal-cancel').addEventListener('click', () => { document.getElementById('import-modal').style.display = 'none'; });

    document.getElementById('import-file').addEventListener('change', function () {
        document.getElementById('btn-do-import').disabled = !this.files.length;
    });

    document.getElementById('btn-do-import').addEventListener('click', async () => {
        const fileInput = document.getElementById('import-file');
        const file = fileInput.files[0];
        if (!file) { Toast.error('Pilih file XLSX terlebih dahulu.'); return; }

        if (!file.name.toLowerCase().endsWith('.xlsx')) {
            Toast.error('Hanya file berekstensi .xlsx yang diperbolehkan.');
            return;
        }

        const fd = new FormData();
        fd.append('file', file);
        const btn = document.getElementById('btn-do-import');
        btn.classList.add('loading'); btn.disabled = true;
        try {
            const res = await fetch('/api/import.php', { method: 'POST', body: fd });
            const data = await res.json();
            btn.classList.remove('loading'); btn.disabled = false;
            if (!data.success) {
                Toast.error(data.message ?? 'Import gagal.');
                return;
            }
            const r = data.data;
            let msg = `Import selesai: ${r.inserted} ditambahkan, ${r.updated || 0} diperbarui`;
            if (r.skipped > 0) {
                msg += `, ${r.skipped} dilewati`;
            }
            Toast.gold(msg);
            document.getElementById('import-modal').style.display = 'none';
            fileInput.value = '';
            document.getElementById('btn-do-import').disabled = true;
            loadStudents();
        } catch (e) {
            btn.classList.remove('loading'); btn.disabled = false;
            Toast.error('Gagal import: ' + e.message);
        }
    });

    // Generate Accounts Button
    const btnGenAccounts = document.getElementById('btn-generate-accounts');
    if (btnGenAccounts) {
        btnGenAccounts.addEventListener('click', async () => {
            if (!confirm('Buat akun login untuk semua siswa yang belum memiliki akun? (Password default: pplg123)')) return;
            btnGenAccounts.disabled = true;
            btnGenAccounts.classList.add('loading');
            try {
                const res = await fetch('/api/students.php?action=generate_accounts', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ default_password: 'pplg123' })
                });
                const data = await res.json();
                btnGenAccounts.disabled = false;
                btnGenAccounts.classList.remove('loading');
                if (data.success) {
                    Toast.gold(data.data.message || 'Akun siswa berhasil dibuat.');
                    loadStudents();
                } else {
                    Toast.error(data.message || 'Gagal membuat akun siswa.');
                }
            } catch (e) {
                btnGenAccounts.disabled = false;
                btnGenAccounts.classList.remove('loading');
                Toast.error('Terjadi kesalahan: ' + e.message);
            }
        });
    }

    // Filter & search debounce
    let debounceTimer;
    document.getElementById('search-input').addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { currentPage = 1; loadStudents(); }, 350);
    });
    document.getElementById('filter-class').addEventListener('change', (e) => {
        const classId = e.target.value;
        const btnExport = document.getElementById('btn-export');
        if (btnExport) {
            btnExport.href = classId > 0 ? `/admin/students.php?export=1&class_id=${classId}` : '/admin/students.php?export=1';
        }
        currentPage = 1;
        loadStudents();
    });

    // Close modal handlers
    document.getElementById('modal-close').addEventListener('click', closeModal);
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    document.getElementById('student-modal').addEventListener('click', e => { if (e.target === document.getElementById('student-modal')) closeModal(); });
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>