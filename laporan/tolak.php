<?php
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
