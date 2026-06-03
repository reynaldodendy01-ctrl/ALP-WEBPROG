<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Tambah Stok Galon';
$activeMenu = 'galon';
define('ROOT', dirname(__DIR__));

// Only dispensers without a galon record
$dispenserList = $pdo->query("
    SELECT d.id, d.nama_lokasi, d.gedung, d.lantai
    FROM dispensers d
    LEFT JOIN galon g ON g.dispenser_id = d.id
    WHERE g.id IS NULL
    ORDER BY d.gedung, d.lantai
")->fetchAll();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $dispenser_id    = intval($_POST['dispenser_id']    ?? 0);
    $jumlah_tersedia = intval($_POST['jumlah_tersedia'] ?? 0);
    $kapasitas_max   = intval($_POST['kapasitas_max']   ?? 5);
    $catatan         = trim($_POST['catatan']           ?? '');
    $terakhir_diisi  = !empty($_POST['terakhir_diisi'])  ? $_POST['terakhir_diisi'] : null;

    if (!$dispenser_id)                                       $errors[] = 'Pilih dispenser terlebih dahulu.';
    if ($jumlah_tersedia < 0 || $jumlah_tersedia > 99)       $errors[] = 'Jumlah tersedia tidak valid.';
    if ($kapasitas_max < 1   || $kapasitas_max  > 99)        $errors[] = 'Kapasitas maksimal tidak valid.';
    if ($jumlah_tersedia > $kapasitas_max)                    $errors[] = 'Jumlah tersedia tidak boleh melebihi kapasitas.';

    if (empty($errors)) {
        $pdo->prepare("
            INSERT INTO galon (dispenser_id, jumlah_tersedia, kapasitas_max, terakhir_diisi, catatan)
            VALUES (:dispenser_id, :jumlah, :kap, :isi, :catatan)
        ")->execute([
            ':dispenser_id' => $dispenser_id,
            ':jumlah'       => $jumlah_tersedia,
            ':kap'          => $kapasitas_max,
            ':isi'          => $terakhir_diisi,
            ':catatan'      => $catatan ?: null,
        ]);
        set_flash('success', 'Data stok galon berhasil ditambahkan!');
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Tambah Record Stok Galon</div>
    </div>
</div>

<?php if ($errors): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?>
        <div style="color:#dc2626;font-size:.875rem;"><?= h($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:560px;">
<div class="card" style="padding:32px;">
<?php if (empty($dispenserList)): ?>
    <div style="text-align:center;color:#9ca3af;padding:24px;">
        <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px">inventory_2</span>
        Semua dispenser sudah memiliki record galon.<br>
        <a href="index.php" style="color:#0058bc">Kembali ke daftar</a> untuk mengeditnya.
    </div>
<?php else: ?>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Dispenser <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <option value="">— Pilih Dispenser —</option>
                <?php foreach ($dispenserList as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($old['dispenser_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                    <?= h($d['nama_lokasi']) ?> — <?= h($d['gedung']) ?> Lt.<?= $d['lantai'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Jumlah Tersedia <span style="color:#ef4444">*</span></label>
                <input type="number" name="jumlah_tersedia" value="<?= h($old['jumlah_tersedia'] ?? '0') ?>"
                       class="form-input" min="0" max="99" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kapasitas Maksimal <span style="color:#ef4444">*</span></label>
                <input type="number" name="kapasitas_max" value="<?= h($old['kapasitas_max'] ?? '5') ?>"
                       class="form-input" min="1" max="99" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tanggal Terakhir Diisi</label>
            <input type="datetime-local" name="terakhir_diisi"
                   value="<?= h($old['terakhir_diisi'] ?? '') ?>" class="form-input">
        </div>

        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" class="form-textarea" rows="2"><?= h($old['catatan'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
<?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
