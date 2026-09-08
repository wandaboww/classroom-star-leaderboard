<?php
/**
 * Admin — Users Management
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Users Management';
$activeNav = 'users';
$role      = 'admin';

// SSR: Ambil data awal langsung dari DB
$users = UserModel::all(500);
$totalUsers = count($users);

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Users</h1>
        <p>Kelola semua akun pengguna sistem</p>
    </div>
    <button class="btn btn-primary" id="btn-add-user" aria-haspopup="dialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah User
    </button>
</div>

<!-- Search & Filter Bar -->
<div class="card card-sm mb-4">
    <div class="flex gap-3 flex-wrap" style="align-items:center;">
        <div class="input-group" style="flex:1; min-width:200px;">
            <span class="input-icon input-icon-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" class="form-control has-icon-left" id="search-input" placeholder="Cari nama, username, email..." aria-label="Cari pengguna">
        </div>
        <select class="form-control" id="filter-role" style="width:auto; min-width:140px;" aria-label="Filter role">
            <option value="">Semua Role</option>
            <option value="admin">Admin</option>
            <option value="teacher">Teacher</option>
            <option value="student">Student</option>
        </select>
        <div id="users-count" class="text-muted text-sm"><?= $totalUsers ?> user</div>
    </div>
</div>

<!-- Users Table (SSR Initial Render) -->
<div class="card" id="users-card">
    <div class="table-wrapper" id="users-table-wrapper">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <div class="empty-state-title">Tidak ada user ditemukan</div>
            </div>
        <?php else: ?>
            <table class="table" id="users-table" aria-label="Tabel Pengguna">
                <thead>
                    <tr>
                        <th scope="col">Nama / Username</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <?php foreach ($users as $u):
                        $rClass = match($u['role']) { 'admin'=>'role-admin', 'teacher'=>'role-teacher', 'student'=>'role-student', default=>'badge-muted' };
                        $isCurrent = ($u['id'] == Auth::user()['id']);
                    ?>
                    <tr id="user-row-<?= $u['id'] ?>">
                        <td>
                            <div class="student-cell">
                                <div class="avatar avatar-sm <?= $u['role']==='admin'?'avatar-gold':'avatar-purple' ?>"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                                <div>
                                    <div class="student-cell-name"><?= htmlspecialchars($u['name']) ?></div>
                                    <div class="student-cell-nis">@<?= htmlspecialchars($u['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted text-sm"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge <?= $rClass ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td>
                            <span class="badge <?= $u['is_active']==1?'badge-success':'badge-muted' ?>">
                                <?= $u['is_active']==1?'Aktif':'Nonaktif' ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="flex gap-2" style="justify-content:flex-end;">
                                <button class="btn btn-ghost btn-sm" onclick="editUser(<?= $u['id'] ?>)" title="Edit" aria-label="Edit <?= htmlspecialchars($u['name']) ?>">
                                    ✏️
                                </button>
                                <?php if (!$isCurrent): ?>
                                <button class="btn btn-ghost btn-sm text-danger" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')" title="Hapus" aria-label="Hapus <?= htmlspecialchars($u['name']) ?>">
                                    🗑️
                                </button>
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

<!-- Modal: Add/Edit User -->
<div class="modal-backdrop" id="user-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Tambah User</h3>
            <button class="modal-close" id="modal-close" aria-label="Tutup modal">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="form-error" class="alert alert-error" style="display:none;"></div>
            <form id="user-form" novalidate>
                <input type="hidden" id="user-id" value="">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="f-name">Nama Lengkap</label>
                        <input type="text" class="form-control" id="f-name" placeholder="Contoh: Budi Santoso">
                        <span class="form-error" id="err-name"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="f-role">Role</label>
                        <select class="form-control" id="f-role">
                            <option value="">-- Pilih Role --</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                        <span class="form-error" id="err-role"></span>
                    </div>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label form-label-required" for="f-username">Username</label>
                        <input type="text" class="form-control" id="f-username" placeholder="Contoh: budi.santoso">
                        <span class="form-error" id="err-username"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label-required" for="f-email">Email</label>
                        <input type="email" class="form-control" id="f-email" placeholder="budi@sekolah.com">
                        <span class="form-error" id="err-email"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" id="f-pass-label" for="f-password">
                        Password <span id="pass-hint" style="color:var(--clr-text-muted); font-weight:400;">(Kosongkan jika tidak ingin mengubah)</span>
                    </label>
                    <div class="input-group password-wrapper">
                        <input type="password" class="form-control" id="f-password" placeholder="Min. 8 karakter">
                        <button type="button" class="password-toggle" id="toggle-modal-pass" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <span class="form-error" id="err-password"></span>
                </div>
                <div class="form-group" id="nip-group" style="display:none;">
                    <label class="form-label" for="f-nip">NIP (Opsional)</label>
                    <input type="text" class="form-control" id="f-nip" placeholder="Nomor Induk Pegawai">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
                        <input type="checkbox" id="f-active" checked style="width:16px; height:16px; accent-color: var(--clr-gold-500);">
                        <span class="text-sm">Aktif</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="modal-cancel">Batal</button>
            <button class="btn btn-primary" id="modal-save">
                <span class="btn-text">Simpan</span>
            </button>
        </div>
    </div>
</div>

<style>
.password-wrapper { position: relative; }
.password-toggle {
    position: absolute; right: var(--space-3); top: 50%; transform: translateY(-50%);
    background: none; border: none; color: var(--clr-text-muted); cursor: pointer;
    padding: 4px; display: flex; border-radius: var(--radius-sm); transition: var(--transition-fast);
}
.password-toggle:hover { color: var(--clr-text-primary); }
#f-password { padding-right: 2.5rem; }
</style>

<script>
const CURRENT_USER_ID = <?= Auth::user()['id'] ?>;
let allUsers = <?= json_encode($users, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let editingId = 0;

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
}

function renderTable(users) {
    const roleFilter = document.getElementById('filter-role').value;
    const search     = document.getElementById('search-input').value.toLowerCase().trim();

    let filtered = users.filter(u => {
        const matchRole   = !roleFilter || u.role === roleFilter;
        const matchSearch = !search || (u.name && u.name.toLowerCase().includes(search)) ||
                            (u.username && u.username.toLowerCase().includes(search)) ||
                            (u.email && u.email.toLowerCase().includes(search));
        return matchRole && matchSearch;
    });

    document.getElementById('users-count').textContent = `${filtered.length} user`;

    if (!filtered.length) {
        document.getElementById('users-table-wrapper').innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <div class="empty-state-title">Tidak ada user ditemukan</div>
            </div>`;
        return;
    }

    const roleBadge = r => {
        const map = { admin: 'role-admin', teacher: 'role-teacher', student: 'role-student' };
        return `<span class="badge ${map[r] ?? 'badge-muted'}">${escHtml(r)}</span>`;
    };

    const rows = filtered.map(u => `
        <tr id="user-row-${u.id}">
            <td>
                <div class="student-cell">
                    <div class="avatar avatar-sm ${u.role === 'admin' ? 'avatar-gold' : 'avatar-purple'}">${escHtml((u.name||'U')[0].toUpperCase())}</div>
                    <div>
                        <div class="student-cell-name">${escHtml(u.name)}</div>
                        <div class="student-cell-nis">@${escHtml(u.username)}</div>
                    </div>
                </div>
            </td>
            <td class="text-muted text-sm">${escHtml(u.email)}</td>
            <td>${roleBadge(u.role)}</td>
            <td>
                <span class="badge ${u.is_active == 1 ? 'badge-success' : 'badge-muted'}">
                    ${u.is_active == 1 ? 'Aktif' : 'Nonaktif'}
                </span>
            </td>
            <td class="text-right">
                <div class="flex gap-2" style="justify-content:flex-end;">
                    <button class="btn btn-ghost btn-sm" onclick="editUser(${u.id})" title="Edit" aria-label="Edit ${escHtml(u.name)}">
                        ✏️
                    </button>
                    ${u.id !== CURRENT_USER_ID ? `
                    <button class="btn btn-ghost btn-sm text-danger" onclick="deleteUser(${u.id}, '${escHtml(u.name).replace(/'/g,"\\'")}')" title="Hapus" aria-label="Hapus ${escHtml(u.name)}">
                        🗑️
                    </button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');

    document.getElementById('users-table-wrapper').innerHTML = `
        <table class="table" id="users-table" aria-label="Tabel Pengguna">
            <thead>
                <tr>
                    <th scope="col">Nama / Username</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    `;
}

// Filter listeners (Instant Client-side)
document.getElementById('search-input').addEventListener('input', () => renderTable(allUsers));
document.getElementById('filter-role').addEventListener('change', () => renderTable(allUsers));

// Role select -> show NIP if teacher
document.getElementById('f-role').addEventListener('change', (e) => {
    document.getElementById('nip-group').style.display = e.target.value === 'teacher' ? 'block' : 'none';
});

// Modal open/close
function openModal(title, isEdit = false) {
    editingId = isEdit ? editingId : 0;
    document.getElementById('modal-title').textContent = title;
    document.getElementById('form-error').style.display = 'none';
    clearErrors();
    document.getElementById('pass-hint').style.display = isEdit ? 'inline' : 'none';
    document.getElementById('user-modal').style.display = 'flex';
    document.getElementById('f-name').focus();
}

function closeModal() {
    document.getElementById('user-modal').style.display = 'none';
    document.getElementById('user-form').reset();
    editingId = 0;
}

document.getElementById('btn-add-user').addEventListener('click', () => {
    document.getElementById('user-form').reset();
    document.getElementById('f-active').checked = true;
    document.getElementById('nip-group').style.display = 'none';
    openModal('Tambah User');
});
document.getElementById('modal-close').addEventListener('click', closeModal);
document.getElementById('modal-cancel').addEventListener('click', closeModal);

// Password toggle
document.getElementById('toggle-modal-pass').addEventListener('click', () => {
    const inp = document.getElementById('f-password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
});

function editUser(id) {
    const u = allUsers.find(x => x.id == id);
    if (!u) return;
    editingId = id;
    document.getElementById('f-name').value     = u.name;
    document.getElementById('f-role').value     = u.role;
    document.getElementById('f-username').value = u.username;
    document.getElementById('f-email').value    = u.email;
    document.getElementById('f-password').value = '';
    document.getElementById('f-active').checked = (u.is_active == 1);
    document.getElementById('nip-group').style.display = u.role === 'teacher' ? 'block' : 'none';
    openModal('Edit User', true);
}

function clearErrors() {
    ['name','role','username','email','password'].forEach(f => {
        const el = document.getElementById('err-' + f);
        if (el) el.textContent = '';
    });
}

// Save User (AJAX Progressive Enhancement)
document.getElementById('modal-save').addEventListener('click', async () => {
    clearErrors();
    const btn = document.getElementById('modal-save');
    btn.classList.add('loading'); btn.disabled = true;

    const payload = {
        name:      document.getElementById('f-name').value.trim(),
        role:      document.getElementById('f-role').value,
        username:  document.getElementById('f-username').value.trim(),
        email:     document.getElementById('f-email').value.trim(),
        password:  document.getElementById('f-password').value,
        is_active: document.getElementById('f-active').checked ? 1 : 0,
    };
    if (payload.role === 'teacher') {
        payload.nip = document.getElementById('f-nip').value.trim();
    }

    try {
        const url    = editingId ? `/api/users.php?id=${editingId}` : '/api/users.php';
        const method = editingId ? 'PUT' : 'POST';
        const res    = await fetch(url, { method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data   = await res.json();

        btn.classList.remove('loading'); btn.disabled = false;

        if (!data.success) {
            if (data.errors) {
                Object.entries(data.errors).forEach(([k, v]) => {
                    const el = document.getElementById('err-' + k);
                    if (el) el.textContent = v;
                });
            } else {
                const errDiv = document.getElementById('form-error');
                errDiv.textContent = data.message || 'Terjadi kesalahan.';
                errDiv.style.display = 'block';
            }
            return;
        }

        Toast.success(data.message || (editingId ? 'User berhasil diperbarui' : 'User berhasil dibuat'));
        closeModal();

        // Refresh dataset silently
        const updated = await API.get('/api/users.php');
        allUsers = updated.data?.users ?? [];
        renderTable(allUsers);
    } catch (e) {
        btn.classList.remove('loading'); btn.disabled = false;
        Toast.error('Gagal menyimpan user: ' + e.message);
    }
});

// Delete User
async function deleteUser(id, name) {
    if (!confirm(`Yakin ingin menghapus user "${name}"?\nData profil guru/siswa terkait juga akan terhapus.`)) return;

    try {
        const res  = await fetch(`/api/users.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (!data.success) {
            Toast.error(data.message || 'Gagal menghapus user.');
            return;
        }
        Toast.success('User berhasil dihapus.');
        allUsers = allUsers.filter(u => u.id != id);
        renderTable(allUsers);
    } catch (e) {
        Toast.error('Gagal menghapus user: ' + e.message);
    }
}
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
