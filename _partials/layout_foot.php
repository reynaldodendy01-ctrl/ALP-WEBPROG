<?php
/**
 * =============================================================================
 * _partials/layout_foot.php — Shared Footer & Penutup HTML Dashboard
 * =============================================================================
 *
 * DESKRIPSI:
 *   File ini merupakan komponen penutup layout (shared partial) yang di-include
 *   di bagian paling bawah setiap halaman dashboard CariGalon, berpasangan dengan
 *   layout_head.php yang membuka struktur HTML. Tanggung jawab utama file ini adalah
 *   menutup tag-tag HTML yang telah dibuka oleh layout_head.php, yaitu tag <main>
 *   (tempat konten halaman), tag <div> wrapper main-content, tag <body>, dan
 *   tag <html>. Tanpa file ini, struktur HTML setiap halaman dashboard tidak akan
 *   lengkap dan dapat menyebabkan rendering yang tidak konsisten di browser.
 *
 * FUNGSI UTAMA:
 *   - Menutup tag </main> yang dibuka oleh layout_head.php (konten utama halaman)
 *   - Menutup tag </div> wrapper main-content (area di sebelah kanan sidebar)
 *   - Menutup tag </body> dan </html> untuk melengkapi struktur dokumen HTML
 *   - Menjadi titik akhir eksekusi PHP untuk setiap halaman dashboard
 *
 * ALUR KERJA (FLOW):
 *   1. File di-include oleh halaman dashboard di baris terakhir setelah semua konten
 *   2. Tag </main> ditutup untuk mengakhiri area konten utama halaman
 *   3. Tag </div> ditutup untuk mengakhiri wrapper main-content (margin-left: 260px)
 *   4. Tag </body> dan </html> ditutup untuk melengkapi dokumen HTML
 *   5. Eksekusi PHP berakhir; respons dikirim ke browser pengguna
 *
 * TABEL DATABASE YANG DIAKSES:
 *   - (tidak ada; file ini hanya berisi markup HTML penutup, tidak ada logika PHP)
 *
 * VARIABEL PENTING:
 *   - (tidak ada variabel PHP yang digunakan di file ini)
 *
 * DEPENDENCY / FILE YANG DI-INCLUDE:
 *   - (tidak ada; file ini adalah endpoint layout, tidak meng-include file lain)
 *
 * AKSES:
 *   Tidak diakses langsung oleh pengguna. Di-include secara server-side oleh
 *   semua halaman dashboard sebagai pasangan dari layout_head.php.
 *
 * CATATAN PENGEMBANG:
 *   Jika perlu menambahkan script JavaScript global (misalnya: library chart, analytics,
 *   atau script lain yang harus dimuat di akhir halaman), tambahkan sebelum tag </body>
 *   di file ini agar berlaku untuk semua halaman dashboard sekaligus.
 *
 * @author   Tim CariGalon
 * @project  CariGalon — Sistem Monitoring Dispenser Air Kampus
 * @version  1.0.0
 * =============================================================================
 */

// ===================================================
// CATATAN PENTING UNTUK PEMULA — Cara kerja file ini
// ===================================================
// File ini adalah PASANGAN dari layout_head.php.
// layout_head.php membuka tag-tag HTML seperti <body>, <div>, dan <main>.
// File ini MENUTUP tag-tag tersebut agar struktur HTML menjadi lengkap.
//
// Bayangkan seperti membuka dan menutup kurung kurawal { }:
//   layout_head.php → membuka {
//   [konten halaman] → isi di dalam
//   layout_foot.php  → menutup }
//
// Setiap halaman dashboard mengakhiri file-nya dengan:
//   <?php include __DIR__ . '/../_partials/layout_foot.php'; ?>
?>

    </main><!-- /main → Menutup tag <main class="p-8"> yang dibuka di layout_head.php (area konten utama halaman) -->
</div><!-- /main-content → Menutup <div style="margin-left:260px;"> pembungkus area konten di sebelah kanan sidebar -->

<!-- Menutup <body> dan <html> — mengakhiri dokumen HTML sepenuhnya -->
<!-- Tanpa kedua tag penutup ini, browser bisa berperilaku tidak terduga -->
</body>
</html>
