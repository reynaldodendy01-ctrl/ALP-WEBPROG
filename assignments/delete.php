<?php
// =========================================================================
// FILE: assignments/delete.php
// FUNGSI: Menghapus Data Penugasan (Surat Tugas) dari Database (DELETE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. PASTIKAN AKSI DIJALANKAN LEWAT TOMBOL (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tangkap ID yang mau dihapus dari form HTML gaib
    $id = intval($_POST['id'] ?? 0);

    try {
        // ─── 3. CRUD (DELETE) - HAPUS DATA DARI MYSQL ───────────────────
        $stmt = $pdo->prepare("DELETE FROM staff_dispenser_assignment WHERE Assignment_ID = :id");
        $stmt->execute([':id' => $id]);
        
        // PENTING: Jika tugas dihapus, apakah laporannya ikut terhapus?
        // Jawabannya: TIDAK. Laporannya tetap ada (aman). Hanya surat tugas staff-nya yang hilang.
        set_flash('success', 'Penugasan berhasil dihapus.');
    } catch (PDOException $e) {
        // Biasanya error kalau tugas ini sudah pernah dicatat isinya ke tabel "Refill"
        // (Misal staff sudah isi galon, lalu admin mau hapus tugasnya. Pasti MySQL marah / nolak!)
        set_flash('error', 'Gagal menghapus penugasan: ' . $e->getMessage());
    }
}

// 4. KEMBALIKAN ADMIN KE DAFTAR PENUGASAN
header('Location: index.php');
exit;
