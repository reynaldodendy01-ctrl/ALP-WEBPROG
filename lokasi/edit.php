<?php
/* DESKRIPSI FILE: Halaman form untuk mengubah rincian informasi lokasi/gedung yang sudah ada. */

// ===================================================
// INISIALISASI — Muat koneksi database dan atur variabel halaman
// ===================================================
require_once __DIR__ . '/../db.php'; // Hubungkan ke database lewat file db.php (berisi variabel $pdo)

$pageTitle  = 'Edit Lokasi'; // Judul yang tampil di tab browser
$activeMenu = 'lokasi';      // Menandai menu 'lokasi' aktif di navigasi
define('ROOT', dirname(__DIR__)); // Simpan path folder induk sebagai konstanta ROOT

// ===================================================
// AMBIL ID DARI URL — Baca ID lokasi yang ingin diedit
// ===================================================
// URL edit halaman ini bentuknya: edit.php?id=5
// $_GET['id'] mengambil nilai angka setelah '?id=' dari URL
// intval() memastikan nilainya berupa angka bulat (bukan teks atau karakter aneh)
// ?? 0 berarti: kalau tidak ada parameter 'id' di URL, pakai nilai default 0
$id = intval($_GET['id'] ?? 0);

// ===================================================
// [SELECT] Cek apakah lokasi dengan ID tersebut ada di database
// ===================================================
// Siapkan query dengan parameter aman (:id) supaya tidak rentan SQL Injection
$stmt = $pdo->prepare("SELECT * FROM lokasi WHERE Lokasi_ID = :id");
$stmt->execute([':id' => $id]); // Jalankan query dengan nilai $id yang sudah divalidasi
$location = $stmt->fetch();     // Ambil 1 baris data lokasi sebagai array (atau false jika tidak ditemukan)

// Jika lokasi tidak ditemukan (mungkin ID tidak valid atau data sudah dihapus)
if (!$location) {
    set_flash('error', 'Lokasi tidak ditemukan.'); // Simpan pesan error ke sesi
    // Kirim pengguna kembali ke daftar lokasi
    header('Location: index.php');
    exit; // Hentikan eksekusi PHP supaya kode di bawah tidak ikut jalan
}

// ===================================================
// PERSIAPAN VARIABEL — Siapkan wadah untuk error dan data awal form
// ===================================================
$errors = []; // Array kosong untuk pesan error validasi
$old    = $location; // Isi $old dengan data dari database — supaya form langsung terisi nilai lama

