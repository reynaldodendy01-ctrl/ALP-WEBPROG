<?php
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
