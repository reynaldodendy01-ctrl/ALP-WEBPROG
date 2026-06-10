<?php
// =========================================================================
// FILE: dispensers/edit.php
// FUNGSI: Menampilkan Form Edit Dispenser & Menyimpan Perubahan ke MySQL (UPDATE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Edit Dispenser';
$activeMenu = 'dispensers';
define('ROOT', dirname(__DIR__));

// 3. MENANGKAP ID DARI URL (Contoh link: edit.php?id=5)
// 'intval()' sangat penting untuk merubah isi '?id=5' benar-benar menjadi angka bulat '5', 
// bukan teks atau kode aneh yang diselipkan hacker.
$id = intval($_GET['id'] ?? 0);

// 4. MENGAMBIL DATA DISPENSER LAMA DARI DATABASE
// Kita perlu mencari dispenser dengan ID tersebut agar kita bisa memunculkan data lamanya di dalam kotak-kotak form HTML.
$stmt = $pdo->prepare("SELECT * FROM dispenser WHERE Dispenser_ID = :id");
$stmt->execute([':id' => $id]);
$dispenser = $stmt->fetch(); // 'fetch()' mengambil hanya SATU baris data (karena ID pasti unik).

// Jika dispensernya ternyata tidak ada (mungkin sudah dihapus orang lain atau salah ketik ID)
if (!$dispenser) {
    set_flash('error', 'Dispenser tidak ditemukan.'); // Tampilkan kotak pesan merah
    header('Location: index.php'); // Lemparkan kembali ke halaman daftar
    exit;
}

// Mengambil daftar lokasi (untuk menu pilihan/dropdown Gedung dan Lantai di HTML nanti)
$locationsList = $pdo->query("SELECT Lokasi_ID, Nama_Gedung, Lantai FROM lokasi ORDER BY Nama_Gedung, Lantai")->fetchAll();

// 5. PERSIAPAN VARIABEL ERROR & PENGINGAT FORM
$errors = [];
$old    = $dispenser; // Array '$old' kita isi dengan data asli dari database. Nanti akan ditampilkan di kotak input (value="<?=...").

// 6. MENANGKAP PERUBAHAN DATA SETELAH TOMBOL "PERBARUI" DIKLIK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Timpa $old dengan data ketikan baru (supaya kalau ada error, ketikan user nggak hilang)
    $old = $_POST;

    // Bersihkan data ketikan
    $lokasi_id      = intval($_POST['lokasi_id'] ?? 0);
    $kode_dispenser = trim($_POST['kode_dispenser'] ?? '');
    $kategori       = $_POST['kategori'] ?? '';

    // --- PROSES VALIDASI (CEK SYARAT) ---
    if (!$lokasi_id) {
        $errors[] = 'Pilih lokasi dispenser.';
    }
    if (!$kode_dispenser) {
        $errors[] = 'Kode dispenser wajib diisi.';
    }
    if (!in_array($kategori, ['Normal', 'Hot & Cold', 'Hot, Cold & Normal'])) {
        $errors[] = 'Kategori tidak valid.';
    }

    // Cek duplikat Kode Dispenser
    // Tapiii.. pastikan kodenya dicek KECUALI jika tidak diubah (sama seperti kode lamanya).
    // Karena kalau kodenya sama dengan dirinya sendiri, ya jelas terdeteksi "sudah ada".
    if ($kode_dispenser && $kode_dispenser !== $dispenser['Kode_Dispenser']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dispenser WHERE Kode_Dispenser = :code");
        $stmt->execute([':code' => $kode_dispenser]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Kode dispenser sudah terdaftar.';
        }
    }

    // Jika semua validasi lolos ($errors kosong), simpan pembaruannya!
    if (empty($errors)) {
        try {
            // ─── CRUD (UPDATE) - Memperbarui Data di MySQL ──────────────────────
            // Kita menggunakan perintah 'UPDATE ... SET ... WHERE ...'
            // 'WHERE Dispenser_ID = :id' SANGAT FATAL. Jika lupa ditaruh, 
            // maka SEMUA Ratusan Dispenser di database namanya akan berubah sama persis!
            $stmt = $pdo->prepare("
                UPDATE dispenser 
                SET Lokasi_ID = :lokasi_id, Kode_Dispenser = :kode_dispenser, Kategori = :kategori
                WHERE Dispenser_ID = :id
            ");
            
            // Mengirim data baru yang diketik user
            $stmt->execute([
                ':lokasi_id'      => $lokasi_id,
                ':kode_dispenser' => $kode_dispenser,
                ':kategori'       => $kategori,
                ':id'             => $id, // Penting! Jangan lupa ngirim ID-nya.
            ]);

            // Munculkan notifikasi hijau
            set_flash('success', "Dispenser \"$kode_dispenser\" berhasil diperbarui!");
            header('Location: index.php'); // Balik ke halaman utama dispenser
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui dispenser: ' . $e->getMessage();
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
        <div class="page-title">Edit Dispenser</div>
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
            <label class="form-label">Kode Dispenser <span style="color:#ef4444">*</span></label>
            <input type="text" name="kode_dispenser" value="<?= h($old['Kode_Dispenser'] ?? ($old['kode_dispenser'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: DISP-MB-101"
                   required maxlength="50">
        </div>

        <div class="form-group">
            <label class="form-label">Lokasi <span style="color:#ef4444">*</span></label>
            <select name="lokasi_id" class="form-select" required>
                <option value="">— Pilih Lokasi —</option>
                <?php foreach ($locationsList as $l): ?>
                <option value="<?= $l['Lokasi_ID'] ?>" <?= (($old['Lokasi_ID'] ?? ($old['lokasi_id'] ?? '')) == $l['Lokasi_ID']) ? 'selected' : '' ?>>
                    <?= h($l['Nama_Gedung']) ?> - Lantai <?= h($l['Lantai']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Kategori <span style="color:#ef4444">*</span></label>
            <select name="kategori" class="form-select" required>
                <?php foreach (['Normal', 'Hot & Cold', 'Hot, Cold & Normal'] as $kat): ?>
                <option value="<?= $kat ?>" <?= (($old['Kategori'] ?? ($old['kategori'] ?? 'Normal')) === $kat) ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Dispenser
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
