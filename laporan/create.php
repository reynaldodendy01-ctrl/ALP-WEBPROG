<?php
/**
 * =============================================================================
 * create.php — Halaman Form Pembuatan Laporan Kendala Baru (Manual oleh Admin)
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menyediakan halaman formulir bagi admin untuk membuat laporan kendala
 *   dispenser air secara manual tanpa harus melalui aplikasi mobile pelapor.
 *   Fungsi ini sangat berguna dalam situasi di mana laporan diterima secara lisan
 *   atau melalui saluran lain dan perlu diinput langsung oleh pihak admin ke sistem.
 *   File ini menangani seluruh siklus: menampilkan form, memvalidasi input, mengunggah
 *   foto pendukung (opsional), dan menyimpan data laporan baru ke tabel water_report.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan form input laporan kendala baru dengan field: pelapor, dispenser,
 *     kategori masalah, status awal, foto pendukung, dan deskripsi kendala.
 *   - Mendukung pra-seleksi dispenser melalui parameter GET (?dispenser_id=X) sehingga
 *     dapat dipanggil dari halaman dispenser secara langsung.
 *   - Memvalidasi semua input wajib (pelapor, dispenser, kategori, status) sebelum
 *     menyimpan data ke database.
 *   - Menangani upload foto pendukung dengan validasi ekstensi (jpg, jpeg, png, gif)
 *     dan batas ukuran file maksimum 5MB; file disimpan di folder /uploads/.
 *   - Menyimpan record laporan baru ke tabel water_report menggunakan prepared statement
 *     PDO untuk mencegah SQL injection.
 *   - Menampilkan kembali nilai input lama (old input) saat terjadi error validasi.
 *
 * ALUR KERJA (FLOW):
 *   1. db.php di-include untuk koneksi PDO dan helper functions.
 *   2. Daftar pelapor dan dispenser diambil dari database untuk mengisi dropdown form.
 *   3. Jika ada parameter GET dispenser_id, nilai tersebut diset ke $old untuk pra-seleksi.
 *   4. Jika request adalah POST, semua input diambil dan divalidasi satu per satu.
 *   5. Jika ada file foto yang diupload, ekstensi dan ukurannya divalidasi.
 *   6. File foto yang valid dipindahkan ke direktori /uploads/ dengan nama acak (md5+uniqid).
 *   7. Jika tidak ada error, record baru diinsert ke tabel water_report dengan PDO.
 *   8. Setelah berhasil, flash message sukses diset dan pengguna diredirect ke index.php.
 *   9. Jika ada error, form dirender ulang dengan pesan error dan nilai input lama.
 *  10. layout_foot.php di-include untuk menutup halaman.
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - water_report  : Tabel tujuan INSERT data laporan kendala baru.
 *   - reporter      : Sumber data daftar pelapor untuk dropdown (SELECT).
 *   - dispenser     : Sumber data daftar dispenser untuk dropdown (SELECT + JOIN lokasi).
 *   - lokasi        : Di-JOIN dengan dispenser untuk menampilkan nama gedung & lantai.
 *
 * VARIABEL PENTING:
 *   - $reportersList   : Array pelapor (Reporter_ID, Nama, Nim) untuk opsi dropdown.
 *   - $dispensersList  : Array dispenser (Dispenser_ID, Kode, Gedung, Lantai) untuk opsi dropdown.
 *   - $errors          : Array berisi pesan-pesan error validasi yang ditemukan.
 *   - $old             : Array nilai input lama untuk repopulasi form saat validasi gagal.
 *   - $foto_url        : String path relatif foto yang berhasil diupload (atau null jika tidak ada).
 *   - $reporter_id     : Integer ID pelapor yang dipilih dari form.
 *   - $dispenser_id    : Integer ID dispenser bermasalah yang dipilih dari form.
 *   - $kategori        : String kategori masalah ('Galon Kosong' atau 'Dispenser Rusak / Bocor').
 *   - $status          : String status awal laporan ('Pending', 'Diproses', 'Selesai', 'Ditolak').
 *   - $deskripsi_report: String deskripsi teks bebas dari kendala yang dilaporkan.
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php          : Koneksi database PDO & helper functions (h(), set_flash()).
 *   - layout_head.php : Header HTML, sidebar navigasi, dan pembuka konten utama.
 *   - layout_foot.php : Footer HTML dan script penutup halaman.
 *
 * AKSES:
 *   Hanya dapat diakses oleh Super Admin yang sudah login. Staff biasa tidak
 *   memiliki akses ke halaman pembuatan laporan manual ini karena laporan
 *   staff umumnya berasal dari sistem pelapor langsung.
 *
 * CATATAN PENGEMBANG:
 *   - Nama file foto di-generate menggunakan md5(uniqid(time(), true)) untuk
 *     menghindari collision dan menyembunyikan nama asli file dari pengguna.
 *   - Folder /uploads/ dibuat secara otomatis (mkdir) jika belum ada saat pertama kali upload.
 *   - Validasi status ('Pending', 'Diproses', 'Selesai', 'Ditolak') dilakukan di server-side
 *     untuk mencegah manipulasi nilai status melalui DevTools browser.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INISIALISASI — Muat koneksi database dan atur variabel halaman
// ===================================================
require_once __DIR__ . '/../db.php'; // Muat file koneksi database PDO dan fungsi-fungsi helper

$pageTitle  = 'Buat Laporan';  // Judul yang tampil di tab browser
$activeMenu = 'laporan';       // Tandai menu "laporan" di sidebar sebagai aktif
define('ROOT', dirname(__DIR__)); // ROOT = path absolut ke folder utama proyek

// ===================================================
// [SELECT] Ambil data pelapor untuk isian dropdown form
// ===================================================
// Daftar pelapor diurutkan berdasarkan nama agar mudah dicari
$reportersList = $pdo->query("SELECT Reporter_ID, Nama, Nim FROM reporter ORDER BY Nama")->fetchAll();

// ===================================================
// [SELECT] Ambil data dispenser untuk isian dropdown form
// ===================================================
// JOIN dengan tabel lokasi agar kita bisa tampilkan nama gedung dan lantai
// sehingga pengguna mudah mengenali dispenser mana yang bermasalah
$dispensersList = $pdo->query("
    SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
    FROM dispenser d 
    JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
    ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
")->fetchAll();

$errors = []; // Array kosong untuk menampung pesan error validasi
$old    = []; // Array kosong untuk menyimpan nilai input lama (supaya form tidak kosong saat error)

// ===================================================
// PRA-SELEKSI DISPENSER — Jika dipanggil dari halaman lain dengan parameter ?dispenser_id=X
// ===================================================
// Fitur ini memungkinkan halaman dispenser mengirim ID dispenser langsung ke form ini
// sehingga dropdown dispenser sudah terpilih otomatis
if (isset($_GET['dispenser_id'])) {
    $old['dispenser_id'] = intval($_GET['dispenser_id']); // intval() pastikan nilainya angka, bukan teks berbahaya
}

// ===================================================
// PROSES FORM POST — Jalankan hanya jika pengguna mengklik tombol "Simpan Laporan"
// ===================================================
// $_SERVER['REQUEST_METHOD'] memberitahu kita apakah halaman dibuka biasa (GET)
// atau dikunjungi karena form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan semua data yang dikirim dari form ke $old,
    // supaya kalau ada error, form bisa diisi ulang dengan nilai yang sudah diketik tadi
    $old = $_POST;

    // ===================================================
    // AMBIL DATA DARI FORM — Baca setiap field yang dikirim pengguna
    // ===================================================
    // intval() = ubah nilai ke angka bulat, mencegah karakter asing masuk sebagai ID
    // ?? 0 artinya: jika field tidak ada di POST, gunakan nilai default 0
    $reporter_id      = intval($_POST['reporter_id'] ?? 0);
    $dispenser_id     = intval($_POST['dispenser_id'] ?? 0);
    $kategori         = $_POST['kategori'] ?? '';                  // Ambil teks kategori apa adanya
    $status           = $_POST['status'] ?? 'Pending';             // Default status adalah 'Pending'
    $deskripsi_report = trim($_POST['deskripsi_report'] ?? '');    // trim() hapus spasi di awal/akhir

    // ===================================================
    // VALIDASI INPUT — Periksa setiap field wajib
    // ===================================================
    // Jika ada field yang tidak valid, tambahkan pesan error ke array $errors

    // Pelapor wajib dipilih (ID tidak boleh 0)
    if (!$reporter_id) {
        $errors[] = 'Pilih pelapor.';
    }
    // Dispenser wajib dipilih (ID tidak boleh 0)
    if (!$dispenser_id) {
        $errors[] = 'Pilih dispenser.';
    }
<<<<<<< Updated upstream
    if (!in_array($kategori, ['Galon Kosong', 'Dispenser Rusak', 'Kebocoran', 'Distribusi Tidak Merata', 'Lainnya'])) {
=======
    // Kategori harus salah satu dari dua nilai yang diizinkan
    // in_array() = cek apakah nilai ada di dalam array
    if (!in_array($kategori, ['Galon Kosong', 'Dispenser Rusak / Bocor'])) {
>>>>>>> Stashed changes
        $errors[] = 'Kategori masalah tidak valid.';
    }
    // Status harus salah satu dari empat nilai yang diizinkan (validasi server-side)
    if (!in_array($status, ['Pending', 'Diproses', 'Selesai', 'Ditolak'])) {
        $errors[] = 'Status tidak valid.';
    }
    if (!$deskripsi_report) {
        $errors[] = 'Deskripsi kendala wajib diisi.';
    }

    // ===================================================
    // PROSES UPLOAD FOTO — Tangani file foto yang diupload (opsional)
    // ===================================================
    $foto_url = null; // Default: tidak ada foto (null berarti kosong/tidak ada nilai)

    // Cek apakah ada file yang diupload DAN tidak ada error dari PHP saat menerima file tersebut
    // UPLOAD_ERR_OK = kode 0 = file diterima dengan sempurna
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['foto']['tmp_name']; // Path sementara file di server sebelum dipindahkan
        $fileName      = $_FILES['foto']['name'];     // Nama file asli yang diupload pengguna
        $fileSize      = $_FILES['foto']['size'];     // Ukuran file dalam satuan byte
        // pathinfo() ambil informasi file, PATHINFO_EXTENSION ambil ekstensinya saja (misal: jpg)
        // strtolower() ubah ke huruf kecil supaya 'JPG' dan 'jpg' dianggap sama
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Hanya format gambar ini yang diizinkan

        // Periksa apakah ekstensi file termasuk dalam daftar yang diizinkan
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "Ekstensi file tidak valid. Diperbolehkan: JPG, JPEG, PNG, GIF.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            // 5 * 1024 * 1024 = 5 MB dalam satuan byte
            // Jika ukuran file melebihi 5MB, tolak upload
            $errors[] = "Ukuran file terlalu besar. Maksimum 5MB.";
        } else {
            // File valid! Sekarang pindahkan ke folder uploads/
            $uploadFileDir = ROOT . '/uploads/'; // Path lengkap folder tujuan simpan foto

            // Buat folder uploads/ jika belum ada (0777 = semua punya izin baca/tulis, true = buat subfolder jika perlu)
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }

            // Buat nama file baru yang unik dan acak supaya tidak ada dua file dengan nama sama
            // md5(uniqid(time(), true)) = kombinasi waktu + angka unik yang di-hash menjadi string 32 karakter
            $newFileName = md5(uniqid(time(), true)) . '.' . $fileExtension;
            $dest_path   = $uploadFileDir . $newFileName; // Path lengkap tujuan file

            // Pindahkan file dari folder sementara ke folder uploads/
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Jika berhasil, simpan path relatif foto (bukan path absolut) untuk disimpan ke database
                $foto_url = 'uploads/' . $newFileName;
            } else {
                // Jika gagal dipindahkan, tambahkan pesan error
                $errors[] = "Gagal mengunggah foto. Silakan coba lagi.";
            }
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        // UPLOAD_ERR_NO_FILE = pengguna tidak memilih file (opsional, boleh kosong)
        // Jika error bukan karena "tidak ada file", berarti ada masalah lain
        $errors[] = "Terjadi kesalahan pada file upload: Kode " . $_FILES['foto']['error'];
    }

    // ===================================================
    // [INSERT] Simpan laporan baru ke database jika tidak ada error
    // ===================================================
    if (empty($errors)) { // empty() = true jika array $errors kosong (tidak ada error)
        try {
            // Siapkan query INSERT dengan parameter aman (mencegah SQL Injection)
            // Tanda :nama_kolom adalah placeholder yang akan diisi nanti dengan nilai nyata
            $stmt = $pdo->prepare("
                INSERT INTO water_report (Reporter_ID, Dispenser_ID, Kategori, Status, Deskripsi_Report, Foto_url, Reported_At)
                VALUES (:reporter_id, :dispenser_id, :kategori, :status, :deskripsi_report, :foto_url, NOW())
                -- NOW() = fungsi MySQL untuk mengisi waktu saat ini secara otomatis
            ");

            // Jalankan query dengan memasukkan nilai nyata menggantikan placeholder
            $stmt->execute([
                ':reporter_id'      => $reporter_id,
                ':dispenser_id'     => $dispenser_id,
                ':kategori'         => $kategori,
                ':status'           => $status,
                ':deskripsi_report' => $deskripsi_report,
                ':foto_url'         => $foto_url, // null jika tidak ada foto, path file jika ada
            ]);

            // Simpan pesan sukses ke sesi (flash message) untuk ditampilkan di halaman berikutnya
            set_flash('success', 'Laporan kendala berhasil dibuat!');
            // Kirim pengguna ke halaman daftar laporan setelah berhasil menyimpan
            header('Location: index.php');
            exit; // Hentikan eksekusi PHP di sini supaya tidak ada kode yang berjalan setelah redirect
        } catch (PDOException $e) {
            // Jika ada error dari database (contoh: kolom tidak ada, koneksi putus)
            // PDOException menangkap error tersebut dan kita simpan pesannya ke $errors
            $errors[] = 'Gagal menyimpan laporan: ' . $e->getMessage();
        }
    }
}

// ===================================================
// RENDER HALAMAN — Tampilkan form HTML
// ===================================================
// Jika ada error, form akan tampil ulang dengan nilai lama ($old) dan pesan error ($errors)
// Jika belum ada POST, form tampil kosong
include __DIR__ . '/../_partials/layout_head.php';
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Buat Laporan Kendala Baru</div>
    </div>
</div>

<?php if ($errors): // Jika ada error validasi, tampilkan kotak pesan error merah ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): // Loop tiap pesan error dan tampilkan satu per satu ?>
        <div style="color:#dc2626;font-size:.875rem;display:flex;align-items:center;gap:6px;">
            <span class="mat-icon" style="font-size:16px">error</span> <?= h($e) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:680px;">
<div class="card" style="padding:32px;">
    <?php // enctype="multipart/form-data" wajib ada jika form memiliki input upload file
    // Tanpa ini, file foto tidak akan terkirim ke PHP ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Pelapor <span style="color:#ef4444">*</span></label>
            <select name="reporter_id" class="form-select" required>
                <option value="">— Pilih Pelapor —</option>
                <?php foreach ($reportersList as $rep): // Tampilkan tiap pelapor sebagai opsi ?>
                <option value="<?= $rep['Reporter_ID'] ?>" <?= ($old['reporter_id'] ?? '') == $rep['Reporter_ID'] ? 'selected' : '' ?>>
                    <?php // Jika pelapor ini sudah dipilih sebelumnya (saat form di-submit ulang), tandai sebagai 'selected' ?>
                    <?= h($rep['Nama']) ?> (NIM: <?= h($rep['Nim']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Dispenser Bermasalah <span style="color:#ef4444">*</span></label>
            <select name="dispenser_id" class="form-select" required>
                <option value="">— Pilih Dispenser —</option>
                <?php foreach ($dispensersList as $d): // Tampilkan tiap dispenser dengan info gedung dan lantai ?>
                <option value="<?= $d['Dispenser_ID'] ?>" <?= ($old['dispenser_id'] ?? '') == $d['Dispenser_ID'] ? 'selected' : '' ?>>
                    <?= h($d['Nama_Gedung']) ?> Lt. <?= h($d['Lantai']) ?> - <?= h($d['Kode_Dispenser']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label class="form-label">Kategori Masalah <span style="color:#ef4444">*</span></label>
                <select name="kategori" class="form-select" required>
                    <option value="">— Pilih Masalah —</option>
                    <?php foreach (['Galon Kosong', 'Dispenser Rusak', 'Kebocoran', 'Distribusi Tidak Merata', 'Lainnya'] as $kat): ?>
                    <option value="<?= $kat ?>" <?= ($old['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444">*</span></label>
                <select name="status" class="form-select" required>
                    <?php foreach (['Pending', 'Diproses', 'Selesai', 'Ditolak'] as $st): ?>
                    <?php // Default 'Pending' karena laporan baru biasanya masuk dalam kondisi menunggu ?>
                    <option value="<?= $st ?>" <?= ($old['status'] ?? 'Pending') === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Upload Foto Pendukung (Opsional)</label>
            <?php // accept="image/*" membatasi file yang bisa dipilih hanya gambar (validasi di browser)
            // Tapi validasi sesungguhnya tetap dilakukan di server (PHP) karena browser bisa di-bypass ?>
            <input type="file" name="foto" accept="image/*" class="form-input">
        </div>

        <div class="form-group">
<<<<<<< Updated upstream
            <label class="form-label">Deskripsi Kendala <span style="color:#ef4444">*</span></label>
            <textarea name="deskripsi_report" class="form-textarea" placeholder="Tulis rincian kendala air dispenser di sini…" rows="4" required><?= h($old['deskripsi_report'] ?? '') ?></textarea>
=======
            <label class="form-label">Deskripsi Kendala (Opsional)</label>
            <?php // Nilai textarea diambil dari $old agar tidak hilang saat form di-submit ulang karena error ?>
            <textarea name="deskripsi_report" class="form-textarea" placeholder="Tulis rincian kendala air dispenser di sini…" rows="4"><?= h($old['deskripsi_report'] ?? '') ?></textarea>
>>>>>>> Stashed changes
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Laporan
            </button>
            <?php // Tombol Batal: kembali ke daftar laporan tanpa menyimpan apapun ?>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Tutup HTML halaman ?>
