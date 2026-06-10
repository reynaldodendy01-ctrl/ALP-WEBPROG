<?php
// =========================================================================
// FILE: laporan/index.php
// FUNGSI: Menampilkan Halaman Daftar Semua Laporan Masalah (READ)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

$validCategories = ['Galon Kosong', 'Dispenser Rusak / Bocor'];

// 3. ── MENGATUR FILTER PENCARIAN ─────────────────────────────────────────────────
// Sama seperti halaman dispenser, kita buat keranjang filter.
// '1=1' artinya ambil semua data. Nanti kita tambahkan syarat lain pakai 'AND'.
$whereClause = '1=1';
$params = [];

// Jika user memfilter Status (Pending/Selesai/dsb)
if (!empty($_GET['status'])) {
    $whereClause .= ' AND wr.Status = :status';
    $params[':status'] = $_GET['status'];
}
// Jika user memfilter Kategori (Galon Kosong/Dispenser Rusak)
if (!empty($_GET['kategori'])) {
    $whereClause .= ' AND wr.Kategori = :kategori';
    $params[':kategori'] = $_GET['kategori'];
}
// Jika user ngetik kata pencarian (nama pelapor, kode dispenser, atau isi laporan)
if (!empty($_GET['q'])) {
    $whereClause .= ' AND (rep.Nama LIKE :q OR d.Kode_Dispenser LIKE :q OR wr.Deskripsi_Report LIKE :q)';
    $params[':q'] = '%' . $_GET['q'] . '%';
}
// Jika user memfilter nama gedung
if (!empty($_GET['gedung'])) {
    $whereClause .= ' AND l.Nama_Gedung = :gedung';
    $params[':gedung'] = $_GET['gedung'];
}

