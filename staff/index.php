<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

$staff = $pdo->query("
    SELECT s.*, COUNT(d.id) AS jumlah_dispenser
    FROM staff s
    LEFT JOIN dispensers d ON d.staff_id = s.id
    GROUP BY s.id
    ORDER BY s.status DESC, s.nama
")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Staff</div>
        <div class="page-subtitle"><?= count($staff) ?> staff terdaftar</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">person_add</span> Tambah Staff
    </a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nama Staff</th>
            <th>No. HP</th>
            <th>Area Tugas</th>
            <th>Dispenser Ditugaskan</th>
            <th>Status</th>
            <th>Bergabung</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($staff)): ?>
        <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada staff terdaftar</td></tr>
    <?php else: foreach ($staff as $i => $s): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0058bc,#1a78e5);
                         display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0;">
                        <?= strtoupper(mb_substr($s['nama'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600"><?= h($s['nama']) ?></span>
                </div>
            </td>
            <td style="font-size:.875rem"><?= h($s['no_hp']) ?></td>
            <td style="font-size:.875rem"><?= h($s['area_tugas']) ?></td>
            <td>
                <span style="font-size:1rem;font-weight:800;color:#0058bc"><?= $s['jumlah_dispenser'] ?></span>
                <span style="font-size:.8rem;color:#9ca3af"> dispenser</span>
            </td>
            <td>
                <span class="badge <?= $s['status'] === 'Aktif' ? 'badge-green' : 'badge-gray' ?>">
                    <?= h($s['status']) ?>
                </span>
            </td>
            <td style="font-size:.8rem;color:#9ca3af"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="edit.php?id=<?= $s['id'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus staff <?= h(addslashes($s['nama'])) ?>?')">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
