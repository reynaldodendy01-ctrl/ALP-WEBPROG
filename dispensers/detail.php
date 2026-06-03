<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Detail Dispenser';
$activeMenu = 'dispensers';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT d.*, l.Nama_Gedung, l.Lantai, l.Keterangan
    FROM dispenser d
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    WHERE d.Dispenser_ID = :id
");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();
if (!$d) { set_flash('error', 'Dispenser tidak ditemukan.'); header('Location: index.php'); exit; }

// Related Laporan
$stmtLaporan = $pdo->prepare("
    SELECT wr.*, rep.Nama AS nama_pelapor, rep.Nim AS nim_pelapor 
    FROM water_report wr
    JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID
    WHERE wr.Dispenser_ID = :id 
    ORDER BY wr.Reported_At DESC LIMIT 8
");
$stmtLaporan->execute([':id' => $id]);
$laporan = $stmtLaporan->fetchAll();

// Related Assignments & Refill Logs
$stmtAssignments = $pdo->prepare("
    SELECT sda.*, ms.Nama AS nama_staff, ms.No_Telp AS hp_staff,
           (SELECT COUNT(*) FROM refill_logs rl WHERE rl.Assignment_ID = sda.Assignment_ID) AS has_refill
    FROM staff_dispenser_assignment sda
    JOIN maintenance_staff ms ON sda.Staff_ID = ms.Staff_ID
    WHERE sda.Dispenser_ID = :id 
    ORDER BY sda.Created_At DESC LIMIT 8
");
$stmtAssignments->execute([':id' => $id]);
$assignments = $stmtAssignments->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali ke Daftar
        </a>
        <div class="page-title">Dispenser: <?= h($d['Kode_Dispenser']) ?></div>
        <div class="page-subtitle"><?= h($d['Nama_Gedung']) ?> · Lantai <?= h($d['Lantai']) ?></div>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="edit.php?id=<?= $d['Dispenser_ID'] ?>" class="btn-secondary">
            <span class="mat-icon" style="font-size:18px">edit</span> Edit
        </a>
        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus dispenser ini?')">
            <input type="hidden" name="id" value="<?= $d['Dispenser_ID'] ?>">
            <button type="submit" class="btn-danger" style="padding:10px 16px;">
                <span class="mat-icon" style="font-size:18px">delete</span> Hapus
            </button>
        </form>
    </div>
</div>

<!-- Info Cards -->
<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem;">
    <div class="card" style="padding:24px;">
        <div style="font-size:.78rem;color:#9ca3af;font-weight:600;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">Kategori Dispenser</div>
        <span class="badge badge-blue" style="font-size:.95rem;padding:8px 16px;"><?= h($d['Kategori']) ?></span>
    </div>
    <div class="card" style="padding:24px;">
        <div style="font-size:.78rem;color:#9ca3af;font-weight:600;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">Keterangan Lokasi</div>
        <div style="font-size:1rem;font-weight:700;color:#0b1f3a"><?= h($d['Keterangan'] ?? '—') ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
    <!-- Laporan -->
    <div class="card">
        <div style="padding:18px 22px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:.95rem;font-weight:700;color:#0b1f3a">Laporan Kendala (Water Reports)</div>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Pelapor</th><th>Kategori</th><th>Status</th><th>Waktu</th></tr></thead>
            <tbody>
            <?php if (empty($laporan)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:24px;font-size:.85rem;">Belum ada laporan kendala</td></tr>
            <?php else: foreach ($laporan as $l):
                $sb = match($l['Status']) { 'Pending'=>'badge-yellow','Diproses'=>'badge-blue','Selesai'=>'badge-green','Ditolak'=>'badge-red',default=>'badge-gray' };
            ?>
                <tr>
                    <td style="font-size:.85rem;font-weight:600">
                        <div><?= h($l['nama_pelapor']) ?></div>
                        <div style="font-size:.7rem;color:#9ca3af"><?= h($l['nim_pelapor']) ?></div>
                    </td>
                    <td style="font-size:.8rem"><span class="badge badge-orange"><?= h($l['Kategori']) ?></span></td>
                    <td><span class="badge <?= $sb ?>" style="font-size:.72rem"><?= $l['Status'] ?></span></td>
                    <td style="font-size:.75rem;color:#9ca3af"><?= date('d/m H:i', strtotime($l['Reported_At'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Assignments History -->
    <div class="card">
        <div style="padding:18px 22px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:.95rem;font-weight:700;color:#0b1f3a">Riwayat Penugasan (Assignments)</div>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Staff</th><th>Status Penugasan</th><th>Refill</th><th>Tanggal</th></tr></thead>
            <tbody>
            <?php if (empty($assignments)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:24px;font-size:.85rem;">Belum ada riwayat penugasan</td></tr>
            <?php else: foreach ($assignments as $a):
                $ab = match($a['Status']) { 'Pending'=>'badge-yellow','On Progress'=>'badge-blue','Completed'=>'badge-green','Cancelled'=>'badge-red',default=>'badge-gray' };
            ?>
                <tr>
                    <td style="font-size:.85rem;font-weight:600">
                        <div><?= h($a['nama_staff']) ?></div>
                        <div style="font-size:.7rem;color:#9ca3af"><?= h($a['hp_staff']) ?></div>
                    </td>
                    <td><span class="badge <?= $ab ?>" style="font-size:.72rem"><?= $a['Status'] ?></span></td>
                    <td style="font-size:.8rem;text-align:center;">
                        <?= $a['has_refill'] > 0 ? '✓ Ya' : '—' ?>
                    </td>
                    <td style="font-size:.75rem;color:#9ca3af"><?= date('d/m H:i', strtotime($a['Created_At'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
