// Load stats and dispensers on page load
fetch('api/get_landing_data.php')
  .then(r => r.json())
  .then(data => {
    document.getElementById('stat-dispensers').textContent = data.total_dispensers;
    document.getElementById('stat-gedung').textContent = data.total_gedung;

    const select = document.querySelector('#report-form select[name="dispenser_id"]');
    data.dispensers.forEach(d => {
      const opt = document.createElement('option');
      opt.value = d.Dispenser_ID;
      opt.textContent = d.Kode_Dispenser + ' — ' + d.Nama_Gedung + ' Lt.' + d.Lantai;
      select.appendChild(opt);
    });
  });

// Handle report form submit
document.getElementById('report-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const successEl = document.getElementById('form-success');
  const errorEl = document.getElementById('form-error');
  successEl.style.display = 'none';
  errorEl.style.display = 'none';

  fetch('api/submit_report.php', {
    method: 'POST',
    body: new FormData(this)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      successEl.textContent = data.message;
      successEl.style.display = 'block';
      document.getElementById('report-form').reset();
    } else {
      errorEl.textContent = data.message;
      errorEl.style.display = 'block';
    }
  });
});