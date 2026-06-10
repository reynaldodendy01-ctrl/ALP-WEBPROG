<?php
// =========================================================================
// FILE: reporters/delete.php
// FUNGSI: Menghapus Data Akun Pelapor (Mahasiswa) (DELETE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. TANGKAP ID DARI TOMBOL (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    try {
        // 3. CRUD (DELETE) - HAPUS DARI MYSQL
        $stmt = $pdo->prepare("DELETE FROM reporter WHERE Reporter_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Pelapor berhasil dihapus.');
    } catch (PDOException $e) {
        // CATATAN:
        // Kalau MySQL error di sini, kemungkinan besar pelapor ini
        // SUDAH PERNAH bikin laporan sebelumnya!
        // MySQL menolak menghapus supaya history laporannya gak hilang alias "Orphaned Data".
        set_flash('error', 'Gagal menghapus pelapor (biasanya karena sudah terikat dengan data laporan): ' . $e->getMessage());
    }
}

// 4. KEMBALIKAN KE DAFTAR PELAPOR
header('Location: index.php');
exit;
