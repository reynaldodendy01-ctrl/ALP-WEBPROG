<?php
/**
 * =============================================================================
 * submit_report.php — API Endpoint Pengiriman Laporan Kendala Dispenser
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini adalah RESTful API endpoint yang menerima laporan kendala dispenser
 *   air (galon kosong atau dispenser rusak/bocor) yang dikirimkan dari halaman
 *   publik kampus oleh pengunjung (mahasiswa, dosen, karyawan). Endpoint ini
 *   memproses data form multipart (termasuk upload foto opsional), melakukan
 *   validasi input dan file, menyimpan data pelapor (baru atau yang sudah ada),
 *   dan memasukkan laporan baru ke dalam tabel water_report menggunakan transaksi
 *   database untuk menjamin konsistensi. Seluruh respons dikembalikan dalam
 *   format JSON.
 *
 * FUNGSI UTAMA:
 *   - Menerima request HTTP POST dari form laporan halaman publik (atau AJAX/fetch)
 *   - Menangani preflight CORS (OPTIONS request) untuk kompatibilitas cross-origin
 *   - Validasi input wajib: nama, nim, dispenser_id, kategori
 *   - Validasi kategori: hanya 'Galon Kosong' atau 'Dispenser Rusak / Bocor'
 *   - Upload foto opsional: validasi ekstensi (jpg, jpeg, png, gif) dan ukuran (maks 5MB)
 *   - Simpan file foto ke direktori /uploads/ dengan nama unik (md5 + uniqid)
 *   - Find-or-insert pelapor: bila NIM sudah ada, nama diperbarui; bila baru, di-INSERT
 *   - INSERT laporan baru ke tabel water_report dengan status awal 'Pending'
 *   - Mengembalikan JSON { success: true/false, message: "..." }
 *
 * ALUR KERJA (FLOW):
 *   1. Header Content-Type: application/json dan CORS headers diset
 *   2. Bila request OPTIONS (CORS preflight): langsung exit
 *   3. Bila request bukan POST: kembalikan JSON error, exit
 *   4. db.php di-include untuk koneksi database
 *   5. Input dibaca dari $_POST: nama, nim, dispenser_id, kategori, deskripsi
 *   6. Validasi field wajib dan nilai kategori; bila gagal: kembalikan JSON error
 *   7. Bila ada file upload ($_FILES['foto']):
 *      a. Validasi ekstensi dan ukuran file
 *      b. Buat direktori /uploads/ bila belum ada
 *      c. Generate nama file unik dengan md5(uniqid(time(), true))
 *      d. Pindahkan file dari tmp ke /uploads/; simpan path relatif ke $foto_url
 *   8. Mulai transaksi PDO
 *   9. Cari Reporter_ID berdasarkan NIM; bila tidak ada: INSERT pelapor baru
 *      Bila ada: UPDATE nama pelapor agar tetap terkini
 *  10. INSERT ke tabel water_report dengan status 'Pending' dan data lengkap
 *  11. Commit transaksi; kembalikan JSON success
 *  12. Bila Exception: rollback transaksi; kembalikan JSON error
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - reporter     : SELECT untuk cek NIM; INSERT pelapor baru atau UPDATE nama
 *   - water_report : INSERT laporan baru dengan status 'Pending'
 *
 * VARIABEL PENTING:
 *   - $nama         : Nama lengkap pelapor (dari $_POST['nama'])
 *   - $nim          : NIM pelapor (dari $_POST['nim'])
 *   - $dispenser_id : ID dispenser yang dilaporkan (integer dari $_POST['dispenser_id'])
 *   - $kategori     : Jenis kendala ('Galon Kosong' / 'Dispenser Rusak / Bocor')
 *   - $deskripsi    : Deskripsi detail laporan (opsional, dari $_POST['deskripsi'])
 *   - $foto_url     : Path relatif foto yang diupload (nullable, contoh: 'uploads/abc123.jpg')
 *   - $upload_ok    : Boolean flag keberhasilan proses upload foto
 *   - $reporter_id  : ID pelapor (dari SELECT atau lastInsertId setelah INSERT)
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - db.php : Koneksi database PDO (di-include di dalam try block)
 *
 * AKSES:
 *   Dapat diakses oleh semua pengguna publik tanpa login (tidak memerlukan sesi).
 *   Endpoint ini terbuka untuk umum karena ditujukan untuk form pelaporan publik
 *   di halaman landing page kampus.
 *
 * CATATAN PENGEMBANG:
 *   - CORS diset ke Access-Control-Allow-Origin: * sehingga dapat dipanggil dari
 *     domain manapun; pertimbangkan untuk membatasi ke domain spesifik di produksi
 *   - Nama file foto diacak dengan md5(uniqid(time(), true)) untuk mencegah konflik
 *     nama file dan enumerasi direktori
 *   - Direktori /uploads/ dibuat otomatis dengan chmod 0777 bila belum ada
 *   - Seluruh operasi DB (find/insert reporter + insert water_report) dibungkus
 *     dalam satu transaksi untuk konsistensi data atomik
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */
// api/submit_report.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Hanya menerima request POST.'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../db.php';

    $nama = trim($_POST['nama'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $dispenser_id = intval($_POST['dispenser_id'] ?? 0);
    $kategori = $_POST['kategori'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!$nama || !$nim || !$dispenser_id || !$kategori) {
        echo json_encode([
            'success' => false,
            'message' => 'Nama, NIM, dispenser, dan kategori wajib diisi.'
        ]);
        exit;
    }

    $allowedCategories = ['Galon Kosong', 'Dispenser Rusak / Bocor'];
    if (!in_array($kategori, $allowedCategories)) {
        echo json_encode([
            'success' => false,
            'message' => 'Kategori tidak valid.'
        ]);
        exit;
    }

    $foto_url = null;
    $upload_ok = true;
    $report_error = null;

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
            // Save to root directory uploads/
            $uploadFileDir = dirname(__DIR__) . '/uploads/';
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

    if (!$upload_ok) {
        echo json_encode([
            'success' => false,
            'message' => $report_error
        ]);
        exit;
    }

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

    echo json_encode([
        'success' => true,
        'message' => 'Laporan berhasil dikirim! Staff kami akan segera menindaklanjuti.'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengirim laporan: ' . $e->getMessage()
    ]);
}
