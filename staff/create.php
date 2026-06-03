<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Tambah Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $nama       = trim($_POST['nama']       ?? '');
    $no_hp      = trim($_POST['no_hp']      ?? '');
    $area_tugas = trim($_POST['area_tugas'] ?? '');
    $status     = $_POST['status']           ?? 'Aktif';

    if (!$nama)                                              $errors[] = 'Nama staff wajib diisi.';
    if (!$no_hp)                                             $errors[] = 'No. HP wajib diisi.';
    if (!$area_tugas)                                        $errors[] = 'Area tugas wajib diisi.';
    if (!in_array($status, ['Aktif','Tidak Aktif']))         $errors[] = 'Status tidak valid.';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO staff (nama, no_hp, area_tugas, status) VALUES (:nama, :hp, :area, :status)")
            ->execute([':nama'=>$nama,':hp'=>$no_hp,':area'=>$area_tugas,':status'=>$status]);
        set_flash('success', "Staff \"$nama\" berhasil ditambahkan!");
        header('Location: index.php'); exit;
    }
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Tambah Staff Baru</div>
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
            <input type="text" name="nama" value="<?= h($old['nama'] ?? '') ?>"
                   class="form-input" placeholder="Nama lengkap staff" required maxlength="100">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">No. HP <span style="color:#ef4444">*</span></label>
                <input type="text" name="no_hp" value="<?= h($old['no_hp'] ?? '') ?>"
                       class="form-input" placeholder="08xxxxxxxxxx" required maxlength="20">
            </div>
            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444">*</span></label>
                <select name="status" class="form-select">
                    <option value="Aktif"       <?= ($old['status'] ?? 'Aktif') === 'Aktif'       ? 'selected' : '' ?>>Aktif</option>
                    <option value="Tidak Aktif" <?= ($old['status'] ?? '') === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Area Tugas <span style="color:#ef4444">*</span></label>
            <input type="text" name="area_tugas" value="<?= h($old['area_tugas'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: Main Building Lt. 1-5" required maxlength="100">
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
