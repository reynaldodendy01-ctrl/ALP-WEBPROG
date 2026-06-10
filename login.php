<?php
<<<<<<< Updated upstream
// login.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    header('Location: dashboard/index.php');
    exit;
=======
/**
 * =============================================================================
 * login.php — Halaman Form Login Staff & Admin
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman antarmuka login yang menjadi pintu masuk utama
 *   bagi seluruh pengguna internal sistem CariGalon, baik Staff Maintenance maupun
 *   Super Admin. Halaman ini menampilkan formulir berbasis dark-theme dengan desain
 *   glass-morphism yang modern, dilengkapi validasi sisi klien menggunakan JavaScript.
 *   Jika pengguna sudah terautentikasi (sesi aktif), mereka akan langsung diarahkan
 *   ke halaman dashboard tanpa perlu login ulang. Selain itu, terdapat mekanisme
 *   pengecekan khusus untuk memastikan fitur login hanya aktif di server lokal
 *   (localhost), bukan di lingkungan demo publik seperti Vercel.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan form login dengan input email dan password
 *   - Mengarahkan pengguna yang sudah login ke dashboard/index.php secara otomatis
 *   - Menampilkan pesan error dari query string (?error=...) yang dikirim oleh login_process.php
 *   - Memblokir login di lingkungan non-lokal (Vercel/publik) dengan pesan notifikasi
 *   - Menyediakan tautan kembali ke halaman beranda publik (index.html)
 *
 * ALUR KERJA (FLOW):
 *   1. Sesi PHP dimulai; jika $_SESSION['staff_id'] sudah ada, redirect ke dashboard
 *   2. HTML halaman dirender: head (Tailwind CSS, Google Fonts, Material Symbols)
 *   3. Body merender background ambient lights dan card login bergaya glass-morphism
 *   4. Form POST dikirim ke login_process.php saat tombol "Masuk Dashboard" diklik
 *   5. JavaScript membaca query string ?error= dan menampilkan error-box jika ada
 *   6. JavaScript memeriksa hostname; jika bukan localhost, form diblokir dan pesan
 *      "demo frontend saja" ditampilkan kepada pengguna
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - (tidak ada; akses database dilakukan oleh login_process.php)
 *
 * VARIABEL PENTING:
 *   - $_SESSION['staff_id']  : Jika ada, pengguna dianggap sudah login dan di-redirect
 *   - $_POST['email']        : Email staff yang diinput (dikirim ke login_process.php)
 *   - $_POST['password']     : Password staff yang diinput (dikirim ke login_process.php)
 *   - p (JS URLSearchParams) : Objek JavaScript untuk membaca parameter error dari URL
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - login_process.php : Tujuan POST form; memproses validasi kredensial
 *   - index.html        : Halaman beranda publik (tautan "Kembali ke Beranda")
 *
 * AKSES:
 *   Dapat diakses oleh semua pengguna yang belum login. Pengguna yang sudah
 *   terautentikasi akan di-redirect otomatis ke dashboard.
 *
 * CATATAN PENGEMBANG:
 *   Halaman ini sengaja tidak meng-include db.php karena tidak ada akses database
 *   di sisi ini. Waspada terhadap potensi open redirect jika logika redirect diubah.
 *   Pastikan form action selalu mengarah ke login_process.php yang aman.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// MULAI SESI — Aktifkan sistem sesi PHP
// ===================================================
// session_start() harus dipanggil PALING AWAL sebelum output apapun.
// Sesi adalah cara PHP "mengingat" siapa pengguna antar halaman (seperti login status).
session_start();

// ===================================================
// CEK APAKAH SUDAH LOGIN — Jika sudah, tidak perlu login lagi
// ===================================================
// isset() mengecek apakah variabel $_SESSION['staff_id'] sudah ada.
// Kalau sudah ada, berarti pengguna sudah login sebelumnya → langsung kirim ke dashboard.
if (isset($_SESSION['staff_id'])) {
    // Kirim pengguna ke halaman dashboard tanpa harus login ulang
    header("Location: dashboard/index.php"); // header('Location: ...') = redirect ke halaman lain
    exit; // Hentikan eksekusi PHP di sini — penting! tanpa exit, kode di bawah tetap jalan
>>>>>>> Stashed changes
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $staff = $stmt->fetch();

            if ($staff && password_verify($password, $staff['Password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_id'] = $staff['Staff_ID'];
                $_SESSION['staff_name'] = $staff['Nama'];
                $_SESSION['staff_email'] = $staff['Email'];
                $_SESSION['staff_role'] = $staff['Role'];

                set_flash('success', "Selamat datang kembali, " . $staff['Nama'] . "!");
                header('Location: dashboard/index.php');
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Staff — CariGalon</title>
    <meta name="description" content="Halaman login khusus staff CariGalon Universitas Ciputra.">

    <!-- Load Tailwind CSS dari internet (CDN) — framework CSS untuk styling cepat -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <!-- Load font Inter dari Google Fonts — supaya tulisan terlihat rapi dan modern -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Load ikon Material Symbols — koleksi ikon siap pakai dari Google -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1" rel="stylesheet">

    <!-- ===================================================
         KONFIGURASI WARNA TAILWIND — Daftarkan warna kustom
         =================================================== -->
    <!-- Konfigurasi Tailwind: tambah warna khusus proyek ini supaya bisa dipakai di kelas CSS -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0058bc',        // Biru utama
                        'primary-dark': '#003f8a', // Biru lebih gelap (hover)
                        darkbg: '#0b1f3a'          // Biru sangat gelap (background)
                    },
                    fontFamily: { inter: ['Inter', 'sans-serif'] } // Daftarkan font Inter
                }
            }
        }
    </script>

    <!-- ===================================================
         CSS KUSTOM — Gaya tambahan yang tidak ada di Tailwind
         =================================================== -->
    <style>
        /* Semua elemen pakai font Inter secara default */
        * { font-family: 'Inter', sans-serif; }

        /* .glass-card = efek kaca buram (glass-morphism): transparan + blur di belakangnya */
        .glass-card {
            background: rgba(255, 255, 255, 0.08);  /* Putih tapi sangat transparan */
            backdrop-filter: blur(16px);             /* Blur konten di belakang elemen ini */
            -webkit-backdrop-filter: blur(16px);     /* Versi untuk browser Safari/iOS */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Border tipis semi-transparan */
        }

        /* .glow-input:focus = efek cahaya biru saat input sedang diklik/diisi */
        .glow-input:focus {
            border-color: #0058bc;                      /* Ganti warna border jadi biru */
            box-shadow: 0 0 0 4px rgba(0, 88, 188, 0.25); /* Lingkaran cahaya di sekitar input */
        }
    </style>
