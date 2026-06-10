<?php
// =========================================================================
// FILE: laporan/create.php
// FUNGSI: Menampilkan Form Buat Laporan Baru & Menyimpan Datanya (CREATE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Buat Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

// 3. MENGAMBIL DATA UNTUK MENU PILIHAN (DROPDOWN)
// Supaya admin tidak perlu mengetik nama pelapor/dispenser satu-satu secara manual.
$reportersList = $pdo->query("SELECT Reporter_ID, Nama, Nim FROM reporter ORDER BY Nama")->fetchAll();

// Disini kita JOIN tabel 'dispenser' dengan 'lokasi' supaya di menu pilihan
// kita bisa menampilkan nama gedungnya (bukan cuma kodenya doang yang bikin bingung).
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

$errors = [];
$old    = [];

// 4. MENANGKAP ID DISPENSER DARI URL (Fitur Pintasan)
// Jika di URL ada 'create.php?dispenser_id=5', maka langsung pilih dispenser no 5 di form.
if (isset($_GET['dispenser_id'])) {
    $old['dispenser_id'] = intval($_GET['dispenser_id']);
}

// 5. MENANGKAP DATA SAAT FORM DIKIRIM (TOMBOL SUBMIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $reporter_id      = intval($_POST['reporter_id'] ?? 0);
    $dispenser_id     = intval($_POST['dispenser_id'] ?? 0);
    $kategori         = $_POST['kategori'] ?? '';
    $status           = $_POST['status'] ?? 'Pending';
    $deskripsi_report = trim($_POST['deskripsi_report'] ?? '');

    // --- PROSES VALIDASI ---
    if (!$reporter_id) {
        $errors[] = 'Pilih pelapor.';
    }
    if (!$dispenser_id) {
        $errors[] = 'Pilih dispenser.';
    }
    // Mencegah hacker ngarang nama kategori atau status sendiri
    if (!in_array($kategori, ['Galon Kosong', 'Dispenser Rusak / Bocor'])) {
        $errors[] = 'Kategori masalah tidak valid.';
    }
    if (!in_array($status, ['Pending', 'Diproses', 'Selesai', 'Ditolak'])) {
        $errors[] = 'Status tidak valid.';
    }

    // --- 6. PROSES UPLOAD FOTO (BAGIAN TERSULIT) ---
    $foto_url = null; // Awalnya anggap tidak ada foto
    
    // Cek: Apakah user mengupload foto? Dan apakah tidak ada error saat upload?
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name']; // Lokasi foto sementara di server
        $fileName = $_FILES['foto']['name']; // Nama asli foto ('gambar.jpg')
        $fileSize = $_FILES['foto']['size']; // Ukuran foto
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); // Mengambil tipe file ('jpg')

        // Validasi tipe file (Hanya boleh gambar)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "Ekstensi file tidak valid. Diperbolehkan: JPG, JPEG, PNG, GIF.";
        } 
        // Validasi ukuran (Maksimal 5 MB)
        elseif ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "Ukuran file terlalu besar. Maksimum 5MB.";
        } else {
            // Tentukan folder tempat foto akan disimpan permanen ('/uploads/')
            $uploadFileDir = ROOT . '/uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true); // Jika folder belum ada, buat foldernya!
            }
            
            // Mengubah nama foto menjadi nama unik/acak. 
            // Kenapa? Supaya kalau ada 2 orang upload foto bernama 'gambar.jpg', file lama tidak tertimpa.
            $newFileName = md5(uniqid(time(), true)) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            // Pindahkan file dari tempat sementara ke folder 'uploads/'
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $foto_url = 'uploads/' . $newFileName; // Simpan jalur file ini untuk dimasukkan ke MySQL
            } else {
                $errors[] = "Gagal mengunggah foto. Silakan coba lagi.";
            }
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Jika ada error upload selain "User tidak pilih file"
        $errors[] = "Terjadi kesalahan pada file upload: Kode " . $_FILES['foto']['error'];
    }

    // Jika semua lolos ($errors kosong), simpan!
    if (empty($errors)) {
        try {
            // ─── 7. CRUD (CREATE) - MENYIMPAN LAPORAN BARU KE MYSQL ───────────────────
            // Kita menyiapkan perintah INSERT INTO ke tabel 'water_report'.
            // Di sini kita menyimpan data foto ($foto_url) dan mengatur 'Reported_At' pakai fungsi NOW() dari MySQL.
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
                ':foto_url'         => $foto_url, // Bisa berisi link gambar, bisa juga NULL kalau tidak upload
            ]);

            set_flash('success', 'Laporan kendala berhasil dibuat!');
            header('Location: index.php'); // Lemparkan ke daftar laporan
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
                    <?php foreach (['Galon Kosong', 'Dispenser Rusak / Bocor'] as $kat): ?>
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
            <label class="form-label">Deskripsi Kendala (Opsional)</label>
            <textarea name="deskripsi_report" class="form-textarea" placeholder="Tulis rincian kendala air dispenser di sini…" rows="4"><?= h($old['deskripsi_report'] ?? '') ?></textarea>
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
