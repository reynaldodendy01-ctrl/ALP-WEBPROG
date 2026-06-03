<?php
require_once __DIR__ . '/../db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
$id = intval($_POST['id'] ?? 0);
if ($id) {
    $row = $pdo->prepare("SELECT nama FROM staff WHERE id=:id");
    $row->execute([':id'=>$id]);
    $row = $row->fetch();
    // Set NULL on foreign keys first (already handled by DB ON DELETE SET NULL)
    $pdo->prepare("DELETE FROM staff WHERE id=:id")->execute([':id'=>$id]);
    set_flash('success', "Staff \"{$row['nama']}\" berhasil dihapus.");
} else { set_flash('error', 'Data tidak ditemukan.'); }
header('Location: index.php'); exit;