</head>

<!-- Body: latar belakang gelap, konten di tengah layar -->
<body class="bg-[#071120] text-white min-h-screen flex items-center justify-center relative overflow-hidden px-4">

    <!-- ===================================================
         AMBIENT LIGHTS — Efek cahaya latar belakang dekoratif
         =================================================== -->
    <!-- Lingkaran besar biru transparan di pojok kiri atas — hanya dekorasi visual -->
    <div class="absolute w-[400px] h-[400px] rounded-full bg-blue-600/10 blur-[100px] -top-20 -left-20"></div>
    <!-- Lingkaran besar ungu transparan di pojok kanan bawah — hanya dekorasi visual -->
    <div class="absolute w-[500px] h-[500px] rounded-full bg-indigo-500/10 blur-[120px] -bottom-20 -right-20"></div>

    <!-- Wadah utama kartu login, lebar maksimal 28rem, ada di atas layer lain (z-10) -->
    <div class="w-full max-w-md z-10">

        <!-- ===================================================
             LOGO HEADER — Logo dan tagline CariGalon di atas form
             =================================================== -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black tracking-tight text-white flex items-center justify-center gap-2">
                <!-- Ikon tetes air dari Material Symbols -->
                <span class="material-symbols-outlined text-[36px] text-blue-500">water_drop</span>
                CariGalon
            </h1>
            <p class="text-slate-400 text-sm mt-2">Masuk untuk mengelola logistik air Universitas Ciputra</p>
        </div>

        <!-- ===================================================
             KARTU LOGIN — Kotak form login bergaya kaca (glass)
             =================================================== -->
        <div class="glass-card rounded-3xl p-8 shadow-2xl relative">
            <!-- Garis biru tipis dekoratif di bagian atas kartu -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-[2px] bg-gradient-to-r from-transparent via-blue-500 to-transparent"></div>
            
            <h2 class="text-xl font-bold mb-6 text-slate-100 flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-slate-400">lock</span>
                Staff Login
            </h2>

<<<<<<< Updated upstream
            <?php if (!empty($error)): ?>
                <div class="flex items-start gap-3 p-4 mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-200 text-sm font-medium animate-pulse">
                    <span class="material-symbols-outlined text-[20px] shrink-0">error</span>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <?php render_flash(); ?>

            <form method="POST" action="login.php" class="space-y-5">
=======
            <!-- ===================================================
                 FORM LOGIN — Form yang dikirim ke login_process.php
                 ===================================================
                 method="POST"  : Data dikirim lewat body request (tidak kelihatan di URL)
                 action="..."   : Tujuan pengiriman form — login_process.php yang akan cek email & password
                 onsubmit="..." : Sebelum dikirim, jalankan fungsi handleLogin() untuk cek localhost
            -->
            <form method="POST" action="login_process.php" onsubmit="return handleLogin(event)" class="space-y-5">

                <!-- ===================================================
                     KOTAK ERROR — Muncul jika login gagal atau bukan localhost
                     ===================================================
                     display:none = disembunyikan dulu, JavaScript akan menampilkannya jika ada error
                -->
                <div id="error-box" style="display:none" class="flex items-start gap-3 p-4 mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-200 text-sm font-medium">
                    <span class="material-symbols-outlined text-[20px] shrink-0">error</span>
                    <!-- Teks pesan error akan diisi oleh JavaScript -->
                    <span id="error-msg"></span>
                </div>

                <!-- ===================================================
                     INPUT EMAIL — Kolom isian alamat email staff
                     =================================================== -->
