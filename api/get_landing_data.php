<?php
// api/get_landing_data.php
// File ini berfungsi sebagai "API" (Application Programming Interface).
// API adalah jembatan penghubung antara halaman depan pengguna (HTML/JS) dengan database (MySQL).
// Di sini kita mengambil data dispenser dan statistik untuk ditampilkan di halaman utama.
header('Content-Type: application/json'); // Mengatur format balasan berupa JSON (agar mudah dibaca Javascript)
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

try {
    require_once __DIR__ . '/../db.php';

    // ─── Mengambil Statistik Dasar ─────────────────────────────────────────
    // COUNT(*) berguna untuk menghitung ada berapa total baris data (dispenser).
    $total_dispensers = $pdo->query("SELECT COUNT(*) FROM dispenser")->fetchColumn();
    // COUNT(DISTINCT Nama_Gedung) menghitung jumlah gedung tanpa ada yang dobel.
    $total_gedung = $pdo->query("SELECT COUNT(DISTINCT Nama_Gedung) FROM lokasi")->fetchColumn();

    // Dispensers for form dropdown (only available ones)
    $dispensersList = $pdo->query("
        SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
        FROM dispenser d
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        WHERE d.Dispenser_ID NOT IN (
            SELECT Dispenser_ID FROM water_report WHERE Status IN ('Pending', 'Diproses')
        )
        ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
    ")->fetchAll();

    // All dispensers with their latest active report status
    // If no active report (Pending/Diproses), the dispenser is "Tersedia"
    $dispenserStatus = $pdo->query("
        SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai,
               wr.WaterReport_ID, wr.Kategori, wr.Status AS report_status,
               wr.Deskripsi_Report, wr.Foto_url, wr.Reported_At
        FROM dispenser d
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        LEFT JOIN water_report wr ON wr.Dispenser_ID = d.Dispenser_ID
            AND wr.Status IN ('Pending', 'Diproses')
        ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser, wr.Reported_At DESC
    ")->fetchAll();

    // Process: group by dispenser, pick the latest active report per dispenser
    $dispenserMap = [];
    foreach ($dispenserStatus as $row) {
        $id = $row['Dispenser_ID'];
        if (!isset($dispenserMap[$id])) {
            $dispenserMap[$id] = [
                'Dispenser_ID' => $row['Dispenser_ID'],
                'Kode_Dispenser' => $row['Kode_Dispenser'],
                'Nama_Gedung' => $row['Nama_Gedung'],
                'Lantai' => $row['Lantai'],
                'status' => 'Tersedia',
                'kategori' => null,
                'deskripsi' => null,
                'foto_url' => null,
                'reported_at' => null,
                'report_id' => null,
            ];
        }
        // If there is an active report and we haven't set one yet
        if ($row['WaterReport_ID'] && $dispenserMap[$id]['status'] === 'Tersedia') {
            $dispenserMap[$id]['status'] = $row['report_status'];
            $dispenserMap[$id]['kategori'] = $row['Kategori'];
            $dispenserMap[$id]['deskripsi'] = $row['Deskripsi_Report'];
            $dispenserMap[$id]['foto_url'] = $row['Foto_url'];
            $dispenserMap[$id]['reported_at'] = $row['Reported_At'];
            $dispenserMap[$id]['report_id'] = $row['WaterReport_ID'];
        }
    }
    $dispenserStatusList = array_values($dispenserMap);

    // All reports (for the full report history)
    $reports = $pdo->query("
        SELECT wr.WaterReport_ID, wr.Kategori, wr.Status, wr.Deskripsi_Report,
               wr.Foto_url, wr.Reported_At,
               d.Kode_Dispenser, l.Nama_Gedung, l.Lantai
        FROM water_report wr
        JOIN dispenser d ON wr.Dispenser_ID = d.Dispenser_ID
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID
        ORDER BY wr.Reported_At DESC
    ")->fetchAll();

    // Staff summary
    $staffSummary = $pdo->query("
        SELECT ms.Staff_ID, ms.Nama,
               COUNT(DISTINCT a.Assignment_ID) AS total_done,
               COALESCE(SUM(rl.jumlah_refill), 0) AS total_refills
        FROM maintenance_staff ms
        LEFT JOIN staff_dispenser_assignment a ON a.Staff_ID = ms.Staff_ID AND a.Status = 'Completed'
        LEFT JOIN (
            SELECT Assignment_ID, COUNT(*) AS jumlah_refill
            FROM refill_logs
            GROUP BY Assignment_ID
        ) rl ON rl.Assignment_ID = a.Assignment_ID
        WHERE ms.Role = 'Staff'
        GROUP BY ms.Staff_ID, ms.Nama
        ORDER BY total_done DESC, total_refills DESC
    ")->fetchAll();

    // Staff daily detail
    $staffDailyDetail = $pdo->query("
        SELECT ms.Staff_ID, ms.Nama,
               DATE(a.Created_At) AS tanggal,
               COUNT(a.Assignment_ID) AS jumlah
        FROM maintenance_staff ms
        JOIN staff_dispenser_assignment a ON a.Staff_ID = ms.Staff_ID AND a.Status = 'Completed'
        WHERE ms.Role = 'Staff'
        GROUP BY ms.Staff_ID, ms.Nama, DATE(a.Created_At)
        ORDER BY ms.Nama, tanggal DESC
    ")->fetchAll();

    echo json_encode([
        'success'          => true,
        'total_dispensers'  => (int)$total_dispensers,
        'total_gedung'      => (int)$total_gedung,
        'dispensers'        => $dispensersList,
        'dispenser_status'  => $dispenserStatusList,
        'reports'           => $reports,
        'staff_summary'     => $staffSummary,
        'staff_daily'       => $staffDailyDetail,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success'          => false,
        'message'          => 'Database error: ' . $e->getMessage(),
        'total_dispensers'  => 5,
        'total_gedung'      => 2,
        'dispensers'        => [],
        'dispenser_status'  => [],
        'reports'           => [],
        'staff_summary'     => [],
        'staff_daily'       => [],
    ]);
}
