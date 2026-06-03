<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Refill Logs';
$activeMenu = 'refill';
define('ROOT', dirname(__DIR__));

$stmt = $pdo->prepare("
    SELECT rl.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
    FROM refill_logs rl
    JOIN staff_dispenser_assignment sda ON rl.Assignment_ID = sda.Assignment_ID
    JOIN maintenance_staff ms ON sda.Staff_ID = ms.Staff_ID
    JOIN dispenser d ON sda.Dispenser_ID = d.Dispenser_ID
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    ORDER BY rl.Refill_At DESC
");
$stmt->execute();
$refills = $stmt->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Riwayat Pengisian Galon (Refill Logs)</div>
        <div class="page-subtitle"><?= count($refills) ?> refill tercatat</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">recycling</span> Catat Refill Baru
    </a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Staff PIC</th>
            <th>Dispenser / Lokasi</th>
            <th>Catatan</th>
            <th>Waktu Refill</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($refills)): ?>
        <tr>
            <td colspan="6" style="text-align:center;color:#9ca3af;padding:48px;">
                <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">recycling</span>
                Belum ada log refill tercatat
            </td>
        </tr>
    <?php else: foreach ($refills as $i => $r): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td>
            <td>
                <span style="font-weight:600;color:#0b1f3a;"><?= h($r['nama_staff']) ?></span>
            </td>
            <td>
                <div style="font-weight:700;color:#0058bc;"><?= h($r['Kode_Dispenser']) ?></div>
                <div style="font-size:.78rem;color:#50616b;"><?= h($r['Nama_Gedung']) ?> Lt. <?= h($r['Lantai']) ?></div>
            </td>
            <td style="font-size:.85rem;max-width:300px;word-wrap:break-word;white-space:normal;"><?= h($r['Catatan'] ?? '—') ?></td>
            <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;">
                <?= date('d M Y, H:i', strtotime($r['Refill_At'])) ?> WIB
            </td>
            <td>
                <form method="POST" action="delete.php" onsubmit="return confirm('Hapus log refill ini?')">
                    <input type="hidden" name="id" value="<?= $r['Logs_ID'] ?>">
                    <button type="submit" class="btn-danger" style="padding:6px 12px; font-size:.75rem;">
                        <span class="mat-icon" style="font-size:14px;vertical-align:middle;">delete</span> Hapus
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
