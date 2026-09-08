<?php
/**
 * Teacher — Cara Kerja Bintang (Kelola Panduan Dinamis)
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['teacher', 'admin']);

$pageTitle = 'Cara Kerja Bintang';
$activeNav = 'rules';
$role      = Auth::role();
$user      = Auth::user();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rulesText = trim($_POST['star_rules'] ?? '');
    if (empty($rulesText)) {
        $error = 'Panduan cara kerja bintang tidak boleh kosong.';
    } else {
        $exists = Database::queryOne("SELECT `key` FROM settings WHERE `key` = 'star_rules'");
        if ($exists) {
            Database::execute("UPDATE settings SET value = ?, updated_at = NOW() WHERE `key` = 'star_rules'", [$rulesText]);
        } else {
            Database::execute("INSERT INTO settings (`key`, `value`, `label`, `updated_at`) VALUES ('star_rules', ?, 'Panduan Cara Kerja Bintang', NOW())", [$rulesText]);
        }
        $success = 'Panduan cara kerja bintang berhasil disimpan! Perubahan langsung diterapkan pada tampilan siswa.';
    }
}

// Ambil data panduan saat ini
$setting = Database::queryOne("SELECT value, updated_at FROM settings WHERE `key` = 'star_rules'");
$currentRules = $setting['value'] ?? '';
$lastUpdated  = $setting['updated_at'] ?? null;

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>📖 Cara Kerja Bintang</h1>
        <p>Kelola panduan dan aturan perolehan bintang untuk murid</p>
    </div>
    <div class="flex gap-3 flex-wrap">
        <a href="/teacher/rules-preview.php" class="btn btn-secondary">
            👁️ Pratinjau Tampilan Siswa
        </a>
        <a href="/teacher/leaderboard.php" class="btn btn-secondary">
            🏆 Ke Leaderboard
        </a>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success mb-6" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <span>✅ <?= htmlspecialchars($success) ?></span>
    <a href="/teacher/rules-preview.php" class="btn btn-sm btn-primary" style="background: #16a34a; border-color: #22c55e;">
        👁️ Lihat Pratinjau Siswa →
    </a>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger mb-6">
    <span>⚠️ <?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="flex justify-between items-center flex-wrap gap-2 mb-4 pb-3" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex items-center gap-2">
            <span style="font-size: 1.2rem;">✏️</span>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #fff; margin: 0;">Editor Panduan Bintang</h3>
        </div>
        <?php if ($lastUpdated): ?>
        <span class="text-xs text-muted" style="display: inline-flex; align-items: center; gap: 4px;">
            🕒 Terakhir disimpan: <?= date('d M Y, H:i', strtotime($lastUpdated)) ?>
        </span>
        <?php endif; ?>
    </div>

    <p class="text-sm text-muted mb-4">
        Tuliskan kriteria perolehan bintang, tugas, keaktifan, atau kebijakan penilaian kelas Anda. Teks ini akan langsung tampil di menu <strong>Cara Kerja Bintang</strong> pada akun seluruh murid.
    </p>

    <!-- Quick Insert Toolbar -->
    <div class="mb-3">
        <span class="text-xs text-muted" style="display: block; margin-bottom: 6px;">⚡ Sisipkan Template Cepat:</span>
        <div class="flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-secondary" onclick="insertTemplate('keaktifan')">+ Keaktifan</button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="insertTemplate('tugas')">+ Tugas Tepat Waktu</button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="insertTemplate('sikap')">+ Sikap & Disiplin</button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="insertTemplate('level')">+ Predikat Level</button>
        </div>
    </div>

    <form method="POST" action="">
        <div class="form-group mb-4">
            <textarea 
                name="star_rules" 
                id="star-rules-input" 
                class="form-control" 
                rows="14" 
                style="font-family: inherit; line-height: 1.7; resize: vertical; min-height: 320px; font-size: 0.95rem;"
                placeholder="Tuliskan aturan atau cara kerja bintang di sini..."
                required
            ><?= htmlspecialchars($currentRules) ?></textarea>
        </div>

        <div class="flex justify-between items-center flex-wrap gap-4 pt-2" style="border-top: 1px solid rgba(255, 255, 255, 0.08);">
            <div class="flex items-center gap-3">
                <span class="text-xs text-muted" id="char-count">0 karakter</span>
                <span class="text-xs text-muted">|</span>
                <a href="/teacher/rules-preview.php" class="text-xs text-muted hover-underline" style="color: #60a5fa;">
                    👁️ Buka Pratinjau Siswa
                </a>
            </div>
            <div class="flex gap-2">
                <a href="/teacher/rules-preview.php" class="btn btn-secondary">
                    👁️ Pratinjau
                </a>
                <button type="submit" class="btn btn-primary" id="btn-save-rules">
                    💾 Simpan Panduan
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const textarea = document.getElementById('star-rules-input');
const charCount = document.getElementById('char-count');

function updateCharCount() {
    charCount.textContent = textarea.value.length + ' karakter';
}

textarea.addEventListener('input', updateCharCount);
updateCharCount();

function insertTemplate(type) {
    let snippet = '';
    switch(type) {
        case 'keaktifan':
            snippet = "\n- Keaktifan di Kelas (+1 s/d +3 ⭐): Aktif bertanya, menjawab pertanyaan guru, atau berdiskusi.";
            break;
        case 'tugas':
            snippet = "\n- Ketepatan Tugas (+2 s/d +4 ⭐): Menyelesaikan dan mengumpulkan tugas tepat waktu dengan rapi.";
            break;
        case 'sikap':
            snippet = "\n- Sikap & Teladan (+1 s/d +5 ⭐): Menunjukkan kedisiplinan, sopan santun, dan saling membantu teman.";
            break;
        case 'level':
            snippet = "\n- Predikat Level: Akumulasi bintang menentukan predikat (Very Low s/d Outstanding) sebagai nilai bonus rapor.";
            break;
    }
    textarea.value += snippet;
    textarea.focus();
    updateCharCount();
}
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
