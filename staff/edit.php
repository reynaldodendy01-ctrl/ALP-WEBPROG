<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Staff_ID = :id");
$stmt->execute([':id' => $id]);
$staff = $stmt->fetch();

if (!$staff) {
    set_flash('error', 'Staff tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = $staff;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');

    if (!$nama) {
        $errors[] = 'Nama staff wajib diisi.';
    }
    if (!$email) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (!$no_telp) {
        $errors[] = 'No. telepon wajib diisi.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE maintenance_staff 
                SET Nama = :nama, Email = :email, No_Telp = :no_telp
                WHERE Staff_ID = :id
            ");
            $stmt->execute([
                ':nama'    => $nama,
                ':email'   => $email,
                ':no_telp' => $no_telp,
                ':id'      => $id,
            ]);

            set_flash('success', "Staff \"$nama\" berhasil diperbarui!");
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui staff: ' . $e->getMessage();
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
        <div class="page-title">Edit Staff</div>
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

<div style="max-width:560px;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Nama Lengkap <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama" value="<?= h($old['Nama'] ?? ($old['nama'] ?? '')) ?>"
                   class="form-input" placeholder="Nama lengkap staff" required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">Email <span style="color:#ef4444">*</span></label>
            <input type="email" name="email" value="<?= h($old['Email'] ?? ($old['email'] ?? '')) ?>"
                   class="form-input" placeholder="staff@uc.ac.id" required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">No. Telepon / WhatsApp <span style="color:#ef4444">*</span></label>
            <input type="text" name="no_telp" value="<?= h($old['No_Telp'] ?? ($old['no_telp'] ?? '')) ?>"
                   class="form-input" placeholder="08xxxxxxxxxx" required maxlength="20">
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Staff
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
