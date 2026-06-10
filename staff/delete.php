<?php
/**
 * =============================================================================
 * staff/delete.php — Proses Penghapusan Akun Staff dari Sistem
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah endpoint backend yang menangani penghapusan permanen sebuah
 *   akun staff atau admin dari database sistem CariGalon. File ini tidak merender
 *   tampilan HTML apapun; ia hanya memproses request POST yang dikirim melalui
 *   form konfirmasi di halaman index.php, menjalankan perintah DELETE ke database,
 *   lalu langsung redirect ke index.php dengan flash message yang sesuai. Penghapusan
 *   bersifat permanen dan akan mempengaruhi data assignment yang mereferensikan
 *   staff tersebut jika tidak ada constraint ON DELETE SET NULL.
 *
 * FUNGSI UTAMA:
 *   - Menerima Staff_ID melalui form POST dari halaman index.php
 *   - Menjalankan perintah DELETE pada tabel `maintenance_staff` berdasarkan Staff_ID
 *   - Menangani PDOException jika penghapusan gagal (misal: referential integrity)
 *   - Menyetel flash message 'success' atau 'error' sesuai hasil operasi
 *   - Redirect otomatis ke index.php setelah proses selesai (baik sukses maupun gagal)
 *   - Hanya memproses logika hapus jika REQUEST_METHOD adalah 'POST'
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk koneksi PDO dan helper functions
 *   2. Cek apakah request method adalah POST; jika bukan, langsung redirect ke index.php
 *   3. Ambil dan cast nilai $_POST['id'] menjadi integer (keamanan dari injection)
 *   4. Eksekusi prepared statement: DELETE FROM maintenance_staff WHERE Staff_ID = :id
 *   5. Jika berhasil: set_flash 'success' dengan pesan konfirmasi penghapusan akun
 *   6. Jika PDOException: set_flash 'error' dengan detail pesan error dari database
 *   7. Redirect ke index.php dan hentikan eksekusi dengan exit
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - maintenance_staff : Target operasi DELETE berdasarkan Staff_ID yang diterima via POST
 *
 * VARIABEL PENTING:
 *   - $id  : ID staff yang akan dihapus, di-cast ke integer dari $_POST['id']
 *            untuk mencegah SQL injection meskipun prepared statement sudah digunakan
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash())
 *
 * AKSES:
 *   Hanya bisa diakses oleh Admin yang sudah login. Request harus berupa POST
 *   (dikirim melalui form konfirmasi di halaman index.php disertai dialog confirm JS).
 *
 * CATATAN PENGEMBANG:
 *   Penghapusan akun staff yang masih memiliki assignment aktif dapat menyebabkan
 *   data assignment menjadi orphan (jika tidak ada CASCADE). Disarankan menambahkan
 *   pengecekan jumlah assignment aktif sebelum mengizinkan penghapusan, atau
 *   mengimplementasikan soft-delete (kolom is_deleted) sebagai alternatif yang lebih aman.
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
        $stmt = $pdo->prepare("DELETE FROM maintenance_staff WHERE Staff_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Staff berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus staff: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
