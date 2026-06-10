<?php
// =========================================================================
// FILE: dispensers/create.php
// FUNGSI: Menampilkan Form Tambah Dispenser Baru & Menyimpan Datanya ke MySQL
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
// require_once memastikan file 'db.php' dimuat. Ini wajib agar kita punya $pdo untuk ngobrol sama MySQL.
// '__DIR__' artinya lokasi folder file ini (dispensers/). '../' artinya mundur satu folder ke luar.
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL UNTUK TAMPILAN
// Variabel ini dibaca oleh file 'layout_head.php' nanti untuk mengubah judul tab browser
// dan menebalkan warna menu 'Dispenser' di bagian kiri (Sidebar).
$pageTitle  = 'Tambah Dispenser';
$activeMenu = 'dispensers';
define('ROOT', dirname(__DIR__)); // Menentukan posisi akar dari website kita

// 3. MENGAMBIL DATA UNTUK PILIHAN (DROPDOWN) LOKASI
// Sebelum form ditampilkan, kita butuh daftar lokasi (Gedung dan Lantai) dari database.
// $pdo->query(...) langsung menjalankan SQL dan mengambil 'fetchAll()' (semua baris) ke dalam $locationsList.
$locationsList = $pdo->query("SELECT Lokasi_ID, Nama_Gedung, Lantai FROM lokasi ORDER BY Nama_Gedung, Lantai")->fetchAll();

// 4. PERSIAPAN VARIABEL ERROR & DATA LAMA
$errors = []; // Variabel array (keranjang) kosong. Kalau ada yang salah (misal form kosong), pesannya masuk sini.
$old    = []; // Array untuk menyimpan data ketikan sebelumnya, supaya kalau error user nggak perlu ngetik ulang.

// 5. MENANGKAP PENGIRIMAN DATA (SAAT TOMBOL SUBMIT DIKLIK)
// $_SERVER['REQUEST_METHOD'] === 'POST' artinya kita mengecek: "Apakah user baru saja menekan tombol Submit?"
// Kalau belum (baru sekedar buka halaman), maka kode di dalam blok 'if' ini TIDAK AKAN DIJALANKAN.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Simpan semua ketikan user ke dalam variabel $old
    $old = $_POST;

    // Menangkap satu per satu data dari form HTML
    // intval() memaksa data menjadi Angka Bulat (mencegah diketik huruf)
    // trim() membuang spasi kosong di awal/akhir kata.
    $lokasi_id      = intval($_POST['lokasi_id'] ?? 0);
    $kode_dispenser = trim($_POST['kode_dispenser'] ?? '');
    $kategori       = $_POST['kategori'] ?? '';

    // --- PROSES VALIDASI (PENGECEKAN SYARAT) ---
    // Kalau $lokasi_id kosong (bernilai 0 atau belum milih), masukkan pesan ke keranjang $errors.
    if (!$lokasi_id) {
        $errors[] = 'Pilih lokasi dispenser.';
    }
    // Kalau kode dispenser belum diketik, masukkan pesan error.
    if (!$kode_dispenser) {
        $errors[] = 'Kode dispenser wajib diisi.';
    }
    // Cek apakah kategori yang dipilih masuk akal (hanya boleh 3 pilihan ini).
    // Ini buat mencegah hacker mengubah elemen HTML pilihan dropdown di browser mereka.
    if (!in_array($kategori, ['Normal', 'Hot & Cold', 'Hot, Cold & Normal'])) {
        $errors[] = 'Kategori tidak valid.';
    }

    // --- MENCEGAH DATA KEMBAR ---
    // Check duplicate Kode_Dispenser (Mencegah kode dispenser kembar di database)
    // Kalau user sudah ngetik kodenya, kita cek ke tabel 'dispenser' apakah kodenya sudah ada (COUNT(*)).
    if ($kode_dispenser) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dispenser WHERE Kode_Dispenser = :code");
        $stmt->execute([':code' => $kode_dispenser]);
        // fetchColumn() mengambil hasil hitungan. Kalau lebih dari 0, berarti kodenya sudah dipakai.
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Kode dispenser sudah terdaftar.';
        }
    }

    // Jika array $errors KOSONG (berarti lolos semua syarat validasi di atas), barulah kita simpan ke MySQL.
    if (empty($errors)) {
        try {
            // ─── CRUD (CREATE) - Menyimpan Data Baru ke MySQL ───────────────────
            // Kita menyiapkan query 'INSERT INTO' untuk menambah data ke tabel 'dispenser'.
            // Tanda ':lokasi_id' disebut 'placeholder', gunanya untuk melindungi kita dari serangan hacker (SQL Injection).
            $stmt = $pdo->prepare("
                INSERT INTO dispenser (Lokasi_ID, Kode_Dispenser, Kategori)
                VALUES (:lokasi_id, :kode_dispenser, :kategori)
            ");
            
            // Kita jalankan querynya dan mengganti placeholder dengan data asli yang kita dapatkan dari form
            $stmt->execute([
                ':lokasi_id'      => $lokasi_id,
                ':kode_dispenser' => $kode_dispenser,
                ':kategori'       => $kategori,
            ]);

            // Set pesan sukses dan pindahkan (redirect) pengguna kembali ke halaman daftar (index.php)
            set_flash('success', "Dispenser \"$kode_dispenser\" berhasil ditambahkan!");
            header('Location: index.php'); // Perintah untuk pindah halaman
            exit; // Stop proses PHP di sini agar tidak ada kode lain yang jalan
        } catch (PDOException $e) {
            // Jika terjadi kesalahan pada database saat menyimpan, akan ditangkap disini dan dimunculkan pesan gagalnya
            $errors[] = 'Gagal menyimpan dispenser: ' . $e->getMessage();
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
        <div class="page-title">Tambah Dispenser Baru</div>
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
            <input type="text" name="kode_dispenser" value="<?= h($old['kode_dispenser'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: DISP-MB-101"
                   required maxlength="50">
        </div>

        <div class="form-group">
            <label class="form-label">Lokasi <span style="color:#ef4444">*</span></label>
            <select name="lokasi_id" class="form-select" required>
                <option value="">— Pilih Lokasi —</option>
                <?php foreach ($locationsList as $l): ?>
                <option value="<?= $l['Lokasi_ID'] ?>" <?= ($old['lokasi_id'] ?? '') == $l['Lokasi_ID'] ? 'selected' : '' ?>>
                    <?= h($l['Nama_Gedung']) ?> - Lantai <?= h($l['Lantai']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Kategori <span style="color:#ef4444">*</span></label>
            <select name="kategori" class="form-select" required>
                <?php foreach (['Normal', 'Hot & Cold', 'Hot, Cold & Normal'] as $kat): ?>
                <option value="<?= $kat ?>" <?= ($old['kategori'] ?? 'Normal') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Dispenser
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
