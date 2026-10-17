// Reads QR codes from the camera with jsQR and records each one.
(() => {
  const video = document.getElementById('kamera');
  const hasil = document.getElementById('hasil');
  const form = document.getElementById('form-scan');
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  const WARNA = { tercatat: 'success', sudah: 'info', galat: 'danger' };
  let sibuk = false;
  let terakhir = '';
  let terakhirAt = 0;

  const tampil = (warna, judul, detail = '') => {
    const strong = document.createElement('strong');
    strong.textContent = judul;
    const small = document.createElement('div');
    small.className = 'fs-6 mt-1';
    small.textContent = detail;
    hasil.className = `alert alert-${warna} fs-5`;
    hasil.replaceChildren(strong, small);
  };

  const kirim = async (nis) => {
    sibuk = true;
    const data = new FormData(form);
    data.set('nis', nis);
    try {
      const res = await fetch(form.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();
      if (json.csrf) {
        // The page was open too long; take the new token and ask for another scan.
        form.querySelector('input[type=hidden]').value = json.csrf.token;
        tampil('warning', 'Scan sekali lagi.');
      } else if (!res.ok) {
        tampil('danger', json.pesan || 'Presensi belum tersimpan. Muat ulang halaman.');
      } else {
        const warna = json.status === 'terlambat' && json.hasil === 'tercatat' ? 'warning' : WARNA[json.hasil];
        tampil(warna, json.pesan, json.kelas ? `Kelas ${json.kelas}${json.jam ? ` · pukul ${json.jam}` : ''}` : '');
      }
    } catch {
      tampil('danger', 'Koneksi terputus.', 'Periksa jaringan, lalu scan lagi.');
    }
    setTimeout(() => { sibuk = false; }, 1500);
  };

  const pindai = () => {
    if (!sibuk && video.readyState === video.HAVE_ENOUGH_DATA) {
      // Smaller frames keep scanning fast on slow laptops and phones.
      const skala = Math.min(1, 640 / video.videoWidth);
      canvas.width = Math.round(video.videoWidth * skala);
      canvas.height = Math.round(video.videoHeight * skala);
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const kode = jsQR(ctx.getImageData(0, 0, canvas.width, canvas.height).data, canvas.width, canvas.height, { inversionAttempts: 'dontInvert' });
      // The same card held in front of the camera is sent once per 5 seconds.
      if (kode && kode.data && !(kode.data === terakhir && Date.now() - terakhirAt < 5000)) {
        terakhir = kode.data;
        terakhirAt = Date.now();
        kirim(kode.data);
      }
    }
    setTimeout(pindai, 150);
  };

  if (!navigator.mediaDevices) {
    tampil('danger', 'Kamera tidak dapat dibuka.', 'Buka halaman ini lewat alamat https di Chrome atau Edge.');
    return;
  }
  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
    .then((stream) => {
      video.srcObject = stream;
      video.play();
      pindai();
    })
    .catch(() => tampil('danger', 'Kamera tidak dapat dibuka.', 'Izinkan akses kamera di browser, lalu muat ulang halaman.'));
})();
