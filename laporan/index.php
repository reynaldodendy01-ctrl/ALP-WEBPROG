<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

// ── Filters ─────────────────────────────────────────────────────────────────
$whereClause = '1=1';
$params = [];

if (!empty($_GET['status'])) {
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
}

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
$stmt->execute($params);
$reports = $stmt->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Laporan Kendala Air (Water Reports)</div>
        <div class="page-subtitle"><?= count($reports) ?> laporan masuk</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">edit_note</span> Buat Laporan
    </a>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Cari Laporan / Pelapor</label>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   placeholder="Ketik nama pelapor, dispenser, atau deskripsi..." class="form-input" style="padding:9px 12px;">
        </div>
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Kategori Masalah</label>
            <select name="kategori" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Kategori</option>
                <?php foreach (['Galon Kosong','Dispenser Rusak','Kebocoran','Distribusi Tidak Merata','Lainnya'] as $kat): ?>
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
                <th>Status</th>
                <th>Ditugaskan Ke</th>
                <th>Waktu Laporan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($reports)): ?>
            <tr>
                <td colspan="9" style="text-align:center;color:#9ca3af;padding:48px;">
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
                        <a href="edit.php?id=<?= $r['WaterReport_ID'] ?>" class="btn-edit" title="Edit Laporan / Status">
                            <span class="mat-icon" style="font-size:15px">edit</span>
                        </a>
                        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus laporan kendala ini?')">
                            <input type="hidden" name="id" value="<?= $r['WaterReport_ID'] ?>">
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
