<?php
// =========================================================================
// FILE: refill/delete.php
// FUNGSI: Menghapus Data Riwayat (Log) Refill dari Database (DELETE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. PASTIKAN DIA MENGGUNAKAN TOMBOL "HAPUS" (METHOD POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0); // Ambil ID dari tombol form HTML

    try {
        // ─── 3. CRUD (DELETE) - HAPUS DATA DARI TABEL refill_logs ───────────────────
        $stmt = $pdo->prepare("DELETE FROM refill_logs WHERE Logs_ID = :id");
        $stmt->execute([':id' => $id]);
        
        // PENTING DIBACA OLEH STAFF:
        // Menghapus history refill di sini TIDAK AKAN mengembalikan status 
        // penugasan (Assignment) ke "On Progress". Kalau mau revisi, harus ngomong ke admin!
        set_flash('success', 'Log refill berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus log refill: ' . $e->getMessage());
    }
}

// 4. USIR KEMBALI KE HALAMAN DAFTAR REFILL
header('Location: index.php');
exit;