// ===================================================
// CEK METHOD — Proses hanya jika form dikirimkan (method POST)
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Timpa $old dengan data baru dari form supaya input user tidak hilang jika ada error validasi
    $old = $_POST;

    // ===================================================
    // AMBIL & BERSIHKAN DATA FORM — Ambil input dari user dan bersihkan
    // ===================================================
    $nama_gedung = trim($_POST['nama_gedung'] ?? ''); // trim() = buang spasi di awal dan akhir teks
    $lantai      = intval($_POST['lantai'] ?? 0);     // intval() = paksa jadi angka bulat, default 0 jika kosong
    $keterangan  = trim($_POST['keterangan'] ?? '');  // Keterangan boleh kosong (opsional)

    // ===================================================
    // VALIDASI FORM — Cek apakah data yang diisi sudah benar
    // ===================================================

    // Validasi 1: Nama gedung tidak boleh kosong
    // !$nama_gedung = true jika string kosong setelah di-trim
    if (!$nama_gedung) {
        $errors[] = 'Nama gedung wajib diisi.'; // Tambahkan pesan error ke array $errors
    }

    // Validasi 2: Lantai harus berada di rentang -5 sampai 100
    // Lantai bisa minus untuk basement (contoh: -1, -2)
    if ($lantai < -5 || $lantai > 100) {
        $errors[] = 'Lantai harus antara -5 dan 100.'; // Tambahkan pesan error jika di luar batas
    }

    // ===================================================
    // UPDATE KE DATABASE — Lanjutkan hanya jika tidak ada error
    // ===================================================
    // empty($errors) = true jika array $errors masih kosong (semua validasi lolos)
    if (empty($errors)) {
        try {
            // [UPDATE] Ubah data lokasi di tabel 'lokasi' berdasarkan Lokasi_ID yang sesuai
            // SET = kolom mana saja yang akan diubah nilainya
            // WHERE Lokasi_ID = :id = pastikan hanya baris dengan ID yang tepat yang diubah
            // (Tanpa WHERE, SEMUA baris di tabel akan ikut berubah — sangat berbahaya!)
            $stmt = $pdo->prepare("
                UPDATE lokasi 
                SET Nama_Gedung = :nama_gedung, Lantai = :lantai, Keterangan = :keterangan
                WHERE Lokasi_ID = :id
            ");
            // Jalankan query dengan memasukkan nilai nyata sebagai pengganti parameter
            $stmt->execute([
                ':nama_gedung' => $nama_gedung,
                ':lantai'      => $lantai,
                ':keterangan'  => $keterangan ?: null, // Jika keterangan kosong, simpan NULL bukan string kosong
                ':id'          => $id,                 // ID lokasi yang sedang diedit
            ]);

            // Simpan pesan sukses ke sesi supaya bisa ditampilkan setelah redirect
            set_flash('success', "Lokasi \"$nama_gedung\" Lt. $lantai berhasil diperbarui!");
            // Kirim pengguna kembali ke halaman daftar lokasi setelah berhasil update
            header('Location: index.php');
            exit; // Hentikan eksekusi PHP di sini
        } catch (PDOException $e) {
            // Jika terjadi error saat update ke database, tampilkan pesan errornya
            $errors[] = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
}

// ===================================================
// TAMPILAN — Muat bagian atas halaman (HTML head, navbar, sidebar)
// ===================================================
include __DIR__ . '/../_partials/layout_head.php'; // File ini berisi <html>, <head>, dan pembuka <body>
?>

<!-- Bagian judul halaman dan tombol kembali -->
<div class="page-header">
    <div>
        <!-- Tautan kembali ke halaman daftar lokasi -->
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Edit Lokasi</div>
    </div>
</div>

<?php if ($errors): ?>
<!-- Kotak merah berisi daftar pesan error — hanya tampil jika ada isi di array $errors -->
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): ?>
        <!-- Loop: tampilkan setiap pesan error satu per satu -->
        <div style="color:#dc2626;font-size:.875rem;display:flex;align-items:center;gap:6px;">
            <span class="mat-icon" style="font-size:16px">error</span> <?= h($e) ?> <!-- h() untuk keamanan output -->
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Kartu form edit lokasi, dibatasi lebar 680px agar nyaman dibaca -->
<div style="max-width:680px;">
<div class="card" style="padding:32px;">
    <!-- Form menggunakan method POST agar data dikirim ke server -->
    <form method="POST">
        <!-- Input Nama Gedung (wajib diisi) -->
        <div class="form-group">
            <label class="form-label">Nama Gedung <span style="color:#ef4444">*</span></label>
            <!-- Logika nilai input: coba ambil dari $old['Nama_Gedung'] (data DB, kapital)
                 jika tidak ada, coba dari $old['nama_gedung'] (data POST, huruf kecil)
                 Ini menangani dua kondisi: pertama buka halaman (dari DB) vs. setelah form gagal (dari POST) -->
            <input type="text" name="nama_gedung" value="<?= h($old['Nama_Gedung'] ?? ($old['nama_gedung'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: Main Building, UC Tower, UC Plaza..."
                   required maxlength="100">
        </div>

        <!-- Input Lantai (wajib diisi, bertipe angka) -->
        <div class="form-group">
            <label class="form-label">Lantai <span style="color:#ef4444">*</span></label>
            <!-- Sama seperti Nama Gedung: coba ambil dari data DB dulu, baru dari data POST -->
            <input type="number" name="lantai" value="<?= h($old['Lantai'] ?? ($old['lantai'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: 1, 2, 3..." required min="-5" max="100">
        </div>

        <!-- Textarea Keterangan (opsional — boleh dikosongkan) -->
        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <!-- Sama: coba 'Keterangan' dari DB, kalau tidak ada coba 'keterangan' dari POST -->
            <textarea name="keterangan" class="form-textarea" placeholder="Detail lokasi spesifik (misal: Samping lift barang)…" rows="3"><?= h($old['Keterangan'] ?? ($old['keterangan'] ?? '')) ?></textarea>
        </div>

        <!-- Tombol aksi: Perbarui atau Batal -->
        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Lokasi
            </button>
            <!-- Tombol batal: kembali ke daftar lokasi tanpa menyimpan perubahan -->
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Muat bagian bawah halaman (penutup </body> dan </html>) ?>
