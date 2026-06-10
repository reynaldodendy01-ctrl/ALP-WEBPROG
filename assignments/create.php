<?php
/**
 * =============================================================================
 * create.php — Halaman Form Pembuatan Penugasan Baru
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menyediakan halaman form untuk membuat penugasan (assignment) baru
 *   yang menghubungkan seorang staf maintenance dengan unit dispenser tertentu.
 *   Penugasan dapat bersifat rutin/mandiri atau dikaitkan secara langsung dengan
 *   laporan kendala (water report) yang masuk dari pengunjung kampus. Saat
 *   penugasan berhasil disimpan, status water report terkait akan diperbarui
 *   secara otomatis sesuai status awal yang dipilih menggunakan transaksi database.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan form input untuk memilih staf, dispenser, water report, dan status awal
 *   - Validasi input sisi server (staff_id, dispenser_id, status)
 *   - Menyimpan data penugasan baru ke tabel staff_dispenser_assignment
 *   - Memperbarui status water_report secara otomatis saat penugasan On Progress/Completed
 *   - Menampilkan pesan error yang informatif apabila validasi gagal
 *   - Redirect ke halaman index dengan flash message sukses setelah berhasil
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk mendapatkan koneksi $pdo dan helper functions
 *   2. Daftar staf maintenance, dispenser, dan water report aktif (Pending/Diproses)
 *      diambil dari database untuk mengisi dropdown pilihan pada form
 *   3. Jika request adalah GET, form kosong ditampilkan
 *   4. Jika request adalah POST:
 *      a. Nilai $_POST dibaca dan disanitasi
 *      b. Validasi: staff_id, dispenser_id wajib ada; status harus valid
 *      c. Bila ada error, form ditampilkan ulang dengan pesan error dan nilai lama
 *      d. Bila valid, transaksi database dimulai (beginTransaction)
 *      e. INSERT ke tabel staff_dispenser_assignment
 *      f. Jika ada water_report_id: UPDATE status water_report sesuai status penugasan
 *      g. Commit transaksi, set flash sukses, redirect ke index.php
 *      h. Bila exception: rollback dan tampilkan pesan error
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - staff_dispenser_assignment : Tabel utama untuk menyimpan data penugasan baru
 *   - maintenance_staff          : Diambil untuk daftar dropdown pilihan staf
 *   - dispenser                  : Diambil untuk daftar dropdown pilihan dispenser
 *   - lokasi                     : Di-JOIN dengan dispenser untuk info gedung & lantai
 *   - water_report               : Diambil laporan aktif; diperbarui statusnya bila terkait
 *   - reporter                   : Di-JOIN dengan water_report untuk nama pelapor di dropdown
 *
 * VARIABEL PENTING:
 *   - $staffList      : Array daftar staf maintenance untuk dropdown
 *   - $dispensersList : Array daftar dispenser beserta info lokasi untuk dropdown
 *   - $reportsList    : Array daftar water report berstatus Pending/Diproses untuk dropdown
 *   - $errors         : Array pesan error validasi yang dikumpulkan sebelum INSERT
 *   - $old            : Array nilai $_POST yang dipertahankan saat form dirender ulang
 *   - $staff_id       : ID staf yang dipilih (integer, hasil intval)
 *   - $dispenser_id   : ID dispenser yang dipilih (integer, hasil intval)
 *   - $water_report_id: ID water report yang dikaitkan (nullable integer)
 *   - $status         : Status awal penugasan (Pending/On Progress/Completed/Cancelled)
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML dan script JS penutup
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin. Staf maintenance tidak memiliki hak untuk
 *   membuat penugasan secara langsung.
 *
 * CATATAN PENGEMBANG:
 *   - Pembaruan status water_report bersifat kondisional dan berjalan dalam transaksi
 *     yang sama dengan INSERT penugasan, sehingga konsistensi data terjamin
 *   - WaterReport_ID bersifat opsional; jika tidak dipilih, penugasan bersifat "Mandiri"
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

// Judul halaman dan menu aktif di sidebar
$pageTitle  = 'Tambah Penugasan';
$activeMenu = 'assignments';
define('ROOT', dirname(__DIR__)); // Path folder root proyek

// ===================================================
// [SELECT] Ambil data untuk mengisi pilihan dropdown di form
// ===================================================

// Ambil semua nama staf maintenance (diurutkan A-Z)
$staffList = $pdo->query("SELECT Staff_ID, Nama FROM maintenance_staff ORDER BY Nama")->fetchAll();

// [SELECT] Ambil semua dispenser beserta info gedung dan lantainya
// JOIN lokasi → supaya bisa tampilkan "Main Building Lt. 2 - DSP-001" di dropdown
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID   -- Gabungkan tabel dispenser dengan lokasi
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

// [SELECT] Ambil laporan air yang masih aktif (Pending atau Diproses) untuk pilihan opsional
// JOIN reporter → untuk tampilkan nama pelapor di dropdown
// JOIN dispenser → untuk tampilkan kode dispenser di dropdown
// Hanya laporan yang belum selesai yang muncul di sini
$reportsList = $pdo->query("
    SELECT wr.WaterReport_ID, wr.Kategori, rep.Nama AS nama_pelapor, d.Kode_Dispenser
    FROM water_report wr
    JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID   -- Ambil nama pelapor
    JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID   -- Ambil kode dispenser
    WHERE wr.Status IN ('Pending', 'Diproses')              -- Hanya laporan yang belum selesai
    ORDER BY wr.Reported_At DESC                            -- Laporan terbaru di atas
")->fetchAll();

// Array untuk menampung pesan error validasi
$errors = [];
// Array untuk menyimpan nilai form lama (dipakai saat ada error supaya pengguna tidak isi ulang)
$old    = [];

// ===================================================
// PROSES FORM POST — Jalankan hanya saat form dikirim
// ===================================================
// $_SERVER['REQUEST_METHOD'] berisi 'GET' saat halaman dibuka biasa, 'POST' saat form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST; // Simpan semua nilai yang dikirim untuk ditampilkan ulang di form

    // Baca dan amankan nilai dari form
    $staff_id       = intval($_POST['staff_id'] ?? 0);      // intval() = konversi ke angka bulat
    $dispenser_id   = intval($_POST['dispenser_id'] ?? 0);
    // Kalau water_report_id dikosongkan (opsional), jadikan null bukan 0
    $water_report_id = !empty($_POST['water_report_id']) ? intval($_POST['water_report_id']) : null;
    $status         = $_POST['status'] ?? 'Pending';         // Default status: Pending

    // ===================================================
    // VALIDASI INPUT — Pastikan data yang dikirim sudah benar
    // ===================================================
    if (!$staff_id) { // Jika staff_id = 0 berarti belum dipilih
        $errors[] = 'Pilih staff maintenance.';
    }
    if (!$dispenser_id) { // Jika dispenser_id = 0 berarti belum dipilih
        $errors[] = 'Pilih dispenser.';
    }
    // Status harus salah satu dari nilai yang valid (mencegah nilai dimanipulasi)
    if (!in_array($status, ['Pending', 'On Progress', 'Completed', 'Cancelled'])) {
        $errors[] = 'Status tidak valid.';
    }

    // Hanya lanjut ke database jika tidak ada error validasi
    if (empty($errors)) {
        try {
            // ===================================================
            // [TRANSAKSI] Mulai transaksi database
            // ===================================================
            // KENAPA PERLU TRANSAKSI?
            // Kita melakukan DUA operasi: INSERT penugasan + UPDATE laporan.
            // Kalau operasi pertama berhasil tapi kedua gagal, data jadi tidak konsisten
            // (penugasan ada tapi status laporan tidak berubah).
            // Dengan transaksi: jika salah satu gagal, SEMUA dibatalkan (rollback).
            $pdo->beginTransaction(); // Mulai transaksi — perubahan belum disimpan ke DB

            // ===================================================
            // [INSERT] Simpan penugasan baru ke tabel staff_dispenser_assignment
            // ===================================================
            $stmt = $pdo->prepare("
                INSERT INTO staff_dispenser_assignment (Staff_ID, Dispenser_ID, WaterReport_ID, Status, Created_At)
                VALUES (:staff_id, :dispenser_id, :water_report_id, :status, NOW())
                -- NOW() = waktu saat ini otomatis diambil dari server database
            ");
            $stmt->execute([
                ':staff_id'        => $staff_id,
                ':dispenser_id'    => $dispenser_id,
                ':water_report_id' => $water_report_id, // null kalau tidak dipilih
                ':status'          => $status,
            ]);

            // ===================================================
            // [UPDATE] Sinkronisasi status laporan air sesuai status penugasan
            // ===================================================
            // Jika penugasan dikaitkan dengan laporan DAN statusnya "On Progress"
            // → ubah status laporan dari "Pending" menjadi "Diproses"
            if ($water_report_id && $status === 'On Progress') {
                $pdo->prepare("UPDATE water_report SET Status = 'Diproses' WHERE WaterReport_ID = :wr_id")
                    ->execute([':wr_id' => $water_report_id]);
            // Jika statusnya "Completed" → tandai laporan sebagai selesai + catat waktu selesai
            } elseif ($water_report_id && $status === 'Completed') {
                $pdo->prepare("UPDATE water_report SET Status = 'Selesai', Resolved_At = NOW() WHERE WaterReport_ID = :wr_id")
                    ->execute([':wr_id' => $water_report_id]);
                // Resolved_At = NOW() → catat waktu laporan selesai diselesaikan
            }

            $pdo->commit(); // Simpan semua perubahan ke database secara permanen
            set_flash('success', 'Penugasan berhasil dibuat!'); // Simpan pesan sukses ke sesi
            // Kirim pengguna ke halaman daftar penugasan setelah berhasil
            header('Location: index.php');
            exit; // Hentikan eksekusi PHP agar tidak lanjut ke bawah
        } catch (PDOException $e) {
            // Kalau ada error database, batalkan SEMUA perubahan (rollback)
            $pdo->rollBack(); // Kembalikan database ke kondisi sebelum transaksi dimulai
            $errors[] = 'Gagal menyimpan penugasan: ' . $e->getMessage();
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
        <div class="page-title">Tambah Penugasan Baru</div>
    </div>
</div>

<?php if ($errors): ?>
<!-- Tampilkan semua pesan error jika ada (warna merah) -->
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?>
        <div style="color:#dc2626;font-size:.875rem;display:flex;align-items:center;gap:6px;">
            <span class="mat-icon" style="font-size:16px">error</span> <?= h($e) ?><!-- h() mencegah XSS pada pesan error -->
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:680px;">
<div class="card" style="padding:32px;">
    <form method="POST"><!-- method="POST" → data form dikirim via HTTP POST, bukan lewat URL -->
        <!-- Dropdown: Pilih Staf yang ditugaskan -->
        <div class="form-group">
            <label class="form-label">Tugaskan Kepada Staff <span style="color:#ef4444">*</span></label>
            <select name="staff_id" class="form-select" required>
                <option value="">— Pilih Staff Maintenance —</option>
                <?php foreach ($staffList as $s): ?>
                <!-- Tampilkan setiap staf sebagai pilihan; tandai 'selected' jika dipilih sebelumnya -->
                <option value="<?= $s['Staff_ID'] ?>" <?= ($old['staff_id'] ?? '') == $s['Staff_ID'] ? 'selected' : '' ?>>
                    <?= h($s['Nama']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dropdown: Pilih Dispenser target -->
        <div class="form-group">
            <label class="form-label">Dispenser Target <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <option value="">— Pilih Dispenser —</option>
                <?php foreach ($dispensersList as $d): ?>
                <option value="<?= $d['Dispenser_ID'] ?>" <?= ($old['dispenser_id'] ?? '') == $d['Dispenser_ID'] ? 'selected' : '' ?>>
                    <?= h($d['Nama_Gedung']) ?> Lt. <?= h($d['Lantai']) ?> - <?= h($d['Kode_Dispenser']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dropdown: Hubungkan dengan Laporan (opsional) -->
        <div class="form-group">
            <label class="form-label">Hubungkan dengan Laporan Masalah (Opsional)</label>
            <select name="water_report_id" class="form-select">
                <option value="">— Routine Check / Mandiri (Tidak Terikat Laporan) —</option>
                <?php foreach ($reportsList as $rep): ?>
                <option value="<?= $rep['WaterReport_ID'] ?>" <?= ($old['water_report_id'] ?? '') == $rep['WaterReport_ID'] ? 'selected' : '' ?>>
                    [Laporan #<?= $rep['WaterReport_ID'] ?>] Pelapor: <?= h($rep['nama_pelapor']) ?> - <?= h($rep['Kategori']) ?> (Dispenser: <?= h($rep['Kode_Dispenser']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size:.78rem;color:#9ca3af;margin-top:4px;">Memilih laporan akan membantu melacak penyelesaian kendala di dashboard admin.</p>
        </div>

        <!-- Dropdown: Status Awal Penugasan -->
        <div class="form-group">
            <label class="form-label">Status Awal Penugasan <span style="color:#ef4444">*</span></label>
            <select name="status" class="form-select" required>
                <?php foreach (['Pending', 'On Progress', 'Completed', 'Cancelled'] as $st): ?>
                <!-- ($old['status'] ?? 'Pending') → default 'Pending' kalau belum pernah dikirim -->
                <option value="<?= $st ?>" <?= ($old['status'] ?? 'Pending') === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Buat Penugasan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a><!-- Kembali tanpa menyimpan -->
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
