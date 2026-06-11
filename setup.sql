

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- DATABASE

CREATE TABLE `maintenance_staff` (
  `Staff_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nama` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `No_Telp` varchar(20) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Staff','Admin') NOT NULL DEFAULT 'Staff',
  `Gedung` varchar(50) NULL,
  PRIMARY KEY (`Staff_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- password123 (bcrypt)
INSERT INTO `maintenance_staff` (`Staff_ID`, `Nama`, `Email`, `No_Telp`, `Password`, `Role`, `Gedung`) VALUES
(1, 'Budi Santoso',     'budi.santoso@uc.ac.id',   '08111234567', '$2y$10$NhBfk2CUcMbcoANvnjTiIuIy/wMHSfT1lgxTku2tGxoeDiMP9Clj.', 'Admin', NULL),
(2, 'Agus Wahyudi',     'agus.wahyudi@uc.ac.id',   '08119876543', '$2y$10$NhBfk2CUcMbcoANvnjTiIuIy/wMHSfT1lgxTku2tGxoeDiMP9Clj.', 'Staff', 'Main Building'),
(3, 'Siti Rahayu',      'siti.rahayu@uc.ac.id',    '08221122334', '$2y$10$NhBfk2CUcMbcoANvnjTiIuIy/wMHSfT1lgxTku2tGxoeDiMP9Clj.', 'Staff', 'Main Building'),
(4, 'Deni Firmansyah',  'deni.f@uc.ac.id',          '08225566778', '$2y$10$NhBfk2CUcMbcoANvnjTiIuIy/wMHSfT1lgxTku2tGxoeDiMP9Clj.', 'Staff', 'UC Tower'),
(5, 'Rina Kusuma',      'rina.kusuma@uc.ac.id',    '08119001234', '$2y$10$NhBfk2CUcMbcoANvnjTiIuIy/wMHSfT1lgxTku2tGxoeDiMP9Clj.', 'Staff', 'UC Tower');

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `area_tugas` varchar(100) NOT NULL,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `staff` (`id`, `nama`, `no_hp`, `area_tugas`, `status`) VALUES
(1, 'Agus Wahyudi',    '08119876543', 'Main Building Lt. 1-4',  'Aktif'),
(2, 'Siti Rahayu',     '08221122334', 'Main Building Lt. 5-7',  'Aktif'),
(3, 'Deni Firmansyah', '08225566778', 'UC Tower Lt. 1-8',       'Aktif'),
(4, 'Rina Kusuma',     '08119001234', 'UC Tower Lt. 9-16',      'Aktif'),
(5, 'Budi Santoso',    '08111234567', 'UC Tower Lt. 17-24',     'Aktif');


CREATE TABLE `lokasi` (
  `Lokasi_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nama_Gedung` varchar(100) NOT NULL,
  `Lantai` int(11) NOT NULL,
  `Keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Lokasi_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `lokasi` (`Lokasi_ID`, `Nama_Gedung`, `Lantai`, `Keterangan`) VALUES
(1,  'Main Building', 1, 'Lobby utama samping resepsionis'),
(2,  'Main Building', 1, 'Koridor selatan dekat pintu masuk samping'),
(3,  'Main Building', 2, 'Dekat kantin/food court area barat'),
(4,  'Main Building', 2, 'Koridor utara dekat tangga darurat'),
(5,  'Main Building', 3, 'Koridor timur dekat Lab Komputer SIFT'),
(6,  'Main Building', 3, 'Collaboration Space Lt. 3 (dekat printer)'),
(7,  'Main Building', 4, 'Lounge mahasiswa Lt. 4'),
(8,  'Main Building', 4, 'Koridor barat dekat ruang dosen'),
(9,  'Main Building', 5, 'Depan Lab Komputer SIFT Lt. 5'),
(10, 'Main Building', 5, 'Collaboration Space Lt. 5 (SIFT area)'),
(11, 'Main Building', 6, 'Koridor selatan Lt. 6'),
(12, 'Main Building', 6, 'Depan ruang kelas Lt. 6 area timur'),
(13, 'Main Building', 7, 'Theater Lt. 7 — area foyer'),
(14, 'Main Building', 7, 'Koridor luar Theater Lt. 7'),
(15, 'UC Tower', 1,  'Lobby utama UC Tower — dekat resepsionis'),
(16, 'UC Tower', 1,  'Area parkir dalam / pintu masuk basement UC Tower'),
(17, 'UC Tower', 2,  'Area tunggu Lt. 2 UC Tower'),
(18, 'UC Tower', 3,  'Koridor utara Lt. 3 UC Tower'),
(19, 'UC Tower', 4,  'Depan ruang kelas Lt. 4 UC Tower'),
(20, 'UC Tower', 5,  'Student Lounge Lt. 5 UC Tower'),
(21, 'UC Tower', 6,  'Koridor timur Lt. 6 UC Tower'),
(22, 'UC Tower', 7,  'Depan Lab Komputer Lt. 7 UC Tower'),
(23, 'UC Tower', 8,  'Koridor barat Lt. 8 UC Tower'),
(24, 'UC Tower', 9,  'Depan ruang kelas Lt. 9 UC Tower'),
(25, 'UC Tower', 10, 'Lounge mahasiswa Lt. 10 UC Tower'),
(26, 'UC Tower', 11, 'Koridor selatan Lt. 11 UC Tower'),
(27, 'UC Tower', 12, 'Depan Lab Fotografi Lt. 12 UC Tower'),
(28, 'UC Tower', 13, 'Koridor utara Lt. 13 UC Tower'),
(29, 'UC Tower', 14, 'Library area Lt. 14 UC Tower'),
(30, 'UC Tower', 14, 'Ruang diskusi Lt. 14 UC Tower'),
(31, 'UC Tower', 15, 'IBM RC Lt. 15 — dekat Theater IBM'),
(32, 'UC Tower', 16, 'Koridor selatan Lt. 16 UC Tower'),
(33, 'UC Tower', 17, 'Depan ruang kelas Lt. 17 UC Tower'),
(34, 'UC Tower', 18, 'Fashion Design Studio Lt. 18 UC Tower'),
(35, 'UC Tower', 18, 'Koridor timur Lt. 18 UC Tower'),
(36, 'UC Tower', 19, 'Depan Art Studio Lt. 19 UC Tower'),
(37, 'UC Tower', 20, 'Koridor selatan Lt. 20 UC Tower'),
(38, 'UC Tower', 21, 'Creative Theater foyer Lt. 21 UC Tower'),
(39, 'UC Tower', 22, 'Lab Komputer Lt. 22 UC Tower'),
(40, 'UC Tower', 23, 'Multifunction Hall foyer Lt. 23 UC Tower'),
(41, 'UC Tower', 24, 'Rooftop Lounge / Multipurpose Hall kapasitas 800 — Lt. 24');


CREATE TABLE `dispenser` (
  `Dispenser_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Lokasi_ID` bigint(20) UNSIGNED NOT NULL,
  `Kode_Dispenser` varchar(50) NOT NULL,
  `Kategori` enum('Normal','Hot & Cold','Hot, Cold & Normal') NOT NULL DEFAULT 'Normal',
  PRIMARY KEY (`Dispenser_ID`),
  KEY `Lokasi_ID` (`Lokasi_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `dispenser` (`Dispenser_ID`, `Lokasi_ID`, `Kode_Dispenser`, `Kategori`) VALUES
(1,  1,  'DISP-MB-L1-A', 'Hot & Cold'),
(2,  2,  'DISP-MB-L1-B', 'Normal'),
(3,  3,  'DISP-MB-L2-A', 'Hot, Cold & Normal'),
(4,  4,  'DISP-MB-L2-B', 'Normal'),
(5,  5,  'DISP-MB-L3-A', 'Hot & Cold'),
(6,  6,  'DISP-MB-L3-B', 'Normal'),
(7,  7,  'DISP-MB-L4-A', 'Hot & Cold'),
(8,  8,  'DISP-MB-L4-B', 'Normal'),
(9,  9,  'DISP-MB-L5-A', 'Hot, Cold & Normal'),
(10, 10, 'DISP-MB-L5-B', 'Normal'),
(11, 11, 'DISP-MB-L6-A', 'Hot & Cold'),
(12, 12, 'DISP-MB-L6-B', 'Normal'),
(13, 13, 'DISP-MB-L7-A', 'Hot & Cold'),
(14, 14, 'DISP-MB-L7-B', 'Normal'),
(15, 15, 'DISP-UCT-L1-A', 'Hot, Cold & Normal'),
(16, 16, 'DISP-UCT-L1-B', 'Normal'),
(17, 17, 'DISP-UCT-L2-A', 'Hot & Cold'),
(18, 18, 'DISP-UCT-L3-A', 'Normal'),
(19, 19, 'DISP-UCT-L4-A', 'Hot & Cold'),
(20, 20, 'DISP-UCT-L5-A', 'Hot, Cold & Normal'),
(21, 21, 'DISP-UCT-L6-A', 'Normal'),
(22, 22, 'DISP-UCT-L7-A', 'Hot & Cold'),
(23, 23, 'DISP-UCT-L8-A', 'Normal'),
(24, 24, 'DISP-UCT-L9-A', 'Hot & Cold'),
(25, 25, 'DISP-UCT-L10-A', 'Hot, Cold & Normal'),
(26, 26, 'DISP-UCT-L11-A', 'Normal'),
(27, 27, 'DISP-UCT-L12-A', 'Hot & Cold'),
(28, 28, 'DISP-UCT-L13-A', 'Normal'),
(29, 29, 'DISP-UCT-L14-A', 'Hot, Cold & Normal'),
(30, 30, 'DISP-UCT-L14-B', 'Normal'),
(31, 31, 'DISP-UCT-L15-A', 'Hot & Cold'),
(32, 32, 'DISP-UCT-L16-A', 'Normal'),
(33, 33, 'DISP-UCT-L17-A', 'Hot & Cold'),
(34, 34, 'DISP-UCT-L18-A', 'Hot, Cold & Normal'),
(35, 35, 'DISP-UCT-L18-B', 'Normal'),
(36, 36, 'DISP-UCT-L19-A', 'Hot & Cold'),
(37, 37, 'DISP-UCT-L20-A', 'Normal'),
(38, 38, 'DISP-UCT-L21-A', 'Hot & Cold'),
(39, 39, 'DISP-UCT-L22-A', 'Normal'),
(40, 40, 'DISP-UCT-L23-A', 'Hot, Cold & Normal'),
(41, 41, 'DISP-UCT-L24-A', 'Hot & Cold');


CREATE TABLE `dispensers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(150) NOT NULL,
  `gedung` enum('Main Building','UC Tower') NOT NULL,
  `lantai` tinyint(3) UNSIGNED NOT NULL,
  `kode_dispenser` varchar(50) NOT NULL DEFAULT '',
  `status` enum('Normal','Kosong','Rusak','Maintenance') NOT NULL DEFAULT 'Normal',
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `dispensers` (`id`, `nama_lokasi`, `gedung`, `lantai`, `kode_dispenser`, `status`, `staff_id`, `catatan`) VALUES
-- MAIN BUILDING — 7 lantai
(1,  'Lobby Utama — Samping Resepsionis',           'Main Building', 1, 'DISP-MB-L1-A', 'Normal',      1, NULL),
(2,  'Koridor Selatan — Dekat Pintu Masuk Samping', 'Main Building', 1, 'DISP-MB-L1-B', 'Normal',      1, NULL),
(3,  'Dekat Kantin / Food Court Area Barat',        'Main Building', 2, 'DISP-MB-L2-A', 'Kosong',      1, 'Perlu diisi ulang segera'),
(4,  'Koridor Utara Lt. 2 — Dekat Tangga Darurat',  'Main Building', 2, 'DISP-MB-L2-B', 'Normal',      1, NULL),
(5,  'Koridor Timur — Dekat Lab Komputer SIFT',     'Main Building', 3, 'DISP-MB-L3-A', 'Rusak',       1, 'Bocor di bagian keran panas'),
(6,  'Collaboration Space Lt. 3 (Dekat Printer)',   'Main Building', 3, 'DISP-MB-L3-B', 'Normal',      1, NULL),
(7,  'Lounge Mahasiswa Lt. 4',                      'Main Building', 4, 'DISP-MB-L4-A', 'Normal',      2, NULL),
(8,  'Koridor Barat — Dekat Ruang Dosen Lt. 4',     'Main Building', 4, 'DISP-MB-L4-B', 'Normal',      2, NULL),
(9,  'Depan Lab Komputer SIFT Lt. 5',               'Main Building', 5, 'DISP-MB-L5-A', 'Maintenance', 2, 'Sedang diservis'),
(10, 'Collaboration Space Lt. 5 — SIFT Area',       'Main Building', 5, 'DISP-MB-L5-B', 'Normal',      2, NULL),
(11, 'Koridor Selatan Lt. 6',                       'Main Building', 6, 'DISP-MB-L6-A', 'Normal',      2, NULL),
(12, 'Depan Ruang Kelas Lt. 6 Area Timur',          'Main Building', 6, 'DISP-MB-L6-B', 'Kosong',      2, 'Galon habis sejak pagi'),
(13, 'Theater Lt. 7 — Area Foyer',                  'Main Building', 7, 'DISP-MB-L7-A', 'Normal',      2, NULL),
(14, 'Koridor Luar Theater Lt. 7',                  'Main Building', 7, 'DISP-MB-L7-B', 'Normal',      2, NULL),
-- UC TOWER — 24 lantai
(15, 'Lobby Utama UC Tower — Dekat Resepsionis',    'UC Tower', 1,  'DISP-UCT-L1-A',  'Normal', 3, NULL),
(16, 'Pintu Masuk Samping / Area Parkir Dalam',     'UC Tower', 1,  'DISP-UCT-L1-B',  'Kosong', 3, NULL),
(17, 'Area Tunggu Lt. 2 UC Tower',                  'UC Tower', 2,  'DISP-UCT-L2-A',  'Normal', 3, NULL),
(18, 'Koridor Utara Lt. 3 UC Tower',                'UC Tower', 3,  'DISP-UCT-L3-A',  'Normal', 3, NULL),
(19, 'Depan Ruang Kelas Lt. 4 UC Tower',            'UC Tower', 4,  'DISP-UCT-L4-A',  'Rusak',  3, 'Keran macet'),
(20, 'Student Lounge Lt. 5 UC Tower',               'UC Tower', 5,  'DISP-UCT-L5-A',  'Normal', 3, NULL),
(21, 'Koridor Timur Lt. 5 UC Tower',                'UC Tower', 5,  'DISP-UCT-L5-B',  'Normal', 3, NULL),
(22, 'Koridor Timur Lt. 6 UC Tower',                'UC Tower', 6,  'DISP-UCT-L6-A',  'Normal', 3, NULL),
(23, 'Depan Lab Komputer Lt. 7 UC Tower',           'UC Tower', 7,  'DISP-UCT-L7-A',  'Normal', 3, NULL),
(24, 'Koridor Barat Lt. 8 UC Tower',                'UC Tower', 8,  'DISP-UCT-L8-A',  'Normal', 4, NULL),
(25, 'Depan Ruang Kelas Lt. 9 UC Tower',            'UC Tower', 9,  'DISP-UCT-L9-A',  'Normal', 4, NULL),
(26, 'Lounge Mahasiswa Lt. 10 UC Tower',            'UC Tower', 10, 'DISP-UCT-L10-A', 'Normal', 4, NULL),
(27, 'Koridor Barat Lt. 10 UC Tower',               'UC Tower', 10, 'DISP-UCT-L10-B', 'Kosong', 4, 'Galon kosong 1 hari'),
(28, 'Koridor Selatan Lt. 11 UC Tower',             'UC Tower', 11, 'DISP-UCT-L11-A', 'Normal', 4, NULL),
(29, 'Depan Lab Fotografi Lt. 12 UC Tower',         'UC Tower', 12, 'DISP-UCT-L12-A', 'Normal', 4, NULL),
(30, 'Koridor Utara Lt. 13 UC Tower',               'UC Tower', 13, 'DISP-UCT-L13-A', 'Normal', 4, NULL),
(31, 'Library Lt. 14 UC Tower',                     'UC Tower', 14, 'DISP-UCT-L14-A', 'Normal', 4, NULL),
(32, 'Ruang Diskusi Lt. 14 UC Tower',               'UC Tower', 14, 'DISP-UCT-L14-B', 'Normal', 4, NULL),
(33, 'IBM RC Lt. 15 — Dekat Theater IBM RC',        'UC Tower', 15, 'DISP-UCT-L15-A', 'Normal', 4, NULL),
(34, 'Koridor Barat Lt. 15 UC Tower',               'UC Tower', 15, 'DISP-UCT-L15-B', 'Normal', 4, NULL),
(35, 'Koridor Selatan Lt. 16 UC Tower',             'UC Tower', 16, 'DISP-UCT-L16-A', 'Normal', 4, NULL),
(36, 'Depan Ruang Kelas Lt. 17 UC Tower',           'UC Tower', 17, 'DISP-UCT-L17-A', 'Normal', 5, NULL),
(37, 'Fashion Design Studio Lt. 18 UC Tower',       'UC Tower', 18, 'DISP-UCT-L18-A', 'Normal', 5, NULL),
(38, 'Koridor Timur Lt. 18 UC Tower',               'UC Tower', 18, 'DISP-UCT-L18-B', 'Kosong', 5, 'Belum diisi sejak kemarin'),
(39, 'Depan Art Studio Lt. 19 UC Tower',            'UC Tower', 19, 'DISP-UCT-L19-A', 'Normal', 5, NULL),
(40, 'Koridor Selatan Lt. 20 UC Tower',             'UC Tower', 20, 'DISP-UCT-L20-A', 'Normal', 5, NULL),
(41, 'Creative Theater Foyer Lt. 21 UC Tower',      'UC Tower', 21, 'DISP-UCT-L21-A', 'Normal', 5, NULL),
(42, 'Koridor Barat Lt. 21 UC Tower',               'UC Tower', 21, 'DISP-UCT-L21-B', 'Normal', 5, NULL),
(43, 'Lab Komputer Lt. 22 UC Tower',                'UC Tower', 22, 'DISP-UCT-L22-A', 'Normal', 5, NULL),
(44, 'Multifunction Hall Foyer Lt. 23 UC Tower',    'UC Tower', 23, 'DISP-UCT-L23-A', 'Normal', 5, NULL),
(45, 'Koridor Timur Lt. 23 UC Tower',               'UC Tower', 23, 'DISP-UCT-L23-B', 'Normal', 5, NULL),
(46, 'Multifunction Hall Lt. 24 — Area Utama',      'UC Tower', 24, 'DISP-UCT-L24-A', 'Normal', 5, NULL),
(47, 'Rooftop Lounge Lt. 24 UC Tower',              'UC Tower', 24, 'DISP-UCT-L24-B', 'Normal', 5, NULL);


CREATE TABLE `galon` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dispenser_id` int(10) UNSIGNED NOT NULL,
  `jumlah_tersedia` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `kapasitas_max` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `terakhir_diisi` datetime DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispenser_id` (`dispenser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `galon` (`id`, `dispenser_id`, `jumlah_tersedia`, `kapasitas_max`, `terakhir_diisi`) VALUES
(1,  1,  4, 5, '2026-06-09 08:00:00'),
(2,  2,  3, 5, '2026-06-08 14:00:00'),
(3,  3,  0, 5, '2026-06-05 10:00:00'),
(4,  4,  5, 5, '2026-06-10 07:30:00'),
(5,  5,  1, 5, '2026-06-07 09:00:00'),
(6,  6,  3, 5, '2026-06-09 11:00:00'),
(7,  7,  5, 5, '2026-06-10 07:00:00'),
(8,  8,  4, 5, '2026-06-09 08:30:00'),
(9,  9,  2, 5, '2026-06-08 08:00:00'),
(10, 10, 3, 5, '2026-06-09 09:00:00'),
(11, 11, 4, 5, '2026-06-10 06:45:00'),
(12, 12, 0, 5, '2026-06-09 06:00:00'),
(13, 13, 5, 5, '2026-06-10 07:00:00'),
(14, 14, 4, 5, '2026-06-09 10:00:00'),
(15, 15, 4, 5, '2026-06-10 07:30:00'),
(16, 16, 0, 5, '2026-06-08 08:00:00'),
(17, 17, 3, 5, '2026-06-09 12:00:00'),
(18, 18, 5, 5, '2026-06-10 07:00:00'),
(19, 19, 1, 5, '2026-06-08 09:00:00'),
(20, 20, 4, 5, '2026-06-09 13:00:00'),
(21, 21, 3, 5, '2026-06-09 13:15:00'),
(22, 22, 5, 5, '2026-06-10 07:00:00'),
(23, 23, 4, 5, '2026-06-09 08:00:00'),
(24, 24, 3, 5, '2026-06-08 15:00:00'),
(25, 25, 5, 5, '2026-06-10 06:30:00'),
(26, 26, 4, 5, '2026-06-09 10:00:00'),
(27, 27, 0, 5, '2026-06-09 10:00:00'),
(28, 28, 3, 5, '2026-06-09 11:00:00'),
(29, 29, 4, 5, '2026-06-09 09:00:00'),
(30, 30, 5, 5, '2026-06-10 07:00:00'),
(31, 31, 4, 5, '2026-06-10 07:30:00'),
(32, 32, 3, 5, '2026-06-09 13:00:00'),
(33, 33, 5, 5, '2026-06-10 07:00:00'),
(34, 34, 4, 5, '2026-06-09 10:30:00'),
(35, 35, 3, 5, '2026-06-09 10:30:00'),
(36, 36, 5, 5, '2026-06-10 07:00:00'),
(37, 37, 4, 5, '2026-06-09 08:00:00'),
(38, 38, 0, 5, '2026-06-09 08:00:00'),
(39, 39, 3, 5, '2026-06-09 12:00:00'),
(40, 40, 5, 5, '2026-06-10 07:00:00'),
(41, 41, 4, 5, '2026-06-09 14:00:00'),
(42, 42, 4, 5, '2026-06-09 14:00:00'),
(43, 43, 3, 5, '2026-06-09 09:00:00'),
(44, 44, 5, 5, '2026-06-10 07:00:00'),
(45, 45, 4, 5, '2026-06-10 07:00:00'),
(46, 46, 5, 5, '2026-06-10 07:00:00'),
(47, 47, 4, 5, '2026-06-10 07:00:00');


CREATE TABLE `reporter` (
  `Reporter_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nama` varchar(100) NOT NULL,
  `Nim` varchar(20) NOT NULL,
  PRIMARY KEY (`Reporter_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reporter` (`Reporter_ID`, `Nama`, `Nim`) VALUES
(1, 'Ahmad Rizki',       '07060121001'),
(2, 'Dewi Sartika',      '07060121002'),
(3, 'Benny Kurniawan',   '07060121003'),
(4, 'Maya Putri',        '07060121004'),
(5, 'Reza Firmansyah',   '07060121005'),
(6, 'Kevin Salim',       '07060121006'),
(7, 'Natasha Wijaya',    '07060121007'),
(8, 'Galih Prakoso',     '07060121008'),
(9, 'Florencia Tanoto',  '07060121009');


CREATE TABLE `water_report` (
  `WaterReport_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Reporter_ID` bigint(20) UNSIGNED NOT NULL,
  `Dispenser_ID` bigint(20) UNSIGNED NOT NULL,
  `Kategori` enum('Galon Kosong','Dispenser Rusak / Bocor') NOT NULL,
  `Status` enum('Pending','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Pending',
  `Deskripsi_Report` varchar(255) NULL,
  `Foto_url` varchar(255) DEFAULT NULL,
  `Reported_At` datetime NOT NULL DEFAULT current_timestamp(),
  `Resolved_At` datetime DEFAULT NULL,
  PRIMARY KEY (`WaterReport_ID`),
  KEY `Reporter_ID` (`Reporter_ID`),
  KEY `Dispenser_ID` (`Dispenser_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `water_report` (`WaterReport_ID`, `Reporter_ID`, `Dispenser_ID`, `Kategori`, `Status`, `Deskripsi_Report`, `Foto_url`, `Reported_At`, `Resolved_At`) VALUES
(1, 1,  3,  'Galon Kosong',           'Diproses', 'Galon di dekat kantin sudah kosong sejak kemarin sore', NULL, '2026-06-09 08:14:24', NULL),
(2, 2,  5,  'Dispenser Rusak / Bocor','Selesai',  'Air menetes dari keran panas dispenser koridor timur Lt. 3', NULL, '2026-06-08 10:14:24', '2026-06-09 09:17:51'),
(3, 3,  19, 'Dispenser Rusak / Bocor','Diproses', 'Keran dispenser UCT Lt. 4 tidak bisa dibuka sama sekali', NULL, '2026-06-08 23:14:24', NULL),
(4, 4,  16, 'Galon Kosong',           'Selesai',  'Dispenser lobby UC Tower kosong sejak pagi', NULL, '2026-06-07 23:14:24', '2026-06-08 23:14:24'),
(5, 5,  27, 'Galon Kosong',           'Pending',  'Dispenser Lt. 10 UCT sudah kosong lebih dari sehari', NULL, '2026-06-09 20:14:24', NULL),
(6, 6,  38, 'Galon Kosong',           'Pending',  'Dispenser Fashion Design Lt. 18 B belum diisi dari kemarin', NULL, '2026-06-09 15:00:00', NULL),
(7, 7,  12, 'Galon Kosong',           'Diproses', 'Lt. 6 Main Building area timur kosong, banyak yang butuh', NULL, '2026-06-10 07:30:00', NULL),
(8, 8,  5,  'Dispenser Rusak / Bocor','Pending',  'Dispenser MB Lt. 3 masih bocor meski sudah dilaporkan', 'uploads/rpt_mb_l3a.png', '2026-06-10 08:09:24', NULL),
(9, 9,  9,  'Dispenser Rusak / Bocor','Diproses', 'Dispenser Lab Komputer SIFT Lt. 5 tidak keluar air sama sekali', NULL, '2026-06-09 18:12:56', NULL);


CREATE TABLE `laporan` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dispenser_id` int(10) UNSIGNED NOT NULL,
  `nama_pelapor` varchar(100) NOT NULL,
  `kontak_pelapor` varchar(100) DEFAULT NULL,
  `jenis_masalah` enum('Galon Kosong','Dispenser Rusak / Bocor') NOT NULL,
  `deskripsi` text NULL,
  `status` enum('Pending','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Pending',
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispenser_id` (`dispenser_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `laporan` (`id`, `dispenser_id`, `nama_pelapor`, `kontak_pelapor`, `jenis_masalah`, `deskripsi`, `status`, `staff_id`, `catatan_admin`) VALUES
(1, 3,  'Ahmad Rizki',      'ahmad.rizki@student.uc.ac.id',     'Galon Kosong',           'Galon di dekat kantin sudah kosong sejak kemarin sore, tolong diisi', 'Diproses', 1, NULL),
(2, 5,  'Dewi Sartika',     'dewi.sartika@student.uc.ac.id',    'Dispenser Rusak / Bocor','Air menetes dari keran panas dispenser koridor timur Lt. 3',          'Selesai',  1, 'Sudah diperbaiki'),
(3, 19, 'Benny Kurniawan',  'benny.k@student.uc.ac.id',         'Dispenser Rusak / Bocor','Keran dispenser UCT Lt. 4 tidak bisa dibuka sama sekali',             'Diproses', 3, NULL),
(4, 16, 'Maya Putri',       '0812-9988-7766',                   'Galon Kosong',           'Dispenser lobby UC Tower sudah kosong 2 hari, padahal banyak yang pakai', 'Selesai', 3, NULL),
(5, 27, 'Reza Firmansyah',  'reza.f@student.uc.ac.id',          'Galon Kosong',           'Dispenser Lt. 10 UCT sudah kosong lebih dari sehari',                 'Pending',  NULL, NULL),
(6, 38, 'Kevin Salim',      'kevin.s@student.uc.ac.id',         'Galon Kosong',           'Dispenser Fashion Design Lt. 18 B belum diisi dari kemarin',          'Pending',  NULL, NULL),
(7, 12, 'Natasha Wijaya',   'natasha.w@student.uc.ac.id',       'Galon Kosong',           'Lt. 6 Main Building area timur kosong, banyak yang butuh',            'Diproses', 2, NULL),
(8, 9,  'Florencia Tanoto', 'florencia.t@student.uc.ac.id',     'Dispenser Rusak / Bocor','Dispenser Lab Komputer SIFT Lt. 5 tidak keluar air sama sekali',       'Diproses', 2, NULL);


CREATE TABLE `refill_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dispenser_id` int(10) UNSIGNED NOT NULL,
  `staff_id` int(10) UNSIGNED DEFAULT NULL,
  `jumlah_galon` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `tanggal_refill` datetime NOT NULL DEFAULT current_timestamp(),
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispenser_id` (`dispenser_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `refill_log` (`id`, `dispenser_id`, `staff_id`, `jumlah_galon`, `tanggal_refill`, `catatan`) VALUES
(1,  1,  1, 3, '2026-06-09 08:00:00', NULL),
(2,  4,  1, 5, '2026-06-10 07:30:00', 'Isi penuh'),
(3,  7,  2, 5, '2026-06-10 07:00:00', 'Isi penuh'),
(4,  8,  2, 4, '2026-06-09 08:30:00', NULL),
(5,  15, 3, 4, '2026-06-10 07:30:00', NULL),
(6,  18, 3, 5, '2026-06-10 07:00:00', 'Isi penuh'),
(7,  31, 4, 4, '2026-06-10 07:30:00', NULL),
(8,  33, 4, 5, '2026-06-10 07:00:00', 'Isi penuh'),
(9,  37, 5, 4, '2026-06-09 08:00:00', NULL),
(10, 46, 5, 5, '2026-06-10 07:00:00', 'Isi penuh');


CREATE TABLE `refill_logs` (
  `Logs_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Assignment_ID` bigint(20) UNSIGNED NOT NULL,
  `Refill_At` datetime NOT NULL DEFAULT current_timestamp(),
  `Catatan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Logs_ID`),
  KEY `Assignment_ID` (`Assignment_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `refill_logs` (`Logs_ID`, `Assignment_ID`, `Refill_At`, `Catatan`) VALUES
(1, 2, '2026-06-08 23:14:24', 'Pengisian ulang 2 galon selesai'),
(2, 4, '2026-06-09 09:17:51', NULL),
(3, 5, '2026-06-09 13:49:57', NULL),
(4, 6, '2026-06-09 13:50:16', 'Diperbaiki'),
(5, 7, '2026-06-09 18:11:36', NULL);


CREATE TABLE `staff_dispenser_assignment` (
  `Assignment_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Staff_ID` bigint(20) UNSIGNED NOT NULL,
  `Dispenser_ID` bigint(20) UNSIGNED NOT NULL,
  `WaterReport_ID` bigint(20) UNSIGNED DEFAULT NULL,
  `Status` enum('Pending','On Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `Created_At` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Assignment_ID`),
  KEY `Staff_ID` (`Staff_ID`),
  KEY `Dispenser_ID` (`Dispenser_ID`),
  KEY `WaterReport_ID` (`WaterReport_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `staff_dispenser_assignment` (`Assignment_ID`, `Staff_ID`, `Dispenser_ID`, `WaterReport_ID`, `Status`, `Created_At`) VALUES
(1, 1, 3,  1, 'On Progress', '2026-06-09 09:00:00'),
(2, 3, 16, 4, 'Completed',   '2026-06-08 00:00:00'),
(3, 3, 19, 3, 'On Progress', '2026-06-08 23:30:00'),
(4, 1, 5,  2, 'Completed',   '2026-06-09 09:17:00'),
(5, 2, 9,  9, 'On Progress', '2026-06-09 18:30:00'),
(6, 2, 12, 7, 'On Progress', '2026-06-10 07:35:00'),
(7, 4, 27, 5, 'Pending',     '2026-06-10 07:00:00'),
(8, 5, 38, 6, 'Pending',     '2026-06-10 07:00:00');

-- FK
ALTER TABLE `dispenser`
  ADD CONSTRAINT `dispenser_ibfk_1` FOREIGN KEY (`Lokasi_ID`) REFERENCES `lokasi` (`Lokasi_ID`) ON DELETE CASCADE;

ALTER TABLE `dispensers`
  ADD CONSTRAINT `dispensers_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

ALTER TABLE `galon`
  ADD CONSTRAINT `galon_ibfk_1` FOREIGN KEY (`dispenser_id`) REFERENCES `dispensers` (`id`) ON DELETE CASCADE;

ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`dispenser_id`) REFERENCES `dispensers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laporan_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

ALTER TABLE `refill_log`
  ADD CONSTRAINT `refill_log_ibfk_1` FOREIGN KEY (`dispenser_id`) REFERENCES `dispensers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refill_log_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

ALTER TABLE `refill_logs`
  ADD CONSTRAINT `refill_logs_ibfk_1` FOREIGN KEY (`Assignment_ID`) REFERENCES `staff_dispenser_assignment` (`Assignment_ID`) ON DELETE CASCADE;

ALTER TABLE `staff_dispenser_assignment`
  ADD CONSTRAINT `sda_ibfk_1` FOREIGN KEY (`Staff_ID`) REFERENCES `maintenance_staff` (`Staff_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `sda_ibfk_2` FOREIGN KEY (`Dispenser_ID`) REFERENCES `dispenser` (`Dispenser_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `sda_ibfk_3` FOREIGN KEY (`WaterReport_ID`) REFERENCES `water_report` (`WaterReport_ID`) ON DELETE SET NULL;

ALTER TABLE `water_report`
  ADD CONSTRAINT `wr_ibfk_1` FOREIGN KEY (`Reporter_ID`) REFERENCES `reporter` (`Reporter_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `wr_ibfk_2` FOREIGN KEY (`Dispenser_ID`) REFERENCES `dispenser` (`Dispenser_ID`) ON DELETE CASCADE;

COMMIT;
