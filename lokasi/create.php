<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Tambah Lokasi';
$activeMenu = 'lokasi';
define('ROOT', dirname(__DIR__));

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $nama_gedung = trim($_POST['nama_gedung'] ?? '');
    $lantai      = intval($_POST['lantai'] ?? 0);
    $keterangan  = trim($_POST['keterangan'] ?? '');

    if (!$nama_gedung) {
        $errors[] = 'Nama gedung wajib diisi.';
    }
    if ($lantai < -5 || $lantai > 100) {
        $errors[] = 'Lantai harus antara -5 dan 100.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO lokasi (Nama_Gedung, Lantai, Keterangan)
                VALUES (:nama_gedung, :lantai, :keterangan)
            ");
            $stmt->execute([
                ':nama_gedung' => $nama_gedung,
                ':lantai'      => $lantai,
                ':keterangan'  => $keterangan ?: null,
            ]);

            set_flash('success', "Lokasi \"$nama_gedung\" Lt. $lantai berhasil ditambahkan!");
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
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
        <div class="page-title">Tambah Lokasi Baru</div>
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
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Nama Gedung <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama_gedung" value="<?= h($old['nama_gedung'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: Main Building, UC Tower, UC Plaza..."
                   required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">Lantai <span style="color:#ef4444">*</span></label>
            <input type="number" name="lantai" value="<?= h($old['lantai'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: 1, 2, 3..." required min="-5" max="100">
        </div>

        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-textarea" placeholder="Detail lokasi spesifik (misal: Samping lift barang)…" rows="3"><?= h($old['keterangan'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Lokasi
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
