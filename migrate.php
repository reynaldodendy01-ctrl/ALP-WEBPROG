<?php
// migrate.php
require_once __DIR__ . '/db.php';

echo "Memulai migrasi database...\n";

try {
    // 1. Cek apakah kolom Password sudah ada di tabel maintenance_staff
    $stmt = $pdo->query("SHOW COLUMNS FROM maintenance_staff LIKE 'Password'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        echo "Kolom 'Password' tidak ditemukan. Menambahkan kolom...\n";
        // Tambahkan kolom sebagai nullable dulu agar tidak error jika ada data lama
        $pdo->exec("ALTER TABLE maintenance_staff ADD COLUMN Password VARCHAR(255) NULL");
        echo "Kolom 'Password' berhasil ditambahkan.\n";
    } else {
        echo "Kolom 'Password' sudah ada.\n";
    }

    // 2. Set default password 'password123' untuk semua staff yang belum memiliki password
    $defaultPassword = 'password123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE maintenance_staff SET Password = :password WHERE Password IS NULL OR Password = ''");
    $stmt->execute([':password' => $hashedPassword]);
    $updatedCount = $stmt->rowCount();

    if ($updatedCount > 0) {
        echo "Berhasil memperbarui password untuk $updatedCount staff lama (default: '$defaultPassword').\n";
    } else {
        echo "Tidak ada staff lama yang perlu diperbarui password-nya.\n";
    }

    // 3. Ubah kolom menjadi NOT NULL
    $pdo->exec("ALTER TABLE maintenance_staff MODIFY COLUMN Password VARCHAR(255) NOT NULL");
    echo "Kolom 'Password' sekarang diatur menjadi NOT NULL.\n";

    echo "Migrasi database selesai dengan sukses!\n";
} catch (Exception $e) {
    echo "Error saat migrasi: " . $e->getMessage() . "\n";
    exit(1);
}
