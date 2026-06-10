<?php
// api/get_landing_data.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

try {
    require_once __DIR__ . '/../db.php';

    $total_dispensers = $pdo->query("SELECT COUNT(*) FROM dispenser")->fetchColumn();
    $total_gedung = $pdo->query("SELECT COUNT(DISTINCT Nama_Gedung) FROM lokasi")->fetchColumn();
    
    // Fetch dispensers list for reporting form dropdown
    $dispensersList = $pdo->query("
        SELECT d.Dispenser_ID, d.Kode_Dispenser, l.Nama_Gedung, l.Lantai 
        FROM dispenser d 
        JOIN lokasi l ON d.Lokasi_ID = l.Lokasi_ID 
        ORDER BY l.Nama_Gedung, l.Lantai, d.Kode_Dispenser
    ")->fetchAll();

    echo json_encode([
        'success' => true,
        'total_dispensers' => (int)$total_dispensers,
        'total_gedung' => (int)$total_gedung,
        'dispensers' => $dispensersList
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        // Fallbacks in case database has failed or is unconfigured
        'total_dispensers' => 5,
        'total_gedung' => 2,
        'dispensers' => []
    ]);
}
