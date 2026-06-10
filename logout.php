<?php
// logout.php
// File ini bertugas untuk MENGELUARKAN staff (LOGOUT) dan menghapus ingatannya dari server.

// 1. Mulai sesi jika belum dimulai (Kita harus tau sesi siapa yang mau dihapus)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kosongkan semua data memori sesi (Hapus nama, email, role, id dari memori)
$_SESSION = [];

// 3. Hapus "Cookie" Sesi dari browser pengguna.
// Cookie adalah file kecil di browser yang mengingat kode sesi.
// Kita ubah waktu berlakunya mundur ke masa lalu (time() - 42000) supaya browser otomatis menghapusnya.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan sesinya secara total dari server PHP.
session_destroy();

// 5. Kita butuh menampilkan pesan "Berhasil Keluar" di halaman login nanti.
// Karena sesinya baru saja dihancurkan, kita tidak bisa pakai sesi yang lama.
// Jadi kita mulai sesi BARU yang bersih...
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lalu panggil fungsi dari db.php untuk menyimpan pesan flash ke sesi yang baru ini.
require_once __DIR__ . '/db.php';
set_flash('success', 'Anda telah berhasil keluar dari dashboard.');

// 6. Lempar kembali pengguna ke halaman form login
header('Location: login.php');
exit; // Pastikan proses berhenti di sini
