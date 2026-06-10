<?php
// =========================================================================
// FILE: refill/create.php
// FUNGSI: Mencatat Isi Ulang Galon Baru & Otomatis Menyelesaikan Tugas
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Catat Refill';
$activeMenu = 'refill';
define('ROOT', dirname(__DIR__));

// 2. LOGIKA PILIHAN TUGAS (DROPDOWN)
// Di form, staff/admin harus memilih: "Refill ini ngerjain tugas yang mana?"

// Jika yang login adalah Staff lapangan:
if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
    // Staff HANYA BISA melihat dan memilih Tugas yang ditugaskan khusus untuk dia saja!
    $stmt = $pdo->prepare("
        SELECT a.Assignment_ID, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai, a.Status
        FROM staff_dispenser_assignment a
        JOIN maintenance_staff ms ON a.Staff_ID = ms.Staff_ID
        JOIN dispenser d ON a.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        WHERE a.Status IN ('Pending', 'On Progress') AND a.Staff_ID = :staff_id
        ORDER BY a.Created_At DESC
    ");
    $stmt->execute([':staff_id' => $_SESSION['staff_id']]);
    $assignmentsList = $stmt->fetchAll();

    // Kalau staff ini ternyata lagi nggak punya tugas apa-apa, cegah dia nambah refill secara ngawur.
    if (empty($assignmentsList)) {
        set_flash('error', 'Tidak ada penugasan aktif. Ambil laporan terlebih dahulu untuk dapat mencatat refill.');
        header('Location: ../dashboard/index.php');
        exit;
    }
} else {
    // Tapi jika yang login adalah Bos / Admin:
    // Admin BISA melihat SEMUA tugas yang sedang berjalan untuk dimasukkan log-nya.
    $assignmentsList = $pdo->query("
        SELECT a.Assignment_ID, ms.Nama AS nama_staff, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai, a.Status
        FROM staff_dispenser_assignment a
        JOIN maintenance_staff ms ON a.Staff_ID = ms.Staff_ID
        JOIN dispenser d ON a.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        WHERE a.Status != 'Cancelled'
        ORDER BY a.Created_At DESC
    ")->fetchAll();
}

$errors = [];
$old    = [];

// 3. FITUR PINTASAN DARI URL
// Kalau dari halaman dashboard diklik "Refill", maka ID assignment-nya otomatis terpilih di form.
if (isset($_GET['assignment_id'])) {
    $old['assignment_id'] = intval($_GET['assignment_id']);
}

// 4. SAAT FORM DIKIRIM OLEH STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $catatan       = trim($_POST['catatan'] ?? '');

    // Validasi
    if (!$assignment_id) {
        $errors[] = 'Pilih penugasan (assignment) yang berkaitan.';
    }

    if (empty($errors)) {
        try {
            // ─── 5. MEMULAI TRANSAKSI MYSQL ───────────────────
            // Kita melakukan 3 HAL SEKALIGUS di sini:
            // 1) Bikin catatan Refill
            // 2) Menyelesaikan Tugas Staff (Completed)
            // 3) Menyelesaikan Laporan Mahasiswa (Selesai)
            $pdo->beginTransaction();

            // PERINTAH 1: Masukkan data ke tabel refill_logs
            $stmt = $pdo->prepare("
                INSERT INTO refill_logs (Assignment_ID, Refill_At, Catatan)
                VALUES (:assignment_id, NOW(), :catatan)
            ");
            $stmt->execute([
                ':assignment_id' => $assignment_id,
                ':catatan'       => $catatan ?: null,
            ]);

            // PERINTAH 2: Otomatis ubah status penugasan (Assignment) si staff jadi 'Completed'
            // Karena asumsinya: Kalau udah dicatat masuk ke refill_logs, ya berarti kerjanya selesai!
            $pdo->prepare("UPDATE staff_dispenser_assignment SET Status = 'Completed' WHERE Assignment_ID = :asg_id")
                ->execute([':asg_id' => $assignment_id]);

            // PERINTAH 3: Cari tahu apakah tugas ini berawal dari keluhan mahasiswa (Water Report)?
            $stmtAsg = $pdo->prepare("SELECT WaterReport_ID FROM staff_dispenser_assignment WHERE Assignment_ID = :asg_id");
            $stmtAsg->execute([':asg_id' => $assignment_id]);
            $report_id = $stmtAsg->fetchColumn();

            // Jika iya (Ada laporannya), maka otomatis ubah status laporan mahasiswa itu jadi 'Selesai'
            if ($report_id) {
                $pdo->prepare("UPDATE water_report SET Status = 'Selesai', Resolved_At = NOW() WHERE WaterReport_ID = :wr_id")
                    ->execute([':wr_id' => $report_id]);
            }

            // Simpan semua perubahan
            $pdo->commit();
            set_flash('success', 'Log pengisian galon berhasil dicatat!');
            header('Location: index.php'); // Lemparkan ke daftar riwayat refill
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal mencatat refill: ' . $e->getMessage();
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
        <div class="page-title">Catat Pengisian Galon (Refill)</div>
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
            <label class="form-label">Pilih Penugasan Maintenance <span style="color:#ef4444">*</span></label>
            <select name="assignment_id" class="form-select" required>
                <option value="">— Pilih Penugasan Staff —</option>
                <?php foreach ($assignmentsList as $asg): ?>
                <option value="<?= $asg['Assignment_ID'] ?>" <?= ($old['assignment_id'] ?? '') == $asg['Assignment_ID'] ? 'selected' : '' ?>>
                    [Penugasan #<?= $asg['Assignment_ID'] ?>] <?= h($asg['nama_staff']) ?> → <?= h($asg['Kode_Dispenser']) ?> (<?= h($asg['Nama_Gedung']) ?>) - Status: <?= h($asg['Status']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size:.78rem;color:#9ca3af;margin-top:4px;">Pilih penugasan staff yang melatarbelakangi pengisian galon ini. Mencatat refill akan mengubah status penugasan menjadi "Completed".</p>
        </div>

        <div class="form-group">
            <label class="form-label">Catatan Pengisian</label>
            <textarea name="catatan" class="form-textarea" placeholder="Tulis catatan (misal: Mengisi 2 galon air mineral standard)…" rows="3"><?= h($old['catatan'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Catat Refill
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
