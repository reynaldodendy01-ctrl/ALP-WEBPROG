<?php
// =========================================================================
// FILE: laporan/delete.php
// FUNGSI: Menghapus Laporan dari MySQL (DELETE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// Memulai sesi untuk mengecek siapa yang lagi login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. KEAMANAN AKSES (Harus Login Dulu)
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// 3. KEAMANAN AKSES (Hanya Admin yang Boleh Hapus!)
// Staff lapangan biasa TIDAK PUNYA WEWENANG untuk menghapus jejak laporan
// Ini penting untuk mencegah kecurangan di lapangan.
if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    set_flash('error', 'Akses ditolak: Hanya Super Admin yang dapat menghapus laporan secara langsung.');
    header('Location: index.php');
    exit;
}

// 4. MENGHAPUS LAPORAN (Hanya jika disubmit pakai tombol POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tangkap ID laporan yang mau dihapus
    $id = intval($_POST['id'] ?? 0);

    try {
        // ─── 5. CRUD (DELETE) - HAPUS DATA DARI MYSQL ───────────────────
        $stmt = $pdo->prepare("DELETE FROM water_report WHERE WaterReport_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Laporan kendala berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus laporan: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
