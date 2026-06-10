<?php
/**
 * =============================================================================
 * dispensers/edit.php — Halaman Edit Data Dispenser
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menangani proses pengeditan informasi sebuah unit dispenser yang
 *   sudah terdaftar dalam sistem CariGalon. Admin dapat memperbarui kode dispenser,
 *   mengubah lokasi pemasangan, serta mengubah kategori jenis dispenser. Data
 *   existing dimuat terlebih dahulu dari database menggunakan ID yang diterima
 *   lewat query string (?id=), dan form diisi otomatis dengan nilai tersebut
 *   sebagai nilai default untuk kenyamanan pengeditan.
 *
 * FUNGSI UTAMA:
 *   - Memuat data dispenser existing dari database berdasarkan Dispenser_ID (?id=)
 *   - Menampilkan form edit yang sudah terisi nilai data saat ini
 *   - Validasi server-side: field wajib, kategori valid, dan cek duplikasi kode jika diubah
 *   - Pengecekan duplikasi kode hanya dijalankan jika kode dispenser berubah dari nilai semula
 *   - Menyimpan perubahan ke tabel `dispenser` via UPDATE
 *   - Redirect ke index.php dengan flash message sukses setelah berhasil diperbarui
 *   - Redirect ke index.php dengan flash error jika dispenser tidak ditemukan (id tidak valid)
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk koneksi PDO dan helper functions
 *   2. Ambil parameter ?id dari URL, query data dispenser; redirect jika tidak ditemukan
 *   3. Fetch semua lokasi dari tabel `lokasi` untuk dropdown pilihan lokasi
 *   4. Jika request adalah GET: render form yang sudah terisi data dispenser saat ini
 *   5. Jika request adalah POST: ambil & sanitasi input dari $_POST
 *   6. Validasi: cek kelengkapan, validitas kategori, dan duplikasi kode (jika kode berubah)
 *   7. Jika ada error: render ulang form dengan pesan error dan nilai input POST
 *   8. Jika valid: eksekusi UPDATE pada tabel `dispenser` berdasarkan Dispenser_ID
 *   9. Set flash message sukses, redirect ke index.php
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - dispenser  : Dibaca untuk memuat data awal; diperbarui via UPDATE setelah validasi sukses
 *   - lokasi     : Dibaca untuk mengisi dropdown pilihan lokasi dispenser
 *
 * VARIABEL PENTING:
 *   - $id                : ID dispenser yang akan diedit, diambil dari $_GET['id']
 *   - $dispenser         : Array data dispenser existing dari database (nilai awal form)
 *   - $locationsList     : Array semua lokasi untuk dropdown; berisi Lokasi_ID, Nama_Gedung, Lantai
 *   - $errors            : Array pesan error validasi; kosong = tidak ada error
 *   - $old               : Array berisi data DB (GET) atau POST (setelah submit gagal) untuk repopulasi
 *   - $kode_dispenser    : Kode dispenser baru yang diinput; dicek duplikat hanya jika berbeda
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
 *   Variabel $old diinisialisasi dengan $dispenser (data DB) sehingga form langsung
 *   terisi saat pertama kali dibuka. Saat POST gagal validasi, $old diganti dengan
 *   $_POST agar input user sebelum error tidak hilang. Pola ini perlu dipertahankan
 *   secara konsisten di semua halaman edit.
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

$pageTitle  = 'Edit Dispenser'; // Judul halaman yang tampil di tab browser
$activeMenu = 'dispensers';     // Highlight menu 'dispensers' di sidebar navigasi
define('ROOT', dirname(__DIR__)); // Simpan path folder utama sebagai konstanta global

// ===================================================
// AMBIL ID & VALIDASI — Baca ID dispenser dari URL dan pastikan datanya ada
// ===================================================
// URL halaman ini: edit.php?id=5 — kita ambil angka 5 dari URL
// intval() mengubah teks menjadi angka integer; jika bukan angka hasilnya 0
$id = intval($_GET['id'] ?? 0); // Ambil ID dari URL (?id=...), default 0 jika tidak ada

// ===================================================
// [SELECT] Cari data dispenser berdasarkan ID
// ===================================================
$stmt = $pdo->prepare("SELECT * FROM dispenser WHERE Dispenser_ID = :id");
$stmt->execute([':id' => $id]); // Jalankan query dengan ID yang sudah divalidasi
$dispenser = $stmt->fetch();    // Ambil 1 baris hasil — false jika tidak ada

// Jika dispenser tidak ditemukan (ID tidak valid atau sudah dihapus)
if (!$dispenser) {
    set_flash('error', 'Dispenser tidak ditemukan.'); // Simpan pesan error ke sesi
    header('Location: index.php'); // Kirim pengguna kembali ke halaman daftar
    exit; // Hentikan eksekusi PHP — jangan tampilkan form edit
}

// ===================================================
// [SELECT] Ambil semua lokasi untuk dropdown pilihan lokasi
// ===================================================
$locationsList = $pdo->query("SELECT Lokasi_ID, Nama_Gedung, Lantai FROM lokasi ORDER BY Nama_Gedung, Lantai")->fetchAll();

// ===================================================
// PERSIAPAN VARIABEL — Siapkan array error dan data awal form
// ===================================================
$errors = []; // Array kosong untuk menampung pesan error validasi
// $old diisi dengan data dispenser dari database supaya form langsung terisi saat pertama dibuka
$old    = $dispenser; // Nilai awal form = data yang sudah ada di database

// ===================================================
// PROSES FORM — Hanya dijalankan saat form dikirim (method POST)
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST; // Ganti $old dengan input POST terbaru agar form tidak balik ke data lama saat ada error

    // Ambil dan bersihkan nilai input dari form
    $lokasi_id      = intval($_POST['lokasi_id'] ?? 0);     // ID lokasi pilihan, diubah ke integer
    $kode_dispenser = trim($_POST['kode_dispenser'] ?? ''); // Kode dispenser, spasi dihapus
    $kategori       = $_POST['kategori'] ?? '';             // Kategori dispenser

    // ===================================================
    // VALIDASI INPUT — Cek kelengkapan dan kevalidan data
    // ===================================================
    // Validasi penting agar data rusak/kosong tidak masuk ke database
    if (!$lokasi_id) {
        $errors[] = 'Pilih lokasi dispenser.'; // Lokasi belum dipilih
    }
    if (!$kode_dispenser) {
        $errors[] = 'Kode dispenser wajib diisi.'; // Kode kosong
    }
    // Cek apakah kategori termasuk pilihan yang valid
    if (!in_array($kategori, ['Normal', 'Hot & Cold', 'Hot, Cold & Normal'])) {
        $errors[] = 'Kategori tidak valid.'; // Nilai kategori di luar opsi yang diizinkan
    }

    // ===================================================
    // [SELECT] Cek duplikat kode — HANYA jika kode diubah dari nilai aslinya
    // ===================================================
    // Logika: kalau kode tidak berubah, tidak perlu cek duplikat (kode itu milik dispenser ini sendiri)
    // Tapi kalau kode diubah, cek dulu apakah kode baru sudah dipakai dispenser lain
    if ($kode_dispenser && $kode_dispenser !== $dispenser['Kode_Dispenser']) {
        // $kode_dispenser !== $dispenser['Kode_Dispenser'] = kode berubah dari aslinya
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dispenser WHERE Kode_Dispenser = :code");
        $stmt->execute([':code' => $kode_dispenser]); // Cari kode baru di database
        if ($stmt->fetchColumn() > 0) { // Kalau ditemukan (COUNT > 0) berarti duplikat
            $errors[] = 'Kode dispenser sudah terdaftar.';
        }
    }

    // ===================================================
    // SIMPAN PERUBAHAN — Hanya dijalankan jika tidak ada error
    // ===================================================
    if (empty($errors)) {
        try {
            // [UPDATE] Perbarui data dispenser di database berdasarkan ID
            // UPDATE mengubah baris yang sudah ada, berbeda dengan INSERT yang menambah baris baru
            $stmt = $pdo->prepare("
                UPDATE dispenser 
                SET Lokasi_ID = :lokasi_id, Kode_Dispenser = :kode_dispenser, Kategori = :kategori
                WHERE Dispenser_ID = :id
            ");
            $stmt->execute([
                ':lokasi_id'      => $lokasi_id,      // Lokasi baru yang dipilih
                ':kode_dispenser' => $kode_dispenser, // Kode dispenser yang baru (atau sama)
                ':kategori'       => $kategori,       // Kategori yang baru (atau sama)
                ':id'             => $id,             // ID dispenser yang sedang diedit — PENTING! Tanpa ini semua baris ter-update
            ]);

            set_flash('success', "Dispenser \"$kode_dispenser\" berhasil diperbarui!"); // Simpan pesan sukses ke sesi
            header('Location: index.php'); // Kirim pengguna ke halaman daftar
            exit; // Hentikan eksekusi setelah redirect
        } catch (PDOException $e) {
            // Kalau query UPDATE gagal, tangkap errornya dan tampilkan ke pengguna
            $errors[] = 'Gagal memperbarui dispenser: ' . $e->getMessage();
        }
    }
}

// ===================================================
// RENDER TAMPILAN — Muat header HTML dan tampilkan form
// ===================================================
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

<?php if ($errors): // Tampilkan kotak error merah jika ada pesan error ?>
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
            <?php
            // Cara pengisian nilai form yang cerdas:
            // - Saat pertama buka (GET): $old = $dispenser (data DB), ambil $old['Kode_Dispenser'] (huruf besar)
            // - Saat POST gagal validasi: $old = $_POST, ambil $old['kode_dispenser'] (huruf kecil — nama field form)
            // Operator ?? mencoba kunci pertama, kalau tidak ada coba kunci kedua, kalau tidak ada juga pakai ''
            ?>
            <input type="text" name="kode_dispenser" value="<?= h($old['Kode_Dispenser'] ?? ($old['kode_dispenser'] ?? '')) ?>"
                   class="form-input" placeholder="Contoh: DISP-MB-101"
                   required maxlength="50">
        </div>

        <div class="form-group">
            <label class="form-label">Lokasi <span style="color:#ef4444">*</span></label>
            <select name="lokasi_id" class="form-select" required>
                <option value="">— Pilih Lokasi —</option>
                <?php foreach ($locationsList as $l): // Tampilkan semua opsi lokasi ?>
                <?php
                // Cek lokasi mana yang seharusnya terpilih (selected):
                // Ambil dari $old['Lokasi_ID'] (data DB, saat GET) atau $old['lokasi_id'] (data POST, saat gagal)
                // == dipakai (bukan ===) karena tipe bisa berbeda (string vs integer)
                ?>
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
                <?php
                // Sama seperti lokasi: ambil dari $old['Kategori'] (data DB) atau $old['kategori'] (data POST)
                // Kalau dua-duanya tidak ada, gunakan 'Normal' sebagai default
                ?>
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

<?php include __DIR__ . '/../_partials/layout_foot.php'; // Muat footer dan penutup HTML ?>
