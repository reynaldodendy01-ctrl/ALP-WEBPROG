<?php
// =========================================================================
// FILE: lokasi/index.php
// FUNGSI: Menampilkan Halaman Daftar Semua Lokasi Gedung (READ)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Lokasi';
$activeMenu = 'lokasi'; // Sidebar menu "Lokasi" akan menyala
define('ROOT', dirname(__DIR__));

// 3. ─── CRUD (READ) - MENGAMBIL DATA LOKASI DARI MYSQL ───────────────────
// Query SQL ini mengambil semua kolom (l.*) dari tabel 'lokasi'.
// Kita juga menempelkan tabel 'dispenser' (d) menggunakan 'LEFT JOIN'.
// Kenapa pakai LEFT JOIN? Supaya lokasi yang belum punya dispenser pun tetap muncul di daftar.
// Fungsi COUNT(d.Dispenser_ID) akan menghitung ada berapa baris dispenser yang numpang di lokasi tersebut.
// GROUP BY l.Lokasi_ID memastikan penghitungan dikelompokkan per lokasi.
$locations = $pdo->query("
    SELECT l.*, COUNT(d.Dispenser_ID) AS jumlah_dispenser
    FROM lokasi l
    LEFT JOIN dispenser d ON d.Lokasi_ID = l.Lokasi_ID
    GROUP BY l.Lokasi_ID
    ORDER BY l.Nama_Gedung, l.Lantai
")->fetchAll(); // fetchAll() merubah hasil dari MySQL menjadi array (daftar) di PHP.

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Lokasi</div>
        <div class="page-subtitle"><?= count($locations) ?> lokasi terdaftar</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">map</span> Tambah Lokasi
    </a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nama Gedung</th>
            <th>Lantai</th>
            <th>Keterangan</th>
            <th>Dispenser</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($locations)): ?>
        <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada lokasi terdaftar</td></tr>
    <?php else: foreach ($locations as $i => $l): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <span style="font-weight:600;color:#0b1f3a;"><?= h($l['Nama_Gedung']) ?></span>
            </td>
            <td style="font-weight:600">Lt. <?= h($l['Lantai']) ?></td>
            <td style="font-size:.875rem;color:#50616b;"><?= h($l['Keterangan'] ?? '—') ?></td>
            <td>
                <span style="font-size:1rem;font-weight:800;color:#0058bc"><?= $l['jumlah_dispenser'] ?></span>
                <span style="font-size:.8rem;color:#9ca3af"> dispenser</span>
            </td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="edit.php?id=<?= $l['Lokasi_ID'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus lokasi <?= h(addslashes($l['Nama_Gedung'])) ?> Lt. <?= $l['Lantai'] ?>? Dispensers yang ada di lokasi ini juga akan terhapus.')">
                        <input type="hidden" name="id" value="<?= $l['Lokasi_ID'] ?>">
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
