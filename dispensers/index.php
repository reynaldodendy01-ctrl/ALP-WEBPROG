<?php
// =========================================================================
// FILE: dispensers/index.php
// FUNGSI: Menampilkan Daftar Semua Dispenser (Halaman Utama Dispenser / READ)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
// 'require_once' digunakan untuk memuat file koneksi database (db.php) ke dalam file ini,
// supaya kita bisa berinteraksi dengan database MySQL (mengambil data dari tabel).
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
// Variabel ini dibaca oleh file '_partials/layout_head.php' untuk mengatur judul tab
// dan menentukan menu mana yang menyala di sidebar kiri.
$pageTitle  = 'Dispensers'; 
$activeMenu = 'dispensers'; 
define('ROOT', dirname(__DIR__)); // Menyimpan lokasi folder paling luar

// ─── 3. MENGATUR FILTER PENCARIAN ───────────────────────────────────────────
// Bagian ini menangani kotak pencarian dan pilihan "Semua Gedung" / "Semua Kategori".
// Awalnya kita buat variabel '$whereClause' berisi '1=1' (artinya: ambil semua data tanpa kecuali).
$whereClause = '1=1';
$params = []; // Tempat menyimpan kata kunci pencarian yang aman dari SQL Injection.

// Jika pengguna memilih filter "Gedung" dari kotak pilihan (misalnya memilih 'UC Tower')
if (!empty($_GET['gedung'])) {
    // Tambahkan syarat: "...DAN Nama_Gedung harus sama dengan gedung yang dipilih"
    $whereClause .= ' AND l.Nama_Gedung = :gedung';
    // Simpan pilihan gedungnya ke dalam placeholder aman ':gedung'
    $params[':gedung'] = $_GET['gedung'];
}
// Jika pengguna memilih filter "Kategori" (misal: 'Hot & Cold')
if (!empty($_GET['kategori'])) {
    // Tambahkan syarat: "...DAN Kategori harus sama dengan yang dipilih"
    $whereClause .= ' AND d.Kategori = :kategori';
    $params[':kategori'] = $_GET['kategori'];
}
// Jika pengguna mengetik kata kunci pencarian (misal mencari 'DISP-123' atau 'Tower')
if (!empty($_GET['q'])) {
    // Tambahkan syarat pencarian menggunakan 'LIKE' (mencari teks yang mengandung kata kunci)
    // Tanda '%' di awal dan akhir artinya: kata kuncinya boleh ada di awal, tengah, atau akhir kalimat.
    $whereClause .= ' AND (d.Kode_Dispenser LIKE :q OR l.Nama_Gedung LIKE :q OR l.Keterangan LIKE :q)';
    $params[':q'] = '%' . $_GET['q'] . '%';
}

// ─── 4. CRUD (READ) - MENGAMBIL DATA DARI MYSQL ──────────────────────────────────
// Ini adalah jantung dari halaman ini. Kita mengambil data dispenser dari database.
// Kita menggunakan klausa 'JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID' 
// untuk menggabungkan tabel 'dispenser' dan 'lokasi'. Dengan begitu kita bisa tahu nama gedung untuk tiap dispenser.
// Kita juga menempelkan '$whereClause' yang sudah kita rakit di atas agar filter pencariannya berjalan.
$stmt = $pdo->prepare("
    SELECT d.*, l.Nama_Gedung, l.Lantai, l.Keterangan,
           (SELECT COUNT(*) FROM staff_dispenser_assignment sda WHERE sda.Dispenser_ID = d.Dispenser_ID AND sda.Status != 'Completed') AS active_assignments
    FROM dispenser d
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    WHERE $whereClause
    ORDER BY l.Nama_Gedung, l.Lantai, d.Dispenser_ID
");

// Setelah SQL siap, kita jalankan query-nya sambil menyisipkan data saringan ($params).
$stmt->execute($params);

// fetchAll() mengambil SEMUA hasil baris dari database, 
// lalu mengubahnya menjadi Array (laci besar berisi daftar laci-laci kecil).
$dispensers = $stmt->fetchAll();

// 5. MENGAMBIL DAFTAR NAMA GEDUNG (Untuk menu pilihan dropdown filter)
// 'DISTINCT' artinya: ambil nama gedung tanpa ada yang dobel.
$gedungList = $pdo->query("SELECT DISTINCT Nama_Gedung FROM lokasi ORDER BY Nama_Gedung")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Dispenser</div>
        <div class="page-subtitle"><?= count($dispensers) ?> dispenser ditemukan</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">add</span>
        Tambah Dispenser
    </a>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Cari Dispenser / Lokasi</label>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   placeholder="Ketik kode dispenser atau nama gedung…" class="form-input" style="padding:9px 12px;">
        </div>
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Gedung</label>
            <select name="gedung" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Gedung</option>
                <?php foreach ($gedungList as $g): ?>
                <option value="<?= h($g) ?>" <?= ($_GET['gedung'] ?? '') === $g ? 'selected' : '' ?>><?= h($g) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Kategori</label>
            <select name="kategori" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Kategori</option>
                <?php foreach (['Normal','Hot & Cold','Hot, Cold & Normal'] as $kat): ?>
                <option value="<?= $kat ?>" <?= ($_GET['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="mat-icon" style="font-size:18px">search</span> Filter
        </button>
        <a href="index.php" class="btn-secondary" style="padding:10px 20px;">Reset</a>
    </form>
</div>

<!-- Table -->
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
        <?php if (empty($dispensers)): ?>
            <tr>
                <td colspan="7" style="text-align:center;color:#9ca3af;padding:48px;">
                    <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">water_drop</span>
                    Tidak ada dispenser ditemukan
                </td>
            </tr>
        <?php else: foreach ($dispensers as $i => $d): ?>
            <tr>
                <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td>
                <td>
                    <div style="font-weight:700;color:#0058bc;">
                        <?= h($d['Kode_Dispenser']) ?>
                    </div>
                </td>
                <td>
                    <div style="font-weight:600;color:#0b1f3a;"><?= h($d['Nama_Gedung']) ?></div>
                    <div style="font-size:.78rem;color:#6b7280;"><?= h($d['Keterangan'] ?? '—') ?></div>
                </td>
                <td style="font-weight:600">Lt. <?= h($d['Lantai']) ?></td>
                <td>
                    <span class="badge badge-blue"><?= h($d['Kategori']) ?></span>
                </td>
                <td>
                    <?php if ($d['active_assignments'] > 0): ?>
                        <span class="badge badge-orange"><?= $d['active_assignments'] ?> ditugaskan</span>
                    <?php else: ?>
                        <span class="badge badge-green">Clear</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <a href="edit.php?id=<?= $d['Dispenser_ID'] ?>" class="btn-edit" title="Edit">
                            <span class="mat-icon" style="font-size:15px">edit</span>
                        </a>
                        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus dispenser \'<?= h(addslashes($d['Kode_Dispenser'])) ?>\'? Semua data terkait (laporan, assignment) ikut terhapus.')">
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

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
