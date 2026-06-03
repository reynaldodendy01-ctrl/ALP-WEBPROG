<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Buat Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

$dispenserList = $pdo->query("SELECT id, nama_lokasi, gedung, lantai FROM dispensers ORDER BY gedung, lantai")->fetchAll();
$jenisList     = ['Galon Kosong','Dispenser Rusak','Kebocoran','Distribusi Tidak Merata','Lainnya'];

$errors = [];
$old    = [];

// Pre-fill dispenser if coming from detail page
$old['dispenser_id'] = $_GET['dispenser_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $dispenser_id   = intval($_POST['dispenser_id']   ?? 0);
    $nama_pelapor   = trim($_POST['nama_pelapor']     ?? '');
    $kontak_pelapor = trim($_POST['kontak_pelapor']   ?? '');
    $jenis_masalah  = $_POST['jenis_masalah']          ?? '';
    $deskripsi      = trim($_POST['deskripsi']         ?? '');

    if (!$dispenser_id)                      $errors[] = 'Pilih dispenser.';
    if (!$nama_pelapor)                      $errors[] = 'Nama pelapor wajib diisi.';
    if (!in_array($jenis_masalah,$jenisList)) $errors[] = 'Jenis masalah tidak valid.';
    if (!$deskripsi)                         $errors[] = 'Deskripsi laporan wajib diisi.';

    if (empty($errors)) {
        $pdo->prepare("
            INSERT INTO laporan (dispenser_id, nama_pelapor, kontak_pelapor, jenis_masalah, deskripsi)
            VALUES (:did, :nama, :kontak, :jenis, :desk)
        ")->execute([
            ':did'    => $dispenser_id,
            ':nama'   => $nama_pelapor,
            ':kontak' => $kontak_pelapor ?: null,
            ':jenis'  => $jenis_masalah,
            ':desk'   => $deskripsi,
        ]);
        set_flash('success', 'Laporan berhasil dikirim! Tim kami akan segera menindaklanjuti.');
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
        <div class="page-title">Buat Laporan Baru</div>
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
            <label class="form-label">Dispenser Bermasalah <span style="color:#ef4444">*</span></label>
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
                <label class="form-label">Nama Pelapor <span style="color:#ef4444">*</span></label>
                <input type="text" name="nama_pelapor" value="<?= h($old['nama_pelapor'] ?? '') ?>"
                       class="form-input" placeholder="Nama lengkap" required maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">Kontak (Email / No HP)</label>
                <input type="text" name="kontak_pelapor" value="<?= h($old['kontak_pelapor'] ?? '') ?>"
                       class="form-input" placeholder="opsional" maxlength="100">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Jenis Masalah <span style="color:#ef4444">*</span></label>
            <select name="jenis_masalah" class="form-select" required>
                <option value="">— Pilih Jenis Masalah —</option>
                <?php foreach ($jenisList as $j): ?>
                <option value="<?= $j ?>" <?= ($old['jenis_masalah'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi Masalah <span style="color:#ef4444">*</span></label>
            <textarea name="deskripsi" class="form-textarea" rows="4"
                      placeholder="Jelaskan masalah secara detail…" required><?= h($old['deskripsi'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">send</span> Kirim Laporan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
