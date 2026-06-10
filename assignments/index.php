<?php
/**
 * =============================================================================
 * index.php — Halaman Daftar Manajemen Penugasan (Assignments)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman utama modul Assignments yang menampilkan seluruh
 *   data penugasan (assignment) staf maintenance terhadap dispenser air di kampus.
 *   Setiap baris tabel merepresentasikan satu penugasan yang mencakup informasi
 *   staf yang ditugaskan, lokasi dispenser, laporan air yang terkait (jika ada),
 *   status penugasan, serta jumlah log refill yang telah tercatat. Halaman ini
 *   juga menyediakan fitur filter interaktif untuk mempersempit tampilan data
 *   berdasarkan kriteria staf, status, maupun gedung.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan daftar semua penugasan staf maintenance dalam format tabel
 *   - Filter data berdasarkan nama staf, status penugasan, dan gedung
 *   - Menampilkan badge status berwarna (Pending, On Progress, Completed, Cancelled)
 *   - Menampilkan thumbnail foto laporan dengan modal popup lightbox
 *   - Menampilkan jumlah log refill yang telah dicatat per penugasan
 *   - Tombol aksi Edit dan Hapus untuk setiap baris penugasan
 *   - Tautan cepat ke form Catat Refill bila penugasan belum punya log refill
 *
 * ALUR KERJA (FLOW):
 *   1. File db.php di-include untuk mendapatkan koneksi $pdo dan helper functions
 *   2. Parameter filter (status, staff_id, gedung) dibaca dari $_GET
 *   3. Query SQL dibangun secara dinamis menggunakan WHERE clause dan parameterized query
 *   4. Data penugasan diambil dengan JOIN ke tabel maintenance_staff, dispenser,
 *      lokasi, dan water_report; subquery menghitung total refill per assignment
 *   5. Daftar staf diambil untuk mengisi dropdown filter
 *   6. layout_head.php di-include untuk merender header dan sidebar navigasi
 *   7. Flash message (sukses/error dari aksi sebelumnya) ditampilkan
 *   8. Filter bar dirender dalam tag <form method="GET">
 *   9. Tabel data dirender; bila kosong tampil pesan "tidak ada penugasan"
 *  10. Modal popup foto dan script JavaScript lightbox dirender di bagian bawah
 *  11. layout_foot.php di-include untuk menutup halaman
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - staff_dispenser_assignment : Data utama penugasan staf ke dispenser
 *   - maintenance_staff          : Data nama staf maintenance
 *   - dispenser                  : Kode dan informasi unit dispenser
 *   - lokasi                     : Gedung dan lantai lokasi dispenser
 *   - water_report               : Laporan kendala air yang dikaitkan ke penugasan
 *   - refill_logs                : Log pengisian galon (untuk hitung total refill)
 *
 * VARIABEL PENTING:
 *   - $whereClause   : String kondisi WHERE yang dibangun secara dinamis untuk filter
 *   - $params        : Array parameter PDO untuk query berdasarkan filter aktif
 *   - $assignments   : Array hasil query semua penugasan yang akan ditampilkan
 *   - $staffList     : Array daftar staf maintenance untuk dropdown filter
 *   - $statusBadge   : CSS class badge berdasarkan nilai Status penugasan
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions (h(), render_flash(), get_foto_url())
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML, script JS penutup
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin atau supervisor yang memiliki akses ke panel
 *   manajemen. Akses langsung oleh staff biasa dibatasi oleh middleware sesi.
 *
 * CATATAN PENGEMBANG:
 *   - Photo popup diimplementasikan dengan JavaScript vanilla tanpa library eksternal
 *   - Subquery COUNT(*) pada refill_logs berjalan per baris; pertimbangkan index
 *     pada kolom Assignment_ID di tabel refill_logs untuk performa optimal
 *   - Status badge menggunakan PHP match() expression (PHP 8+)
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INISIALISASI — Muat file koneksi database dan helper functions
// ===================================================
// require_once artinya: muat file db.php satu kali saja.
// __DIR__ adalah folder tempat file ini berada, jadi kita naik satu level ke atas (/../)
require_once __DIR__ . '/../db.php';

// Judul halaman dan menu aktif di sidebar navigasi
$pageTitle  = 'Assignments';
$activeMenu = 'assignments';
// ROOT = folder induk proyek, dipakai di beberapa helper function
define('ROOT', dirname(__DIR__));

// ===================================================
// FILTER DINAMIS — Bangun kondisi WHERE berdasarkan pilihan pengguna
// ===================================================
// '1=1' adalah trik supaya kondisi WHERE selalu valid meskipun tidak ada filter aktif.
// Nanti kita tambah kondisi lain di belakangnya pakai ' AND ...'
$whereClause = '1=1';
$params = []; // Array untuk menyimpan nilai-nilai filter (aman dari SQL Injection)

// Jika pengguna memilih filter Status (misal: "Pending")
if (!empty($_GET['status'])) {
    $whereClause .= ' AND a.Status = :status'; // Tambah kondisi filter status
    $params[':status'] = $_GET['status'];       // Simpan nilainya ke array params
}
// Jika pengguna memilih filter Staf tertentu
if (!empty($_GET['staff_id'])) {
    $whereClause .= ' AND a.Staff_ID = :staff_id'; // Filter berdasarkan ID staf
    $params[':staff_id'] = $_GET['staff_id'];
}
<<<<<<< Updated upstream
=======
// Jika pengguna memilih filter Gedung tertentu
if (!empty($_GET['gedung'])) {
    $whereClause .= ' AND l.Nama_Gedung = :gedung'; // Filter berdasarkan nama gedung
    $params[':gedung'] = $_GET['gedung'];
}
>>>>>>> Stashed changes

// ===================================================
// [SELECT] Ambil semua penugasan beserta info staf, dispenser, laporan & jumlah refill
// ===================================================
// Query ini menggabungkan (JOIN) beberapa tabel sekaligus:
//   a   = staff_dispenser_assignment (penugasan utama)
//   ms  = maintenance_staff          (nama staf)
//   d   = dispenser                  (kode dispenser)
//   l   = lokasi                     (gedung & lantai)
//   wr  = water_report               (laporan kendala, bisa NULL → LEFT JOIN)
//
// Subquery (SELECT COUNT(*) ...) menghitung berapa kali refill sudah dicatat
// untuk setiap penugasan, tanpa perlu query terpisah per baris.
$stmt = $pdo->prepare("
    SELECT a.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai,
           wr.Kategori AS kategori_laporan, wr.Deskripsi_Report, wr.Foto_url,
           (SELECT COUNT(*) FROM refill_logs rl WHERE rl.Assignment_ID = a.Assignment_ID) AS total_refills
    FROM staff_dispenser_assignment a
    JOIN maintenance_staff ms ON a.Staff_ID = ms.Staff_ID         -- Gabungkan dengan tabel staf
    JOIN dispenser d ON a.Dispenser_ID = d.Dispenser_ID           -- Gabungkan dengan tabel dispenser
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID                    -- Gabungkan dengan tabel lokasi
    LEFT JOIN water_report wr ON a.WaterReport_ID = wr.WaterReport_ID -- LEFT JOIN: penugasan tanpa laporan tetap tampil
    WHERE $whereClause
    ORDER BY a.Created_At DESC
"); // Urut dari yang paling baru dibuat
$stmt->execute($params);       // Jalankan query dengan parameter filter (aman dari SQL Injection)
$assignments = $stmt->fetchAll(); // Ambil semua baris hasil query sebagai array

// ===================================================
// [SELECT] Ambil daftar staf untuk isian dropdown filter
// ===================================================
// Query sederhana tanpa parameter, langsung jalankan dengan query() (bukan prepare)
$staffList = $pdo->query("SELECT Staff_ID, Nama FROM maintenance_staff ORDER BY Nama")->fetchAll();

// ===================================================
// TAMPILKAN HEADER HALAMAN — Muat template header & sidebar navigasi
// ===================================================
include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); // Tampilkan pesan sukses/error dari aksi sebelumnya (misalnya setelah hapus) ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Penugasan (Assignments)</div>
        <div class="page-subtitle"><?= count($assignments) ?> penugasan terdaftar</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">assignment_turned_in</span> Tambah Penugasan
    </a>
</div>

<!-- Filter Bar: Form GET — nilai filter dikirim lewat URL (?status=Pending&staff_id=2 dst) -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="min-width:180px; flex:1;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Pilih Staff</label>
            <select name="staff_id" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Staff</option>
                <?php foreach ($staffList as $s): ?>
                <!-- Tandai 'selected' kalau nilai di URL cocok dengan ID staf ini -->
                <option value="<?= $s['Staff_ID'] ?>" <?= ($_GET['staff_id'] ?? '') == $s['Staff_ID'] ? 'selected' : '' ?>><?= h($s['Nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Status Penugasan</label>
            <select name="status" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Status</option>
                <?php foreach (['Pending','On Progress','Completed','Cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="mat-icon" style="font-size:18px">search</span> Filter
        </button>
        <a href="index.php" class="btn-secondary" style="padding:10px 20px;">Reset</a><!-- Kembali ke tampilan tanpa filter -->
    </form>
</div>

<!-- Tabel Daftar Penugasan -->
<div class="card" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Staff Maintenance</th>
                <th>Dispenser / Lokasi</th>
                <th>Terkait Laporan</th>
                <th>Status</th>
                <th>Logs Refill</th>
                <th>Waktu Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($assignments)): ?>
            <!-- Tampil jika tidak ada data sama sekali (atau hasil filter kosong) -->
            <tr>
                <td colspan="8" style="text-align:center;color:#9ca3af;padding:48px;">
                    <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">assignment_turned_in</span>
                    Tidak ada penugasan ditemukan
                </td>
            </tr>
        <?php else: foreach ($assignments as $i => $a):
            // ===================================================
            // TENTUKAN WARNA BADGE STATUS — Setiap status punya warna berbeda
            // ===================================================
            // match() adalah versi modern dari switch(); cocok nilai $a['Status'] lalu pilih class CSS-nya
            $statusBadge = match($a['Status']) {
                'Pending'     => 'badge-yellow', // Menunggu — warna kuning
                'On Progress' => 'badge-blue',   // Sedang dikerjakan — warna biru
                'Completed'   => 'badge-green',  // Selesai — warna hijau
                'Cancelled'   => 'badge-red',    // Dibatalkan — warna merah
                default       => 'badge-gray',   // Status tidak dikenal — warna abu
            };
        ?>
            <tr>
                <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td><!-- Nomor urut baris (mulai dari 1) -->
                <td>
                    <span style="font-weight:600;color:#0b1f3a;"><?= h($a['nama_staff']) ?></span><!-- h() = htmlspecialchars, mencegah XSS -->
                </td>
                <td>
                    <div style="font-weight:700;color:#0058bc;"><?= h($a['Kode_Dispenser']) ?></div>
                    <div style="font-size:.78rem;color:#50616b;"><?= h($a['Nama_Gedung']) ?> Lt. <?= h($a['Lantai']) ?></div>
                </td>
                <td>
                    <?php if ($a['WaterReport_ID']): ?>
                        <!-- Penugasan ini terkait dengan laporan kendala air -->
                        <div style="display:flex; gap:8px; align-items:center;">
                            <?php if ($a['Foto_url']): ?>
<<<<<<< Updated upstream
                                <a href="<?= h(get_foto_url($a['Foto_url'])) ?>" target="_blank" style="flex-shrink:0;">
                                    <img src="<?= h(get_foto_url($a['Foto_url'])) ?>" alt="Foto" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;display:block;" class="hover:scale-105 transition-all">
                                </a>
=======
                                <!-- Tampilkan thumbnail foto; klik foto untuk membuka popup besar -->
                                <img src="<?= h(get_foto_url($a['Foto_url'])) ?>" alt="Foto" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;display:block;cursor:pointer;flex-shrink:0;" onclick="openPhotoPopup('<?= h(get_foto_url($a['Foto_url'])) ?>')">
>>>>>>> Stashed changes
                            <?php endif; ?>
                            <div>
                                <div style="font-size:.8rem;font-weight:600;color:#c2410c;">[WR #<?= $a['WaterReport_ID'] ?>] <?= h($a['kategori_laporan']) ?></div>
                                <!-- title="" menampilkan teks lengkap saat mouse diarahkan ke elemen -->
                                <div style="font-size:.75rem;color:#6b7280;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= h($a['Deskripsi_Report']) ?>"><?= h($a['Deskripsi_Report']) ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Penugasan tidak terkait laporan, berarti ini pengecekan rutin -->
                        <span style="color:#9ca3af;font-size:.8rem;">Routine / Mandiri</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $statusBadge ?>"><?= h($a['Status']) ?></span></td><!-- Badge warna sesuai status -->
                <td style="text-align:center;">
                    <?php if ($a['total_refills'] > 0): ?>
                        <!-- Kalau sudah ada refill, tampilkan jumlahnya -->
                        <span class="badge badge-green" style="font-weight:700;"><?= $a['total_refills'] ?> Refill</span>
                    <?php else: ?>
                        <?php if ($a['Status'] !== 'Cancelled'): ?>
                            <!-- Belum ada refill dan penugasan masih aktif → tampilkan tombol catat refill -->
                            <a href="../refill/create.php?assignment_id=<?= $a['Assignment_ID'] ?>" class="btn-edit" style="font-size:.75rem;padding:4px 8px;background:#e8f0fe;color:#0058bc;">
                                <span class="mat-icon" style="font-size:12px;vertical-align:middle;">add</span> Refill
                            </a>
                        <?php else: ?>
                            <!-- Penugasan dibatalkan, tidak bisa catat refill -->
                            <span style="color:#9ca3af;font-size:.8rem;">—</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;">
                    <?= date('d/m/y H:i', strtotime($a['Created_At'])) ?><!-- Format tanggal: hari/bulan/tahun jam:menit -->
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <!-- Tombol Edit: arahkan ke halaman edit dengan ID penugasan di URL -->
                        <a href="edit.php?id=<?= $a['Assignment_ID'] ?>" class="btn-edit" title="Edit Penugasan">
                            <span class="mat-icon" style="font-size:15px">edit</span>
                        </a>
                        <!-- Tombol Hapus: pakai form POST agar lebih aman daripada link GET -->
                        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus penugasan ini? Log refill terkait juga akan terhapus.')">
                            <!-- Input tersembunyi: kirim ID penugasan ke delete.php tanpa tampil di layar -->
                            <input type="hidden" name="id" value="<?= $a['Assignment_ID'] ?>">
                            <button type="submit" class="btn-danger" title="Hapus">
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

<<<<<<< Updated upstream
<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
=======
<?php include __DIR__ . '/../_partials/layout_foot.php'; // Tutup halaman dengan footer ?>

<!-- ===================================================
     MODAL POPUP FOTO — Tampilkan foto besar saat thumbnail diklik
     =================================================== -->
<!-- Modal ini tersembunyi (display:none) dan hanya muncul saat fungsi openPhotoPopup() dipanggil -->
<div id="photo-popup" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;" onclick="closePhotoPopup()">
    <!-- Klik di luar gambar → tutup popup (onclick di parent div) -->
    <div style="position:relative;max-width:90vw;max-height:90vh;" onclick="event.stopPropagation()">
        <!-- event.stopPropagation() → klik di dalam kotak gambar tidak menutup popup -->
        <img id="photo-popup-img" src="" alt="Foto" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:12px;display:block;">
        <button onclick="closePhotoPopup()" style="position:absolute;top:-14px;right:-14px;background:#fff;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.3);">&times;</button><!-- Tombol × untuk tutup -->
    </div>
</div>
<script>
// ===================================================
// JAVASCRIPT — Fungsi buka dan tutup popup foto
// ===================================================

// Fungsi ini dipanggil saat thumbnail foto diklik
function openPhotoPopup(src) {
    document.getElementById('photo-popup-img').src = src; // Set sumber gambar popup
    var popup = document.getElementById('photo-popup');
    popup.style.display = 'flex'; // Tampilkan popup (ubah dari 'none' ke 'flex')
    document.body.style.overflow = 'hidden'; // Matikan scroll halaman saat popup terbuka
}
// Fungsi ini menutup popup foto
function closePhotoPopup() {
    document.getElementById('photo-popup').style.display = 'none'; // Sembunyikan popup
    document.body.style.overflow = ''; // Aktifkan kembali scroll halaman
}
// Tutup popup juga ketika pengguna menekan tombol Escape di keyboard
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePhotoPopup();
});
</script>
>>>>>>> Stashed changes
