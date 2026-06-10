<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
define('ROOT', dirname(__DIR__));

if (!isset($_SESSION['staff_role'])) {
    $stmt = $pdo->prepare("SELECT Role FROM maintenance_staff WHERE Staff_ID = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['staff_id']]);
    $_SESSION['staff_role'] = $stmt->fetchColumn() ?: 'Staff';
}

$isStaff = ($_SESSION['staff_role'] === 'Staff');

if ($isStaff) {
    // ── Queries for Staff ──────────────────────────────────────────────────
    $staff_id = $_SESSION['staff_id'];

    // 1. My active tasks count (Pending or On Progress)
    $stmtMyTasksCount = $pdo->prepare("SELECT COUNT(*) FROM staff_dispenser_assignment WHERE Staff_ID = :staff_id AND Status IN ('Pending', 'On Progress')");
    $stmtMyTasksCount->execute([':staff_id' => $staff_id]);
    $total_my_active_tasks = $stmtMyTasksCount->fetchColumn();

    // 2. Global pending water reports (unclaimed/waiting for staff)
    $total_pending_reports = $pdo->query("SELECT COUNT(*) FROM water_report WHERE Status = 'Pending'")->fetchColumn();

    // 3. My completed refills count
    $stmtMyRefillsCount = $pdo->prepare("
        SELECT COUNT(*) FROM refill_logs rl
        JOIN staff_dispenser_assignment sda ON rl.Assignment_ID = sda.Assignment_ID
        WHERE sda.Staff_ID = :staff_id
    ");
    $stmtMyRefillsCount->execute([':staff_id' => $staff_id]);
    $total_my_completed_refills = $stmtMyRefillsCount->fetchColumn();

    // 4. My active tasks list
    $stmtMyActiveTasks = $pdo->prepare("
        SELECT a.*, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai,
               wr.Kategori AS kategori_laporan, wr.Deskripsi_Report, wr.Foto_url
        FROM staff_dispenser_assignment a
        JOIN dispenser d ON a.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        LEFT JOIN water_report wr ON a.WaterReport_ID = wr.WaterReport_ID
        WHERE a.Staff_ID = :staff_id AND a.Status IN ('Pending', 'On Progress')
        ORDER BY a.Created_At DESC
    ");
    $stmtMyActiveTasks->execute([':staff_id' => $staff_id]);
    $my_active_tasks = $stmtMyActiveTasks->fetchAll();

    // 5. Global pending water reports (for staff to accept)
    $unclaimed_reports = $pdo->query("
        SELECT wr.*, rep.Nama AS nama_pelapor, rep.Nim AS nim_pelapor, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
        FROM water_report wr
        JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID
        JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        WHERE wr.Status = 'Pending'
        ORDER BY wr.Reported_At DESC LIMIT 10
    ")->fetchAll();

} else {
    // ── Queries for Admin (Super Admin) ───────────────────────────────────
    $total_dispensers = $pdo->query("SELECT COUNT(*) FROM dispenser")->fetchColumn();
    $total_locations  = $pdo->query("SELECT COUNT(*) FROM lokasi")->fetchColumn();
    $total_staff      = $pdo->query("SELECT COUNT(*) FROM maintenance_staff")->fetchColumn();

    $reports_pending  = $pdo->query("SELECT COUNT(*) FROM water_report WHERE Status = 'Pending'")->fetchColumn();
    $reports_proses   = $pdo->query("SELECT COUNT(*) FROM water_report WHERE Status = 'Diproses'")->fetchColumn();

    $assignments_active = $pdo->query("SELECT COUNT(*) FROM staff_dispenser_assignment WHERE Status IN ('Pending', 'On Progress')")->fetchColumn();
    $refills_today      = $pdo->query("SELECT COUNT(*) FROM refill_logs WHERE DATE(Refill_At) = CURDATE()")->fetchColumn();

    // Recent Laporan (Water Reports)
    $recentLaporan = $pdo->query("
        SELECT wr.*, rep.Nama AS nama_pelapor, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
        FROM water_report wr
        JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID
        JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        ORDER BY wr.Reported_At DESC LIMIT 5
    ")->fetchAll();

    // Recent Refill
    $recentRefill = $pdo->query("
        SELECT rl.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung
        FROM refill_logs rl
        JOIN staff_dispenser_assignment sda ON rl.Assignment_ID = sda.Assignment_ID
        JOIN maintenance_staff ms ON sda.Staff_ID = ms.Staff_ID
        JOIN dispenser d ON sda.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        ORDER BY rl.Refill_At DESC LIMIT 5
    ")->fetchAll();

    // Active Assignments
    $activeAssignmentsList = $pdo->query("
        SELECT a.*, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
        FROM staff_dispenser_assignment a
        JOIN maintenance_staff ms ON a.Staff_ID = ms.Staff_ID
        JOIN dispenser d ON a.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        WHERE a.Status IN ('Pending', 'On Progress')
        ORDER BY a.Created_At DESC LIMIT 5
    ")->fetchAll();
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<?php if ($isStaff): ?>
<!-- ========================================== -->
<!--  STAFF / OFFICE BOY DASHBOARD INTERFACE    -->
<!-- ========================================== -->

<!-- Welcome Message -->
<div class="card p-6 mb-6 bg-gradient-to-r from-blue-900 to-indigo-900 text-white relative overflow-hidden" style="border: none;">
    <div class="absolute w-[200px] h-[200px] rounded-full bg-blue-500/10 blur-[50px] -top-10 -left-10"></div>
    <div class="relative z-10 flex items-center justify-between">
        <div>
            <div style="font-size: 1.5rem; font-weight: 800;">Halo, <?= h($_SESSION['staff_name']) ?>! 👋</div>
            <p class="text-blue-100 text-sm mt-1">Ini adalah pusat tugas Anda. Fokus hari ini: cek dispenser, isi air, dan tangani laporan kerusakan.</p>
        </div>
        <span class="material-symbols-outlined text-[48px] text-blue-300 opacity-60">engineering</span>
    </div>
</div>

<!-- Stats Grid (Staff Focus) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-7">
    <?php
    $stats = [
        ['icon'=>'assignment_turned_in', 'val'=>$total_my_active_tasks, 'label'=>'Tugas Aktif Saya', 'color'=>'#0058bc', 'bg'=>'#e8f0fe'],
        ['icon'=>'campaign',            'val'=>$total_pending_reports,  'label'=>'Laporan Pending Kampus',  'color'=>'#dc2626', 'bg'=>'#fee2e2'],
        ['icon'=>'task_alt',            'val'=>$total_my_completed_refills, 'label'=>'Total Refill Selesai', 'color'=>'#10b981', 'bg'=>'#dcfce7'],
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

<!-- Main content grid -->
<div class="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-5 items-start">

    <!-- Left Column: Tasks and Laporan -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        
        <!-- List 1: Tugas Aktif Saya -->
        <div class="card">
            <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;">
                <div style="font-size:1rem;font-weight:700;color:#0b1f3a">🛠️ Tugas Aktif Saya</div>
                <div style="font-size:.8rem;color:#9ca3af;margin-top:2px">Selesaikan tugas di bawah ini dengan mengisi galon / memperbaiki kendala.</div>
            </div>
            
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Dispenser</th>
                            <th>Kategori Masalah</th>
                            <th>Foto</th>
                            <th>Status Kerja</th>
                            <th>Waktu Tugas</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($my_active_tasks)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;color:#9ca3af;padding:48px;">
                                <span class="mat-icon" style="font-size:36px;display:block;margin-bottom:8px;color:#cbd5e1;">task</span>
                                Belum ada tugas aktif. Silakan ambil dari laporan masuk di bawah!
                            </td>
                        </tr>
                    <?php else: foreach ($my_active_tasks as $t):
                        $statusBadge = $t['Status'] === 'Pending' ? 'badge-yellow' : 'badge-blue';
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;color:#0058bc;"><?= h($t['Kode_Dispenser']) ?></div>
                                <div style="font-size:.78rem;color:#50616b;"><?= h($t['Nama_Gedung']) ?> Lt. <?= h($t['Lantai']) ?></div>
                            </td>
                            <td>
                                <?php if ($t['WaterReport_ID']): ?>
                                    <span class="badge badge-orange"><?= h($t['kategori_laporan']) ?></span>
                                    <div style="font-size:.75rem;color:#9ca3af;margin-top:2px;max-width:200px;text-overflow:ellipsis;white-space:nowrap;overflow:hidden;" title="<?= h($t['Deskripsi_Report']) ?>"><?= h($t['Deskripsi_Report']) ?></div>
                                <?php else: ?>
                                    <span style="color:#9ca3af;font-size:.8rem;">Routine / Mandiri</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['WaterReport_ID'] && $t['Foto_url']): ?>
                                    <img src="<?= h(get_foto_url($t['Foto_url'])) ?>" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;cursor:pointer;" onclick="openPhotoPopup('<?= h(get_foto_url($t['Foto_url'])) ?>')">
                                <?php else: ?>
                                    <span style="color:#9ca3af">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $statusBadge ?>"><?= h($t['Status']) ?></span>
                            </td>
                            <td style="font-size:.78rem;color:#9ca3af;">
                                <?= date('d/m H:i', strtotime($t['Created_At'])) ?>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                                    <?php if ($t['Status'] === 'Pending'): ?>
                                        <a href="../laporan/start_assignment.php?id=<?= $t['Assignment_ID'] ?>" 
                                           class="btn-primary" style="padding:6px 12px; font-size: .75rem; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);">
                                            <span class="mat-icon" style="font-size:14px; vertical-align:middle;">play_arrow</span> Mulai
                                        </a>
                                    <?php else: ?>
                                        <a href="../refill/create.php?assignment_id=<?= $t['Assignment_ID'] ?>" 
                                           class="btn-primary" style="padding:6px 12px; font-size: .75rem;">
                                            <span class="mat-icon" style="font-size:14px; vertical-align:middle;">done</span> Refill
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($t['WaterReport_ID']): ?>
                                        <a href="../laporan/tolak.php?id=<?= $t['WaterReport_ID'] ?>" 
                                           class="btn-danger" style="padding:6px 12px; font-size: .75rem;" onclick="return confirm('Tolak laporan ini sebagai laporan palsu?')">
                                            <span class="mat-icon" style="font-size:14px; vertical-align:middle;">block</span> Tolak
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- List 2: Laporan Masuk Baru (Unclaimed) -->
        <div class="card">
            <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-size:1rem;font-weight:700;color:#0b1f3a">📢 Laporan Masuk Terbaru (Belum Diambil)</div>
                    <div style="font-size:.8rem;color:#9ca3af;margin-top:2px">Laporan dari mahasiswa/reporter yang belum dikerjakan. Ambil untuk menjadikannya tugas Anda.</div>
                </div>
            </div>
            
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Pelapor</th>
                            <th>Dispenser</th>
                            <th>Masalah</th>
                            <th>Deskripsi</th>
                            <th>Foto</th>
                            <th>Waktu Masuk</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($unclaimed_reports)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;color:#9ca3af;padding:48px;">
                                <span class="mat-icon" style="font-size:36px;display:block;margin-bottom:8px;color:#cbd5e1;">campaign</span>
                                Bersih! Tidak ada laporan kendala yang menunggu saat ini.
                            </td>
                        </tr>
                    <?php else: foreach ($unclaimed_reports as $ur): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;color:#0b1f3a;"><?= h($ur['nama_pelapor']) ?></div>
                                <div style="font-size:.78rem;color:#6b7280;">NIM: <?= h($ur['nim_pelapor']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:700;color:#0058bc;"><?= h($ur['Kode_Dispenser']) ?></div>
                                <div style="font-size:.78rem;color:#50616b;"><?= h($ur['Nama_Gedung']) ?> Lt. <?= h($ur['Lantai']) ?></div>
                            </td>
                            <td>
                                <span class="badge badge-orange"><?= h($ur['Kategori']) ?></span>
                            </td>
                            <td style="font-size:.82rem;max-width:200px;word-wrap:break-word;white-space:normal;"><?= h($ur['Deskripsi_Report']) ?></td>
                            <td>
                                <?php if ($ur['Foto_url']): ?>
                                    <img src="<?= h(get_foto_url($ur['Foto_url'])) ?>" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;cursor:pointer;" onclick="openPhotoPopup('<?= h(get_foto_url($ur['Foto_url'])) ?>')">
                                <?php else: ?>
                                    <span style="color:#9ca3af">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.78rem;color:#9ca3af;white-space:nowrap;">
                                <?= date('d/m H:i', strtotime($ur['Reported_At'])) ?>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                                    <a href="../laporan/ambil.php?id=<?= $ur['WaterReport_ID'] ?>" 
                                       class="btn-primary" style="padding:6px 12px; font-size: .75rem; background: linear-gradient(135deg, #0058bc, #1d4ed8);">
                                        <span class="mat-icon" style="font-size:14px; vertical-align:middle;">assignment</span> Ambil Tugas
                                    </a>
                                    <a href="../laporan/tolak.php?id=<?= $ur['WaterReport_ID'] ?>" 
                                       class="btn-danger" style="padding:6px 12px; font-size: .75rem;" onclick="return confirm('Tolak laporan ini sebagai laporan palsu?')">
                                        <span class="mat-icon" style="font-size:14px; vertical-align:middle;">block</span> Tolak
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /left column -->

    <!-- Right Column: Quick Links -->
    <div style="display:flex;flex-direction:column;gap:1.25rem;">
        
        <div class="card" style="padding:20px;">
            <div style="font-size:.95rem;font-weight:700;color:#0b1f3a;margin-bottom:14px;">Aksi Cepat Staff</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="../laporan/index.php" class="btn-secondary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">report</span> Lihat Semua Laporan
                </a>
                <a href="../refill/index.php" class="btn-secondary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">history</span> Riwayat Refill Log
                </a>
            </div>
        </div>

        <div class="card" style="padding:20px;background:#f8fafc;border:1px dashed #cbd5e1;text-align:center;">
            <span class="material-symbols-outlined text-[40px] text-slate-400 mb-2">info</span>
            <div style="font-size:.85rem;font-weight:700;color:#334155;">Pemberitahuan Sistem</div>
            <p style="font-size:.75rem;color:#64748b;margin-top:6px;line-height:1.5;">Setiap penanganan dispenser yang selesai dikerjakan WAJIB dicatat melalui menu Refill untuk pemutakhiran stok galon real-time.</p>
        </div>

    </div><!-- /right column -->

</div>

<?php else: ?>
<!-- ========================================== -->
<!--  SUPER ADMIN DASHBOARD INTERFACE            -->
<!-- ========================================== -->

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-7">
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
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-7">
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
<div class="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-5 items-start">

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
                    <th>Foto</th>
                    <th>Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentLaporan)): ?>
                <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:32px;">Belum ada laporan kendala</td></tr>
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
                    <td>
                        <?php if ($l['Foto_url']): ?>
                            <img src="<?= h(get_foto_url($l['Foto_url'])) ?>" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;cursor:pointer;" onclick="openPhotoPopup('<?= h(get_foto_url($l['Foto_url'])) ?>')">
                        <?php else: ?>
                            <span style="color:#9ca3af">—</span>
                        <?php endif; ?>
                    </td>
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
            <div style="font-size:.95rem;font-weight:700;color:#0b1f3a;margin-bottom:14px;">Aksi Cepat Admin</div>
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
<?php endif; ?>

<!-- Photo Popup Modal -->
<div id="photo-popup" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center;" onclick="closePhotoPopup()">
    <div style="position:relative;max-width:90vw;max-height:90vh;" onclick="event.stopPropagation()">
        <img id="photo-popup-img" src="" alt="Foto" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:12px;display:block;">
        <button onclick="closePhotoPopup()" style="position:absolute;top:-14px;right:-14px;background:#fff;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.3);">&times;</button>
    </div>
</div>

<script>
function openPhotoPopup(src) {
    document.getElementById('photo-popup-img').src = src;
    var popup = document.getElementById('photo-popup');
    popup.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closePhotoPopup() {
    document.getElementById('photo-popup').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePhotoPopup();
});
</script>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
