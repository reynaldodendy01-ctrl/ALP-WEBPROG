<?php
/**
 * =============================================================================
 * delete.php — Proses Penghapusan Data Penugasan
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan handler backend (tanpa tampilan UI) yang bertugas memproses
 *   permintaan penghapusan satu record penugasan staf dari tabel
 *   staff_dispenser_assignment. File ini hanya menerima request HTTP POST untuk
 *   mencegah penghapusan tidak sengaja melalui klik link biasa. Setelah proses
 *   selesai (berhasil maupun gagal), pengguna akan di-redirect kembali ke halaman
 *   daftar penugasan (index.php) beserta flash message yang sesuai.
 *
 * FUNGSI UTAMA:
 *   - Menerima Assignment_ID melalui $_POST['id']
 *   - Menghapus record penugasan dari tabel staff_dispenser_assignment
 *   - Menampilkan flash message sukses atau error setelah proses selesai
 *   - Redirect otomatis ke index.php setelah eksekusi
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk mendapatkan koneksi $pdo dan helper set_flash()
 *   2. Dicek apakah request method adalah POST; selain POST diabaikan
 *   3. Assignment_ID dibaca dari $_POST['id'] dan dikonversi ke integer (intval)
 *   4. Query DELETE dijalankan menggunakan prepared statement dengan parameter binding
 *   5. Bila sukses: set_flash('success', ...) dipanggil
 *   6. Bila PDOException: set_flash('error', ...) dipanggil dengan pesan error
 *   7. Header Location redirect ke index.php dieksekusi lalu script dihentikan (exit)
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - staff_dispenser_assignment : Record penugasan yang dihapus berdasarkan Assignment_ID
 *
 * VARIABEL PENTING:
 *   - $id : Assignment_ID yang akan dihapus (integer dari $_POST['id'])
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash())
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin melalui form POST dari halaman index.php.
 *   Akses langsung via URL (GET) tidak akan memicu proses penghapusan.
 *
 * CATATAN PENGEMBANG:
 *   - Pastikan foreign key constraint di tabel refill_logs sudah diset ke
 *     ON DELETE CASCADE agar log refill terkait ikut terhapus secara otomatis,
 *     sesuai peringatan konfirmasi yang ditampilkan di halaman index.php
 *   - File ini tidak merender HTML apapun; seluruh output adalah redirect
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INISIALISASI — Muat koneksi database dan fungsi helper
// ===================================================
require_once __DIR__ . '/../db.php';

// ===================================================
// CEK METODE REQUEST — Hanya proses jika dikirim via POST
// ===================================================
// File ini HANYA boleh dijalankan dari form POST (tombol Hapus di index.php).
// Jika seseorang mengetik URL ini langsung di browser (GET request), blok ini dilewati.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil ID penugasan dari data form yang dikirim, pastikan berupa angka bulat
    $id = intval($_POST['id'] ?? 0); // intval() memastikan nilainya angka, bukan teks berbahaya

    try {
        // ===================================================
        // [DELETE] Hapus penugasan berdasarkan Assignment_ID
        // ===================================================
        // Prepared statement: :id adalah placeholder yang aman dari SQL Injection
        $stmt = $pdo->prepare("DELETE FROM staff_dispenser_assignment WHERE Assignment_ID = :id");
        $stmt->execute([':id' => $id]); // Jalankan query dengan nilai ID yang sudah divalidasi
        
        // Simpan pesan sukses ke sesi supaya bisa ditampilkan di halaman berikutnya
        set_flash('success', 'Penugasan berhasil dihapus.');
    } catch (PDOException $e) {
        // Kalau database error (misalnya ada constraint yang mencegah penghapusan)
        set_flash('error', 'Gagal menghapus penugasan: ' . $e->getMessage());
    }
}

// ===================================================
// REDIRECT — Kirim pengguna kembali ke halaman daftar penugasan
// ===================================================
// header('Location: ...') berfungsi seperti "pindah halaman otomatis"
header('Location: index.php');
exit; // Hentikan eksekusi PHP agar tidak ada kode yang jalan setelah redirect