// 4. ─── CRUD (READ) - MENGAMBIL DATA LAPORAN DARI DATABASE ───────────────────
// Query ini adalah "Raja"-nya Query di project ini karena ia menggabungkan BANYAK tabel.
// Tujuannya agar kita bisa melihat semua info lengkap dalam 1 baris tabel, tanpa perlu bolak-balik cari ID.
// 
// - water_report (wr)  : Tabel utama (yang berisi laporan)
// - reporter (rep)     : Digabungkan (JOIN) supaya kita tahu NAMA si pelapor (bukan cuma ID-nya)
// - dispenser (d)      : Digabungkan (JOIN) supaya kita tahu KODE dispensernya
// - lokasi (l)         : Digabungkan (JOIN) supaya kita tahu GEDUNG & LANTAI dispensernya
//
// Yang paling rumit: 'assigned_staff'. 
// Ini adalah "Query di dalam Query" (Sub-query) untuk mencari: "Siapa sih nama staff yang lagi ngerjain tugas ini?"
$stmt = $pdo->prepare("
    SELECT wr.*, rep.Nama AS nama_pelapor, rep.Nim AS nim_pelapor,
           d.Kode_Dispenser, l.Nama_Gedung, l.Lantai,
           (SELECT s.Nama FROM staff_dispenser_assignment sda 
            JOIN maintenance_staff s ON sda.Staff_ID = s.Staff_ID
            WHERE sda.WaterReport_ID = wr.WaterReport_ID AND sda.Status != 'Cancelled' LIMIT 1) AS assigned_staff
    FROM water_report wr
    JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID
    JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    WHERE $whereClause
    ORDER BY wr.Reported_At DESC
");
$stmt->execute($params); // Menjalankan query SQL beserta filternya
$reports = $stmt->fetchAll(); // Simpan semua hasilnya di array '$reports'

// Mengambil daftar nama gedung unik untuk pilihan di kotak Filter
$buildings = $pdo->query("SELECT DISTINCT Nama_Gedung FROM lokasi ORDER BY Nama_Gedung")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Laporan Kendala Air</div>
        <div class="page-subtitle"><?= count($reports) ?> laporan masuk</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Cari Laporan / Pelapor</label>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   placeholder="Ketik nama pelapor, dispenser, atau deskripsi..." class="form-input" style="padding:9px 12px;">
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Gedung</label>
            <select name="gedung" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Gedung</option>
                <?php foreach ($buildings as $b): ?>
                <option value="<?= h($b['Nama_Gedung']) ?>" <?= ($_GET['gedung'] ?? '') === $b['Nama_Gedung'] ? 'selected' : '' ?>><?= h($b['Nama_Gedung']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Kategori Masalah</label>
            <select name="kategori" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Kategori</option>
                <?php foreach ($validCategories as $kat): ?>
                <option value="<?= $kat ?>" <?= ($_GET['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Status</label>
            <select name="status" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Status</option>
                <?php foreach (['Pending','Diproses','Selesai','Ditolak'] as $st): ?>
                <option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
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
        <?php if (empty($reports)): ?>
            <tr>
                <td colspan="10" style="text-align:center;color:#9ca3af;padding:48px;">
                    <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">report</span>
                    Tidak ada laporan kendala ditemukan
                </td>
            </tr>
        <?php else: foreach ($reports as $i => $r):
            $statusBadge = match($r['Status']) {
                'Pending'  => 'badge-yellow',
                'Diproses' => 'badge-blue',
                'Selesai'  => 'badge-green',
                'Ditolak'  => 'badge-red',
                default    => 'badge-gray',
            };
        ?>
            <tr>
                <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td>
                <td>
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
                    <?php if ($r['Foto_url']): ?>
                        <img src="<?= h(get_foto_url($r['Foto_url'])) ?>" alt="Foto"
                             onclick="showPhotoModal('<?= h(get_foto_url($r['Foto_url'])) ?>')"
                             style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;cursor:pointer;">
                    <?php else: ?>
                        <span style="color:#9ca3af">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $statusBadge ?>"><?= h($r['Status']) ?></span></td>
                <td style="font-size:.85rem;font-weight:500;color:#0b1f3a;"><?= h($r['assigned_staff'] ?? '—') ?></td>
                <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;">
                    <?= date('d/m/y H:i', strtotime($r['Reported_At'])) ?>
                    <?php if ($r['Resolved_At']): ?>
                        <div style="font-size:.7rem;color:#10b981;">Selesai: <?= date('d/m H:i', strtotime($r['Resolved_At'])) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <?php if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff'): ?>
                            <?php if ($r['Status'] === 'Pending'): ?>
                                <a href="ambil.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-primary" style="padding:6px 12px; font-size:.75rem; background: linear-gradient(135deg, #0058bc, #1d4ed8);">
                                    <span class="mat-icon" style="font-size:14px; vertical-align:middle;">assignment</span> Ambil Tugas
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($r['Status'], ['Pending', 'Diproses'])): ?>
                                <a href="tolak.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-danger" style="padding:6px 12px; font-size:.75rem;" onclick="return confirm('Tolak laporan ini sebagai laporan palsu?')">
                                    <span class="mat-icon" style="font-size:14px; vertical-align:middle;">block</span> Tolak
                                </a>
                            <?php elseif ($r['Status'] === 'Selesai'): ?>
                                <span class="badge badge-green" style="font-size:.75rem;">Selesai</span>
                            <?php elseif ($r['Status'] === 'Ditolak'): ?>
                                <span class="badge badge-red" style="font-size:.75rem;">Ditolak</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Admin Actions -->
                            <a href="edit.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-edit" title="Edit Laporan / Status">
                                <span class="mat-icon" style="font-size:15px">edit</span>
                            </a>
                            
                            <?php if (in_array($r['Status'], ['Pending', 'Diproses'])): ?>
                                <a href="tolak.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-danger" style="padding:8px 12px; font-size:.8rem;" onclick="return confirm('Tolak laporan ini sebagai laporan palsu?')">
                                    <span class="mat-icon" style="font-size:14px; vertical-align:middle;">block</span> Tolak
                                </a>
                            <?php endif; ?>

                            <form method="POST" action="delete.php" onsubmit="return confirm('Hapus laporan kendala ini?')">
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

<!-- Photo Modal -->
<div id="photo-modal" onclick="this.style.display='none'" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;display:none;align-items:center;justify-content:center;cursor:pointer;">
    <div onclick="event.stopPropagation()" style="position:relative;max-width:90vw;max-height:90vh;">
        <img id="photo-modal-img" src="" alt="Foto" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <button onclick="document.getElementById('photo-modal').style.display='none'" style="position:absolute;top:-12px;right:-12px;background:#fff;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;line-height:32px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.3);">&times;</button>
    </div>
</div>

<script>
function showPhotoModal(src) {
    document.getElementById('photo-modal-img').src = src;
    var m = document.getElementById('photo-modal');
    m.style.display = 'flex';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('photo-modal').style.display = 'none';
});
</script>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
