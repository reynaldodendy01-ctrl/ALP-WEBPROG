/* DESKRIPSI FILE: Skrip JavaScript untuk menangani logika interaktif di halaman utama pengunjung, termasuk filter tabel dan pengiriman form laporan. */
// js/landing.js

var allDispenserStatus = [];

fetch('api/get_landing_data.php')
  .then(function(r) { return r.json(); })
  .then(function(data) {

    // Stats
    document.getElementById('stat-dispensers').textContent = data.total_dispensers;
    document.getElementById('stat-gedung').textContent = data.total_gedung;

    // Fill dispenser dropdown in report form
    var select = document.getElementById('dispenser_id');
    if (data.dispensers) {
      data.dispensers.forEach(function(d) {
        var opt = document.createElement('option');
        opt.value = d.Dispenser_ID;
        opt.textContent = d.Kode_Dispenser + ' — ' + d.Nama_Gedung + ' Lt.' + d.Lantai;
        select.appendChild(opt);
      });
    }

    // Store all dispensers, then show only Tersedia
    allDispenserStatus = data.dispenser_status || [];
    renderDispenserTable(allDispenserStatus);
  })
  .catch(function() {
    document.getElementById('report-table-body').innerHTML =
      '<tr><td colspan="3" style="text-align:center;padding:32px;color:#9ca3af;">Gagal memuat data. Coba refresh halaman.</td></tr>';
  });

// === Dispenser Status Table ===
function renderDispenserTable(dispensers) {
  var tbody = document.getElementById('report-table-body');
  var filterGedung = document.getElementById('filter-gedung').value;

  var filtered = dispensers.filter(function(d) {
    if (filterGedung && d.Nama_Gedung !== filterGedung) return false;
    return true;
  });

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:40px;color:#9ca3af;">Tidak ada data dispenser.</td></tr>';
    return;
  }

  tbody.innerHTML = '';
  filtered.forEach(function(d) {
    var statusClass = 'badge-tersedia';
    var statusText = 'Tersedia';
    if (d.status === 'Pending') {
      statusClass = 'badge-pending';
      statusText = 'Dilaporkan';
    } else if (d.status === 'Diproses') {
      statusClass = 'badge-diproses';
      statusText = 'Diproses';
    }

    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td style="font-weight:700;color:#0058bc;">' + escHtml(d.Kode_Dispenser) + '</td>' +
      '<td>' + escHtml(d.Nama_Gedung) + ' Lt.' + d.Lantai + '</td>' +
      '<td><span class="badge-status ' + statusClass + '">' + statusText + '</span></td>';
    tbody.appendChild(tr);
  });
}

// Attach filter event
var gedungFilter = document.getElementById('filter-gedung');
if (gedungFilter) {
  gedungFilter.addEventListener('change', function() {
    renderDispenserTable(allDispenserStatus);
  });
}

// === Report Form ===
document.getElementById('report-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var successEl = document.getElementById('form-success');
  var errorEl   = document.getElementById('form-error');
  successEl.style.display = 'none';
  errorEl.style.display   = 'none';

  var submitBtn = this.querySelector('button[type="submit"]');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Mengirim...';

  fetch('api/submit_report.php', {
    method: 'POST',
    body: new FormData(this)
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      successEl.textContent = data.message || 'Laporan berhasil dikirim!';
      successEl.style.display = 'block';
      document.getElementById('report-form').reset();
      document.getElementById('file-preview-container').classList.add('hidden');
    } else {
      errorEl.textContent = data.message || 'Gagal mengirim laporan.';
      errorEl.style.display = 'block';
    }
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">send</span> Kirim Laporan Sekarang';
  })
  .catch(function() {
    errorEl.textContent = 'Terjadi kesalahan koneksi. Coba lagi.';
    errorEl.style.display = 'block';
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">send</span> Kirim Laporan Sekarang';
  });
});

// Utility
function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}