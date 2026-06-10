<?php
/**
 * =============================================================================
 * create.php — Halaman Form Tambah Pelapor Baru
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menyediakan halaman form untuk mendaftarkan pelapor baru secara
 *   manual oleh admin ke dalam sistem CariGalon. Pelapor adalah individu
 *   (mahasiswa, dosen, atau karyawan) yang mengajukan laporan kendala dispenser
 *   air di kampus. Data yang dicatat mencakup nama lengkap dan NIM pelapor.
 *   Sistem memeriksa keunikan NIM agar tidak terjadi duplikasi data pelapor
 *   sebelum menyimpan ke database.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan form input Nama dan NIM pelapor baru
 *   - Validasi input sisi server: nama dan NIM wajib diisi
 *   - Pemeriksaan duplikasi NIM sebelum INSERT ke database
 *   - Menyimpan data pelapor baru ke tabel reporter
 *   - Menampilkan pesan error yang jelas apabila validasi atau duplikasi NIM gagal
 *   - Redirect ke index.php dengan flash message sukses setelah berhasil
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk koneksi database dan helper functions
 *   2. Jika request adalah GET: form kosong ditampilkan
 *   3. Jika request adalah POST:
 *      a. Nilai nama dan nim dibaca dari $_POST dan di-trim
 *      b. Validasi: nama dan nim tidak boleh kosong
 *      c. Bila validasi lulus: cek apakah NIM sudah ada di tabel reporter
 *      d. Bila NIM sudah ada: tambahkan error "NIM sudah terdaftar"
 *      e. Bila NIM belum ada: jalankan INSERT ke tabel reporter
 *      f. Set flash message sukses, redirect ke index.php, exit
 *      g. Bila PDOException: tambahkan error teknis dari pesan exception
 *   4. Bila ada error: form dirender ulang dengan pesan error dan nilai lama ($old)
 *   5. layout_foot.php di-include untuk menutup halaman
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - reporter : SELECT untuk cek duplikasi NIM; INSERT untuk data pelapor baru
 *
 * VARIABEL PENTING:
 *   - $errors : Array pesan error validasi yang dikumpulkan sebelum INSERT
 *   - $old    : Array nilai $_POST yang dipertahankan untuk repopulate form
 *   - $nama   : Nama lengkap pelapor (string, hasil trim dari $_POST['nama'])
 *   - $nim    : NIM pelapor (string, hasil trim dari $_POST['nim'])
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php                    : Koneksi database PDO & helper functions
 *   - _partials/layout_head.php : Header HTML, sidebar navigasi, dan CSS global
 *   - _partials/layout_foot.php : Footer HTML dan script JS penutup
 *
 * AKSES:
 *   Hanya bisa diakses oleh admin. Pendaftaran pelapor manual diperlukan saat
 *   pelapor belum terdaftar atau perlu dikelola secara langsung dari panel admin.
 *
 * CATATAN PENGEMBANG:
 *   - NIM bersifat unik di tabel reporter; pastikan kolom Nim memiliki constraint UNIQUE
 *   - Maxlength form disesuaikan: nama maks 100 karakter, NIM maks 20 karakter
 *   - Pendaftaran pelapor juga dapat terjadi secara otomatis melalui API submit_report.php
 *     saat pengguna mengirim laporan dari halaman publik
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Tambah Pelapor';
$activeMenu = 'reporters';
define('ROOT', dirname(__DIR__));

$errors = [];
$old    = [];

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

    if (empty($errors)) {
        try {
            // Check if NIM already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reporter WHERE Nim = :nim");
            $stmt->execute([':nim' => $nim]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'NIM pelapor sudah terdaftar.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO reporter (Nama, Nim)
                    VALUES (:nama, :nim)
                ");
                $stmt->execute([
                    ':nama' => $nama,
                    ':nim'  => $nim,
                ]);

                set_flash('success', "Pelapor \"$nama\" berhasil ditambahkan!");
                header('Location: index.php');
                exit;
            }
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
        <div class="page-title">Tambah Pelapor Baru</div>
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
            <input type="text" name="nama" value="<?= h($old['nama'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: Ahmad Rizki"
                   required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">NIM <span style="color:#ef4444">*</span></label>
            <input type="text" name="nim" value="<?= h($old['nim'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: 0706012110001" required maxlength="20">
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Pelapor
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
