<?php
/* DESKRIPSI FILE: Halaman daftar lokasi (gedung dan lantai) tempat dispenser diletakkan. */

// ===================================================
// INISIALISASI — Muat file koneksi database dan atur variabel halaman
// ===================================================
require_once __DIR__ . '/../db.php'; // Hubungkan ke database lewat file db.php (berisi variabel $pdo)

$pageTitle  = 'Lokasi';   // Judul tab browser yang akan ditampilkan
$activeMenu = 'lokasi';   // Menandai menu 'lokasi' sebagai aktif di sidebar/navigasi
define('ROOT', dirname(__DIR__)); // Simpan path folder induk sebagai konstanta ROOT, berguna untuk include file

// ===================================================
// [SELECT] Ambil semua data lokasi beserta jumlah dispenser di masing-masing lokasi
// ===================================================
// Query ini menggabungkan dua tabel:
//   - 'lokasi'    : berisi info gedung dan lantai
//   - 'dispenser' : berisi info dispenser yang ada di tiap lokasi
// LEFT JOIN artinya: tampilkan semua lokasi, meski belum punya dispenser sekalipun (jumlah = 0)
// COUNT(d.Dispenser_ID) = hitung berapa dispenser yang ada di tiap lokasi
// GROUP BY l.Lokasi_ID  = kelompokkan hasil per lokasi supaya COUNT bekerja dengan benar
// ORDER BY Nama_Gedung, Lantai = urutkan dari A-Z lalu dari lantai terkecil
$locations = $pdo->query("
    SELECT l.*, COUNT(d.Dispenser_ID) AS jumlah_dispenser
    FROM lokasi l
    LEFT JOIN dispenser d ON d.Lokasi_ID = l.Lokasi_ID
    GROUP BY l.Lokasi_ID
    ORDER BY l.Nama_Gedung, l.Lantai
")->fetchAll(); // fetchAll() = ambil semua baris hasil query sebagai array

// ===================================================
// TAMPILAN — Muat bagian atas halaman (HTML head, navbar, sidebar)
// ===================================================
include __DIR__ . '/../_partials/layout_head.php'; // File ini berisi <html>, <head>, dan pembuka <body>
?>

<?php render_flash(); // Tampilkan pesan sukses/error dari sesi (misalnya "Lokasi berhasil ditambahkan") ?>

<!-- Bagian judul halaman dan tombol "Tambah Lokasi" -->
<div class="page-header">
    <div>
        <div class="page-title">Manajemen Lokasi</div>
        <!-- Tampilkan jumlah total lokasi yang sudah terdaftar -->
        <div class="page-subtitle"><?= count($locations) ?> lokasi terdaftar</div>
    </div>
    <!-- Tombol untuk pergi ke halaman tambah lokasi baru -->
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">map</span> Tambah Lokasi
    </a>
</div>

<!-- Tabel daftar lokasi, dibungkus card agar bisa di-scroll ke samping jika layar kecil -->
<div class="card" style="overflow-x:auto;">
<table>
    <!-- Baris kepala tabel (header kolom) -->
    <thead>
        <tr>
            <th>#</th>           <!-- Nomor urut -->
            <th>Nama Gedung</th>
            <th>Lantai</th>
            <th>Keterangan</th>
            <th>Dispenser</th>   <!-- Jumlah dispenser di lokasi ini -->
            <th>Aksi</th>        <!-- Tombol edit dan hapus -->
        </tr>
    </thead>
    <tbody>
    <?php if (empty($locations)): ?>
        <!-- Jika array $locations kosong (belum ada data), tampilkan pesan ini -->
        <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada lokasi terdaftar</td></tr>
    <?php else: foreach ($locations as $i => $l): ?>
        <!-- Loop: tampilkan satu baris per lokasi, $i = indeks (mulai 0), $l = data lokasi -->
        <tr>
            <!-- Nomor urut: $i mulai dari 0, jadi tambahkan 1 agar tampil mulai dari 1 -->
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <!-- h() adalah fungsi untuk mengamankan teks sebelum ditampilkan (mencegah XSS) -->
                <span style="font-weight:600;color:#0b1f3a;"><?= h($l['Nama_Gedung']) ?></span>
            </td>
            <!-- Tampilkan nomor lantai, contoh: "Lt. 3" -->
            <td style="font-weight:600">Lt. <?= h($l['Lantai']) ?></td>
            <!-- Tampilkan keterangan; jika kosong/null, tampilkan tanda '—' -->
            <td style="font-size:.875rem;color:#50616b;"><?= h($l['Keterangan'] ?? '—') ?></td>
            <td>
                <!-- Tampilkan angka jumlah dispenser (hasil COUNT dari query di atas) -->
                <span style="font-size:1rem;font-weight:800;color:#0058bc"><?= $l['jumlah_dispenser'] ?></span>
                <span style="font-size:.8rem;color:#9ca3af"> dispenser</span>
            </td>
            <td>
                <div style="display:flex;gap:6px;">
                    <!-- Tombol Edit: arahkan ke edit.php dengan membawa ID lokasi di URL -->
                    <a href="edit.php?id=<?= $l['Lokasi_ID'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <!-- Form Hapus: menggunakan method POST ke delete.php -->
                    <!-- onsubmit="return confirm(...)" = tampilkan kotak konfirmasi sebelum mengirim form -->
                    <!-- PENTING: Pesan konfirmasi memperingatkan bahwa dispenser di lokasi ini juga ikut terhapus (cascading delete) -->
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus lokasi <?= h(addslashes($l['Nama_Gedung'])) ?> Lt. <?= $l['Lantai'] ?>? Dispensers yang ada di lokasi ini juga akan terhapus.')">
                        <!-- Input tersembunyi: kirim Lokasi_ID ke delete.php tanpa ditampilkan di layar -->
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

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Muat bagian bawah halaman (penutup </body> dan </html>) ?>
