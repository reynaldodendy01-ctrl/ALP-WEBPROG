<?php
// File ini bertugas untuk Menghapus Dispenser (Bagian 'DELETE' dalam CRUD)
// File ini tidak punya tampilan HTML, hanya memproses penghapusan data secara "di belakang layar".
require_once __DIR__ . '/../db.php';

// Pastikan data dikirim menggunakan metode POST (lebih aman daripada lewat URL/GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil ID dispenser yang dikirim oleh tombol "Hapus"
    $id = intval($_POST['id'] ?? 0);

    try {
        // ─── CRUD (DELETE) - Menghapus Data dari MySQL ──────────────────────
        // Kita menyiapkan query 'DELETE FROM' untuk menghapus data dari tabel 'dispenser'.
        // INGAT: Selalu gunakan klausa 'WHERE' saat melakukan DELETE, 
        // jika tidak, SEMUA data di tabel akan terhapus!
        $stmt = $pdo->prepare("DELETE FROM dispenser WHERE Dispenser_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Dispenser berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus dispenser: ' . $e->getMessage());
    }
}

// Setelah selesai (berhasil atau gagal), kembalikan pengguna ke halaman daftar dispenser
header('Location: index.php');
exit;
