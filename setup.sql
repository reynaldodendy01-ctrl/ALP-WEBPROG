-- ============================================================
--  CariGalon — Database Setup Script
--  Jalankan di phpMyAdmin atau MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS carigalon
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE carigalon;

-- ─── Tabel Staff ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS staff (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    no_hp       VARCHAR(20)  NOT NULL,
    area_tugas  VARCHAR(100) NOT NULL,
    status      ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Tabel Dispensers ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS dispensers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lokasi VARCHAR(150) NOT NULL,
    gedung      ENUM('Main Building','UC Tower','Gedung Lain') NOT NULL,
    lantai      TINYINT UNSIGNED NOT NULL,
    status      ENUM('Normal','Kosong','Rusak','Maintenance') NOT NULL DEFAULT 'Normal',
    staff_id    INT UNSIGNED NULL,
    catatan     TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Tabel Galon (stok) ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS galon (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispenser_id    INT UNSIGNED NOT NULL,
    jumlah_tersedia TINYINT UNSIGNED NOT NULL DEFAULT 0,
    kapasitas_max   TINYINT UNSIGNED NOT NULL DEFAULT 5,
    terakhir_diisi  DATETIME NULL,
    catatan         TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dispenser_id) REFERENCES dispensers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Tabel Laporan ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS laporan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispenser_id    INT UNSIGNED NOT NULL,
    nama_pelapor    VARCHAR(100) NOT NULL,
    kontak_pelapor  VARCHAR(100) NULL,
    jenis_masalah   ENUM('Galon Kosong','Dispenser Rusak','Kebocoran','Distribusi Tidak Merata','Lainnya') NOT NULL,
    deskripsi       TEXT NOT NULL,
    status          ENUM('Pending','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Pending',
    staff_id        INT UNSIGNED NULL,
    catatan_admin   TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dispenser_id) REFERENCES dispensers(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id)    REFERENCES staff(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Tabel Refill Log ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS refill_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispenser_id    INT UNSIGNED NOT NULL,
    staff_id        INT UNSIGNED NULL,
    jumlah_galon    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    tanggal_refill  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    catatan         TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispenser_id) REFERENCES dispensers(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id)     REFERENCES staff(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  SEED DATA — Data awal contoh
-- ============================================================

-- Staff
INSERT INTO staff (nama, no_hp, area_tugas, status) VALUES
('Budi Santoso',    '08111234567', 'Main Building Lt. 1-5',  'Aktif'),
('Agus Wahyudi',    '08119876543', 'Main Building Lt. 6-10', 'Aktif'),
('Siti Rahayu',     '08221122334', 'UC Tower Lt. 1-5',       'Aktif'),
('Deni Firmansyah', '08225566778', 'UC Tower Lt. 6-10',      'Aktif'),
('Rina Kusuma',     '08119001234', 'Main Building Semua',    'Tidak Aktif');

-- Dispensers — Main Building
INSERT INTO dispensers (nama_lokasi, gedung, lantai, status, staff_id, catatan) VALUES
('Lobby Utama Main Building',   'Main Building', 1,  'Normal',      1, NULL),
('Koridor Selatan Lt. 1',       'Main Building', 1,  'Normal',      1, NULL),
('Dekat Kantin Lt. 2',          'Main Building', 2,  'Kosong',      1, 'Perlu diisi ulang segera'),
('Ruang Baca Lt. 2',            'Main Building', 2,  'Normal',      1, NULL),
('Depan Lift Lt. 3',            'Main Building', 3,  'Normal',      2, NULL),
('Koridor Timur Lt. 3',         'Main Building', 3,  'Rusak',       2, 'Bocor di bagian keran panas'),
('Area Printer Lt. 4',          'Main Building', 4,  'Normal',      2, NULL),
('Lounge Mahasiswa Lt. 4',      'Main Building', 4,  'Normal',      2, NULL),
('Depan Lab Komputer Lt. 5',    'Main Building', 5,  'Maintenance', 2, 'Sedang diservis'),
('Koridor Barat Lt. 5',         'Main Building', 5,  'Normal',      2, NULL),
-- Dispensers — UC Tower
('Lobby UC Tower',              'UC Tower',      1,  'Normal',      3, NULL),
('Dekat Resepsionis UC Tower',  'UC Tower',      1,  'Kosong',      3, NULL),
('Area Tunggu Lt. 2 UC Tower',  'UC Tower',      2,  'Normal',      3, NULL),
('Koridor Utara Lt. 3 UC Tower','UC Tower',      3,  'Normal',      4, NULL),
('Depan Ruang Rapat Lt. 4',     'UC Tower',      4,  'Rusak',       4, 'Keran macet'),
('Lounge Lt. 5 UC Tower',       'UC Tower',      5,  'Normal',      4, NULL);

-- Galon stok awal
INSERT INTO galon (dispenser_id, jumlah_tersedia, kapasitas_max, terakhir_diisi) VALUES
(1,  4, 5, NOW() - INTERVAL 1 DAY),
(2,  3, 5, NOW() - INTERVAL 2 DAY),
(3,  0, 5, NOW() - INTERVAL 5 DAY),
(4,  5, 5, NOW()),
(5,  2, 5, NOW() - INTERVAL 3 DAY),
(6,  1, 5, NOW() - INTERVAL 4 DAY),
(7,  4, 5, NOW() - INTERVAL 1 DAY),
(8,  5, 5, NOW()),
(9,  2, 5, NOW() - INTERVAL 2 DAY),
(10, 3, 5, NOW() - INTERVAL 1 DAY),
(11, 4, 5, NOW()),
(12, 0, 5, NOW() - INTERVAL 6 DAY),
(13, 3, 5, NOW() - INTERVAL 1 DAY),
(14, 5, 5, NOW()),
(15, 1, 5, NOW() - INTERVAL 3 DAY),
(16, 4, 5, NOW() - INTERVAL 2 DAY);

-- Laporan
INSERT INTO laporan (dispenser_id, nama_pelapor, kontak_pelapor, jenis_masalah, deskripsi, status, staff_id) VALUES
(3,  'Ahmad Rizki',    'ahmad.rizki@student.ciputra.ac.id',  'Galon Kosong',           'Galon di dekat kantin sudah kosong sejak kemarin sore, tolong diisi',       'Diproses', 1),
(6,  'Dewi Sartika',   'dewi.sartika@student.ciputra.ac.id', 'Kebocoran',              'Air menetes dari keran panas dispenser koridor timur lt 3',                  'Pending',  NULL),
(15, 'Benny Kurniawan','benny@student.ciputra.ac.id',        'Dispenser Rusak',        'Keran dispenser UC Tower lt 4 tidak bisa dibuka sama sekali',               'Diproses', 4),
(12, 'Maya Putri',     '0812-9988-7766',                     'Galon Kosong',           'Dispenser lobby UC Tower sudah kosong 2 hari, padahal banyak yang pakai',    'Selesai',  3),
(5,  'Reza Firmansyah','reza.f@student.ciputra.ac.id',       'Distribusi Tidak Merata','Lt 3 Main Building cuma ada 1 dispenser yang jalan, yang lain kosong terus', 'Pending',  NULL);

-- Refill Log
INSERT INTO refill_log (dispenser_id, staff_id, jumlah_galon, tanggal_refill, catatan) VALUES
(1,  1, 3, NOW() - INTERVAL 1 DAY,  NULL),
(4,  1, 5, NOW(),                   'Isi penuh'),
(7,  2, 2, NOW() - INTERVAL 1 DAY,  NULL),
(8,  2, 5, NOW(),                   'Isi penuh'),
(11, 3, 4, NOW(),                   NULL),
(14, 4, 5, NOW(),                   'Isi penuh'),
(12, 3, 4, NOW() - INTERVAL 6 DAY,  NULL),
(3,  1, 0, NOW() - INTERVAL 5 DAY,  'Stok habis, belum bisa diisi'),
(13, 3, 3, NOW() - INTERVAL 1 DAY,  NULL),
(16, 4, 4, NOW() - INTERVAL 2 DAY,  NULL);
