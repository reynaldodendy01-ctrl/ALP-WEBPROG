<?php
// _partials/layout.php — Shared sidebar layout component
// Usage: include __DIR__ . '/../_partials/layout.php';
// Set $pageTitle and $activeMenu before including.

if (!defined('ROOT')) {
    // Calculate root relative to this partial (2 levels up from _partials/)
    define('ROOT', rtrim(dirname(dirname(__FILE__)), '/\\'));
}

// Determine base path relative to ROOT folder
$scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
$rootDir = realpath(ROOT);
if ($scriptDir === $rootDir) {
    $base = './';
} else {
    $base = '../';
}

if (session_status() === PHP_SESSION_NONE) session_start();

// Enforce staff session authentication
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ' . $base . 'login.php');
    exit;
}

// Enforce role-based folder access control
if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    $adminOnlyFolders = ['lokasi', 'dispensers', 'reporters', 'staff', 'assignments'];
    $currentPath = $_SERVER['SCRIPT_NAME'];
    $isBlocked = false;
    foreach ($adminOnlyFolders as $folder) {
        if (strpos($currentPath, "/$folder/") !== false) {
            $isBlocked = true;
            break;
        }
    }
    if ($isBlocked) {
        set_flash('error', 'Akses ditolak: Halaman ini hanya dapat diakses oleh Super Admin.');
        header('Location: ' . $base . 'dashboard/index.php');
        exit;
    }
}

$pageTitle  = $pageTitle  ?? 'CariGalon';
$activeMenu = $activeMenu ?? '';

$menuItems = [
    ['key' => 'dashboard',   'href' => $base . 'dashboard/index.php',  'icon' => 'dashboard',      'label' => 'Dashboard'],
    ['key' => 'lokasi',      'href' => $base . 'lokasi/index.php',     'icon' => 'map',            'label' => 'Lokasi'],
    ['key' => 'dispensers',  'href' => $base . 'dispensers/index.php', 'icon' => 'water_drop',     'label' => 'Dispensers'],
    ['key' => 'reporters',   'href' => $base . 'reporters/index.php',  'icon' => 'person',         'label' => 'Reporters'],
    ['key' => 'laporan',     'href' => $base . 'laporan/index.php',    'icon' => 'report',         'label' => 'Laporan Masalah'],
    ['key' => 'staff',       'href' => $base . 'staff/index.php',      'icon' => 'engineering',    'label' => 'Staff'],
    ['key' => 'assignments', 'href' => $base . 'assignments/index.php','icon' => 'assignment_turned_in', 'label' => 'Assignments'],
    ['key' => 'refill',      'href' => $base . 'refill/index.php',     'icon' => 'recycling',      'label' => 'Refill Log'],
];

