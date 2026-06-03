<?php
require_once __DIR__ . '/../db.php';

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
