<<<<<<< Updated upstream:index.php
<?php
require_once __DIR__ . '/db.php';
=======
<!-- DESKRIPSI FILE: Halaman utama (landing page) aplikasi untuk pengunjung publik yang berisi informasi ketersediaan galon dan form pelaporan. -->
>>>>>>> Stashed changes:index.html

// Handle public report submission
$report_success = null;
$report_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $nama = trim($_POST['nama'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $dispenser_id = intval($_POST['dispenser_id'] ?? 0);
    $kategori = $_POST['kategori'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!$nama || !$nim || !$dispenser_id || !$kategori || !$deskripsi) {
        $report_error = "Semua field bertanda * wajib diisi.";
    } else {
        $foto_url = null;
        $upload_ok = true;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileSize = $_FILES['foto']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $report_error = "Ekstensi file tidak valid. Diperbolehkan: JPG, JPEG, PNG, GIF.";
                $upload_ok = false;
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $report_error = "Ukuran file terlalu besar. Maksimum 5MB.";
                $upload_ok = false;
            } else {
                $uploadFileDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                $newFileName = md5(uniqid(time(), true)) . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $foto_url = 'uploads/' . $newFileName;
                } else {
                    $report_error = "Gagal mengunggah foto. Silakan coba lagi.";
                    $upload_ok = false;
                }
            }
        } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $report_error = "Terjadi kesalahan pada file upload: Kode " . $_FILES['foto']['error'];
            $upload_ok = false;
        }

        if ($upload_ok) {
            try {
                $pdo->beginTransaction();

                // Find or insert reporter
                $stmt = $pdo->prepare("SELECT Reporter_ID FROM reporter WHERE Nim = :nim");
                $stmt->execute([':nim' => $nim]);
                $reporter_id = $stmt->fetchColumn();

                if (!$reporter_id) {
                    $stmt = $pdo->prepare("INSERT INTO reporter (Nama, Nim) VALUES (:nama, :nim)");
                    $stmt->execute([':nama' => $nama, ':nim' => $nim]);
                    $reporter_id = $pdo->lastInsertId();
                } else {
                    $stmt = $pdo->prepare("UPDATE reporter SET Nama = :nama WHERE Reporter_ID = :reporter_id");
                    $stmt->execute([':nama' => $nama, ':reporter_id' => $reporter_id]);
                }

                // Insert water report
                $stmt = $pdo->prepare("
                    INSERT INTO water_report (Reporter_ID, Dispenser_ID, Kategori, Status, Deskripsi_Report, Foto_url, Reported_At)
                    VALUES (:reporter_id, :dispenser_id, :kategori, 'Pending', :deskripsi, :foto_url, NOW())
                ");
                $stmt->execute([
                    ':reporter_id' => $reporter_id,
                    ':dispenser_id' => $dispenser_id,
                    ':kategori' => $kategori,
                    ':deskripsi' => $deskripsi,
                    ':foto_url' => $foto_url
                ]);

                $pdo->commit();
                $report_success = "Laporan berhasil dikirim! Staff kami akan segera menindaklanjuti.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $report_error = "Gagal mengirim laporan: " . $e->getMessage();
            }
        }
    }
}

