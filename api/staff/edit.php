<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$s = $pdo->prepare("SELECT * FROM staff WHERE id=:id");
$s->execute([':id' => $id]);
$s = $s->fetch();
if (!$s) { set_flash('error', 'Staff tidak ditemukan.'); header('Location: index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama']       ?? '');
    $no_hp      = trim($_POST['no_hp']      ?? '');
    $area_tugas = trim($_POST['area_tugas'] ?? '');
    $status     = $_POST['status']           ?? 'Aktif';

    if (!$nama)       $errors[] = 'Nama wajib diisi.';
    if (!$no_hp)      $errors[] = 'No. HP wajib diisi.';
    if (!$area_tugas) $errors[] = 'Area tugas wajib diisi.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE staff SET nama=:nama, no_hp=:hp, area_tugas=:area, status=:status WHERE id=:id")
            ->execute([':nama'=>$nama,':hp'=>$no_hp,':area'=>$area_tugas,':status'=>$status,':id'=>$id]);
        set_flash('success', "Data staff \"$nama\" berhasil diperbarui!");
        header('Location: index.php'); exit;
    }
    $s = array_merge($s, $_POST);
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Edit Staff</div>
        <div class="page-subtitle">#<?= $id ?> — <?= h($s['nama']) ?></div>
    </div>
</div>

<?php if ($errors): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?><div style="color:#dc2626;font-size:.875rem;"><?= h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:560px;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Nama Lengkap <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama" value="<?= h($s['nama']) ?>" class="form-input" required maxlength="100">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">No. HP <span style="color:#ef4444">*</span></label>
                <input type="text" name="no_hp" value="<?= h($s['no_hp']) ?>" class="form-input" required maxlength="20">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="Aktif"       <?= $s['status']==='Aktif'       ? 'selected' : '' ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= $s['status']==='Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Area Tugas <span style="color:#ef4444">*</span></label>
            <input type="text" name="area_tugas" value="<?= h($s['area_tugas']) ?>" class="form-input" required maxlength="100">
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
