<?php
/**
 * =============================================================================
 * logout.php — Proses Penghancuran Sesi & Redirect ke Login
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menangani proses logout pengguna secara aman dan lengkap dari sistem
 *   CariGalon. Proses logout tidak hanya sekadar menghapus variabel sesi, tetapi
 *   juga menghancurkan cookie sesi yang ada di browser pengguna untuk mencegah
 *   sesi zombie (session yang masih valid di sisi klien meski sudah dihapus di server).
 *   Setelah sesi lama dihancurkan, sesi baru dibuat untuk menyimpan flash message
 *   konfirmasi logout, kemudian pengguna diarahkan kembali ke halaman login.php.
 *
 * FUNGSI UTAMA:
 *   - Menghapus seluruh data sesi aktif ($_SESSION = [])
 *   - Menghancurkan cookie sesi di browser pengguna (setcookie dengan waktu kedaluwarsa di masa lalu)
 *   - Memanggil session_destroy() untuk menghapus data sesi di sisi server
 *   - Memulai sesi baru yang bersih untuk menampung flash message konfirmasi
 *   - Menyetel flash message sukses "Anda telah berhasil keluar dari dashboard"
 *   - Meredirect pengguna ke login.php setelah proses logout selesai
 *
 * ALUR KERJA (FLOW):
 *   1. Sesi dimulai jika belum aktif
 *   2. Seluruh data $_SESSION dikosongkan dengan mengganti dengan array kosong
 *   3. Jika cookie sesi aktif (session.use_cookies), cookie dihapus dari browser
 *      dengan menetapkan waktu kedaluwarsa di masa lalu (time() - 42000)
 *   4. session_destroy() dipanggil untuk menghancurkan data sesi di server
 *   5. Sesi baru dimulai kembali (untuk keperluan flash message)
 *   6. db.php di-include untuk mengakses fungsi set_flash()
 *   7. Flash message sukses di-set ke sesi baru
 *   8. Header redirect ke login.php dieksekusi dan script dihentikan
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - (tidak ada; file ini hanya mengelola sesi dan cookie, tidak ada query database)
 *
 * VARIABEL PENTING:
 *   - $_SESSION              : Dikosongkan sepenuhnya di awal proses logout
 *   - $params                : Array parameter cookie sesi (path, domain, secure, httponly)
 *   - session_get_cookie_params() : Fungsi PHP untuk mendapatkan konfigurasi cookie sesi saat ini
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Di-include setelah sesi baru dimulai, untuk mengakses fungsi set_flash()
 *
 * AKSES:
 *   Dapat diakses oleh semua pengguna yang sedang login (staff maupun admin).
 *   Biasanya dipanggil melalui tautan "Keluar (Logout)" di sidebar navigasi.
 *
 * CATATAN PENGEMBANG:
 *   Urutan operasi logout (kosongkan → hapus cookie → destroy → sesi baru) sangat
 *   penting untuk keamanan. Jangan mengubah urutan ini tanpa memahami implikasinya.
 *   Nilai 42000 pada penghapusan cookie adalah konvensi umum untuk memastikan cookie
 *   kedaluwarsa di masa lalu tanpa bergantung pada sinkronisasi jam server-klien.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// logout.php

// ===================================================
// LANGKAH 1 — Pastikan sesi sudah aktif sebelum diproses
// ===================================================
// Kita tidak bisa menghapus sesi kalau sesinya belum "dibuka" terlebih dahulu.
// Seperti mau mengosongkan tas: tasnya harus dibuka dulu baru bisa dikosongkan.
// PHP_SESSION_NONE berarti sesi belum dimulai sama sekali
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Buka/mulai sesi supaya bisa kita akses dan hapus
}

// ===================================================
// LANGKAH 2 — Kosongkan semua data yang tersimpan di sesi
// ===================================================
// $_SESSION adalah array PHP yang menyimpan data login pengguna.
// Dengan mengisinya dengan array kosong [], semua data (nama, ID, role, dll.) terhapus.
// Ini seperti mengosongkan isi tas sebelum membuang tasnya.
//
// KENAPA ini tidak cukup saja (tanpa langkah berikutnya)?
//   Karena "tas sesinya" (file sesi di server) masih ada, hanya isinya kosong.
//   Kita perlu menghancurkan tas-nya juga agar benar-benar aman.
$_SESSION = []; // Kosongkan semua data sesi — login, nama, role, semuanya hilang

// ===================================================
// LANGKAH 3 — Hapus cookie sesi dari browser pengguna
// ===================================================
// Sesi PHP menggunakan "cookie" di browser untuk mengidentifikasi pengguna.
// Cookie ini seperti "kartu anggota" yang tersimpan di browser.
// Kalau kita hanya hapus sesi di server tapi tidak hapus cookie-nya,
// browser masih punya "kartu anggota" — meskipun kartu itu sudah tidak valid.
// Ini bisa menyebabkan masalah atau kebingungan, jadi kita hapus juga cookie-nya.

// Destroy session cookie if set
// ini_get("session.use_cookies") = cek apakah PHP dikonfigurasi pakai cookie untuk sesi
// (hampir selalu true di setup normal)
if (ini_get("session.use_cookies")) {
    // Ambil informasi konfigurasi cookie sesi saat ini (path, domain, secure, httponly)
    // Kita perlu info ini supaya cookie yang kita hapus cocok dengan yang ada di browser
    $params = session_get_cookie_params();

    // setcookie() = kirim instruksi ke browser untuk membuat/mengubah cookie
    // Di sini kita pakai trik: set cookie dengan waktu kedaluwarsa di MASA LALU
    // Browser akan otomatis menghapus cookie yang sudah kedaluwarsa
    setcookie(
        session_name(),       // Nama cookie sesi (biasanya "PHPSESSID")
        '',                   // Isi cookie dikosongkan
        time() - 42000,       // Waktu kedaluwarsa = sekarang dikurangi 42000 detik (sudah lewat)
                              // 42000 detik ≈ 11.6 jam yang lalu — dipastikan sudah kadaluarsa
        $params["path"],      // Path cookie (biasanya "/")
        $params["domain"],    // Domain cookie (misal "localhost" atau "carigalon.com")
        $params["secure"],    // Apakah cookie hanya untuk HTTPS? (true/false)
        $params["httponly"]   // Apakah cookie tidak bisa diakses JavaScript? (true = lebih aman)
    );
}

// ===================================================
// LANGKAH 4 — Hancurkan data sesi di server
// ===================================================
// session_destroy() menghapus FILE sesi yang ada di server (bukan cuma isinya).
// Ini seperti membakar "tasnya" setelah dikosongkan isinya.
// Setelah ini, ID sesi yang lama sudah tidak valid sama sekali di server.
//
// KENAPA perlu session_destroy() kalau sudah $_SESSION = []?
//   $_SESSION = [] hanya mengosongkan variabel PHP di memori (RAM).
//   session_destroy() menghapus file penyimpanan sesi di hard disk server.
//   Keduanya diperlukan untuk logout yang benar-benar bersih dan aman.
session_destroy();

// ===================================================
// LANGKAH 5 — Mulai sesi BARU yang bersih
// ===================================================
// Setelah session_destroy(), kita tidak bisa langsung tulis $_SESSION lagi.
// Kita perlu memulai sesi baru (dengan ID baru yang aman) untuk menyimpan
// flash message konfirmasi logout yang akan ditampilkan di halaman login.
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Buat sesi baru yang bersih — ID sesi sudah berbeda dari sebelumnya
}

// ===================================================
// LANGKAH 6 — Muat db.php untuk akses fungsi set_flash()
// ===================================================
// db.php di-include SETELAH sesi baru dimulai (bukan sebelumnya),
// karena db.php juga memanggil session_start() — kita tidak mau konflik.
// Setelah baris ini, fungsi set_flash() tersedia untuk dipakai.
// Set flash message on the new session
require_once __DIR__ . '/db.php';

// ===================================================
// LANGKAH 7 — Simpan pesan konfirmasi logout ke sesi baru
// ===================================================
// Simpan pesan sukses ke sesi baru supaya muncul sekali di halaman login
// sebagai konfirmasi bahwa proses logout berhasil
set_flash('success', 'Anda telah berhasil keluar dari dashboard.');

// ===================================================
// LANGKAH 8 — Kirim pengguna ke halaman login
// ===================================================
// header('Location: ...') mengirim instruksi ke browser untuk berpindah halaman
// Ini seperti papan penunjuk arah: "sekarang pergi ke halaman login"
header('Location: login.php'); // Kirim pengguna ke halaman login setelah logout selesai
exit; // Hentikan semua kode PHP di sini — jangan eksekusi apapun lagi setelah redirect
