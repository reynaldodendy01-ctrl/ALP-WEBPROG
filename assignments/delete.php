<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM staff_dispenser_assignment WHERE Assignment_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Penugasan berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus penugasan: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
