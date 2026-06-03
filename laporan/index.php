<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

$whereClause = '1=1';
$params = [];

if (!empty($_GET['status'])) {
    $whereClause .= ' AND l.status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['jenis'])) {
    $whereClause .= ' AND l.jenis_masalah = :jenis';
    $params[':jenis'] = $_GET['jenis'];
}
if (!empty($_GET['q'])) {
    $whereClause .= ' AND (l.nama_pelapor LIKE :q OR l.deskripsi LIKE :q2)';
    $params[':q']  = '%' . $_GET['q'] . '%';
    $params[':q2'] = '%' . $_GET['q'] . '%';
}

$stmt = $pdo->prepare("
    SELECT l.*, d.nama_lokasi, d.gedung, d.lantai, s.nama AS nama_staff
    FROM laporan l
    JOIN dispensers d ON l.dispenser_id = d.id
    LEFT JOIN staff s ON l.staff_id = s.id
    WHERE $whereClause
    ORDER BY FIELD(l.status,'Pending','Diproses','Selesai','Ditolak'), l.created_at DESC
");
$stmt->execute($params);
$laporan = $stmt->fetchAll();

$jenisList = ['Galon Kosong','Dispenser Rusak','Kebocoran','Distribusi Tidak Merata','Lainnya'];

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Laporan</div>
        <div class="page-subtitle"><?= count($laporan) ?> laporan ditemukan</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">add</span> Buat Laporan
    </a>
</div>

<!-- Filters -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Cari</label>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   placeholder="Nama pelapor atau deskripsi…" class="form-input" style="padding:9px 12px;">
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
        <div style="min-width:200px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Jenis Masalah</label>
            <select name="jenis" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Jenis</option>
                <?php foreach ($jenisList as $j): ?>
                <option value="<?= $j ?>" <?= ($_GET['jenis'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="mat-icon" style="font-size:18px">search</span> Filter
        </button>
        <a href="index.php" class="btn-secondary" style="padding:10px 20px;">Reset</a>
    </form>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Pelapor</th>
            <th>Dispenser</th>
            <th>Jenis Masalah</th>
            <th>Deskripsi</th>
            <th>Status</th>
            <th>Staff</th>
            <th>Waktu</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($laporan)): ?>
        <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:48px;">Tidak ada laporan</td></tr>
    <?php else: foreach ($laporan as $i => $l):
        $sb = match($l['status']) { 'Pending'=>'badge-yellow','Diproses'=>'badge-blue','Selesai'=>'badge-green','Ditolak'=>'badge-red',default=>'badge-gray' };
    ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <div style="font-weight:600;font-size:.875rem"><?= h($l['nama_pelapor']) ?></div>
                <?php if ($l['kontak_pelapor']): ?>
                <div style="font-size:.75rem;color:#9ca3af"><?= h($l['kontak_pelapor']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div style="font-size:.82rem;font-weight:600"><?= h($l['nama_lokasi']) ?></div>
                <div style="font-size:.75rem;color:#9ca3af"><?= h($l['gedung']) ?> Lt.<?= $l['lantai'] ?></div>
            </td>
            <td><span class="badge badge-orange" style="font-size:.72rem"><?= h($l['jenis_masalah']) ?></span></td>
            <td style="font-size:.82rem;max-width:180px;"><?= h(mb_strimwidth($l['deskripsi'], 0, 60, '…')) ?></td>
            <td><span class="badge <?= $sb ?>"><?= h($l['status']) ?></span></td>
            <td style="font-size:.82rem"><?= h($l['nama_staff'] ?? '—') ?></td>
            <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap"><?= date('d/m/y H:i', strtotime($l['created_at'])) ?></td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="edit.php?id=<?= $l['id'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus laporan ini?')">
                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
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
