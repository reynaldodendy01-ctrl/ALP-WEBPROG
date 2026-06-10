<?php
/**
 * =============================================================================
 * index.php — Halaman Daftar Manajemen Pelapor (Reporters)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman utama modul Reporters yang menampilkan seluruh
 *   data individu (mahasiswa, dosen, atau karyawan) yang pernah mengajukan
 *   laporan kendala dispenser melalui sistem CariGalon. Setiap baris dalam
 *   tabel menampilkan avatar inisial, nama lengkap, NIM, dan jumlah laporan
 *   yang pernah dibuat oleh pelapor tersebut. Halaman ini menyediakan operasi
 *   CRUD dasar: melihat daftar, menambah, mengedit, dan menghapus data pelapor.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan semua pelapor terdaftar beserta jumlah laporan masing-masing
 *   - Menampilkan avatar inisial nama pelapor dengan warna gradasi biru
 *   - Tombol Edit untuk memperbarui data pelapor (nama & NIM)
 *   - Tombol Hapus dengan konfirmasi dialog sebelum menghapus
 *   - Tombol "Tambah Pelapor" untuk mendaftarkan pelapor baru secara manual
 *   - Menampilkan jumlah total pelapor terdaftar di subtitle header
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk koneksi database dan helper functions
 *   2. Query SELECT dijalankan dengan LEFT JOIN ke water_report dan GROUP BY
 *      Reporter_ID untuk menghitung jumlah_laporan per pelapor
 *   3. Hasil disimpan dalam array $reporters, diurutkan berdasarkan Nama ASC
 *   4. layout_head.php di-include untuk header dan sidebar
 *   5. Flash message ditampilkan
 *   6. Tabel data dirender dengan loop foreach; bila kosong, tampil pesan informatif
 *   7. Setiap baris memiliki link Edit dan form POST ke delete.php
 *   8. layout_foot.php di-include untuk menutup halaman
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - reporter     : Data utama pelapor yang ditampilkan (nama, NIM, Reporter_ID)
 *   - water_report : Di-JOIN (LEFT JOIN) untuk menghitung jumlah laporan per pelapor
 *
 * VARIABEL PENTING:
 *   - $reporters : Array semua pelapor beserta jumlah laporan masing-masing
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions (h(), render_flash())
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML dan script JS penutup
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin. Data pelapor bersifat sensitif (mengandung
 *   identitas mahasiswa/karyawan kampus).
 *
 * CATATAN PENGEMBANG:
 *   - Avatar inisial digenerate dengan mb_substr($r['Nama'], 0, 1) untuk mendukung
 *     karakter multibyte (nama dengan huruf non-ASCII)
 *   - Penghapusan pelapor harus mempertimbangkan cascading ke tabel water_report;
 *     pastikan foreign key constraint sudah dikonfigurasi dengan tepat di database
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Reporters';
$activeMenu = 'reporters';
define('ROOT', dirname(__DIR__));

$reporters = $pdo->query("
    SELECT r.*, COUNT(wr.WaterReport_ID) AS jumlah_laporan
    FROM reporter r
    LEFT JOIN water_report wr ON wr.Reporter_ID = r.Reporter_ID
    GROUP BY r.Reporter_ID
    ORDER BY r.Nama
")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Pelapor (Reporters)</div>
        <div class="page-subtitle"><?= count($reporters) ?> pelapor terdaftar</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">person_add</span> Tambah Pelapor
    </a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nama Pelapor</th>
            <th>NIM</th>
            <th>Jumlah Laporan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($reporters)): ?>
        <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada pelapor terdaftar</td></tr>
    <?php else: foreach ($reporters as $i => $r): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0058bc,#1a78e5);
                          display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0;">
                        <?= strtoupper(mb_substr($r['Nama'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600"><?= h($r['Nama']) ?></span>
                </div>
            </td>
            <td style="font-weight:600"><?= h($r['Nim']) ?></td>
            <td>
                <span style="font-size:1rem;font-weight:800;color:#0058bc"><?= $r['jumlah_laporan'] ?></span>
                <span style="font-size:.8rem;color:#9ca3af"> laporan</span>
            </td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="edit.php?id=<?= $r['Reporter_ID'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus pelapor <?= h(addslashes($r['Nama'])) ?>?')">
                        <input type="hidden" name="id" value="<?= $r['Reporter_ID'] ?>">
                        <button type="submit" class="btn-danger">
                            <span class="mat-icon" style="font-size:15px">delete</span>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
