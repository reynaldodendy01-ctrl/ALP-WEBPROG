<?php
// migrate_role.php
require_once __DIR__ . '/db.php';

echo "Memulai migrasi role database...\n";

try {
    // 1. Cek apakah kolom Role sudah ada di tabel maintenance_staff
    $stmt = $pdo->query("SHOW COLUMNS FROM maintenance_staff LIKE 'Role'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        echo "Kolom 'Role' tidak ditemukan. Menambahkan kolom...\n";
        $pdo->exec("ALTER TABLE maintenance_staff ADD COLUMN Role ENUM('Staff', 'Admin') NOT NULL DEFAULT 'Staff'");
        echo "Kolom 'Role' berhasil ditambahkan.\n";
    } else {
        echo "Kolom 'Role' sudah ada.\n";
    }

    // 2. Set role 'Admin' untuk Budi Santoso (budi.santoso@uc.ac.id)
    $stmt = $pdo->prepare("UPDATE maintenance_staff SET Role = 'Admin' WHERE Email = 'budi.santoso@uc.ac.id'");
    $stmt->execute();
    echo "Mengatur Budi Santoso sebagai Admin.\n";

    echo "Migrasi role selesai dengan sukses!\n";
} catch (Exception $e) {
    echo "Error saat migrasi role: " . $e->getMessage() . "\n";
    exit(1);
}
