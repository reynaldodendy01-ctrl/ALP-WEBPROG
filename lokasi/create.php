<?php
// =========================================================================
// FILE: lokasi/create.php
// FUNGSI: Menampilkan Form Tambah Lokasi Gedung Baru & Menyimpan Datanya (CREATE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Tambah Lokasi';
$activeMenu = 'lokasi';
define('ROOT', dirname(__DIR__));

// 3. PERSIAPAN VARIABEL ERROR & PENGINGAT KETIKAN
$errors = []; // Variabel array (keranjang) kosong. Kalau ada error validasi, pesannya ditaruh sini.
$old    = []; // Array untuk menyimpan ketikan sebelumnya jika terjadi error.

// 4. MENANGKAP DATA SAAT TOMBOL SUBMIT DIKLIK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Timpa $old dengan data ketikan baru 
    $old = $_POST;

    // Bersihkan spasi kosong dan ubah ke tipe data yang sesuai
    $nama_gedung = trim($_POST['nama_gedung'] ?? '');
    $lantai      = intval($_POST['lantai'] ?? 0);
    $keterangan  = trim($_POST['keterangan'] ?? '');

    // --- PROSES VALIDASI ---
    // Pastikan admin sudah mengisi nama gedung
    if (!$nama_gedung) {
        $errors[] = 'Nama gedung wajib diisi.';
    }
    // Pastikan lantai masuk akal (antara basement 5 sampai lantai 100)
    if ($lantai < -5 || $lantai > 100) {
        $errors[] = 'Lantai harus antara -5 dan 100.';
    }

    // Jika tidak ada error ($errors kosong), simpan ke MySQL
    if (empty($errors)) {
        try {
            // ─── 5. CRUD (CREATE) - MENYIMPAN DATA LOKASI KE MYSQL ───────────────────
            // Siapkan query penyisipan data (INSERT INTO).
            // :nama_gedung, :lantai, :keterangan adalah placeholder (lubang kosong) yang aman dari SQL Injection.
            $stmt = $pdo->prepare("
                INSERT INTO lokasi (Nama_Gedung, Lantai, Keterangan)
                VALUES (:nama_gedung, :lantai, :keterangan)
            ");
            
            // Masukkan data sebenarnya ke dalam placeholder tersebut
            $stmt->execute([
                ':nama_gedung' => $nama_gedung,
                ':lantai'      => $lantai,
                ':keterangan'  => $keterangan ?: null, // Jika keterangan dibiarkan kosong, simpan sebagai 'null' (kosong tulen di database)
            ]);

            // Buat notifikasi hijau "berhasil"
            set_flash('success', "Lokasi \"$nama_gedung\" Lt. $lantai berhasil ditambahkan!");
            header('Location: index.php'); // Lemparkan pengguna balik ke halaman index
            exit; // Matikan file
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
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
        <div class="page-title">Tambah Lokasi Baru</div>
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
            <label class="form-label">Nama Gedung <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama_gedung" value="<?= h($old['nama_gedung'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: Main Building, UC Tower, UC Plaza..."
                   required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">Lantai <span style="color:#ef4444">*</span></label>
            <input type="number" name="lantai" value="<?= h($old['lantai'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: 1, 2, 3..." required min="-5" max="100">
        </div>

        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-textarea" placeholder="Detail lokasi spesifik (misal: Samping lift barang)…" rows="3"><?= h($old['keterangan'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Lokasi
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
