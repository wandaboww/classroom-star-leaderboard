<?php
/**
 * Admin — Settings
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'System Settings';
$activeNav = 'settings';
$role      = 'admin';

$settings = Database::query("SELECT * FROM settings ORDER BY `key`");
$settingsMap = array_column($settings, 'value', 'key');

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed = ['app_name','max_stars_per_award','min_stars_per_award','max_star_opportunity','leaderboard_public'];
    foreach ($allowed as $key) {
        if (isset($_POST[$key])) {
            Database::execute("UPDATE settings SET value = ? WHERE `key` = ?", [trim($_POST[$key]), $key]);
        }
    }
    $saved = true;
    $settings    = Database::query("SELECT * FROM settings ORDER BY `key`");
    $settingsMap = array_column($settings, 'value', 'key');
}

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header">
    <h1>System Settings</h1>
    <p>Konfigurasi aplikasi Classroom Star</p>
</div>

<?php if ($saved): ?>
<div class="alert alert-success mb-6">✅ Pengaturan berhasil disimpan.</div>
<?php endif; ?>

<form method="POST" action="">
    <div class="card mb-6">
        <h3 class="mb-6">Pengaturan Umum</h3>
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label" for="app_name">Nama Aplikasi</label>
                <input type="text" class="form-control" id="app_name" name="app_name"
                       value="<?= htmlspecialchars($settingsMap['app_name'] ?? 'Classroom Star') ?>">
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <h3 class="mb-6">Konfigurasi Bintang</h3>
        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label" for="min_stars">Min Bintang per Pemberian</label>
                <input type="number" class="form-control" id="min_stars" name="min_stars_per_award"
                       value="<?= (int)($settingsMap['min_stars_per_award'] ?? 1) ?>" min="1" max="5">
                <span class="form-hint">Default: 1</span>
            </div>
            <div class="form-group">
                <label class="form-label" for="max_stars">Max Bintang per Pemberian</label>
                <input type="number" class="form-control" id="max_stars" name="max_stars_per_award"
                       value="<?= (int)($settingsMap['max_stars_per_award'] ?? 5) ?>" min="1" max="10">
                <span class="form-hint">Default: 5</span>
            </div>
            <div class="form-group">
                <label class="form-label" for="max_opp">Maksimum Peluang Bintang / Semester</label>
                <input type="number" class="form-control" id="max_opp" name="max_star_opportunity"
                       value="<?= (int)($settingsMap['max_star_opportunity'] ?? 100) ?>" min="1">
                <span class="form-hint">Digunakan untuk menghitung persentase level</span>
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <h3 class="mb-4">Leaderboard</h3>
        <div class="form-group">
            <label style="display:flex; align-items:center; gap:var(--space-3); cursor:pointer;">
                <input type="checkbox" name="leaderboard_public" value="1"
                       <?= ($settingsMap['leaderboard_public'] ?? '1') === '1' ? 'checked' : '' ?>
                       style="width:18px; height:18px; accent-color:var(--clr-gold-500);">
                <div>
                    <div class="fw-semibold" style="color:var(--clr-text-primary);">Leaderboard Publik untuk Siswa</div>
                    <div class="text-xs text-muted">Jika aktif, siswa bisa melihat ranking semua siswa di kelasnya</div>
                </div>
            </label>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">💾 Simpan Pengaturan</button>
    </div>
</form>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
