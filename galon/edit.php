<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Stok Galon';
$activeMenu = 'galon';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT g.*, d.nama_lokasi, d.gedung, d.lantai FROM galon g JOIN dispensers d ON g.dispenser_id=d.id WHERE g.id=:id");
$stmt->execute([':id' => $id]);
$g = $stmt->fetch();
if (!$g) { set_flash('error', 'Data galon tidak ditemukan.'); header('Location: index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah_tersedia = intval($_POST['jumlah_tersedia'] ?? 0);
    $kapasitas_max   = intval($_POST['kapasitas_max']   ?? 5);
    $terakhir_diisi  = !empty($_POST['terakhir_diisi'])  ? $_POST['terakhir_diisi'] : null;
    $catatan         = trim($_POST['catatan']           ?? '');

    if ($jumlah_tersedia < 0 || $jumlah_tersedia > 99) $errors[] = 'Jumlah tersedia tidak valid.';
    if ($kapasitas_max < 1   || $kapasitas_max  > 99)  $errors[] = 'Kapasitas tidak valid.';
    if ($jumlah_tersedia > $kapasitas_max)              $errors[] = 'Jumlah tersedia tidak boleh melebihi kapasitas.';

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE galon SET jumlah_tersedia=:j, kapasitas_max=:k, terakhir_diisi=:d, catatan=:c
            WHERE id=:id
        ")->execute([':j'=>$jumlah_tersedia,':k'=>$kapasitas_max,':d'=>$terakhir_diisi,':c'=>$catatan?:null,':id'=>$id]);
        set_flash('success', 'Stok galon berhasil diperbarui!');
        header('Location: index.php');
        exit;
    }
    $g = array_merge($g, $_POST);
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Edit Stok Galon</div>
        <div class="page-subtitle"><?= h($g['nama_lokasi']) ?> — <?= h($g['gedung']) ?> Lt.<?= $g['lantai'] ?></div>
    </div>
</div>

<?php if ($errors): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?>
        <div style="color:#dc2626;font-size:.875rem;"><?= h($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:520px;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Jumlah Tersedia <span style="color:#ef4444">*</span></label>
                <input type="number" name="jumlah_tersedia" value="<?= h($g['jumlah_tersedia']) ?>"
                       class="form-input" min="0" max="99" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kapasitas Maksimal <span style="color:#ef4444">*</span></label>
                <input type="number" name="kapasitas_max" value="<?= h($g['kapasitas_max']) ?>"
                       class="form-input" min="1" max="99" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tanggal Terakhir Diisi</label>
            <input type="datetime-local" name="terakhir_diisi"
                   value="<?= $g['terakhir_diisi'] ? date('Y-m-d\TH:i', strtotime($g['terakhir_diisi'])) : '' ?>"
                   class="form-input">
        </div>

        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" class="form-textarea" rows="2"><?= h($g['catatan']) ?></textarea>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
