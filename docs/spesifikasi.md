# Spesifikasi Spensada

Aplikasi presensi pagi dengan QR code untuk SMP 1 Dawe. Dokumen ini satu-satunya spesifikasi; bila kode berbeda dari dokumen ini, tanyakan ke pemilik repo.

## 1. Ruang lingkup

Yang ada:

1. **1 akun admin.** Tidak ada akun guru, staf, wali kelas, atau siswa.
2. **Data siswa:** NIS, nama, kelas (teks bebas, misalnya `7A`). Tambah, ubah, hapus, cari, dan import dari Excel.
3. **Scan QR** lewat kamera laptop atau HP di halaman `/panel/scan` (admin login). Hanya presensi pagi, tidak ada presensi pulang. Isi QR di kartu siswa = NIS siswa. Scan bekerja *local-first* (§2.1).
4. **Ubah status manual** oleh admin untuk siswa mana pun di tanggal mana pun.
5. **Libur:** admin menandai tanggal libur.
6. **Rekap harian** per tanggal (semua kelas atau 1 kelas), bisa diunduh sebagai Excel dan PDF.

Yang tidak ada: cetak kartu QR (kartu dibuat sekolah di luar aplikasi, dengan isi QR = NIS), presensi pulang, notifikasi, portal siswa, tahun ajaran, foto, log aktivitas, aplikasi kiosk terpisah.

## 2. Aturan presensi

Semua jam dalam WIB (`Asia/Jakarta`).

| Waktu scan | Status |
|---|---|
| sebelum 07.00.00 | Hadir |
| 07.00.00 sampai 07.59.59 | Terlambat |
| mulai 08.00.00 | Scan ditolak |

- 1 siswa hanya punya 1 catatan per hari. Scan ulang menampilkan catatan pertama, tidak mengubahnya.
- Hari sekolah: Senin sampai Sabtu. Hari Minggu dan tanggal yang ditandai libur tidak menerima scan dan tidak menghasilkan Alpa.
- **Alpa otomatis:** di hari sekolah, setelah 08.00 (atau untuk tanggal yang sudah lewat), siswa tanpa catatan tampil sebagai Alpa. Alpa dihitung saat rekap ditampilkan, tidak disimpan, jadi tidak perlu jadwal tugas (cron).
- Siswa yang ditambahkan pada hari itu tidak otomatis Alpa untuk hari itu. Siswa yang ditambahkan sesudah suatu tanggal tidak muncul di rekap tanggal tersebut.
- **Status manual:** Hadir, Terlambat, Izin, Sakit, Dispensasi, Alpa, atau Kosongkan (hapus catatan). Keterangan boleh diisi, paling panjang 255 karakter. Jam scan yang sudah ada tetap disimpan.

### 2.1 Scan local-first

Scan tidak menunggu server atau database, agar antrean siswa cepat.

- Saat halaman scan dibuka, browser memuat jam server, aturan hari ini (libur, jam masuk, jam tutup), daftar siswa, dan catatan presensi hari ini.
- Setiap scan langsung diperiksa di perangkat dengan aturan §2, hasilnya langsung tampil dengan bunyi (§2.2), dan scan yang diterima disimpan di browser (`localStorage`). Jam scan memakai jam perangkat yang sudah disamakan dengan jam server.
- Scan yang tersimpan dikirim ke server di latar belakang: paling banyak 100 scan per kiriman, setiap 5 detik selama ada scan yang belum terkirim. Admin juga bisa menekan "Kirim sekarang".
- Server memeriksa ulang setiap scan dengan jam scan dari perangkat, bukan jam kiriman tiba. Jam scan yang lebih dari 5 menit di depan jam server ditolak. Kiriman ulang tidak menggandakan catatan.
- Bila koneksi putus, halaman ditutup, atau sesi login berakhir, scan yang belum terkirim tetap tersimpan di browser itu dan dikirim saat halaman scan dibuka lagi. Halaman memperingatkan bila ditutup sebelum semua scan terkirim.
- Halaman scan yang dibuka sebelum hari ini harus dimuat ulang. Siswa atau libur yang ditambahkan setelah halaman dibuka baru dikenali setelah halaman dimuat ulang.

### 2.2 Bunyi di halaman scan

Bunyi dibuat di browser (Web Audio API), tanpa file suara.

| Kejadian | Bunyi |
|---|---|
| Tercatat Hadir | 2 nada naik |
| Tercatat Terlambat | 2 nada datar |
| Sudah tercatat sebelumnya | 1 nada pendek |
| Kode QR tidak dikenal, presensi sudah ditutup, atau hari libur | Dengung rendah |
| Scan ditolak server, kiriman mulai gagal (koneksi putus atau sesi berakhir), atau halaman dibuka kemarin | 2 nada turun, sekali saja selama kiriman masih gagal |

- Tombol "Suara" menyalakan atau mematikan bunyi. Pilihan disimpan di browser itu.
- Browser baru mengizinkan bunyi setelah halaman disentuh atau diklik. Bila bunyi belum aktif, tombol bertuliskan "Nyalakan suara"; sentuh tombol itu sekali setelah membuka halaman scan.

## 3. Data

| Tabel | Kolom |
|---|---|
| `admin` | `id`, `username` (unik), `password_hash` |
| `siswa` | `id`, `nis` (unik, huruf/angka/titik/minus, ≤ 20), `nama` (≤ 100), `kelas` (≤ 20) |
| `presensi` | `id`, `siswa_id` → `siswa` (hapus siswa = hapus presensinya), `tanggal`, `status`, `jam_scan` (kosong bila diisi manual), `keterangan`; unik (`siswa_id`, `tanggal`) |
| `libur` | `tanggal` (kunci), `keterangan` (≤ 100) |

Semua tabel punya `created_at` dan `updated_at` kecuali `libur`.

## 4. Import siswa

- File `.xlsx`, `.xls`, atau `.csv`, paling besar 5 MB. Kolom A NIS, B Nama, C Kelas. Baris pertama boleh judul kolom.
- NIS yang sudah ada diperbarui nama dan kelasnya; NIS baru ditambahkan.
- Bila ada 1 baris salah (kolom kosong, NIS ganda di file, NIS tidak valid), seluruh file tidak disimpan dan semua baris yang salah ditampilkan.

## 5. Halaman

| URL | Isi |
|---|---|
| `/login` | Login admin. Paling banyak 10 percobaan per menit per alamat jaringan. |
| `/panel/scan` | Kamera, hasil scan, dan jumlah scan yang belum terkirim. Kiriman scan: `POST /panel/scan` dengan JSON `{"scan": [{"id", "nis", "waktu"}]}`. |
| `/panel/rekap` | Rekap harian, ubah status, unduh Excel/PDF (`/panel/rekap/excel`, `/panel/rekap/pdf`) |
| `/panel/siswa` | Daftar siswa, tambah, ubah, hapus, import |
| `/panel/libur` | Tanggal libur |
| `/panel/password` | Ganti password admin |

Sesi admin berakhir setelah 8 jam. Mengganti password mengakhiri sesi di perangkat lain.

## 6. Teknis

- CodeIgniter 4.7, PHP 8.3+, MySQL 8.4, Bootstrap 5.3 standar (file lokal, tanpa CDN).
- Library: PhpSpreadsheet (Excel), mPDF (PDF rekap), jsQR 1.4.0 (membaca QR dari kamera).
- Kamera hanya bisa dibuka lewat `https` (atau `localhost`), di Chrome/Edge.
- Membuat admin atau mengganti password yang lupa: `php spark admin:password`.
