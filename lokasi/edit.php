<?php
// =========================================================================
// FILE: lokasi/edit.php
// FUNGSI: Menampilkan Form Edit Lokasi Gedung & Menyimpan Perubahannya (UPDATE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Edit Lokasi';
$activeMenu = 'lokasi';
define('ROOT', dirname(__DIR__));

// 3. MENANGKAP ID LOKASI DARI URL
// Misalnya URL-nya adalah "edit.php?id=3", maka kita ambil angka 3 nya.
$id = intval($_GET['id'] ?? 0);

// 4. MENGAMBIL DATA LOKASI LAMA DARI DATABASE
// Kita perlu mencari lokasi dengan ID tersebut agar kita bisa memunculkan data aslinya di form.
$stmt = $pdo->prepare("SELECT * FROM lokasi WHERE Lokasi_ID = :id");
$stmt->execute([':id' => $id]);
$location = $stmt->fetch(); // Mengambil satu baris data

// Jika lokasinya tidak ditemukan (misal ID-nya salah atau sudah dihapus)
if (!$location) {
    set_flash('error', 'Lokasi tidak ditemukan.'); // Beri peringatan merah
    header('Location: index.php'); // Lemparkan balik ke halaman daftar
    exit; // Stop proses
}

// 5. PERSIAPAN VARIABEL
$errors = [];
$old    = $location; // Isi $old dengan data asli lokasi tersebut. Nanti ditampilkan di value="<..." HTML.

// 6. MENANGKAP PERUBAHAN SAAT TOMBOL "PERBARUI" DIKLIK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Timpa $old dengan data baru supaya ketikan tidak hilang kalau ada error
    $old = $_POST;

    // Bersihkan data
    $nama_gedung = trim($_POST['nama_gedung'] ?? '');
    $lantai      = intval($_POST['lantai'] ?? 0);
    $keterangan  = trim($_POST['keterangan'] ?? '');

    // --- PROSES VALIDASI ---
    if (!$nama_gedung) {
        $errors[] = 'Nama gedung wajib diisi.';
    }
    if ($lantai < -5 || $lantai > 100) {
        $errors[] = 'Lantai harus antara -5 dan 100.';
    }

    // Jika tidak ada error validasi, simpan perubahannya ke MySQL
    if (empty($errors)) {
        try {
            // ─── 7. CRUD (UPDATE) - MEMPERBARUI DATA LOKASI DI MYSQL ───────────────────
            // Kita menggunakan 'UPDATE ... SET ... WHERE ...'
            // 'WHERE Lokasi_ID = :id' wajib ada supaya cuma lokasi ini yang berubah, bukan semuanya!
            $stmt = $pdo->prepare("
                UPDATE lokasi 
                SET Nama_Gedung = :nama_gedung, Lantai = :lantai, Keterangan = :keterangan
                WHERE Lokasi_ID = :id
            ");
            
            // Masukkan ketikan baru ke dalam placeholder
            $stmt->execute([
                ':nama_gedung' => $nama_gedung,
                ':lantai'      => $lantai,
                ':keterangan'  => $keterangan ?: null,
                ':id'          => $id, // ID lokasi yang mau diubah
            ]);

            // Tampilkan notifikasi hijau
            set_flash('success', "Lokasi \"$nama_gedung\" Lt. $lantai berhasil diperbarui!");
            header('Location: index.php'); // Lemparkan balik ke daftar
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui data: ' . $e->getMessage();
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
        <div class="page-title">Edit Lokasi</div>
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
            <input type="text" name="nama_gedung" value="<?= h($old['Nama_Gedung'] ?? ($old['nama_gedung'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: Main Building, UC Tower, UC Plaza..."
                   required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">Lantai <span style="color:#ef4444">*</span></label>
            <input type="number" name="lantai" value="<?= h($old['Lantai'] ?? ($old['lantai'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: 1, 2, 3..." required min="-5" max="100">
        </div>

        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-textarea" placeholder="Detail lokasi spesifik (misal: Samping lift barang)…" rows="3"><?= h($old['Keterangan'] ?? ($old['keterangan'] ?? '')) ?></textarea>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Lokasi
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