>>>>>>> Stashed changes
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2" for="email">Email Staff</label>
                    <div class="relative">
                        <!-- Ikon amplop (mail) di dalam kotak input, posisi kiri -->
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
<<<<<<< Updated upstream
                        <input type="email" id="email" name="email" value="<?= h($email) ?>" required
=======
                        <!-- Input email: type="email" otomatis validasi format email, required = wajib diisi -->
                        <input type="email" id="email" name="email" value="" required
>>>>>>> Stashed changes
                               class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-700/50 rounded-xl text-sm text-white placeholder-slate-500 glow-input outline-none transition-all"
                               placeholder="nama.staff@uc.ac.id">
                    </div>
                </div>

                <!-- ===================================================
                     INPUT PASSWORD — Kolom isian kata sandi
                     =================================================== -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2" for="password">Password</label>
                    <div class="relative">
                        <!-- Ikon gembok terbuka di dalam kotak input -->
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock_open</span>
                        <!-- Input password: type="password" otomatis menyembunyikan karakter yang diketik -->
                        <input type="password" id="password" name="password" required
                               class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-700/50 rounded-xl text-sm text-white placeholder-slate-500 glow-input outline-none transition-all"
                               placeholder="••••••••">
                    </div>
                </div>

                <!-- ===================================================
                     TOMBOL SUBMIT — Tombol untuk mengirim form login
                     =================================================== -->
                <div class="pt-2">
                    <!-- type="submit" = saat diklik, form akan dikirim ke action (login_process.php) -->
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold py-3.5 px-6 rounded-xl text-sm shadow-lg hover:shadow-blue-500/10 active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">login</span>
                        Masuk Dashboard
                    </button>
                </div>
            </form>
        </div>

        <!-- ===================================================
             LINK KEMBALI — Tautan ke halaman publik (bukan dashboard)
             =================================================== -->
        <div class="text-center mt-6">
<<<<<<< Updated upstream
            <a href="index.php" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 font-medium transition-colors">
=======
            <!-- Klik link ini untuk kembali ke halaman depan publik (index.html) -->
            <a href="index.html" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 font-medium transition-colors">
>>>>>>> Stashed changes
                <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                Kembali ke Beranda Publik
            </a>
        </div>
    </div>
<<<<<<< Updated upstream
=======

    <!-- ===================================================
         JAVASCRIPT — Logika tampilan error & cek localhost
         =================================================== -->
    <script>
        // ===================================================
        // BACA PESAN ERROR DARI URL — Contoh: login.php?error=Email+salah
        // ===================================================
        // URLSearchParams membaca bagian "?..." dari URL halaman ini
        const p = new URLSearchParams(location.search);

        // Kalau ada parameter 'error' di URL (dikirim oleh login_process.php setelah gagal login)
        if (p.get('error')) {
            document.getElementById('error-box').style.display = 'flex'; // Tampilkan kotak error (dari hidden jadi flex)
            // Isi teks error dengan pesan dari URL (decodeURIComponent = ubah %20 → spasi, dll.)
            document.getElementById('error-msg').textContent = decodeURIComponent(p.get('error'));
        }

        // ===================================================
        // CEK LOCALHOST — Blokir login jika bukan di server lokal
        // ===================================================
        // Vercel warning
        // location.hostname = nama domain/IP halaman ini (misal: 'localhost' atau 'carigalon.vercel.app')
        const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';

        // Jika bukan localhost (misalnya dibuka dari Vercel/internet), tampilkan peringatan
        if (!isLocal) {
            document.getElementById('error-box').style.display = 'flex'; // Tampilkan kotak error
            document.getElementById('error-msg').textContent = 'Login hanya tersedia di server lokal. Versi ini adalah demo frontend saja.';
        }

        // ===================================================
        // FUNGSI handleLogin — Cegah submit form jika bukan localhost
        // ===================================================
        // Fungsi ini dipanggil saat tombol "Masuk Dashboard" diklik (onsubmit pada <form>)
        function handleLogin(e) {
            // Cek lagi apakah dibuka di localhost
            const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';

            // Jika bukan localhost, batalkan pengiriman form
            if (!isLocal) {
                e.preventDefault(); // Cegah form terkirim ke server
                document.getElementById('error-box').style.display = 'flex';
                document.getElementById('error-msg').textContent = 'Login hanya tersedia di server lokal. Versi ini adalah demo frontend saja.';
                return false; // Hentikan proses submit
            }
            return true; // Kalau localhost, izinkan form dikirim normal
        }
    </script>
    
>>>>>>> Stashed changes
</body>
</html>