// Fetch dynamic stats for landing page
try {
    $total_dispensers = $pdo->query("SELECT COUNT(*) FROM dispenser")->fetchColumn();
    $total_gedung = $pdo->query("SELECT COUNT(DISTINCT Nama_Gedung) FROM lokasi")->fetchColumn();
    
    // Fetch dispensers list for reporting form dropdown
    $dispensersList = $pdo->query("
        SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
        FROM dispenser d 
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
        ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
    ")->fetchAll();
} catch (PDOException $e) {
    $total_dispensers = 5; 
    $total_gedung = 2;
    $dispensersList = [];
}
?>
<!DOCTYPE html>
<html class="light" lang="id">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>CariGalon - Pantau & Kelola Air Kampus UC</title>
    <meta name="description" content="Platform real-time memantau status galon, melaporkan kerusakan dispenser, dan memastikan distribusi air merata di Universitas Ciputra.">

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#0058bc",
                        background: "#f7f9fb",
                        surface: "#f7f9fb",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f2f4f6",
                        "surface-container": "#eceef0",
                        "surface-container-high": "#e6e8ea",
                        "surface-container-highest": "#e0e3e5",
                        "surface-variant": "#e0e3e5",
                        secondary: "#50616b",
                        tertiary: "#545c72",
                        outline: "#717786",
                        "outline-variant": "#c1c6d7",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#414755",
                        "on-primary": "#ffffff",
                        "secondary-container": "#d3e5f1",
                    },

                    fontFamily: {
                        inter: ["Inter", "sans-serif"]
                    },

                    maxWidth: {
                        container: "1280px"
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f7f9fb;
            color: #191c1e;
            -webkit-font-smoothing: antialiased;
        }

        .material-symbols-outlined {
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 24;
        }

        .ambient-shadow {
            box-shadow: 0 30px 60px -12px rgba(0, 91, 193, 0.08);
        }

        .fade-in {
            animation: fadeIn 1s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .blend-image {
            mix-blend-mode: multiply;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid #c1c6d7;
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
        }

        .stat-item {
            padding: 1.75rem 1.5rem;
            text-align: center;
            border-right: 1px solid #e0e3e5;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-num {
            font-size: 2rem;
            font-weight: 800;
            color: #0058bc;
            line-height: 1;
            margin-bottom: 0.35rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #414755;
            font-weight: 500;
        }

        .bento-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1rem;
            margin-top: 3rem;
        }

        .bento-a {
            grid-column: span 5;
        }

        .bento-b {
            grid-column: span 7;
        }

        .bento-c,
        .bento-d,
        .bento-e {
            grid-column: span 4;
        }

        @media (max-width: 768px) {

            .bento-a,
            .bento-b,
            .bento-c,
            .bento-d,
            .bento-e {
                grid-column: span 12;
            }

            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-item:nth-child(2) {
                border-right: none;
            }

            .stat-item:nth-child(3),
            .stat-item:nth-child(4) {
                border-top: 1px solid #e0e3e5;
            }
        }

        @media (max-width: 640px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }

            .stat-item {
                border-right: none;
                border-bottom: 1px solid #e0e3e5;
            }

            .stat-item:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header id="top-app-bar"
        class="fixed top-0 left-0 w-full z-50 bg-white/80 backdrop-blur-md h-20 flex items-center transition-all duration-300 border-b border-transparent">

        <div class="max-w-container mx-auto w-full px-6 lg:px-16 flex justify-between items-center">

            <div class="flex items-center gap-12">

                <div class="text-3xl font-black tracking-tight text-primary cursor-pointer" onclick="location.href='#'">
                    CariGalon
                </div>

                <nav class="hidden md:flex items-center gap-8">
                    <a class="text-sm text-on-surface-variant hover:text-primary transition-colors" href="#features">
                        Fitur Utama
                    </a>

                    <a class="text-sm text-on-surface-variant hover:text-primary transition-colors" href="dispensers/index.php">
                        Daftar Dispenser
                    </a>

                    <a class="text-sm text-on-surface-variant hover:text-primary transition-colors" href="galon/index.php">
                        Stok Galon
                    </a>

                    <a class="text-sm text-on-surface-variant hover:text-primary transition-colors" href="laporan/index.php">
                        Laporan Masalah
                    </a>
                </nav>

            </div>

            <div class="flex items-center gap-6">

                <a href="dashboard/index.php" class="hidden md:block text-sm text-on-surface-variant hover:text-primary transition-colors font-medium">
                    Dashboard Admin
                </a>

                <a href="dashboard/index.php"
                    class="bg-primary text-white text-sm font-semibold px-6 py-3 rounded-full hover:shadow-lg hover:bg-blue-700 active:scale-95 transition-all text-center">
                    Masuk Platform
                </a>

            </div>

        </div>

    </header>

    <!-- MAIN -->
    <main class="pt-32 pb-32">

        <!-- HERO -->
        <section class="max-w-container mx-auto px-6 lg:px-16 mb-20 text-center relative overflow-hidden">

            <div class="relative z-10 fade-in">

                <!-- Badge -->
                <div
                    class="inline-flex items-center gap-2 bg-secondary-container text-primary border border-outline-variant/40 rounded-full px-4 py-2 text-xs mb-6 tracking-widest uppercase font-semibold">

                    <span id="radioIcon" class="material-symbols-outlined text-[14px] transition-all duration-300">
                    radio_button_checked
                </span>

                    Live monitoring aktif — Universitas Ciputra

                </div>

                <!-- Heading -->
                <h1 class="text-5xl lg:text-7xl font-black tracking-tight mb-6 mx-auto max-w-[900px] leading-tight">
                    Kelola air kampus dari
                    <span class="text-primary">
                        satu dashboard
                    </span>.
                </h1>

                <!-- Description -->
                <p class="text-lg text-on-surface-variant mb-12 mx-auto max-w-[640px] leading-relaxed">
                    Platform real-time untuk memantau status galon,
                    melaporkan kerusakan dispenser,
                    dan memastikan distribusi air merata di seluruh gedung UC.
                </p>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-16">

                    <a href="dispensers/index.php"
                        class="bg-primary text-white px-10 py-5 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-1 active:scale-95 transition-all font-semibold min-w-[220px] inline-flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">water_drop</span> Cek Status Dispenser
                    </a>

                    <a href="#buat-laporan"
                        class="bg-white border border-outline-variant text-on-surface px-10 py-5 rounded-full hover:border-primary/30 hover:-translate-y-1 active:scale-95 transition-all font-semibold min-w-[220px] inline-flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">edit_note</span> Buat Laporan Kerusakan
                    </a>

                </div>

                <!-- Hero Image -->
                <div class="relative flex justify-center items-center h-[460px]">

                    <img alt="Large blue water gallon"
                        class="h-[520px] md:w-[420px] object-contain blend-image fade-in"
                        src="air.gif">

                </div>

            </div>

        </section>

        <!-- STATS -->
        <section class="max-w-container mx-auto px-6 lg:px-16 mb-32 fade-in">

            <div class="stats-bar ambient-shadow">

                <div class="stat-item">
                    <div class="stat-num"><?= htmlspecialchars($total_dispensers) ?></div>
                    <div class="stat-label">Dispenser Terpantau</div>
                </div>

                <div class="stat-item">
                    <div class="stat-num"><?= htmlspecialchars($total_gedung) ?></div>
                    <div class="stat-label">Gedung Dipetakan</div>
                </div>

                <div class="stat-item">
                    <div class="stat-num">98%</div>
                    <div class="stat-label">Uptime Monitoring</div>
                </div>

                <div class="stat-item">
                    <div class="stat-num">&lt;15m</div>
                    <div class="stat-label">Rata-rata Respons</div>
                </div>

            </div>

        </section>

        <!-- FEATURES -->
        <section id="features" class="max-w-container mx-auto px-6 lg:px-16 fade-in scroll-mt-24">

            <div class="mb-3">
                <span class="text-xs uppercase tracking-[0.2em] font-bold text-primary">
                    Fitur Utama
                </span>
            </div>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">

                <h2 class="text-4xl lg:text-5xl font-black tracking-tight max-w-[560px] leading-tight">
                    Semua yang kamu butuhkan dalam satu platform.
                </h2>

                <p class="text-on-surface-variant max-w-[360px] leading-relaxed">
                    Dari pemantauan real-time hingga manajemen staff,
                    CariGalon menjawab setiap kebutuhan logistik air kampus.
                </p>

            </div>

            <!-- Bento Grid -->
            <div class="bento-grid">

                <!-- A -->
                <div onclick="location.href='dispensers/index.php'"
                    class="bento-a bg-white rounded-3xl ambient-shadow border border-outline-variant/30 hover:border-primary/20 hover:-translate-y-1 transition-all group p-10 cursor-pointer">

                    <div
                        class="w-14 h-14 bg-secondary-container text-primary rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                        <span class="material-symbols-outlined text-[32px]">
                            sensors
                        </span>

                    </div>

                    <h3 class="text-3xl font-bold mb-4">
                        Status Dispenser Real-Time
                    </h3>

                    <p class="text-on-surface-variant leading-relaxed">
                        Pantau kondisi setiap dispenser secara langsung.
                        Data diperbarui otomatis sehingga kamu selalu tahu
                        mana yang perlu diisi ulang.
                    </p>

                </div>

                <!-- B -->
                <div onclick="location.href='galon/index.php'"
                    class="bento-b bg-white rounded-3xl ambient-shadow border border-outline-variant/30 hover:border-primary/20 hover:-translate-y-1 transition-all group p-10 cursor-pointer">

                    <div
                        class="w-14 h-14 bg-secondary-container text-primary rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                        <span class="material-symbols-outlined text-[32px]">
                            bar_chart
                        </span>

                    </div>

                    <h3 class="text-3xl font-bold mb-4">
                        Stok Galon & Analitik
                    </h3>

                    <p class="text-on-surface-variant leading-relaxed">
                        Pantau tingkat ketersediaan air pada dispenser dan pastikan pasokan air tidak pernah habis
                        dengan progress bar persentase stok visual.
                    </p>

                </div>

                <!-- C -->
                <div onclick="location.href='laporan/create.php'"
                    class="bento-c bg-white rounded-3xl ambient-shadow border border-outline-variant/30 hover:border-primary/20 hover:-translate-y-1 transition-all group p-10 cursor-pointer">

                    <div
                        class="w-14 h-14 bg-secondary-container text-primary rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                        <span class="material-symbols-outlined text-[32px]">
                            edit_note
                        </span>

                    </div>

                    <h3 class="text-2xl font-bold mb-4">
                        Laporan Cepat
                    </h3>

                    <p class="text-on-surface-variant leading-relaxed">
                        Mahasiswa dan staff bisa langsung melaporkan dispenser rusak atau bocor hanya dalam beberapa ketukan.
                    </p>

                </div>

                <!-- D -->
                <div onclick="location.href='staff/index.php'"
                    class="bento-d bg-white rounded-3xl ambient-shadow border border-outline-variant/30 hover:border-primary/20 hover:-translate-y-1 transition-all group p-10 cursor-pointer">

                    <div
                        class="w-14 h-14 bg-secondary-container text-primary rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                        <span class="material-symbols-outlined text-[32px]">
                            engineering
                        </span>

                    </div>

                    <h3 class="text-2xl font-bold mb-4">
                        Manajemen Staff
                    </h3>

                    <p class="text-on-surface-variant leading-relaxed">
                        Tugaskan staff pemeliharaan ke dispenser tertentu dengan mudah dan pantau performa kerjanya.
                    </p>

                </div>

                <!-- E -->
                <div onclick="location.href='refill/index.php'"
                    class="bento-e bg-white rounded-3xl ambient-shadow border border-outline-variant/30 hover:border-primary/20 hover:-translate-y-1 transition-all group p-10 cursor-pointer">

                    <div
                        class="w-14 h-14 bg-secondary-container text-primary rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                        <span class="material-symbols-outlined text-[32px]">
                            recycling
                        </span>

                    </div>

                    <h3 class="text-2xl font-bold mb-4">
                        Riwayat Refill
                    </h3>

                    <p class="text-on-surface-variant leading-relaxed">
                        Catat log setiap pengisian ulang galon air secara lengkap beserta volume dan penanggung jawabnya.
                    </p>

                </div>

            </div>

        </section>

        <!-- REPORT SECTION -->
        <section id="buat-laporan" class="max-w-container mx-auto px-6 lg:px-16 mt-32 scroll-mt-24">
            <div class="card p-8 md:p-12 bg-gradient-to-br from-white to-blue-50/50 shadow-xl border border-blue-100 rounded-3xl">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-10">
                        <span class="text-xs uppercase tracking-[0.2em] font-bold text-primary">Laporkan Kendala</span>
                        <h2 class="text-3xl md:text-4xl font-extrabold text-on-surface mt-2 mb-4">Buat Laporan Kerusakan / Masalah</h2>
                        <p class="text-on-surface-variant text-sm md:text-base leading-relaxed">
                            Menemukan dispenser yang bocor, rusak, atau galon kosong? Laporkan di bawah ini, dan tim maintenance kami akan segera datang memperbaikinya.
                        </p>
                    </div>

                    <?php if ($report_success): ?>
                        <div class="flex items-center gap-3 px-5 py-4 mb-6 rounded-2xl border bg-emerald-50 border-emerald-300 text-emerald-800 text-sm font-medium">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            <span><?= htmlspecialchars($report_success) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($report_error): ?>
                        <div class="flex items-center gap-3 px-5 py-4 mb-6 rounded-2xl border bg-red-50 border-red-300 text-red-800 text-sm font-medium">
                            <span class="material-symbols-outlined text-[20px]">error</span>
                            <span><?= htmlspecialchars($report_error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php#buat-laporan" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="submit_report" value="1">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-on-surface mb-2" for="nama">Nama Pelapor <span class="text-red-500">*</span></label>
                                <input class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" 
                                       type="text" id="nama" name="nama" placeholder="Ketik nama Anda…" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-on-surface mb-2" for="nim">NIM Pelapor <span class="text-red-500">*</span></label>
                                <input class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" 
                                       type="text" id="nim" name="nim" placeholder="Ketik NIM Anda…" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-on-surface mb-2" for="dispenser_id">Pilih Dispenser <span class="text-red-500">*</span></label>
                                <select class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all cursor-pointer" 
                                        id="dispenser_id" name="dispenser_id" required>
                                    <option value="">— Pilih Lokasi Dispenser —</option>
                                    <?php foreach ($dispensersList as $d): ?>
                                        <option value="<?= $d['Dispenser_ID'] ?>">
                                            <?= htmlspecialchars($d['Nama_Gedung']) ?> (Lt. <?= htmlspecialchars($d['Lantai']) ?>) - <?= htmlspecialchars($d['Kode_Dispenser']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-on-surface mb-2" for="kategori">Kategori Masalah <span class="text-red-500">*</span></label>
                                <select class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all cursor-pointer" 
                                        id="kategori" name="kategori" required>
                                    <option value="">— Pilih Kategori Masalah —</option>
                                    <option value="Galon Kosong">Galon Kosong</option>
                                    <option value="Dispenser Rusak">Dispenser Rusak</option>
                                    <option value="Kebocoran">Kebocoran</option>
                                    <option value="Distribusi Tidak Merata">Distribusi Tidak Merata</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-on-surface mb-2" for="foto">Upload Foto Kendala (Opsional)</label>
                            <div class="flex items-center justify-center w-full">
                                <label for="foto" class="flex flex-col items-center justify-center w-full h-32 border-2 border-outline-variant border-dashed rounded-xl cursor-pointer bg-white hover:bg-gray-50 transition-all">
                                    <div class="flex flex-col items-center justify-center pt-4 pb-4">
                                        <span class="material-symbols-outlined text-[32px] text-gray-400 mb-1">cloud_upload</span>
                                        <p class="mb-1 text-sm text-gray-500 font-medium">Klik untuk upload foto</p>
                                        <p class="text-xs text-gray-400">PNG, JPG, JPEG atau GIF (Max. 5MB)</p>
                                    </div>
                                    <input id="foto" name="foto" type="file" accept="image/*" class="hidden" />
                                </label>
                            </div>
                            <div id="file-preview-container" class="mt-2 hidden">
                                <p class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                    File terpilih: <span id="file-name"></span>
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-on-surface mb-2" for="deskripsi">Deskripsi Kendala <span class="text-red-500">*</span></label>
                            <textarea class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all resize-none h-28" 
                                      id="deskripsi" name="deskripsi" placeholder="Detail kendala yang dialami dispenser…" required></textarea>
                        </div>

                        <div class="text-center pt-4">
                            <button type="submit" 
                                    class="bg-primary text-white text-sm font-semibold px-10 py-4 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:scale-95 transition-all inline-flex items-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">send</span> Kirim Laporan Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-surface-container-low border-t border-outline-variant/30 pt-20 pb-12 mt-32">

        <div class="max-w-container mx-auto px-6 lg:px-16">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-20">

                <div class="md:col-span-2">

                    <div class="text-3xl font-black text-primary mb-6">
                        CariGalon
                    </div>

                    <p class="text-on-surface-variant max-w-sm mb-8 leading-relaxed">
                        Platform monitoring dan manajemen logistik air minum kampus Universitas Ciputra.
                    </p>

                </div>

                <div>

                    <h4 class="text-sm font-bold uppercase tracking-widest mb-6">
                        Platform
                    </h4>

                    <ul class="flex flex-col gap-4">

                        <li>
                            <a class="text-on-surface-variant hover:text-primary transition-colors" href="dashboard/index.php">
                                Dashboard Admin
                            </a>
                        </li>

                        <li>
                            <a class="text-on-surface-variant hover:text-primary transition-colors" href="dispensers/index.php">
                                Peta Dispenser
                            </a>
                        </li>

                        <li>
                            <a class="text-on-surface-variant hover:text-primary transition-colors" href="laporan/create.php">
                                Buat Laporan
                            </a>
                        </li>

                    </ul>

                </div>

                <div>

                    <h4 class="text-sm font-bold uppercase tracking-widest mb-6">
                        Navigasi Cepat
                    </h4>

                    <ul class="flex flex-col gap-4">

                        <li>
                            <a class="text-on-surface-variant hover:text-primary transition-colors" href="galon/index.php">
                                Stok Galon
                            </a>
                        </li>

                        <li>
                            <a class="text-on-surface-variant hover:text-primary transition-colors" href="staff/index.php">
                                Manajemen Staff
                            </a>
                        </li>

                        <li>
                            <a class="text-on-surface-variant hover:text-primary transition-colors" href="refill/index.php">
                                Riwayat Refill
                            </a>
                        </li>

                    </ul>

                </div>

            </div>

            <div
                class="flex flex-col md:flex-row justify-between items-center pt-10 border-t border-outline-variant/20 gap-6">

                <p class="text-on-surface-variant opacity-60 text-sm">
                    © 2026 CariGalon — Universitas Ciputra.
                </p>

                <div class="flex gap-8 text-sm">

                    <a class="text-on-surface-variant hover:text-primary transition-all" href="#">
                        Tentang Proyek
                    </a>

                    <a class="text-on-surface-variant hover:text-primary transition-all" href="#">
                        Kontak
                    </a>

                    <a class="text-on-surface-variant hover:text-primary transition-all" href="#">
                        Kebijakan Privasi
                    </a>

                </div>

            </div>

        </div>

    </footer>

    <script>
        window.addEventListener('scroll', () => {
            const header = document.getElementById('top-app-bar');

            if (window.scrollY > 20) {
                header.classList.add(
                    'h-16',
                    'shadow-sm'
                );

                header.classList.remove(
                    'h-20'
                );
            } else {
                header.classList.remove(
                    'h-16',
                    'shadow-sm'
                );

                header.classList.add(
                    'h-20'
                );
            }
        });
    </script>

    <script>
const icon = document.getElementById("radioIcon");

let checked = true;

setInterval(() => {
    checked = !checked;

    icon.textContent = checked
        ? "radio_button_checked"
        : "radio_button_unchecked";
}, 800);

// File input preview
document.getElementById('foto').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : '';
    const container = document.getElementById('file-preview-container');
    const nameEl = document.getElementById('file-name');
    if (fileName) {
        nameEl.textContent = fileName;
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
});
</script>

</body>

</html>
