<?php
// Memulai sesi (agar kita bisa mengecek memori server, apakah ada user yang sedang login)
session_start();

// Mengecek jika di dalam memori sesi ada kunci bernama 'staff_id' 
// Artinya: Staff ini sudah berhasil login sebelumnya.
if (isset($_SESSION['staff_id'])) {
    // Karena sudah login, jangan biarkan dia di halaman login ini lagi, lempar dia ke dashboard!
    header("Location: dashboard/index.php");
    exit; // Stop memproses sisa file ini.
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

            

            <!-- FORM LOGIN: Saat diklik (submit), datanya akan dikirim pakai metode 'POST' menuju file 'login_process.php' -->
            <!-- onsubmit="return handleLogin(event)" mengeksekusi kode Javascript tambahan di bawah layar sebelum data benar-benar pergi -->
            <form method="POST" action="login_process.php" onsubmit="return handleLogin(event)" class="space-y-5">
                <!-- Box berwarna merah ini akan muncul dari Javascript jika gagal login (awalnya display:none atau disembunyikan) -->
                <div id="error-box" style="display:none" class="flex items-start gap-3 p-4 mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-200 text-sm font-medium">
                    <span class="material-symbols-outlined text-[20px] shrink-0">error</span>
                    <span id="error-msg"></span>
                </div>
                
                <!-- BLOK INPUT EMAIL -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2" for="email">Email Staff</label>
                    <div class="relative">
                        <!-- name="email" adalah "nama variabel" yang nanti akan ditangkap oleh login_process.php pakai $_POST['email'] -->
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                        <input type="email" id="email" name="email" value="" required
                               class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-700/50 rounded-xl text-sm text-white placeholder-slate-500 glow-input outline-none transition-all"
                               placeholder="nama.staff@uc.ac.id">
                    </div>
                </div>

                <!-- BLOK INPUT PASSWORD -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2" for="password">Password</label>
                    <div class="relative">
                        <!-- name="password" adalah variabel yang ditangkap oleh login_process.php pakai $_POST['password'] -->
                        <!-- type="password" gunanya agar ketikan disensor jadi bulat-bulat hitam -->
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock_open</span>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-700/50 rounded-xl text-sm text-white placeholder-slate-500 glow-input outline-none transition-all"
                               placeholder="••••••••">
                    </div>
                </div>

                <!-- TOMBOL SUBMIT LOGIN -->
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
            <a href="index.html" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 font-medium transition-colors">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span>
                Kembali ke Beranda Publik
            </a>
        </div>
    </div>
    <script>
        // ─── BLOK JAVASCRIPT: Menangani tampilan pesan Error dan Pengecekan Lokasi Server
        
        // Membaca isi alamat URL. Jika alamatnya 'login.php?error=Salah%20password'
        const p = new URLSearchParams(location.search);
        if (p.get('error')) {
            // Maka kita memunculkan kotak warna merah (yang awalnya display:none)
            document.getElementById('error-box').style.display = 'flex';
            // Lalu kita ganti isinya dengan tulisan dari error di alamat link.
            document.getElementById('error-msg').textContent = decodeURIComponent(p.get('error'));
        }

        // Pengecekan Khusus Vercel:
        // Karena Vercel hanya hosting frontend tanpa database PHP aktif di project ini,
        // Kita cegah form dikirim jika tidak sedang dites secara offline pakai XAMPP.
        // 'localhost' atau '127.0.0.1' adalah tanda bahwa file ini dibuka via XAMPP/lokal.
        const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
        if (!isLocal) {
            document.getElementById('error-box').style.display = 'flex';
            document.getElementById('error-msg').textContent = 'Login hanya tersedia di server lokal. Versi ini adalah demo frontend saja.';
        }
        
        // Fungsi handleLogin() ini dicek setiap kali tombol submit ditekan.
        // Kalau return 'false', form tidak jadi terkirim ke login_process.php.
        // Kalau return 'true', form lolos dan PHP diizinkan memproses data.
        function handleLogin(e) {
            const isLocal = location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            if (!isLocal) {
                e.preventDefault(); // Mencegah pindah halaman
                document.getElementById('error-box').style.display = 'flex';
                document.getElementById('error-msg').textContent = 'Login hanya tersedia di server lokal. Versi ini adalah demo frontend saja.';
                return false; // Gagal form!
            }
            return true; // Sukses form jalan!
        }
    </script>
    
</body>
</html>