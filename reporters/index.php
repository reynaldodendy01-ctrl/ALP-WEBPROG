<?php
// =========================================================================
// FILE: reporters/index.php
// FUNGSI: Menampilkan Daftar Mahasiswa/Dosen yang Pernah Melapor (READ)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Reporters';
$activeMenu = 'reporters';
define('ROOT', dirname(__DIR__));

// 2. ─── CRUD (READ) - MENGAMBIL DATA PELAPOR DARI MYSQL ───────────────────
// Selain mengambil data pelapor, kita pakai trik COUNT dan GROUP BY
// Supaya bisa menghitung: "Si A ini udah pernah lapor berapa kali sih?"
$reporters = $pdo->query("
    SELECT r.*, COUNT(wr.WaterReport_ID) AS jumlah_laporan
    FROM reporter r
    LEFT JOIN water_report wr ON wr.Reporter_ID = r.Reporter_ID
    GROUP BY r.Reporter_ID
    ORDER BY r.Nama
")->fetchAll();

include __DIR__ . '/../_partials/layout_head.php';
?>

<?php render_flash(); ?>

<div class="page-header">
    <div>
        <div class="page-title">Manajemen Pelapor (Reporters)</div>
        <div class="page-subtitle"><?= count($reporters) ?> pelapor terdaftar</div>
    </div>
    <a href="create.php" class="btn-primary">
        <span class="mat-icon" style="font-size:20px">person_add</span> Tambah Pelapor
    </a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nama Pelapor</th>
            <th>NIM</th>
            <th>Jumlah Laporan</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($reporters)): ?>
        <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:48px;">Belum ada pelapor terdaftar</td></tr>
    <?php else: foreach ($reporters as $i => $r): ?>
        <tr>
            <td style="color:#9ca3af;font-size:.8rem"><?= $i+1 ?></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#0058bc,#1a78e5);
                          display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.9rem;flex-shrink:0;">
                        <?= strtoupper(mb_substr($r['Nama'], 0, 1)) ?>
                    </div>
                    <span style="font-weight:600"><?= h($r['Nama']) ?></span>
                </div>
            </td>
            <td style="font-weight:600"><?= h($r['Nim']) ?></td>
            <td>
                <span style="font-size:1rem;font-weight:800;color:#0058bc"><?= $r['jumlah_laporan'] ?></span>
                <span style="font-size:.8rem;color:#9ca3af"> laporan</span>
            </td>
            <td>
                <div style="display:flex;gap:6px;">
                    <a href="edit.php?id=<?= $r['Reporter_ID'] ?>" class="btn-edit">
                        <span class="mat-icon" style="font-size:15px">edit</span>
                    </a>
                    <form method="POST" action="delete.php" onsubmit="return confirm('Hapus pelapor <?= h(addslashes($r['Nama'])) ?>?')">
                        <input type="hidden" name="id" value="<?= $r['Reporter_ID'] ?>">
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
