<?php
/**
 * =============================================================================
 * db.php — Konfigurasi Koneksi Database & Helper Functions Global
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan inti dari seluruh sistem CariGalon yang bertanggung jawab
 *   membangun koneksi ke database MySQL menggunakan PDO (PHP Data Objects) dengan
 *   pengaturan keamanan dan performa terbaik. Selain koneksi, file ini juga
 *   menyediakan sekumpulan fungsi pembantu (helper) yang digunakan di seluruh
 *   halaman sistem, mulai dari manajemen flash message, sanitasi output HTML,
 *   hingga resolver path URL foto. File ini wajib di-include (require_once) oleh
 *   hampir semua file PHP lainnya di proyek ini sebelum melakukan operasi apapun.
 *
 * FUNGSI UTAMA:
 *   - Menginisialisasi koneksi PDO ke database MySQL 'carigalon'
 *   - Mengatur timezone PHP dan MySQL session ke WIB (UTC+7)
 *   - Menyediakan fungsi set_flash() untuk menyimpan pesan notifikasi sementara di sesi
 *   - Menyediakan fungsi get_flash() untuk mengambil & menghapus flash message dari sesi
 *   - Menyediakan fungsi render_flash() untuk merender alert HTML dari flash message
 *   - Menyediakan fungsi h() sebagai sanitizer output untuk mencegah XSS
 *   - Menyediakan fungsi get_foto_url() untuk me-resolve path relatif/absolut foto
 *
 * ALUR KERJA (FLOW):
 *   1. Timezone PHP di-set ke 'Asia/Jakarta' (WIB)
 *   2. Konstanta koneksi (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHAR) didefinisikan
 *   3. Objek PDO dibuat dengan mode error EXCEPTION dan fetch mode ASSOC
 *   4. MySQL session timezone di-set ke '+07:00' via query SET time_zone
 *   5. Jika koneksi gagal, sistem mati dan mengembalikan JSON error
 *   6. Sesi PHP dimulai jika belum aktif (diperlukan oleh flash message)
 *   7. Fungsi-fungsi helper (set_flash, get_flash, render_flash, h, get_foto_url) didefinisikan
 *      dan siap digunakan oleh semua halaman yang meng-include file ini
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - (tidak ada query langsung; file ini hanya membuka koneksi $pdo)
 *
 * VARIABEL PENTING:
 *   - $pdo            : Objek PDO global yang digunakan oleh seluruh file PHP lain
 *   - DB_HOST         : Host database (default: 'localhost')
 *   - DB_NAME         : Nama database (default: 'carigalon')
 *   - DB_USER         : Username database (default: 'root')
 *   - DB_PASS         : Password database (default: '' / kosong untuk XAMPP)
 *   - DB_CHAR         : Character set koneksi (default: 'utf8mb4')
 *   - $_SESSION['flash'] : Array sementara yang menyimpan pesan flash (type + message)
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - (tidak ada; file ini adalah dependency utama, bukan yang di-include)
 *
 * AKSES:
 *   File ini tidak diakses langsung oleh pengguna. Di-include secara server-side
 *   oleh semua halaman PHP yang memerlukan koneksi database atau helper functions.
 *
 * CATATAN PENGEMBANG:
 *   Ubah nilai DB_USER dan DB_PASS sesuai konfigurasi server lokal atau produksi.
 *   Jangan commit kredensial database ke repository publik. Pertimbangkan penggunaan
 *   file .env untuk menyimpan konfigurasi sensitif di lingkungan produksi.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
// ─── CariGalon — Database Connection ───────────────────────────────────────
// Ubah kredensial sesuai setup lokal kamu (default: XAMPP)

<<<<<<< Updated upstream
define('DB_HOST', 'localhost');
define('DB_NAME', 'carigalon');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');
=======
// ===================================================
// TIMEZONE — Atur zona waktu PHP ke WIB (Jakarta)
// ===================================================
// Ini penting supaya semua tanggal/jam di PHP pakai WIB (UTC+7),
// bukan waktu server default yang mungkin UTC atau zona lain.
date_default_timezone_set('Asia/Jakarta');

// ===================================================
// KONSTANTA DATABASE — Informasi untuk koneksi ke MySQL
// ===================================================
// define() membuat "konstanta" — nilainya tetap dan tidak bisa diubah selama program berjalan.
// Ini lebih aman dari variabel biasa ($var) karena tidak bisa ditimpa secara tidak sengaja.
>>>>>>> Stashed changes

define('DB_HOST', 'localhost'); // Alamat server database — 'localhost' artinya ada di komputer yang sama
define('DB_NAME', 'carigalon'); // Nama database yang akan dipakai di MySQL
define('DB_USER', 'root');      // Username MySQL — default XAMPP adalah 'root'
define('DB_PASS', '');          // Password MySQL — default XAMPP adalah kosong (tidak ada password)
define('DB_CHAR', 'utf8mb4');   // Encoding karakter — utf8mb4 mendukung semua karakter termasuk emoji

// ===================================================
// KONEKSI PDO — Hubungkan PHP ke database MySQL
// ===================================================
// PDO (PHP Data Objects) adalah cara modern PHP untuk terhubung ke database.
// Keunggulan PDO dibanding cara lama (mysql_connect):
//   1. Mendukung banyak jenis database (MySQL, PostgreSQL, SQLite, dll.)
//   2. Punya "prepared statement" yang mencegah SQL Injection
//   3. Penanganan error lebih rapi dengan sistem Exception
//
// try-catch di sini artinya:
//   - try  = "coba jalankan kode ini..."
//   - catch = "...kalau ada error, tangkap dan jalankan ini sebagai gantinya"
// Ini mencegah program crash total dan memberi pesan error yang informatif.
try {
    // Buat objek PDO baru — ini seperti "membuka pintu" ke database
    $pdo = new PDO(
        // DSN (Data Source Name): string yang menjelaskan "di mana" databasenya
        // Format: "mysql:host=HOST;dbname=NAMADB;charset=ENCODING"
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR,
        DB_USER, // Username yang tadi didefinisikan di atas
        DB_PASS, // Password yang tadi didefinisikan di atas
        [
            // ERRMODE_EXCEPTION: kalau ada error SQL, lempar Exception (bisa di-catch)
            // Tanpa ini, error SQL diam-diam dan susah di-debug
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // FETCH_ASSOC: hasil query dikembalikan sebagai array dengan nama kolom sebagai key
            // Contoh: $row['Nama'] bukan $row[0] — jauh lebih mudah dibaca!
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // EMULATE_PREPARES = false: pakai "prepared statement" asli dari MySQL,
            // bukan simulasi PHP — ini lebih aman dari SQL Injection
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
<<<<<<< Updated upstream
=======

    // Set MySQL session timezone ke WIB (UTC+7)
    // Ini memastikan fungsi tanggal/waktu di dalam MySQL juga pakai WIB,
    // sinkron dengan timezone PHP yang sudah di-set di atas
    $pdo->exec("SET time_zone = '+07:00'");

>>>>>>> Stashed changes
} catch (PDOException $e) {
    // Kalau koneksi gagal (misal MySQL tidak jalan, password salah, dll.),
    // hentikan semua proses dengan die() dan tampilkan pesan error dalam format JSON.
    // Format JSON dipilih karena file ini bisa dipanggil dari halaman HTML maupun API.
    die(json_encode([
        'error'   => true,
        'message' => 'Koneksi database gagal: ' . $e->getMessage(), // getMessage() = ambil detail pesan errornya
    ]));
}

// ===================================================
// INISIALISASI SESI — Mulai sesi PHP kalau belum aktif
// ===================================================
// $_SESSION adalah "tas" penyimpanan data sementara milik setiap pengguna.
// Data di sesi tersimpan di server dan bisa diakses di semua halaman selama sesi aktif.
// session_status() === PHP_SESSION_NONE artinya "sesi belum dimulai"
// — kalau belum, kita mulai dulu sebelum pakai $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Mulai sesi PHP — wajib dipanggil sebelum bisa baca/tulis $_SESSION
}

// ===================================================
// FUNGSI set_flash() — Simpan pesan notifikasi sementara ke sesi
// ===================================================
// "Flash message" adalah pesan yang hanya muncul SEKALI, lalu hilang.
// Contoh penggunaan: setelah hapus data berhasil, simpan pesan "Data berhasil dihapus",
// lalu redirect ke halaman lain — pesan itu akan muncul di halaman tujuan satu kali saja.
//
// Parameter:
//   $type    = jenis pesan: 'success' (hijau) atau 'error' (merah)
//   $message = teks pesan yang akan ditampilkan ke pengguna
function set_flash(string $type, string $message): void {
    // Simpan pesan ke sesi sebagai array dengan dua kunci: 'type' dan 'message'
    // Contoh hasil: $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data disimpan!']
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// ===================================================
// FUNGSI get_flash() — Ambil dan hapus pesan flash dari sesi
// ===================================================
// Fungsi ini mengambil pesan flash yang tersimpan, lalu LANGSUNG MENGHAPUSNYA dari sesi.
// Ini yang membuat flash message hanya muncul sekali — setelah diambil, langsung dihapus.
// Return: array ['type'=>..., 'message'=>...] kalau ada pesan, atau null kalau tidak ada.
function get_flash(): ?array { // Tanda "?" berarti bisa return null (tidak selalu ada pesan)
    if (isset($_SESSION['flash'])) { // Cek dulu apakah ada pesan flash yang tersimpan
        $flash = $_SESSION['flash']; // Ambil pesannya, simpan di variabel lokal
        unset($_SESSION['flash']);   // Hapus dari sesi supaya tidak muncul lagi
        return $flash;               // Kembalikan data pesan ke pemanggil fungsi
    }
    return null; // Tidak ada pesan flash — kembalikan null
}

// ===================================================
// FUNGSI render_flash() — Tampilkan pesan flash sebagai HTML
// ===================================================
// Fungsi ini memanggil get_flash() untuk ambil pesan, lalu meng-output-nya sebagai
// komponen HTML dengan styling Tailwind CSS yang cantik.
// Cukup panggil render_flash() di dalam HTML dan pesan otomatis muncul (atau tidak muncul
// kalau memang tidak ada pesan).
function render_flash(): void {
    $flash = get_flash(); // Ambil pesan flash (sekaligus menghapusnya dari sesi)
    if (!$flash) return;  // Kalau tidak ada pesan, langsung keluar dari fungsi — tidak cetak apapun

    // Tentukan warna latar berdasarkan tipe pesan:
    // 'success' → warna hijau (emerald), selain itu → warna merah (red)
    $color = $flash['type'] === 'success'
        ? 'bg-emerald-50 border-emerald-300 text-emerald-800' // Hijau untuk pesan sukses
        : 'bg-red-50 border-red-300 text-red-800';            // Merah untuk pesan error

    // Tentukan ikon Material Icons berdasarkan tipe pesan
    $icon  = $flash['type'] === 'success' ? 'check_circle' : 'error'; // centang hijau atau tanda silang merah

    // Cetak HTML alert langsung ke halaman menggunakan Heredoc (<<<HTML ... HTML)
    // Heredoc adalah cara menulis string panjang di PHP tanpa perlu escape tanda kutip
    echo <<<HTML
    <div class="flex items-center gap-3 px-5 py-4 mb-6 rounded-2xl border {$color} text-sm font-medium">
        <span class="material-symbols-outlined text-[20px]">{$icon}</span>
        <span>{$flash['message']}</span>
    </div>
    HTML;
}

// ===================================================
// FUNGSI h() — Sanitasi output untuk mencegah XSS
// ===================================================
// XSS (Cross-Site Scripting) adalah serangan di mana hacker menyisipkan kode JavaScript
// berbahaya ke dalam halaman web melalui input pengguna.
// Fungsi h() mengubah karakter berbahaya menjadi karakter aman:
//   < menjadi &lt;   (supaya tidak dianggap tag HTML)
//   > menjadi &gt;
//   " menjadi &quot;
//   ' menjadi &#039;
//
// SELALU gunakan h() saat menampilkan data dari database atau input pengguna ke HTML!
// Contoh: echo h($nama_pengguna); — BUKAN echo $nama_pengguna;
function h(mixed $val): string {
    // htmlspecialchars() = fungsi PHP bawaan untuk mengubah karakter berbahaya jadi aman
    // ENT_QUOTES = konversi tanda kutip tunggal (') DAN ganda (")
    // 'UTF-8'    = gunakan encoding UTF-8 (mendukung huruf Indonesia dan karakter internasional)
    // $val ?? '' = kalau $val null/tidak ada, gunakan string kosong supaya tidak error
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

// ===================================================
// FUNGSI get_foto_url() — Bangun URL yang benar untuk foto
// ===================================================
// Foto bisa disimpan dengan path yang berbeda-beda (ada yang path lokal, ada yang URL penuh).
// Fungsi ini mendeteksi jenis path-nya dan mengembalikan URL yang bisa langsung dipakai
// di tag <img src="...">, tidak peduli dari folder mana file PHP dijalankan.
//
// Parameter:
//   $path = path foto yang tersimpan di database (bisa null, URL lengkap, atau path relatif)
// Return: URL lengkap atau relatif yang bisa dipakai di browser
function get_foto_url(?string $path): string { // Tanda "?" berarti $path boleh bernilai null
    if (!$path) {
        return ''; // Kalau path kosong atau null, kembalikan string kosong (tidak ada foto)
    }

    // Cek apakah $path sudah berupa URL lengkap (misalnya "https://example.com/foto.jpg")
    // filter_var() dengan FILTER_VALIDATE_URL = fungsi PHP untuk validasi format URL
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path; // Kalau sudah URL lengkap, langsung kembalikan apa adanya
    }

    // Kalau bukan URL lengkap, berarti path relatif — kita hitung prefix yang benar
    $rootDir   = realpath(__DIR__);                          // Folder tempat db.php berada (root proyek)
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'])); // Folder file PHP yang sedang dijalankan

    // Bandingkan dua folder: apakah file yang berjalan ada di subfolder atau di root?
    $relativePrefix = '';
    if ($scriptDir !== $rootDir) {
        // File yang berjalan ada di SUBFOLDER (misal /dashboard/index.php)
        // — perlu naik satu level ke atas dengan '../'
        $relativePrefix = '../';
    } else {
        // File yang berjalan ada di ROOT (misal /index.php atau /login.php)
        // — cukup pakai './' yang artinya "folder saat ini"
        $relativePrefix = './';
    }

    // Gabungkan prefix dengan path foto
    // ltrim($path, '/\\') = hapus karakter '/' atau '\' di depan path supaya tidak dobel
    // Contoh hasil: '../uploads/foto.jpg' atau './uploads/foto.jpg'
    return $relativePrefix . ltrim($path, '/\\');
}
