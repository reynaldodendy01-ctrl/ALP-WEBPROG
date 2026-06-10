<?php
// =========================================================================
// FILE: laporan/start_assignment.php
// FUNGSI: Memulai Tugas/Assignment yang masih "Pending" (Di-assign oleh Admin)
// Catatan: File ini berjalan di belakang layar.
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. KEAMANAN AKSES
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// 3. MENANGKAP ID ASSIGNMENT DARI URL
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    set_flash('error', 'ID Penugasan tidak valid.');
    header('Location: ../dashboard/index.php');
    exit;
}

try {
    // ─── 4. CEK KONDISI PENUGASAN ───────────────────
    // Cari data penugasannya dari tabel 'staff_dispenser_assignment'
    $stmt = $pdo->prepare("SELECT * FROM staff_dispenser_assignment WHERE Assignment_ID = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        set_flash('error', 'Penugasan tidak ditemukan.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // CEK KEAMANAN EKSTRA: Staff biasa HANYA BOLEH memulai tugas miliknya sendiri!
    // Admin bebas. Kalau ID staff yang di tugas beda dengan ID staff yang login, tolak!
    if ($_SESSION['staff_role'] === 'Staff' && intval($assignment['Staff_ID']) !== intval($_SESSION['staff_id'])) {
        set_flash('error', 'Akses ditolak: Penugasan ini bukan ditujukan untuk Anda.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Pastikan tugas ini benar-benar masih 'Pending' (Belum dikerjakan)
    if ($assignment['Status'] !== 'Pending') {
        set_flash('error', 'Penugasan ini sudah berjalan atau selesai.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // ─── 5. MEMULAI TRANSAKSI MYSQL (TRANSACTION) ───────────────────
    // Menghindari data setengah jalan jika tiba-tiba sistem error
    $pdo->beginTransaction();

    // Perintah 1: Ubah status Tugas menjadi 'On Progress'
    $stmtUpdate = $pdo->prepare("UPDATE staff_dispenser_assignment SET Status = 'On Progress' WHERE Assignment_ID = :id");
    $stmtUpdate->execute([':id' => $id]);

    // Perintah 2: Jika tugas ini berasal dari "Laporan" orang lain, kita harus mengubah 
    //             status laporannya juga dari 'Pending' jadi 'Diproses'.
    if ($assignment['WaterReport_ID']) {
        $stmtReport = $pdo->prepare("UPDATE water_report SET Status = 'Diproses' WHERE WaterReport_ID = :wr_id");
        $stmtReport->execute([':wr_id' => $assignment['WaterReport_ID']]);
    }

    // Simpan permanen perubahan!
    $pdo->commit();
    set_flash('success', 'Tugas dimulai! Status penugasan kini menjadi On Progress.');
} catch (PDOException $e) {
    // Batalkan perubahan jika ada masalah
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal memulai penugasan: ' . $e->getMessage());
}

header('Location: ../dashboard/index.php');
exit;
