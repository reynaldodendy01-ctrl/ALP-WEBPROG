# 🚀 Alur Belajar PHP & CRUD (Untuk Pemula)

Halo! Karena kamu masih baru di PHP, aku sudah menambahkan komentar-komentar (catatan) di dalam kode PHP-mu (khususnya di folder `dispensers`) menggunakan bahasa Indonesia yang sangat mendasar. Catatan ini tidak mengubah caramu menjalankan program, tapi bisa kamu baca untuk belajar.

Berikut adalah urutan atau "workflow" file mana saja yang sebaiknya kamu baca pertama kali agar mudah paham:

---

## 1️⃣ Mulai dari Koneksi ke Database (`db.php`)
**File:** `db.php` di folder utama.
- **Tujuan:** Pahami dulu bagaimana PHP bisa ngobrol sama MySQL.
- Di file ini ada kode `new PDO(...)`. PDO adalah cara PHP untuk login ke database kamu (XAMPP). Ibaratnya ini adalah jembatan penghubung aplikasimu dengan database `carigalon`.

## 2️⃣ Pahami Konsep READ (Membaca/Menampilkan Data)
**File:** `dispensers/index.php`
- **Tujuan:** Pahami bagaimana data dari database diambil dan ditampilkan di tabel HTML.
- **Cari Baris Ini:** `$pdo->prepare("SELECT ...")`
- **Penjelasan:** `SELECT` adalah perintah SQL untuk mengambil data. Di file ini, kita meminta PHP mengambil semua data dispenser lalu mengirimkannya ke tabel di halaman website.

## 3️⃣ Pahami Konsep CREATE (Menambah Data Baru)
**File:** `dispensers/create.php`
- **Tujuan:** Mengerti cara form HTML mengirim data, lalu PHP menyimpannya ke MySQL.
- **Cari Baris Ini:** `$pdo->prepare("INSERT INTO ...")`
- **Penjelasan:** Saat tombol "Simpan" diklik, data masuk lewat variabel `$_POST`. Lalu `INSERT INTO` adalah perintah SQL untuk memasukkan baris baru ke dalam tabel.

## 4️⃣ Pahami Konsep UPDATE (Mengubah Data)
**File:** `dispensers/edit.php`
- **Tujuan:** Cara mengambil data lama, menampilkannya di form, lalu menyimpannya kembali jika diubah.
- **Cari Baris Ini:** `$pdo->prepare("UPDATE ... SET ... WHERE ...")`
- **Penjelasan:** Bedanya dengan Create adalah, kita harus memberi tahu database **baris mana yang mau diubah** menggunakan klausa `WHERE ID = ...` (jangan sampai semuanya terubah!).

## 5️⃣ Pahami Konsep DELETE (Menghapus Data)
**File:** `dispensers/delete.php`
- **Tujuan:** Paling simpel! Cara menghapus data secara permanen.
- **Cari Baris Ini:** `$pdo->prepare("DELETE FROM ... WHERE ...")`
- **Penjelasan:** Saat kamu klik tombol "Hapus", ID akan dikirim ke file ini, dan file ini akan menjalankan perintah `DELETE` di database. File ini bekerja di balik layar, jadi setelah selesai langsung memindahkanmu (redirect) kembali ke halaman index.

---

### 💡 Tips Belajar Tambahan:
1. **Buka file-file di atas di VSCode / Editor teks kamu.**
2. Baca kode yang berwarna hijau (komentar yang diawali dengan `//`).
3. Kalau ada tulisan seperti `$_POST`, itu artinya PHP sedang mengambil data yang diketik user di form HTML.
4. Kalau ada tulisan `$_GET`, itu artinya PHP mengambil data dari link URL (contoh: `edit.php?id=5`, maka `$_GET['id']` hasilnya `5`).

Selamat belajar! Kalau kamu sudah mengerti alur di folder `dispensers`, maka kamu otomatis akan mengerti alur di folder `laporan`, `staff`, dan lainnya karena konsepnya **sama persis**.
