# Tahapan Spensada

Peta tahapan proyek, disusun ulang dari nol pada 18 Oktober 2026. Status setiap tahap dicek langsung ke kode di `main`, bukan ke catatan lama.

Isi aplikasi mengikuti [`spesifikasi.md`](spesifikasi.md). Dokumen ini hanya mencatat urutan kerja dan posisinya sekarang.

## Ringkasan

| No | Tahap | Status |
|---|---|---|
| 1 | Fondasi aplikasi | Selesai |
| 2 | Akun admin dan login | Selesai |
| 3 | Data siswa dan import | Selesai |
| 4 | Kartu QR | Selesai |
| 5 | Scan QR | Selesai di kode, belum diuji di sekolah |
| 6 | Rekap harian dan status manual | Selesai |
| 7 | Libur | Selesai |
| 8 | Pengujian otomatis | Ada, perlu dijalankan ulang di Laragon |
| 9 | Pemasangan di server sekolah | Belum |
| 10 | Uji coba di sekolah | Belum |
| 11 | Mulai dipakai harian | Belum |

Arti status: **Selesai** berarti kode sudah ada di `main` dan sesuai spesifikasi. **Belum** berarti belum ada bukti di repo bahwa tahap itu sudah dikerjakan.

## 1. Fondasi aplikasi — Selesai

CodeIgniter 4.7, koneksi MySQL dengan zona waktu WIB, halaman galat berbahasa Indonesia, Bootstrap 5.3 lokal, helper format tanggal dan angka.

Bukti: `app/Database/MySQLi/`, `app/Config/Spensada.php`, `app/Helpers/format_helper.php`, `app/Views/errors/html/`, `public/galat/`, `public/aset/vendor/bootstrap/5.3.8/`, `app/Filters/Keamanan.php`, `app/Filters/Csrf.php`.

## 2. Akun admin dan login — Selesai

1 akun admin, login dibatasi 10 percobaan per menit per alamat jaringan, sesi berakhir setelah 8 jam, ganti password mengakhiri sesi di perangkat lain, perintah `php spark admin:password` untuk membuat admin atau mengganti password yang lupa.

Bukti: `app/Controllers/Login.php`, `app/Filters/Sesi.php`, `app/Controllers/Panel/Password.php`, `app/Commands/AdminPassword.php`, `app/Config/Session.php` (`expiration` 28800).

## 3. Data siswa dan import — Selesai

Daftar siswa dengan cari dan filter kelas, tambah, ubah, hapus (presensinya ikut terhapus), import `.xlsx`/`.xls`/`.csv` sampai 5 MB. Bila ada baris salah, seluruh file dibatalkan.

Bukti: `app/Controllers/Panel/Siswa.php`, `app/Services/ImportSiswa.php`, `app/Models/SiswaModel.php`, `app/Views/panel/siswa/`.

## 4. Kartu QR — Selesai

PDF A4 berisi 12 kartu per halaman (3 kolom, 4 baris), isi QR = NIS. Bisa dicetak semua kelas atau 1 kelas.

Bukti: `Siswa::kartu()` dan `app/Views/panel/siswa/kartu.php`.

## 5. Scan QR — Selesai di kode, belum diuji di sekolah

Halaman kamera dengan jsQR 1.4.0. Sebelum 07.00 Hadir, 07.00–07.59 Terlambat, mulai 08.00 ditolak. Hari Minggu dan tanggal libur ditolak. Scan ulang tidak mengubah catatan pertama.

Bukti: `app/Controllers/Panel/Scan.php`, `app/Services/Presensi.php` (`scan()`, `statusScan()`), `public/aset/js/scan.js`, `public/aset/vendor/jsqr/`.

Yang belum: uji dengan webcam dan HP sekolah lewat HTTPS (lihat tahap 10).

## 6. Rekap harian dan status manual — Selesai

Rekap per tanggal, semua kelas atau 1 kelas. Alpa dihitung saat rekap ditampilkan (tanpa cron). Admin bisa mengubah status siswa mana pun di tanggal mana pun, termasuk Kosongkan. Unduh Excel dan PDF.

Bukti: `app/Controllers/Panel/Rekap.php`, `Presensi::rekap()` dan `Presensi::ubah()`, `app/Views/panel/rekap.php`, `app/Views/panel/rekap_pdf.php`.

## 7. Libur — Selesai

Admin menandai dan menghapus tanggal libur. Hari Minggu otomatis libur.

Bukti: `app/Controllers/Panel/Libur.php`, `app/Models/LiburModel.php`, `Presensi::libur()`.

## 8. Pengujian otomatis — Ada, perlu dijalankan ulang di Laragon

56 pengujian PHPUnit: 34 unit (`tests/unit/`) dan 22 dengan database (`tests/database/`). Menurut catatan terakhir semuanya lulus di MySQL 8.0, tetapi belum dijalankan di Laragon dengan MySQL 8.4.

Yang belum: jalankan `composer test` di Laragon dan catat hasilnya.

## 9. Pemasangan di server sekolah — Belum

Server Windows + Laragon + Nginx + MySQL, alamat `https://spensada.web.id`. File `env-example-production` sudah ada.

Langkah:

1. `copy env-example-production .env`, lalu `php spark key:generate`.
2. Buat database kosong `absensi` (tabel versi lama tidak bisa dipindahkan).
3. `php spark migrate`, lalu `php spark admin:password`.
4. Pasang sertifikat HTTPS (kamera hanya jalan lewat `https`).

Belum diputuskan: lokasi server dan sumber sertifikat HTTPS.

## 10. Uji coba di sekolah — Belum

1. Import data siswa asli.
2. Cetak 1 halaman kartu QR.
3. Scan dengan webcam laptop dan HP sekolah di Chrome/Edge.
4. Cek rekap hari itu, unduh Excel dan PDF.

## 11. Mulai dipakai harian — Belum

Cetak kartu QR untuk semua siswa, isi tanggal libur semester ini, lalu mulai presensi pagi.
