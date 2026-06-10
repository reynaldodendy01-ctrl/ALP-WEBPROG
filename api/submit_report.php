<?php
// api/submit_report.php
// Ini adalah "API" untuk Menerima Laporan dari Mahasiswa/Staff (Halaman Utama).
// Ketika form dikirim di halaman depan, datanya mendarat di file ini.

// ─── PENGATURAN HEADER & KEAMANAN ─────────────────────────────────────────
// Kita memberitahu browser bahwa balasan dari file ini adalah data format JSON (bukan HTML biasa).
header('Content-Type: application/json'); 
// Mengizinkan website dari domain/alamat manapun (*) untuk mengirim data ke file ini.
header('Access-Control-Allow-Origin: *');
// Mengizinkan tipe data tertentu untuk dikirim.
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
// Hanya memperbolehkan metode GET, POST, dan OPTIONS.
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// OPTIONS biasanya dikirim oleh browser secara otomatis sebelum mengirim POST (namanya "Preflight Request").
// Kalau ada OPTIONS, langsung tutup file karena kita cuma butuh tahu ia diizinkan.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Kalau metode pengiriman data BUKAN 'POST' (misalnya cuma diakses langsung dari URL/GET), tolak!
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Hanya menerima request POST.'
    ]);
    exit;
}

try {
    // Muat koneksi database dari folder luar
    require_once __DIR__ . '/../db.php';

    // ─── MENANGKAP DATA DARI FORM ─────────────────────────────────────────
    // Kita menangkap data yang diketik di form menggunakan $_POST.
    // trim() membuang spasi kosong di awal/akhir supaya data bersih.
    // intval() memaksa data berubah jadi angka bulat (khusus ID dispenser).
    $nama = trim($_POST['nama'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $dispenser_id = intval($_POST['dispenser_id'] ?? 0);
    $kategori = $_POST['kategori'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    // Cek jika ada isian wajib yang masih kosong
    if (!$nama || !$nim || !$dispenser_id || !$kategori) {
        echo json_encode([
            'success' => false,
            'message' => 'Nama, NIM, dispenser, dan kategori wajib diisi.'
        ]);
        exit;
    }

    // Pastikan kategori yang dipilih cuma ada 2 pilihan ini. Biar nggak bisa di-hack/dirusak.
    $allowedCategories = ['Galon Kosong', 'Dispenser Rusak / Bocor'];
    if (!in_array($kategori, $allowedCategories)) {
        echo json_encode([
            'success' => false,
            'message' => 'Kategori tidak valid.'
        ]);
        exit;
    }

    // ─── PROSES UPLOAD FOTO ───────────────────────────────────────────────
    $foto_url = null; // Awalnya tidak ada foto
    $upload_ok = true; // Anggap tidak ada masalah
    $report_error = null; // Menyimpan pesan error jika upload gagal

    // Jika ada kiriman gambar ($_FILES['foto']) dan kondisinya 'OK' (tidak error)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name']; // Lokasi foto sementara di server
        $fileName = $_FILES['foto']['name']; // Nama asli file dari hp/komputer user
        $fileSize = $_FILES['foto']['size']; // Ukuran file
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); // Mengambil tipe format gambar (jpg, png)

        // Hanya izinkan 4 format ini
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            $report_error = "Ekstensi file tidak valid. Diperbolehkan: JPG, JPEG, PNG, GIF.";
            $upload_ok = false;
        } elseif ($fileSize > 5 * 1024 * 1024) { 
            // Kalau fotonya kebesaran (lebih dari 5 MB), tolak!
            $report_error = "Ukuran file terlalu besar. Maksimum 5MB.";
            $upload_ok = false;
        } else {
            // Tentukan folder penyimpanan (keluar dari folder 'api', lalu masuk folder 'uploads')
            $uploadFileDir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true); // Bikin folder kalau belum ada
            }
            
            // Beri nama file baru secara acak supaya tidak saling tertimpa fotonya
            $newFileName = md5(uniqid(time(), true)) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName; // Jalur akhirnya

            // Pindahkan dari tempat sementara ke folder final
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $foto_url = 'uploads/' . $newFileName; // Simpan jalur fotonya buat ke database
            } else {
                $report_error = "Gagal mengunggah foto. Silakan coba lagi.";
                $upload_ok = false;
            }
        }
    } elseif (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Jika ada error upload selain "file kosong" (misal ukuran melebih setelan XAMPP)
        $report_error = "Terjadi kesalahan pada file upload: Kode " . $_FILES['foto']['error'];
        $upload_ok = false;
    }

    // Kalau ada masalah upload, langsung hentikan API di sini.
    if (!$upload_ok) {
        echo json_encode([
            'success' => false,
            'message' => $report_error
        ]);
        exit;
    }

    // ─── TRANSAKSI DATABASE ──────────────────────────────────────────────
    // Kita pakai beginTransaction supaya kalau proses simpan laporan atau pelapor ada yang gagal,
    // semuanya otomatis dibatalkan (rollback). Ini mencegah data masuk setengah-setengah.
    $pdo->beginTransaction();

    // 1. Mencari data pelapor (Apakah dia sudah pernah lapor sebelumnya di tabel 'reporter'?)
    $stmt = $pdo->prepare("SELECT Reporter_ID FROM reporter WHERE Nim = :nim");
    $stmt->execute([':nim' => $nim]);
    $reporter_id = $stmt->fetchColumn();

    // Jika pelapor belum pernah terdaftar, tambahkan data pelapor baru ke tabel 'reporter'
    if (!$reporter_id) {
        $stmt = $pdo->prepare("INSERT INTO reporter (Nama, Nim) VALUES (:nama, :nim)");
        $stmt->execute([':nama' => $nama, ':nim' => $nim]);
        $reporter_id = $pdo->lastInsertId(); // Ambil ID yang baru saja dibuat oleh MySQL
    } else {
        // Jika sudah terdaftar, mungkin saja namanya berubah, jadi kita perbarui datanya.
        $stmt = $pdo->prepare("UPDATE reporter SET Nama = :nama WHERE Reporter_ID = :reporter_id");
        $stmt->execute([':nama' => $nama, ':reporter_id' => $reporter_id]);
    }

    // 2. Memasukkan laporannya ke tabel utama ('water_report')
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

    // Jika semua proses di atas mulus, baru deh "Di-Save" permanen (Commit)!
    $pdo->commit();

    // Balasan JSON ketika proses benar-benar sukses. (Ini yang dibaca file Javascript index.html)
    echo json_encode([
        'success' => true,
        'message' => 'Laporan berhasil dikirim! Staff kami akan segera menindaklanjuti.'
    ]);

} catch (Exception $e) {
    // Kalau ada error selama try {...}, kita akan lompat ke mari.
    // Jika masih dalam status transaksi...
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack(); // Batalkan semua simpanan database sejak beginTransaction (kembalikan seperti semula).
    }
    // Lalu kasih tahu Frontend (Javascript) apa kerusakannya.
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengirim laporan: ' . $e->getMessage()
    ]);
}
