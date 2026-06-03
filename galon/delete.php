<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$id = intval($_POST['id'] ?? 0);
if ($id) {
    $pdo->prepare("DELETE FROM galon WHERE id=:id")->execute([':id'=>$id]);
    set_flash('success', 'Record galon berhasil dihapus.');
} else {
    set_flash('error', 'Data tidak ditemukan.');
}
header('Location: index.php'); exit;
