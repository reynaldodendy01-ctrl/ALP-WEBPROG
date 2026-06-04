<?php
// login.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true) {
    header('Location: dashboard/index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM maintenance_staff WHERE Email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $staff = $stmt->fetch();

            if ($staff && password_verify($password, $staff['Password'])) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_id'] = $staff['Staff_ID'];
                $_SESSION['staff_name'] = $staff['Nama'];
                $_SESSION['staff_email'] = $staff['Email'];
                $_SESSION['staff_role'] = $staff['Role'];

                set_flash('success', "Selamat datang kembali, " . $staff['Nama'] . "!");
                header('Location: dashboard/index.php');
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Staff — CariGalon</title>
    <meta name="description" content="Halaman login khusus staff CariGalon Universitas Ciputra.">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0..1" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0058bc',
                        'primary-dark': '#003f8a',
                        darkbg: '#0b1f3a'
                    },
                    fontFamily: { inter: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glow-input:focus {
            border-color: #0058bc;
            box-shadow: 0 0 0 4px rgba(0, 88, 188, 0.25);
        }
    </style>
</head>
<body class="bg-[#071120] text-white min-h-screen flex items-center justify-center relative overflow-hidden px-4">
    <!-- Ambient Background Lights -->
    <div class="absolute w-[400px] h-[400px] rounded-full bg-blue-600/10 blur-[100px] -top-20 -left-20"></div>
    <div class="absolute w-[500px] h-[500px] rounded-full bg-indigo-500/10 blur-[120px] -bottom-20 -right-20"></div>

    <div class="w-full max-w-md z-10">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black tracking-tight text-white flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[36px] text-blue-500">water_drop</span>
                CariGalon
            </h1>
            <p class="text-slate-400 text-sm mt-2">Masuk untuk mengelola logistik air Universitas Ciputra</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-3xl p-8 shadow-2xl relative">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-[2px] bg-gradient-to-r from-transparent via-blue-500 to-transparent"></div>
            
            <h2 class="text-xl font-bold mb-6 text-slate-100 flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-slate-400">lock</span>
                Staff Login
            </h2>

            <?php if (!empty($error)): ?>
                <div class="flex items-start gap-3 p-4 mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-200 text-sm font-medium animate-pulse">
                    <span class="material-symbols-outlined text-[20px] shrink-0">error</span>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <?php render_flash(); ?>

            <form method="POST" action="login.php" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2" for="email">Email Staff</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                        <input type="email" id="email" name="email" value="<?= h($email) ?>" required
                               class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-700/50 rounded-xl text-sm text-white placeholder-slate-500 glow-input outline-none transition-all"
                               placeholder="nama.staff@uc.ac.id">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2" for="password">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock_open</span>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-700/50 rounded-xl text-sm text-white placeholder-slate-500 glow-input outline-none transition-all"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold py-3.5 px-6 rounded-xl text-sm shadow-lg hover:shadow-blue-500/10 active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">login</span>
                        Masuk Dashboard
                    </button>
                </div>
            </form>
        </div>

        <!-- Back to Homepage Link -->
        <div class="text-center mt-6">
            <a href="index.php" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 font-medium transition-colors">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                Kembali ke Beranda Publik
            </a>
        </div>
    </div>
</body>
</html>
