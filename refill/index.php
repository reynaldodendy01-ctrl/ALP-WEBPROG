<?php
/**
 * =============================================================================
 * index.php — Halaman Daftar Riwayat Pengisian Galon (Refill Logs)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman utama modul Refill yang menampilkan seluruh riwayat
 *   pencatatan pengisian ulang galon air (refill logs) di semua unit dispenser
 *   kampus. Setiap entri log mencatat informasi staf yang melakukan pengisian,
 *   kode dispenser beserta lokasinya, catatan tambahan pengisian, dan waktu refill
 *   dilakukan. Halaman ini berfungsi sebagai audit trail aktivitas operasional
 *   maintenance galon di seluruh kampus.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan semua log refill dalam format tabel yang terurut dari yang terbaru
 *   - Menampilkan informasi staf PIC, kode dispenser, lokasi gedung & lantai
 *   - Menampilkan catatan pengisian dan waktu refill dalam format tanggal WIB
 *   - Tombol hapus log refill untuk setiap baris (dengan konfirmasi dialog)
 *   - Tombol "Catat Refill Baru" hanya muncul untuk admin (bukan untuk role Staff)
 *   - Menampilkan pesan kosong apabila belum ada log refill tercatat
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk mendapatkan koneksi $pdo dan helper functions
 *   2. Query SELECT dijalankan dengan JOIN ke tabel assignment, staff, dispenser, lokasi
 *      diurutkan berdasarkan Refill_At DESC (terbaru di atas)
 *   3. Semua log refill disimpan dalam array $refills
 *   4. layout_head.php di-include untuk merender header dan sidebar navigasi
 *   5. Flash message dari aksi sebelumnya ditampilkan
 *   6. Tombol "Catat Refill Baru" ditampilkan secara kondisional berdasarkan role sesi
 *   7. Tabel data dirender dengan loop foreach; bila kosong tampil pesan informatif
 *   8. Setiap baris memiliki form POST ke delete.php untuk menghapus log
 *   9. layout_foot.php di-include untuk menutup halaman
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - refill_logs                : Data utama riwayat pengisian galon yang ditampilkan
 *   - staff_dispenser_assignment : Relasi untuk mendapatkan staf dan dispenser terkait
 *   - maintenance_staff          : Nama staf yang melakukan refill
 *   - dispenser                  : Kode unit dispenser
 *   - lokasi                     : Gedung dan lantai lokasi dispenser
 *
 * VARIABEL PENTING:
 *   - $refills : Array semua log refill hasil query JOIN yang akan ditampilkan di tabel
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions (h(), render_flash())
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML dan script JS penutup
 *
 * AKSES:
 *   Dapat diakses oleh admin dan staff yang login. Namun tombol "Catat Refill Baru"
 *   hanya tampil untuk pengguna dengan role selain 'Staff' (yaitu admin/supervisor).
 *
 * CATATAN PENGEMBANG:
 *   - Role check menggunakan $_SESSION['staff_role']; pastikan session sudah diinisialisasi
 *     melalui db.php atau middleware sebelum halaman ini dimuat
 *   - Waktu Refill_At ditampilkan dalam format "d M Y, H:i WIB" menggunakan date() PHP
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Refill Logs';
$activeMenu = 'refill';
define('ROOT', dirname(__DIR__));

$stmt = $pdo->prepare("
    SELECT rl.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
    FROM refill_logs rl
    JOIN staff_dispenser_assignment sda ON rl.Assignment_ID = sda.Assignment_ID
    JOIN maintenance_staff ms ON sda.Staff_ID = ms.Staff_ID
    JOIN dispenser d ON sda.Dispenser_ID = d.Dispenser_ID
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    ORDER BY rl.Refill_At DESC
");
$stmt->execute();
$refills = $stmt->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Riwayat Pengisian Galon (Refill Logs)</div>
        <div class="page-subtitle"><?= count($refills) ?> refill tercatat</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">recycling</span> Catat Refill Baru
    </a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Staff PIC</th>
            <th>Dispenser / Lokasi</th>
            <th>Catatan</th>
            <th>Waktu Refill</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($refills)): ?>
        <tr>
            <td colspan="6" style="text-align:center;color:#9ca3af;padding:48px;">
                <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">recycling</span>
                Belum ada log refill tercatat
            </td>
        </tr>
    <?php else: foreach ($refills as $i => $r): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td>
            <td>
                <span style="font-weight:600;color:#0b1f3a;"><?= h($r['nama_staff']) ?></span>
            </td>
            <td>
                <div style="font-weight:700;color:#0058bc;"><?= h($r['Kode_Dispenser']) ?></div>
                <div style="font-size:.78rem;color:#50616b;"><?= h($r['Nama_Gedung']) ?> Lt. <?= h($r['Lantai']) ?></div>
            </td>
            <td style="font-size:.85rem;max-width:300px;word-wrap:break-word;white-space:normal;"><?= h($r['Catatan'] ?? '—') ?></td>
            <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;">
                <?= date('d M Y, H:i', strtotime($r['Refill_At'])) ?> WIB
            </td>
            <td>
                <form method="POST" action="delete.php" onsubmit="return confirm('Hapus log refill ini?')">
                    <input type="hidden" name="id" value="<?= $r['Logs_ID'] ?>">
                    <button type="submit" class="btn-danger" style="padding:6px 12px; font-size:.75rem;">
                        <span class="mat-icon" style="font-size:14px;vertical-align:middle;">delete</span> Hapus
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
