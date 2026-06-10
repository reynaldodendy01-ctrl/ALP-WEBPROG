<?php
/**
 * =============================================================================
 * dashboard/index.php — Halaman Beranda Dashboard (Admin & Staff)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman utama (beranda) dari dashboard internal sistem
 *   CariGalon yang bersifat adaptif: tampilannya berubah secara dinamis tergantung
 *   pada role pengguna yang sedang login. Untuk role 'Staff' (Office Boy / Teknisi),
 *   halaman menampilkan daftar tugas aktif pribadi, laporan masuk yang belum diambil,
 *   dan statistik personal. Untuk role 'Admin' (Super Admin), halaman menampilkan
 *   ringkasan statistik global sistem, laporan kendala terbaru dari seluruh dispenser,
 *   riwayat refill terkini, penugasan aktif semua staff, dan menu aksi cepat admin.
 *   Halaman ini menjadi pusat operasional harian bagi kedua tipe pengguna.
 *
 * FUNGSI UTAMA:
 *   - Mendeteksi role pengguna dari sesi dan menyesuaikan tampilan dashboard
 *   - Untuk Staff: menampilkan tugas aktif pribadi (Pending/On Progress)
 *   - Untuk Staff: menampilkan daftar laporan masuk (Pending/unclaimed) yang bisa diambil
 *   - Untuk Staff: menampilkan statistik (tugas aktif, laporan pending, total refill selesai)
 *   - Untuk Admin: menampilkan statistik global (total dispenser, lokasi, laporan, penugasan)
 *   - Untuk Admin: menampilkan tabel laporan kendala terbaru (5 terakhir)
 *   - Untuk Admin: menampilkan tabel riwayat refill terkini (5 terakhir)
 *   - Untuk Admin: menampilkan daftar penugasan aktif (5 terbaru)
 *   - Menyediakan popup modal untuk melihat foto laporan dalam ukuran besar
 *   - Menyediakan tombol aksi cepat (Quick Actions) untuk navigasi ke halaman terkait
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include; sesi dimulai; konstanta ROOT didefinisikan
 *   2. Role pengguna diambil dari sesi; jika tidak ada, di-query dari database
 *   3. Berdasarkan $isStaff (boolean), cabang query yang berbeda dijalankan:
 *      - Staff: query tugas aktif, laporan pending, laporan unclaimed, jumlah refill
 *      - Admin: query total dispenser, lokasi, staff, laporan, penugasan, refill hari ini
 *              + query list laporan terbaru, refill terbaru, penugasan aktif
 *   4. layout_head.php di-include untuk merender sidebar + header dashboard
 *   5. render_flash() dipanggil untuk menampilkan notifikasi sesi jika ada
 *   6. Konten HTML dashboard dirender sesuai kondisi $isStaff (if/else)
 *   7. Modal popup foto dirender di akhir body
 *   8. Script JavaScript untuk modal foto (openPhotoPopup/closePhotoPopup) diinline
 *   9. layout_foot.php di-include untuk menutup tag HTML
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - maintenance_staff           : Mengambil role staff berdasarkan Staff_ID dari sesi
 *   - staff_dispenser_assignment  : Menghitung dan mengambil tugas aktif staff
 *   - water_report                : Mengambil laporan pending (unclaimed) dan statistik global
 *   - refill_logs                 : Menghitung refill selesai milik staff dan riwayat refill admin
 *   - dispenser                   : JOIN untuk mendapatkan Kode_Dispenser pada setiap laporan/tugas
 *   - lokasi                      : JOIN untuk mendapatkan Nama_Gedung dan Lantai dispenser
 *   - reporter                    : JOIN untuk mendapatkan nama dan NIM pelapor (water_report)
 *
 * VARIABEL PENTING:
 *   - $isStaff                  : Boolean; true jika role 'Staff', false jika Admin
 *   - $my_active_tasks          : Array tugas aktif milik staff yang login (mode Staff)
 *   - $unclaimed_reports        : Array laporan pending yang belum diambil siapapun (mode Staff)
 *   - $total_my_active_tasks    : Jumlah tugas aktif pribadi staff (mode Staff)
 *   - $total_pending_reports    : Total laporan berstatus Pending di seluruh sistem (mode Staff)
 *   - $total_my_completed_refills : Total refill yang telah diselesaikan staff ini (mode Staff)
 *   - $total_dispensers         : Total dispenser terdaftar (mode Admin)
 *   - $total_locations          : Total lokasi gedung terdaftar (mode Admin)
 *   - $total_staff              : Total staff maintenance aktif (mode Admin)
 *   - $reports_pending          : Jumlah laporan berstatus Pending (mode Admin)
 *   - $reports_proses           : Jumlah laporan berstatus Diproses (mode Admin)
 *   - $assignments_active       : Jumlah penugasan aktif (mode Admin)
 *   - $refills_today            : Jumlah refill yang dicatat hari ini (mode Admin)
 *   - $recentLaporan            : Array 5 laporan kendala terbaru (mode Admin)
 *   - $recentRefill             : Array 5 riwayat refill terbaru (mode Admin)
 *   - $activeAssignmentsList    : Array 5 penugasan aktif terbaru (mode Admin)
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php               : Koneksi database ($pdo) & helper functions (h, render_flash, get_foto_url)
 *   - layout_head.php      : Header HTML, sidebar navigasi, pengecekan sesi & role akses
 *   - layout_foot.php      : Penutup tag </main></div></body></html>
 *
 * AKSES:
 *   Hanya dapat diakses oleh pengguna yang sudah login (staff maupun admin).
 *   Pengguna yang belum login akan di-redirect ke login.php oleh layout_head.php.
 *
 * CATATAN PENGEMBANG:
 *   Halaman ini memiliki dua "mode" tampilan yang dikontrol oleh variabel $isStaff.
 *   Pastikan setiap perubahan pada struktur tabel yang di-query di sini juga direfleksikan
 *   pada file-file terkait (laporan/ambil.php, refill/create.php, dll.). Foto laporan
 *   dirender menggunakan get_foto_url() untuk menangani path relatif secara otomatis.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';


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
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.75rem;">
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
<div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start;">

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
                                    <a href="<?= h(get_foto_url($t['Foto_url'])) ?>" target="_blank">
                                        <img src="<?= h(get_foto_url($t['Foto_url'])) ?>" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;" class="hover:scale-105 transition-all">
                                    </a>
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
                                    <a href="<?= h(get_foto_url($ur['Foto_url'])) ?>" target="_blank">
                                        <img src="<?= h(get_foto_url($ur['Foto_url'])) ?>" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;" class="hover:scale-105 transition-all">
                                    </a>
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
                <a href="../refill/create.php" class="btn-primary" style="justify-content:center;padding:11px;">
                    <span class="mat-icon" style="font-size:18px;">recycling</span> Catat Refill Mandiri
                </a>
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
                            <a href="<?= h(get_foto_url($l['Foto_url'])) ?>" target="_blank">
                                <img src="<?= h(get_foto_url($l['Foto_url'])) ?>" alt="Foto" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;display:block;" class="hover:scale-105 transition-all">
                            </a>
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

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
