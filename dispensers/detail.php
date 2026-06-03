<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Detail Dispenser';
$activeMenu = 'dispensers';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT d.*, s.nama AS nama_staff, s.no_hp AS hp_staff,
           g.jumlah_tersedia, g.kapasitas_max, g.terakhir_diisi, g.id AS galon_id
    FROM dispensers d
    LEFT JOIN staff s ON d.staff_id = s.id
    LEFT JOIN galon g ON g.dispenser_id = d.id
    WHERE d.id = :id
");
$stmt->execute([':id' => $id]);
$d = $stmt->fetch();
if (!$d) { set_flash('error', 'Dispenser tidak ditemukan.'); header('Location: index.php'); exit; }

// Related laporan
$laporan = $pdo->prepare("
    SELECT l.*, s.nama AS nama_staff FROM laporan l
    LEFT JOIN staff s ON l.staff_id = s.id
    WHERE l.dispenser_id = :id ORDER BY l.created_at DESC LIMIT 8
");
$laporan->execute([':id' => $id]);
$laporan = $laporan->fetchAll();

// Refill log
$refills = $pdo->prepare("
    SELECT r.*, s.nama AS nama_staff FROM refill_log r
    LEFT JOIN staff s ON r.staff_id = s.id
    WHERE r.dispenser_id = :id ORDER BY r.tanggal_refill DESC LIMIT 8
");
$refills->execute([':id' => $id]);
$refills = $refills->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';

$statusBadge = match($d['status']) {
    'Normal' => 'badge-green', 'Kosong' => 'badge-yellow',
    'Rusak'  => 'badge-red',   'Maintenance' => 'badge-orange',
    default  => 'badge-gray',
};
$stok = $d['jumlah_tersedia'] ?? 0;
$kap  = $d['kapasitas_max']   ?? 5;
$pct  = $kap > 0 ? intval(($stok / $kap) * 100) : 0;
$barC = $stok == 0 ? '#ef4444' : ($pct < 40 ? '#f59e0b' : '#22c55e');
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali ke Daftar
        </a>
        <div class="page-title"><?= h($d['nama_lokasi']) ?></div>
        <div class="page-subtitle"><?= h($d['gedung']) ?> · Lantai <?= h($d['lantai']) ?></div>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="edit.php?id=<?= $d['id'] ?>" class="btn-secondary">
            <span class="mat-icon" style="font-size:18px">edit</span> Edit
        </a>
        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus dispenser ini?')">
            <input type="hidden" name="id" value="<?= $d['id'] ?>">
            <button type="submit" class="btn-danger" style="padding:10px 16px;">
                <span class="mat-icon" style="font-size:18px">delete</span> Hapus
            </button>
        </form>
    </div>
</div>

<!-- Info Cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
    <div class="card" style="padding:24px;">
        <div style="font-size:.78rem;color:#9ca3af;font-weight:600;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">Status</div>
        <span class="badge <?= $statusBadge ?>" style="font-size:.95rem;padding:8px 16px;"><?= h($d['status']) ?></span>
    </div>
    <div class="card" style="padding:24px;">
        <div style="font-size:.78rem;color:#9ca3af;font-weight:600;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">Stok Galon</div>
        <div style="font-size:2rem;font-weight:800;color:<?= $barC ?>;line-height:1"><?= $stok ?><span style="font-size:1rem;color:#9ca3af">/<?= $kap ?></span></div>
        <div style="margin-top:10px;height:8px;background:#e5e7eb;border-radius:99px;">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $barC ?>;border-radius:99px;transition:width .5s;"></div>
        </div>
        <?php if ($d['terakhir_diisi']): ?>
        <div style="font-size:.78rem;color:#9ca3af;margin-top:6px;">Terakhir diisi: <?= date('d M Y H:i', strtotime($d['terakhir_diisi'])) ?></div>
        <?php endif; ?>
    </div>
    <div class="card" style="padding:24px;">
        <div style="font-size:.78rem;color:#9ca3af;font-weight:600;margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">Staff PIC</div>
        <?php if ($d['nama_staff']): ?>
            <div style="font-size:1rem;font-weight:700;color:#0b1f3a"><?= h($d['nama_staff']) ?></div>
            <div style="font-size:.82rem;color:#9ca3af;margin-top:4px;"><?= h($d['hp_staff']) ?></div>
        <?php else: ?>
            <div style="color:#9ca3af;font-size:.9rem;">Belum ditugaskan</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($d['catatan']): ?>
<div class="card" style="padding:20px;margin-bottom:1.5rem;background:#fffbeb;border-color:#fde68a;">
    <div style="display:flex;gap:10px;align-items:flex-start;">
        <span class="mat-icon" style="color:#d97706;">notes</span>
        <div>
            <div style="font-size:.8rem;font-weight:600;color:#92400e;margin-bottom:4px;">Catatan</div>
            <div style="font-size:.9rem;color:#78350f"><?= nl2br(h($d['catatan'])) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
    <!-- Laporan -->
    <div class="card">
        <div style="padding:18px 22px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:.95rem;font-weight:700;color:#0b1f3a">Laporan Terkait</div>
            <a href="../laporan/create.php?dispenser_id=<?= $id ?>" class="btn-edit" style="font-size:.78rem;">+ Buat Laporan</a>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Pelapor</th><th>Masalah</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($laporan)): ?>
                <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:24px;font-size:.85rem;">Belum ada laporan</td></tr>
            <?php else: foreach ($laporan as $l):
                $sb = match($l['status']) { 'Pending'=>'badge-yellow','Diproses'=>'badge-blue','Selesai'=>'badge-green','Ditolak'=>'badge-red',default=>'badge-gray' };
            ?>
                <tr>
                    <td style="font-size:.85rem;font-weight:600"><?= h($l['nama_pelapor']) ?></td>
                    <td style="font-size:.8rem"><?= h($l['jenis_masalah']) ?></td>
                    <td><span class="badge <?= $sb ?>" style="font-size:.72rem"><?= $l['status'] ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Refill Log -->
    <div class="card">
        <div style="padding:18px 22px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:.95rem;font-weight:700;color:#0b1f3a">Riwayat Refill</div>
            <a href="../refill/create.php?dispenser_id=<?= $id ?>" class="btn-edit" style="font-size:.78rem;">+ Catat Refill</a>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Tanggal</th><th>Staff</th><th style="text-align:center">Galon</th></tr></thead>
            <tbody>
            <?php if (empty($refills)): ?>
                <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:24px;font-size:.85rem;">Belum ada riwayat</td></tr>
            <?php else: foreach ($refills as $r): ?>
                <tr>
                    <td style="font-size:.8rem;color:#9ca3af"><?= date('d/m/y H:i', strtotime($r['tanggal_refill'])) ?></td>
                    <td style="font-size:.85rem"><?= h($r['nama_staff'] ?? '—') ?></td>
                    <td style="text-align:center;font-weight:800;color:#0058bc"><?= $r['jumlah_galon'] ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
