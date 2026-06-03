<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Catat Refill';
$activeMenu = 'refill';
define('ROOT', dirname(__DIR__));

$dispenserList = $pdo->query("SELECT id, nama_lokasi, gedung, lantai FROM dispensers ORDER BY gedung, lantai")->fetchAll();
$staffList     = $pdo->query("SELECT id, nama FROM staff WHERE status='Aktif' ORDER BY nama")->fetchAll();

$errors = [];
$old    = [];
$old['dispenser_id'] = $_GET['dispenser_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $dispenser_id  = intval($_POST['dispenser_id']  ?? 0);
    $staff_id      = !empty($_POST['staff_id']) ? intval($_POST['staff_id']) : null;
    $jumlah_galon  = intval($_POST['jumlah_galon']  ?? 1);
    $tanggal_refill = !empty($_POST['tanggal_refill']) ? $_POST['tanggal_refill'] : date('Y-m-d\TH:i');
    $catatan       = trim($_POST['catatan']          ?? '');
    $update_stok   = isset($_POST['update_stok']);

    if (!$dispenser_id)                              $errors[] = 'Pilih dispenser.';
    if ($jumlah_galon < 1 || $jumlah_galon > 99)    $errors[] = 'Jumlah galon harus antara 1–99.';

    if (empty($errors)) {
        // Insert refill log
        $pdo->prepare("
            INSERT INTO refill_log (dispenser_id, staff_id, jumlah_galon, tanggal_refill, catatan)
            VALUES (:did, :sid, :jml, :tgl, :cat)
        ")->execute([
            ':did' => $dispenser_id,
            ':sid' => $staff_id,
            ':jml' => $jumlah_galon,
            ':tgl' => $tanggal_refill,
            ':cat' => $catatan ?: null,
        ]);

        // Optionally update galon stock
        if ($update_stok) {
            $galon = $pdo->prepare("SELECT * FROM galon WHERE dispenser_id=:did");
            $galon->execute([':did' => $dispenser_id]);
            $galon = $galon->fetch();

            if ($galon) {
                $newJumlah = min($galon['kapasitas_max'], $galon['jumlah_tersedia'] + $jumlah_galon);
                $pdo->prepare("
                    UPDATE galon SET jumlah_tersedia=:j, terakhir_diisi=:tgl
                    WHERE dispenser_id=:did
                ")->execute([':j'=>$newJumlah, ':tgl'=>$tanggal_refill, ':did'=>$dispenser_id]);

                // Update dispenser status to Normal if it was Kosong
                $pdo->prepare("
                    UPDATE dispensers SET status='Normal'
                    WHERE id=:did AND status='Kosong'
                ")->execute([':did' => $dispenser_id]);
            }
        }

        set_flash('success', "Refill $jumlah_galon galon berhasil dicatat!" . ($update_stok ? ' Stok galon diperbarui.' : ''));
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
        <div class="page-title">Catat Pengisian Galon</div>
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

<div style="max-width:620px;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Dispenser <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required id="dispenser_select">
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
                <label class="form-label">Jumlah Galon <span style="color:#ef4444">*</span></label>
                <input type="number" name="jumlah_galon" value="<?= h($old['jumlah_galon'] ?? '1') ?>"
                       class="form-input" min="1" max="99" required>
            </div>
            <div class="form-group">
                <label class="form-label">Staff Pengisi</label>
                <select name="staff_id" class="form-select">
                    <option value="">— Pilih Staff —</option>
                    <?php foreach ($staffList as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($old['staff_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= h($s['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tanggal & Waktu Pengisian</label>
            <input type="datetime-local" name="tanggal_refill"
                   value="<?= h($old['tanggal_refill'] ?? date('Y-m-d\TH:i')) ?>"
                   class="form-input">
        </div>

        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" class="form-textarea" rows="2"
                      placeholder="Catatan tambahan (opsional)…"><?= h($old['catatan'] ?? '') ?></textarea>
        </div>

        <!-- Auto update stok toggle -->
        <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:14px;margin-bottom:20px;">
            <input type="checkbox" name="update_stok" id="update_stok" value="1"
                   <?= isset($old['update_stok']) || !isset($_POST['update_stok']) ? 'checked' : '' ?>
                   style="width:18px;height:18px;accent-color:#0058bc;cursor:pointer;">
            <label for="update_stok" style="font-size:.875rem;font-weight:600;color:#0369a1;cursor:pointer;">
                Perbarui stok galon secara otomatis
                <span style="font-weight:400;color:#0284c7;display:block;font-size:.78rem;">
                    Jumlah stok di modul Galon akan ditambah sesuai input
                </span>
            </label>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">recycling</span> Catat Refill
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
