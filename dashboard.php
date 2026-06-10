<?php
// File ini (dashboard.php) ada di root folder (folder utama).
// Karena halaman utama aplikasi (untuk mahasiswa) ada di index.html,
// dan halaman dashboard admin/staff ada di dalam folder 'dashboard/index.php',
// maka file ini hanya bertugas sebagai pengalih (redirect).

// Jika ada orang nyasar membuka 'localhost/ALP-WEBPROG/dashboard.php'
// Maka perintah header('Location: index.html') akan langsung melempar mereka
// kembali ke halaman depan index.html.
header('Location: index.html');
exit; // Stop proses supaya kode tidak lanjut.
