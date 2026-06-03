<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Laporan';
$activeMenu = 'laporan';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM water_report WHERE WaterReport_ID = :id");
$stmt->execute([':id' => $id]);
$report = $stmt->fetch();

if (!$report) {
    set_flash('error', 'Laporan tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$reportersList = $pdo->query("SELECT Reporter_ID, Nama, Nim FROM reporter ORDER BY Nama")->fetchAll();
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

$errors = [];
$old    = $report;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $reporter_id      = intval($_POST['reporter_id'] ?? 0);
    $dispenser_id     = intval($_POST['dispenser_id'] ?? 0);
    $kategori         = $_POST['kategori'] ?? '';
    $status           = $_POST['status'] ?? 'Pending';
    $deskripsi_report = trim($_POST['deskripsi_report'] ?? '');
    $foto_url         = trim($_POST['foto_url'] ?? '');

    // Validation
    if (!$reporter_id) {
        $errors[] = 'Pilih pelapor.';
    }
    if (!$dispenser_id) {
        $errors[] = 'Pilih dispenser.';
    }
    if (!in_array($kategori, ['Galon Kosong', 'Dispenser Rusak', 'Kebocoran', 'Distribusi Tidak Merata', 'Lainnya'])) {
        $errors[] = 'Kategori masalah tidak valid.';
    }
    if (!in_array($status, ['Pending', 'Diproses', 'Selesai', 'Ditolak'])) {
        $errors[] = 'Status tidak valid.';
    }
    if (!$deskripsi_report) {
        $errors[] = 'Deskripsi kendala wajib diisi.';
    }

    if (empty($errors)) {
        try {
            // Determine resolved_at timestamp
            $resolved_at = $report['Resolved_At'];
            if ($status === 'Selesai' && $report['Status'] !== 'Selesai') {
                $resolved_at = date('Y-m-d H:i:s');
            } elseif ($status !== 'Selesai') {
                $resolved_at = null;
            }

            $stmt = $pdo->prepare("
                UPDATE water_report 
                SET Reporter_ID = :reporter_id, Dispenser_ID = :dispenser_id, Kategori = :kategori, 
                    Status = :status, Deskripsi_Report = :deskripsi_report, Foto_url = :foto_url, Resolved_At = :resolved_at
                WHERE WaterReport_ID = :id
            ");
            $stmt->execute([
                ':reporter_id'      => $reporter_id,
                ':dispenser_id'     => $dispenser_id,
                ':kategori'         => $kategori,
                ':status'           => $status,
                ':deskripsi_report' => $deskripsi_report,
                ':foto_url'         => $foto_url ?: null,
                ':resolved_at'      => $resolved_at,
                ':id'               => $id,
            ]);

            set_flash('success', 'Laporan kendala berhasil diperbarui!');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui laporan: ' . $e->getMessage();
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
        <div class="page-title">Edit Laporan Kendala</div>
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

<div style="display:grid;grid-template-columns: 2fr 1fr;gap:1.5rem;align-items:start;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Pelapor <span style="color:#ef4444">*</span></label>
            <select name="reporter_id" class="form-select" required>
                <option value="">— Pilih Pelapor —</option>
                <?php foreach ($reportersList as $rep): ?>
                <option value="<?= $rep['Reporter_ID'] ?>" <?= (($old['Reporter_ID'] ?? ($old['reporter_id'] ?? '')) == $rep['Reporter_ID']) ? 'selected' : '' ?>>
                    <?= h($rep['Nama']) ?> (NIM: <?= h($rep['Nim']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Dispenser Bermasalah <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <option value="">— Pilih Dispenser —</option>
                <?php foreach ($dispensersList as $d): ?>
                <option value="<?= $d['Dispenser_ID'] ?>" <?= (($old['Dispenser_ID'] ?? ($old['dispenser_id'] ?? '')) == $d['Dispenser_ID']) ? 'selected' : '' ?>>
                    <?= h($d['Nama_Gedung']) ?> Lt. <?= h($d['Lantai']) ?> - <?= h($d['Kode_Dispenser']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Kategori Masalah <span style="color:#ef4444">*</span></label>
                <select name="kategori" class="form-select" required>
                    <option value="">— Pilih Masalah —</option>
                    <?php foreach (['Galon Kosong', 'Dispenser Rusak', 'Kebocoran', 'Distribusi Tidak Merata', 'Lainnya'] as $kat): ?>
                    <option value="<?= $kat ?>" <?= (($old['Kategori'] ?? ($old['kategori'] ?? '')) === $kat) ? 'selected' : '' ?>><?= $kat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444">*</span></label>
                <select name="status" class="form-select" required>
                    <?php foreach (['Pending', 'Diproses', 'Selesai', 'Ditolak'] as $st): ?>
                    <option value="<?= $st ?>" <?= (($old['Status'] ?? ($old['status'] ?? 'Pending')) === $st) ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">URL Foto Pendukung (Opsional)</label>
            <input type="url" name="foto_url" value="<?= h($old['Foto_url'] ?? ($old['foto_url'] ?? '')) ?>"
                   class="form-input" placeholder="https://example.com/foto-rusak.jpg">
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi Kendala <span style="color:#ef4444">*</span></label>
            <textarea name="deskripsi_report" class="form-textarea" placeholder="Tulis rincian kendala air dispenser di sini…" rows="4" required><?= h($old['Deskripsi_Report'] ?? ($old['deskripsi_report'] ?? '')) ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Perubahan
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>

<div style="display:flex;flex-direction:column;gap:1.5rem;">
    <!-- Photo Preview -->
    <?php if (!empty($report['Foto_url'])): ?>
    <div class="card" style="padding:20px;">
        <div style="font-weight:700;color:#0b1f3a;margin-bottom:12px;">Foto Lampiran</div>
        <img src="<?= h($report['Foto_url']) ?>" alt="Foto kendala" style="width:100%;height:auto;border-radius:10px;border:1px solid #e5e7eb;max-height:220px;object-fit:cover;">
    </div>
    <?php endif; ?>

    <!-- Info Card -->
    <div class="card" style="padding:20px;">
        <div style="font-weight:700;color:#0b1f3a;margin-bottom:12px;">Metadata Laporan</div>
        <div style="font-size:.82rem;color:#6b7280;line-height:1.6;">
            <div>Reported At: <b><?= date('d M Y, H:i', strtotime($report['Reported_At'])) ?> WIB</b></div>
            <?php if ($report['Resolved_At']): ?>
                <div class="text-emerald-700">Resolved At: <b><?= date('d M Y, H:i', strtotime($report['Resolved_At'])) ?> WIB</b></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
