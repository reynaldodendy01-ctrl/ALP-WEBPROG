<?php
$files = [
    'api/get_landing_data.php' => 'API endpoint untuk mengambil data statistik, status dispenser, dan laporan untuk halaman utama pengunjung.',
    'api/submit_report.php' => 'API endpoint untuk memproses pengiriman laporan kendala (galon kosong/dispenser rusak) dari halaman utama pengunjung.',
    'assignments/create.php' => 'Halaman untuk membuat penugasan (assignment) baru kepada staf maintenance untuk menangani suatu laporan.',
    'assignments/delete.php' => 'Proses penghapusan data penugasan staf dari database.',
    'assignments/edit.php' => 'Halaman untuk mengedit atau memperbarui data penugasan staf yang sudah ada.',
    'assignments/index.php' => 'Halaman daftar semua penugasan (assignment) staf maintenance beserta status penyelesaiannya.',
    'css/style.css' => 'File stylesheet utama yang mengatur tampilan dan desain antarmuka aplikasi, khususnya bagian dashboard admin.',
    'dashboard/index.php' => 'Halaman beranda dashboard admin yang menampilkan ringkasan laporan aktif, tugas staf, dan statistik sistem.',
    'dispensers/create.php' => 'Halaman form untuk menambahkan data dispenser baru ke dalam sistem.',
    'dispensers/delete.php' => 'Proses penghapusan data dispenser dari database.',
    'dispensers/edit.php' => 'Halaman form untuk mengedit informasi dispenser yang sudah terdaftar.',
    'dispensers/index.php' => 'Halaman daftar semua dispenser, status ketersediaan, dan riwayat tugas terkait dispenser tersebut.',
    'js/landing.js' => 'Skrip JavaScript untuk menangani logika interaktif di halaman utama pengunjung, termasuk filter tabel dan pengiriman form laporan.',
    'laporan/ambil.php' => 'Proses atau aksi bagi staf untuk mengambil/menerima suatu laporan agar mulai diproses.',
    'laporan/create.php' => 'Halaman form (admin) untuk membuat laporan masalah/kendala dispenser secara manual.',
    'laporan/delete.php' => 'Proses penghapusan data laporan kendala dari database.',
    'laporan/edit.php' => 'Halaman form (admin) untuk mengedit rincian dari suatu laporan kendala.',
    'laporan/index.php' => 'Halaman daftar semua laporan kendala (galon kosong/bocor) yang masuk ke sistem.',
    'laporan/start_assignment.php' => 'Aksi untuk memulai pengerjaan (memproses) laporan yang telah diambil oleh staf.',
    'laporan/tolak.php' => 'Aksi (admin/staf) untuk menolak suatu laporan dengan memberikan alasan penolakan.',
    'lokasi/create.php' => 'Halaman form untuk menambahkan data lokasi/gedung baru ke dalam sistem.',
    'lokasi/delete.php' => 'Proses penghapusan data lokasi/gedung dari database.',
    'lokasi/edit.php' => 'Halaman form untuk mengubah rincian informasi lokasi/gedung yang sudah ada.',
    'lokasi/index.php' => 'Halaman daftar lokasi (gedung dan lantai) tempat dispenser diletakkan.',
    'refill/create.php' => 'Halaman form pencatatan riwayat pengisian ulang (refill) galon untuk dispenser tertentu.',
    'refill/delete.php' => 'Proses penghapusan log/riwayat pengisian ulang galon dari database.',
    'refill/index.php' => 'Halaman daftar seluruh log pengisian ulang galon (refill) di berbagai dispenser.',
    'reporters/create.php' => 'Halaman form untuk menambahkan data pelapor secara manual ke sistem.',
    'reporters/delete.php' => 'Proses penghapusan data pelapor dari sistem.',
    'reporters/edit.php' => 'Halaman form untuk memperbarui atau mengedit informasi identitas pelapor.',
    'reporters/index.php' => 'Halaman daftar semua orang (mahasiswa/dosen/karyawan) yang pernah membuat laporan.',
    'staff/create.php' => 'Halaman form untuk menambahkan akun staf maintenance atau admin baru.',
    'staff/delete.php' => 'Proses penghapusan akun staf/admin dari sistem.',
    'staff/edit.php' => 'Halaman form untuk mengubah profil, password, atau role dari akun staf/admin.',
    'staff/index.php' => 'Halaman daftar semua akun pengguna sistem (staf maintenance dan admin).',
    '_partials/layout_foot.php' => 'Bagian penutup HTML (footer) yang di-include pada seluruh halaman dashboard admin.',
    '_partials/layout_head.php' => 'Bagian pembuka HTML (header), navigasi sidebar, dan pengecekan sesi login untuk dashboard admin.',
    'dashboard.php' => 'File redirect lama (deprecated) atau script utama dashboard.',
    'db.php' => 'File konfigurasi koneksi ke database MySQL menggunakan PDO, dan memuat berbagai helper function dasar.',
    'index.html' => 'Halaman utama (landing page) aplikasi untuk pengunjung publik yang berisi informasi ketersediaan galon dan form pelaporan.',
    'login.php' => 'Halaman form login bagi staf dan admin untuk mengakses dashboard sistem.',
    'login_process.php' => 'Proses validasi kredensial (email & password) dan inisialisasi sesi saat pengguna login.',
    'logout.php' => 'Proses menghancurkan sesi login saat ini dan mengarahkan pengguna kembali ke halaman login.'
];

foreach ($files as $file => $desc) {
    if (!file_exists($file)) continue;
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $content = file_get_contents($file);
    if (strpos($content, 'DESKRIPSI FILE:') !== false) continue;

    $comment = "";
    if ($ext === 'php') {
        $comment = "/* DESKRIPSI FILE: $desc */\n";
        if (substr(trim($content), 0, 5) === '<?php') {
            $content = preg_replace('/<\?php\s*/', "<?php\n" . $comment, $content, 1);
        } else {
            $content = "<?php " . $comment . " ?>\n" . $content;
        }
    } elseif ($ext === 'html') {
        $comment = "<!-- DESKRIPSI FILE: $desc -->\n";
        $content = $comment . $content;
    } elseif ($ext === 'js' || $ext === 'css') {
        $comment = "/* DESKRIPSI FILE: $desc */\n";
        $content = $comment . $content;
    }

    file_put_contents($file, $content);
    echo "Added comment to $file\n";
}
?>
