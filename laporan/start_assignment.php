<?php
/**
 * =============================================================================
 * start_assignment.php — Aksi Memulai Pengerjaan Penugasan oleh Staf Maintenance
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah action script yang memungkinkan staf maintenance untuk secara
 *   resmi memulai pengerjaan sebuah penugasan (assignment) yang sebelumnya masih
 *   berstatus 'Pending'. Ketika staf memulai penugasan, status assignment diperbarui
 *   menjadi 'On Progress', dan jika penugasan tersebut terhubung dengan sebuah laporan
 *   air (water_report), status laporan tersebut juga diperbarui menjadi 'Diproses'.
 *   Seluruh proses berjalan dalam satu transaksi atomik untuk menjaga konsistensi data
 *   antara tabel penugasan dan tabel laporan air.
 *
 * FUNGSI UTAMA:
 *   - Memvalidasi sesi login staf; redirect ke login.php jika belum terautentikasi.
 *   - Memvalidasi ID assignment dari parameter GET; redirect jika ID tidak valid.
 *   - Memastikan assignment yang dimaksud benar-benar ada di database.
 *   - Membatasi staf biasa agar hanya bisa memulai penugasan miliknya sendiri
 *     (cek Staff_ID pada assignment vs. staff_id di sesi).
 *   - Memastikan assignment masih berstatus 'Pending' sebelum diproses.
 *   - Mengubah status assignment dari 'Pending' menjadi 'On Progress'.
 *   - Jika assignment terhubung dengan water_report, mengubah status laporan
 *     tersebut menjadi 'Diproses' secara sinkron dalam transaksi yang sama.
 *   - Meredirect staf ke dashboard setelah proses selesai.
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include; sesi diperiksa untuk autentikasi.
 *   2. ID assignment diambil dan divalidasi dari $_GET['id'].
 *   3. Data assignment di-fetch dari tabel staff_dispenser_assignment.
 *   4. Jika assignment tidak ditemukan, flash error dan redirect ke dashboard.
 *   5. Jika pengguna adalah 'Staff' dan Staff_ID tidak cocok dengan sesinya, akses ditolak.
 *   6. Status assignment diperiksa; jika bukan 'Pending', flash error dan redirect.
 *   7. Transaksi PDO dimulai.
 *   8. Status assignment diupdate menjadi 'On Progress'.
 *   9. Jika WaterReport_ID ada, status water_report terkait diupdate menjadi 'Diproses'.
 *  10. Transaksi di-commit; flash success diset.
 *  11. Jika error terjadi, transaksi di-rollback dan flash error diset.
 *  12. Redirect ke ../dashboard/index.php.
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - staff_dispenser_assignment : Di-SELECT (fetch data assignment) dan di-UPDATE (ubah status ke 'On Progress').
 *   - water_report               : Di-UPDATE (ubah status ke 'Diproses') jika WaterReport_ID terdapat pada assignment.
 *
 * VARIABEL PENTING:
 *   - $id         : Integer ID penugasan yang akan dimulai (dari $_GET['id']).
 *   - $assignment : Array asosiatif data penugasan yang di-fetch dari database.
 *   - $stmtUpdate : PDOStatement untuk mengupdate status assignment ke 'On Progress'.
 *   - $stmtReport : PDOStatement untuk mengupdate status water_report ke 'Diproses' (kondisional).
 *   - $_SESSION['staff_id']   : ID staf yang sedang login, diverifikasi terhadap assignment.
 *   - $_SESSION['staff_role'] : Peran staf; digunakan untuk mengizinkan Admin memulai assignment siapapun.
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash()).
 *
 * AKSES:
 *   Semua pengguna yang sudah login. Namun staf biasa (peran 'Staff') hanya bisa
 *   memulai penugasan yang memang ditugaskan kepada dirinya sendiri. Super Admin
 *   dapat memulai penugasan milik staf manapun tanpa pembatasan.
 *
 * CATATAN PENGEMBANG:
 *   - Perbedaan antara ambil.php dan start_assignment.php: ambil.php digunakan untuk
 *     mengklaim laporan baru (status: Pending → Diproses + buat assignment baru),
 *     sedangkan start_assignment.php digunakan untuk memulai assignment yang sudah ada
 *     (status assignment: Pending → On Progress).
 *   - File ini tidak memiliki tampilan HTML; murni sebagai action handler lalu redirect.
 *   - Penggunaan transaksi penting agar update assignment dan update water_report selalu
 *     berjalan bersamaan (atomic), tidak ada kondisi setengah-update.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
// laporan/start_assignment.php — Action script to start a pending assignment
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
    set_flash('error', 'ID Penugasan tidak valid.');
    header('Location: ../dashboard/index.php');
    exit;
}

try {
    // Fetch the assignment to ensure it exists
    $stmt = $pdo->prepare("SELECT * FROM staff_dispenser_assignment WHERE Assignment_ID = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        set_flash('error', 'Penugasan tidak ditemukan.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Regular Staff can only start their own assignments
    if ($_SESSION['staff_role'] === 'Staff' && intval($assignment['Staff_ID']) !== intval($_SESSION['staff_id'])) {
        set_flash('error', 'Akses ditolak: Penugasan ini bukan ditujukan untuk Anda.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    if ($assignment['Status'] !== 'Pending') {
        set_flash('error', 'Penugasan ini sudah berjalan atau selesai.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Begin Transaction
    $pdo->beginTransaction();

    // Update assignment status to 'On Progress'
    $stmtUpdate = $pdo->prepare("UPDATE staff_dispenser_assignment SET Status = 'On Progress' WHERE Assignment_ID = :id");
    $stmtUpdate->execute([':id' => $id]);

    // If it's linked to a water report, update that report to 'Diproses'
    if ($assignment['WaterReport_ID']) {
        $stmtReport = $pdo->prepare("UPDATE water_report SET Status = 'Diproses' WHERE WaterReport_ID = :wr_id");
        $stmtReport->execute([':wr_id' => $assignment['WaterReport_ID']]);
    }

    $pdo->commit();
    set_flash('success', 'Tugas dimulai! Status penugasan kini menjadi On Progress.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal memulai penugasan: ' . $e->getMessage());
}

header('Location: ../dashboard/index.php');
exit;
