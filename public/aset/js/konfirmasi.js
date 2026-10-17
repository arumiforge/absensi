// Asks before submitting forms marked with data-konfirmasi (inline handlers are blocked by the CSP).
document.addEventListener('submit', (event) => {
  const pesan = event.target.dataset.konfirmasi;
  if (pesan && !window.confirm(pesan)) {
    event.preventDefault();
  }
});
