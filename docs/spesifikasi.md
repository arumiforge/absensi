# Spesifikasi Spensada

Aplikasi presensi pagi dengan QR code untuk SMP 1 Dawe. Dokumen ini satu-satunya spesifikasi; bila kode berbeda dari dokumen ini, tanyakan ke pemilik repo.

## 1. Ruang lingkup

Yang ada:

1. **1 akun admin.** Tidak ada akun guru, staf, wali kelas, atau siswa.
2. **Data siswa:** NIS, nama, kelas (teks bebas, misalnya `7A`). Tambah, ubah, hapus, cari, dan import dari Excel.
3. **Kartu QR:** PDF siap cetak, 12 kartu per halaman A4. Isi QR = NIS siswa.
4. **Scan QR** lewat kamera laptop atau HP di halaman `/panel/scan` (admin login). Hanya presensi pagi, tidak ada presensi pulang.
5. **Ubah status manual** oleh admin untuk siswa mana pun di tanggal mana pun.
6. **Libur:** admin menandai tanggal libur.
7. **Rekap harian** per tanggal (semua kelas atau 1 kelas), bisa diunduh sebagai Excel dan PDF.

Yang tidak ada: presensi pulang, notifikasi, portal siswa, tahun ajaran, foto, log aktivitas, aplikasi kiosk offline.

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
| `/panel/scan` | Kamera + hasil scan |
| `/panel/rekap` | Rekap harian, ubah status, unduh Excel/PDF (`/panel/rekap/excel`, `/panel/rekap/pdf`) |
| `/panel/siswa` | Daftar siswa, tambah, ubah, hapus, import, cetak kartu QR (`/panel/siswa/kartu`) |
| `/panel/libur` | Tanggal libur |
| `/panel/password` | Ganti password admin |

Sesi admin berakhir setelah 8 jam. Mengganti password mengakhiri sesi di perangkat lain.

## 6. Teknis

- CodeIgniter 4.7, PHP 8.3+, MySQL 8.4, Bootstrap 5.3 standar (file lokal, tanpa CDN).
- Library: PhpSpreadsheet (Excel), mPDF (PDF), chillerlan/php-qrcode (kartu QR), jsQR 1.4.0 (membaca QR dari kamera).
- Kamera hanya bisa dibuka lewat `https` (atau `localhost`), di Chrome/Edge.
- Membuat admin atau mengganti password yang lupa: `php spark admin:password`.
- Nama sekolah di halaman login, rekap, dan kartu QR diambil dari `Config\Spensada::$namaSekolah`.
- Urutan kerja dan posisi proyek: [`tahapan.md`](tahapan.md).
