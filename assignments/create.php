<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Tambah Penugasan';
$activeMenu = 'assignments';
define('ROOT', dirname(__DIR__));

$staffList = $pdo->query("SELECT Staff_ID, Nama FROM maintenance_staff ORDER BY Nama")->fetchAll();
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

// Only load Pending/Diproses water reports, plus reporter name
$reportsList = $pdo->query("
    SELECT wr.WaterReport_ID, wr.Kategori, rep.Nama AS nama_pelapor, d.Kode_Dispenser
    FROM water_report wr
    JOIN reporter rep ON wr.Reporter_ID = rep.Reporter_ID
    JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID
    WHERE wr.Status IN ('Pending', 'Diproses')
    ORDER BY wr.Reported_At DESC
")->fetchAll();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $staff_id       = intval($_POST['staff_id'] ?? 0);
    $dispenser_id   = intval($_POST['dispenser_id'] ?? 0);
    $water_report_id = !empty($_POST['water_report_id']) ? intval($_POST['water_report_id']) : null;
    $status         = $_POST['status'] ?? 'Pending';

    // Validation
    if (!$staff_id) {
        $errors[] = 'Pilih staff maintenance.';
    }
    if (!$dispenser_id) {
        $errors[] = 'Pilih dispenser.';
    }
    if (!in_array($status, ['Pending', 'On Progress', 'Completed', 'Cancelled'])) {
        $errors[] = 'Status tidak valid.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO staff_dispenser_assignment (Staff_ID, Dispenser_ID, WaterReport_ID, Status, Created_At)
                VALUES (:staff_id, :dispenser_id, :water_report_id, :status, NOW())
            ");
            $stmt->execute([
                ':staff_id'        => $staff_id,
                ':dispenser_id'    => $dispenser_id,
                ':water_report_id' => $water_report_id,
                ':status'          => $status,
            ]);

            // If a water report is linked, we can automatically update its status to 'Diproses'
            if ($water_report_id && $status === 'On Progress') {
                $pdo->prepare("UPDATE water_report SET Status = 'Diproses' WHERE WaterReport_ID = :wr_id")
                    ->execute([':wr_id' => $water_report_id]);
            } elseif ($water_report_id && $status === 'Completed') {
                $pdo->prepare("UPDATE water_report SET Status = 'Selesai', Resolved_At = NOW() WHERE WaterReport_ID = :wr_id")
                    ->execute([':wr_id' => $water_report_id]);
            }

            $pdo->commit();
            set_flash('success', 'Penugasan berhasil dibuat!');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan penugasan: ' . $e->getMessage();
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
        <div class="page-title">Tambah Penugasan Baru</div>
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
            <label class="form-label">Tugaskan Kepada Staff <span style="color:#ef4444">*</span></label>
            <select name="staff_id" class="form-select" required>
                <option value="">— Pilih Staff Maintenance —</option>
                <?php foreach ($staffList as $s): ?>
                <option value="<?= $s['Staff_ID'] ?>" <?= ($old['staff_id'] ?? '') == $s['Staff_ID'] ? 'selected' : '' ?>>
                    <?= h($s['Nama']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Dispenser Target <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <option value="">— Pilih Dispenser —</option>
                <?php foreach ($dispensersList as $d): ?>
                <option value="<?= $d['Dispenser_ID'] ?>" <?= ($old['dispenser_id'] ?? '') == $d['Dispenser_ID'] ? 'selected' : '' ?>>
                    <?= h($d['Nama_Gedung']) ?> Lt. <?= h($d['Lantai']) ?> - <?= h($d['Kode_Dispenser']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Hubungkan dengan Laporan Masalah (Opsional)</label>
            <select name="water_report_id" class="form-select">
                <option value="">— Routine Check / Mandiri (Tidak Terikat Laporan) —</option>
                <?php foreach ($reportsList as $rep): ?>
                <option value="<?= $rep['WaterReport_ID'] ?>" <?= ($old['water_report_id'] ?? '') == $rep['WaterReport_ID'] ? 'selected' : '' ?>>
                    [Laporan #<?= $rep['WaterReport_ID'] ?>] Pelapor: <?= h($rep['nama_pelapor']) ?> - <?= h($rep['Kategori']) ?> (Dispenser: <?= h($rep['Kode_Dispenser']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size:.78rem;color:#9ca3af;margin-top:4px;">Memilih laporan akan membantu melacak penyelesaian kendala di dashboard admin.</p>
        </div>

        <div class="form-group">
            <label class="form-label">Status Awal Penugasan <span style="color:#ef4444">*</span></label>
            <select name="status" class="form-select" required>
                <?php foreach (['Pending', 'On Progress', 'Completed', 'Cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= ($old['status'] ?? 'Pending') === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Buat Penugasan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
