<?php
/**
 * =============================================================================
 * dispensers/create.php — Halaman Tambah Dispenser Baru
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menyediakan form interaktif untuk mendaftarkan unit dispenser air
 *   baru ke dalam sistem CariGalon. Admin dapat mengisi kode unik dispenser,
 *   memilih lokasi gedung dan lantai dari daftar yang tersedia, serta menentukan
 *   kategori dispenser (Normal, Hot & Cold, atau Hot, Cold & Normal). Sebelum
 *   data disimpan, sistem melakukan validasi server-side termasuk pengecekan
 *   duplikasi kode dispenser untuk menjaga integritas data.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan form input untuk Kode Dispenser, Lokasi, dan Kategori
 *   - Memuat daftar lokasi (gedung + lantai) dari database sebagai opsi dropdown
 *   - Validasi server-side: field wajib, kategori valid, dan kode tidak duplikat
 *   - Menyimpan data dispenser baru ke tabel `dispenser` via INSERT
 *   - Menampilkan pesan error inline jika validasi gagal (dengan repopulasi form)
 *   - Redirect ke index.php dengan flash message sukses setelah berhasil disimpan
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk koneksi PDO dan helper functions
 *   2. Fetch semua lokasi dari tabel `lokasi` untuk mengisi dropdown pilihan lokasi
 *   3. Jika request adalah GET: render form kosong
 *   4. Jika request adalah POST: ambil & sanitasi input dari $_POST
 *   5. Jalankan validasi: cek kelengkapan field, validitas kategori, dan duplikasi kode
 *   6. Jika ada error: render ulang form beserta pesan error dan nilai input sebelumnya
 *   7. Jika valid: eksekusi INSERT ke tabel `dispenser`
 *   8. Set flash message sukses, redirect ke index.php
 *   9. Tangkap PDOException jika query gagal dan tampilkan pesan error
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - lokasi     : Dibaca untuk mengisi dropdown pilihan lokasi dispenser
 *   - dispenser  : Target INSERT data dispenser baru; juga dicek untuk validasi duplikasi kode
 *
 * VARIABEL PENTING:
 *   - $locationsList     : Array semua lokasi (Lokasi_ID, Nama_Gedung, Lantai) dari DB
 *   - $errors            : Array pesan error validasi; kosong = tidak ada error
 *   - $old               : Array berisi nilai input POST sebelumnya untuk repopulasi form
 *   - $lokasi_id         : ID lokasi yang dipilih dari dropdown (integer)
 *   - $kode_dispenser    : Kode unik dispenser yang diinput admin (string, maks 50 karakter)
 *   - $kategori          : Jenis dispenser: 'Normal', 'Hot & Cold', atau 'Hot, Cold & Normal'
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php          : Koneksi database PDO & helper functions (h(), set_flash())
 *   - layout_head.php : Header HTML, sidebar navigasi, dan stylesheet utama
 *   - layout_foot.php : Footer HTML, script JavaScript penutup halaman
 *
 * AKSES:
 *   Hanya bisa diakses oleh Admin yang sudah login.
 *
 * CATATAN PENGEMBANG:
 *   Pengecekan duplikasi Kode_Dispenser dilakukan dengan query COUNT terpisah sebelum
 *   INSERT untuk memberikan pesan error yang lebih deskriptif daripada mengandalkan
 *   constraint UNIQUE dari database. Pastikan kolom Kode_Dispenser di tabel dispenser
 *   tetap memiliki constraint UNIQUE sebagai lapisan keamanan kedua.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// INISIALISASI — Muat koneksi database dan setting halaman
// ===================================================
require_once __DIR__ . '/../db.php'; // Sertakan file koneksi database (wajib ada)

$pageTitle  = 'Tambah Dispenser'; // Judul halaman untuk browser
$activeMenu = 'dispensers';       // Highlight menu 'dispensers' di sidebar
define('ROOT', dirname(__DIR__)); // Simpan path folder utama sebagai konstanta

// ===================================================
// [SELECT] Ambil semua lokasi dari database untuk isian dropdown
// ===================================================
// Query ini mengambil ID, nama gedung, dan lantai dari tabel lokasi
// Hasilnya akan dipakai untuk membuat opsi <option> di form
$locationsList = $pdo->query("SELECT Lokasi_ID, Nama_Gedung, Lantai FROM lokasi ORDER BY Nama_Gedung, Lantai")->fetchAll();

// ===================================================
// PERSIAPAN VARIABEL — Siapkan array error dan data lama
// ===================================================
$errors = []; // Array kosong untuk menampung pesan error validasi
$old    = []; // Array kosong untuk menyimpan nilai form saat terjadi error (supaya tidak hilang)

// ===================================================
// PROSES FORM — Hanya dijalankan saat form dikirim (method POST)
// ===================================================
// Saat pertama kali halaman dibuka (GET), blok ini dilewati
// Saat pengguna klik tombol "Simpan", method berubah jadi POST dan blok ini dijalankan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST; // Simpan semua input POST ke $old agar bisa diisi ulang ke form jika ada error

    // Ambil dan bersihkan nilai input dari form
    $lokasi_id      = intval($_POST['lokasi_id'] ?? 0);       // Ubah ke angka integer, default 0 jika tidak ada
    $kode_dispenser = trim($_POST['kode_dispenser'] ?? '');   // Hapus spasi di awal/akhir kode dispenser
    $kategori       = $_POST['kategori'] ?? '';               // Ambil nilai kategori apa adanya

    // ===================================================
    // VALIDASI INPUT — Kenapa validasi penting?
    // ===================================================
    // Validasi mencegah data rusak masuk ke database.
    // Tanpa validasi, pengguna bisa menyimpan data kosong, salah format, atau duplikat.
    // Validasi di server (PHP) adalah WAJIB karena validasi di browser bisa dilewati.

    // Cek apakah lokasi sudah dipilih (bukan 0 = belum pilih)
    if (!$lokasi_id) {
        $errors[] = 'Pilih lokasi dispenser.'; // Tambahkan pesan error ke array
    }
    // Cek apakah kode dispenser sudah diisi (tidak kosong)
    if (!$kode_dispenser) {
        $errors[] = 'Kode dispenser wajib diisi.';
    }
    // Cek apakah kategori yang dipilih termasuk dalam daftar yang valid
    // in_array() = cek apakah $kategori ada di dalam array pilihan yang diizinkan
    if (!in_array($kategori, ['Normal', 'Hot & Cold', 'Hot, Cold & Normal'])) {
        $errors[] = 'Kategori tidak valid.'; // Kategori di luar pilihan resmi ditolak
    }

    // ===================================================
    // [SELECT] Cek apakah kode dispenser sudah terdaftar sebelumnya (duplikat)
    // ===================================================
    // Kita tidak mau 2 dispenser punya kode yang sama, jadi kita cek dulu ke database
    // COUNT(*) menghitung berapa baris yang punya kode sama — kalau > 0 berarti sudah ada
    if ($kode_dispenser) { // Hanya cek jika kode dispenser tidak kosong
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dispenser WHERE Kode_Dispenser = :code");
        $stmt->execute([':code' => $kode_dispenser]); // Jalankan query dengan kode yang diinput
        if ($stmt->fetchColumn() > 0) { // fetchColumn() ambil nilai kolom pertama (angka COUNT)
            $errors[] = 'Kode dispenser sudah terdaftar.'; // Ada duplikat — tolak penyimpanan
        }
    }

    // ===================================================
    // SIMPAN DATA — Hanya dijalankan jika tidak ada error
    // ===================================================
    if (empty($errors)) { // empty() = cek apakah array $errors kosong (tidak ada error)
        try { // try-catch = coba jalankan kode, kalau gagal tangkap errornya
            // [INSERT] Simpan data dispenser baru ke tabel dispenser
            // Gunakan prepared statement dengan parameter ':nama_param' untuk keamanan (anti SQL Injection)
            $stmt = $pdo->prepare("
                INSERT INTO dispenser (Lokasi_ID, Kode_Dispenser, Kategori)
                VALUES (:lokasi_id, :kode_dispenser, :kategori)
            ");
            $stmt->execute([
                ':lokasi_id'      => $lokasi_id,      // ID lokasi yang dipilih dari dropdown
                ':kode_dispenser' => $kode_dispenser, // Kode unik dispenser
                ':kategori'       => $kategori,       // Jenis dispenser (Normal / Hot & Cold / dll)
            ]);

            // Simpan pesan sukses ke sesi supaya bisa ditampilkan setelah redirect
            set_flash('success', "Dispenser \"$kode_dispenser\" berhasil ditambahkan!");
            // Kirim pengguna ke halaman daftar dispenser setelah berhasil menyimpan
            header('Location: index.php');
            exit; // Hentikan eksekusi PHP agar kode di bawah tidak jalan
        } catch (PDOException $e) {
            // Kalau query database gagal (misal: koneksi putus), tangkap errornya
            $errors[] = 'Gagal menyimpan dispenser: ' . $e->getMessage();
        }
    }
}

// ===================================================
// RENDER TAMPILAN — Tampilkan HTML halaman
// ===================================================
include __DIR__ . '/../_partials/layout_head.php'; // Muat header, sidebar, stylesheet
?>

<div class="page-header">
    <div>
        <a href="index.php" style="color:#9ca3af;font-size:.85rem;text-decoration:none;display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <span class="mat-icon" style="font-size:16px">arrow_back</span> Kembali
        </a>
        <div class="page-title">Tambah Dispenser Baru</div>
    </div>
</div>

<?php if ($errors): // Jika ada error validasi, tampilkan kotak merah berisi daftar error ?>
<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:14px;padding:14px 18px;margin-bottom:1.25rem;">
    <?php foreach ($errors as $e): // Loop dan tampilkan setiap pesan error ?>
        <div style="color:#dc2626;font-size:.875rem;display:flex;align-items:center;gap:6px;">
            <span class="mat-icon" style="font-size:16px">error</span> <?= h($e) ?><?php // h() agar teks error aman ditampilkan ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="max-width:680px;">
<div class="card" style="padding:32px;">
    <?php // Form dikirim ke halaman ini sendiri (action tidak diisi = self-submit) dengan method POST ?>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Kode Dispenser <span style="color:#ef4444">*</span></label><?php // Tanda * = wajib diisi ?>
            <?php // value="<?= h($old['kode_dispenser'] ?? '') ?>" = isi ulang nilai input jika ada error sebelumnya ?>
            <input type="text" name="kode_dispenser" value="<?= h($old['kode_dispenser'] ?? '') ?>"
                   class="form-input" placeholder="Contoh: DISP-MB-101"
                   required maxlength="50"><?php // required = wajib diisi browser; maxlength = maks 50 karakter ?>
        </div>

        <div class="form-group">
            <label class="form-label">Lokasi <span style="color:#ef4444">*</span></label>
            <select name="lokasi_id" class="form-select" required>
                <option value="">— Pilih Lokasi —</option>
                <?php foreach ($locationsList as $l): // Buat opsi dari daftar lokasi yang diambil dari DB ?>
                <?php // Cek apakah lokasi ini yang dipilih sebelumnya (saat error), kalau iya beri 'selected' ?>
                <option value="<?= $l['Lokasi_ID'] ?>" <?= ($old['lokasi_id'] ?? '') == $l['Lokasi_ID'] ? 'selected' : '' ?>>
                    <?= h($l['Nama_Gedung']) ?> - Lantai <?= h($l['Lantai']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Kategori <span style="color:#ef4444">*</span></label>
            <select name="kategori" class="form-select" required>
                <?php foreach (['Normal', 'Hot & Cold', 'Hot, Cold & Normal'] as $kat): // Tiga pilihan kategori tetap ?>
                <?php // Kalau belum pernah submit, default 'Normal' yang terpilih ?>
                <option value="<?= $kat ?>" <?= ($old['kategori'] ?? 'Normal') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Dispenser
            </button>
            <a href="index.php" class="btn-secondary">Batal</a><?php // Batal = kembali ke daftar tanpa menyimpan ?>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Muat footer dan penutup HTML ?>
