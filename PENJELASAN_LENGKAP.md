# 📚 Panduan Detail Belajar PHP untuk Pemula

Ini adalah penjelasan sangat detail baris demi baris tentang apa yang terjadi pada aplikasi PHP kamu. Karena kamu baru belajar, bayangkan PHP sebagai seorang "Pelayan Restoran", MySQL sebagai "Dapur", dan Browser (HTML/CSS) sebagai "Menu dan Meja Makan".

Semua blok kode dalam website ini menggunakan pola yang sangat mirip, jadi begitu kamu paham 3 hal utama di bawah ini, kamu pasti akan mengerti seluruh isi aplikasi ini!

---

## 1️⃣ BAGAIMANA CARA HTML DAN PHP BERKOMUNIKASI?

Perhatikan blok ini di file manapun (misal `create.php`, `login.php`):
```html
<form method="POST">
    <input type="text" name="nama_gedung">
    <button type="submit">Simpan</button>
</form>
```

**Penjelasan:**
Saat tombol "Simpan" diklik, HTML akan mengumpulkan semua tulisan yang kamu ketik di dalam `<input>`, lalu membungkusnya dalam "paket" bernama **POST**. "Paket" ini dikirim ke server (PHP).

Lalu bagaimana PHP menangkapnya? Perhatikan kode PHP di bagian atas file:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_gedung'];
}
```
**Penjelasan:**
- `$_SERVER['REQUEST_METHOD'] === 'POST'` artinya PHP mengecek: *"Eh, apakah barusan ada pengunjung yang menekan tombol submit form?"*
- Jika iya, PHP akan membuka "paket" tersebut menggunakan variabel `$_POST`.
- Teks di dalam kotak kurung siku `['nama_gedung']` **harus persis** sama dengan atribut `name="nama_gedung"` di HTML.
- Sekarang datanya sudah pindah dari HTML masuk ke variabel PHP `$nama`.

---

## 2️⃣ BAGAIMANA CARA PHP NGOBROL DENGAN DATABASE (MySQL)?

PHP tidak menyimpan data secara permanen. Ia butuh MySQL (Dapur) untuk menyimpannya. Untuk ngobrol, ia butuh "alat terjemah" yang bernama **PDO**. Inilah alasan kenapa di awal setiap file selalu ada baris `require_once '../db.php';` (untuk membawa PDO ini).

Ada 2 tipe ngobrol:

### A. MENGAMBIL DATA (READ / SELECT)
Kapan digunakan? Saat kamu ingin menampilkan tabel di layar.

```php
// 1. PHP bilang ke MySQL: "Pilihkan semua (*) kolom dari tabel dispenser!"
$stmt = $pdo->query("SELECT * FROM dispenser");

// 2. MySQL merespon, lalu PHP mengambil SEMUA datanya dan mengubahnya jadi daftar (Array)
$data = $stmt->fetchAll();
```
Kalau diterjemahkan ke bahasa sehari-hari: `$data` sekarang adalah sebuah laci besar yang isinya banyak laci kecil-kecil (tiap laci kecil adalah 1 dispenser).

Untuk menampilkannya di HTML, kita pakai perulangan `foreach`:
```php
<?php foreach ($data as $dispenser): ?>
    <tr>
        <td><?= $dispenser['Kode_Dispenser'] ?></td>
    </tr>
<?php endforeach; ?>
```
`foreach` artinya "Untuk setiap dispenser yang ada di dalam laci data, tolong buatkan baris tabel `<tr>`".
Tanda `<?=` sama saja artinya dengan `<?php echo` (Tolong cetak teks ini ke layar).

### B. MENGUBAH / MENAMBAH / MENGHAPUS DATA
Kapan digunakan? Saat ada perubahan yang dikirim lewat form POST tadi.

Karena data ini datang dari *User* (pengunjung web), PHP sangat berhati-hati karena pengunjung web bisa saja hacker. Oleh karena itu, kita **TIDAK PERNAH** memasukkan tulisan dari user secara langsung. Kita gunakan "Placeholder" (titik dua `:`).

Contoh Menyimpan (INSERT):
```php
// 1. Siapkan SQL Kosong dengan "Placeholder" (:kode, :kat)
$stmt = $pdo->prepare("INSERT INTO dispenser (Kode_Dispenser, Kategori) VALUES (:kode, :kat)");

// 2. Kirim data aslinya untuk menggantikan Placeholder tadi secara aman
$stmt->execute([
    ':kode' => $kode_dari_user,
    ':kat'  => $kategori_dari_user
]);
```

**Konsep ini adalah inti (Jantung) dari 90% seluruh file yang ada di project kamu!**
- Di `dispensers/create.php`, kita pakai `INSERT INTO`.
- Di `dispensers/edit.php`, kita pakai `UPDATE ... SET ... WHERE ID = :id`.
- Di `dispensers/delete.php`, kita pakai `DELETE FROM ... WHERE ID = :id`.

---

## 3️⃣ BAGAIMANA CARA KERJA `index.html` dan `api/`?

Project kamu ini lumayan canggih karena memisahkan antara bagian Admin (di folder `dispensers`, `staff`, dsb) dengan bagian Mahasiswa (di `index.html`).

- Halaman Admin dibangun murni pakai gabungan HTML+PHP (satu file).
- Sedangkan Halaman `index.html` dibangun murni HTML & Javascript tanpa PHP. Kok bisa ia membaca data dari MySQL?
- Jawabannya adalah karena ada folder `api/`. 

File-file di dalam `api/` (contoh: `api/get_landing_data.php`) tidak mencetak tulisan HTML, melainkan mencetak data **JSON**. JSON adalah format tulisan universal yang bisa dibaca oleh bahasa pemrograman apapun, termasuk Javascript.

**Alurnya:**
1. Kamu buka `index.html` di browser.
2. Javascript di dalam `index.html` akan memanggil (nge-*fetch*) alamat `api/get_landing_data.php` secara sembunyi-sembunyi.
3. File PHP tersebut membaca MySQL, mengambil data total laporan, dll, lalu mengubahnya jadi tulisan berformat JSON.
4. Javascript menerima tulisan JSON tersebut, lalu memecah isinya dan mengubah tampilan warna/angka di layar `index.html` tanpa perlu me-refresh halaman!

---

## CONTOH PENERAPAN ILMU DI ATAS PADA FILE LAIN

Jika kamu membuka file **`laporan/tolak.php`**, lihatlah betapa simpelnya file ini sekarang setelah kamu tahu konsepnya:
1. Menangkap ID yang mau ditolak lewat URL `$_GET['id']`
2. Menyiapkan query UPDATE: `$pdo->prepare("UPDATE water_report SET Status = 'Ditolak' WHERE WaterReport_ID = :id")`
3. Menjalankannya: `$stmt->execute([':id' => $id])`
4. Mengembalikan pengguna ke halaman semula: `header('Location: index.php'); exit;`

Tentu saja kode aslinya sedikit lebih panjang karena ditambah pesan sukses/gagal (pakai `set_flash()`), tapi "nyawa" dan alur utamanya persis seperti 4 langkah di atas!

Semoga penjelasan ultra-detail ini membantu kamu benar-benar menguasai alur PHP dasar ya! Buka file PHP kamu berdampingan dengan panduan ini, kamu pasti langsung paham bahasanya.
