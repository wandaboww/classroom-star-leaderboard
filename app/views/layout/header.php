<?php
/**
 * Shared Layout — Header (PHP include)
 * Usage: include dirname(__DIR__) . '/../app/views/layout/header.php';
 *
 * Required variables before include:
 *   $pageTitle    string  — halaman title
 *   $activeNav    string  — nav item yang aktif (e.g. 'dashboard', 'students')
 *   $role         string  — 'admin' | 'teacher' | 'student' (auto-detected jika kosong)
 */

$appConfig = require dirname(__DIR__) . '/../../app/config/app.php';
$appName   = $appConfig['name'];
$user      = Auth::user();
$role      = $role ?? $user['role'] ?? 'student';
$pageTitle = $pageTitle ?? 'Dashboard';
$activeNav = $activeNav ?? 'dashboard';

// Nav items per role
$navItems = [];

if ($role === 'admin') {
    $navItems = [
        ['id' => 'dashboard', 'label' => 'Dashboard',      'icon' => 'grid',     'href' => '/admin/dashboard.php'],
        ['id' => 'users',     'label' => 'Users',           'icon' => 'users',    'href' => '/admin/users.php'],
        ['id' => 'teachers',  'label' => 'Teachers',        'icon' => 'user-check','href' => '/admin/teachers.php'],
        ['id' => 'students',  'label' => 'Students',        'icon' => 'graduation-cap','href' => '/admin/students.php'],
        ['id' => 'classes',   'label' => 'Classes',         'icon' => 'door-open','href' => '/admin/classes.php'],
        ['id' => 'semesters', 'label' => 'Semesters',       'icon' => 'calendar', 'href' => '/admin/semesters.php'],
        ['section' => 'Star System'],
        ['id' => 'leaderboard','label' => 'Leaderboard',   'icon' => 'trophy',   'href' => '/admin/leaderboard.php'],
        ['id' => 'report',    'label' => 'Report Star Activity', 'icon' => 'bar-chart','href' => '/admin/report.php'],
        ['id' => 'levels',    'label' => 'Level Config',   'icon' => 'sliders',  'href' => '/admin/levels.php'],
        ['section' => 'System'],
        ['id' => 'settings',  'label' => 'Settings',       'icon' => 'settings', 'href' => '/admin/settings.php'],
    ];
} elseif ($role === 'teacher') {
    $navItems = [
        ['id' => 'dashboard',  'label' => 'Dashboard',     'icon' => 'grid',    'href' => '/teacher/dashboard.php'],
        ['id' => 'give-stars', 'label' => 'Give Stars',    'icon' => 'star',    'href' => '/teacher/give-stars.php'],
        ['section' => 'Data'],
        ['id' => 'students',   'label' => 'My Students',   'icon' => 'users',   'href' => '/teacher/students.php'],
        ['id' => 'leaderboard','label' => 'Leaderboard',   'icon' => 'trophy',  'href' => '/teacher/leaderboard.php'],
        ['id' => 'report',     'label' => 'Report Star Activity', 'icon' => 'bar-chart','href' => '/teacher/report.php'],
        ['id' => 'progress',   'label' => 'Student Progress','icon' => 'trending-up','href' => '/teacher/progress.php'],
        ['section' => 'Panduan'],
        ['id' => 'rules',         'label' => 'Cara kerja bintang',      'icon' => 'book-open', 'href' => '/teacher/rules.php'],
        ['id' => 'rules-preview', 'label' => 'Pratinjau Tampilan Siswa','icon' => 'eye',       'href' => '/teacher/rules-preview.php'],
    ];
} else {
    $navItems = [
        ['id' => 'dashboard',  'label' => 'Dashboard',     'icon' => 'grid',    'href' => '/student/dashboard.php'],
        ['id' => 'leaderboard','label' => 'Leaderboard',   'icon' => 'trophy',  'href' => '/student/leaderboard.php'],
        ['id' => 'report',     'label' => 'Report Star Activity', 'icon' => 'bar-chart','href' => '/student/report.php'],
        ['section' => 'Panduan'],
        ['id' => 'rules',      'label' => 'Cara kerja bintang', 'icon' => 'book-open', 'href' => '/student/rules.php'],
    ];
}

// SVG icons helper
if (!function_exists('navIcon')) {
    function navIcon(string $name): string {
        $icons = [
            'grid'           => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
            'users'          => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user-check'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>',
            'graduation-cap' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
            'door-open'      => '<path d="M13 4h3a2 2 0 0 1 2 2v14"/><path d="M2 20h3"/><path d="M13 20h9"/><path d="M10 12v.01"/><path d="M13 4.562v16.157a1 1 0 0 1-1.279.961L5 19V5.562a2 2 0 0 1 1.279-1.87l6-2a2 2 0 0 1 1.721.24"/><circle cx="10" cy="12" r=".5" fill="currentColor"/>',
            'calendar'       => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
            'star'           => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'trophy'         => '<polyline points="8 5.5 8 1 16 1 16 5.5"/><path d="M4 9v1a5 5 0 0 0 10 0V9"/><path d="M8 19h8l1 4H7l1-4z"/><path d="M4 9H2V6h4"/><path d="M20 9h2V6h-4"/><line x1="8" y1="19" x2="8" y2="14"/><line x1="16" y1="19" x2="16" y2="14"/>',
            'bar-chart'      => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
            'sliders'        => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
            'book-open'      => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
            'eye'            => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
            'settings'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'trending-up'    => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
            'log-out'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        ];
        $d = $icons[$name] ?? '<circle cx="12" cy="12" r="5"/>';
        return '<svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
    }
}

// Role badge label
$roleBadge = match($role) {
    'admin'   => ['label' => 'Admin',   'class' => 'role-admin'],
    'teacher' => ['label' => 'Teacher', 'class' => 'role-teacher'],
    default   => ['label' => 'Student', 'class' => 'role-student'],
};
$initials = strtoupper(substr($user['name'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/components.css">
</head>
<body>
<div class="app-shell">

    <!-- Sidebar overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">

        <!-- Brand -->
        <a href="/<?= $role ?>/dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">⭐</div>
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">Classroom Star</span>
                <span class="sidebar-brand-tagline">Participation Leaderboard</span>
            </div>
        </a>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item): ?>
                <?php if (isset($item['section'])): ?>
                    <div class="nav-section-label"><?= htmlspecialchars($item['section']) ?></div>
                <?php else: ?>
                    <a
                        href="<?= htmlspecialchars($item['href']) ?>"
                        class="nav-item <?= ($activeNav === $item['id']) ? 'active' : '' ?>"
                        id="nav-<?= htmlspecialchars($item['id']) ?>"
                    >
                        <?= navIcon($item['icon']) ?>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- User Footer -->
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar avatar-sm avatar-<?= $role === 'admin' ? 'gold' : 'purple' ?>">
                    <?= $initials ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                    <div class="sidebar-user-role">
                        <span class="badge badge-<?= htmlspecialchars($roleBadge['class']) ?>"><?= $roleBadge['label'] ?></span>
                    </div>
                </div>
                <a href="/logout.php" title="Logout" style="color:var(--clr-text-muted);display:flex;">
                    <?= navIcon('log-out') ?>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Top Header -->
        <header class="top-header">
            <button class="header-menu-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div>
                <div class="header-title"><?= htmlspecialchars($pageTitle) ?></div>
            </div>
            <div class="header-actions">
                <!-- Tambahkan header actions jika diperlukan -->
            </div>
        </header>

        <!-- Page Content starts here -->
        <main class="page-content">
