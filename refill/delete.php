<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM refill_logs WHERE Logs_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Log refill berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus log refill: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
