<?php
/* DESKRIPSI FILE: Halaman form untuk menambahkan data lokasi/gedung baru ke dalam sistem. */

// ===================================================
// INISIALISASI — Muat koneksi database dan atur variabel halaman
// ===================================================
require_once __DIR__ . '/../db.php'; // Hubungkan ke database lewat file db.php (berisi variabel $pdo)

$pageTitle  = 'Tambah Lokasi'; // Judul yang tampil di tab browser
$activeMenu = 'lokasi';        // Menandai menu 'lokasi' aktif di navigasi
define('ROOT', dirname(__DIR__)); // Simpan path folder induk sebagai konstanta ROOT

// ===================================================
// PERSIAPAN VARIABEL — Siapkan wadah untuk error dan data lama
// ===================================================
$errors = []; // Array kosong untuk menampung pesan error validasi (akan diisi jika ada yang salah)
$old    = []; // Array untuk menyimpan data yang sudah diketik user, supaya tidak hilang saat halaman reload

// ===================================================
// CEK METHOD — Proses hanya jika form dikirimkan (method POST)
// ===================================================
// $_SERVER['REQUEST_METHOD'] berisi method HTTP yang digunakan: 'GET' (buka halaman biasa) atau 'POST' (kirim form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan semua data form ke $old, supaya nilai di input tidak hilang jika ada error validasi
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
    // !$nama_gedung = true jika string kosong setelah di-trim (artinya user tidak mengisi apa-apa)
    if (!$nama_gedung) {
        $errors[] = 'Nama gedung wajib diisi.'; // Tambahkan pesan error ke array $errors
    }

    // Validasi 2: Lantai harus berada di rentang -5 sampai 100
    // Lantai bisa minus (contoh: basement = -1, -2)
    // Lantai tidak mungkin lebih dari 100 (gedung tertinggi di dunia ~160 lantai, kita batasi 100)
    if ($lantai < -5 || $lantai > 100) {
        $errors[] = 'Lantai harus antara -5 dan 100.'; // Tambahkan pesan error jika di luar batas
    }

    // ===================================================
    // SIMPAN KE DATABASE — Lanjutkan hanya jika tidak ada error
    // ===================================================
    // empty($errors) = true jika array $errors masih kosong (artinya semua validasi lolos)
    if (empty($errors)) {
        try {
            // [INSERT] Simpan data lokasi baru ke tabel 'lokasi'
            // Menggunakan parameter bernama (:nama_gedung, :lantai, :keterangan) untuk keamanan
            // — teknik ini disebut Prepared Statement, mencegah serangan SQL Injection
            $stmt = $pdo->prepare("
                INSERT INTO lokasi (Nama_Gedung, Lantai, Keterangan)
                VALUES (:nama_gedung, :lantai, :keterangan)
            ");
            // Jalankan query dengan memasukkan nilai nyata sebagai pengganti parameter di atas
            $stmt->execute([
                ':nama_gedung' => $nama_gedung,
                ':lantai'      => $lantai,
                ':keterangan'  => $keterangan ?: null, // Jika keterangan kosong, simpan NULL (bukan string kosong)
            ]);

            // Simpan pesan sukses ke sesi supaya bisa ditampilkan setelah redirect
            set_flash('success', "Lokasi \"$nama_gedung\" Lt. $lantai berhasil ditambahkan!");
            // Kirim pengguna kembali ke halaman daftar lokasi setelah berhasil simpan
            header('Location: index.php');
            exit; // Hentikan eksekusi PHP di sini supaya tidak ada kode yang jalan setelah redirect
        } catch (PDOException $e) {
            // Jika terjadi error saat menyimpan ke database (misalnya koneksi putus, constraint dilanggar)
            // PDOException menangkap error dari operasi database
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage(); // Tampilkan pesan error dari database
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
        <div class="page-title">Tambah Lokasi Baru</div>
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

<!-- Kartu form tambah lokasi, dibatasi lebar 680px agar nyaman dibaca -->
<div style="max-width:680px;">
<div class="card" style="padding:32px;">
    <!-- Form menggunakan method POST agar data dikirim ke server (bukan tampil di URL seperti GET) -->
    <form method="POST">
        <!-- Input Nama Gedung (wajib diisi, ditandai bintang merah *) -->
        <div class="form-group">
            <label class="form-label">Nama Gedung <span style="color:#ef4444">*</span></label>
            <!-- value="..." diisi dengan $old['nama_gedung'] supaya teks yang sudah diketik tidak hilang saat ada error -->
            <input type="text" name="nama_gedung" value="<?= h($old['nama_gedung'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: Main Building, UC Tower, UC Plaza..."
                   required maxlength="100"> <!-- required = wajib diisi (validasi browser), maxlength = batas 100 karakter -->
        </div>

        <!-- Input Lantai (wajib diisi, bertipe angka) -->
        <div class="form-group">
            <label class="form-label">Lantai <span style="color:#ef4444">*</span></label>
            <input type="number" name="lantai" value="<?= h($old['lantai'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: 1, 2, 3..." required min="-5" max="100">
                   <!-- min="-5" max="100" = validasi batas lantai di browser (sinkron dengan validasi PHP di atas) -->
        </div>

        <!-- Textarea Keterangan (opsional — boleh dikosongkan) -->
        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-textarea" placeholder="Detail lokasi spesifik (misal: Samping lift barang)…" rows="3"><?= h($old['keterangan'] ?? '') ?></textarea>
        </div>

        <!-- Tombol aksi: Simpan atau Batal -->
        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Lokasi
            </button>
            <!-- Tombol batal: kembali ke daftar lokasi tanpa menyimpan apapun -->
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Muat bagian bawah halaman (penutup </body> dan </html>) ?>
