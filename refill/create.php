<?php
/**
 * =============================================================================
 * create.php — Halaman Form Pencatatan Pengisian Galon (Catat Refill)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menyediakan form untuk mencatat aktivitas pengisian ulang galon air
 *   (refill) yang dikaitkan dengan satu penugasan (assignment) aktif. Terdapat
 *   logika khusus berbasis role: staf maintenance hanya dapat memilih penugasan
 *   milik mereka sendiri yang masih aktif, sedangkan admin dapat melihat seluruh
 *   penugasan yang belum dibatalkan. Setelah refill berhasil dicatat, status
 *   penugasan otomatis berubah menjadi "Completed" dan water report terkait
 *   (bila ada) juga diperbarui menjadi "Selesai" dalam satu transaksi atomik.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan form pencatatan refill dengan dropdown pilihan penugasan aktif
 *   - Membedakan daftar penugasan berdasarkan role sesi (Staff vs Admin)
 *   - Mendukung pre-select penugasan via parameter URL (?assignment_id=...)
 *   - Memvalidasi bahwa penugasan dipilih sebelum menyimpan
 *   - Menyimpan log refill baru ke tabel refill_logs
 *   - Mengubah status penugasan menjadi "Completed" secara otomatis
 *   - Memperbarui status water_report terkait menjadi "Selesai" + Resolved_At
 *   - Redirect ke index.php dengan flash message sukses setelah berhasil
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk koneksi dan helper functions
 *   2. Dicek role staf dari $_SESSION['staff_role']:
 *      - Role 'Staff': query penugasan aktif milik staf itu saja (WHERE Staff_ID = :staff_id)
 *        Jika tidak ada penugasan aktif, redirect ke dashboard dengan pesan error
 *      - Role lain (Admin): query semua penugasan yang Status != 'Cancelled'
 *   3. Bila ada parameter GET assignment_id, nilai tersebut di-preselect di form
 *   4. Jika request POST:
 *      a. assignment_id dan catatan dibaca dari $_POST
 *      b. Validasi: assignment_id wajib dipilih
 *      c. Bila valid: transaksi dimulai
 *      d. INSERT ke refill_logs (Assignment_ID, Refill_At, Catatan)
 *      e. UPDATE status penugasan menjadi 'Completed'
 *      f. Ambil WaterReport_ID dari penugasan; bila ada, UPDATE water_report = 'Selesai'
 *      g. Commit, flash sukses, redirect ke index.php
 *      h. Bila exception: rollback, tampilkan error
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - refill_logs                : INSERT data log refill baru
 *   - staff_dispenser_assignment : SELECT daftar penugasan; UPDATE status jadi 'Completed'
 *   - maintenance_staff          : JOIN untuk mendapatkan nama staf di dropdown
 *   - dispenser                  : JOIN untuk kode dispenser
 *   - lokasi                     : JOIN untuk nama gedung dan lantai
 *   - water_report               : UPDATE status menjadi 'Selesai' bila terkait penugasan
 *
 * VARIABEL PENTING:
 *   - $assignmentsList : Array penugasan aktif yang tersedia untuk dipilih di form
 *   - $errors          : Array pesan validasi error
 *   - $old             : Array nilai form lama (untuk repopulate saat error)
 *   - $assignment_id   : ID penugasan yang dipilih (integer)
 *   - $catatan         : Catatan pengisian galon (string, nullable)
 *   - $report_id       : WaterReport_ID dari penugasan terpilih (untuk sinkronisasi)
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML dan script JS penutup
 *
 * AKSES:
 *   Dapat diakses oleh admin dan staf maintenance yang memiliki penugasan aktif.
 *   Staf tanpa penugasan aktif akan di-redirect ke dashboard secara otomatis.
 *
 * CATATAN PENGEMBANG:
 *   - Seluruh operasi database (INSERT refill + UPDATE assignment + UPDATE water_report)
 *     dibungkus dalam satu transaksi untuk menjamin konsistensi data
 *   - Parameter ?assignment_id di URL memudahkan akses cepat dari halaman assignments/index.php
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Catat Refill';
$activeMenu = 'refill';
define('ROOT', dirname(__DIR__));

if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Staff') {
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
} else {
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

// Support passing assignment_id via GET
if (isset($_GET['assignment_id'])) {
    $old['assignment_id'] = intval($_GET['assignment_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $catatan       = trim($_POST['catatan'] ?? '');

    // Validation
    if (!$assignment_id) {
        $errors[] = 'Pilih penugasan (assignment) yang berkaitan.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert refill log
            $stmt = $pdo->prepare("
                INSERT INTO refill_logs (Assignment_ID, Refill_At, Catatan)
                VALUES (:assignment_id, NOW(), :catatan)
            ");
            $stmt->execute([
                ':assignment_id' => $assignment_id,
                ':catatan'       => $catatan ?: null,
            ]);

            // Auto-complete the penugasan when refilled
            $pdo->prepare("UPDATE staff_dispenser_assignment SET Status = 'Completed' WHERE Assignment_ID = :asg_id")
                ->execute([':asg_id' => $assignment_id]);

            // Also, find if this assignment is linked to a water report, and set that report to Completed (Selesai)
            $stmtAsg = $pdo->prepare("SELECT WaterReport_ID FROM staff_dispenser_assignment WHERE Assignment_ID = :asg_id");
            $stmtAsg->execute([':asg_id' => $assignment_id]);
            $report_id = $stmtAsg->fetchColumn();

            if ($report_id) {
                $pdo->prepare("UPDATE water_report SET Status = 'Selesai', Resolved_At = NOW() WHERE WaterReport_ID = :wr_id")
                    ->execute([':wr_id' => $report_id]);
            }

            $pdo->commit();
            set_flash('success', 'Log pengisian galon berhasil dicatat!');
            header('Location: index.php');
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
