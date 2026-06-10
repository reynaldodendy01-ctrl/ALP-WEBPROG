<?php
/**
 * =============================================================================
 * delete.php — Proses Penghapusan Data Pelapor
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah handler backend tanpa tampilan (headless) yang memproses
 *   penghapusan satu record pelapor dari tabel reporter. Hanya menerima
 *   request HTTP POST agar penghapusan tidak dapat dipicu melalui klik link
 *   URL secara langsung. Setelah proses penghapusan selesai (berhasil atau gagal),
 *   pengguna di-redirect kembali ke halaman daftar pelapor (index.php) dengan
 *   flash message yang menginformasikan hasil operasi.
 *
 * FUNGSI UTAMA:
 *   - Menerima Reporter_ID melalui $_POST['id']
 *   - Menghapus record pelapor dari tabel reporter
 *   - Memberikan flash message sukses atau error sesuai hasil operasi
 *   - Redirect otomatis ke index.php setelah eksekusi
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk mendapatkan koneksi $pdo dan fungsi set_flash()
 *   2. Dicek apakah request method adalah POST; selain POST tidak diproses
 *   3. Reporter_ID dibaca dari $_POST['id'] dan dikonversi ke integer dengan intval()
 *   4. Prepared statement DELETE dieksekusi dengan parameter binding (:id)
 *   5. Bila berhasil: set_flash('success', 'Pelapor berhasil dihapus.')
 *   6. Bila PDOException: set_flash('error', ...) dengan pesan error dari exception
 *   7. Header Location redirect ke index.php; script dihentikan (exit)
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - reporter : Record pelapor yang dihapus berdasarkan Reporter_ID
 *
 * VARIABEL PENTING:
 *   - $id : Reporter_ID dari pelapor yang akan dihapus (integer dari $_POST['id'])
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash())
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin. Form POST pemicu ada di halaman reporters/index.php
 *   dengan dialog konfirmasi JavaScript sebelum submit.
 *
 * CATATAN PENGEMBANG:
 *   - Penghapusan pelapor berpotensi memengaruhi tabel water_report yang memiliki
 *     foreign key ke Reporter_ID. Pastikan constraint database sudah dikonfigurasi
 *     dengan tepat (RESTRICT / CASCADE) sesuai kebutuhan bisnis aplikasi.
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
        $stmt = $pdo->prepare("DELETE FROM reporter WHERE Reporter_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Pelapor berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus pelapor: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
