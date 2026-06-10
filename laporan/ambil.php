<?php
// =========================================================================
// FILE: laporan/ambil.php
// FUNGSI: Aksi Staff Mengambil Laporan yang Masih "Pending" (Untuk dikerjakan)
// Catatan: File ini berjalan cepat di belakang layar, tanpa HTML.
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// Memulai sesi (session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. KEAMANAN AKSES (Harus Login Dulu)
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// 3. MENANGKAP ID LAPORAN (Contoh klik link: ambil.php?id=12)
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    set_flash('error', 'ID Laporan tidak valid.');
    header('Location: ../dashboard/index.php');
    exit;
}

try {
    // ─── 4. CEK KONDISI LAPORAN SAAT INI ───────────────────
    // Kita harus cek dulu apakah laporan ini beneran ada? Dan apakah statusnya masih 'Pending'?
    // Siapa tahu sedetik yang lalu laporan ini sudah diambil sama staff lain.
    $stmt = $pdo->prepare("SELECT * FROM water_report WHERE WaterReport_ID = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch();

    if (!$report) {
        set_flash('error', 'Laporan tidak ditemukan.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    if ($report['Status'] !== 'Pending') {
        set_flash('error', 'Laporan ini sudah diproses atau diselesaikan oleh staff lain.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // ─── 5. MEMULAI TRANSAKSI MYSQL (TRANSACTION) ───────────────────
    // Apa itu Transaction? 
    // Ini adalah fitur MySQL di mana kita bisa menjalankan 2 perintah sekaligus secara AMAN.
    // Kalau salah satu perintah gagal, perintah satunya akan dibatalkan otomatis (Rollback).
    // Jadi tidak ada cerita datanya setengah masuk setengah hilang.
    $pdo->beginTransaction();

    // Perintah 1: Ubah status laporan dari 'Pending' jadi 'Diproses'
    $stmtUpdate = $pdo->prepare("UPDATE water_report SET Status = 'Diproses' WHERE WaterReport_ID = :id");
    $stmtUpdate->execute([':id' => $id]);

    // Perintah 2: Buat Surat Tugas (Assignment) untuk Staff yang lagi login ini
    $stmtInsert = $pdo->prepare("
        INSERT INTO staff_dispenser_assignment (Staff_ID, Dispenser_ID, WaterReport_ID, Status, Created_At)
        VALUES (:staff_id, :dispenser_id, :water_report_id, 'On Progress', NOW())
    ");
    $stmtInsert->execute([
        ':staff_id'        => $_SESSION['staff_id'], // ID staff yang lagi login
        ':dispenser_id'    => $report['Dispenser_ID'], // ID Dispenser yang rusak dari laporan tadi
        ':water_report_id' => $id // ID laporannya
    ]);

    // Selesaikan transaksi! (Simpan permanen dua perintah di atas)
    $pdo->commit();
    set_flash('success', 'Laporan berhasil diambil! Silakan mulai mengerjakan tugas ini.');
} catch (PDOException $e) {
    // Jika di tengah jalan tiba-tiba database mati/error, batalkan semua perubahan!
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal mengambil laporan: ' . $e->getMessage());
}

header('Location: ../dashboard/index.php');
exit;
