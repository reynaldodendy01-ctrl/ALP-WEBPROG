<?php
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Dispensers';
$activeMenu = 'dispensers';
define('ROOT', dirname(__DIR__));

// ── Filters ─────────────────────────────────────────────────────────────────
$whereClause = '1=1';
$params = [];

if (!empty($_GET['gedung'])) {
    $whereClause .= ' AND d.gedung = :gedung';
    $params[':gedung'] = $_GET['gedung'];
}
if (!empty($_GET['status'])) {
    $whereClause .= ' AND d.status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['q'])) {
    $whereClause .= ' AND d.nama_lokasi LIKE :q';
    $params[':q'] = '%' . $_GET['q'] . '%';
}

$stmt = $pdo->prepare("
    SELECT d.*, s.nama AS nama_staff, g.jumlah_tersedia, g.kapasitas_max
    FROM dispensers d
    LEFT JOIN staff s ON d.staff_id = s.id
    LEFT JOIN galon g ON g.dispenser_id = d.id
    WHERE $whereClause
    ORDER BY d.gedung, d.lantai, d.id
");
$stmt->execute($params);
$dispensers = $stmt->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Dispenser</div>
        <div class="page-subtitle"><?= count($dispensers) ?> dispenser ditemukan</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">add</span>
        Tambah Dispenser
    </a>
</div>

<!-- Filter Bar -->
<div class="card" style="padding:16px 20px;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:180px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Cari Nama Lokasi</label>
            <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
                   placeholder="Ketik nama lokasi…" class="form-input" style="padding:9px 12px;">
        </div>
        <div style="min-width:170px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Gedung</label>
            <select name="gedung" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Gedung</option>
                <option value="Main Building" <?= ($_GET['gedung'] ?? '') === 'Main Building' ? 'selected' : '' ?>>Main Building</option>
                <option value="UC Tower"      <?= ($_GET['gedung'] ?? '') === 'UC Tower'      ? 'selected' : '' ?>>UC Tower</option>
                <option value="Gedung Lain"   <?= ($_GET['gedung'] ?? '') === 'Gedung Lain'   ? 'selected' : '' ?>>Gedung Lain</option>
            </select>
        </div>
        <div style="min-width:160px;">
            <label class="form-label" style="margin-bottom:4px;font-size:.78rem;">Status</label>
            <select name="status" class="form-select" style="padding:9px 12px;">
                <option value="">Semua Status</option>
                <?php foreach (['Normal','Kosong','Rusak','Maintenance'] as $st): ?>
                <option value="<?= $st ?>" <?= ($_GET['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 20px;">
            <span class="mat-icon" style="font-size:18px">search</span> Filter
        </button>
        <a href="index.php" class="btn-secondary" style="padding:10px 20px;">Reset</a>
    </form>
</div>

<!-- Table -->
<div class="card" style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nama Lokasi</th>
                <th>Gedung</th>
                <th>Lantai</th>
                <th>Status</th>
                <th>Stok Galon</th>
                <th>Staff</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($dispensers)): ?>
            <tr>
                <td colspan="8" style="text-align:center;color:#9ca3af;padding:48px;">
                    <span class="mat-icon" style="font-size:48px;display:block;margin-bottom:8px;">water_drop</span>
                    Tidak ada dispenser ditemukan
                </td>
            </tr>
        <?php else: foreach ($dispensers as $i => $d):
            $statusBadge = match($d['status']) {
                'Normal'      => 'badge-green',
                'Kosong'      => 'badge-yellow',
                'Rusak'       => 'badge-red',
                'Maintenance' => 'badge-orange',
                default       => 'badge-gray',
            };
            $stok = $d['jumlah_tersedia'] ?? '—';
            $kap  = $d['kapasitas_max']   ?? '—';
            $pct  = ($kap && $kap > 0) ? intval(($stok / $kap) * 100) : 0;
            $barC = ($stok === 0) ? '#ef4444' : ($pct < 40 ? '#f59e0b' : '#22c55e');
        ?>
            <tr>
                <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1 ?></td>
                <td>
                    <a href="detail.php?id=<?= $d['id'] ?>"
                       style="font-weight:600;color:#0b1f3a;text-decoration:none;">
                        <?= h($d['nama_lokasi']) ?>
                    </a>
                </td>
                <td><span class="badge badge-blue"><?= h($d['gedung']) ?></span></td>
                <td style="font-weight:600">Lt. <?= h($d['lantai']) ?></td>
                <td><span class="badge <?= $statusBadge ?>"><?= h($d['status']) ?></span></td>
                <td>
                    <?php if ($stok !== '—'): ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;height:8px;background:#e5e7eb;border-radius:99px;min-width:60px;">
                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $barC ?>;border-radius:99px;"></div>
                        </div>
                        <span style="font-size:.8rem;font-weight:700;color:<?= $barC ?>;"><?= $stok ?>/<?= $kap ?></span>
                    </div>
                    <?php else: ?>
                        <span style="color:#9ca3af;font-size:.8rem;">Belum dicatat</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.85rem;"><?= h($d['nama_staff'] ?? '—') ?></td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <a href="detail.php?id=<?= $d['id'] ?>" class="btn-edit">
                            <span class="mat-icon" style="font-size:15px">visibility</span>
                        </a>
                        <a href="edit.php?id=<?= $d['id'] ?>" class="btn-edit">
                            <span class="mat-icon" style="font-size:15px">edit</span>
                        </a>
                        <form method="POST" action="delete.php" onsubmit="return confirm('Hapus dispenser \'<?= h(addslashes($d['nama_lokasi'])) ?>\'? Semua data terkait (galon, laporan, refill) ikut terhapus.')">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn-danger">
                                <span class="mat-icon" style="font-size:15px">delete</span>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
