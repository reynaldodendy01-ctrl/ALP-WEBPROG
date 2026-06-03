<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
define('ROOT', dirname(__DIR__));

// ── Statistics ──────────────────────────────────────────────────────────────
$total_dispensers = $pdo->query("SELECT COUNT(*) FROM dispensers")->fetchColumn();
$kosong           = $pdo->query("SELECT COUNT(*) FROM dispensers WHERE status='Kosong'")->fetchColumn();
$rusak            = $pdo->query("SELECT COUNT(*) FROM dispensers WHERE status IN ('Rusak','Maintenance')")->fetchColumn();
$laporan_pending  = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status='Pending'")->fetchColumn();
$laporan_proses   = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status='Diproses'")->fetchColumn();
$total_staff      = $pdo->query("SELECT COUNT(*) FROM staff WHERE status='Aktif'")->fetchColumn();
$refill_today     = $pdo->query("SELECT COALESCE(SUM(jumlah_galon),0) FROM refill_log WHERE DATE(tanggal_refill)=CURDATE()")->fetchColumn();
$stok_kritis      = $pdo->query("SELECT COUNT(*) FROM galon WHERE jumlah_tersedia = 0")->fetchColumn();

// ── Recent Laporan ───────────────────────────────────────────────────────────
$recentLaporan = $pdo->query("
    SELECT l.*, d.nama_lokasi, d.gedung, d.lantai, s.nama AS nama_staff
    FROM laporan l
    JOIN dispensers d ON l.dispenser_id = d.id
    LEFT JOIN staff s ON l.staff_id = s.id
    ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();

// ── Dispenser Status Distribution ──────────────────────────────────────────
$statusDist = $pdo->query("SELECT status, COUNT(*) AS total FROM dispensers GROUP BY status")->fetchAll();

// ── Stok Rendah ────────────────────────────────────────────────────────────
$lowStock = $pdo->query("
    SELECT g.*, d.nama_lokasi, d.gedung, d.lantai
    FROM galon g
    JOIN dispensers d ON g.dispenser_id = d.id
    WHERE g.jumlah_tersedia <= 1
    ORDER BY g.jumlah_tersedia ASC LIMIT 6
")->fetchAll();

// ── Recent Refill ─────────────────────────────────────────────────────────
$recentRefill = $pdo->query("
    SELECT r.*, d.nama_lokasi, d.gedung, s.nama AS nama_staff
    FROM refill_log r
    JOIN dispensers d ON r.dispenser_id = d.id
    LEFT JOIN staff s ON r.staff_id = s.id
    ORDER BY r.tanggal_refill DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<!-- Stats Grid -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.75rem;">
    <?php
    $stats = [
        ['icon'=>'water_drop',  'val'=>$total_dispensers, 'label'=>'Total Dispenser', 'color'=>'#0058bc', 'bg'=>'#e8f0fe'],
        ['icon'=>'warning',     'val'=>$kosong,           'label'=>'Galon Kosong',    'color'=>'#d97706', 'bg'=>'#fef3c7'],
        ['icon'=>'build',       'val'=>$rusak,            'label'=>'Rusak/Maintenance','color'=>'#dc2626','bg'=>'#fee2e2'],
        ['icon'=>'report',      'val'=>$laporan_pending,  'label'=>'Laporan Pending', 'color'=>'#7c3aed', 'bg'=>'#ede9fe'],
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
        <div style="width:48px;height:48px;border-radius:12px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="mat-icon" style="color:#16a34a;font-size:24px;">engineering</span>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#16a34a"><?= $total_staff ?></div>
            <div style="font-size:.8rem;color:#6b7280">Staff Aktif</div>
        </div>
    </div>
    <div class="card p-5 flex items-center gap-4">
        <div style="width:48px;height:48px;border-radius:12px;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="mat-icon" style="color:#1d4ed8;font-size:24px;">recycling</span>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#1d4ed8"><?= $refill_today ?></div>
            <div style="font-size:.8rem;color:#6b7280">Galon Diisi Hari Ini</div>
        </div>
    </div>
    <div class="card p-5 flex items-center gap-4">
        <div style="width:48px;height:48px;border-radius:12px;background:#ffedd5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <span class="mat-icon" style="color:#c2410c;font-size:24px;">inventory_2</span>
        </div>
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#c2410c"><?= $stok_kritis ?></div>
            <div style="font-size:.8rem;color:#6b7280">Stok Kritis (0 Galon)</div>
        </div>
    </div>
</div>

<!-- Main content grid -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start;">

    <!-- Left: Laporan Terbaru -->
    <div class="card">
        <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:1rem;font-weight:700;color:#0b1f3a">Laporan Terbaru</div>
                <div style="font-size:.8rem;color:#9ca3af;margin-top:2px"><?= $laporan_pending ?> pending · <?= $laporan_proses ?> diproses</div>
            </div>
            <a href="../laporan/index.php" class="btn-secondary" style="padding:8px 16px;font-size:.8rem;">Lihat Semua</a>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Pelapor</th>
                    <th>Dispenser</th>
                    <th>Masalah</th>
                    <th>Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentLaporan)): ?>
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:32px;">Belum ada laporan</td></tr>
            <?php else: foreach ($recentLaporan as $l):
                $statusBadge = match($l['status']) {
                    'Pending'   => 'badge-yellow',
                    'Diproses'  => 'badge-blue',
                    'Selesai'   => 'badge-green',
                    'Ditolak'   => 'badge-red',
                    default     => 'badge-gray',
                };
            ?>
                <tr>
                    <td style="font-weight:600"><?= h($l['nama_pelapor']) ?></td>
                    <td>
                        <div style="font-size:.8rem;font-weight:600"><?= h($l['nama_lokasi']) ?></div>
                        <div style="font-size:.75rem;color:#9ca3af"><?= h($l['gedung']) ?> · Lt <?= h($l['lantai']) ?></div>
                    </td>
                    <td><span class="badge badge-orange"><?= h($l['jenis_masalah']) ?></span></td>
                    <td><span class="badge <?= $statusBadge ?>"><?= h($l['status']) ?></span></td>
                    <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;"><?= date('d/m H:i', strtotime($l['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:1.25rem;">

        <!-- Stok Kritis -->
        <div class="card">
            <div style="padding:18px 20px;border-bottom:1px solid #f3f4f6;">
                <div style="font-size:.95rem;font-weight:700;color:#0b1f3a">⚠️ Stok Kritis</div>
                <div style="font-size:.78rem;color:#9ca3af;margin-top:2px">Dispenser perlu diisi segera</div>
            </div>
            <div style="padding:12px;">
            <?php if (empty($lowStock)): ?>
                <div style="text-align:center;color:#9ca3af;padding:20px;font-size:.85rem;">Semua stok aman ✓</div>
            <?php else: foreach ($lowStock as $g):
                $pct = $g['kapasitas_max'] > 0 ? ($g['jumlah_tersedia'] / $g['kapasitas_max']) * 100 : 0;
                $barColor = $g['jumlah_tersedia'] == 0 ? '#ef4444' : ($pct < 40 ? '#f59e0b' : '#22c55e');
            ?>
                <div style="padding:10px;border-radius:10px;background:#f9fafb;margin-bottom:8px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <div>
                            <div style="font-size:.82rem;font-weight:600;color:#374151"><?= h(mb_strimwidth($g['nama_lokasi'], 0, 28, '…')) ?></div>
                            <div style="font-size:.72rem;color:#9ca3af"><?= h($g['gedung']) ?> Lt.<?= h($g['lantai']) ?></div>
                        </div>
                        <span style="font-size:.9rem;font-weight:800;color:<?= $barColor ?>;">
                            <?= $g['jumlah_tersedia'] ?>/<?= $g['kapasitas_max'] ?>
                        </span>
                    </div>
                    <div style="height:6px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $barColor ?>;border-radius:99px;transition:width .3s;"></div>
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
                <a href="../laporan/create.php" class="btn-secondary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">edit_note</span> Buat Laporan
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
        <thead><tr><th>Dispenser</th><th>Gedung</th><th>Staff</th><th>Jumlah Galon</th><th>Waktu Refill</th></tr></thead>
        <tbody>
        <?php if (empty($recentRefill)): ?>
            <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:32px;">Belum ada riwayat refill</td></tr>
        <?php else: foreach ($recentRefill as $r): ?>
            <tr>
                <td style="font-weight:600"><?= h($r['nama_lokasi']) ?></td>
                <td><span class="badge badge-blue"><?= h($r['gedung']) ?></span></td>
                <td><?= h($r['nama_staff'] ?? '—') ?></td>
                <td>
                    <span style="font-size:1.1rem;font-weight:800;color:#0058bc"><?= $r['jumlah_galon'] ?></span>
                    <span style="font-size:.8rem;color:#9ca3af"> galon</span>
                </td>
                <td style="font-size:.82rem;color:#9ca3af"><?= date('d M Y, H:i', strtotime($r['tanggal_refill'])) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
