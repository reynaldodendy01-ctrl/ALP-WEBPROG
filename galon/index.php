<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Stok Galon';
$activeMenu = 'galon';
define('ROOT', dirname(__DIR__));

$galon = $pdo->query("
    SELECT g.*, d.nama_lokasi, d.gedung, d.lantai, d.status AS status_dispenser
    FROM galon g
    JOIN dispensers d ON g.dispenser_id = d.id
    ORDER BY g.jumlah_tersedia ASC, d.gedung, d.lantai
")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Stok Galon</div>
        <div class="page-subtitle"><?= count($galon) ?> entri stok galon</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">add</span> Tambah Stok
    </a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Dispenser</th>
            <th>Gedung / Lantai</th>
            <th>Status Dispenser</th>
            <th>Stok Tersedia</th>
            <th>Kapasitas</th>
            <th>Level</th>
            <th>Terakhir Diisi</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($galon)): ?>
        <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada data galon</td></tr>
    <?php else: foreach ($galon as $i => $g):
        $pct  = $g['kapasitas_max'] > 0 ? intval(($g['jumlah_tersedia'] / $g['kapasitas_max']) * 100) : 0;
        $barC = $g['jumlah_tersedia'] == 0 ? '#ef4444' : ($pct < 40 ? '#f59e0b' : '#22c55e');
        $levelLabel = $g['jumlah_tersedia'] == 0 ? ['badge-red','Kosong'] : ($pct < 40 ? ['badge-yellow','Rendah'] : ['badge-green','Aman']);
        $dStatus = match($g['status_dispenser']) {
            'Normal'=>'badge-green','Kosong'=>'badge-yellow','Rusak'=>'badge-red','Maintenance'=>'badge-orange',default=>'badge-gray'
        };
    ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem;"><?= $i+1 ?></td>
            <td>
                <a href="../dispensers/detail.php?id=<?= $g['dispenser_id'] ?>"
                   style="font-weight:600;color:#0b1f3a;text-decoration:none;"><?= h($g['nama_lokasi']) ?></a>
            </td>
            <td>
                <span class="badge badge-blue" style="font-size:.75rem"><?= h($g['gedung']) ?></span>
                <span style="color:#9ca3af;font-size:.8rem;margin-left:4px">Lt. <?= $g['lantai'] ?></span>
            </td>
            <td><span class="badge <?= $dStatus ?>" style="font-size:.75rem"><?= $g['status_dispenser'] ?></span></td>
            <td style="font-size:1.4rem;font-weight:800;color:<?= $barC ?>;"><?= $g['jumlah_tersedia'] ?></td>
            <td style="font-size:.9rem;color:#6b7280;"><?= $g['kapasitas_max'] ?> galon</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px;min-width:120px;">
                    <div style="flex:1;height:8px;background:#e5e7eb;border-radius:99px;">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $barC ?>;border-radius:99px;"></div>
                    </div>
                    <span class="badge <?= $levelLabel[0] ?>" style="font-size:.7rem;"><?= $levelLabel[1] ?></span>
                </div>
            </td>
            <td style="font-size:.8rem;color:#9ca3af;">
                <?= $g['terakhir_diisi'] ? date('d M Y H:i', strtotime($g['terakhir_diisi'])) : '—' ?>
            </td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="edit.php?id=<?= $g['id'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus record stok galon ini?')">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
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
