<?php
/**
 * =============================================================================
 * tolak.php — Aksi Penolakan Laporan Kendala Air (Admin & Staff)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah action script yang memproses penolakan sebuah laporan kendala
 *   air yang dianggap palsu, tidak valid, atau spam. Baik Super Admin maupun Staff
 *   yang sudah login dapat menggunakan aksi ini untuk menolak laporan yang masih
 *   berstatus 'Pending' atau 'Diproses'. Ketika sebuah laporan ditolak, statusnya
 *   diubah menjadi 'Ditolak' dan semua penugasan aktif (status 'Pending' atau
 *   'On Progress') yang terhubung dengan laporan tersebut dibatalkan secara otomatis
 *   dengan status 'Cancelled', semuanya dalam satu transaksi atomik.
 *
 * FUNGSI UTAMA:
 *   - Memvalidasi sesi login pengguna; redirect ke login.php jika belum terautentikasi.
 *   - Memvalidasi ID laporan dari parameter GET; redirect jika ID tidak valid (0 atau kosong).
 *   - Memastikan laporan yang akan ditolak benar-benar ada di database.
 *   - Memastikan laporan belum berstatus 'Selesai' atau 'Ditolak' (tidak bisa ditolak dua kali).
 *   - Mengubah status laporan menjadi 'Ditolak' di tabel water_report.
 *   - Membatalkan semua penugasan aktif terkait laporan tersebut di tabel
 *     staff_dispenser_assignment (ubah status ke 'Cancelled').
 *   - Meredirect pengguna kembali ke halaman asal (HTTP_REFERER) atau ke dashboard
 *     jika referrer tidak tersedia atau berasal dari domain luar.
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include; sesi diperiksa untuk autentikasi.
 *   2. ID laporan diambil dan divalidasi dari $_GET['id'].
 *   3. Data laporan di-fetch dari tabel water_report berdasarkan ID.
 *   4. Jika laporan tidak ditemukan, flash error dan redirect ke dashboard.
 *   5. Status laporan diperiksa; jika sudah 'Selesai' atau 'Ditolak', flash error dan redirect.
 *   6. Transaksi PDO dimulai dengan $pdo->beginTransaction().
 *   7. Status water_report diupdate menjadi 'Ditolak'.
 *   8. Semua assignment aktif (Pending/On Progress) terkait laporan diupdate ke 'Cancelled'.
 *   9. Transaksi di-commit; flash success diset.
 *  10. Jika PDOException terjadi, transaksi di-rollback dan flash error diset.
 *  11. Redirect ke HTTP_REFERER (jika valid & satu domain) atau ke ../dashboard/index.php.
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - water_report               : Di-SELECT (cek keberadaan & status) dan di-UPDATE (set Status = 'Ditolak').
 *   - staff_dispenser_assignment : Di-UPDATE massal (set Status = 'Cancelled' untuk semua assignment aktif terkait).
 *
 * VARIABEL PENTING:
 *   - $id                    : Integer ID laporan yang akan ditolak (dari $_GET['id']).
 *   - $report                : Array asosiatif data laporan yang di-fetch dari database.
 *   - $stmtUpdate            : PDOStatement untuk mengupdate status water_report ke 'Ditolak'.
 *   - $stmtCancelAssignments : PDOStatement untuk membatalkan semua assignment aktif terkait.
 *   - $referrer              : String URL asal pengguna dari $_SERVER['HTTP_REFERER'] untuk redirect cerdas.
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash()).
 *
 * AKSES:
 *   Semua pengguna yang sudah login (Super Admin & Staff). Berbeda dengan delete.php
 *   dan edit.php yang terbatas hanya untuk Super Admin, aksi penolakan ini dapat
 *   dilakukan oleh Staff biasa karena merupakan bagian dari alur kerja lapangan mereka.
 *
 * CATATAN PENGEMBANG:
 *   - Redirect cerdas menggunakan HTTP_REFERER memungkinkan aksi ini dipanggil dari
 *     berbagai halaman (index.php laporan atau dashboard) dan kembali ke halaman asal.
 *   - Pemeriksaan strpos($referrer, $_SERVER['HTTP_HOST']) memastikan redirect hanya ke
 *     halaman internal sistem, bukan URL eksternal (mencegah open redirect vulnerability).
 *   - Penolakan bersifat final; tidak ada fitur "batalkan penolakan" yang tersedia.
 *     Status 'Ditolak' dan assignment 'Cancelled' tidak dapat dikembalikan melalui UI ini.
 *   - File ini tidak memiliki tampilan HTML; murni sebagai action handler lalu redirect.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
// laporan/tolak.php — Action script to reject a water report as fake or spam
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
    // 1. Fetch the water report
    $stmt = $pdo->prepare("SELECT * FROM water_report WHERE WaterReport_ID = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch();

    if (!$report) {
        set_flash('error', 'Laporan tidak ditemukan.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    if (in_array($report['Status'], ['Selesai', 'Ditolak'])) {
        set_flash('error', 'Laporan ini sudah diselesaikan atau sudah ditolak sebelumnya.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // 2. Begin Transaction to update status and cancel linked assignments
    $pdo->beginTransaction();

    // Update report status to 'Ditolak'
    $stmtUpdate = $pdo->prepare("UPDATE water_report SET Status = 'Ditolak' WHERE WaterReport_ID = :id");
    $stmtUpdate->execute([':id' => $id]);

    // Cancel any active staff assignments linked to this report
    $stmtCancelAssignments = $pdo->prepare("
        UPDATE staff_dispenser_assignment 
        SET Status = 'Cancelled' 
        WHERE WaterReport_ID = :id AND Status IN ('Pending', 'On Progress')
    ");
    $stmtCancelAssignments->execute([':id' => $id]);

    $pdo->commit();
    set_flash('success', 'Laporan berhasil ditolak dan penugasan terkait dibatalkan.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal menolak laporan: ' . $e->getMessage());
}

// Redirect back to referring page if available, else dashboard
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referrer && strpos($referrer, $_SERVER['HTTP_HOST']) !== false) {
    header('Location: ' . $referrer);
} else {
    header('Location: ../dashboard/index.php');
}
exit;
