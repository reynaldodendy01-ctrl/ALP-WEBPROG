<?php
/**
 * =============================================================================
 * staff/index.php — Halaman Daftar & Manajemen Staff Maintenance
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan halaman utama modul Manajemen Staff yang menampilkan
 *   seluruh akun pengguna sistem CariGalon, mencakup staf maintenance maupun
 *   admin. Setiap baris dalam tabel menampilkan avatar inisial nama, data kontak
 *   (email, nomor telepon), area gedung yang ditangani, jumlah assignment aktif
 *   yang sedang berjalan, serta total keseluruhan penugasan yang pernah diterima.
 *   Halaman ini menjadi pusat kontrol akun dengan tombol aksi edit dan hapus.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan tabel daftar semua staff (maintenance_staff) beserta statistik assignment
 *   - Menghitung jumlah assignment aktif (status Pending atau On Progress) per staff via agregasi SQL
 *   - Menghitung total seluruh assignment (semua status) per staff
 *   - Menampilkan avatar inisial nama staff dengan warna gradient biru
 *   - Menampilkan badge area gedung tugas atau label "Semua" jika tidak spesifik
 *   - Tombol aksi Edit (menuju edit.php) dan Hapus (POST ke delete.php) per baris
 *   - Menampilkan pesan flash (sukses/error) hasil operasi CRUD sebelumnya
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk koneksi PDO dan helper functions
 *   2. Eksekusi query SELECT dengan LEFT JOIN ke staff_dispenser_assignment
 *      dan GROUP BY Staff_ID untuk menghitung total_assignments & active_assignments
 *   3. Fetch semua hasil ke array $staff, diurutkan berdasarkan nama (ORDER BY s.Nama)
 *   4. Render layout_head.php (header, sidebar, stylesheet)
 *   5. Tampilkan flash message jika ada dari operasi sebelumnya
 *   6. Render header halaman dengan jumlah staff terdaftar dan tombol "Tambah Staff"
 *   7. Render tabel responsif; jika $staff kosong tampilkan pesan empty-state
 *   8. Tutup halaman dengan layout_foot.php
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - maintenance_staff            : Data utama akun staff (nama, email, no_telp, role, gedung)
 *   - staff_dispenser_assignment   : Tabel relasi assignment; di-JOIN untuk menghitung statistik
 *
 * VARIABEL PENTING:
 *   - $staff                     : Array semua data staff beserta kolom agregasi statistik
 *   - $s['total_assignments']    : Total semua assignment (semua status) yang pernah dimiliki staff
 *   - $s['active_assignments']   : Jumlah assignment dengan status 'Pending' atau 'On Progress'
 *   - $s['Gedung']               : Area gedung tugas staff; NULL atau kosong berarti semua gedung
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php          : Koneksi database PDO & helper functions (h(), set_flash(), render_flash())
 *   - layout_head.php : Header HTML, sidebar navigasi, dan stylesheet utama
 *   - layout_foot.php : Footer HTML, script JavaScript penutup halaman
 *
 * AKSES:
 *   Hanya bisa diakses oleh Admin yang sudah login. Staff maintenance tidak
 *   seharusnya mengakses modul manajemen akun ini.
 *
 * CATATAN PENGEMBANG:
 *   Kalkulasi active_assignments menggunakan CASE WHEN di dalam SUM() sehingga
 *   lebih efisien daripada subquery terpisah. Jika status assignment bertambah
 *   di masa mendatang (misal: 'Paused'), pastikan kondisi CASE di query ini
 *   diperbarui agar hitungan aktif tetap akurat.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

$staff = $pdo->query("
    SELECT s.*, 
           COUNT(a.Assignment_ID) AS total_assignments,
           SUM(CASE WHEN a.Status = 'Pending' OR a.Status = 'On Progress' THEN 1 ELSE 0 END) AS active_assignments
    FROM maintenance_staff s
    LEFT JOIN staff_dispenser_assignment a ON a.Staff_ID = s.Staff_ID
    GROUP BY s.Staff_ID
    ORDER BY s.Nama
")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Staff Maintenance</div>
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
            <th>Email</th>
            <th>No. Telepon</th>
            <th>Assignment Aktif</th>
            <th>Total Penugasan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($staff)): ?>
        <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada staff terdaftar</td></tr>
    <?php else: foreach ($staff as $i => $s): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0058bc,#1a78e5);
                          display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0;">
                        <?= strtoupper(mb_substr($s['Nama'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600"><?= h($s['Nama']) ?></span>
                </div>
            </td>
            <td style="font-size:.875rem"><?= h($s['Email']) ?></td>
            <td style="font-size:.875rem"><?= h($s['No_Telp']) ?></td>
            <td>
                <span style="font-size:1rem;font-weight:800;color:#c2410c"><?= intval($s['active_assignments']) ?></span>
                <span style="font-size:.8rem;color:#9ca3af"> aktif</span>
            </td>
            <td>
                <span style="font-size:1rem;font-weight:800;color:#0058bc"><?= intval($s['total_assignments']) ?></span>
                <span style="font-size:.8rem;color:#9ca3af"> selesai/total</span>
            </td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="edit.php?id=<?= $s['Staff_ID'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus staff <?= h(addslashes($s['Nama'])) ?>?')">
                        <input type="hidden" name="id" value="<?= $s['Staff_ID'] ?>">
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
