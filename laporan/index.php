<?php
/**
 * =============================================================================
 * index.php — Halaman Daftar Semua Laporan Kendala Air
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman utama modul Laporan pada sistem CariGalon yang
 *   menampilkan seluruh laporan kendala dispenser air (galon kosong maupun
 *   dispenser rusak/bocor) yang masuk ke dalam sistem. Halaman ini menyediakan
 *   tampilan tabel lengkap beserta fitur pencarian dan filter multikriteria
 *   sehingga admin maupun staf dapat dengan mudah memantau status setiap laporan.
 *   Aksi yang tersedia disesuaikan berdasarkan peran pengguna yang sedang login
 *   (Super Admin atau Staff biasa), memastikan hak akses yang tepat.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan daftar seluruh laporan kendala dalam format tabel terurut
 *     berdasarkan waktu laporan terbaru (ORDER BY Reported_At DESC).
 *   - Menyediakan filter dinamis berdasarkan: status laporan, kategori masalah,
 *     gedung lokasi dispenser, dan kata kunci pencarian bebas (nama pelapor,
 *     kode dispenser, atau deskripsi laporan).
 *   - Menampilkan badge status berwarna (Pending = kuning, Diproses = biru,
 *     Selesai = hijau, Ditolak = merah) untuk kemudahan identifikasi visual.
 *   - Menampilkan foto lampiran laporan dengan dukungan modal preview foto
 *     (lightbox) tanpa meninggalkan halaman.
 *   - Menampilkan nama staf yang sedang ditugaskan pada setiap laporan (subquery
 *     ke tabel staff_dispenser_assignment).
 *   - Memberikan tombol aksi yang relevan:
 *       * Staff: tombol "Ambil Tugas" (jika Pending) dan "Tolak".
 *       * Admin: tombol Edit, Tolak, dan Hapus.
 *
 * ALUR KERJA (FLOW):
 *   1. File db.php di-include untuk mendapatkan koneksi PDO dan helper functions.
 *   2. Parameter filter dibaca dari $_GET (status, kategori, gedung, q).
 *   3. Query SELECT dibangun secara dinamis dengan klausa WHERE sesuai filter aktif,
 *      disertai subquery untuk mendapatkan nama staf yang ditugaskan.
 *   4. Data laporan diambil dari database dan disimpan dalam array $reports.
 *   5. Daftar gedung unik diambil dari tabel lokasi untuk mengisi dropdown filter.
 *   6. layout_head.php di-include untuk merender HTML header, sidebar, dan navigasi.
 *   7. Flash message ditampilkan (jika ada pesan sukses/gagal dari aksi sebelumnya).
 *   8. Form filter dan tabel laporan dirender; baris tabel diisi secara iteratif.
 *   9. Modal lightbox foto dirender di akhir halaman beserta JavaScript pendukungnya.
 *  10. layout_foot.php di-include untuk menutup struktur HTML dan memuat script footer.
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - water_report              : Tabel utama berisi semua laporan kendala air.
 *   - reporter                  : Data pelapor (nama dan NIM mahasiswa/pengguna).
 *   - dispenser                 : Data dispenser (kode identifikasi unit).
 *   - lokasi                    : Data lokasi dispenser (gedung dan lantai).
 *   - staff_dispenser_assignment: Data penugasan staf terhadap laporan (subquery).
 *   - maintenance_staff         : Data staf maintenance (untuk nama yang ditugaskan).
 *
 * VARIABEL PENTING:
 *   - $whereClause   : String klausa WHERE SQL yang dibangun secara dinamis berdasarkan filter.
 *   - $params        : Array parameter PDO untuk binding nilai filter pada prepared statement.
 *   - $reports       : Array asosiatif hasil fetch semua laporan sesuai filter yang aktif.
 *   - $buildings     : Array daftar gedung unik untuk opsi dropdown filter gedung.
 *   - $validCategories: Array kategori masalah yang valid ('Galon Kosong', 'Dispenser Rusak / Bocor').
 *   - $statusBadge   : String nama kelas CSS badge yang ditentukan berdasarkan status laporan.
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php              : Koneksi database PDO & helper functions (h(), get_foto_url(), render_flash()).
 *   - layout_head.php     : Header HTML, sidebar navigasi, dan tag pembuka konten utama.
 *   - layout_foot.php     : Footer HTML, script penutup, dan tag penutup konten utama.
 *
 * AKSES:
 *   Dapat diakses oleh semua pengguna yang sudah login (Super Admin & Staff).
 *   Namun tombol aksi yang ditampilkan dibedakan berdasarkan peran sesi pengguna
 *   ($_SESSION['staff_role']): Staff hanya bisa "Ambil Tugas" dan "Tolak",
 *   sedangkan Admin mendapatkan akses penuh (Edit, Tolak, Hapus).
 *
 * CATATAN PENGEMBANG:
 *   - Subquery untuk mendapatkan assigned_staff dibatasi dengan LIMIT 1 untuk
 *     mengambil satu staf saja; jika ada multi-assignment, hanya nama pertama yang muncul.
 *   - Fungsi get_foto_url() dari db.php menangani normalisasi path foto (lokal vs URL eksternal).
 *   - Modal foto menggunakan inline JavaScript dan style; pertimbangkan refactor ke file JS
 *     terpisah jika kode berkembang.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INISIALISASI — Muat file koneksi database dan atur variabel halaman
// ===================================================
// require_once: memuat file db.php sekali saja (tidak boleh double-load)
// __DIR__ berarti "folder tempat file ini berada" supaya path-nya selalu tepat
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Laporan';  // Judul tab browser
$activeMenu = 'laporan';  // Menandai menu "laporan" di sidebar sebagai aktif
define('ROOT', dirname(__DIR__)); // ROOT = folder utama proyek (satu level di atas folder laporan/)

<<<<<<< Updated upstream
// ── Filters ─────────────────────────────────────────────────────────────────
=======
// Daftar kategori masalah yang diizinkan di sistem ini
$validCategories = ['Galon Kosong', 'Dispenser Rusak / Bocor'];

// ===================================================
// FILTER DINAMIS — Bangun klausa WHERE berdasarkan input pengguna dari URL
// ===================================================
// Bayangkan WHERE SQL seperti corong penyaring: semakin banyak filter,
// semakin sedikit data yang lolos dan ditampilkan.

// '1=1' adalah trik SQL: kondisi yang selalu benar, jadi aman sebagai titik awal
// lalu kita sambung dengan AND ... AND ... sesuai filter yang aktif
>>>>>>> Stashed changes
$whereClause = '1=1';
$params = []; // Array untuk menyimpan nilai-nilai filter yang akan dikirim ke query SQL

// Jika pengguna memilih filter Status dari dropdown (contoh: ?status=Pending di URL)
if (!empty($_GET['status'])) {
<<<<<<< Updated upstream
    $whereClause .= ' AND wr.Status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['kategori'])) {
    $whereClause .= ' AND wr.Kategori = :kategori';
    $params[':kategori'] = $_GET['kategori'];
}
if (!empty($_GET['q'])) {
    $whereClause .= ' AND (rep.Nama LIKE :q OR d.Kode_Dispenser LIKE :q OR wr.Deskripsi_Report LIKE :q)';
    $params[':q'] = '%' . $_GET['q'] . '%';
=======
    $whereClause .= ' AND wr.Status = :status'; // Tambah syarat: status harus cocok
    $params[':status'] = $_GET['status'];        // Simpan nilai status ke array params
>>>>>>> Stashed changes
}

// Jika pengguna memilih filter Kategori (contoh: ?kategori=Galon+Kosong di URL)
if (!empty($_GET['kategori'])) {
    $whereClause .= ' AND wr.Kategori = :kategori'; // Tambah syarat: kategori harus cocok
    $params[':kategori'] = $_GET['kategori'];        // Simpan nilai kategori
}

// Jika pengguna mengetik kata kunci di kotak pencarian (contoh: ?q=lantai+3 di URL)
if (!empty($_GET['q'])) {
    // Cari di kolom nama pelapor, kode dispenser, atau deskripsi laporan
    $whereClause .= ' AND (rep.Nama LIKE :q OR d.Kode_Dispenser LIKE :q OR wr.Deskripsi_Report LIKE :q)';
    // LIKE '%kata%' artinya: cari yang mengandung kata ini di mana saja dalam teks
    $params[':q'] = '%' . $_GET['q'] . '%'; // Tambah % di kiri dan kanan untuk pencarian "mengandung"
}

// Jika pengguna memilih filter Gedung dari dropdown (contoh: ?gedung=Gedung+A di URL)
if (!empty($_GET['gedung'])) {
    $whereClause .= ' AND l.Nama_Gedung = :gedung'; // Tambah syarat: nama gedung harus cocok
    $params[':gedung'] = $_GET['gedung'];             // Simpan nilai gedung
}

// ===================================================
// [SELECT] Ambil semua laporan dari database sesuai filter yang aktif
// ===================================================
// Query ini mengambil data dari banyak tabel sekaligus menggunakan JOIN
// JOIN = menggabungkan baris dari dua tabel yang punya kolom yang cocok
$stmt = $pdo->prepare("
    SELECT wr.*, rep.Nama AS nama_pelapor, rep.Nim AS nim_pelapor,
           d.Kode_Dispenser, l.Nama_Gedung, l.Lantai,
           -- Subquery: ambil nama staf yang sedang mengerjakan laporan ini
           -- Subquery adalah query di dalam query, dieksekusi per baris laporan
           (SELECT s.Nama FROM staff_dispenser_assignment sda 
            JOIN maintenance_staff s ON sda.Staff_ID = s.Staff_ID
            WHERE sda.WaterReport_ID = wr.WaterReport_ID AND sda.Status != 'Cancelled' LIMIT 1) AS assigned_staff
    FROM water_report wr
    JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID      -- Gabung tabel pelapor
    JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID       -- Gabung tabel dispenser
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID                 -- Gabung tabel lokasi
    WHERE $whereClause                                          -- Terapkan filter yang sudah dibangun
    ORDER BY wr.Reported_At DESC                               -- Urutkan dari laporan terbaru
");
$stmt->execute($params); // Jalankan query dengan nilai-nilai filter (aman dari SQL Injection)
$reports = $stmt->fetchAll(); // Ambil SEMUA baris hasil sebagai array

<<<<<<< Updated upstream
=======
// ===================================================
// [SELECT] Ambil daftar gedung unik untuk isian dropdown filter
// ===================================================
// DISTINCT = hanya ambil nilai yang berbeda (tidak duplikat)
// Hasilnya dipakai untuk mengisi pilihan dropdown "Gedung" di form filter
$buildings = $pdo->query("SELECT DISTINCT Nama_Gedung FROM lokasi ORDER BY Nama_Gedung")->fetchAll();

// ===================================================
// RENDER HALAMAN — Muat header HTML, sidebar, dan navigasi
// ===================================================
// Semua kode PHP di atas sudah selesai, sekarang saatnya tampilkan HTML-nya
>>>>>>> Stashed changes
include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); // Tampilkan pesan sukses/error dari aksi sebelumnya (jika ada) ?>

<div class="page-header">
    <div>
        <div class="page-title">Laporan Kendala Air (Water Reports)</div>
        <div class="page-subtitle"><?= count($reports) ?> laporan masuk</div>
        <?php // count($reports) = hitung jumlah laporan yang ditemukan setelah filter diterapkan ?>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">edit_note</span> Buat Laporan
    </a>
</div>

<!-- Filter Bar: Form pencarian dan filter yang dikirim via GET (URL) -->
<!-- Saat tombol "Filter" diklik, halaman akan reload dengan parameter filter di URL -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <?php // method="GET": data form dikirim lewat URL, bukan body request (beda dengan POST) ?>
        <div style="flex:1;min-width:180px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Cari Laporan / Pelapor</label>
            <?php // h() = fungsi untuk mengamankan output dari karakter berbahaya (XSS prevention) ?>
            <?php // $_GET['q'] ?? '' artinya: ambil nilai 'q' dari URL, kalau tidak ada gunakan string kosong ?>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   placeholder="Ketik nama pelapor, dispenser, atau deskripsi..." class="form-input" style="padding:9px 12px;">
        </div>
<<<<<<< Updated upstream
=======
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Gedung</label>
            <select name="gedung" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Gedung</option>
                <?php foreach ($buildings as $b): // Tampilkan setiap gedung sebagai opsi dropdown ?>
                <option value="<?= h($b['Nama_Gedung']) ?>" <?= ($_GET['gedung'] ?? '') === $b['Nama_Gedung'] ? 'selected' : '' ?>>
                    <?php // Jika gedung ini sedang dipilih di filter, tambahkan atribut 'selected' ?>
                    <?= h($b['Nama_Gedung']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
>>>>>>> Stashed changes
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Kategori Masalah</label>
            <select name="kategori" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Kategori</option>
<<<<<<< Updated upstream
                <?php foreach (['Galon Kosong','Dispenser Rusak','Kebocoran','Distribusi Tidak Merata','Lainnya'] as $kat): ?>
=======
                <?php foreach ($validCategories as $kat): // Loop tiap kategori masalah ?>
>>>>>>> Stashed changes
                <option value="<?= $kat ?>" <?= ($_GET['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Status</label>
            <select name="status" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Status</option>
                <?php foreach (['Pending','Diproses','Selesai','Ditolak'] as $st): // Loop tiap opsi status ?>
                <option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="mat-icon" style="font-size:18px">search</span> Filter
        </button>
        <?php // Tombol Reset: kembali ke index.php tanpa parameter filter apapun ?>
        <a href="index.php" class="btn-secondary" style="padding:10px 20px;">Reset</a>
    </form>
</div>

<!-- Tabel Laporan: Menampilkan semua laporan yang lolos filter -->
<div class="card" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Pelapor</th>
                <th>Dispenser / Lokasi</th>
                <th>Masalah</th>
                <th>Deskripsi</th>
                <th>Foto</th>
                <th>Status</th>
                <th>Ditugaskan Ke</th>
                <th>Waktu Laporan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($reports)): // Jika tidak ada laporan yang cocok dengan filter ?>
            <tr>
                <td colspan="10" style="text-align:center;color:#9ca3af;padding:48px;">
                    <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">report</span>
                    Tidak ada laporan kendala ditemukan
                </td>
            </tr>
        <?php else: foreach ($reports as $i => $r): // Loop tiap laporan, $i = nomor urut, $r = data laporan

            // ===================================================
            // TENTUKAN WARNA BADGE STATUS — Sesuaikan warna dengan status laporan
            // ===================================================
            // match() = seperti switch/case tapi lebih ringkas di PHP 8+
            // Hasilnya adalah nama kelas CSS untuk warna badge
            $statusBadge = match($r['Status']) {
                'Pending'  => 'badge-yellow', // Kuning = menunggu
                'Diproses' => 'badge-blue',   // Biru = sedang dikerjakan
                'Selesai'  => 'badge-green',  // Hijau = sudah selesai
                'Ditolak'  => 'badge-red',    // Merah = ditolak
                default    => 'badge-gray',   // Abu-abu = status tidak dikenal
            };
        ?>
            <tr>
                <?php // $i + 1 karena array PHP mulai dari 0, tapi kita ingin tampilkan nomor mulai 1 ?>
                <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td>
                <td>
                    <?php // h() membersihkan teks dari karakter berbahaya sebelum ditampilkan ke HTML ?>
                    <div style="font-weight:600;color:#0b1f3a;"><?= h($r['nama_pelapor']) ?></div>
                    <div style="font-size:.78rem;color:#6b7280;">NIM: <?= h($r['nim_pelapor']) ?></div>
                </td>
                <td>
                    <div style="font-weight:700;color:#0058bc;"><?= h($r['Kode_Dispenser']) ?></div>
                    <div style="font-size:.78rem;color:#50616b;"><?= h($r['Nama_Gedung']) ?> Lt. <?= h($r['Lantai']) ?></div>
                </td>
                <td><span class="badge badge-orange"><?= h($r['Kategori']) ?></span></td>
                <td style="font-size:.82rem;max-width:240px;word-wrap:break-word;white-space:normal;"><?= h($r['Deskripsi_Report']) ?></td>
                <td>
<<<<<<< Updated upstream
                    <?php if ($r['Foto_url']): ?>
                        <a href="<?= h(get_foto_url($r['Foto_url'])) ?>" target="_blank">
                            <img src="<?= h(get_foto_url($r['Foto_url'])) ?>" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;" class="hover:scale-105 transition-all">
                        </a>
                    <?php else: ?>
=======
                    <?php if ($r['Foto_url']): // Cek apakah laporan ini punya foto lampiran ?>
                        <?php // get_foto_url() mengubah path foto di database menjadi URL yang bisa diakses browser ?>
                        <img src="<?= h(get_foto_url($r['Foto_url'])) ?>" alt="Foto"
                             onclick="showPhotoModal('<?= h(get_foto_url($r['Foto_url'])) ?>')"
                             <?php // Klik foto → panggil fungsi JavaScript showPhotoModal() untuk buka modal preview ?>
                             style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;cursor:pointer;">
                    <?php else: // Jika tidak ada foto, tampilkan tanda strip ?>
>>>>>>> Stashed changes
                        <span style="color:#9ca3af">—</span>
                    <?php endif; ?>
                </td>
                <?php // Tampilkan badge status dengan warna yang sudah ditentukan di atas ?>
                <td><span class="badge <?= $statusBadge ?>"><?= h($r['Status']) ?></span></td>
                <?php // ?? '—' artinya: jika assigned_staff kosong/null, tampilkan tanda strip ?>
                <td style="font-size:.85rem;font-weight:500;color:#0b1f3a;"><?= h($r['assigned_staff'] ?? '—') ?></td>
                <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;">
                    <?php // strtotime() mengubah teks tanggal ke angka timestamp, lalu date() memformat ulang ?>
                    <?= date('d/m/y H:i', strtotime($r['Reported_At'])) ?>
                    <?php if ($r['Resolved_At']): // Tampilkan waktu selesai jika laporan sudah diselesaikan ?>
                        <div style="font-size:.7rem;color:#10b981;">Selesai: <?= date('d/m H:i', strtotime($r['Resolved_At'])) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <?php // ===================================================
                        // TOMBOL AKSI — Tampilkan tombol berbeda tergantung peran pengguna
                        // ===================================================
                        // Cek apakah pengguna yang login adalah Staff biasa (bukan Admin)
                        ?>
                        <?php if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff'): ?>
                            <?php // Tombol "Ambil Tugas" hanya muncul jika laporan masih Pending ?>
                            <?php if ($r['Status'] === 'Pending'): ?>
                                <?php // Link ke ambil.php dengan ID laporan, untuk mengklaim laporan ini ?>
                                <a href="ambil.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-primary" style="padding:6px 12px; font-size:.75rem; background: linear-gradient(135deg, #0058bc, #1d4ed8);">
                                    <span class="mat-icon" style="font-size:14px; vertical-align:middle;">assignment</span> Ambil Tugas
                                </a>
                            <?php endif; ?>

                            <?php // Tombol "Tolak" muncul jika laporan masih Pending atau sedang Diproses ?>
                            <?php if (in_array($r['Status'], ['Pending', 'Diproses'])): ?>
                                <?php // onclick confirm() = minta konfirmasi dulu sebelum lanjut ke tolak.php ?>
                                <a href="tolak.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-danger" style="padding:6px 12px; font-size:.75rem;" onclick="return confirm('Tolak laporan ini sebagai laporan palsu?')">
                                    <span class="mat-icon" style="font-size:14px; vertical-align:middle;">block</span> Tolak
                                </a>
                            <?php elseif ($r['Status'] === 'Selesai'): // Jika sudah selesai, tidak ada tombol aksi ?>
                                <span class="badge badge-green" style="font-size:.75rem;">Selesai</span>
                            <?php elseif ($r['Status'] === 'Ditolak'): // Jika sudah ditolak, tampilkan badge saja ?>
                                <span class="badge badge-red" style="font-size:.75rem;">Ditolak</span>
                            <?php endif; ?>
                        <?php else: // Jika pengguna adalah Super Admin, tampilkan tombol lengkap ?>
                            <!-- Admin Actions: Edit, Tolak, dan Hapus -->
                            <?php // Tombol Edit: arahkan ke edit.php dengan ID laporan ?>
                            <a href="edit.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-edit" title="Edit Laporan / Status">
                                <span class="mat-icon" style="font-size:15px">edit</span>
                            </a>
                            
                            <?php // Tombol Tolak untuk Admin: sama seperti Staff, tapi dengan style sedikit berbeda ?>
                            <?php if (in_array($r['Status'], ['Pending', 'Diproses'])): ?>
                                <a href="tolak.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-danger" style="padding:8px 12px; font-size:.8rem;" onclick="return confirm('Tolak laporan ini sebagai laporan palsu?')">
                                    <span class="mat-icon" style="font-size:14px; vertical-align:middle;">block</span> Tolak
                                </a>
                            <?php endif; ?>

                            <?php // Form Hapus: menggunakan form POST (bukan GET) untuk keamanan
                            // Data ID dikirim sebagai hidden input, bukan lewat URL ?>
                            <form method="POST" action="delete.php" onsubmit="return confirm('Hapus laporan kendala ini?')">
                                <?php // input hidden: menyimpan ID laporan yang mau dihapus, tidak terlihat pengguna ?>
                                <input type="hidden" name="id" value="<?= $r['WaterReport_ID'] ?>">
                                <button type="submit" class="btn-danger" title="Hapus" style="padding:8px 12px;">
                                    <span class="mat-icon" style="font-size:14px; vertical-align:middle;">delete</span> Hapus
                                </button>
                            </form>
                        <?php endif; ?>
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
<!-- Modal Preview Foto (Lightbox) -->
<!-- Modal ini tersembunyi (display:none) dan akan muncul saat foto diklik -->
<div id="photo-modal" onclick="this.style.display='none'" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;display:none;align-items:center;justify-content:center;cursor:pointer;">
    <?php // onclick di div luar: klik area gelap di luar foto = tutup modal ?>
    <div onclick="event.stopPropagation()" style="position:relative;max-width:90vw;max-height:90vh;">
        <?php // event.stopPropagation() = hentikan klik agar tidak "bocor" ke div luar (mencegah modal tertutup saat klik foto) ?>
        <img id="photo-modal-img" src="" alt="Foto" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <button onclick="document.getElementById('photo-modal').style.display='none'" style="position:absolute;top:-12px;right:-12px;background:#fff;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;line-height:32px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.3);">&times;</button>
    </div>
</div>

<script>
// ===================================================
// JavaScript: Fungsi untuk membuka modal foto saat thumbnail diklik
// ===================================================

// Fungsi showPhotoModal dipanggil saat pengguna klik thumbnail foto di tabel
function showPhotoModal(src) {
    // Masukkan URL foto ke atribut src gambar di dalam modal
    document.getElementById('photo-modal-img').src = src;
    // Tampilkan modal dengan mengubah display menjadi 'flex' (dari sebelumnya 'none')
    var m = document.getElementById('photo-modal');
    m.style.display = 'flex';
}

// Tutup modal jika pengguna menekan tombol Escape di keyboard
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('photo-modal').style.display = 'none';
});
</script>

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Tutup halaman HTML dengan footer ?>
>>>>>>> Stashed changes
