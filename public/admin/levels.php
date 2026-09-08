<?php
/**
 * Admin — Level Configuration
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['admin']);

$pageTitle = 'Level Configuration';
$activeNav = 'levels';
$role      = 'admin';

$levels = Database::query('SELECT * FROM levels ORDER BY level_number');

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>Level & Kategori</h1>
        <p>Konfigurasi threshold level dan nilai tambah partisipasi</p>
    </div>
</div>

<div class="alert alert-info mb-6">
    ℹ️ Level ditentukan berdasarkan persentase: <strong>(Total Bintang Siswa / Maksimum Peluang Bintang) × 100%</strong>.
    Konfigurasi Maksimum Peluang Bintang di halaman <a href="/admin/settings.php">Settings</a>.
</div>

<div class="card">
    <div class="flex-between mb-6">
        <h3 style="margin:0;">Tabel Level</h3>
        <button class="btn btn-primary btn-sm" id="btn-save-all">💾 Simpan Perubahan</button>
    </div>

    <div id="levels-table-wrapper">
    <div class="table-wrapper">
        <table class="table" id="levels-table">
            <thead>
                <tr>
                    <th style="width:80px;">Level</th>
                    <th>Predikat</th>
                    <th style="width:140px;">Min %</th>
                    <th style="width:140px;">Max %</th>
                    <th style="width:140px;">Nilai Tambah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($levels as $lv): ?>
                <tr data-id="<?= $lv['id'] ?>">
                    <td>
                        <span class="badge badge-gold fw-bold">Level <?= $lv['level_number'] ?></span>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="predicate" value="<?= htmlspecialchars($lv['predicate']) ?>">
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" class="form-control" name="min_percent" value="<?= $lv['min_percent'] ?>" min="0" max="100" step="0.01">
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="max_percent" value="<?= $lv['max_percent'] ?>" min="0" max="100" step="0.01">
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" class="form-control" name="additional_score" value="<?= $lv['additional_score'] ?>" min="0" max="100">
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>

    <div id="save-result" class="mt-4" style="display:none;"></div>
</div>

<script>
document.getElementById('btn-save-all').addEventListener('click', async () => {
    const rows = document.querySelectorAll('#levels-table tbody tr');
    const updates = [];
    rows.forEach(row => {
        updates.push({
            id:               parseInt(row.dataset.id),
            predicate:        row.querySelector('[name=predicate]').value.trim(),
            min_percent:      parseFloat(row.querySelector('[name=min_percent]').value),
            max_percent:      parseFloat(row.querySelector('[name=max_percent]').value),
            additional_score: parseInt(row.querySelector('[name=additional_score]').value),
        });
    });

    const btn = document.getElementById('btn-save-all');
    btn.classList.add('loading'); btn.disabled = true;

    const res  = await fetch('/api/levels.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ levels: updates }) });
    const data = await res.json();
    btn.classList.remove('loading'); btn.disabled = false;

    const result = document.getElementById('save-result');
    result.style.display = 'flex';
    if (data.success) {
        result.className = 'alert alert-success mt-4';
        result.textContent = '✅ Konfigurasi level berhasil disimpan.';
        Toast.success('Level berhasil diperbarui.');
    } else {
        result.className = 'alert alert-error mt-4';
        result.textContent = data.message ?? 'Gagal menyimpan.';
    }
});
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
