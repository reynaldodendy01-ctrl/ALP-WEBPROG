<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Assignments';
$activeMenu = 'assignments';
define('ROOT', dirname(__DIR__));

// ── Filters ─────────────────────────────────────────────────────────────────
$whereClause = '1=1';
$params = [];

if (!empty($_GET['status'])) {
    $whereClause .= ' AND a.Status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['staff_id'])) {
    $whereClause .= ' AND a.Staff_ID = :staff_id';
    $params[':staff_id'] = $_GET['staff_id'];
}

$stmt = $pdo->prepare("
    SELECT a.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai,
           wr.Kategori AS kategori_laporan, wr.Deskripsi_Report, wr.Foto_url,
           (SELECT COUNT(*) FROM refill_logs rl WHERE rl.Assignment_ID = a.Assignment_ID) AS total_refills
    FROM staff_dispenser_assignment a
    JOIN maintenance_staff ms ON a.Staff_ID = ms.Staff_ID
    JOIN dispenser d ON a.Dispenser_ID = d.Dispenser_ID
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    LEFT JOIN water_report wr ON a.WaterReport_ID = wr.WaterReport_ID
    WHERE $whereClause
    ORDER BY a.Created_At DESC
");
$stmt->execute($params);
$assignments = $stmt->fetchAll();

$staffList = $pdo->query("SELECT Staff_ID, Nama FROM maintenance_staff ORDER BY Nama")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Penugasan (Assignments)</div>
        <div class="page-subtitle"><?= count($assignments) ?> penugasan terdaftar</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">assignment_turned_in</span> Tambah Penugasan
    </a>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="min-width:180px; flex:1;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Pilih Staff</label>
            <select name="staff_id" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Staff</option>
                <?php foreach ($staffList as $s): ?>
                <option value="<?= $s['Staff_ID'] ?>" <?= ($_GET['staff_id'] ?? '') == $s['Staff_ID'] ? 'selected' : '' ?>><?= h($s['Nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Status Penugasan</label>
            <select name="status" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Status</option>
                <?php foreach (['Pending','On Progress','Completed','Cancelled'] as $st): ?>
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
                <th>Staff Maintenance</th>
                <th>Dispenser / Lokasi</th>
                <th>Terkait Laporan</th>
                <th>Status</th>
                <th>Logs Refill</th>
                <th>Waktu Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($assignments)): ?>
            <tr>
                <td colspan="8" style="text-align:center;color:#9ca3af;padding:48px;">
                    <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">assignment_turned_in</span>
                    Tidak ada penugasan ditemukan
                </td>
            </tr>
        <?php else: foreach ($assignments as $i => $a):
            $statusBadge = match($a['Status']) {
                'Pending'     => 'badge-yellow',
                'On Progress' => 'badge-blue',
                'Completed'   => 'badge-green',
                'Cancelled'   => 'badge-red',
                default       => 'badge-gray',
            };
        ?>
            <tr>
                <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td>
                <td>
                    <span style="font-weight:600;color:#0b1f3a;"><?= h($a['nama_staff']) ?></span>
                </td>
                <td>
                    <div style="font-weight:700;color:#0058bc;"><?= h($a['Kode_Dispenser']) ?></div>
                    <div style="font-size:.78rem;color:#50616b;"><?= h($a['Nama_Gedung']) ?> Lt. <?= h($a['Lantai']) ?></div>
                </td>
                <td>
                    <?php if ($a['WaterReport_ID']): ?>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <?php if ($a['Foto_url']): ?>
                                <a href="<?= h(get_foto_url($a['Foto_url'])) ?>" target="_blank" style="flex-shrink:0;">
                                    <img src="<?= h(get_foto_url($a['Foto_url'])) ?>" alt="Foto" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;display:block;" class="hover:scale-105 transition-all">
                                </a>
                            <?php endif; ?>
                            <div>
                                <div style="font-size:.8rem;font-weight:600;color:#c2410c;">[WR #<?= $a['WaterReport_ID'] ?>] <?= h($a['kategori_laporan']) ?></div>
                                <div style="font-size:.75rem;color:#6b7280;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= h($a['Deskripsi_Report']) ?>"><?= h($a['Deskripsi_Report']) ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <span style="color:#9ca3af;font-size:.8rem;">Routine / Mandiri</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $statusBadge ?>"><?= h($a['Status']) ?></span></td>
                <td style="text-align:center;">
                    <?php if ($a['total_refills'] > 0): ?>
                        <span class="badge badge-green" style="font-weight:700;"><?= $a['total_refills'] ?> Refill</span>
                    <?php else: ?>
                        <?php if ($a['Status'] !== 'Cancelled'): ?>
                            <a href="../refill/create.php?assignment_id=<?= $a['Assignment_ID'] ?>" class="btn-edit" style="font-size:.75rem;padding:4px 8px;background:#e8f0fe;color:#0058bc;">
                                <span class="mat-icon" style="font-size:12px;vertical-align:middle;">add</span> Refill
                            </a>
                        <?php else: ?>
                            <span style="color:#9ca3af;font-size:.8rem;">—</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;">
                    <?= date('d/m/y H:i', strtotime($a['Created_At'])) ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <a href="edit.php?id=<?= $a['Assignment_ID'] ?>" class="btn-edit" title="Edit Penugasan">
                            <span class="mat-icon" style="font-size:15px">edit</span>
                        </a>
                        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus penugasan ini? Log refill terkait juga akan terhapus.')">
                            <input type="hidden" name="id" value="<?= $a['Assignment_ID'] ?>">
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
