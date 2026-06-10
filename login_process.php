<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    header('Location: dashboard/index.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.html?error=' . urlencode('Email dan password wajib diisi.'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $staff = $stmt->fetch();

    if ($staff && password_verify($password, $staff['Password'])) {
        session_regenerate_id(true);
        $_SESSION['staff_logged_in'] = true;
        $_SESSION['staff_id']        = $staff['Staff_ID'];
        $_SESSION['staff_name']      = $staff['Nama'];
        $_SESSION['staff_email']     = $staff['Email'];
        $_SESSION['staff_role']      = $staff['Role'];
        set_flash('success', 'Selamat datang kembali, ' . $staff['Nama'] . '!');
        header('Location: dashboard/index.php');
        exit;
    } else {
        header('Location: login.html?error=' . urlencode('Email atau password salah.'));
        exit;
    }
} catch (PDOException $e) {
    header('Location: login.html?error=' . urlencode('Terjadi kesalahan sistem.'));
    exit;
}