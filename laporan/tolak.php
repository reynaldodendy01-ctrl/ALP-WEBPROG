<?php
// =========================================================================
// FILE: laporan/tolak.php
// FUNGSI: Aksi Staff Menolak Laporan (Karena Dianggap Palsu / Iseng)
// Catatan: File ini berjalan cepat di belakang layar.
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. KEAMANAN AKSES (Harus Login Dulu)
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// 3. MENANGKAP ID LAPORAN DARI URL
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    set_flash('error', 'ID Laporan tidak valid.');
    header('Location: ../dashboard/index.php');
    exit;
}

try {
    // ─── 4. CEK KONDISI LAPORAN ───────────────────
    $stmt = $pdo->prepare("SELECT * FROM water_report WHERE WaterReport_ID = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch();

    if (!$report) {
        set_flash('error', 'Laporan tidak ditemukan.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // Kalau sudah terlanjur berstatus 'Selesai' atau 'Ditolak', tidak bisa ditolak lagi.
    if (in_array($report['Status'], ['Selesai', 'Ditolak'])) {
        set_flash('error', 'Laporan ini sudah diselesaikan atau sudah ditolak sebelumnya.');
        header('Location: ../dashboard/index.php');
        exit;
    }

    // ─── 5. MEMULAI TRANSAKSI MYSQL ───────────────────
    $pdo->beginTransaction();

    // Perintah 1: Ubah status laporannya menjadi 'Ditolak'
    $stmtUpdate = $pdo->prepare("UPDATE water_report SET Status = 'Ditolak' WHERE WaterReport_ID = :id");
    $stmtUpdate->execute([':id' => $id]);

    // Perintah 2: Batalkan juga Surat Tugas (Assignment) yang mungkin sudah terlanjur dibuat!
    // Ini PENTING. Kalau laporan ditolak, tugas untuk staff juga harus dicoret ('Cancelled').
    $stmtCancelAssignments = $pdo->prepare("
        UPDATE staff_dispenser_assignment 
        SET Status = 'Cancelled' 
        WHERE WaterReport_ID = :id AND Status IN ('Pending', 'On Progress')
    ");
    $stmtCancelAssignments->execute([':id' => $id]);

    $pdo->commit();
    set_flash('success', 'Laporan berhasil ditolak dan penugasan terkait dibatalkan.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal menolak laporan: ' . $e->getMessage());
}

// 6. KEMBALIKAN KE HALAMAN SEBELUMNYA
// Kode ini mengecek dari halaman mana admin tadi berasal (misal dari halaman Laporan, atau Dashboard)
// lalu mengembalikannya tepat ke tempat asal tersebut secara mulus.
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referrer && strpos($referrer, $_SERVER['HTTP_HOST']) !== false) {
    header('Location: ' . $referrer);
} else {
    // Kalau nggak tahu asalnya, lempar aja ke dashboard
    header('Location: ../dashboard/index.php');
}
exit;
