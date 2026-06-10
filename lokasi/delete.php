<?php
// =========================================================================
// FILE: lokasi/delete.php
// FUNGSI: Menghapus Lokasi dari MySQL (DELETE)
// Catatan: File ini tidak punya tampilan HTML sama sekali (berjalan di balik layar).
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. PASTIKAN METODENYA POST
// Kita hanya mengizinkan penghapusan jika form dikirim menggunakan POST.
// Ini untuk mencegah ada orang tak sengaja klik link 'delete.php?id=1' dan datanya hilang.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil ID lokasi yang mau dihapus
    $id = intval($_POST['id'] ?? 0);

    try {
        // ─── 3. CRUD (DELETE) - MENGHAPUS DATA DARI MYSQL ───────────────────
        // Menyiapkan perintah penghapusan. 
        // Ingat: WAJIB pakai WHERE Lokasi_ID = :id agar yang dihapus cuma 1 baris!
        $stmt = $pdo->prepare("DELETE FROM lokasi WHERE Lokasi_ID = :id");
        $stmt->execute([':id' => $id]);
        
        // Buat notifikasi hijau
        set_flash('success', 'Lokasi berhasil dihapus.');
    } catch (PDOException $e) {
        // Buat notifikasi merah kalau gagal (misalnya karena database lagi sibuk/rusak)
        set_flash('error', 'Gagal menghapus lokasi: ' . $e->getMessage());
    }
}

// 4. SETELAH SELESAI MENGHAPUS (Atau jika ada yang nekat buka langsung pakai GET)
// Lemparkan pengguna kembali ke halaman daftar lokasi secara otomatis
header('Location: index.php');
exit;
