<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Refill Log';
$activeMenu = 'refill';
define('ROOT', dirname(__DIR__));

// Filter
$whereClause = '1=1';
$params      = [];

if (!empty($_GET['gedung'])) {
    $whereClause .= ' AND d.gedung = :gedung';
    $params[':gedung'] = $_GET['gedung'];
}
if (!empty($_GET['date'])) {
    $whereClause .= ' AND DATE(r.tanggal_refill) = :date';
    $params[':date'] = $_GET['date'];
}

$stmt = $pdo->prepare("
    SELECT r.*, d.nama_lokasi, d.gedung, d.lantai, s.nama AS nama_staff
    FROM refill_log r
    JOIN dispensers d ON r.dispenser_id = d.id
    LEFT JOIN staff s ON r.staff_id = s.id
    WHERE $whereClause
    ORDER BY r.tanggal_refill DESC
    LIMIT 100
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$totalGalon = array_sum(array_column($logs, 'jumlah_galon'));

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Riwayat Refill Galon</div>
        <div class="page-subtitle"><?= count($logs) ?> entri · <?= $totalGalon ?> total galon diisi</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">add</span> Catat Refill
    </a>
</div>

<!-- Filter -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Gedung</label>
            <select name="gedung" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Gedung</option>
                <option value="Main Building" <?= ($_GET['gedung'] ?? '') === 'Main Building' ? 'selected' : '' ?>>Main Building</option>
                <option value="UC Tower"      <?= ($_GET['gedung'] ?? '') === 'UC Tower'      ? 'selected' : '' ?>>UC Tower</option>
            </select>
        </div>
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Tanggal</label>
            <input type="date" name="date" value="<?= h($_GET['date'] ?? '') ?>"
                   class="form-input" style="padding:9px 12px;">
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
            <th>Dispenser</th>
            <th>Gedung / Lantai</th>
            <th>Staff</th>
            <th style="text-align:center">Jumlah Galon</th>
            <th>Tanggal Refill</th>
            <th>Catatan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($logs)): ?>
        <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada riwayat refill</td></tr>
    <?php else: foreach ($logs as $i => $r): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <a href="../dispensers/detail.php?id=<?= $r['dispenser_id'] ?>"
                   style="font-weight:600;color:#0b1f3a;text-decoration:none;font-size:.875rem;">
                    <?= h($r['nama_lokasi']) ?>
                </a>
            </td>
            <td>
                <span class="badge badge-blue" style="font-size:.75rem"><?= h($r['gedung']) ?></span>
                <span style="color:#9ca3af;font-size:.8rem;margin-left:4px">Lt. <?= $r['lantai'] ?></span>
            </td>
            <td style="font-size:.875rem"><?= h($r['nama_staff'] ?? '—') ?></td>
            <td style="text-align:center">
                <span style="font-size:1.3rem;font-weight:800;color:#0058bc"><?= $r['jumlah_galon'] ?></span>
                <span style="font-size:.78rem;color:#9ca3af"> galon</span>
            </td>
            <td style="font-size:.85rem;white-space:nowrap"><?= date('d M Y, H:i', strtotime($r['tanggal_refill'])) ?></td>
            <td style="font-size:.82rem;color:#6b7280"><?= h($r['catatan'] ?? '—') ?></td>
            <td>
                <form method="POST" action="delete.php" onsubmit="return confirm('Hapus log refill ini?')">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn-danger">
                        <span class="mat-icon" style="font-size:15px">delete</span>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
