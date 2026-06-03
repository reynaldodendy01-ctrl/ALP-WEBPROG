<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Dispenser';
$activeMenu = 'dispensers';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$dispenser = $pdo->prepare("SELECT * FROM dispensers WHERE id = :id");
$dispenser->execute([':id' => $id]);
$dispenser = $dispenser->fetch();
if (!$dispenser) { set_flash('error', 'Dispenser tidak ditemukan.'); header('Location: index.php'); exit; }

$staffList = $pdo->query("SELECT id, nama FROM staff WHERE status='Aktif' ORDER BY nama")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lokasi = trim($_POST['nama_lokasi'] ?? '');
    $gedung      = $_POST['gedung']           ?? '';
    $lantai      = intval($_POST['lantai']    ?? 0);
    $status      = $_POST['status']           ?? '';
    $staff_id    = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
    $catatan     = trim($_POST['catatan']     ?? '');

    if (!$nama_lokasi)                                           $errors[] = 'Nama lokasi wajib diisi.';
    if (!in_array($gedung, ['Main Building','UC Tower','Gedung Lain'])) $errors[] = 'Gedung tidak valid.';
    if ($lantai < 1 || $lantai > 50)                            $errors[] = 'Lantai harus antara 1–50.';
    if (!in_array($status, ['Normal','Kosong','Rusak','Maintenance'])) $errors[] = 'Status tidak valid.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE dispensers
            SET nama_lokasi=:nama_lokasi, gedung=:gedung, lantai=:lantai,
                status=:status, staff_id=:staff_id, catatan=:catatan
            WHERE id=:id
        ");
        $stmt->execute([
            ':nama_lokasi' => $nama_lokasi,
            ':gedung'      => $gedung,
            ':lantai'      => $lantai,
            ':status'      => $status,
            ':staff_id'    => $staff_id,
            ':catatan'     => $catatan ?: null,
            ':id'          => $id,
        ]);
        set_flash('success', "Dispenser \"$nama_lokasi\" berhasil diperbarui!");
        header('Location: index.php');
        exit;
    }
    // Re-populate with POST data on error
    $dispenser = array_merge($dispenser, $_POST);
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Edit Dispenser</div>
        <div class="page-subtitle">ID #<?= $id ?> — <?= h($dispenser['nama_lokasi']) ?></div>
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
            <label class="form-label">Nama Lokasi <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama_lokasi" value="<?= h($dispenser['nama_lokasi']) ?>"
                   class="form-input" required maxlength="150">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Gedung <span style="color:#ef4444">*</span></label>
                <select name="gedung" class="form-select" required>
                    <?php foreach (['Main Building','UC Tower','Gedung Lain'] as $g): ?>
                    <option value="<?= $g ?>" <?= $dispenser['gedung'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Lantai <span style="color:#ef4444">*</span></label>
                <input type="number" name="lantai" value="<?= h($dispenser['lantai']) ?>"
                       class="form-input" min="1" max="50" required>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444">*</span></label>
                <select name="status" class="form-select" required>
                    <?php foreach (['Normal','Kosong','Rusak','Maintenance'] as $st): ?>
                    <option value="<?= $st ?>" <?= $dispenser['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Staff Penanggung Jawab</label>
                <select name="staff_id" class="form-select">
                    <option value="">— Tidak Ditugaskan —</option>
                    <?php foreach ($staffList as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $dispenser['staff_id'] == $s['id'] ? 'selected' : '' ?>><?= h($s['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" class="form-textarea" rows="3"><?= h($dispenser['catatan']) ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Perubahan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
