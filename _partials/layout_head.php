<?php
/**
 * =============================================================================
 * _partials/layout_head.php — Shared Header, Sidebar Navigasi & Guard Autentikasi
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah komponen layout bersama (shared partial) yang di-include di bagian
 *   paling atas setiap halaman dashboard CariGalon. Tugasnya mencakup tiga tanggung
 *   jawab utama: (1) menjaga keamanan dengan memverifikasi sesi login dan hak akses role,
 *   (2) merender seluruh blok <head> HTML beserta CSS global dan konfigurasi Tailwind,
 *   dan (3) merender sidebar navigasi vertikal yang adaptif berdasarkan role pengguna.
 *   File ini juga mendefinisikan seluruh class CSS kustom sistem (card, btn-primary,
 *   btn-secondary, badge, form-input, dll.) yang digunakan di semua halaman dashboard.
 *   Setiap halaman yang menggunakan file ini harus menetapkan variabel $pageTitle dan
 *   $activeMenu sebelum melakukan include agar sidebar dan judul tab browser tampil benar.
 *
 * FUNGSI UTAMA:
 *   - Menghitung base path ($base) secara dinamis tergantung kedalaman direktori halaman
 *   - Memeriksa sesi login; jika tidak terautentikasi, redirect ke login.php
 *   - Menerapkan kontrol akses berbasis role (RBAC): staff dilarang mengakses
 *     folder-folder admin-only (lokasi, dispensers, reporters, staff, assignments)
 *   - Merender tag <head> lengkap: charset, viewport, judul, Tailwind CDN, Google Fonts,
 *     Material Symbols Outlined, dan blok <style> CSS kustom global
 *   - Merender sidebar navigasi vertikal dengan logo CariGalon, daftar menu dinamis,
 *     dan tautan logout di bagian bawah sidebar
 *   - Memfilter item menu sidebar berdasarkan role: Staff hanya melihat Dashboard,
 *     Laporan Masalah, dan Refill Log; Admin melihat semua menu
 *   - Merender top header bar dengan judul halaman, tanggal/jam WIB, dan info pengguna
 *   - Membuka tag <main> yang akan diisi oleh konten halaman masing-masing
 *
 * ALUR KERJA (FLOW):
 *   1. Konstanta ROOT didefinisikan (jika belum ada) sebagai path absolut root proyek
 *   2. $base dihitung: './' jika script ada di root, '../' jika di subdirektori
 *   3. Sesi dimulai jika belum aktif
 *   4. Jika staff_logged_in tidak ada atau false, redirect ke login.php
 *   5. Jika role adalah 'Staff' dan halaman yang diakses masuk folder admin-only,
 *      set flash error dan redirect ke dashboard/index.php
 *   6. $pageTitle dan $activeMenu di-set ke default jika belum didefinisikan
 *   7. Array $menuItems dibuat dengan semua item menu lengkap
 *   8. Jika role Staff, $menuItems difilter hanya menyisakan dashboard, laporan, refill
 *   9. HTML <head> dirender dengan semua stylesheet dan konfigurasi Tailwind
 *   10. Sidebar (<aside>) dirender dengan menu yang sudah difilter
 *   11. Top header bar dirender dengan info pengguna
 *   12. Tag <main class="p-8"> dibuka (ditutup oleh layout_foot.php)
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - (tidak ada query database langsung di file ini; data diambil dari sesi $_SESSION)
 *
 * VARIABEL PENTING:
 *   - $pageTitle             : Judul halaman (ditampilkan di <title> dan top header)
 *   - $activeMenu            : Key menu aktif (digunakan untuk styling .active pada sidebar)
 *   - $base                  : Prefix path relatif ('./' atau '../') untuk semua link/href
 *   - $menuItems             : Array menu navigasi sidebar (key, href, icon, label)
 *   - $adminOnlyFolders      : Array nama folder yang dilarang diakses oleh role Staff
 *   - $isBlocked             : Boolean; true jika staff mencoba akses folder admin-only
 *   - $_SESSION['staff_name']: Nama staff untuk ditampilkan di top header dan avatar
 *   - $_SESSION['staff_role']: Role staff ('Admin'/'Staff') untuk kontrol akses menu
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Harus sudah di-include oleh halaman pemanggil sebelum layout_head.php,
 *              karena fungsi h() dan set_flash() digunakan di sini
 *
 * AKSES:
 *   Tidak diakses langsung; hanya di-include oleh halaman-halaman dashboard.
 *   Semua halaman yang meng-include file ini otomatis terlindungi oleh guard autentikasi.
 *
 * CATATAN PENGEMBANG:
 *   Setiap penambahan halaman baru ke dashboard wajib: (1) menambahkan item ke $menuItems,
 *   (2) menentukan apakah halaman tersebut admin-only (tambahkan ke $adminOnlyFolders jika ya),
 *   (3) menetapkan $pageTitle dan $activeMenu sebelum include file ini. CSS kustom global
 *   (btn-primary, card, badge, dll.) didefinisikan di sini dan berlaku untuk semua halaman.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// CATATAN PENTING UNTUK PEMULA — Cara kerja file ini
// ===================================================
// File ini di-include (disertakan) di AWAL setiap halaman dashboard.
// Artinya, daripada menulis ulang sidebar dan pengecekan login di setiap halaman,
// cukup tulis sekali di sini, lalu panggil dengan: include 'layout_head.php'
// Cara include-nya:
//   include __DIR__ . '/../_partials/layout_head.php';
// Sebelum include, halaman WAJIB set dua variabel ini:
//   $pageTitle  = 'Nama Halaman'; // Muncul di tab browser dan header atas
//   $activeMenu = 'key_menu';     // Menu mana yang harus ditandai aktif di sidebar

// _partials/layout.php — Shared sidebar layout component
// Usage: include __DIR__ . '/../_partials/layout.php';
// Set $pageTitle and $activeMenu before including.

// ===================================================
// KONSTANTA ROOT — Path absolut ke folder root proyek
// ===================================================
// defined('ROOT') mengecek apakah konstanta ROOT sudah dibuat sebelumnya.
// Kalau belum, buat sekarang. Ini mencegah error "constant already defined".
// ROOT dipakai untuk include file lain dengan path yang pasti benar.
if (!defined('ROOT')) {
    // Calculate root relative to this partial (2 levels up from _partials/)
    // dirname(__FILE__) = folder file ini (_partials/)
    // dirname(dirname(__FILE__)) = 2 level ke atas = folder root proyek
    // rtrim(..., '/\\') = hapus garis miring di ujung path agar konsisten
    define('ROOT', rtrim(dirname(dirname(__FILE__)), '/\\'));
}

// ===================================================
// HITUNG BASE PATH — Menentukan prefix URL relatif
// ===================================================
// Masalah: file di folder 'dashboard/' butuh '../' untuk naik ke root,
// tapi file di root langsung butuh './'
// Solusi: bandingkan lokasi script saat ini dengan ROOT
// Determine base path relative to ROOT folder
$scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'])); // Path folder file PHP yang sedang dijalankan
$rootDir = realpath(ROOT);                                   // Path folder root proyek

if ($scriptDir === $rootDir) {
    // Jika file ada di folder root (sama dengan ROOT), prefix cukup './'
    $base = './';
} else {
    // Jika file ada di subfolder (misal dashboard/), perlu '../' untuk naik ke root
    $base = '../';
}

// ===================================================
// MULAI SESI — Aktifkan sesi jika belum aktif
// ===================================================
// PHP_SESSION_NONE artinya sesi belum dimulai. Kita cek dulu supaya tidak error
// kalau session_start() dipanggil dua kali (bisa terjadi saat halaman pemanggil
// sudah memanggil session_start() terlebih dahulu).
if (session_status() === PHP_SESSION_NONE) session_start();

// ===================================================
// GUARD AUTENTIKASI — Blokir akses jika belum login
// ===================================================
// Enforce staff session authentication
// $_SESSION['staff_logged_in'] diset menjadi true oleh login_process.php saat login berhasil.
// Jika variabel itu tidak ada (belum login) atau nilainya bukan true (sesi rusak/palsu),
// paksa pengguna kembali ke halaman login.
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    // Kirim pengguna ke halaman login — $base memastikan path benar dari manapun
    header('Location: ' . $base . 'login.php');
    exit; // WAJIB! Hentikan eksekusi agar kode di bawah tidak ikut dijalankan
}

// ===================================================
// KONTROL AKSES BERBASIS ROLE (RBAC)
// ===================================================
// Enforce role-based folder access control
// Jika pengguna adalah Staff (bukan Admin), cegah akses ke halaman-halaman admin-only.
// Ini penting karena Staff tidak boleh mengelola data lokasi, dispenser, staff lain, dll.
if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    // Daftar nama folder yang HANYA boleh diakses oleh Admin, bukan Staff biasa
    $adminOnlyFolders = ['lokasi', 'dispensers', 'reporters', 'staff', 'assignments'];

    // $_SERVER['SCRIPT_NAME'] = path URL halaman yang sedang dibuka, contoh: /dashboard/lokasi/index.php
    $currentPath = $_SERVER['SCRIPT_NAME'];
    $isBlocked = false; // Anggap boleh akses dulu (false = tidak diblokir)

    // Cek satu per satu: apakah URL mengandung nama folder admin-only?
    foreach ($adminOnlyFolders as $folder) {
        if (strpos($currentPath, "/$folder/") !== false) {
            // strpos() mencari apakah '/namaFolder/' ada di dalam URL
            // Kalau ditemukan (bukan false), berarti Staff mencoba buka halaman admin
            $isBlocked = true;
            break; // Tidak perlu cek folder lain, langsung hentikan looping
        }
    }

    if ($isBlocked) {
        // Simpan pesan error ke sesi supaya bisa ditampilkan di dashboard sebagai notifikasi
        set_flash('error', 'Akses ditolak: Halaman ini hanya dapat diakses oleh Super Admin.');
        // Kirim Staff kembali ke halaman dashboard mereka
        header('Location: ' . $base . 'dashboard/index.php');
        exit; // Hentikan eksekusi
    }
}

// ===================================================
// DEFAULT VARIABEL — Pastikan $pageTitle dan $activeMenu ada
// ===================================================
// Operator ?? (null coalescing): kalau variabel belum di-set, gunakan nilai default.
// Ini mencegah PHP error "Undefined variable" jika halaman lupa set variabel ini.
$pageTitle  = $pageTitle  ?? 'CariGalon'; // Default judul halaman jika tidak di-set
$activeMenu = $activeMenu ?? '';           // Default tidak ada menu yang aktif

// ===================================================
// DAFTAR MENU SIDEBAR — Semua item navigasi dashboard
// ===================================================
// Array asosiatif berisi semua item menu sidebar.
// Setiap item punya:
//   'key'   = ID unik menu (dicocokkan dengan $activeMenu untuk tandai menu aktif)
//   'href'  = URL tujuan saat diklik ($base otomatis menyesuaikan path)
//   'icon'  = nama ikon dari Material Symbols (misal: 'dashboard', 'map', dll.)
//   'label' = teks yang ditampilkan di sidebar
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

// ===================================================
// FILTER MENU UNTUK STAFF — Sembunyikan menu admin-only
// ===================================================
// Jika yang login adalah Staff (bukan Admin), saring daftar menu.
// Staff hanya boleh melihat 3 menu: Dashboard, Laporan Masalah, dan Refill Log.
// array_filter() = membuat array baru yang hanya berisi item yang lolos fungsi filter.
// in_array() = cek apakah nilai ada di dalam array.
if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    $menuItems = array_filter($menuItems, function($item) {
        // Kembalikan true (pertahankan item) hanya jika key-nya ada di daftar menu Staff
        return in_array($item['key'], ['dashboard', 'laporan', 'refill']);
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Judul tab browser: "Nama Halaman — CariGalon Dashboard" -->
    <!-- h() = fungsi sanitasi: mengubah karakter berbahaya (<,>,") agar tidak bisa dieksploitasi (XSS) -->
    <title><?= h($pageTitle) ?> — CariGalon Dashboard</title>
    <meta name="description" content="Dashboard manajemen logistik galon air minum Universitas Ciputra.">

    <!-- Load Tailwind CSS dari CDN — framework CSS yang membuat styling lebih mudah dengan class siap pakai -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <!-- Load font Inter dari Google Fonts — font modern yang dipakai di seluruh dashboard -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Load ikon Material Symbols — koleksi ikon vektor dari Google yang dipakai di menu dan tombol -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1" rel="stylesheet">

    <!-- ===================================================
         KONFIGURASI WARNA & FONT TAILWIND
         =================================================== -->
    <!-- Extend Tailwind dengan warna dan font kustom proyek CariGalon -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:   '#0058bc',         // Biru utama — warna brand CariGalon
                        'primary-dark': '#003f8a',    // Biru lebih gelap (untuk hover/aktif)
                        'primary-light': '#e8f0fe',   // Biru sangat muda (untuk background badge)
                        surface:   '#f7f9fb',         // Abu-abu sangat muda (background halaman)
                        sidebar:   '#0b1f3a',         // Biru tua pekat untuk background sidebar
                        'sidebar-hover': '#162d50',   // Sidebar item saat di-hover
                        'on-surface': '#191c1e',      // Warna teks di atas surface
                        'on-surface-variant': '#414755', // Warna teks sekunder
                        'outline-variant': '#c1c6d7', // Warna border/garis
                        secondary: '#50616b',         // Warna teks abu-abu
                    },
                    fontFamily: { inter: ['Inter', 'sans-serif'] }, // Daftarkan font Inter
                }
            }
        }
    </script>

    <!-- ===================================================
         CSS KUSTOM GLOBAL — Gaya yang dipakai di semua halaman
         ===================================================
         Semua class di bawah ini bisa dipakai di halaman manapun
         yang meng-include layout_head.php ini.
    -->
    <style>
        /* Semua elemen pakai font Inter */
        * { font-family: 'Inter', sans-serif; }

        /* .mat-icon = ikon dari Material Symbols — agar tampil sebagai ikon (bukan teks biasa) */
        .mat-icon { font-family: 'Material Symbols Outlined'; font-weight: normal;
            font-style: normal; font-size: 24px; line-height: 1; letter-spacing: normal;
            text-transform: none; display: inline-block; white-space: nowrap;
            word-wrap: normal; direction: ltr; -webkit-font-feature-settings: 'liga';
            font-feature-settings: 'liga'; -webkit-font-smoothing: antialiased; }

        /* .sidebar-link = gaya dasar link di sidebar navigasi */
        .sidebar-link { display:flex; align-items:center; gap:12px; padding:10px 16px;
            border-radius:12px; color:#94a3b8; font-size:0.875rem; font-weight:500;
            text-decoration:none; transition:all .2s; }
        /* Saat mouse di atas link sidebar: latar belakang lebih terang */
        .sidebar-link:hover { background:#162d50; color:#fff; }
        /* .active = menu yang sedang aktif (halaman yang sedang dibuka) — diberi warna biru */
        .sidebar-link.active { background:linear-gradient(135deg,#0058bc,#1a78e5);
            color:#fff; box-shadow:0 4px 14px rgba(0,88,188,.4); }
        .sidebar-link.active .mat-icon { color:#fff; } /* Ikon menu aktif juga putih */

        /* .card = komponen kotak putih bertepi bulat — dipakai untuk semua "kartu" konten */
        .card { background:#fff; border-radius:20px; border:1px solid #e5e7eb;
            box-shadow:0 1px 4px rgba(0,0,0,.04); }

        /* .btn-primary = tombol aksi utama berwarna biru gradient */
        .btn-primary { display:inline-flex; align-items:center; gap:8px;
            background:linear-gradient(135deg,#0058bc,#1a78e5); color:#fff;
            padding:10px 22px; border-radius:12px; font-size:.875rem; font-weight:600;
            border:none; cursor:pointer; transition:all .2s;
            box-shadow:0 4px 12px rgba(0,88,188,.3); }
        /* Saat hover: tombol sedikit naik ke atas dan bayangan lebih besar */
        .btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(0,88,188,.4); }

        /* .btn-secondary = tombol aksi sekunder berwarna putih dengan border abu */
        .btn-secondary { display:inline-flex; align-items:center; gap:8px;
            background:#fff; color:#374151; padding:10px 22px; border-radius:12px;
            font-size:.875rem; font-weight:600; border:1.5px solid #e5e7eb;
            cursor:pointer; transition:all .2s; }
        /* Saat hover: border dan teks berubah menjadi biru */
        .btn-secondary:hover { border-color:#0058bc; color:#0058bc; }

        /* .btn-danger = tombol berbahaya (hapus, tolak) berwarna merah muda */
        .btn-danger { display:inline-flex; align-items:center; gap:8px;
            background:#fee2e2; color:#dc2626; padding:8px 16px; border-radius:10px;
            font-size:.8rem; font-weight:600; border:none; cursor:pointer; transition:all .2s; }
        /* Saat hover: latar penuh merah, teks putih — makin jelas bahwa ini berbahaya */
        .btn-danger:hover { background:#dc2626; color:#fff; }

        /* .btn-edit = tombol edit berwarna biru muda */
        .btn-edit { display:inline-flex; align-items:center; gap:6px;
            background:#eff6ff; color:#1d4ed8; padding:8px 14px; border-radius:10px;
            font-size:.8rem; font-weight:600; border:none; cursor:pointer; transition:all .2s;
            text-decoration:none; }
        .btn-edit:hover { background:#dbeafe; } /* Lebih gelap saat hover */

        /* .badge = label status kecil berbentuk pil (pill-shape) */
        .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 12px;
            border-radius:99px; font-size:.75rem; font-weight:600; }
        /* Variasi warna badge berdasarkan status: */
        .badge-green  { background:#dcfce7; color:#15803d; } /* Hijau = Selesai/Sukses */
        .badge-red    { background:#fee2e2; color:#dc2626; } /* Merah = Ditolak/Error */
        .badge-yellow { background:#fef9c3; color:#a16207; } /* Kuning = Pending/Menunggu */
        .badge-gray   { background:#f3f4f6; color:#374151; } /* Abu = Status tidak diketahui */
        .badge-blue   { background:#dbeafe; color:#1d4ed8; } /* Biru = Diproses/In Progress */
        .badge-orange { background:#ffedd5; color:#c2410c; } /* Oranye = Kategori masalah */

        /* Gaya tabel data — dipakai di semua halaman daftar (laporan, dispenser, dll.) */
        table { width:100%; border-collapse:collapse; }
        thead th { padding:12px 16px; text-align:left; font-size:.75rem; font-weight:700;
            color:#6b7280; text-transform:uppercase; letter-spacing:.05em;
            background:#f9fafb; border-bottom:1px solid #e5e7eb; }
        thead th:first-child { border-radius:12px 0 0 0; } /* Pojok kiri header membulat */
        thead th:last-child  { border-radius:0 12px 0 0; } /* Pojok kanan header membulat */
        tbody tr { border-bottom:1px solid #f3f4f6; transition:background .15s; }
        tbody tr:hover { background:#f8faff; } /* Baris berubah warna saat mouse di atasnya */
        tbody td { padding:14px 16px; font-size:.875rem; color:#374151; }

        /* Gaya elemen form — dipakai di halaman tambah/edit data */
        .form-group { margin-bottom:1.25rem; }                /* Jarak antar kelompok field form */
        .form-label { display:block; font-size:.875rem; font-weight:600;
            color:#374151; margin-bottom:.5rem; }             /* Label di atas input */
        .form-input { width:100%; padding:11px 14px; border:1.5px solid #e5e7eb;
            border-radius:12px; font-size:.875rem; color:#191c1e;
            background:#fff; transition:border-color .2s; outline:none; }
        /* Saat input diklik/diisi: border berubah biru dengan efek cahaya */
        .form-input:focus { border-color:#0058bc; box-shadow:0 0 0 3px rgba(0,88,188,.12); }
        .form-select { width:100%; padding:11px 14px; border:1.5px solid #e5e7eb;
            border-radius:12px; font-size:.875rem; color:#191c1e;
            background:#fff; outline:none; cursor:pointer; }  /* Dropdown/pilihan */
        .form-select:focus { border-color:#0058bc; box-shadow:0 0 0 3px rgba(0,88,188,.12); }
        .form-textarea { width:100%; padding:11px 14px; border:1.5px solid #e5e7eb;
            border-radius:12px; font-size:.875rem; color:#191c1e;
            background:#fff; resize:vertical; min-height:100px; outline:none; } /* Area teks besar */
        .form-textarea:focus { border-color:#0058bc; box-shadow:0 0 0 3px rgba(0,88,188,.12); }

        /* Header baris paling atas di dalam konten halaman (judul halaman + tombol aksi) */
        .page-header { display:flex; align-items:center; justify-content:space-between;
            margin-bottom:1.5rem; flex-wrap:wrap; gap:12px; }
        .page-title { font-size:1.5rem; font-weight:800; color:#0b1f3a; }    /* Judul halaman */
        .page-subtitle { font-size:.875rem; color:#6b7280; margin-top:2px; } /* Subjudul kecil */
    </style>
</head>

<!-- Body: background abu-abu muda, layout flex horizontal (sidebar + konten utama berdampingan) -->
<body class="bg-surface min-h-screen flex">

<!-- ===================================================
     SIDEBAR NAVIGASI — Panel kiri berisi menu halaman
     ===================================================
     <aside> = elemen HTML semantik untuk konten samping (sidebar)
     Sidebar ini fixed (tidak ikut scroll), lebar 260px, tinggi penuh layar.
     Warnanya biru tua gelap (bg-sidebar = #0b1f3a).
-->
<!-- ── SIDEBAR ─────────────────────────────────────────── -->
<aside style="width:260px;min-height:100vh;flex-shrink:0;"
       class="bg-sidebar flex flex-col fixed top-0 left-0 h-full z-40">

    <!-- ===================================================
         LOGO — Nama aplikasi di bagian atas sidebar
         =================================================== -->
    <!-- Logo -->
    <div class="px-6 py-6 border-b border-white/10">
        <!-- Klik logo = kembali ke halaman dashboard utama -->
        <a href="<?= $base ?>dashboard/index.php"
           class="text-2xl font-black text-white tracking-tight">CariGalon</a>
        <p class="text-xs text-slate-400 mt-1">Universitas Ciputra</p>
    </div>

    <!-- ===================================================
         MENU NAVIGASI — Daftar link halaman dashboard
         ===================================================
         Loop foreach di sini menghasilkan satu <a> link untuk setiap item di $menuItems.
         Jika $activeMenu cocok dengan 'key' item, tambahkan class 'active' (warna biru).
    -->
    <!-- Nav -->
    <nav class="flex-1 p-4 flex flex-col gap-1 overflow-y-auto">
        <?php foreach ($menuItems as $item): ?>
        <!-- Link menu sidebar: class 'active' ditambahkan kalau ini halaman yang sedang aktif -->
        <a href="<?= $item['href'] ?>"
           class="sidebar-link <?= $activeMenu === $item['key'] ? 'active' : '' ?>">
            <!-- Ikon Material Symbols di sebelah kiri teks label -->
            <span class="mat-icon" style="font-size:20px"><?= $item['icon'] ?></span>
            <?= $item['label'] ?> <!-- Teks nama menu -->
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- ===================================================
         FOOTER SIDEBAR — Link beranda & tombol logout
         =================================================== -->
    <!-- Footer -->
    <div class="p-4 border-t border-white/10 flex flex-col gap-2">
<<<<<<< Updated upstream
        <a href="<?= $base ?>index.php"
=======
        <!-- Link kembali ke halaman publik (index.html) -->
        <a href="<?= $base ?>index.html"
>>>>>>> Stashed changes
           class="sidebar-link" style="font-size:.8rem;">
            <span class="mat-icon" style="font-size:18px">home</span>
            Kembali ke Beranda
        </a>
        <!-- Tombol logout — warna merah agar mudah dikenali, mengarah ke logout.php -->
        <a href="<?= $base ?>logout.php"
           class="sidebar-link text-red-400 hover:bg-red-950/30 hover:text-red-300" style="font-size:.8rem;">
            <span class="mat-icon" style="font-size:18px">logout</span>
            Keluar (Logout)
        </a>
    </div>
</aside>

<!-- ===================================================
     AREA KONTEN UTAMA — Di sebelah kanan sidebar
     ===================================================
     margin-left:260px = geser ke kanan sejauh lebar sidebar agar tidak tertimpa sidebar.
     flex-1 = isi sisa ruang yang tersedia.
-->
<!-- ── MAIN CONTENT ───────────────────────────────────── -->
<div style="margin-left:260px;" class="flex-1 min-h-screen">

    <!-- ===================================================
         HEADER ATAS — Bar info di bagian paling atas halaman
         ===================================================
         sticky top-0 = header ini "nempel" di atas saat halaman di-scroll ke bawah.
         z-30 = ada di atas elemen lain (kecuali sidebar yang z-40).
    -->
    <!-- Top bar -->
    <header class="bg-white border-b border-gray-100 px-8 py-4 flex items-center justify-between sticky top-0 z-30">
        <div>
            <!-- Judul halaman yang sedang dibuka (diambil dari $pageTitle) -->
            <h1 class="text-lg font-bold text-gray-800"><?= h($pageTitle) ?></h1>
            <p class="text-xs text-gray-400">CariGalon Admin Dashboard</p>
        </div>
        <!-- Bagian kanan header: tanggal/jam + avatar + nama pengguna -->
        <div class="flex items-center gap-3">
            <!-- Tampilkan tanggal dan jam saat ini dari server (format: DD Mon YYYY, HH:MM) -->
            <span class="text-xs text-gray-400"><?= date('d M Y, H:i') ?> WIB</span>

            <!-- Avatar bulat berisi inisial nama pengguna yang login -->
            <!-- strtoupper() = ubah ke huruf besar | mb_substr(..., 0, 1) = ambil 1 karakter pertama -->
            <!-- Contoh: "Budi Santoso" → inisial "B" -->
            <div class="w-9 h-9 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm">
                <?= strtoupper(mb_substr($_SESSION['staff_name'] ?? 'A', 0, 1)) ?>
            </div>

            <!-- Nama lengkap dan role pengguna — hanya muncul di layar medium ke atas (hidden md:block) -->
            <div class="hidden md:block text-left">
                <!-- Nama staff yang sedang login (diambil dari sesi) -->
                <div class="text-xs font-semibold text-gray-800"><?= h($_SESSION['staff_name'] ?? 'Staff') ?></div>
                <!-- Label role: tampilkan 'Super Admin' jika Admin, atau 'Staff Maintenance' jika Staff -->
                <div class="text-[10px] text-gray-400"><?= h(($_SESSION['staff_role'] ?? 'Staff') === 'Admin' ? 'Super Admin' : 'Staff Maintenance') ?></div>
            </div>
        </div>
    </header>

    <!-- ===================================================
         AREA KONTEN HALAMAN — Tag <main> dibuka di sini
         ===================================================
         Tag ini DITUTUP oleh layout_foot.php dengan </main>
         Semua konten spesifik tiap halaman ada di dalam tag <main> ini.
         p-8 = padding (jarak dalam) 2rem di semua sisi.
    -->
    <!-- Page Content -->
    <main class="p-8">
