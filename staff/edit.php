<?php
// =========================================================================
// FILE: staff/edit.php
// FUNGSI: Menampilkan Form Edit Staff & Menyimpan Perubahannya (UPDATE)
// =========================================================================

// 1. MEMANGGIL KONEKSI DATABASE
require_once __DIR__ . '/../db.php';

// 2. MENGATUR VARIABEL TAMPILAN UNTUK LAYOUT
$pageTitle  = 'Edit Staff';
$activeMenu = 'staff';
define('ROOT', dirname(__DIR__));

// 3. MENANGKAP ID DARI URL (Contoh link: edit.php?id=8)
// 'intval()' sangat penting untuk mengamankan data dari serangan web, memastikan $id benar-benar angka.
$id = intval($_GET['id'] ?? 0);

// 4. MENGAMBIL DATA STAFF LAMA DARI DATABASE
// Kita perlu mencari staff dengan ID tersebut agar kita bisa memunculkan data aslinya di form.
$stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Staff_ID = :id");
$stmt->execute([':id' => $id]);
$staff = $stmt->fetch(); // Mengambil satu baris data

// Jika staff-nya tidak ditemukan (misal ID-nya ngarang)
if (!$staff) {
    set_flash('error', 'Staff tidak ditemukan.');
    header('Location: index.php'); // Lemparkan balik ke halaman daftar staff
    exit;
}

// 5. PERSIAPAN VARIABEL
$errors = [];
$old    = $staff; // Isi $old dengan data asli staff tersebut. Nanti ditampilkan di form pakai value="<..."


// 6. MENANGKAP PERUBAHAN SAAT TOMBOL "PERBARUI" DIKLIK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Timpa $old dengan data ketikan baru (supaya ketikan user nggak hilang kalau ada error)
    $old = $_POST;

    // Bersihkan ketikan baru
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $no_telp  = trim($_POST['no_telp'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'Staff';
    $gedung   = $_POST['gedung'] ?? '';

    // --- PROSES VALIDASI (CEK SYARAT) ---
    if (!$nama) {
        $errors[] = 'Nama staff wajib diisi.';
    }
    if (!$email) {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.'; // PHP bisa tahu kalau emailnya nggak pakai @
    }
    if (!$no_telp) {
        $errors[] = 'No. telepon wajib diisi.';
    }
    // CEK KHUSUS PASSWORD BARU: Password boleh kosong. TAPI, kalau user mengetik sesuatu (ingin mengganti password),
    // maka ketikannya tidak boleh kurang dari 6 huruf.
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    }
    if (!in_array($role, ['Staff', 'Admin'])) {
        $errors[] = 'Role tidak valid.';
    }

    // Jika semua validasi lolos ($errors kosong), simpan pembaruannya!
    if (empty($errors)) {
        try {
            // ─── 7. CRUD (UPDATE) - MEMPERBARUI DATA STAFF DI MYSQL ───────────────────
            // Kita punya DUA skenario UPDATE di sini.
            
            // SKENARIO 1: Jika admin ngetik password baru (berarti ingin mengubah password)
            if ($password !== '') {
                // Acak dulu password barunya sebelum disimpan!
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE maintenance_staff 
                    SET Nama = :nama, Email = :email, No_Telp = :no_telp, Password = :password, Role = :role, Gedung = :gedung
                    WHERE Staff_ID = :id
                ");
                $stmt->execute([
                    ':nama'     => $nama,
                    ':email'    => $email,
                    ':no_telp'  => $no_telp,
                    ':password' => $hashedPassword, // Simpan password baru
                    ':role'     => $role,
                    ':gedung'   => $gedung ?: null,
                    ':id'       => $id, // WAJIB! Agar yang di-update cuma staff ini
                ]);
            } else {
                // SKENARIO 2: Jika kolom password dibiarkan kosong
                // Artinya: "Tolong perbarui Nama, Email, No_Telp, dll TANPA menyentuh Password lama!"
                // Kita hilangkan tulisan `Password = :password` dari perintah SQL-nya.
                $stmt = $pdo->prepare("
                    UPDATE maintenance_staff 
                    SET Nama = :nama, Email = :email, No_Telp = :no_telp, Role = :role, Gedung = :gedung
                    WHERE Staff_ID = :id
                ");
                $stmt->execute([
                    ':nama'    => $nama,
                    ':email'   => $email,
                    ':no_telp' => $no_telp,
                    ':role'    => $role,
                    ':gedung'  => $gedung ?: null,
                    ':id'      => $id,
                ]);
            }

            // Munculkan notifikasi sukses berwarna hijau
            set_flash('success', "Staff \"$nama\" berhasil diperbarui!");
            header('Location: index.php'); // Lemparkan balik ke daftar
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

        <div class="form-group">
            <label class="form-label">Area Gedung Tugas</label>
            <select name="gedung" class="form-select">
                <option value="">Semua Gedung</option>
                <option value="Main Building" <?= (($old['Gedung'] ?? ($old['gedung'] ?? '')) === 'Main Building') ? 'selected' : '' ?>>Main Building</option>
                <option value="UC Tower" <?= (($old['Gedung'] ?? ($old['gedung'] ?? '')) === 'UC Tower') ? 'selected' : '' ?>>UC Tower</option>
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
