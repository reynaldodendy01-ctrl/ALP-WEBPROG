<?php
// File ini bertugas untuk memproses form login. (Bagian Authentication / Login)
// Di sinilah pengecekan email dan password dilakukan ke database.
require_once __DIR__ . '/db.php';

// Memulai "Sesi" (Session) PHP. Sesi ini seperti "kartu identitas sementara"
// yang mengingat bahwa kamu sudah login selama kamu belum menutup browser.
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    header('Location: dashboard/index.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.php?error=' . urlencode('Email dan password wajib diisi.'));
    exit;
}

try {
    // 1. Coba cari data staff di database yang emailnya sama dengan yang diketik
    $stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $staff = $stmt->fetch(); // Mengambil hasil pencarian

    // 2. Jika staff ketemu (tidak kosong), cek kecocokan passwordnya.
    // Kita menggunakan password_verify() karena password di database dienkripsi (diacak/di-hash),
    // jadi tidak bisa dicek pakai '==' biasa.
    if ($staff && password_verify($password, $staff['Password'])) {
        session_regenerate_id(true); // Keamanan tambahan, mengganti ID sesi yang lama
        
        // 3. Simpan data-data penting ke dalam $_SESSION supaya halaman lain (seperti dashboard) tahu kamu sudah login
        $_SESSION['staff_logged_in'] = true;
        $_SESSION['staff_id']        = $staff['Staff_ID'];
        $_SESSION['staff_name']      = $staff['Nama'];
        $_SESSION['staff_email']     = $staff['Email'];
        $_SESSION['staff_role']      = $staff['Role'];
        set_flash('success', 'Selamat datang kembali, ' . $staff['Nama'] . '!');
        header('Location: dashboard/index.php');
        exit;
    } else {
        header('Location: login.php?error=' . urlencode('Email atau password salah.'));
        exit;
    }
} catch (PDOException $e) {
    header('Location: login.php?error=' . urlencode('Terjadi kesalahan sistem.'));
    exit;
}
