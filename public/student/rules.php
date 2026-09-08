<?php
/**
 * Student — Cara Kerja Bintang (Tampilan Panduan Dinamis)
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['student']);

$pageTitle = 'Cara Kerja Bintang';
$activeNav = 'rules';
$role      = 'student';
$user      = Auth::user();

// Ambil data panduan yang diinputkan oleh guru
$setting = Database::queryOne("SELECT value, updated_at FROM settings WHERE `key` = 'star_rules'");
$rulesText = $setting['value'] ?? '';
$lastUpdated = $setting['updated_at'] ?? null;

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>📖 Cara Kerja Bintang</h1>
        <p>Panduan dan aturan perolehan bintang partisipasi kelas</p>
    </div>
    <div class="flex gap-3">
        <a href="/student/leaderboard.php" class="btn btn-primary">
            🏆 Ke Leaderboard
        </a>
    </div>
</div>

<div class="card" style="background: rgba(18, 22, 34, 0.9); border: 1.5px solid rgba(255, 215, 0, 0.3); border-radius: 20px; padding: var(--space-6);">
    <div class="flex items-center justify-between pb-4 mb-5" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
        <div class="flex items-center gap-3">
            <span style="font-size: 2rem;">⭐</span>
            <div>
                <h2 style="font-size: 1.2rem; font-weight: 800; color: #ffd700; margin: 0;">Aturan & Kriteria Bintang</h2>
                <span class="text-xs text-muted">Panduan resmi yang ditentukan oleh guru pengajar</span>
            </div>
        </div>
        <?php if ($lastUpdated): ?>
        <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 500; text-transform: none; letter-spacing: normal; color: #93c5fd; background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.3); padding: 3px 10px; border-radius: 9999px; margin-left: auto; flex-shrink: 0; white-space: nowrap;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.85;">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Diperbarui: <?= date('d M Y', strtotime($lastUpdated)) ?>
        </span>
        <?php endif; ?>
    </div>

    <?php if (empty(trim($rulesText))): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📖</div>
            <div class="empty-state-title">Belum ada panduan</div>
            <div class="empty-state-desc">Guru belum mengunggah penjelasan cara kerja bintang.</div>
        </div>
    <?php else: ?>
        <div class="rules-body" style="line-height: 1.85; font-size: 1rem; color: #f1f5f9; white-space: pre-wrap; word-break: break-word;">
<?= htmlspecialchars($rulesText) ?>
        </div>
    <?php endif; ?>

    <div class="mt-6 pt-4 flex justify-between items-center flex-wrap gap-4" style="border-top: 1px solid rgba(255, 255, 255, 0.08);">
        <span class="text-xs text-muted">💡 Kumpulkan bintang sebanyak mungkin untuk meningkatkan predikat levelmu!</span>
        <a href="/student/dashboard.php" class="btn btn-secondary btn-sm">
            📊 Lihat Progres Saya
        </a>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
