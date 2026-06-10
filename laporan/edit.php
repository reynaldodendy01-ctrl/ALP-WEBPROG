<?php
/**
 * =============================================================================
 * edit.php — Halaman Form Edit Laporan Kendala (Khusus Super Admin)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menyediakan halaman formulir bagi Super Admin untuk mengubah atau
 *   mengoreksi data dari suatu laporan kendala dispenser air yang sudah ada di sistem.
 *   Halaman ini hanya dapat diakses oleh Super Admin; pengguna dengan peran Staff
 *   akan secara otomatis diredirect dengan pesan error akses ditolak. Form ini
 *   mendukung perubahan pada semua field laporan termasuk penggantian foto lampiran,
 *   perubahan status, pelapor, dispenser, kategori, dan deskripsi kendala.
 *   Ketika status diubah menjadi 'Selesai', timestamp Resolved_At akan diisi otomatis.
 *
 * FUNGSI UTAMA:
 *   - Memastikan otentikasi sesi (redirect ke login.php jika belum login).
 *   - Memblokir akses pengguna dengan peran 'Staff' (hanya Super Admin yang boleh).
 *   - Mengambil dan menampilkan data laporan yang ada berdasarkan ID dari parameter GET.
 *   - Menampilkan form pre-filled dengan data laporan yang akan diedit.
 *   - Memvalidasi semua input wajib (pelapor, dispenser, kategori, status).
 *   - Menangani upload foto pengganti dengan validasi ekstensi & ukuran (maks 5MB);
 *     file foto lama dihapus secara otomatis saat foto baru berhasil diupload.
 *   - Mengupdate record laporan di tabel water_report menggunakan prepared statement.
 *   - Mengisi kolom Resolved_At secara otomatis jika status diubah menjadi 'Selesai'.
 *   - Menampilkan panel foto lampiran yang sudah ada dan metadata laporan (waktu dibuat & diselesaikan).
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include; sesi diperiksa untuk autentikasi dan otorisasi peran.
 *   2. ID laporan diambil dari $_GET['id'] dan data laporan di-fetch dari database.
 *   3. Jika laporan tidak ditemukan, flash error diset dan redirect ke index.php.
 *   4. Daftar pelapor dan dispenser diambil untuk mengisi dropdown form.
 *   5. Jika request adalah POST, input baru diambil dan divalidasi.
 *   6. Jika ada foto baru, file lama dihapus dan foto baru diupload ke /uploads/.
 *   7. Status 'Selesai' memicu pengisian Resolved_At = NOW(); status lain menghapus Resolved_At.
 *   8. Jika validasi lolos, UPDATE dijalankan pada tabel water_report.
 *   9. Flash message sukses diset dan pengguna diredirect ke index.php.
 *  10. Jika ada error, form dirender ulang dengan nilai lama dan pesan error.
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - water_report  : Tabel yang di-SELECT (untuk fetch data lama) dan di-UPDATE (simpan perubahan).
 *   - reporter      : Di-SELECT untuk mengisi dropdown pilihan pelapor.
 *   - dispenser     : Di-SELECT (JOIN dengan lokasi) untuk mengisi dropdown pilihan dispenser.
 *   - lokasi        : Di-JOIN dengan dispenser untuk menampilkan nama gedung dan lantai.
 *
 * VARIABEL PENTING:
 *   - $id           : Integer ID laporan yang sedang diedit (dari $_GET['id']).
 *   - $report       : Array asosiatif data laporan lama yang diambil dari database.
 *   - $old          : Array nilai form; diisi dari $report saat GET, dari $_POST saat validasi gagal.
 *   - $errors       : Array pesan error validasi yang dikumpulkan selama proses validasi.
 *   - $foto_url     : String path foto; dipertahankan dari nilai lama atau diperbarui jika ada upload baru.
 *   - $resolved_at  : Timestamp penyelesaian; diisi NOW() jika status berubah ke 'Selesai', null jika tidak.
 *   - $reportersList: Array data semua pelapor untuk opsi dropdown form.
 *   - $dispensersList: Array data semua dispenser (dengan info lokasi) untuk opsi dropdown form.
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php          : Koneksi database PDO & helper functions (h(), set_flash(), get_foto_url()).
 *   - layout_head.php : Header HTML, sidebar navigasi, dan pembuka konten utama.
 *   - layout_foot.php : Footer HTML dan script penutup halaman.
 *
 * AKSES:
 *   Hanya Super Admin yang sudah login. Pengguna dengan peran 'Staff' akan
 *   diredirect ke index.php dengan pesan error "Akses ditolak".
 *
 * CATATAN PENGEMBANG:
 *   - File foto lama dihapus menggunakan @unlink() (dengan @ untuk menekan error jika file
 *     tidak ditemukan) sebelum menyimpan foto baru.
 *   - Kolom Resolved_At di-set ke null jika status dikembalikan dari 'Selesai' ke status lain,
 *     memastikan konsistensi data waktu penyelesaian.
 *   - Layout dua kolom (2fr 1fr) digunakan: kolom kiri untuk form edit, kolom kanan untuk
 *     preview foto dan metadata laporan.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce authentication
if (!isset($_SESSION['staff_logged_in']) || $_SESSION['staff_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Enforce admin-only access for editing reports
if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    set_flash('error', 'Akses ditolak: Hanya Super Admin yang dapat mengubah data laporan secara langsung.');
    header('Location: index.php');
    exit;
}

$pageTitle  = 'Edit Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM water_report WHERE WaterReport_ID = :id");
$stmt->execute([':id' => $id]);
$report = $stmt->fetch();

if (!$report) {
    set_flash('error', 'Laporan tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$reportersList = $pdo->query("SELECT Reporter_ID, Nama, Nim FROM reporter ORDER BY Nama")->fetchAll();
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

$errors = [];
$old    = $report;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $reporter_id      = intval($_POST['reporter_id'] ?? 0);
    $dispenser_id     = intval($_POST['dispenser_id'] ?? 0);
    $kategori         = $_POST['kategori'] ?? '';
    $status           = $_POST['status'] ?? 'Pending';
    $deskripsi_report = trim($_POST['deskripsi_report'] ?? '');

    // Validation
    if (!$reporter_id) {
        $errors[] = 'Pilih pelapor.';
    }
    if (!$dispenser_id) {
        $errors[] = 'Pilih dispenser.';
    }
    if (!in_array($kategori, ['Galon Kosong', 'Dispenser Rusak', 'Kebocoran', 'Distribusi Tidak Merata', 'Lainnya'])) {
        $errors[] = 'Kategori masalah tidak valid.';
    }
    if (!in_array($status, ['Pending', 'Diproses', 'Selesai', 'Ditolak'])) {
        $errors[] = 'Status tidak valid.';
    }
    if (!$deskripsi_report) {
        $errors[] = 'Deskripsi kendala wajib diisi.';
    }

    $foto_url = $report['Foto_url'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileSize = $_FILES['foto']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "Ekstensi file tidak valid. Diperbolehkan: JPG, JPEG, PNG, GIF.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "Ukuran file terlalu besar. Maksimum 5MB.";
        } else {
            $uploadFileDir = ROOT . '/uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $newFileName = md5(uniqid(time(), true)) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Delete old local file if exists
                if ($report['Foto_url'] && file_exists(ROOT . '/' . $report['Foto_url'])) {
                    @unlink(ROOT . '/' . $report['Foto_url']);
                }
                $foto_url = 'uploads/' . $newFileName;
            } else {
                $errors[] = "Gagal mengunggah foto. Silakan coba lagi.";
            }
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Terjadi kesalahan pada file upload: Kode " . $_FILES['foto']['error'];
    }

    if (empty($errors)) {
        try {
            // Determine resolved_at timestamp
            $resolved_at = $report['Resolved_At'];
            if ($status === 'Selesai' && $report['Status'] !== 'Selesai') {
                $resolved_at = date('Y-m-d H:i:s');
            } elseif ($status !== 'Selesai') {
                $resolved_at = null;
            }

            $stmt = $pdo->prepare("
                UPDATE water_report 
                SET Reporter_ID = :reporter_id, Dispenser_ID = :dispenser_id, Kategori = :kategori, 
                    Status = :status, Deskripsi_Report = :deskripsi_report, Foto_url = :foto_url, Resolved_At = :resolved_at
                WHERE WaterReport_ID = :id
            ");
            $stmt->execute([
                ':reporter_id'      => $reporter_id,
                ':dispenser_id'     => $dispenser_id,
                ':kategori'         => $kategori,
                ':status'           => $status,
                ':deskripsi_report' => $deskripsi_report,
                ':foto_url'         => $foto_url,
                ':resolved_at'      => $resolved_at,
                ':id'               => $id,
            ]);

            set_flash('success', 'Laporan kendala berhasil diperbarui!');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui laporan: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Edit Laporan Kendala</div>
    </div>
</div>

<?php if ($errors): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?>
        <div style="color:#dc2626;font-size:.875rem;display:flex;align-items:center;gap:6px;">
            <span class="mat-icon" style="font-size:16px">error</span> <?= h($e) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns: 2fr 1fr;gap:1.5rem;align-items:start;">
<div class="card" style="padding:32px;">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Pelapor <span style="color:#ef4444">*</span></label>
            <select name="reporter_id" class="form-select" required>
                <option value="">— Pilih Pelapor —</option>
                <?php foreach ($reportersList as $rep): ?>
                <option value="<?= $rep['Reporter_ID'] ?>" <?= (($old['Reporter_ID'] ?? ($old['reporter_id'] ?? '')) == $rep['Reporter_ID']) ? 'selected' : '' ?>>
                    <?= h($rep['Nama']) ?> (NIM: <?= h($rep['Nim']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Dispenser Bermasalah <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <option value="">— Pilih Dispenser —</option>
                <?php foreach ($dispensersList as $d): ?>
                <option value="<?= $d['Dispenser_ID'] ?>" <?= (($old['Dispenser_ID'] ?? ($old['dispenser_id'] ?? '')) == $d['Dispenser_ID']) ? 'selected' : '' ?>>
                    <?= h($d['Nama_Gedung']) ?> Lt. <?= h($d['Lantai']) ?> - <?= h($d['Kode_Dispenser']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Kategori Masalah <span style="color:#ef4444">*</span></label>
                <select name="kategori" class="form-select" required>
                    <option value="">— Pilih Masalah —</option>
                    <?php foreach (['Galon Kosong', 'Dispenser Rusak', 'Kebocoran', 'Distribusi Tidak Merata', 'Lainnya'] as $kat): ?>
                    <option value="<?= $kat ?>" <?= (($old['Kategori'] ?? ($old['kategori'] ?? '')) === $kat) ? 'selected' : '' ?>><?= $kat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444">*</span></label>
                <select name="status" class="form-select" required>
                    <?php foreach (['Pending', 'Diproses', 'Selesai', 'Ditolak'] as $st): ?>
                    <option value="<?= $st ?>" <?= (($old['Status'] ?? ($old['status'] ?? 'Pending')) === $st) ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Upload Foto Pendukung Baru (Opsional)</label>
            <input type="file" name="foto" accept="image/*" class="form-input">
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi Kendala <span style="color:#ef4444">*</span></label>
            <textarea name="deskripsi_report" class="form-textarea" placeholder="Tulis rincian kendala air dispenser di sini…" rows="4" required><?= h($old['Deskripsi_Report'] ?? ($old['deskripsi_report'] ?? '')) ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Perubahan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>

<div style="display:flex;flex-direction:column;gap:1.5rem;">
    <!-- Photo Preview -->
    <?php if (!empty($report['Foto_url'])): ?>
    <div class="card" style="padding:20px;">
        <div style="font-weight:700;color:#0b1f3a;margin-bottom:12px;">Foto Lampiran</div>
        <img src="<?= h(get_foto_url($report['Foto_url'])) ?>" alt="Foto kendala" style="width:100%;height:auto;border-radius:10px;border:1px solid #e5e7eb;max-height:220px;object-fit:cover;">
    </div>
    <?php endif; ?>

    <!-- Info Card -->
    <div class="card" style="padding:20px;">
        <div style="font-weight:700;color:#0b1f3a;margin-bottom:12px;">Metadata Laporan</div>
        <div style="font-size:.82rem;color:#6b7280;line-height:1.6;">
            <div>Reported At: <b><?= date('d M Y, H:i', strtotime($report['Reported_At'])) ?> WIB</b></div>
            <?php if ($report['Resolved_At']): ?>
                <div class="text-emerald-700">Resolved At: <b><?= date('d M Y, H:i', strtotime($report['Resolved_At'])) ?> WIB</b></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
