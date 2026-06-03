<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$row = $pdo->prepare("SELECT nama_lokasi FROM dispensers WHERE id = :id");
$row->execute([':id' => $id]);
$row = $row->fetch();

if ($row) {
    $pdo->prepare("DELETE FROM dispensers WHERE id = :id")->execute([':id' => $id]);
    set_flash('success', "Dispenser \"{$row['nama_lokasi']}\" berhasil dihapus.");
} else {
    set_flash('error', 'Dispenser tidak ditemukan.');
}

header('Location: index.php');
exit;
