-- ============================================================
--  CariGalon — Database Setup Script (ERD Redesign)
--  Jalankan di phpMyAdmin atau MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS carigalon
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE carigalon;

-- Drop tables in reverse dependency order if they exist
DROP TABLE IF EXISTS refill_logs;
DROP TABLE IF EXISTS staff_dispenser_assignment;
DROP TABLE IF EXISTS water_report;
DROP TABLE IF EXISTS dispenser;
DROP TABLE IF EXISTS lokasi;
DROP TABLE IF EXISTS reporter;
DROP TABLE IF EXISTS maintenance_staff;

-- ─── 1. Tabel Maintenance_Staff ──────────────────────────────────────
CREATE TABLE maintenance_staff (
    Staff_ID    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nama        VARCHAR(100) NOT NULL,
    Email       VARCHAR(100) NOT NULL,
    No_Telp     VARCHAR(20)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 2. Tabel Reporter ───────────────────────────────────────────────
CREATE TABLE reporter (
    Reporter_ID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nama        VARCHAR(100) NOT NULL,
    Nim         VARCHAR(20)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 3. Tabel Lokasi ─────────────────────────────────────────────────
CREATE TABLE lokasi (
    Lokasi_ID   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Nama_Gedung VARCHAR(100) NOT NULL,
    Lantai      INT NOT NULL,
    Keterangan  VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 4. Tabel Dispenser ──────────────────────────────────────────────
CREATE TABLE dispenser (
    Dispenser_ID    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Lokasi_ID       BIGINT UNSIGNED NOT NULL,
    Kode_Dispenser  VARCHAR(50) NOT NULL,
    Kategori        ENUM('Normal', 'Hot & Cold', 'Hot, Cold & Normal') NOT NULL DEFAULT 'Normal',
    FOREIGN KEY (Lokasi_ID) REFERENCES lokasi(Lokasi_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 5. Tabel Water_Report ───────────────────────────────────────────
CREATE TABLE water_report (
    WaterReport_ID   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Reporter_ID      BIGINT UNSIGNED NOT NULL,
    Dispenser_ID     BIGINT UNSIGNED NOT NULL,
    Kategori         ENUM('Galon Kosong', 'Dispenser Rusak', 'Kebocoran', 'Distribusi Tidak Merata', 'Lainnya') NOT NULL,
    Status           ENUM('Pending', 'Diproses', 'Selesai', 'Ditolak') NOT NULL DEFAULT 'Pending',
    Deskripsi_Report VARCHAR(255) NOT NULL,
    Foto_url         VARCHAR(255) NULL,
    Reported_At      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Resolved_At      DATETIME NULL,
    FOREIGN KEY (Reporter_ID) REFERENCES reporter(Reporter_ID) ON DELETE CASCADE,
    FOREIGN KEY (Dispenser_ID) REFERENCES dispenser(Dispenser_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 6. Tabel Staff_Dispenser_Assignment ──────────────────────────────
CREATE TABLE staff_dispenser_assignment (
    Assignment_ID  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Staff_ID       BIGINT UNSIGNED NOT NULL,
    Dispenser_ID   BIGINT UNSIGNED NOT NULL,
    WaterReport_ID BIGINT UNSIGNED NULL,
    Status         ENUM('Pending', 'On Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    Created_At     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Staff_ID) REFERENCES maintenance_staff(Staff_ID) ON DELETE CASCADE,
    FOREIGN KEY (Dispenser_ID) REFERENCES dispenser(Dispenser_ID) ON DELETE CASCADE,
    FOREIGN KEY (WaterReport_ID) REFERENCES water_report(WaterReport_ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── 7. Tabel Refill_Logs ────────────────────────────────────────────
CREATE TABLE refill_logs (
    Logs_ID       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Assignment_ID BIGINT UNSIGNED NOT NULL,
    Refill_At     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Catatan       VARCHAR(255) NULL,
    FOREIGN KEY (Assignment_ID) REFERENCES staff_dispenser_assignment(Assignment_ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  SEED DATA — Data awal contoh
-- ============================================================

-- Maintenance Staff
INSERT INTO maintenance_staff (Nama, Email, No_Telp) VALUES
('Budi Santoso',    'budi.santoso@uc.ac.id',    '08111234567'),
('Agus Wahyudi',    'agus.wahyudi@uc.ac.id',    '08119876543'),
('Siti Rahayu',     'siti.rahayu@uc.ac.id',     '08221122334'),
('Deni Firmansyah', 'deni.f@uc.ac.id',          '08225566778'),
('Rina Kusuma',     'rina.kusuma@uc.ac.id',     '08119001234');

-- Reporters
INSERT INTO reporter (Nama, Nim) VALUES
('Ahmad Rizki',    '0706012110001'),
('Dewi Sartika',   '0706012110002'),
('Benny Kurniawan','0706012110003'),
('Maya Putri',     '0706012110004'),
('Reza Firmansyah','0706012110005');

-- Lokasi
INSERT INTO lokasi (Nama_Gedung, Lantai, Keterangan) VALUES
('Main Building', 1, 'Lobby Utama samping Resepsionis'),
('Main Building', 2, 'Dekat Kantin area barat'),
('Main Building', 3, 'Koridor timur dekat Lab Komputer'),
('UC Tower',      1, 'Lobby Tower UC'),
('UC Tower',      4, 'Dekat Ruang Rapat Lt. 4');

-- Dispenser
INSERT INTO dispenser (Lokasi_ID, Kode_Dispenser, Kategori) VALUES
(1, 'DISP-MB-101', 'Hot & Cold'),
(2, 'DISP-MB-201', 'Normal'),
(3, 'DISP-MB-301', 'Hot, Cold & Normal'),
(4, 'DISP-UCT-101', 'Hot & Cold'),
(5, 'DISP-UCT-401', 'Normal');

-- Water Report
INSERT INTO water_report (Reporter_ID, Dispenser_ID, Kategori, Status, Deskripsi_Report, Foto_url, Reported_At, Resolved_At) VALUES
(1, 2, 'Galon Kosong',           'Diproses', 'Galon di dekat kantin sudah kosong sejak kemarin sore', NULL, NOW() - INTERVAL 1 DAY, NULL),
(2, 3, 'Kebocoran',              'Pending',  'Air menetes dari keran panas dispenser koridor timur lt 3', NULL, NOW() - INTERVAL 12 HOUR, NULL),
(3, 5, 'Dispenser Rusak',        'Diproses', 'Keran dispenser UC Tower lt 4 tidak bisa dibuka sama sekali', NULL, NOW() - INTERVAL 2 DAY, NULL),
(4, 4, 'Galon Kosong',           'Selesai',  'Dispenser lobby UC Tower kosong sejak pagi', NULL, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY),
(5, 1, 'Distribusi Tidak Merata','Pending',  'Tekanan air sangat rendah pada keran dingin', NULL, NOW() - INTERVAL 3 HOUR, NULL);

-- Staff Dispenser Assignment
INSERT INTO staff_dispenser_assignment (Staff_ID, Dispenser_ID, WaterReport_ID, Status, Created_At) VALUES
(1, 2, 1, 'On Progress', NOW() - INTERVAL 18 HOUR),
(3, 4, 4, 'Completed',   NOW() - INTERVAL 2 DAY),
(4, 5, 3, 'On Progress', NOW() - INTERVAL 1 DAY),
(2, 3, 2, 'Pending',     NOW() - INTERVAL 6 HOUR);

-- Refill Logs
INSERT INTO refill_logs (Assignment_ID, Refill_At, Catatan) VALUES
(2, NOW() - INTERVAL 2 DAY, 'Pengisian ulang 2 galon selesai');
