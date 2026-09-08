<?php
/**
 * Student — Leaderboard
 */
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole(['student']);

$pageTitle = 'Leaderboard';
$activeNav = 'leaderboard';
$role      = 'student';

$user    = Auth::user();
$student = Database::queryOne('SELECT s.*, c.name AS class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?', [$user['id']]);

$leaderboardPublic = (Database::queryOne("SELECT value FROM settings WHERE `key` = 'leaderboard_public'")['value'] ?? '1') === '1';

$leaderboard = [];
if ($student && ($leaderboardPublic || true)) {
    $leaderboard = Database::query(
        "SELECT s.id, s.name, s.nis, c.name AS class_name,
                COALESCE(SUM(st.stars), 0) AS total_stars
         FROM students s
         JOIN classes c ON s.class_id = c.id
         LEFT JOIN star_transactions st ON st.student_id = s.id AND st.type = 'award'
         WHERE s.class_id = ? AND s.is_active = 1
         GROUP BY s.id
         ORDER BY total_stars DESC",
        [$student['class_id']]
    );
}

$myId = $student['id'] ?? 0;
$myRank = null;
$myStars = 0;
foreach ($leaderboard as $i => $s) {
    if ($s['id'] == $myId) {
        $myRank = $i + 1;
        $myStars = (int)$s['total_stars'];
        break;
    }
}

include dirname(__DIR__, 2) . '/app/views/layout/header.php';
?>

<div class="page-header page-header-row">
    <div>
        <h1>🏆 Leaderboard</h1>
        <p>Ranking kelas <?= htmlspecialchars($student['class_name'] ?? '') ?></p>
    </div>
    <?php if ($student): ?>
    <div class="flex gap-3 flex-wrap">
        <a href="/student/rules.php" class="btn btn-secondary" id="btn-star-rules">
            📖 Cara kerja bintang
        </a>
        <button class="btn btn-primary" id="btn-my-rank" type="button">
            Rank saya
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (!$leaderboardPublic): ?>
        <div class="empty-state"><div class="empty-state-icon">🔒</div><div class="empty-state-title">Leaderboard tidak publik</div><div class="empty-state-desc">Guru atau admin belum mengaktifkan leaderboard untuk siswa.</div></div>
    <?php elseif (empty($leaderboard)): ?>
        <div class="empty-state"><div class="empty-state-icon">🏆</div><div class="empty-state-title">Belum ada data</div></div>
    <?php else: ?>

    <!-- Top 3 Podium (Rank 1 Kiri, Rank 2 Tengah, Rank 3 Kanan) -->
    <?php $top3 = array_slice($leaderboard, 0, 3); ?>
    <div class="podium-container">
        <?php
        $podiumOrder = [0, 1, 2]; // rank 1 (kiri), rank 2 (tengah), rank 3 (kanan)
        foreach ($podiumOrder as $idx):
            if (!isset($top3[$idx])) continue;
            $s = $top3[$idx];
            $rankNum = $idx + 1;
            $rankClass = ['rank-1st', 'rank-2nd', 'rank-3rd'][$idx];
            $medals = ['🥇', '🥈', '🥉'];
            $rankTitles = ['Rank 1', 'Rank 2', 'Rank 3'];
            $avatarClass = match($idx) {
                0 => 'avatar-gold',
                1 => 'avatar-silver',
                default => 'avatar-bronze',
            };
        ?>
        <div class="podium-card podium-rank-<?= $rankNum ?> animate-fade-in" style="animation-delay: <?= $idx * 0.1 ?>s;">
            <div class="podium-rank-pill"><?= $medals[$idx] ?> <?= $rankTitles[$idx] ?></div>
            <div class="avatar avatar-xl <?= $avatarClass ?>" style="margin:0 auto var(--space-3);">
                <?= strtoupper(substr($s['name'], 0, 1)) ?>
            </div>
            <div class="fw-bold" style="color:var(--clr-text-primary); font-size:var(--fs-md); margin-bottom: 2px;"><?= htmlspecialchars($s['name']) ?></div>
            <div class="text-muted text-xs mb-3"><?= htmlspecialchars($s['class_name']) ?></div>
            <div class="podium-medal <?= $rankClass ?> medal-rank-<?= $rankNum ?>"><?= $medals[$idx] ?></div>
            <div class="text-gold fw-bold" style="font-size:var(--fs-lg);">⭐ <?= number_format($s['total_stars']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th class="text-center" style="width:60px;">Rank</th><th>Siswa</th><th class="text-right">Total ⭐</th></tr></thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $s): $isMe = ($s['id'] == $myId); ?>
                <tr <?= $isMe ? 'id="my-rank-row" style="background:rgba(255,193,7,0.07); border-left:3px solid var(--clr-gold-400); transition: all 0.3s ease;"' : '' ?>>
                    <td class="text-center">
                        <div class="rank-badge <?= match(true){ $i===0=>'rank-badge-1',$i===1=>'rank-badge-2',$i===2=>'rank-badge-3',default=>'rank-badge-n' } ?>" style="margin:auto;"><?= $i+1 ?></div>
                    </td>
                    <td>
                        <div class="student-cell">
                            <div class="avatar avatar-sm <?= $isMe?'avatar-gold':($i<3?'avatar-gold':'') ?>"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                            <div>
                                <div class="student-cell-name"><?= htmlspecialchars($s['name']) ?><?= $isMe ? ' <span class="badge badge-gold text-xs">Kamu</span>' : '' ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-right fw-bold <?= $i<3?'text-gold':'' ?>" style="font-size:var(--fs-md);">⭐ <?= number_format($s['total_stars']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnMyRank = document.getElementById('btn-my-rank');
    if (btnMyRank) {
        btnMyRank.addEventListener('click', () => {
            const targetRow = document.getElementById('my-rank-row');
            if (targetRow) {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetRow.classList.remove('my-rank-highlight');
                void targetRow.offsetWidth; // trigger reflow for smooth re-triggering
                targetRow.classList.add('my-rank-highlight');

                <?php if ($myRank !== null): ?>
                if (typeof Toast !== 'undefined' && Toast.gold) {
                    Toast.gold('Kamu saat ini berada di Rank #<?= $myRank ?> (⭐ <?= number_format($myStars) ?> bintang)');
                }
                <?php endif; ?>
            } else {
                if (typeof Toast !== 'undefined' && Toast.info) {
                    Toast.info('Data peringkat kamu belum tersedia di tabel.');
                }
            }
        });
    }
});
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layout/footer.php'; ?>
