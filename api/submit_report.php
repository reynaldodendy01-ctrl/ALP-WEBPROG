<?php
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
