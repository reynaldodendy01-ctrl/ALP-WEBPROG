<?php
/**
 * =============================================================================
 * login_process.php — Proses Autentikasi & Inisialisasi Sesi Login
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan backend processor yang menangani logika autentikasi pengguna
 *   ketika formulir login di login.php di-submit. File ini tidak menghasilkan output
 *   HTML apapun; seluruh kerjanya adalah memvalidasi data input, mencocokkan kredensial
 *   ke database menggunakan prepared statement PDO, dan jika berhasil, menginisialisasi
 *   variabel sesi untuk pengguna yang terautentikasi sebelum melakukan redirect.
 *   Password yang tersimpan di database menggunakan format hash bcrypt dan diverifikasi
 *   menggunakan fungsi password_verify() bawaan PHP yang aman.
 *
 * FUNGSI UTAMA:
 *   - Memvalidasi bahwa email dan password tidak kosong sebelum query ke database
 *   - Mencari data staff berdasarkan email di tabel maintenance_staff menggunakan prepared statement
 *   - Memverifikasi password input dengan hash bcrypt yang tersimpan menggunakan password_verify()
 *   - Meregenerasi session ID (session_regenerate_id) untuk mencegah session fixation attack
 *   - Menyimpan data staff (ID, nama, email, role) ke dalam variabel sesi $_SESSION
 *   - Menetapkan flash message selamat datang dan meredirect ke dashboard jika login berhasil
 *   - Meredirect kembali ke login.php dengan pesan error yang sesuai jika login gagal
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk mendapatkan koneksi $pdo dan helper functions
 *   2. Jika sesi sudah aktif (staff_logged_in = true), langsung redirect ke dashboard
 *   3. Email dan password diambil dari $_POST dan divalidasi (tidak boleh kosong)
 *   4. Query SELECT ke tabel maintenance_staff dengan filter Email menggunakan prepared statement
 *   5. Jika staff ditemukan dan password_verify() berhasil:
 *      a. session_regenerate_id(true) dipanggil untuk keamanan
 *      b. Variabel sesi staff_logged_in, staff_id, staff_name, staff_email, staff_role diisi
 *      c. Flash message sukses di-set, lalu redirect ke dashboard/index.php
 *   6. Jika gagal (email tidak ditemukan atau password salah): redirect ke login.php?error=...
 *   7. Jika terjadi PDOException: redirect ke login.php dengan pesan kesalahan sistem
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - maintenance_staff : Diquery untuk mencocokkan Email dan memverifikasi Password (bcrypt)
 *
 * VARIABEL PENTING:
 *   - $email                       : Email dari $_POST['email'], di-trim untuk keamanan
 *   - $password                    : Password plaintext dari $_POST['password']
 *   - $staff                       : Array asosiatif hasil fetch dari tabel maintenance_staff
 *   - $_SESSION['staff_logged_in'] : Boolean true jika autentikasi berhasil
 *   - $_SESSION['staff_id']        : Staff_ID dari database (digunakan sebagai identifier sesi)
 *   - $_SESSION['staff_name']      : Nama lengkap staff untuk ditampilkan di UI
 *   - $_SESSION['staff_email']     : Email staff yang sedang login
 *   - $_SESSION['staff_role']      : Role staff ('Admin' atau 'Staff') untuk kontrol akses
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database ($pdo) & helper functions (set_flash, dll.)
 *
 * AKSES:
 *   Hanya dapat diakses melalui metode POST dari form login.php. Akses langsung
 *   via GET tidak akan menghasilkan error tetapi tidak akan memproses apapun yang berarti.
 *
 * CATATAN PENGEMBANG:
 *   File ini tidak boleh diakses langsung dari browser. Pastikan semua password
 *   yang disimpan di database sudah di-hash menggunakan password_hash() dengan
 *   algoritma PASSWORD_DEFAULT (bcrypt). Jangan pernah menyimpan password plaintext.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INCLUDE FILE DB — Muat koneksi database dan fungsi helper
// ===================================================
// require_once berarti "sertakan file ini, dan hanya sekali saja"
// __DIR__ = folder tempat file ini berada, jadi tidak akan salah path
// Setelah baris ini, variabel $pdo dan fungsi set_flash() dll. sudah siap dipakai
require_once __DIR__ . '/db.php';

// ===================================================
// INISIALISASI SESI — Pastikan sesi sudah aktif
// ===================================================
// session_status() === PHP_SESSION_NONE berarti sesi belum dimulai
// Kita cek dulu sebelum session_start() supaya tidak error "session already started"
if (session_status() === PHP_SESSION_NONE) session_start();

// ===================================================
// CEK LOGIN GANDA — Kalau sudah login, langsung ke dashboard
// ===================================================
// Kalau pengguna sudah login (ada sesi 'staff_logged_in' bernilai true),
// tidak perlu login lagi — langsung lempar ke dashboard
if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    // Kirim pengguna ke halaman dashboard karena sudah terautentikasi
    header('Location: dashboard/index.php');
    exit; // Hentikan semua kode di bawah ini — jangan lanjut apapun
}

// ===================================================
// AMBIL DATA FORM — Baca email dan password dari form yang dikirim
// ===================================================
// $_POST adalah array yang berisi semua data yang dikirim dari <form method="POST">
// trim() = hapus spasi kosong di depan dan belakang teks (misal " admin@mail.com " → "admin@mail.com")
// ?? ''  = kalau kuncinya tidak ada di $_POST, gunakan string kosong sebagai default (hindari error)
$email    = trim($_POST['email'] ?? '');   // Ambil email dari form, bersihkan spasi di pinggir
$password = $_POST['password'] ?? '';      // Ambil password dari form (JANGAN di-trim — password boleh pakai spasi)

// ===================================================
// VALIDASI INPUT — Pastikan email dan password tidak kosong
// ===================================================
// empty() mengembalikan true kalau variabel kosong (''), null, atau 0
// Kita cek keduanya: kalau salah satu kosong, tolak dan suruh isi ulang
if (empty($email) || empty($password)) {
    // Redirect balik ke halaman login dengan pesan error di URL (?error=...)
    // urlencode() mengubah teks jadi format URL yang aman (spasi → %20, dll.)
    header('Location: login.php?error=' . urlencode('Email dan password wajib diisi.'));
    exit; // Hentikan proses — jangan lanjut ke query database
}

// ===================================================
// PROSES AUTENTIKASI — Cari pengguna di database dan verifikasi password
// ===================================================
// try-catch di sini untuk menangkap error database (misal koneksi putus)
// supaya pengguna mendapat pesan yang ramah, bukan error PHP yang menakutkan
try {
    // -----------------------------------------------
    // [SELECT] Cari staf berdasarkan email di database
    // -----------------------------------------------
    // prepare() = siapkan query dengan "placeholder" (:email) — ini adalah PREPARED STATEMENT
    // Prepared statement mencegah SQL Injection: input pengguna tidak bisa mengubah struktur query
    // Tanpa ini, hacker bisa mengetik "' OR 1=1 --" dan masuk tanpa password!
    $stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Email = :email LIMIT 1");
    // LIMIT 1 = ambil maksimal 1 baris saja — email harusnya unik, jadi cukup 1

    // execute() = jalankan query dengan nilai asli yang menggantikan placeholder :email
    // Array [':email' => $email] berarti isi :email dengan nilai variabel $email
    $stmt->execute([':email' => $email]);

    // fetch() = ambil satu baris hasil query sebagai array asosiatif
    // Contoh hasil: ['Staff_ID' => 3, 'Nama' => 'Budi', 'Email' => '...', 'Password' => '$2y$...', 'Role' => 'Staff']
    // Kalau tidak ditemukan, $staff bernilai false
    $staff = $stmt->fetch();

    // -----------------------------------------------
    // CEK KREDENSIAL — Cocokkan data dari database dengan input pengguna
    // -----------------------------------------------
    // Kondisi ini terdiri dari DUA pengecekan yang harus keduanya benar (&&):
    //   1. $staff          → apakah ada staf dengan email itu di database? (bukan false)
    //   2. password_verify → apakah password yang diketik cocok dengan hash di database?
    //
    // PENTING tentang password_verify():
    //   Password di database TIDAK disimpan dalam bentuk asli (plaintext).
    //   Disimpan dalam bentuk "hash" yang dienkripsi pakai algoritma bcrypt.
    //   Contoh hash bcrypt: "$2y$10$abcdefgh..."
    //   password_verify($passwordPolos, $hashDiDB) = fungsi PHP yang:
    //     - Mengenkripsi $passwordPolos dengan cara yang sama
    //     - Membandingkan hasilnya dengan $hashDiDB
    //     - Return true kalau cocok, false kalau tidak
    //   Ini jauh lebih aman dari membandingkan langsung: $password === $staff['Password']
    if ($staff && password_verify($password, $staff['Password'])) {

        // -----------------------------------------------
        // LOGIN BERHASIL — Buat sesi yang aman
        // -----------------------------------------------

        // session_regenerate_id(true) = ganti ID sesi dengan yang baru
        // Ini mencegah "Session Fixation Attack":
        //   tanpa ini, hacker yang tahu ID sesi sebelum login bisa mencuri sesi setelah login
        // Parameter 'true' = hapus file sesi lama di server (bersih-bersih)
        session_regenerate_id(true);

        // Simpan data staf ke dalam sesi supaya bisa dipakai di semua halaman
        $_SESSION['staff_logged_in'] = true;              // Tandai bahwa pengguna sudah login
        $_SESSION['staff_id']        = $staff['Staff_ID']; // Simpan ID staf — dipakai untuk query data spesifik staf ini
        $_SESSION['staff_name']      = $staff['Nama'];     // Simpan nama — ditampilkan di header/sidebar
        $_SESSION['staff_email']     = $staff['Email'];    // Simpan email — bisa dipakai untuk profil
        $_SESSION['staff_role']      = $staff['Role'];     // Simpan role ('Admin'/'Staff') — untuk kontrol hak akses

        // Simpan pesan selamat datang sebagai flash message (akan muncul sekali di dashboard)
        set_flash('success', 'Selamat datang kembali, ' . $staff['Nama'] . '!');

        // Kirim pengguna ke halaman dashboard setelah login berhasil
        header('Location: dashboard/index.php');
        exit; // Hentikan semua kode di bawah ini

    } else {
        // -----------------------------------------------
        // LOGIN GAGAL — Email tidak ditemukan atau password salah
        // -----------------------------------------------
        // Kita sengaja TIDAK memberi tahu apakah email atau password yang salah,
        // supaya hacker tidak bisa menebak-nebak mana yang benar
        header('Location: login.php?error=' . urlencode('Email atau password salah.'));
        exit; // Hentikan proses
    }

} catch (PDOException $e) {
    // -----------------------------------------------
    // ERROR DATABASE — Tangkap error tak terduga dari database
    // -----------------------------------------------
    // Kalau terjadi error database (bukan salah input pengguna),
    // redirect ke login dengan pesan generik — jangan tampilkan detail error ke pengguna
    // (detail error bisa bocorkan informasi sistem yang berbahaya)
    header('Location: login.php?error=' . urlencode('Terjadi kesalahan sistem.'));
    exit;
}
