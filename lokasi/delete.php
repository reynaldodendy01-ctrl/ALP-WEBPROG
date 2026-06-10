<?php
/* DESKRIPSI FILE: Proses penghapusan data lokasi/gedung dari database. */

// ===================================================
// INISIALISASI — Muat koneksi database
// ===================================================
require_once __DIR__ . '/../db.php'; // Hubungkan ke database lewat file db.php (berisi variabel $pdo)

// ===================================================
// CEK METHOD — Proses hanya jika permintaan datang dari form (method POST)
// ===================================================
// Halaman ini tidak memiliki tampilan (tidak ada HTML) — hanya memproses penghapusan lalu redirect
// Pengecekan POST penting agar tidak bisa dihapus hanya dengan membuka URL di browser (method GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil ID lokasi yang dikirim dari form (field hidden bernama 'id' di index.php)
    // intval() = paksa jadi angka bulat supaya aman (mencegah karakter aneh masuk query)
    $id = intval($_POST['id'] ?? 0); // ?? 0 = kalau 'id' tidak ada di form, pakai nilai default 0

    try {
        // ===================================================
        // [DELETE] Hapus lokasi dari tabel 'lokasi' berdasarkan ID
        // ===================================================
        // PERHATIAN — CASCADING DELETE:
        //   Jika tabel 'dispenser' memiliki Foreign Key ke 'lokasi' dengan ON DELETE CASCADE,
        //   maka SEMUA DISPENSER yang berada di lokasi ini akan IKUT TERHAPUS secara otomatis!
        //   Itulah mengapa pesan konfirmasi di index.php memperingatkan hal ini kepada pengguna.
        // WHERE Lokasi_ID = :id sangat penting!
        //   Tanpa WHERE, semua baris di tabel lokasi akan terhapus sekaligus — sangat berbahaya!
        $stmt = $pdo->prepare("DELETE FROM lokasi WHERE Lokasi_ID = :id");
        // Jalankan query dengan nilai $id yang sudah aman
        $stmt->execute([':id' => $id]);

        // Simpan pesan sukses ke sesi supaya bisa ditampilkan setelah redirect ke index.php
        set_flash('success', 'Lokasi berhasil dihapus.');
    } catch (PDOException $e) {
        // Jika terjadi error saat menghapus (misalnya ada constraint yang mencegah penghapusan),
        // simpan pesan error ke sesi supaya bisa ditampilkan di halaman berikutnya
        set_flash('error', 'Gagal menghapus lokasi: ' . $e->getMessage());
    }
}

// ===================================================
// REDIRECT — Kembalikan pengguna ke halaman daftar lokasi
// ===================================================
// Redirect selalu dilakukan, baik proses berhasil maupun gagal
// Pesan sukses/error sudah disimpan di sesi oleh set_flash() di atas
header('Location: index.php'); // Kirim pengguna ke halaman daftar lokasi
exit; // Hentikan eksekusi PHP — wajib setelah header redirect!
