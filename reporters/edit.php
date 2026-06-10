<?php
/**
 * =============================================================================
 * edit.php — Halaman Form Edit / Perbarui Data Pelapor
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menangani pengeditan data identitas seorang pelapor yang sudah
 *   terdaftar dalam sistem CariGalon. Admin dapat mengubah nama lengkap dan
 *   NIM pelapor melalui form yang sudah pre-filled dengan data saat ini.
 *   Sistem memvalidasi input dan memeriksa keunikan NIM hanya apabila NIM
 *   yang dimasukkan berbeda dari NIM lama, sehingga tidak terjadi error
 *   duplikasi saat admin menyimpan tanpa mengubah NIM.
 *
 * FUNGSI UTAMA:
 *   - Mengambil data pelapor berdasarkan Reporter_ID dari parameter GET
 *   - Menampilkan form edit yang pre-filled dengan data pelapor saat ini
 *   - Validasi: nama dan NIM wajib diisi
 *   - Pemeriksaan duplikasi NIM hanya bila NIM berubah dari nilai semula
 *   - Menjalankan UPDATE ke tabel reporter setelah validasi lulus
 *   - Redirect ke index.php dengan flash message sukses atau error
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk koneksi database dan helper functions
 *   2. Reporter_ID dibaca dari $_GET['id'] dan dikonversi ke integer
 *   3. Data pelapor diambil dari database; bila tidak ditemukan, redirect ke index
 *   4. $old diinisialisasi dengan data pelapor saat ini untuk pre-fill form
 *   5. Jika request adalah GET: form ditampilkan dengan data pelapor yang ada
 *   6. Jika request adalah POST:
 *      a. nama dan nim dibaca dari $_POST dan di-trim
 *      b. Validasi: nama dan nim tidak boleh kosong
 *      c. Bila NIM berubah: cek duplikasi NIM di tabel reporter
 *      d. Bila duplikasi: tambahkan error
 *      e. Bila semua valid: jalankan UPDATE reporter SET Nama, Nim WHERE Reporter_ID
 *      f. Set flash sukses, redirect ke index.php, exit
 *      g. Bila PDOException: tambahkan error teknis
 *   7. Bila ada error: form dirender ulang dengan pesan error dan nilai baru
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - reporter : SELECT untuk mengambil data pelapor; UPDATE untuk memperbarui nama & NIM
 *
 * VARIABEL PENTING:
 *   - $id       : Reporter_ID dari pelapor yang akan diedit (integer dari $_GET['id'])
 *   - $reporter : Array data pelapor yang ada dari database
 *   - $errors   : Array pesan validasi error
 *   - $old      : Array data yang digunakan untuk mengisi nilai form (dari DB atau $_POST)
 *   - $nama     : Nama baru pelapor (string, hasil trim dari $_POST['nama'])
 *   - $nim      : NIM baru pelapor (string, hasil trim dari $_POST['nim'])
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML dan script JS penutup
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin. Perubahan data pelapor tidak memengaruhi
 *   relasi water_report yang sudah ada karena menggunakan Reporter_ID sebagai FK.
 *
 * CATATAN PENGEMBANG:
 *   - $old menggunakan nilai dari $reporter (data DB) saat GET, dan dari $_POST saat POST
 *     Karena kunci array berbeda (kapital vs huruf kecil), form menggunakan null coalescing:
 *     $old['Nama'] ?? ($old['nama'] ?? '')
 *   - Hanya NIM yang berubah yang dicek duplikasinya, sehingga simpan tanpa ubah NIM aman
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Pelapor';
$activeMenu = 'reporters';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM reporter WHERE Reporter_ID = :id");
$stmt->execute([':id' => $id]);
$reporter = $stmt->fetch();

if (!$reporter) {
    set_flash('error', 'Pelapor tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = $reporter;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $nama = trim($_POST['nama'] ?? '');
    $nim  = trim($_POST['nim'] ?? '');

    if (!$nama) {
        $errors[] = 'Nama pelapor wajib diisi.';
    }
    if (!$nim) {
        $errors[] = 'NIM pelapor wajib diisi.';
    }

    // Check duplicate NIM if changed
    if ($nim && $nim !== $reporter['Nim']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reporter WHERE Nim = :nim");
        $stmt->execute([':nim' => $nim]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'NIM pelapor sudah terdaftar.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE reporter 
                SET Nama = :nama, Nim = :nim
                WHERE Reporter_ID = :id
            ");
            $stmt->execute([
                ':nama' => $nama,
                ':nim'  => $nim,
                ':id'   => $id,
            ]);

            set_flash('success', "Pelapor \"$nama\" berhasil diperbarui!");
            header('Location: index.php');
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
        <div class="page-title">Edit Pelapor</div>
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
            <label class="form-label">Nama Pelapor <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama" value="<?= h($old['Nama'] ?? ($old['nama'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: Ahmad Rizki"
                   required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">NIM <span style="color:#ef4444">*</span></label>
            <input type="text" name="nim" value="<?= h($old['Nim'] ?? ($old['nim'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: 0706012110001" required maxlength="20">
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Pelapor
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
