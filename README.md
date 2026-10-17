# Spensada

Aplikasi presensi pagi dengan QR code untuk SMP 1 Dawe, Kudus. Siswa menunjukkan kartu QR ke kamera, status Hadir atau Terlambat tercatat otomatis, dan admin mengunduh rekap harian dalam Excel atau PDF.

Spesifikasi lengkap: [`docs/spesifikasi.md`](docs/spesifikasi.md).

## Fitur

- 1 akun admin.
- Data siswa (NIS, nama, kelas): tambah, ubah, hapus, import Excel.
- Cetak kartu QR siswa (PDF).
- Scan QR lewat kamera laptop atau HP: sebelum 07.00 Hadir, 07.00–08.00 Terlambat, setelah 08.00 ditutup dan siswa yang belum scan menjadi Alpa.
- Izin, Sakit, Dispensasi, dan status lain diisi manual oleh admin.
- Tanggal libur (hari Minggu otomatis libur).
- Rekap harian, unduh Excel dan PDF.

## Teknologi

CodeIgniter 4.7 (PHP 8.3+), MySQL 8.4, Bootstrap 5.3, PhpSpreadsheet, mPDF, chillerlan/php-qrcode, jsQR. Zona waktu `Asia/Jakarta` (WIB).

## Instalasi (Windows + Laragon)

Jalankan di terminal Laragon, dari folder proyek:

```powershell
composer install
copy env .env
```

Isi `.env`: `CI_ENVIRONMENT`, `app.baseURL` (misalnya `https://spensada.test/`), koneksi `database.default.*`, dan `encryption.key` (buat dengan `php spark key:generate`). Lalu:

```powershell
php spark migrate
php spark admin:password
```

Perintah terakhir menampilkan username dan password admin satu kali. Catat, login di `/login`, lalu ganti password di menu **Ganti password**. Bila admin lupa password, jalankan `php spark admin:password` lagi.

Aplikasi harus dibuka lewat `https`, karena kamera dan cookie login hanya bekerja di koneksi aman.

### Memperbarui dari versi lama

Struktur database berubah total. Database lama tidak dapat dimigrasikan: buat database baru yang kosong, lalu jalankan `php spark migrate` dan `php spark admin:password`.

## Pengujian

```powershell
composer test
```

## Lisensi

MIT, hak cipta Arumi Studios. Lihat [`LICENSE`](LICENSE).
