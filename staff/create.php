<?php
/**
 * =============================================================================
 * staff/create.php — Halaman Tambah Akun Staff Baru
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menyediakan form lengkap untuk mendaftarkan akun pengguna baru ke
 *   dalam sistem CariGalon, baik dengan role Staff Maintenance maupun Super Admin.
 *   Admin mengisi nama lengkap, email, nomor telepon/WhatsApp, password, role akses,
 *   dan area gedung yang menjadi tanggung jawab staff tersebut. Password yang
 *   diinput akan di-hash menggunakan password_hash() dengan algoritma bcrypt
 *   sebelum disimpan, sehingga password tidak pernah tersimpan dalam bentuk plaintext.
 *
 * FUNGSI UTAMA:
 *   - Menampilkan form registrasi akun staff dengan field: nama, email, no_telp, password, role, gedung
 *   - Validasi server-side: cek field wajib, format email, panjang password minimum, dan role valid
 *   - Hashing password dengan password_hash() (PASSWORD_DEFAULT / bcrypt) sebelum INSERT
 *   - Menyimpan akun staff baru ke tabel `maintenance_staff` via INSERT
 *   - Repopulasi nilai form jika validasi gagal untuk menghindari user mengisi ulang
 *   - Redirect ke index.php dengan flash message sukses setelah berhasil disimpan
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk koneksi PDO dan helper functions
 *   2. Jika request GET: render form kosong dengan nilai default role 'Staff'
 *   3. Jika request POST: ambil dan trim semua input dari $_POST
 *   4. Validasi: nama wajib, email valid, no_telp wajib, password min 6 karakter, role valid
 *   5. Jika ada error: simpan ke $errors, render ulang form dengan nilai $old (repopulasi)
 *   6. Jika valid: hash password, lakukan INSERT ke tabel maintenance_staff
 *   7. Gedung diset ke NULL jika input kosong (berarti staff handle semua gedung)
 *   8. Set flash 'success', redirect ke index.php; tangkap PDOException jika gagal
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - maintenance_staff : Target INSERT data akun staff baru beserta password hash
 *
 * VARIABEL PENTING:
 *   - $errors           : Array pesan error validasi; jika kosong proses simpan dijalankan
 *   - $old              : Array nilai POST sebelumnya untuk mengisi ulang form saat error
 *   - $nama             : Nama lengkap staff (maks 100 karakter)
 *   - $email            : Alamat email unik staff; divalidasi format dengan FILTER_VALIDATE_EMAIL
 *   - $no_telp          : Nomor telepon/WhatsApp staff (maks 20 karakter)
 *   - $password         : Password plaintext (min 6 karakter); di-hash sebelum disimpan
 *   - $role             : Role akses: 'Staff' (default) atau 'Admin'
 *   - $gedung           : Area gedung tugas; string atau NULL jika staff menangani semua gedung
 *   - $hashedPassword   : Hasil password_hash() yang disimpan ke database
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php          : Koneksi database PDO & helper functions (h(), set_flash())
 *   - layout_head.php : Header HTML, sidebar navigasi, dan stylesheet utama
 *   - layout_foot.php : Footer HTML, script JavaScript penutup halaman
 *
 * AKSES:
 *   Hanya bisa diakses oleh Admin yang sudah login. Staff biasa tidak diizinkan
 *   membuat akun baru.
 *
 * CATATAN PENGEMBANG:
 *   Tidak ada pengecekan duplikasi email sebelum INSERT. Jika kolom Email memiliki
 *   constraint UNIQUE di database, error duplikasi akan ditangkap oleh catch
 *   PDOException dan ditampilkan ke user. Disarankan menambahkan pengecekan
 *   duplikasi email secara eksplisit seperti pola yang digunakan di dispensers/create.php.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Tambah Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $no_telp  = trim($_POST['no_telp'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'Staff';

    if (!$nama) {
        $errors[] = 'Nama staff wajib diisi.';
    }
    if (!$email) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (!$no_telp) {
        $errors[] = 'No. telepon wajib diisi.';
    }
    if (!$password) {
        $errors[] = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if (!in_array($role, ['Staff', 'Admin'])) {
        $errors[] = 'Role tidak valid.';
    }

    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_staff (Nama, Email, No_Telp, Password, Role)
                VALUES (:nama, :email, :no_telp, :password, :role)
            ");
            $stmt->execute([
                ':nama'     => $nama,
                ':email'    => $email,
                ':no_telp'  => $no_telp,
                ':password' => $hashedPassword,
                ':role'     => $role,
            ]);

            set_flash('success', "Staff \"$nama\" berhasil ditambahkan!");
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan staff: ' . $e->getMessage();
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
        <div class="page-title">Tambah Staff Baru</div>
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

<div style="max-width:560px;">
<div class="card" style="padding:32px;">
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Nama Lengkap <span style="color:#ef4444">*</span></label>
            <input type="text" name="nama" value="<?= h($old['nama'] ?? '') ?>"
                   class="form-input" placeholder="Nama lengkap staff" required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">Email <span style="color:#ef4444">*</span></label>
            <input type="email" name="email" value="<?= h($old['email'] ?? '') ?>"
                   class="form-input" placeholder="staff@uc.ac.id" required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">No. Telepon / WhatsApp <span style="color:#ef4444">*</span></label>
            <input type="text" name="no_telp" value="<?= h($old['no_telp'] ?? '') ?>"
                   class="form-input" placeholder="08xxxxxxxxxx" required maxlength="20">
        </div>

        <div class="form-group">
            <label class="form-label">Password <span style="color:#ef4444">*</span></label>
            <input type="password" name="password" class="form-input" 
                   placeholder="Password untuk login staff (min. 6 karakter)" required minlength="6">
        </div>

        <div class="form-group">
            <label class="form-label">Role Akses <span style="color:#ef4444">*</span></label>
            <select name="role" class="form-select" required>
                <option value="Staff" <?= ($old['role'] ?? 'Staff') === 'Staff' ? 'selected' : '' ?>>Staff Maintenance</option>
                <option value="Admin" <?= ($old['role'] ?? '') === 'Admin' ? 'selected' : '' ?>>Super Admin</option>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Simpan Staff
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
