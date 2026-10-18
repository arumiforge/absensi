// Reads QR codes from the camera with jsQR. Each scan is judged on this
// device and kept in localStorage, then sent to the server in batches in
// the background, so a scan never waits for the network or the database.
(() => {
  const KUNCI = 'spensada.antrean-scan';
  const BATCH = 100;
  const JEDA_KIRIM = 5000;
  const WIB = 7 * 3600 * 1000;

  const data = JSON.parse(document.getElementById('data-scan').textContent);
  const video = document.getElementById('kamera');
  const hasil = document.getElementById('hasil');
  const form = document.getElementById('form-scan');
  const infoAntrean = document.getElementById('antrean');
  const tombolKirim = document.getElementById('kirim-sekarang');
  const kotakDitolak = document.getElementById('ditolak');
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  const WARNA = { hadir: 'success', terlambat: 'warning' };

  // Server clock minus device clock, corrected again by every batch answer.
  let selisih = data.jam - Date.now();
  let token = form.querySelector('input[type=hidden]').value;
  let headerToken = 'X-CSRF-TOKEN';
  let mengirim = false;
  let gagal = '';
  let terakhir = '';
  let terakhirAt = 0;
  const ditolak = [];

  const baca = () => {
    try {
      const isi = JSON.parse(localStorage.getItem(KUNCI) || '[]');
      return Array.isArray(isi) ? isi.filter((s) => s && typeof s.id === 'string' && typeof s.nis === 'string' && typeof s.waktu === 'string') : [];
    } catch {
      return [];
    }
  };
  const simpan = () => {
    try {
      localStorage.setItem(KUNCI, JSON.stringify(antrean));
    } catch {
      // Storage blocked or full: the scans stay in memory until they are sent.
    }
  };
  let antrean = baca();

  const idBaru = () => (crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2)}`);
  const statusScan = (jam) => (jam < data.jamMasuk ? 'hadir' : jam < data.jamTutup ? 'terlambat' : null);
  const labelJam = (jam) => jam.slice(0, 5).replace(':', '.');
  const kunci = (nis) => nis.trim().toLowerCase();

  // WIB date and time from the corrected clock, without the device time zone.
  const sekarang = () => {
    const iso = new Date(Date.now() + selisih + WIB).toISOString();
    return { tanggal: iso.slice(0, 10), jam: iso.slice(11, 19) };
  };

  // Today's records: from the server when the page loaded, plus scans not sent yet.
  const tercatat = new Map(Object.entries(data.tercatat));
  for (const scan of antrean) {
    const [tanggal, jam] = scan.waktu.split(' ');
    if (tanggal === data.tanggal && !tercatat.has(kunci(scan.nis))) {
      tercatat.set(kunci(scan.nis), [statusScan(jam) || 'terlambat', labelJam(jam)]);
    }
  }

  const tampil = (warna, judul, detail = '') => {
    const strong = document.createElement('strong');
    strong.textContent = judul;
    const small = document.createElement('div');
    small.className = 'fs-6 mt-1';
    small.textContent = detail;
    hasil.className = `alert alert-${warna} fs-5`;
    hasil.replaceChildren(strong, small);
  };

  const perbaruiInfo = () => {
    const n = antrean.length;
    const jumlah = `${n.toLocaleString('id-ID')} scan`;
    // A new CSRF token is retried in the next round without bothering the admin.
    const masalah = gagal === 'csrf' ? '' : gagal;
    tombolKirim.hidden = n === 0;
    infoAntrean.className = n > 0 && masalah ? 'text-danger' : 'text-body-secondary';
    if (n === 0) {
      infoAntrean.textContent = 'Semua scan sudah terkirim.';
    } else if (masalah === 'login') {
      infoAntrean.textContent = `${jumlah} belum terkirim karena sesi login berakhir. Scan tetap tersimpan di perangkat ini. Login lagi di tab baru, lalu tekan Kirim sekarang.`;
    } else if (masalah === 'koneksi') {
      infoAntrean.textContent = `${jumlah} tersimpan di perangkat ini. Koneksi terputus; scan dikirim otomatis saat koneksi kembali.`;
    } else if (masalah) {
      infoAntrean.textContent = `${jumlah} belum terkirim. Server belum dapat menyimpan; dicoba lagi otomatis.`;
    } else {
      infoAntrean.textContent = `${jumlah} belum terkirim. Dikirim otomatis.`;
    }
  };

  const tampilDitolak = (daftar, batch) => {
    for (const item of daftar) {
      const scan = batch.find((s) => s.id === item.id);
      const siswa = scan && data.siswa[kunci(scan.nis)];
      ditolak.unshift(`${siswa ? siswa[0] : scan ? scan.nis : ''}: ${item.pesan}`);
    }
    ditolak.splice(10);
    const judul = document.createElement('strong');
    judul.textContent = 'Scan berikut tidak disimpan server:';
    const ul = document.createElement('ul');
    ul.className = 'mb-0 mt-1';
    for (const teks of ditolak) {
      const li = document.createElement('li');
      li.textContent = teks;
      ul.append(li);
    }
    kotakDitolak.replaceChildren(judul, ul);
    kotakDitolak.hidden = false;
  };

  const kirimAntrean = async () => {
    if (mengirim || antrean.length === 0) {
      return;
    }
    mengirim = true;
    const batch = antrean.slice(0, BATCH);
    const t1 = Date.now();
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: JSON.stringify({ scan: batch }),
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', [headerToken]: token },
      });
      const json = await res.json();
      if (json.csrf) {
        // The page was open too long; the next round uses the new token.
        headerToken = json.csrf.header;
        token = json.csrf.token;
        gagal = 'csrf';
      } else if (res.status === 401) {
        gagal = 'login';
      } else if (!res.ok) {
        gagal = 'server';
      } else {
        // The server read its clock about halfway between sending and receiving.
        selisih = json.jam - Math.round((t1 + Date.now()) / 2);
        const selesai = new Set(json.hasil.map((h) => h.id));
        antrean = antrean.filter((s) => !selesai.has(s.id));
        simpan();
        const galat = json.hasil.filter((h) => h.hasil === 'galat');
        if (galat.length > 0) {
          tampilDitolak(galat, batch);
        }
        gagal = '';
      }
    } catch {
      gagal = 'koneksi';
    }
    mengirim = false;
    perbaruiInfo();
    if (!gagal && antrean.length > 0) {
      setTimeout(kirimAntrean, 0);
    }
  };

  const catat = (kode) => {
    const siswa = data.siswa[kunci(kode)];
    if (!siswa) {
      tampil('danger', 'Kode QR tidak dikenal. Pastikan kartu milik siswa yang terdaftar.');
      return;
    }
    const [nama, kelas] = siswa;
    const { tanggal, jam } = sekarang();
    if (tanggal !== data.tanggal) {
      tampil('warning', 'Halaman scan ini dibuka kemarin.', 'Muat ulang halaman, lalu scan lagi.');
      return;
    }
    if (data.libur) {
      tampil('danger', `Hari ini libur (${data.libur}). Presensi tidak dicatat.`);
      return;
    }
    const ada = tercatat.get(kunci(kode));
    if (ada) {
      tampil('info', `${nama} sudah tercatat ${data.status[ada[0]]}${ada[1] ? ` pukul ${ada[1]}` : ''}.`, `Kelas ${kelas}`);
      return;
    }
    const status = statusScan(jam);
    if (!status) {
      tampil('danger', `Presensi pagi sudah ditutup pukul ${data.labelTutup}. Minta admin mengubah status bila perlu.`);
      return;
    }
    antrean.push({ id: idBaru(), nis: kode.trim(), waktu: `${tanggal} ${jam}` });
    simpan();
    tercatat.set(kunci(kode), [status, labelJam(jam)]);
    tampil(WARNA[status], `${nama} tercatat ${data.status[status]}.`, `Kelas ${kelas} · pukul ${labelJam(jam)}`);
    perbaruiInfo();
  };

  const pindai = () => {
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      // Smaller frames keep scanning fast on slow laptops and phones.
      const skala = Math.min(1, 640 / video.videoWidth);
      canvas.width = Math.round(video.videoWidth * skala);
      canvas.height = Math.round(video.videoHeight * skala);
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const kode = jsQR(ctx.getImageData(0, 0, canvas.width, canvas.height).data, canvas.width, canvas.height, { inversionAttempts: 'dontInvert' });
      // The same card held in front of the camera counts once per 5 seconds.
      if (kode && kode.data && !(kode.data === terakhir && Date.now() - terakhirAt < 5000)) {
        terakhir = kode.data;
        terakhirAt = Date.now();
        catat(kode.data);
      }
    }
    setTimeout(pindai, 150);
  };

  tombolKirim.addEventListener('click', () => {
    gagal = '';
    kirimAntrean();
  });
  setInterval(kirimAntrean, JEDA_KIRIM);
  window.addEventListener('online', kirimAntrean);
  window.addEventListener('beforeunload', (e) => {
    if (antrean.length > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });
  // A page restored from the back/forward cache has an old clock and old data.
  window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
      window.location.reload();
    }
  });
  perbaruiInfo();
  kirimAntrean();

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
