<?php
/**
 * Teacher — Give Stars (Phase 3 Core Gamification Flow)
 * Fast 4-step wizard: Class → Star Count → Student(s) → Instant Award & Audio/Visual FX
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['teacher', 'admin']);

$pageTitle = 'Give Stars';
$activeNav = 'give-stars';
$role      = Auth::role();
$user      = Auth::user();

// Dapatkan data teacher & kelas yang diampu
$teacherId = null;
if ($role === 'teacher') {
    $teacher   = TeacherModel::findByUserId($user['id']);
    $teacherId = $teacher ? (int)$teacher['id'] : 0;
    $myClasses = $teacherId ? TeacherModel::getClasses($teacherId) : [];
    $classIds  = array_unique(array_column($myClasses, 'class_id'));
} else {
    // Admin mode: bisa memberi bintang ke semua kelas
    $allClasses = ClassModel::all();
    $classIds   = array_column($allClasses, 'id');
}

$semester = SemesterModel::active();
$semesterId = $semester ? (int)$semester['id'] : 0;

$settings = Database::query("SELECT `key`, value FROM settings");
$settingsMap = array_column($settings, 'value', 'key');
$minStars  = max(1,  (int)($settingsMap['min_stars_per_award'] ?? 1));
$maxStars  = min(10, (int)($settingsMap['max_stars_per_award'] ?? 5));
$maxOpp    = max(1,  (int)($settingsMap['max_star_opportunity'] ?? 100));

$classes = !empty($classIds) ? Database::query(
    'SELECT c.id, c.name, c.academic_year,
            (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.is_active = 1) AS student_count
     FROM classes c
     WHERE c.id IN (' . implode(',', array_fill(0, count($classIds), '?')) . ') AND c.is_active = 1
     ORDER BY c.name',
    $classIds
) : [];

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>⭐ Give Stars</h1>
        <p>Berikan reward bintang partisipasi secara instan kepada siswa</p>
    </div>
    <?php if ($semester): ?>
    <div class="badge badge-gold" style="font-size:var(--fs-sm); padding:var(--space-2) var(--space-4);">
        📅 <?= htmlspecialchars($semester['name']) ?> (Aktif)
    </div>
    <?php endif; ?>
</div>

<?php if (!$semester): ?>
<div class="alert alert-warning mb-6">
    ⚠️ Tidak ada semester aktif. Hubungi Admin untuk mengaktifkan periode semester terlebih dahulu.
</div>
<?php endif; ?>

<?php if (empty($classes)): ?>
<div class="alert alert-warning">
    ⚠️ Kamu belum ditugaskan ke kelas manapun. Hubungi Admin untuk melakukan assignment kelas.
</div>
<?php else: ?>

<div class="grid grid-3" style="gap:var(--space-6); align-items:flex-start;">

    <!-- Left / Main Wizard Card (2 Columns) -->
    <div class="card" style="grid-column: span 2;">

        <!-- Step Indicator -->
        <div class="flex gap-0 mb-6" style="border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--clr-border);">
            <?php foreach([['1','1. Kelas'],['2','2. Bintang'],['3','3. Siswa'],['4','4. Konfirmasi']] as $i=>[$num,$label]): ?>
            <div id="step-tab-<?= $num ?>" style="flex:1; padding:var(--space-3) var(--space-2); text-align:center; font-size:var(--fs-xs); font-weight:700; background:<?= $i===0?'var(--clr-gold-500)':'var(--clr-bg-tertiary)' ?>; color:<?= $i===0?'#000':'var(--clr-text-muted)' ?>; transition:var(--transition-fast);">
                <?= $label ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- STEP 1: Pilih Kelas -->
        <div id="step-1" class="wizard-step animate-fade-in">
            <h3 class="mb-2">1. Pilih Kelas</h3>
            <p class="text-muted text-sm mb-4">Pilih kelas tempat kamu mengajar saat ini:</p>
            <div class="grid grid-2" style="gap:var(--space-3);">
                <?php foreach ($classes as $c): ?>
                <button type="button" class="class-select-card card-hover" onclick="selectClass(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['name'])) ?>')">
                    <div style="font-size:2rem; margin-bottom:var(--space-2);">🏫</div>
                    <div class="fw-bold" style="font-size:var(--fs-lg); color:var(--clr-text-primary);"><?= htmlspecialchars($c['name']) ?></div>
                    <div class="text-muted text-xs mt-1"><?= htmlspecialchars($c['academic_year']) ?> · <?= (int)$c['student_count'] ?> Siswa</div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- STEP 2: Pilih Jumlah Bintang -->
        <div id="step-2" class="wizard-step animate-fade-in" style="display:none;">
            <div class="flex-between mb-3">
                <h3 style="margin:0;">2. Pilih Jumlah Bintang</h3>
                <span class="badge badge-purple" id="badge-selected-class"></span>
            </div>
            <p class="text-muted text-sm mb-5">Berapa bintang yang ingin kamu berikan?</p>

            <div class="star-selector-grid">
                <?php for ($n = $minStars; $n <= $maxStars; $n++): ?>
                <button type="button" class="star-choice-btn" onclick="selectStars(<?= $n ?>)">
                    <div class="star-choice-stars"><?= str_repeat('⭐', min($n, 5)) ?><?= $n > 5 ? " ×$n" : "" ?></div>
                    <div class="star-choice-label">+<?= $n ?> Bintang</div>
                </button>
                <?php endfor; ?>
            </div>

            <div class="mt-6">
                <button type="button" class="btn btn-ghost btn-sm" onclick="goStep(1)">← Ganti Kelas</button>
            </div>
        </div>

        <!-- STEP 3: Pilih Siswa (Single / Multi-Select Mode) -->
        <div id="step-3" class="wizard-step animate-fade-in" style="display:none;">
            <div class="flex-between mb-3 flex-wrap gap-2">
                <div>
                    <h3 style="margin:0;">3. Pilih Siswa</h3>
                    <div class="text-muted text-xs mt-1">
                        Kelas: <strong id="s3-class" style="color:var(--clr-text-primary);"></strong> &nbsp;·&nbsp;
                        Reward: <strong id="s3-stars" style="color:var(--clr-gold-400);"></strong>
                    </div>
                </div>
                <!-- Mode Switch -->
                <div class="mode-toggle-group">
                    <button type="button" id="btn-mode-single" class="mode-toggle-btn active" onclick="setSelectMode('single')">👤 1 Siswa</button>
                    <button type="button" id="btn-mode-multi" class="mode-toggle-btn" onclick="setSelectMode('multi')">👥 Kelompok / Banyak</button>
                </div>
            </div>

            <!-- Multi-Select Action Bar -->
            <div id="multi-action-bar" class="flex-between mb-3" style="display:none; background:rgba(255,193,7,0.08); padding:var(--space-2) var(--space-4); border-radius:var(--radius-md); border:1px solid rgba(255,193,7,0.2);">
                <div class="text-sm font-semibold" id="multi-selected-count">0 siswa dipilih</div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-ghost btn-xs" onclick="selectAllStudents(true)">Pilih Semua</button>
                    <button type="button" class="btn btn-ghost btn-xs" onclick="selectAllStudents(false)">Batal Semua</button>
                </div>
            </div>

            <!-- Search input -->
            <div class="input-group mb-3">
                <span class="input-icon input-icon-left">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" class="form-control has-icon-left" id="student-search" placeholder="Cari nama atau NIS siswa..." oninput="filterStudents(this.value)">
            </div>

            <!-- Student List Container -->
            <div id="student-list" class="student-select-container">
                <div class="flex-center" style="padding:var(--space-8);"><div class="spinner"></div></div>
            </div>

            <!-- Action buttons for Step 3 -->
            <div class="flex-between mt-4">
                <button type="button" class="btn btn-ghost btn-sm" onclick="goStep(2)">← Ubah Jumlah Bintang</button>
                <button type="button" class="btn btn-primary" id="btn-multi-proceed" style="display:none;" onclick="proceedMultiSelect()">
                    Lanjut ke Konfirmasi →
                </button>
            </div>
        </div>

        <!-- STEP 4: Konfirmasi Award -->
        <div id="step-4" class="wizard-step animate-fade-in" style="display:none;">
            <h3 class="mb-4">4. Konfirmasi Pemberian Bintang</h3>

            <div class="card card-gold text-center mb-5" style="background:linear-gradient(135deg, rgba(255,193,7,0.12) 0%, rgba(255,143,0,0.06) 100%); padding:var(--space-6);">
                <div id="confirm-stars-display" style="font-size:3.5rem; line-height:1; margin-bottom:var(--space-3); animation:float 3s ease-in-out infinite;">⭐</div>
                <div id="confirm-recipient-title" style="font-size:var(--fs-2xl); font-weight:800; color:var(--clr-text-primary);"></div>
                <div id="confirm-class-subtitle" class="text-muted text-sm mt-2"></div>
                <div id="confirm-recipient-list" class="mt-3 text-xs text-muted" style="max-height:100px; overflow-y:auto;"></div>
            </div>

            <!-- Optional Note -->
            <div class="form-group mb-5">
                <label class="form-label" for="award-note">Catatan / Alasan (Opsional)</label>
                <input type="text" class="form-control" id="award-note" placeholder="Contoh: Menjawab kuis, Aktif diskusi, Tugas tepat waktu">
                <div class="flex gap-2 mt-2 flex-wrap">
                    <span class="chip-tag" onclick="setNote('Aktif bertanya & diskusi')">+ Aktif Diskusi</span>
                    <span class="chip-tag" onclick="setNote('Menjawab pertanyaan')">+ Menjawab Kuis</span>
                    <span class="chip-tag" onclick="setNote('Kerja kelompok sangat baik')">+ Kerja Kelompok</span>
                    <span class="chip-tag" onclick="setNote('Presentasi materi')">+ Presentasi</span>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="goStep(3)">← Kembali</button>
                <button type="button" class="btn btn-primary btn-lg" style="flex:2;" id="btn-award" onclick="executeAward()">
                    <span class="btn-text" id="btn-award-text">⭐ Berikan Bintang Sekarang!</span>
                </button>
            </div>
        </div>

        <!-- STEP 5: Success & Loop Screen -->
        <div id="step-5" class="wizard-step animate-fade-in text-center" style="display:none; padding:var(--space-6) var(--space-4);">
            <div style="font-size:4.5rem; margin-bottom:var(--space-2); animation:pulse 1s infinite alternate;">🎉</div>
            <h2 id="success-headline" class="text-gold" style="font-size:var(--fs-3xl); font-weight:900; margin-bottom:var(--space-2);">Bintang Berhasil Diberikan!</h2>
            <p id="success-desc" class="text-muted text-md mb-6"></p>

            <div id="success-detail-cards" class="grid grid-2 mb-6" style="gap:var(--space-3); max-width:540px; margin:0 auto var(--space-6);"></div>

            <div class="flex gap-3 flex-center flex-wrap">
                <button type="button" class="btn btn-primary btn-lg" onclick="quickAwardNext()">
                    ⭐ Beri Bintang Lagi (Kelas Ini)
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetToStep1()">
                    🏫 Ganti Kelas
                </button>
            </div>
        </div>

    </div>

    <!-- Right Column: Recent Awards & 1-Click Undo Panel -->
    <div class="card" style="grid-column: span 1;">
        <div class="flex-between mb-4">
            <h3 style="margin:0; font-size:var(--fs-md);">Riwayat Hari Ini</h3>
            <button type="button" class="btn btn-ghost btn-xs" onclick="loadRecentAwards()">🔄 Refresh</button>
        </div>
        <div id="recent-awards-container" style="display:flex; flex-direction:column; gap:var(--space-3); max-height:480px; overflow-y:auto;">
            <div class="text-muted text-sm text-center" style="padding:var(--space-6);">Memuat riwayat...</div>
        </div>
    </div>

</div>

<style>
.class-select-card {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: var(--space-6) var(--space-4); border-radius: var(--radius-lg);
    background: var(--clr-bg-tertiary); border: 2px solid var(--clr-border);
    cursor: pointer; text-align: center; transition: var(--transition-fast);
}
.class-select-card:hover {
    border-color: var(--clr-gold-500);
    background: rgba(255, 193, 7, 0.06);
    transform: translateY(-2px);
}
.star-selector-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: var(--space-3);
}
.star-choice-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: var(--space-5) var(--space-3); border-radius: var(--radius-lg);
    background: var(--clr-bg-tertiary); border: 2px solid var(--clr-border);
    cursor: pointer; transition: var(--transition-fast);
}
.star-choice-btn:hover, .star-choice-btn.selected {
    border-color: var(--clr-gold-500);
    background: rgba(255, 193, 7, 0.12);
    box-shadow: 0 0 20px rgba(255, 193, 7, 0.25);
    transform: scale(1.03);
}
.star-choice-stars { font-size: 1.6rem; line-height: 1; margin-bottom: var(--space-2); }
.star-choice-label { font-weight: 700; color: var(--clr-text-primary); font-size: var(--fs-sm); }

.mode-toggle-group {
    display: flex; background: var(--clr-bg-tertiary); border: 1px solid var(--clr-border); border-radius: var(--radius-md); padding: 2px;
}
.mode-toggle-btn {
    background: none; border: none; padding: var(--space-1) var(--space-3); font-size: var(--fs-xs); font-weight: 600;
    color: var(--clr-text-muted); border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition-fast);
}
.mode-toggle-btn.active {
    background: var(--clr-gold-500); color: #000;
}
.student-select-container {
    display: flex; flex-direction: column; gap: var(--space-2); max-height: 380px; overflow-y: auto; padding-right: var(--space-1);
}
.student-choice-card {
    display: flex; align-items: center; gap: var(--space-3); padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-md); background: var(--clr-bg-tertiary); border: 1px solid var(--clr-border);
    cursor: pointer; text-align: left; transition: var(--transition-fast); width: 100%;
}
.student-choice-card:hover {
    border-color: var(--clr-gold-500); background: rgba(255, 193, 7, 0.08);
}
.student-choice-card.selected {
    border-color: var(--clr-gold-500); background: rgba(255, 193, 7, 0.15);
}
.chip-tag {
    font-size: var(--fs-xs); padding: 4px 10px; border-radius: var(--radius-full);
    background: var(--clr-bg-tertiary); border: 1px solid var(--clr-border);
    color: var(--clr-text-secondary); cursor: pointer; transition: var(--transition-fast);
}
.chip-tag:hover {
    border-color: var(--clr-gold-400); color: var(--clr-gold-400); background: rgba(255,193,7,0.06);
}
.recent-tx-item {
    display: flex; align-items: center; justify-content: space-between; padding: var(--space-3);
    background: var(--clr-bg-tertiary); border-radius: var(--radius-md); border: 1px solid var(--clr-border);
    transition: var(--transition-fast);
}
.recent-tx-item.reversed {
    opacity: 0.5; text-decoration: line-through;
}
</style>

<script src="/assets/js/star-fx.js"></script>
<script>
const SEMESTER_ID = <?= (int)$semesterId ?>;
let selectedClassId   = 0;
let selectedClassName = '';
let selectedStars     = 0;
let selectMode        = 'single'; // 'single' | 'multi'
let selectedStudentIds = new Set();
let allStudents       = [];
let lastAwardResult   = null;

function goStep(n) {
    [1,2,3,4,5].forEach(i => {
        const el = document.getElementById('step-' + i);
        if (el) el.style.display = (i === n) ? 'block' : 'none';
        const tab = document.getElementById('step-tab-' + i);
        if (tab) {
            tab.style.background = (i <= n) ? 'var(--clr-gold-500)' : 'var(--clr-bg-tertiary)';
            tab.style.color      = (i <= n) ? '#000' : 'var(--clr-text-muted)';
        }
    });
}

function selectClass(id, name) {
    selectedClassId   = id;
    selectedClassName = name;
    document.getElementById('badge-selected-class').textContent = '🏫 ' + name;
    document.getElementById('s3-class').textContent             = name;
    loadStudents();
    goStep(2);
}

function selectStars(n) {
    selectedStars = n;
    document.querySelectorAll('.star-choice-btn').forEach(b => b.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.getElementById('s3-stars').textContent = `⭐ × ${n}`;
    goStep(3);
}

function setSelectMode(mode) {
    selectMode = mode;
    selectedStudentIds.clear();
    document.getElementById('btn-mode-single').classList.toggle('active', mode === 'single');
    document.getElementById('btn-mode-multi').classList.toggle('active', mode === 'multi');
    document.getElementById('multi-action-bar').style.display  = mode === 'multi' ? 'flex' : 'none';
    document.getElementById('btn-multi-proceed').style.display = mode === 'multi' ? 'inline-flex' : 'none';
    renderStudentList(allStudents);
}

async function loadStudents() {
    const el = document.getElementById('student-list');
    el.innerHTML = '<div class="flex-center" style="padding:var(--space-8);"><div class="spinner"></div></div>';
    const res = await API.get(`/api/students.php?class_id=${selectedClassId}&limit=200`);
    allStudents = res.data?.students ?? [];
    selectedStudentIds.clear();
    renderStudentList(allStudents);
}

function filterStudents(q) {
    const query = q.toLowerCase().trim();
    const filtered = query ? allStudents.filter(s => s.name.toLowerCase().includes(query) || s.nis.includes(query)) : allStudents;
    renderStudentList(filtered);
}

function renderStudentList(students) {
    const el = document.getElementById('student-list');
    if (!students.length) {
        el.innerHTML = '<div class="text-muted text-sm text-center" style="padding:var(--space-6);">Tidak ada siswa ditemukan di kelas ini.</div>';
        return;
    }

    el.innerHTML = students.map(s => {
        const isSel = selectedStudentIds.has(s.id);
        if (selectMode === 'single') {
            return `
            <button type="button" class="student-choice-card" onclick="selectSingleStudent(${s.id})">
                <div class="avatar avatar-sm">${(s.name||'S')[0].toUpperCase()}</div>
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--clr-text-primary);">${escHtml(s.name)}</div>
                    <div style="font-size:var(--fs-xs); color:var(--clr-text-muted);">${escHtml(s.nis)}</div>
                </div>
                <div style="color:var(--clr-gold-400); font-weight:700; font-size:var(--fs-sm);">⭐ ${Number(s.total_stars).toLocaleString()}</div>
            </button>`;
        } else {
            return `
            <div class="student-choice-card ${isSel ? 'selected' : ''}" onclick="toggleStudentMulti(${s.id})">
                <input type="checkbox" ${isSel ? 'checked' : ''} style="width:18px; height:18px; accent-color:var(--clr-gold-500);" onclick="event.stopPropagation(); toggleStudentMulti(${s.id});">
                <div class="avatar avatar-sm">${(s.name||'S')[0].toUpperCase()}</div>
                <div style="flex:1;">
                    <div style="font-weight:700; color:var(--clr-text-primary);">${escHtml(s.name)}</div>
                    <div style="font-size:var(--fs-xs); color:var(--clr-text-muted);">${escHtml(s.nis)}</div>
                </div>
                <div style="color:var(--clr-gold-400); font-weight:700; font-size:var(--fs-sm);">⭐ ${Number(s.total_stars).toLocaleString()}</div>
            </div>`;
        }
    }).join('');

    updateMultiCount();
}

function selectSingleStudent(studentId) {
    selectedStudentIds.clear();
    selectedStudentIds.add(studentId);
    prepareConfirmation();
    goStep(4);
}

function toggleStudentMulti(studentId) {
    if (selectedStudentIds.has(studentId)) {
        selectedStudentIds.delete(studentId);
    } else {
        selectedStudentIds.add(studentId);
    }
    renderStudentList(allStudents);
}

function selectAllStudents(select) {
    if (select) {
        allStudents.forEach(s => selectedStudentIds.add(s.id));
    } else {
        selectedStudentIds.clear();
    }
    renderStudentList(allStudents);
}

function updateMultiCount() {
    const countEl = document.getElementById('multi-selected-count');
    if (countEl) countEl.textContent = `${selectedStudentIds.size} siswa dipilih`;
    const proceedBtn = document.getElementById('btn-multi-proceed');
    if (proceedBtn) proceedBtn.disabled = (selectedStudentIds.size === 0);
}

function proceedMultiSelect() {
    if (selectedStudentIds.size === 0) {
        Toast.error('Pilih minimal 1 siswa.');
        return;
    }
    prepareConfirmation();
    goStep(4);
}

function prepareConfirmation() {
    const selectedList = allStudents.filter(s => selectedStudentIds.has(s.id));
    document.getElementById('confirm-stars-display').innerHTML = '⭐'.repeat(Math.min(selectedStars, 5)) + (selectedStars > 5 ? ` ×${selectedStars}` : '');

    if (selectedList.length === 1) {
        const s = selectedList[0];
        document.getElementById('confirm-recipient-title').textContent = s.name;
        document.getElementById('confirm-class-subtitle').textContent = `${selectedClassName} · NIS: ${s.nis} · Saat ini: ⭐ ${s.total_stars}`;
        document.getElementById('confirm-recipient-list').innerHTML   = '';
        document.getElementById('btn-award-text').textContent         = `⭐ Berikan +${selectedStars} Bintang`;
    } else {
        document.getElementById('confirm-recipient-title').textContent = `${selectedList.length} Siswa (${selectedClassName})`;
        document.getElementById('confirm-class-subtitle').textContent = `Masing-masing akan menerima +${selectedStars} bintang`;
        document.getElementById('confirm-recipient-list').innerHTML   = selectedList.map(s => `<span class="badge badge-muted" style="margin:2px;">${escHtml(s.name)}</span>`).join('');
        document.getElementById('btn-award-text').textContent         = `⭐ Berikan +${selectedStars} Bintang ke ${selectedList.length} Siswa`;
    }
}

function setNote(text) {
    document.getElementById('award-note').value = text;
}

// Execute Star Award
async function executeAward() {
    if (!SEMESTER_ID) { Toast.error('Tidak ada semester aktif.'); return; }
    const btn = document.getElementById('btn-award');
    btn.classList.add('loading'); btn.disabled = true;

    const payload = {
        student_ids: Array.from(selectedStudentIds),
        class_id:    selectedClassId,
        semester_id: SEMESTER_ID,
        stars:       selectedStars,
        note:        document.getElementById('award-note').value.trim()
    };

    try {
        const res  = await fetch('/api/stars.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();
        btn.classList.remove('loading'); btn.disabled = false;

        if (!data.success) {
            Toast.error(data.message || 'Gagal memberikan bintang.');
            return;
        }

        // Gamification FX: Audio Chime + Confetti Particles!
        StarFX.playChime(selectedStars);
        StarFX.burst(window.innerWidth / 2, window.innerHeight / 2, 45);

        Toast.gold(`⭐ ${selectedStars} Bintang berhasil diberikan!`);

        // Render Step 5 (Celebration Screen)
        lastAwardResult = data.data;
        showSuccessScreen(data.data);
        loadRecentAwards();
    } catch (e) {
        btn.classList.remove('loading'); btn.disabled = false;
        Toast.error('Gagal: ' + e.message);
    }
}

function showSuccessScreen(data) {
    const count = data.count || 1;
    document.getElementById('success-headline').textContent = `+${data.stars} Bintang Diberikan!`;
    document.getElementById('success-desc').textContent     = data.message;

    const cardsEl = document.getElementById('success-detail-cards');
    if (data.results && data.results.length > 0) {
        cardsEl.innerHTML = data.results.slice(0, 4).map(r => `
            <div class="card card-sm text-left" style="background:var(--clr-bg-tertiary); border:1px solid rgba(255,193,7,0.2);">
                <div class="fw-bold" style="color:var(--clr-text-primary); font-size:var(--fs-sm);">${escHtml(r.student_name)}</div>
                <div class="flex-between mt-1">
                    <span class="text-gold fw-bold">⭐ ${Number(r.new_total).toLocaleString()}</span>
                    ${r.level ? `<span class="badge badge-gold text-xs">Level ${r.level.level_number}: ${escHtml(r.level.predicate)}</span>` : ''}
                </div>
            </div>
        `).join('');
    }

    goStep(5);
}

function quickAwardNext() {
    // Keep class selected, reset students & notes, go to Step 2 (or 3)
    selectedStudentIds.clear();
    document.getElementById('award-note').value = '';
    loadStudents();
    goStep(2);
}

function resetToStep1() {
    selectedClassId = 0; selectedClassName = ''; selectedStars = 0;
    selectedStudentIds.clear();
    document.getElementById('award-note').value = '';
    goStep(1);
}

// Recent Awards & 1-Click Undo
async function loadRecentAwards() {
    const container = document.getElementById('recent-awards-container');
    try {
        const res  = await API.get('/api/stars.php?action=recent');
        const list = res.data?.transactions ?? [];

        if (!list.length) {
            container.innerHTML = '<div class="text-muted text-xs text-center" style="padding:var(--space-6);">Belum ada bintang yang diberikan hari ini.</div>';
            return;
        }

        container.innerHTML = list.map(tx => {
            const isReversal = (tx.type === 'reversal');
            const isAlreadyReversed = (tx.is_reversed > 0);
            return `
            <div class="recent-tx-item ${isAlreadyReversed ? 'reversed' : ''}">
                <div>
                    <div class="fw-semibold" style="color:var(--clr-text-primary); font-size:var(--fs-sm);">
                        ${escHtml(tx.student_name)}
                    </div>
                    <div class="text-muted text-xs">
                        ${escHtml(tx.class_name)} · ${formatTime(tx.awarded_at)}
                    </div>
                </div>
                <div class="flex gap-2" style="align-items:center;">
                    <span class="text-gold fw-bold text-sm">
                        ${tx.type === 'reversal' ? '-' : '+'}${tx.stars}⭐
                    </span>
                    ${!isReversal && !isAlreadyReversed ? `
                    <button type="button" class="btn btn-ghost btn-xs text-danger" onclick="undoTransaction(${tx.id}, '${escHtml(tx.student_name).replace(/'/g,"\\'")}', ${tx.stars})" title="Batalkan transaksi ini">
                        ↩️ Undo
                    </button>` : ''}
                </div>
            </div>`;
        }).join('');
    } catch (e) {
        container.innerHTML = '<div class="text-muted text-xs text-center">Gagal memuat riwayat.</div>';
    }
}

async function undoTransaction(txId, studentName, stars) {
    if (!confirm(`Batalkan pemberian ${stars}⭐ untuk ${studentName}?`)) return;

    try {
        const res = await fetch('/api/stars.php?action=reversal', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ tx_id: txId, note: 'Dibatalkan oleh guru' })
        });
        const data = await res.json();
        if (data.success) {
            StarFX.playUndo();
            Toast.info(data.message || 'Pemberian bintang dibatalkan.');
            loadRecentAwards();
            if (selectedClassId) loadStudents();
        } else {
            Toast.error(data.message || 'Gagal membatalkan transaksi.');
        }
    } catch (e) {
        Toast.error('Gagal: ' + e.message);
    }
}

function formatTime(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
}

// Initial load
loadRecentAwards();
</script>
<?php endif; ?>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
