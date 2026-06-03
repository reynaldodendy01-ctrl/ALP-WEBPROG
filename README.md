# CariGalon — Panduan Setup

Platform dashboard manajemen logistik galon air minum Universitas Ciputra.

---

## Cara Menjalankan (XAMPP / LAMP)

### 1. Pindahkan folder ke htdocs
```
Salin folder ALP/ ke:  C:\xampp\htdocs\ALP\   (Windows)
                   atau /Applications/XAMPP/htdocs/ALP/  (Mac)
```

### 2. Import Database

1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Klik **Import** → pilih file **`setup.sql`**
3. Klik **Go** → database `carigalon` akan otomatis dibuat beserta data contoh

### 3. Konfigurasi Koneksi (jika perlu)

Edit file `db.php` bagian atas:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'carigalon');
define('DB_USER', 'root');
define('DB_PASS', '');      // ← isi password MySQL jika ada
```

### 4. Buka Browser

```
http://localhost/ALP/
```
atau langsung masuk ke Dashboard Admin:
```
http://localhost/ALP/dashboard/index.php
```

---

## Struktur Halaman

| URL | Fungsi |
|-----|--------|
| `dashboard/index.php` | Dashboard utama (statistik, laporan terbaru, stok kritis) |
| `dispensers/index.php` | Daftar & kelola semua dispenser |
| `dispensers/create.php` | Tambah dispenser baru |
| `dispensers/edit.php?id=X` | Edit dispenser |
| `dispensers/detail.php?id=X` | Detail + riwayat per dispenser |
| `galon/index.php` | Pantau stok galon semua dispenser |
| `galon/edit.php?id=X` | Update jumlah stok |
| `laporan/index.php` | Daftar laporan kerusakan/masalah |
| `laporan/create.php` | Buat laporan baru |
| `laporan/edit.php?id=X` | Update status laporan + assign staff |
| `staff/index.php` | Manajemen staff maintenance |
| `refill/index.php` | Riwayat pengisian galon |
| `refill/create.php` | Catat refill baru (auto-update stok) |

---

## Struktur Database

```
carigalon
├── staff           (id, nama, no_hp, area_tugas, status)
├── dispensers      (id, nama_lokasi, gedung, lantai, status, staff_id, catatan)
├── galon           (id, dispenser_id, jumlah_tersedia, kapasitas_max, terakhir_diisi)
├── laporan         (id, dispenser_id, nama_pelapor, jenis_masalah, deskripsi, status, staff_id)
└── refill_log      (id, dispenser_id, staff_id, jumlah_galon, tanggal_refill)
```

---

## Data Seed Awal

Database sudah berisi:
- **5 staff** maintenance (4 aktif, 1 tidak aktif)
- **16 dispenser** (10 di Main Building, 6 di UC Tower berbagai lantai)
- **16 record stok galon** (ada yang kosong, rendah, dan penuh)
- **5 laporan** contoh (Pending, Diproses, Selesai)
- **10 log refill** historis

---

## Teknologi

- **PHP 8+** native (tanpa framework)
- **MySQL / MariaDB** via PDO
- **Tailwind CSS** CDN
- **Google Fonts** — Inter
- **Material Symbols** — ikon

---

*© 2025 CariGalon — Universitas Ciputra*
