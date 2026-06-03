<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
define('ROOT', dirname(__DIR__));

// ── Statistics ──────────────────────────────────────────────────────────────
$total_dispensers = $pdo->query("SELECT COUNT(*) FROM dispenser")->fetchColumn();
$total_locations  = $pdo->query("SELECT COUNT(*) FROM lokasi")->fetchColumn();
$total_staff      = $pdo->query("SELECT COUNT(*) FROM maintenance_staff")->fetchColumn();

$reports_pending  = $pdo->query("SELECT COUNT(*) FROM water_report WHERE Status = 'Pending'")->fetchColumn();
$reports_proses   = $pdo->query("SELECT COUNT(*) FROM water_report WHERE Status = 'Diproses'")->fetchColumn();

$assignments_active = $pdo->query("SELECT COUNT(*) FROM staff_dispenser_assignment WHERE Status IN ('Pending', 'On Progress')")->fetchColumn();
$refills_today      = $pdo->query("SELECT COUNT(*) FROM refill_logs WHERE DATE(Refill_At) = CURDATE()")->fetchColumn();

// ── Recent Laporan (Water Reports) ───────────────────────────────────────────
$recentLaporan = $pdo->query("
    SELECT wr.*, rep.Nama AS nama_pelapor, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
    FROM water_report wr
    JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID
    JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    ORDER BY wr.Reported_At DESC LIMIT 5
")->fetchAll();

// ── Recent Refill ─────────────────────────────────────────────────────────
$recentRefill = $pdo->query("
    SELECT rl.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung
    FROM refill_logs rl
    JOIN staff_dispenser_assignment sda ON rl.Assignment_ID = sda.Assignment_ID
    JOIN maintenance_staff ms ON sda.Staff_ID = ms.Staff_ID
    JOIN dispenser d ON sda.Dispenser_ID = d.Dispenser_ID
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    ORDER BY rl.Refill_At DESC LIMIT 5
")->fetchAll();

// ── Active Assignments ──────────────────────────────────────────────────────
$activeAssignmentsList = $pdo->query("
    SELECT a.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
    FROM staff_dispenser_assignment a
    JOIN maintenance_staff ms ON a.Staff_ID = ms.Staff_ID
    JOIN dispenser d ON a.Dispenser_ID = d.Dispenser_ID
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
    WHERE a.Status IN ('Pending', 'On Progress')
    ORDER BY a.Created_At DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<!-- Stats Grid -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.75rem;">
    <?php
    $stats = [
        ['icon'=>'water_drop',  'val'=>$total_dispensers,  'label'=>'Total Dispenser',  'color'=>'#0058bc', 'bg'=>'#e8f0fe'],
        ['icon'=>'map',         'val'=>$total_locations,   'label'=>'Total Lokasi',     'color'=>'#10b981', 'bg'=>'#dcfce7'],
        ['icon'=>'report',      'val'=>$reports_pending,   'label'=>'Laporan Pending',  'color'=>'#dc2626', 'bg'=>'#fee2e2'],
        ['icon'=>'engineering', 'val'=>$assignments_active,'label'=>'Penugasan Aktif',  'color'=>'#d97706', 'bg'=>'#fef3c7'],
    ];
    foreach ($stats as $s): ?>
    <div class="card p-5 flex items-center gap-4">
        <div style="width:52px;height:52px;border-radius:14px;background:<?= $s['bg'] ?>;
             display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="mat-icon" style="color:<?= $s['color'] ?>;font-size:26px;"><?= $s['icon'] ?></span>
        </div>
        <div>
            <div style="font-size:1.75rem;font-weight:800;color:<?= $s['color'] ?>;line-height:1;"><?= $s['val'] ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:2px;"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Row 2 stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.75rem;">
    <div class="card p-5 flex items-center gap-4">
        <div style="width:48px;height:48px;border-radius:12px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="mat-icon" style="color:#1d4ed8;font-size:24px;">group</span>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#1d4ed8"><?= $total_staff ?></div>
            <div style="font-size:.8rem;color:#6b7280">Total Staff Maintenance</div>
        </div>
    </div>
    <div class="card p-5 flex items-center gap-4">
        <div style="width:48px;height:48px;border-radius:12px;background:#e0f2fe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="mat-icon" style="color:#0369a1;font-size:24px;">recycling</span>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#0369a1"><?= $refills_today ?></div>
            <div style="font-size:.8rem;color:#6b7280">Refill Galon Hari Ini</div>
        </div>
    </div>
    <div class="card p-5 flex items-center gap-4">
        <div style="width:48px;height:48px;border-radius:12px;background:#f3e8ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="mat-icon" style="color:#7e22ce;font-size:24px;">assignment</span>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#7e22ce"><?= $reports_proses ?></div>
            <div style="font-size:.8rem;color:#6b7280">Laporan Sedang Diproses</div>
        </div>
    </div>
</div>

<!-- Main content grid -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start;">

    <!-- Left: Laporan Terbaru -->
    <div class="card">
        <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:1rem;font-weight:700;color:#0b1f3a">Laporan Kendala Terbaru</div>
                <div style="font-size:.8rem;color:#9ca3af;margin-top:2px"><?= $reports_pending ?> pending · <?= $reports_proses ?> diproses</div>
            </div>
            <a href="../laporan/index.php" class="btn-secondary" style="padding:8px 16px;font-size:.8rem;">Lihat Semua</a>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Pelapor</th>
                    <th>Dispenser</th>
                    <th>Kategori Masalah</th>
                    <th>Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentLaporan)): ?>
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:32px;">Belum ada laporan kendala</td></tr>
            <?php else: foreach ($recentLaporan as $l):
                $statusBadge = match($l['Status']) {
                    'Pending'   => 'badge-yellow',
                    'Diproses'  => 'badge-blue',
                    'Selesai'   => 'badge-green',
                    'Ditolak'   => 'badge-red',
                    default     => 'badge-gray',
                };
            ?>
                <tr>
                    <td style="font-weight:600">
                        <div><?= h($l['nama_pelapor']) ?></div>
                    </td>
                    <td>
                        <div style="font-size:.8rem;font-weight:600"><?= h($l['Kode_Dispenser']) ?></div>
                        <div style="font-size:.75rem;color:#9ca3af"><?= h($l['Nama_Gedung']) ?> · Lt <?= h($l['Lantai']) ?></div>
                    </td>
                    <td><span class="badge badge-orange"><?= h($l['Kategori']) ?></span></td>
                    <td><span class="badge <?= $statusBadge ?>"><?= h($l['Status']) ?></span></td>
                    <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;"><?= date('d/m H:i', strtotime($l['Reported_At'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:1.25rem;">

        <!-- Active Assignments List -->
        <div class="card">
            <div style="padding:18px 20px;border-bottom:1px solid #f3f4f6;">
                <div style="font-size:.95rem;font-weight:700;color:#0b1f3a">🛠️ Penugasan Aktif</div>
                <div style="font-size:.78rem;color:#9ca3af;margin-top:2px">Sedang dikerjakan oleh staff</div>
            </div>
            <div style="padding:12px;">
            <?php if (empty($activeAssignmentsList)): ?>
                <div style="text-align:center;color:#9ca3af;padding:20px;font-size:.85rem;">Tidak ada penugasan aktif</div>
            <?php else: foreach ($activeAssignmentsList as $a):
                $ab = match($a['Status']) { 'Pending'=>'badge-yellow','On Progress'=>'badge-blue','Completed'=>'badge-green','Cancelled'=>'badge-red',default=>'badge-gray' };
            ?>
                <div style="padding:10px;border-radius:10px;background:#f9fafb;margin-bottom:8px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                        <div>
                            <div style="font-size:.82rem;font-weight:700;color:#374151"><?= h($a['Kode_Dispenser']) ?></div>
                            <div style="font-size:.72rem;color:#9ca3af"><?= h($a['Nama_Gedung']) ?> · Lt.<?= h($a['Lantai']) ?></div>
                            <div style="font-size:.75rem;color:#0058bc;margin-top:2px;font-weight:600;">Staff: <?= h($a['nama_staff']) ?></div>
                        </div>
                        <span class="badge <?= $ab ?>" style="font-size:.7rem;"><?= $a['Status'] ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card" style="padding:20px;">
            <div style="font-size:.95rem;font-weight:700;color:#0b1f3a;margin-bottom:14px;">Aksi Cepat</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="../dispensers/create.php" class="btn-primary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">add_circle</span> Tambah Dispenser
                </a>
                <a href="../assignments/create.php" class="btn-secondary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">assignment_turned_in</span> Buat Penugasan
                </a>
                <a href="../refill/create.php" class="btn-secondary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">recycling</span> Catat Refill
                </a>
                <a href="../staff/create.php" class="btn-secondary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">person_add</span> Tambah Staff
                </a>
            </div>
        </div>

    </div><!-- /right col -->
</div>

<!-- Recent Refill -->
<div class="card" style="margin-top:1.25rem;">
    <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:1rem;font-weight:700;color:#0b1f3a">Riwayat Refill Terkini</div>
        <a href="../refill/index.php" class="btn-secondary" style="padding:8px 16px;font-size:.8rem;">Lihat Semua</a>
    </div>
    <div style="overflow-x:auto;">
    <table>
        <thead><tr><th>Dispenser</th><th>Gedung</th><th>Staff</th><th>Catatan</th><th>Waktu Refill</th></tr></thead>
        <tbody>
        <?php if (empty($recentRefill)): ?>
            <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:32px;">Belum ada riwayat refill</td></tr>
        <?php else: foreach ($recentRefill as $r): ?>
            <tr>
                <td style="font-weight:600"><?= h($r['Kode_Dispenser']) ?></td>
                <td><span class="badge badge-blue"><?= h($r['Nama_Gedung']) ?></span></td>
                <td><?= h($r['nama_staff']) ?></td>
                <td style="font-size:.82rem;"><?= h($r['Catatan'] ?? '—') ?></td>
                <td style="font-size:.82rem;color:#9ca3af"><?= date('d M Y, H:i', strtotime($r['Refill_At'])) ?> WIB</td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
