<?php
/**
 * =============================================================================
 * dispensers/delete.php — Proses Penghapusan Data Dispenser
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah endpoint backend yang menangani penghapusan permanen sebuah
 *   unit dispenser dari database sistem CariGalon. File ini tidak merender tampilan
 *   HTML apapun; ia hanya memproses request POST, menjalankan perintah DELETE ke
 *   database, kemudian langsung melakukan redirect ke halaman index.php dengan
 *   flash message yang sesuai. Penghapusan bersifat cascade jika foreign key
 *   constraint dikonfigurasi, sehingga data laporan dan assignment terkait
 *   dispenser tersebut juga akan ikut terhapus.
 *
 * FUNGSI UTAMA:
 *   - Menerima ID dispenser melalui form POST dari halaman index.php
 *   - Menjalankan perintah DELETE pada tabel `dispenser` berdasarkan Dispenser_ID
 *   - Menangani PDOException jika penghapusan gagal (misal: constraint FK aktif)
 *   - Menyetel flash message 'success' atau 'error' sesuai hasil operasi
 *   - Redirect otomatis ke index.php setelah proses selesai (baik sukses maupun gagal)
 *   - Menolak request selain POST (hanya memproses jika REQUEST_METHOD === 'POST')
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk koneksi PDO dan helper functions
 *   2. Cek apakah request method adalah POST; abaikan logika hapus jika bukan
 *   3. Ambil dan cast nilai $_POST['id'] menjadi integer untuk keamanan
 *   4. Eksekusi prepared statement DELETE WHERE Dispenser_ID = :id
 *   5. Jika berhasil: set_flash 'success' dengan pesan konfirmasi penghapusan
 *   6. Jika PDOException: set_flash 'error' dengan pesan detail error database
 *   7. Redirect ke index.php dan hentikan eksekusi script
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - dispenser : Target operasi DELETE berdasarkan Dispenser_ID yang diterima via POST
 *
 * VARIABEL PENTING:
 *   - $id  : ID dispenser yang akan dihapus, di-cast ke integer dari $_POST['id']
 *            untuk mencegah SQL injection meskipun menggunakan prepared statement
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO & helper functions (set_flash())
 *
 * AKSES:
 *   Hanya bisa diakses oleh Admin yang sudah login. Request harus berupa POST
 *   (dikirim melalui form konfirmasi di halaman index.php).
 *
 * CATATAN PENGEMBANG:
 *   Halaman index.php menampilkan dialog konfirmasi JavaScript sebelum form
 *   di-submit ke file ini, sehingga ada lapisan pencegahan aksi tidak sengaja
 *   di sisi client. Namun file ini tidak memiliki validasi kepemilikan data,
 *   sehingga disarankan menambahkan session check di masa mendatang agar hanya
 *   admin yang berhak dapat mengirim request ke endpoint ini.
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
        $stmt = $pdo->prepare("DELETE FROM dispenser WHERE Dispenser_ID = :id");
        $stmt->execute([':id' => $id]);
        
        set_flash('success', 'Dispenser berhasil dihapus.');
    } catch (PDOException $e) {
        set_flash('error', 'Gagal menghapus dispenser: ' . $e->getMessage());
    }
}

header('Location: index.php');
exit;
