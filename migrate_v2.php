<?php
// migrate_v2.php — Run this once to update the existing database schema
// Access via: http://localhost/ALP-WEBPROG/migrate_v2.php

require_once __DIR__ . '/db.php';

$results = [];

// 1. Add Gedung column to maintenance_staff (if not exists)
try {
    $pdo->exec("ALTER TABLE maintenance_staff ADD COLUMN Gedung VARCHAR(50) NULL AFTER Role");
    $results[] = ['ok', 'Added Gedung column to maintenance_staff'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $results[] = ['skip', 'Gedung column already exists in maintenance_staff'];
    } else {
        $results[] = ['error', 'Failed to add Gedung: ' . $e->getMessage()];
    }
}

// 2. Update Kategori ENUM in water_report to new simplified values
try {
    $pdo->exec("ALTER TABLE water_report MODIFY COLUMN Kategori ENUM('Galon Kosong', 'Dispenser Rusak / Bocor') NOT NULL");
    $results[] = ['ok', 'Updated water_report.Kategori ENUM'];
} catch (PDOException $e) {
    // If existing rows have old category values, the ALTER will fail
    // First update old values, then alter
    try {
        $pdo->exec("UPDATE water_report SET Kategori = 'Dispenser Rusak / Bocor' WHERE Kategori IN ('Dispenser Rusak', 'Kebocoran', 'Lainnya')");
        $pdo->exec("UPDATE water_report SET Kategori = 'Galon Kosong' WHERE Kategori = 'Distribusi Tidak Merata'");
        $pdo->exec("ALTER TABLE water_report MODIFY COLUMN Kategori ENUM('Galon Kosong', 'Dispenser Rusak / Bocor') NOT NULL");
        $results[] = ['ok', 'Updated old categories and modified ENUM successfully'];
    } catch (PDOException $e2) {
        $results[] = ['error', 'Failed to update Kategori ENUM: ' . $e2->getMessage()];
    }
}

// 3. Make Deskripsi_Report nullable
try {
    $pdo->exec("ALTER TABLE water_report MODIFY COLUMN Deskripsi_Report VARCHAR(255) NULL");
    $results[] = ['ok', 'Made Deskripsi_Report nullable'];
} catch (PDOException $e) {
    $results[] = ['error', 'Failed to make Deskripsi_Report nullable: ' . $e->getMessage()];
}

// Output results
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Migrate v2</title><style>body{font-family:sans-serif;padding:32px;max-width:640px;} .ok{color:green;} .skip{color:orange;} .error{color:red;} li{margin-bottom:8px;padding:8px 12px;border-radius:6px;} .ok-bg{background:#dcfce7;} .skip-bg{background:#fef9c3;} .err-bg{background:#fee2e2;}</style></head><body>";
echo "<h2>Migration v2 Results</h2><ul>";
foreach ($results as $r) {
    $cls = $r[0] === 'ok' ? 'ok-bg' : ($r[0] === 'skip' ? 'skip-bg' : 'err-bg');
    echo "<li class='$cls'><b class='{$r[0]}'>[" . strtoupper($r[0]) . "]</b> " . htmlspecialchars($r[1]) . "</li>";
}
echo "</ul><p><a href='index.html'>← Back to Home</a></p></body></html>";
