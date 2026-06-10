<?php
/**
 * =============================================================================
 * delete.php — Proses Penghapusan Permanen Laporan Kendala (Khusus Super Admin)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan action script (tanpa UI/tampilan) yang memproses permintaan
 *   penghapusan permanen data laporan kendala dari tabel water_report di database.
 *   File ini hanya menerima request melalui metode POST untuk mencegah penghapusan
 *   tidak sengaja melalui URL langsung. Akses dibatasi hanya untuk Super Admin;
 *   pengguna dengan peran Staff akan diredirect dengan pesan error. Setelah proses
 *   selesai (berhasil atau gagal), pengguna selalu diredirect kembali ke index.php.
 *
 * FUNGSI UTAMA:
 *   - Memeriksa sesi login pengguna; redirect ke halaman login jika belum login.
 *   - Memblokir akses dari pengguna berperan 'Staff' (hanya Super Admin yang boleh menghapus).
 *   - Menerima ID laporan dari data form POST (field 'id').
 *   - Menjalankan query DELETE pada tabel water_report berdasarkan WaterReport_ID.
 *   - Menangani error database menggunakan try-catch PDOException dan menampilkan
 *     pesan error yang sesuai melalui flash message.
 *   - Meredirect pengguna ke index.php setelah proses selesai dengan flash message
 *     sukses atau gagal.
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk mendapatkan koneksi PDO dan helper functions.
 *   2. Sesi diperiksa; jika belum login, redirect ke ../login.php.
 *   3. Peran pengguna diperiksa; jika 'Staff', flash error diset dan redirect ke index.php.
 *   4. Metode request diperiksa; hanya POST yang diproses (GET diabaikan, langsung redirect).
 *   5. ID laporan diambil dari $_POST['id'] dan dikonversi ke integer.
 *   6. Query DELETE dieksekusi menggunakan prepared statement PDO dengan binding parameter.
 *   7. Jika berhasil, flash message sukses diset.
 *   8. Jika terjadi PDOException, flash message error diset dengan detail pesan exception.
 *   9. Redirect ke index.php dilakukan tanpa kondisi (selalu redirect setelah proses).
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - water_report : Tabel yang di-DELETE berdasarkan WaterReport_ID.
 *
 * VARIABEL PENTING:
 *   - $id : Integer ID laporan yang akan dihapus, diambil dari $_POST['id'].
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash()).
 *
 * AKSES:
 *   Hanya Super Admin yang sudah login. Pengguna dengan peran 'Staff' akan
 *   diredirect ke index.php dengan pesan "Akses ditolak". Pengguna yang belum
 *   login akan diredirect ke halaman login.
 *
 * CATATAN PENGEMBANG:
 *   - File ini tidak memiliki tampilan HTML; murni sebagai action handler.
 *   - Penghapusan bersifat PERMANEN (hard delete); tidak ada fitur soft delete atau
 *     recycle bin. Pertimbangkan menambahkan konfirmasi penghapusan dan soft delete
 *     di masa mendatang untuk keamanan data.
 *   - Karena menggunakan CASCADE DELETE di database (jika dikonfigurasi), record terkait
 *     di tabel staff_dispenser_assignment mungkin ikut terhapus secara otomatis.
 *   - Tombol "Hapus" di index.php sudah dilengkapi konfirmasi JavaScript (confirm dialog)
 *     sebagai lapisan perlindungan pertama sebelum request POST ini dieksekusi.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce authentication
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Enforce admin-only access for deleting reports
if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    set_flash('error', 'Akses ditolak: Hanya Super Admin yang dapat menghapus laporan secara langsung.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM water_report WHERE WaterReport_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Laporan kendala berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus laporan: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
