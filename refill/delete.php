<?php
/**
 * =============================================================================
 * delete.php — Proses Penghapusan Log Refill Galon
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah handler backend tanpa tampilan (headless) yang memproses
 *   penghapusan satu entri log pengisian galon (refill) dari tabel refill_logs.
 *   Hanya menerima request HTTP POST untuk keamanan agar penghapusan tidak dapat
 *   dipicu secara tidak sengaja melalui URL langsung. Setelah proses selesai,
 *   pengguna di-redirect kembali ke halaman daftar log refill (index.php) dengan
 *   flash message yang menginformasikan hasil operasi.
 *
 * FUNGSI UTAMA:
 *   - Menerima Logs_ID melalui $_POST['id']
 *   - Menghapus satu entri log refill dari tabel refill_logs
 *   - Memberikan flash message sukses atau error sesuai hasil operasi
 *   - Redirect otomatis ke index.php setelah eksekusi
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk mendapatkan koneksi $pdo dan fungsi set_flash()
 *   2. Dicek apakah request method adalah POST; selain POST tidak diproses
 *   3. Logs_ID dibaca dari $_POST['id'] dan dikonversi ke integer melalui intval()
 *   4. Prepared statement DELETE dieksekusi dengan parameter binding (:id)
 *   5. Bila berhasil: set_flash('success', 'Log refill berhasil dihapus.')
 *   6. Bila PDOException: set_flash('error', ...) dengan pesan exception
 *   7. Header Location redirect ke index.php; script dihentikan (exit)
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - refill_logs : Baris log yang dihapus berdasarkan Logs_ID
 *
 * VARIABEL PENTING:
 *   - $id : Logs_ID dari log refill yang akan dihapus (integer dari $_POST['id'])
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash())
 *
 * AKSES:
 *   Dapat diakses oleh admin. Form POST pemicu ada di halaman refill/index.php
 *   dengan konfirmasi dialog JavaScript sebelum submit.
 *
 * CATATAN PENGEMBANG:
 *   - File ini tidak memperbarui status penugasan terkait setelah log dihapus.
 *     Bila diperlukan rollback status penugasan ke 'Pending'/'On Progress',
 *     logika tambahan perlu ditambahkan di sini.
 *   - File ini tidak merender HTML; seluruh output adalah redirect header.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM refill_logs WHERE Logs_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Log refill berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus log refill: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
