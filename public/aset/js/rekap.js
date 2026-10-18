// Fills the "Ubah status" dialog with the student whose button was pressed.
document.getElementById('ubah-status')?.addEventListener('show.bs.modal', (event) => {
  const tombol = event.relatedTarget;
  const form = event.currentTarget.querySelector('form');
  form.elements.siswa_id.value = tombol.dataset.siswaId;
  form.elements.keterangan.value = tombol.dataset.keterangan;
  document.getElementById('ubah-status-nama').textContent = tombol.dataset.nama;
  for (const pilihan of form.querySelectorAll('input[name=status]')) {
    pilihan.checked = pilihan.value === tombol.dataset.status;
  }
});
