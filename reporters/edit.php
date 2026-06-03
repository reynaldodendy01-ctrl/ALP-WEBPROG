<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Pelapor';
$activeMenu = 'reporters';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM reporter WHERE Reporter_ID = :id");
$stmt->execute([':id' => $id]);
$reporter = $stmt->fetch();

if (!$reporter) {
    set_flash('error', 'Pelapor tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = $reporter;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $nama = trim($_POST['nama'] ?? '');
    $nim  = trim($_POST['nim'] ?? '');

    if (!$nama) {
        $errors[] = 'Nama pelapor wajib diisi.';
    }
    if (!$nim) {
        $errors[] = 'NIM pelapor wajib diisi.';
    }

    // Check duplicate NIM if changed
    if ($nim && $nim !== $reporter['Nim']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reporter WHERE Nim = :nim");
        $stmt->execute([':nim' => $nim]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'NIM pelapor sudah terdaftar.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE reporter 
                SET Nama = :nama, Nim = :nim
                WHERE Reporter_ID = :id
            ");
            $stmt->execute([
                ':nama' => $nama,
                ':nim'  => $nim,
                ':id'   => $id,
            ]);

            set_flash('success', "Pelapor \"$nama\" berhasil diperbarui!");
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui data: ' . $e->getMessage();
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
        <div class="page-title">Edit Pelapor</div>
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
            <label class="form-label">Nama Pelapor <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama" value="<?= h($old['Nama'] ?? ($old['nama'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: Ahmad Rizki"
                   required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">NIM <span style="color:#ef4444">*</span></label>
            <input type="text" name="nim" value="<?= h($old['Nim'] ?? ($old['nim'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: 0706012110001" required maxlength="20">
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Pelapor
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
