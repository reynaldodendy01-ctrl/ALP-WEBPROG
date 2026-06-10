<?php
/**
 * =============================================================================
 * ambil.php — Aksi Pengambilan / Klaim Laporan oleh Staf Maintenance
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah action script yang memungkinkan staf maintenance untuk
 *   "mengambil" atau mengklaim sebuah laporan kendala yang masih berstatus 'Pending'
 *   sehingga laporan tersebut resmi masuk ke daftar tugas staf yang bersangkutan.
 *   Saat seorang staf mengambil laporan, status laporan diubah menjadi 'Diproses'
 *   dan sebuah record penugasan baru (assignment) dibuat di tabel
 *   staff_dispenser_assignment yang menghubungkan staf dengan laporan tersebut.
 *   Seluruh proses dilakukan dalam satu transaksi database untuk menjaga konsistensi data.
 *
 * FUNGSI UTAMA:
 *   - Memvalidasi sesi login staf; redirect ke login.php jika belum terautentikasi.
 *   - Memvalidasi ID laporan dari parameter GET; redirect jika ID tidak valid.
 *   - Memastikan laporan yang akan diambil benar-benar ada dan berstatus 'Pending'.
 *   - Mengubah status laporan dari 'Pending' menjadi 'Diproses' di tabel water_report.
 *   - Membuat record penugasan baru di tabel staff_dispenser_assignment dengan status
 *     'On Progress', menghubungkan Staff_ID, Dispenser_ID, dan WaterReport_ID.
 *   - Mengeksekusi kedua operasi database di atas dalam satu transaksi atomik untuk
 *     memastikan keduanya berhasil atau keduanya dibatalkan (rollback) jika ada error.
 *   - Meredirect staf ke halaman dashboard setelah proses selesai.
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include; sesi diperiksa untuk autentikasi.
 *   2. ID laporan diambil dan divalidasi dari $_GET['id'].
 *   3. Data laporan di-fetch dari tabel water_report berdasarkan ID.
 *   4. Diperiksa apakah laporan ada; jika tidak, flash error dan redirect ke dashboard.
 *   5. Status laporan diperiksa; jika bukan 'Pending', flash error dan redirect.
 *   6. Transaksi PDO dimulai dengan $pdo->beginTransaction().
 *   7. Status water_report diupdate menjadi 'Diproses'.
 *   8. Record baru diinsert ke staff_dispenser_assignment dengan status 'On Progress'.
 *   9. Transaksi di-commit; flash success diset.
 *  10. Jika PDOException terjadi, transaksi di-rollback dan flash error diset.
 *  11. Redirect ke ../dashboard/index.php.
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - water_report               : Di-SELECT (fetch data laporan) dan di-UPDATE (ubah status ke 'Diproses').
 *   - staff_dispenser_assignment : Di-INSERT (buat record penugasan baru dengan status 'On Progress').
 *
 * VARIABEL PENTING:
 *   - $id         : Integer ID laporan yang akan diambil (dari $_GET['id']).
 *   - $report     : Array asosiatif data laporan yang di-fetch dari database.
 *   - $stmtUpdate : PDOStatement untuk mengupdate status water_report.
 *   - $stmtInsert : PDOStatement untuk menginsert record penugasan baru.
 *   - $_SESSION['staff_id']  : ID staf yang sedang login, digunakan sebagai Staff_ID pada assignment.
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash()).
 *
 * AKSES:
 *   Semua pengguna yang sudah login (Super Admin & Staff). Namun secara praktis,
 *   tombol "Ambil Tugas" di index.php hanya ditampilkan untuk pengguna berperan 'Staff'.
 *
 * CATATAN PENGEMBANG:
 *   - Penggunaan transaksi ($pdo->beginTransaction() / commit() / rollBack()) sangat
 *     penting di sini karena dua tabel dimodifikasi sekaligus; jika salah satu gagal,
 *     data tidak akan inkonsisten.
 *   - Tidak ada pengecekan apakah staf yang sama sudah pernah mengambil laporan ini
 *     sebelumnya; pertimbangkan menambahkan pengecekan duplikasi assignment di masa depan.
 *   - File ini tidak memiliki tampilan HTML; murni sebagai action handler lalu redirect.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
// laporan/ambil.php — Action script to claim/accept a pending report
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce staff session authentication
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    set_flash('error', 'ID Laporan tidak valid.');
    header('Location: ../dashboard/index.php');
    exit;
}

try {
    // 1. Fetch the water report to ensure it exists and is Pending
    $stmt = $pdo->prepare("SELECT * FROM water_report WHERE WaterReport_ID = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch();

    if (!$report) {
        set_flash('error', 'Laporan tidak ditemukan.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    if ($report['Status'] !== 'Pending') {
        set_flash('error', 'Laporan ini sudah diproses atau diselesaikan.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // 2. Begin Transaction to update status and create assignment
    $pdo->beginTransaction();

    // Update report status to 'Diproses'
    $stmtUpdate = $pdo->prepare("UPDATE water_report SET Status = 'Diproses' WHERE WaterReport_ID = :id");
    $stmtUpdate->execute([':id' => $id]);

    // Insert assignment for the logged-in staff member
    $stmtInsert = $pdo->prepare("
        INSERT INTO staff_dispenser_assignment (Staff_ID, Dispenser_ID, WaterReport_ID, Status, Created_At)
        VALUES (:staff_id, :dispenser_id, :water_report_id, 'On Progress', NOW())
    ");
    $stmtInsert->execute([
        ':staff_id'        => $_SESSION['staff_id'],
        ':dispenser_id'    => $report['Dispenser_ID'],
        ':water_report_id' => $id
    ]);

    $pdo->commit();
    set_flash('success', 'Laporan berhasil diambil! Silakan mulai mengerjakan tugas ini.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal mengambil laporan: ' . $e->getMessage());
}

header('Location: ../dashboard/index.php');
exit;
