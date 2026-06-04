<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Buat Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

$reportersList = $pdo->query("SELECT Reporter_ID, Nama, Nim FROM reporter ORDER BY Nama")->fetchAll();
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

$errors = [];
$old    = [];

// Support passing dispenser_id via GET
if (isset($_GET['dispenser_id'])) {
    $old['dispenser_id'] = intval($_GET['dispenser_id']);
}

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

    $foto_url = null;
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
            $stmt = $pdo->prepare("
                INSERT INTO water_report (Reporter_ID, Dispenser_ID, Kategori, Status, Deskripsi_Report, Foto_url, Reported_At)
                VALUES (:reporter_id, :dispenser_id, :kategori, :status, :deskripsi_report, :foto_url, NOW())
            ");
            $stmt->execute([
                ':reporter_id'      => $reporter_id,
                ':dispenser_id'     => $dispenser_id,
                ':kategori'         => $kategori,
                ':status'           => $status,
                ':deskripsi_report' => $deskripsi_report,
                ':foto_url'         => $foto_url,
            ]);

            set_flash('success', 'Laporan kendala berhasil dibuat!');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan laporan: ' . $e->getMessage();
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
        <div class="page-title">Buat Laporan Kendala Baru</div>
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

<div style="max-width:680px;">
<div class="card" style="padding:32px;">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Pelapor <span style="color:#ef4444">*</span></label>
            <select name="reporter_id" class="form-select" required>
                <option value="">— Pilih Pelapor —</option>
                <?php foreach ($reportersList as $rep): ?>
                <option value="<?= $rep['Reporter_ID'] ?>" <?= ($old['reporter_id'] ?? '') == $rep['Reporter_ID'] ? 'selected' : '' ?>>
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
                <option value="<?= $d['Dispenser_ID'] ?>" <?= ($old['dispenser_id'] ?? '') == $d['Dispenser_ID'] ? 'selected' : '' ?>>
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
                    <option value="<?= $kat ?>" <?= ($old['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444">*</span></label>
                <select name="status" class="form-select" required>
                    <?php foreach (['Pending', 'Diproses', 'Selesai', 'Ditolak'] as $st): ?>
                    <option value="<?= $st ?>" <?= ($old['status'] ?? 'Pending') === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Upload Foto Pendukung (Opsional)</label>
            <input type="file" name="foto" accept="image/*" class="form-input">
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi Kendala <span style="color:#ef4444">*</span></label>
            <textarea name="deskripsi_report" class="form-textarea" placeholder="Tulis rincian kendala air dispenser di sini…" rows="4" required><?= h($old['deskripsi_report'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Laporan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
