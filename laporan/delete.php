<?php
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
