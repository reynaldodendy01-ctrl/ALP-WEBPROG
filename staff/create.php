<?php
// =========================================================================
// FILE: staff/create.php
// FUNGSI: Menampilkan Form Tambah Staff Baru & Menyimpan Datanya (CREATE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Tambah Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

// 3. PERSIAPAN VARIABEL ERROR & PENGINGAT KETIKAN
$errors = []; // Variabel array kosong untuk menampung pesan error jika user salah ketik.
$old    = []; // Menyimpan ketikan sebelumnya agar form tidak kosong melompong saat terjadi error.

// 4. MENANGKAP PENGIRIMAN DATA SAAT TOMBOL SUBMIT DIKLIK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST; // Timpa $old dengan data dari form

    // Bersihkan ketikan (hapus spasi depan-belakang)
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $no_telp  = trim($_POST['no_telp'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'Staff';
    $gedung   = $_POST['gedung'] ?? '';

    // --- PROSES VALIDASI (CEK SYARAT) ---
    // Pastikan field penting tidak kosong
    if (!$nama) {
        $errors[] = 'Nama staff wajib diisi.';
    }
    // Khusus email, kita pakai fitur canggih PHP (FILTER_VALIDATE_EMAIL)
    // untuk mengecek apakah ketikannya benar-benar format email (punya '@' dan titik).
    if (!$email) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (!$no_telp) {
        $errors[] = 'No. telepon wajib diisi.';
    }
    // Keamanan: Password tidak boleh terlalu pendek
    if (!$password) {
        $errors[] = 'Password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    // Hacker bisa memanipulasi dropdown 'role', pastikan nilainya cuma boleh 2 ini.
    if (!in_array($role, ['Staff', 'Admin'])) {
        $errors[] = 'Role tidak valid.';
    }

    // Jika tidak ada error ($errors kosong), maka simpan!
    if (empty($errors)) {
        try {
            // ─── 5. CRUD (CREATE) - MENYIMPAN STAFF KE MYSQL ───────────────────
            // password_hash() MENGACAK password asli menjadi teks aneh sebelum disimpan ke MySQL.
            // Ini WAJIB, agar jika database diretas, hacker tidak tahu apa password asli si staff.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Siapkan instruksi INSERT
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_staff (Nama, Email, No_Telp, Password, Role, Gedung)
                VALUES (:nama, :email, :no_telp, :password, :role, :gedung)
            ");
            
            // Masukkan data form untuk menggantikan placeholder (:nama, dll)
            $stmt->execute([
                ':nama'     => $nama,
                ':email'    => $email,
                ':no_telp'  => $no_telp,
                ':password' => $hashedPassword, // Simpan password yang sudah DIACAK
                ':role'     => $role,
                ':gedung'   => $gedung ?: null, // Jika bukan untuk 1 gedung tertentu, jadikan null
            ]);

            // Sukses! Lempar balik pengguna ke daftar staff.
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

        <div class="form-group">
            <label class="form-label">Area Gedung Tugas</label>
            <select name="gedung" class="form-select">
                <option value="">Semua Gedung</option>
                <option value="Main Building" <?= ($old['gedung'] ?? '') === 'Main Building' ? 'selected' : '' ?>>Main Building</option>
                <option value="UC Tower" <?= ($old['gedung'] ?? '') === 'UC Tower' ? 'selected' : '' ?>>UC Tower</option>
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
