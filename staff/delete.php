<?php
// =========================================================================
// FILE: staff/delete.php
// FUNGSI: Menghapus Staff dari MySQL (DELETE)
// Catatan: File ini berjalan cepat secara gaib di belakang layar (tanpa HTML).
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. PASTIKAN METODENYA POST
// Ini trik supaya tidak ada hacker yang sembarangan ngetik link "delete.php?id=5" di browser untuk hapus staff.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil ID staff yang mau dihapus dari form gaib HTML
    $id = intval($_POST['id'] ?? 0);

    try {
        // ─── 3. CRUD (DELETE) - MENGHAPUS DATA DARI MYSQL ───────────────────
        // Menyiapkan perintah penghapusan di tabel 'maintenance_staff'
        // Jangan pernah lupa klausa 'WHERE Staff_ID = :id'!
        $stmt = $pdo->prepare("DELETE FROM maintenance_staff WHERE Staff_ID = :id");
        $stmt->execute([':id' => $id]);
        
        // Buat notifikasi hijau
        set_flash('success', 'Staff berhasil dihapus.');
    } catch (PDOException $e) {
        // Biasanya error kalau staff ini sedang dipakai datanya di tabel penugasan (Assignment)
        set_flash('error', 'Gagal menghapus staff: ' . $e->getMessage());
    }
}

// 4. BALIKKAN PENGGUNA KE HALAMAN DAFTAR STAFF
header('Location: index.php');
exit;
