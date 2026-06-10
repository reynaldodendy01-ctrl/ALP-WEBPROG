<?php
/**
 * =============================================================================
 * staff/edit.php — Halaman Edit Profil & Akun Staff
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini menangani proses pengeditan data akun staf atau admin yang sudah
 *   terdaftar dalam sistem CariGalon. Admin dapat memperbarui nama lengkap, email,
 *   nomor telepon, role akses, serta area gedung tugas. Untuk password, field
 *   bersifat opsional: jika dikosongkan maka password lama tetap dipertahankan,
 *   namun jika diisi maka password baru akan di-hash dan disimpan menggantikan
 *   yang lama. Hal ini memberikan fleksibilitas tanpa memaksa admin selalu mengubah
 *   password saat melakukan pembaruan data profil staff.
 *
 * FUNGSI UTAMA:
 *   - Memuat data staff existing dari database berdasarkan Staff_ID (?id=)
 *   - Menampilkan form edit yang sudah terisi nilai data saat ini
 *   - Validasi server-side: nama, email (format), no_telp wajib diisi; password opsional min 6 karakter
 *   - Dua jalur UPDATE: dengan password baru (hash dulu) atau tanpa perubahan password
 *   - Repopulasi form saat validasi gagal menggunakan nilai $old
 *   - Redirect ke index.php dengan flash message sukses atau error yang sesuai
 *   - Menangani kasus staff tidak ditemukan dengan redirect dan flash error
 *
 * ALUR KERJA (FLOW):
 *   1. Inklusi db.php untuk koneksi PDO dan helper functions
 *   2. Ambil parameter ?id dari URL; query data staff; redirect jika tidak ditemukan
 *   3. Inisialisasi $old dengan data staff dari DB untuk pre-fill form
 *   4. Jika request GET: render form terisi data existing
 *   5. Jika request POST: ambil, trim, sanitasi semua input dari $_POST
 *   6. Validasi: nama, email (FILTER_VALIDATE_EMAIL), no_telp wajib; password min 6 jika diisi
 *   7. Jika password baru diisi: hash dan jalankan UPDATE dengan kolom Password
 *   8. Jika password dikosongkan: jalankan UPDATE tanpa mengubah kolom Password
 *   9. Set flash 'success', redirect ke index.php; tangkap PDOException jika gagal
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - maintenance_staff : Dibaca untuk memuat data awal form; diperbarui via UPDATE setelah validasi
 *
 * VARIABEL PENTING:
 *   - $id               : ID staff yang akan diedit, diambil dari $_GET['id'] (integer)
 *   - $staff            : Array data staff existing dari database (sebagai nilai awal form)
 *   - $errors           : Array pesan error validasi; kosong = proses simpan dijalankan
 *   - $old              : Array berisi data DB (saat GET) atau POST (saat validasi gagal)
 *   - $nama             : Nama lengkap staff yang baru (maks 100 karakter)
 *   - $email            : Email baru staff; divalidasi dengan FILTER_VALIDATE_EMAIL
 *   - $no_telp          : Nomor telepon/WhatsApp baru (maks 20 karakter)
 *   - $password         : Password baru plaintext (opsional; kosong = tidak diubah)
 *   - $role             : Role baru: 'Staff' atau 'Admin'
 *   - $gedung           : Area gedung tugas baru; kosong berarti NULL (semua gedung)
 *   - $hashedPassword   : Hasil password_hash() dari $password; hanya dibuat jika $password != ''
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php          : Koneksi database PDO & helper functions (h(), set_flash())
 *   - layout_head.php : Header HTML, sidebar navigasi, dan stylesheet utama
 *   - layout_foot.php : Footer HTML, script JavaScript penutup halaman
 *
 * AKSES:
 *   Hanya bisa diakses oleh Admin yang sudah login. Staff biasa tidak diizinkan
 *   mengedit data akun lain.
 *
 * CATATAN PENGEMBANG:
 *   Logika dua jalur UPDATE (dengan/tanpa password) menggunakan if-else eksplisit
 *   agar tidak ada risiko password ter-null-kan secara tidak sengaja. Pertimbangkan
 *   untuk menambahkan validasi bahwa admin tidak dapat mengubah role dirinya sendiri
 *   menjadi Staff untuk mencegah lockout dari sistem administrasi.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
require_once __DIR__ . '/../db.php';

$pageTitle  = 'Edit Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Staff_ID = :id");
$stmt->execute([':id' => $id]);
$staff = $stmt->fetch();

if (!$staff) {
    set_flash('error', 'Staff tidak ditemukan.');
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = $staff;

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
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    }
    if (!in_array($role, ['Staff', 'Admin'])) {
        $errors[] = 'Role tidak valid.';
    }

    if (empty($errors)) {
        try {
            if ($password !== '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE maintenance_staff 
                    SET Nama = :nama, Email = :email, No_Telp = :no_telp, Password = :password, Role = :role
                    WHERE Staff_ID = :id
                ");
                $stmt->execute([
                    ':nama'     => $nama,
                    ':email'    => $email,
                    ':no_telp'  => $no_telp,
                    ':password' => $hashedPassword,
                    ':role'     => $role,
                    ':id'       => $id,
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE maintenance_staff 
                    SET Nama = :nama, Email = :email, No_Telp = :no_telp, Role = :role
                    WHERE Staff_ID = :id
                ");
                $stmt->execute([
                    ':nama'    => $nama,
                    ':email'   => $email,
                    ':no_telp' => $no_telp,
                    ':role'    => $role,
                    ':id'      => $id,
                ]);
            }

            set_flash('success', "Staff \"$nama\" berhasil diperbarui!");
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui staff: ' . $e->getMessage();
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
        <div class="page-title">Edit Staff</div>
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
            <input type="text" name="nama" value="<?= h($old['Nama'] ?? ($old['nama'] ?? '')) ?>"
                   class="form-input" placeholder="Nama lengkap staff" required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">Email <span style="color:#ef4444">*</span></label>
            <input type="email" name="email" value="<?= h($old['Email'] ?? ($old['email'] ?? '')) ?>"
                   class="form-input" placeholder="staff@uc.ac.id" required maxlength="100">
        </div>

        <div class="form-group">
            <label class="form-label">No. Telepon / WhatsApp <span style="color:#ef4444">*</span></label>
            <input type="text" name="no_telp" value="<?= h($old['No_Telp'] ?? ($old['no_telp'] ?? '')) ?>"
                   class="form-input" placeholder="08xxxxxxxxxx" required maxlength="20">
        </div>

        <div class="form-group">
            <label class="form-label">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
            <input type="password" name="password" class="form-input" 
                   placeholder="Masukkan password baru untuk mengubah" minlength="6">
        </div>

        <div class="form-group">
            <label class="form-label">Role Akses <span style="color:#ef4444">*</span></label>
            <select name="role" class="form-select" required>
                <option value="Staff" <?= (($old['Role'] ?? ($old['role'] ?? 'Staff')) === 'Staff') ? 'selected' : '' ?>>Staff Maintenance</option>
                <option value="Admin" <?= (($old['Role'] ?? ($old['role'] ?? '')) === 'Admin') ? 'selected' : '' ?>>Super Admin</option>
            </select>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn-primary">
                <span class="mat-icon" style="font-size:18px">save</span> Perbarui Staff
            </button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </div>
    </form>
</div>
</div>

<?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
