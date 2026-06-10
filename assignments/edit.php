<?php
/**
 * =============================================================================
 * edit.php — Halaman Form Edit / Perbarui Data Penugasan
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menangani proses pengeditan data penugasan staf maintenance yang
 *   sudah tersimpan dalam database. Halaman ini menampilkan form yang sudah
 *   terisi dengan data lama dan memungkinkan admin mengubah staf yang bertugas,
 *   dispenser target, laporan kendala yang dikaitkan, maupun status penugasan.
 *   Perubahan status penugasan secara otomatis memicu pembaruan status water
 *   report terkait agar data di seluruh sistem tetap sinkron dan konsisten.
 *
 * FUNGSI UTAMA:
 *   - Mengambil dan menampilkan data penugasan yang ada berdasarkan ID (GET parameter)
 *   - Menampilkan form edit yang pre-filled dengan data penugasan saat ini
 *   - Validasi input sisi server sebelum eksekusi UPDATE
 *   - Memperbarui record penugasan di tabel staff_dispenser_assignment
 *   - Sinkronisasi otomatis status water_report berdasarkan status penugasan baru:
 *       * On Progress → water_report: "Diproses"
 *       * Completed   → water_report: "Selesai" + Resolved_At = NOW()
 *       * Cancelled   → water_report: "Pending" + Resolved_At = NULL
 *   - Redirect ke index.php dengan flash message setelah sukses
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk koneksi dan helper functions
 *   2. ID penugasan dibaca dari $_GET['id'] dan divalidasi sebagai integer
 *   3. Data penugasan diambil dari database; bila tidak ada, redirect ke index
 *   4. Daftar staf, dispenser, dan water report (aktif + yang sudah terkait) diambil
 *   5. Jika request adalah GET: form ditampilkan dengan data penugasan yang ada
 *   6. Jika request adalah POST:
 *      a. Nilai $_POST dibaca dan disanitasi
 *      b. Validasi field wajib dan nilai status
 *      c. Bila error: form dirender ulang dengan pesan error dan nilai baru
 *      d. Bila valid: transaksi dimulai, UPDATE staff_dispenser_assignment dijalankan
 *      e. Bila ada water_report_id: UPDATE status water_report sesuai logika sinkronisasi
 *      f. Commit transaksi, set flash sukses, redirect ke index.php
 *      g. Bila exception: rollback dan tampilkan pesan error
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - staff_dispenser_assignment : Data penugasan yang akan di-UPDATE
 *   - maintenance_staff          : Daftar staf untuk dropdown
 *   - dispenser                  : Daftar dispenser untuk dropdown
 *   - lokasi                     : Info gedung & lantai (JOIN dengan dispenser)
 *   - water_report               : Laporan terkait; statusnya diperbarui otomatis
 *   - reporter                   : Nama pelapor untuk label dropdown water report
 *
 * VARIABEL PENTING:
 *   - $id             : Assignment_ID yang dibaca dari $_GET['id']
 *   - $assignment     : Array data penugasan yang ada dari database
 *   - $staffList      : Array staf maintenance untuk dropdown pilihan
 *   - $dispensersList : Array dispenser + lokasi untuk dropdown pilihan
 *   - $reportsList    : Array water report untuk dropdown (aktif + yang sudah terkait)
 *   - $errors         : Array pesan validasi error
 *   - $old            : Array data yang digunakan untuk mengisi ulang nilai form
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML dan script JS penutup
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin. Pembaruan status penugasan berdampak langsung
 *   pada data water report yang tampil di dashboard.
 *
 * CATATAN PENGEMBANG:
 *   - Query reportsList menyertakan water report yang sudah terkait (meski berstatus
 *     selain Pending/Diproses) agar dropdown tidak kehilangan nilai yang sudah tersimpan
 *   - Gunakan intval() pada WaterReport_ID sebelum disertakan langsung di string SQL
 *     (perhatikan baris yang menggunakan concatenation, bukan parameter binding)
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INISIALISASI — Muat koneksi database dan helper functions
// ===================================================
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Penugasan';
$activeMenu = 'assignments';
define('ROOT', dirname(__DIR__));

// ===================================================
// AMBIL ID DARI URL — Baca ?id=... dari alamat halaman ini
// ===================================================
// Contoh URL: edit.php?id=7 → $id akan bernilai 7
$id = intval($_GET['id'] ?? 0); // intval() memastikan nilai berupa angka (bukan teks)

// ===================================================
// [SELECT] Cari data penugasan berdasarkan ID
// ===================================================
$stmt = $pdo->prepare("SELECT * FROM staff_dispenser_assignment WHERE Assignment_ID = :id");
$stmt->execute([':id' => $id]); // Jalankan dengan ID yang sudah divalidasi
$assignment = $stmt->fetch();   // Ambil 1 baris data penugasan

// Jika ID tidak ditemukan di database, hentikan proses dan redirect
if (!$assignment) {
    set_flash('error', 'Penugasan tidak ditemukan.'); // Simpan pesan error ke sesi
    header('Location: index.php');                    // Kirim balik ke halaman daftar
    exit;
}

// ===================================================
// [SELECT] Ambil data untuk dropdown form (staf, dispenser, laporan)
// ===================================================

// Daftar semua staf maintenance
$staffList = $pdo->query("SELECT Staff_ID, Nama FROM maintenance_staff ORDER BY Nama")->fetchAll();

// Daftar dispenser beserta info lokasi (JOIN dengan tabel lokasi)
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID   -- Gabungkan dispenser dengan lokasi gedung
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

// Daftar laporan air untuk dropdown:
// - Tampilkan laporan Pending/Diproses (masih aktif)
// - PLUS laporan yang sudah terkait dengan penugasan ini (meski statusnya berbeda)
//   supaya dropdown tidak kosong saat edit penugasan yang sudah ada laporannya
// intval($assignment['WaterReport_ID'] ?: 0) → pastikan angka (aman untuk dimasukkan ke SQL)
$reportsList = $pdo->query("
    SELECT wr.WaterReport_ID, wr.Kategori, rep.Nama AS nama_pelapor, d.Kode_Dispenser
    FROM water_report wr
    JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID   -- Ambil nama pelapor
    JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID   -- Ambil kode dispenser
    WHERE wr.Status IN ('Pending', 'Diproses') OR wr.WaterReport_ID = " . intval($assignment['WaterReport_ID'] ?: 0) . "
    -- OR: sertakan juga laporan yang sudah terkait meskipun statusnya bukan Pending/Diproses
    ORDER BY wr.Reported_At DESC
")->fetchAll();

// Array untuk menampung pesan error validasi
$errors = [];
// $old diisi dengan data dari database (untuk pre-fill form saat pertama kali dibuka)
$old    = $assignment;

// ===================================================
// PROSES FORM POST — Jalankan hanya saat form dikirim
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST; // Ganti $old dengan data yang baru dikirim dari form

    // Baca dan amankan nilai dari form
    $staff_id       = intval($_POST['staff_id'] ?? 0);
    $dispenser_id   = intval($_POST['dispenser_id'] ?? 0);
    // Jika water_report_id dikosongkan, jadikan null
    $water_report_id = !empty($_POST['water_report_id']) ? intval($_POST['water_report_id']) : null;
    $status         = $_POST['status'] ?? 'Pending';

    // ===================================================
    // VALIDASI INPUT — Cek apakah semua field wajib sudah diisi dengan benar
    // ===================================================
    if (!$staff_id) {
        $errors[] = 'Pilih staff maintenance.';
    }
    if (!$dispenser_id) {
        $errors[] = 'Pilih dispenser.';
    }
    // Pastikan status yang dikirim adalah salah satu nilai yang valid
    if (!in_array($status, ['Pending', 'On Progress', 'Completed', 'Cancelled'])) {
        $errors[] = 'Status tidak valid.';
    }

    // Hanya lanjut jika tidak ada error
    if (empty($errors)) {
        try {
            // ===================================================
            // [TRANSAKSI] Mulai transaksi database
            // ===================================================
            // KENAPA PERLU TRANSAKSI?
            // Kita UPDATE dua tabel sekaligus: penugasan + laporan air.
            // Jika salah satu gagal, kita TIDAK mau data setengah berubah.
            // Transaksi menjamin: semua berhasil ATAU semua dibatalkan.
            $pdo->beginTransaction(); // Mulai transaksi — belum ada perubahan yang tersimpan

            // ===================================================
            // [UPDATE] Perbarui data penugasan di database
            // ===================================================
            $stmt = $pdo->prepare("
                UPDATE staff_dispenser_assignment 
                SET Staff_ID = :staff_id, Dispenser_ID = :dispenser_id, WaterReport_ID = :water_report_id, Status = :status
                WHERE Assignment_ID = :id
            ");
            $stmt->execute([
                ':staff_id'        => $staff_id,
                ':dispenser_id'    => $dispenser_id,
                ':water_report_id' => $water_report_id,
                ':status'          => $status,
                ':id'              => $id, // ID penugasan yang sedang diedit
            ]);

            // ===================================================
            // [UPDATE] Sinkronisasi status laporan air berdasarkan status penugasan baru
            // ===================================================
            // Hanya jalankan jika penugasan ini terkait dengan laporan air
            if ($water_report_id) {
                if ($status === 'Completed') {
                    // Penugasan selesai → laporan juga ditandai Selesai + catat waktu selesai
                    $pdo->prepare("UPDATE water_report SET Status = 'Selesai', Resolved_At = NOW() WHERE WaterReport_ID = :wr_id")
                        ->execute([':wr_id' => $water_report_id]);
                } elseif ($status === 'On Progress') {
                    // Penugasan sedang dikerjakan → laporan ditandai Diproses
                    $pdo->prepare("UPDATE water_report SET Status = 'Diproses', Resolved_At = NULL WHERE WaterReport_ID = :wr_id")
                        ->execute([':wr_id' => $water_report_id]);
                    // Resolved_At = NULL → hapus waktu selesai karena belum selesai
                } elseif ($status === 'Cancelled') {
                    // Penugasan dibatalkan → laporan dikembalikan ke Pending
                    $pdo->prepare("UPDATE water_report SET Status = 'Pending', Resolved_At = NULL WHERE WaterReport_ID = :wr_id")
                        ->execute([':wr_id' => $water_report_id]);
                }
            }

            $pdo->commit(); // Simpan semua perubahan ke database secara permanen
            set_flash('success', 'Penugasan berhasil diperbarui!');
            // Kirim pengguna ke halaman daftar penugasan setelah berhasil
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack(); // Batalkan semua perubahan jika ada error database
            $errors[] = 'Gagal memperbarui penugasan: ' . $e->getMessage();
        }
    }
}

// ===================================================
// TAMPILKAN HEADER HALAMAN — Muat template header & sidebar navigasi
// ===================================================
include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Edit Penugasan</div>
    </div>
</div>

<?php if ($errors): ?>
<!-- Tampilkan semua pesan error jika ada (warna merah) -->
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?>
        <div style="color:#dc2626;font-size:.875rem;display:flex;align-items:center;gap:6px;">
            <span class="mat-icon" style="font-size:16px">error</span> <?= h($e) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:680px;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <!-- Dropdown Staf: pre-filled dengan data lama dari $old -->
        <div class="form-group">
            <label class="form-label">Tugaskan Kepada Staff <span style="color:#ef4444">*</span></label>
            <select name="staff_id" class="form-select" required>
                <option value="">— Pilih Staff Maintenance —</option>
                <?php foreach ($staffList as $s): ?>
                <!-- Cek dua kemungkinan key: 'Staff_ID' (dari DB) atau 'staff_id' (dari $_POST) -->
                <option value="<?= $s['Staff_ID'] ?>" <?= (($old['Staff_ID'] ?? ($old['staff_id'] ?? '')) == $s['Staff_ID']) ? 'selected' : '' ?>>
                    <?= h($s['Nama']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dropdown Dispenser: pre-filled dengan data lama -->
        <div class="form-group">
            <label class="form-label">Dispenser Target <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <option value="">— Pilih Dispenser —</option>
                <?php foreach ($dispensersList as $d): ?>
                <option value="<?= $d['Dispenser_ID'] ?>" <?= (($old['Dispenser_ID'] ?? ($old['dispenser_id'] ?? '')) == $d['Dispenser_ID']) ? 'selected' : '' ?>>
                    <?= h($d['Nama_Gedung']) ?> Lt. <?= h($d['Lantai']) ?> - <?= h($d['Kode_Dispenser']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dropdown Laporan: opsional, pre-filled dengan laporan yang sudah dikaitkan -->
        <div class="form-group">
            <label class="form-label">Hubungkan dengan Laporan Masalah (Opsional)</label>
            <select name="water_report_id" class="form-select">
                <option value="">— Routine Check / Mandiri (Tidak Terikat Laporan) —</option>
                <?php foreach ($reportsList as $rep): ?>
                <!-- Tandai 'selected' jika laporan ini yang sebelumnya terkait -->
                <option value="<?= $rep['WaterReport_ID'] ?>" <?= (($old['WaterReport_ID'] ?? ($old['water_report_id'] ?? '')) == $rep['WaterReport_ID']) ? 'selected' : '' ?>>
                    [Laporan #<?= $rep['WaterReport_ID'] ?>] Pelapor: <?= h($rep['nama_pelapor']) ?> - <?= h($rep['Kategori']) ?> (Dispenser: <?= h($rep['Kode_Dispenser']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dropdown Status: pre-filled dengan status penugasan saat ini -->
        <div class="form-group">
            <label class="form-label">Status Penugasan <span style="color:#ef4444">*</span></label>
            <select name="status" class="form-select" required>
                <?php foreach (['Pending', 'On Progress', 'Completed', 'Cancelled'] as $st): ?>
                <!-- Cek dua kemungkinan key: 'Status' (dari DB) atau 'status' (dari $_POST) -->
                <option value="<?= $st ?>" <?= (($old['Status'] ?? ($old['status'] ?? 'Pending')) === $st) ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Penugasan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a><!-- Kembali tanpa menyimpan perubahan -->
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
