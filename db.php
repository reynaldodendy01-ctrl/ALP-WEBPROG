<?php
// ─── CariGalon — Database Connection (Koneksi Database) ───────────────────────────────────────
// File ini ibarat "kunci rumah" atau "surat izin" agar file-file PHP lain (seperti login, laporan, dispenser)
// bisa berinteraksi (menyimpan, mengambil, mengubah, menghapus) data di MySQL.

// ─── Timezone: WIB (UTC+7) ──────────────────────────────────────────────────
// Kita mengatur zona waktu ke WIB (Waktu Indonesia Barat) agar waktu laporan yang tersimpan sesuai dengan jam lokal kita.
date_default_timezone_set('Asia/Jakarta');

// Di sini kita mendefinisikan "Konstanta" (nilai tetap yang tidak akan berubah).
// Ini adalah "alamat", "nama database", "username", dan "password" dari server XAMPP kamu.
define('DB_HOST', 'localhost'); // Server database berada di komputer yang sama (localhost)
define('DB_NAME', 'carigalon'); // Nama database yang dituju
define('DB_USER', 'root');      // Username bawaan XAMPP untuk masuk ke MySQL
define('DB_PASS', '');          // Password bawaan XAMPP biasanya kosong (tidak ada password)
define('DB_CHAR', 'utf8mb4');   // Format teks standar yang mendukung emoji dan huruf unik


try {
    // Di sinilah proses "koneksi" (login) ke database sebenarnya terjadi.
    // PDO (PHP Data Objects) adalah fitur bawaan PHP untuk ngobrol ke database secara aman.
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR, // Menyambungkan alamat dan nama database
        DB_USER, // Memasukkan username 'root'
        DB_PASS, // Memasukkan password ''
        [
            // Ini adalah "aturan" tambahan untuk PDO:
            // 1. ERRMODE_EXCEPTION: Kalau ada error di database, tolong kasih tau kita.
            // 2. FETCH_ASSOC: Kalau ngambil data, bentuknya dikelompokkan pakai nama kolom (misal: $data['Nama']).
            // 3. EMULATE_PREPARES: Matikan fungsi pura-pura dari PHP biar lebih aman dari hacker (SQL Injection).
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    // Kita pastikan zona waktu mesin MySQL-nya juga di-set ke WIB (+07:00).
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    // Kalau ada yang salah (misalnya nama database salah, atau MySQL belum nyala),
    // PHP akan masuk ke blok "catch" ini, mematikan program, lalu memunculkan tulisan error.
    die(json_encode([
        'error'   => true,
        'message' => 'Koneksi database gagal: ' . $e->getMessage(),
    ]));
}

// ─── Flash Message Helpers (Fungsi Bantuan Pesan Singkat) ──────────────────────────────────────────────────
// Flash Message adalah pesan notifikasi kecil yang muncul sekali lalu hilang saat kita refresh.
// (Contoh: pesan warna hijau "Dispenser berhasil ditambahkan").
// Ini memanfaatkan 'SESSION' dari PHP.

// Jika sistem PHP belum memulai sesi, kita harus memulainya di sini
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi ini bertugas UNTUK MENYIMPAN pesan ke dalam "Kantung Sesi" (Session).
// Butuh 2 hal: tipe pesan (misal: 'success' atau 'error') dan isi pesannya.
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Fungsi ini bertugas UNTUK MENGAMBIL pesan dari "Kantung Sesi".
// Setelah pesan diambil, isi "kantung"-nya langsung dihapus (unset), supaya pesan tidak muncul terus-menerus.
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null; // Kalau nggak ada pesan, balikin nilai kosong.
}

// Fungsi ini bertugas UNTUK MENAMPILKAN HTML (warna hijau/merah) di layar website kita
// berdasarkan tipe pesan yang diambil dari fungsi get_flash() di atas.
function render_flash(): void {
    $flash = get_flash(); // Ambil pesan
    if (!$flash) return;  // Kalau nggak ada pesan, tidak usah melakukan apa-apa

    // Tentukan warna dan ikon berdasarkan jenis tipe:
    // Kalau 'success' (sukses) warnanya hijau muda emerald. Kalau selain itu (misal 'error'), warnanya merah muda.
    $color = $flash['type'] === 'success'
        ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
        : 'bg-red-50 border-red-300 text-red-800';
    $icon  = $flash['type'] === 'success' ? 'check_circle' : 'error';
    
    // Cetak desain HTML kotaknya ke halaman.
    echo <<<HTML
    <div class="flex items-center gap-3 px-5 py-4 mb-6 rounded-2xl border {$color} text-sm font-medium">
        <span class="material-symbols-outlined text-[20px]">{$icon}</span>
        <span>{$flash['message']}</span>
    </div>
    HTML;
}

// ─── Sanitize Helper (Fungsi Bantuan Keamanan) ────────────────────────────────────────────────────────
// Fungsi pendek ini DIBUAT UNTUK KEAMANAN. Namanya fungsi 'h' (singkatan dari htmlspecialchars).
// Fungsinya "Membersihkan" huruf atau karakter aneh yang diketik dari form, agar tidak dianggap kode HTML.
// Ini untuk mencegah virus / kode jahat disisipkan ke dalam website (mencegah XSS Attack).
function h(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

// ─── Photo URL Path Resolver Helper (Bantuan Alamat Foto) ─────────────────────────────────────────
// Fungsi ini berguna kalau kita mau menampilkan gambar.
// Fungsinya menghitung lokasi pasti dari file gambar, apakah harus dikasih '../' atau './'
// agar jalurnya tidak berantakan waktu halamannya ada di dalam folder.
function get_foto_url(?string $path): string {
    if (!$path) {
        return ''; // Kalau tidak ada foto, balikkan teks kosong
    }
    // Jika fotonya dari link internet (misal: http://google.com/foto.jpg)
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }
    
    // Membaca alamat asli di mana project ini berada
    $rootDir = realpath(__DIR__);
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    
    $relativePrefix = '';
    // Jika file PHP yang sedang dijalankan berada di dalam folder (misal: /laporan/index.php)
    if ($scriptDir !== $rootDir) {
        $relativePrefix = '../'; // Mundur 1 langkah (keluar dari folder)
    } else {
        $relativePrefix = './'; // Tetap di folder yang sama
    }
    
    // Gabungkan dengan nama fotonya.
    return $relativePrefix . ltrim($path, '/\\');
}
