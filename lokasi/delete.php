<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM lokasi WHERE Lokasi_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Lokasi berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus lokasi: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
