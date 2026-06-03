<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$l = $pdo->prepare("SELECT * FROM laporan WHERE id=:id");
$l->execute([':id' => $id]);
$l = $l->fetch();
if (!$l) { set_flash('error', 'Laporan tidak ditemukan.'); header('Location: index.php'); exit; }

$dispenserList = $pdo->query("SELECT id, nama_lokasi, gedung, lantai FROM dispensers ORDER BY gedung, lantai")->fetchAll();
$staffList     = $pdo->query("SELECT id, nama FROM staff WHERE status='Aktif' ORDER BY nama")->fetchAll();
$jenisList     = ['Galon Kosong','Dispenser Rusak','Kebocoran','Distribusi Tidak Merata','Lainnya'];
$statusList    = ['Pending','Diproses','Selesai','Ditolak'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dispenser_id   = intval($_POST['dispenser_id']   ?? 0);
    $nama_pelapor   = trim($_POST['nama_pelapor']     ?? '');
    $kontak_pelapor = trim($_POST['kontak_pelapor']   ?? '');
    $jenis_masalah  = $_POST['jenis_masalah']          ?? '';
    $deskripsi      = trim($_POST['deskripsi']         ?? '');
    $status         = $_POST['status']                 ?? '';
    $staff_id       = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
    $catatan_admin  = trim($_POST['catatan_admin']     ?? '');

    if (!$dispenser_id)                       $errors[] = 'Pilih dispenser.';
    if (!$nama_pelapor)                       $errors[] = 'Nama pelapor wajib diisi.';
    if (!in_array($jenis_masalah, $jenisList)) $errors[] = 'Jenis masalah tidak valid.';
    if (!$deskripsi)                          $errors[] = 'Deskripsi wajib diisi.';
    if (!in_array($status, $statusList))      $errors[] = 'Status tidak valid.';

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE laporan SET dispenser_id=:did, nama_pelapor=:nama, kontak_pelapor=:kontak,
                jenis_masalah=:jenis, deskripsi=:desk, status=:status,
                staff_id=:staff_id, catatan_admin=:catatan_admin
            WHERE id=:id
        ")->execute([
            ':did'          => $dispenser_id,
            ':nama'         => $nama_pelapor,
            ':kontak'       => $kontak_pelapor ?: null,
            ':jenis'        => $jenis_masalah,
            ':desk'         => $deskripsi,
            ':status'       => $status,
            ':staff_id'     => $staff_id,
            ':catatan_admin'=> $catatan_admin ?: null,
            ':id'           => $id,
        ]);
        set_flash('success', 'Laporan berhasil diperbarui!');
        header('Location: index.php');
        exit;
    }
    $l = array_merge($l, $_POST);
}

include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Edit Laporan #<?= $id ?></div>
    </div>
</div>

<?php if ($errors): ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?>
        <div style="color:#dc2626;font-size:.875rem;"><?= h($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:720px;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Dispenser <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <?php foreach ($dispenserList as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $l['dispenser_id'] == $d['id'] ? 'selected' : '' ?>>
                    <?= h($d['nama_lokasi']) ?> — <?= h($d['gedung']) ?> Lt.<?= $d['lantai'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Nama Pelapor <span style="color:#ef4444">*</span></label>
                <input type="text" name="nama_pelapor" value="<?= h($l['nama_pelapor']) ?>" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kontak Pelapor</label>
                <input type="text" name="kontak_pelapor" value="<?= h($l['kontak_pelapor']) ?>" class="form-input">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Jenis Masalah <span style="color:#ef4444">*</span></label>
            <select name="jenis_masalah" class="form-select" required>
                <?php foreach ($jenisList as $j): ?>
                <option value="<?= $j ?>" <?= $l['jenis_masalah'] === $j ? 'selected' : '' ?>><?= $j ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi <span style="color:#ef4444">*</span></label>
            <textarea name="deskripsi" class="form-textarea" rows="3" required><?= h($l['deskripsi']) ?></textarea>
        </div>

        <div style="border-top:1px solid #f3f4f6;padding-top:20px;margin-top:4px;">
            <div style="font-size:.8rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px;">Respons Admin</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Status <span style="color:#ef4444">*</span></label>
                    <select name="status" class="form-select" required>
                        <?php foreach ($statusList as $st): ?>
                        <option value="<?= $st ?>" <?= $l['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assign ke Staff</label>
                    <select name="staff_id" class="form-select">
                        <option value="">— Tidak Ditugaskan —</option>
                        <?php foreach ($staffList as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $l['staff_id'] == $s['id'] ? 'selected' : '' ?>><?= h($s['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan Admin</label>
                <textarea name="catatan_admin" class="form-textarea" rows="2"><?= h($l['catatan_admin']) ?></textarea>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Perubahan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
