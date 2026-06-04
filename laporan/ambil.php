<?php
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