if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    $menuItems = array_filter($menuItems, function($item) {
        return in_array($item['key'], ['dashboard', 'laporan', 'refill']);
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — CariGalon Dashboard</title>
    <meta name="description" content="Dashboard manajemen logistik galon air minum Universitas Ciputra.">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:   '#0058bc',
                        'primary-dark': '#003f8a',
                        'primary-light': '#e8f0fe',
                        surface:   '#f7f9fb',
                        sidebar:   '#0b1f3a',
                        'sidebar-hover': '#162d50',
                        'on-surface': '#191c1e',
                        'on-surface-variant': '#414755',
                        'outline-variant': '#c1c6d7',
                        secondary: '#50616b',
                    },
                    fontFamily: { inter: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .mat-icon { font-family: 'Material Symbols Outlined'; font-weight: normal;
            font-style: normal; font-size: 24px; line-height: 1; letter-spacing: normal;
            text-transform: none; display: inline-block; white-space: nowrap;
            word-wrap: normal; direction: ltr; -webkit-font-feature-settings: 'liga';
            font-feature-settings: 'liga'; -webkit-font-smoothing: antialiased; }
        .sidebar-link { display:flex; align-items:center; gap:12px; padding:10px 16px;
            border-radius:12px; color:#94a3b8; font-size:0.875rem; font-weight:500;
            text-decoration:none; transition:all .2s; }
        .sidebar-link:hover { background:#162d50; color:#fff; }
        .sidebar-link.active { background:linear-gradient(135deg,#0058bc,#1a78e5);
            color:#fff; box-shadow:0 4px 14px rgba(0,88,188,.4); }
        .sidebar-link.active .mat-icon { color:#fff; }
        .card { background:#fff; border-radius:20px; border:1px solid #e5e7eb;
            box-shadow:0 1px 4px rgba(0,0,0,.04); }
        .btn-primary { display:inline-flex; align-items:center; gap:8px;
            background:linear-gradient(135deg,#0058bc,#1a78e5); color:#fff;
            padding:10px 22px; border-radius:12px; font-size:.875rem; font-weight:600;
            border:none; cursor:pointer; transition:all .2s;
            box-shadow:0 4px 12px rgba(0,88,188,.3); }
        .btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(0,88,188,.4); }
        .btn-secondary { display:inline-flex; align-items:center; gap:8px;
            background:#fff; color:#374151; padding:10px 22px; border-radius:12px;
            font-size:.875rem; font-weight:600; border:1.5px solid #e5e7eb;
            cursor:pointer; transition:all .2s; }
        .btn-secondary:hover { border-color:#0058bc; color:#0058bc; }
        .btn-danger { display:inline-flex; align-items:center; gap:8px;
            background:#fee2e2; color:#dc2626; padding:8px 16px; border-radius:10px;
            font-size:.8rem; font-weight:600; border:none; cursor:pointer; transition:all .2s; }
        .btn-danger:hover { background:#dc2626; color:#fff; }
        .btn-edit { display:inline-flex; align-items:center; gap:6px;
            background:#eff6ff; color:#1d4ed8; padding:8px 14px; border-radius:10px;
            font-size:.8rem; font-weight:600; border:none; cursor:pointer; transition:all .2s;
            text-decoration:none; }
        .btn-edit:hover { background:#dbeafe; }
        .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 12px;
            border-radius:99px; font-size:.75rem; font-weight:600; }
        .badge-green  { background:#dcfce7; color:#15803d; }
        .badge-red    { background:#fee2e2; color:#dc2626; }
        .badge-yellow { background:#fef9c3; color:#a16207; }
        .badge-gray   { background:#f3f4f6; color:#374151; }
        .badge-blue   { background:#dbeafe; color:#1d4ed8; }
        .badge-orange { background:#ffedd5; color:#c2410c; }
        table { width:100%; border-collapse:collapse; }
        thead th { padding:12px 16px; text-align:left; font-size:.75rem; font-weight:700;
            color:#6b7280; text-transform:uppercase; letter-spacing:.05em;
            background:#f9fafb; border-bottom:1px solid #e5e7eb; }
        thead th:first-child { border-radius:12px 0 0 0; }
        thead th:last-child  { border-radius:0 12px 0 0; }
        tbody tr { border-bottom:1px solid #f3f4f6; transition:background .15s; }
        tbody tr:hover { background:#f8faff; }
        tbody td { padding:14px 16px; font-size:.875rem; color:#374151; }
        .form-group { margin-bottom:1.25rem; }
        .form-label { display:block; font-size:.875rem; font-weight:600;
            color:#374151; margin-bottom:.5rem; }
        .form-input { width:100%; padding:11px 14px; border:1.5px solid #e5e7eb;
            border-radius:12px; font-size:.875rem; color:#191c1e;
            background:#fff; transition:border-color .2s; outline:none; }
        .form-input:focus { border-color:#0058bc; box-shadow:0 0 0 3px rgba(0,88,188,.12); }
        .form-select { width:100%; padding:11px 14px; border:1.5px solid #e5e7eb;
            border-radius:12px; font-size:.875rem; color:#191c1e;
            background:#fff; outline:none; cursor:pointer; }
        .form-select:focus { border-color:#0058bc; box-shadow:0 0 0 3px rgba(0,88,188,.12); }
        .form-textarea { width:100%; padding:11px 14px; border:1.5px solid #e5e7eb;
            border-radius:12px; font-size:.875rem; color:#191c1e;
            background:#fff; resize:vertical; min-height:100px; outline:none; }
        .form-textarea:focus { border-color:#0058bc; box-shadow:0 0 0 3px rgba(0,88,188,.12); }
        .page-header { display:flex; align-items:center; justify-content:space-between;
            margin-bottom:1.5rem; flex-wrap:wrap; gap:12px; }
        .page-title { font-size:1.5rem; font-weight:800; color:#0b1f3a; }
        .page-subtitle { font-size:.875rem; color:#6b7280; margin-top:2px; }
    </style>
</head>
<body class="bg-surface min-h-screen flex">

<!-- ── SIDEBAR ─────────────────────────────────────────── -->
<aside style="width:260px;min-height:100vh;flex-shrink:0;"
       class="bg-sidebar flex flex-col fixed top-0 left-0 h-full z-40">

    <!-- Logo -->
    <div class="px-6 py-6 border-b border-white/10">
        <a href="<?= $base ?>dashboard/index.php"
           class="text-2xl font-black text-white tracking-tight">CariGalon</a>
        <p class="text-xs text-slate-400 mt-1">Universitas Ciputra</p>
    </div>

    <!-- Nav -->
    <nav class="flex-1 p-4 flex flex-col gap-1 overflow-y-auto">
        <?php foreach ($menuItems as $item): ?>
        <a href="<?= $item['href'] ?>"
           class="sidebar-link <?= $activeMenu === $item['key'] ? 'active' : '' ?>">
            <span class="mat-icon" style="font-size:20px"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="p-4 border-t border-white/10 flex flex-col gap-2">
        <a href="<?= $base ?>index.html"
           class="sidebar-link" style="font-size:.8rem;">
            <span class="mat-icon" style="font-size:18px">home</span>
            Kembali ke Beranda
        </a>
        <a href="<?= $base ?>logout.php"
           class="sidebar-link text-red-400 hover:bg-red-950/30 hover:text-red-300" style="font-size:.8rem;">
            <span class="mat-icon" style="font-size:18px">logout</span>
            Keluar (Logout)
        </a>
    </div>
</aside>

<!-- ── MAIN CONTENT ───────────────────────────────────── -->
<div style="margin-left:260px;" class="flex-1 min-h-screen">
    <!-- Top bar -->
    <header class="bg-white border-b border-gray-100 px-8 py-4 flex items-center justify-between sticky top-0 z-30">
        <div>
            <h1 class="text-lg font-bold text-gray-800"><?= h($pageTitle) ?></h1>
            <p class="text-xs text-gray-400">CariGalon Admin Dashboard</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-400"><?= date('d M Y, H:i') ?> WIB</span>
            <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(mb_substr($_SESSION['staff_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="hidden md:block text-left">
                <div class="text-xs font-semibold text-gray-800"><?= h($_SESSION['staff_name'] ?? 'Staff') ?></div>
                <div class="text-[10px] text-gray-400"><?= h(($_SESSION['staff_role'] ?? 'Staff') === 'Admin' ? 'Super Admin' : 'Staff Maintenance') ?></div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="p-8">
