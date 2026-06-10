<?php
/**
 * =============================================================================
 * dispensers/index.php — Halaman Daftar & Manajemen Semua Dispenser
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman utama modul Manajemen Dispenser yang menampilkan
 *   seluruh unit dispenser air yang terdaftar dalam sistem CariGalon. Setiap
 *   baris data menampilkan kode unik dispenser, lokasi gedung, lantai, kategori
 *   jenis dispenser, serta jumlah assignment aktif yang sedang berjalan. Halaman
 *   ini juga menyediakan fitur pencarian dan filter multi-parameter agar admin
 *   dapat menemukan dispenser tertentu dengan cepat dan efisien.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan tabel daftar semua dispenser beserta informasi lokasi lengkap
 *   - Filter dispenser berdasarkan nama gedung (dropdown dinamis dari DB)
 *   - Filter dispenser berdasarkan kategori (Normal / Hot & Cold / Hot, Cold & Normal)
 *   - Pencarian teks bebas pada kode dispenser, nama gedung, dan keterangan lokasi
 *   - Menampilkan jumlah assignment aktif (non-Completed) per dispenser sebagai badge
 *   - Tombol aksi Edit (menuju edit.php) dan Hapus (POST ke delete.php) per baris
 *   - Menampilkan pesan flash (sukses/error) hasil operasi CRUD sebelumnya
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk membuka koneksi PDO dan memuat helper function
 *   2. Membaca parameter GET (?q=, ?gedung=, ?kategori=) untuk membangun klausa WHERE dinamis
 *   3. Menjalankan query SELECT dengan JOIN ke tabel lokasi dan subquery COUNT assignment aktif
 *   4. Mengambil daftar gedung unik dari tabel lokasi untuk mengisi dropdown filter
 *   5. Merender layout_head.php (header & sidebar navigasi)
 *   6. Menampilkan flash message jika ada (hasil redirect dari create/edit/delete)
 *   7. Merender filter bar (form GET dengan input teks, dropdown gedung & kategori)
 *   8. Merender tabel responsif berisi data dispenser; jika kosong tampil pesan empty-state
 *   9. Menutup halaman dengan layout_foot.php
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - dispenser                    : Data utama unit dispenser (kode, kategori, lokasi_id)
 *   - lokasi                       : Data lokasi/gedung tempat dispenser dipasang
 *   - staff_dispenser_assignment   : Tabel relasi assignment staf ke dispenser (untuk hitung aktif)
 *
 * VARIABEL PENTING:
 *   - $whereClause         : String klausa SQL WHERE yang dibangun secara dinamis sesuai filter
 *   - $params              : Array parameter binding PDO yang dipakai bersama $whereClause
 *   - $dispensers          : Array hasil fetch semua dispenser setelah filter diterapkan
 *   - $gedungList          : Array nama gedung unik untuk opsi dropdown filter gedung
 *   - $d['active_assignments'] : Jumlah assignment aktif (status != 'Completed') per dispenser
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php               : Koneksi database PDO & helper functions (h(), set_flash(), render_flash())
 *   - layout_head.php      : Header HTML, sidebar navigasi, dan stylesheet utama
 *   - layout_foot.php      : Footer HTML, script JavaScript penutup halaman
 *
 * AKSES:
 *   Hanya bisa diakses oleh pengguna yang sudah login (Admin maupun Staff Maintenance).
 *
 * CATATAN PENGEMBANG:
 *   Subquery COUNT untuk active_assignments dihitung langsung dalam SELECT utama agar
 *   tidak perlu melakukan query tambahan per baris. Pastikan index pada kolom
 *   staff_dispenser_assignment.Dispenser_ID ada agar performa subquery tetap optimal
 *   saat jumlah data dispenser dan assignment membesar.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INISIALISASI — Muat file koneksi database dan setting awal halaman
// ===================================================
// require_once artinya: "sertakan file db.php SATU KALI saja, dan wajib ada"
// __DIR__ adalah folder tempat file ini berada (dispensers/)
// '/../db.php' berarti naik satu folder ke atas lalu ambil db.php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Dispensers'; // Judul tab browser dan header halaman
$activeMenu = 'dispensers'; // Penanda menu aktif di sidebar navigasi
define('ROOT', dirname(__DIR__)); // Simpan path folder utama proyek sebagai konstanta global

// ===================================================
// FILTER DINAMIS — Bangun klausa WHERE sesuai input pengguna dari URL
// ===================================================
// Ide dasar filter dinamis: kita mulai dengan kondisi "1=1" yang selalu TRUE
// (artinya: ambil semua data), lalu tambahkan kondisi lain sesuai filter yang dipilih.
// Contoh URL: index.php?gedung=Main+Building&kategori=Normal&q=DISP

$whereClause = '1=1'; // Kondisi awal yang selalu benar — belum ada filter
$params = [];         // Array untuk menyimpan nilai parameter query (untuk keamanan)

// Jika pengguna memilih gedung dari dropdown, tambahkan filter gedung
if (!empty($_GET['gedung'])) {
    // Tambahkan syarat: nama gedung harus cocok dengan pilihan pengguna
    $whereClause .= ' AND l.Nama_Gedung = :gedung';
    $params[':gedung'] = $_GET['gedung']; // Simpan nilai gedung yang dipilih
}

// Jika pengguna memilih kategori dari dropdown, tambahkan filter kategori
if (!empty($_GET['kategori'])) {
    // Tambahkan syarat: kategori dispenser harus cocok
    $whereClause .= ' AND d.Kategori = :kategori';
    $params[':kategori'] = $_GET['kategori']; // Simpan nilai kategori yang dipilih
}

// Jika pengguna mengetik kata kunci pencarian di kolom teks
if (!empty($_GET['q'])) {
    // LIKE dipakai untuk pencarian teks — '%' adalah wildcard (cocok dengan apa saja)
    // Contoh: jika q='DISP', maka '%DISP%' akan cocok dengan 'DISP-101', 'NEWDISP', dll.
    // Pencarian dilakukan di 3 kolom sekaligus: kode dispenser, nama gedung, keterangan lokasi
    $whereClause .= ' AND (d.Kode_Dispenser LIKE :q OR l.Nama_Gedung LIKE :q OR l.Keterangan LIKE :q)';
    $params[':q'] = '%' . $_GET['q'] . '%'; // Tambahkan '%' di awal dan akhir kata kunci
}

// ===================================================
// [SELECT] Ambil semua data dispenser beserta info lokasi & jumlah assignment aktif
// ===================================================
// Query ini menggunakan JOIN untuk menggabungkan tabel dispenser (d) dengan tabel lokasi (l)
// JOIN bekerja seperti "sambungkan baris dari tabel d dengan baris dari tabel l yang Lokasi_ID-nya sama"
//
// Di dalam SELECT juga ada SUBQUERY — yaitu query di dalam query.
// (SELECT COUNT(*) FROM staff_dispenser_assignment ...) adalah subquery yang:
//   - Menghitung berapa banyak assignment untuk dispenser ini (sda.Dispenser_ID = d.Dispenser_ID)
//   - Yang statusnya BELUM selesai (Status != 'Completed')
// Hasilnya dinamai 'active_assignments' dan bisa dipakai seperti kolom biasa
$stmt = $pdo->prepare("
    SELECT d.*, l.Nama_Gedung, l.Lantai, l.Keterangan,
           (SELECT COUNT(*) FROM staff_dispenser_assignment sda WHERE sda.Dispenser_ID = d.Dispenser_ID AND sda.Status != 'Completed') AS active_assignments
    FROM dispenser d
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    WHERE $whereClause
    ORDER BY l.Nama_Gedung, l.Lantai, d.Dispenser_ID
");
$stmt->execute($params); // Jalankan query dengan parameter filter yang sudah dikumpulkan
$dispensers = $stmt->fetchAll(); // Ambil semua baris hasil query sebagai array

// ===================================================
// [SELECT] Ambil daftar nama gedung unik untuk isian dropdown filter
// ===================================================
// DISTINCT artinya: jangan duplikat — jika ada 5 dispenser di gedung yang sama,
// nama gedung itu hanya muncul sekali di dropdown.
// PDO::FETCH_COLUMN mengambil hanya satu kolom (kolom pertama) sebagai array sederhana
$gedungList = $pdo->query("SELECT DISTINCT Nama_Gedung FROM lokasi ORDER BY Nama_Gedung")->fetchAll(PDO::FETCH_COLUMN);

// ===================================================
// RENDER TAMPILAN — Muat header HTML, sidebar, dan stylesheet
// ===================================================
include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); // Tampilkan pesan sukses/error dari operasi sebelumnya (simpan/edit/hapus) ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Dispenser</div>
        <div class="page-subtitle"><?= count($dispensers) ?> dispenser ditemukan</div><?php // count() menghitung jumlah elemen array $dispensers ?>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">add</span>
        Tambah Dispenser
    </a>
</div>

<!-- Filter Bar — Form pencarian & penyaringan, method GET supaya filter tampil di URL -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Cari Dispenser / Lokasi</label>
            <?php // h() adalah fungsi helper untuk mencegah XSS — karakter berbahaya diubah jadi aman ?>
            <?php // $_GET['q'] ?? '' artinya: ambil nilai 'q' dari URL, kalau tidak ada gunakan string kosong ?>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   placeholder="Ketik kode dispenser atau nama gedung…" class="form-input" style="padding:9px 12px;">
        </div>
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Gedung</label>
            <select name="gedung" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Gedung</option>
                <?php foreach ($gedungList as $g): // Loop untuk membuat opsi dropdown dari daftar gedung ?>
                <?php // Jika gedung ini sama dengan yang dipilih pengguna, tambahkan atribut 'selected' ?>
                <option value="<?= h($g) ?>" <?= ($_GET['gedung'] ?? '') === $g ? 'selected' : '' ?>><?= h($g) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Kategori</label>
            <select name="kategori" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Kategori</option>
                <?php foreach (['Normal','Hot & Cold','Hot, Cold & Normal'] as $kat): // Loop 3 pilihan kategori ?>
                <?php // Sama seperti dropdown gedung — cek apakah kategori ini yang sedang dipilih ?>
                <option value="<?= $kat ?>" <?= ($_GET['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="mat-icon" style="font-size:18px">search</span> Filter
        </button>
        <a href="index.php" class="btn-secondary" style="padding:10px 20px;">Reset</a><?php // Klik Reset = buka index.php tanpa parameter, semua filter dihapus ?>
    </form>
</div>

<!-- Tabel Daftar Dispenser -->
<div class="card" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Kode Dispenser</th>
                <th>Lokasi / Gedung</th>
                <th>Lantai</th>
                <th>Kategori</th>
                <th>Assignment Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($dispensers)): // Jika tidak ada dispenser yang cocok dengan filter ?>
            <tr>
                <td colspan="7" style="text-align:center;color:#9ca3af;padding:48px;">
                    <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">water_drop</span>
                    Tidak ada dispenser ditemukan
                </td>
            </tr>
        <?php else: foreach ($dispensers as $i => $d): // Loop tiap dispenser — $i = nomor urut, $d = data baris ?>
            <tr>
                <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td><?php // +1 supaya nomor mulai dari 1, bukan 0 ?>
                <td>
<<<<<<< Updated upstream
                    <a href="detail.php?id=<?= $d['Dispenser_ID'] ?>"
                       style="font-weight:700;color:#0058bc;text-decoration:none;">
                        <?= h($d['Kode_Dispenser']) ?>
                    </a>
=======
                    <div style="font-weight:700;color:#0058bc;">
                        <?= h($d['Kode_Dispenser']) ?><?php // Tampilkan kode dispenser dengan h() untuk keamanan ?>
                    </div>
>>>>>>> Stashed changes
                </td>
                <td>
                    <div style="font-weight:600;color:#0b1f3a;"><?= h($d['Nama_Gedung']) ?></div>
                    <?php // '?? '—'' artinya: kalau keterangan kosong/null, tampilkan tanda '—' ?>
                    <div style="font-size:.78rem;color:#6b7280;"><?= h($d['Keterangan'] ?? '—') ?></div>
                </td>
                <td style="font-weight:600">Lt. <?= h($d['Lantai']) ?></td>
                <td>
                    <span class="badge badge-blue"><?= h($d['Kategori']) ?></span>
                </td>
                <td>
                    <?php if ($d['active_assignments'] > 0): // Jika ada assignment yang sedang aktif ?>
                        <?php // Tampilkan badge oranye dengan jumlah assignment yang sedang berjalan ?>
                        <span class="badge badge-orange"><?= $d['active_assignments'] ?> ditugaskan</span>
                    <?php else: // Tidak ada assignment aktif — dispenser bebas ?>
                        <span class="badge badge-green">Clear</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
<<<<<<< Updated upstream
                        <a href="detail.php?id=<?= $d['Dispenser_ID'] ?>" class="btn-edit" title="Detail & Riwayat">
                            <span class="mat-icon" style="font-size:15px">visibility</span>
                        </a>
=======
                        <?php // Tombol Edit — menuju edit.php dengan ID dispenser di URL (?id=...) ?>
>>>>>>> Stashed changes
                        <a href="edit.php?id=<?= $d['Dispenser_ID'] ?>" class="btn-edit" title="Edit">
                            <span class="mat-icon" style="font-size:15px">edit</span>
                        </a>
                        <?php // Form hapus — dikirim via POST ke delete.php saat tombol diklik ?>
                        <?php // onsubmit="return confirm(...)" = tampilkan dialog konfirmasi dulu sebelum dihapus ?>
                        <?php // addslashes() mencegah tanda kutip dalam nama dispenser merusak JavaScript ?>
                        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus dispenser \'<?= h(addslashes($d['Kode_Dispenser'])) ?>\'? Semua data terkait (laporan, assignment) ikut terhapus.')">
                            <?php // Input hidden menyimpan ID dispenser — tidak terlihat pengguna, dikirim ke delete.php ?>
                            <input type="hidden" name="id" value="<?= $d['Dispenser_ID'] ?>">
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

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Muat footer HTML dan penutup halaman ?>
