# Tahapan Spensada

Peta tahapan proyek, disusun ulang dari nol pada 18 Oktober 2026. Status setiap tahap dicek langsung ke kode di `main`, bukan ke catatan lama.

Isi aplikasi mengikuti [`spesifikasi.md`](spesifikasi.md). Dokumen ini hanya mencatat urutan kerja dan posisinya sekarang.

## Ringkasan

| No | Tahap | Status |
|---|---|---|
| 1 | Fondasi aplikasi | Selesai |
| 2 | Akun admin dan login | Selesai |
| 3 | Data siswa dan import | Selesai |
| 4 | Scan QR *local-first* | Selesai di kode, belum diuji di sekolah |
| 5 | Rekap harian dan status manual | Selesai |
| 6 | Libur | Selesai |
| 7 | Pengujian otomatis | Ada, perlu dijalankan di Laragon |
| 8 | Pemasangan di server sekolah | Sebagian |
| 9 | Uji coba di sekolah | Belum |
| 10 | Mulai dipakai harian | Belum |

Arti status: **Selesai** berarti kode sudah ada di `main` dan sesuai spesifikasi. **Sebagian** berarti sudah dimulai tetapi belum lengkap. **Belum** berarti belum ada bukti di repo bahwa tahap itu sudah dikerjakan.

Kartu QR tidak lagi menjadi tahap: kartu dibuat sekolah di luar aplikasi, dengan isi QR = NIS.

## 1. Fondasi aplikasi — Selesai

CodeIgniter 4.7, koneksi MySQL dengan zona waktu WIB, halaman galat berbahasa Indonesia, Bootstrap 5.3 lokal, helper format tanggal dan angka.

Bukti: `app/Database/MySQLi/`, `app/Config/Spensada.php`, `app/Helpers/format_helper.php`, `app/Views/errors/html/`, `public/galat/`, `public/aset/vendor/bootstrap/5.3.8/`, `app/Filters/Keamanan.php`, `app/Filters/Csrf.php`.

## 2. Akun admin dan login — Selesai

1 akun admin, login dibatasi 10 percobaan per menit per alamat jaringan, sesi berakhir setelah 8 jam, ganti password mengakhiri sesi di perangkat lain, perintah `php spark admin:password` untuk membuat admin atau mengganti password yang lupa.

Bukti: `app/Controllers/Login.php`, `app/Filters/Sesi.php`, `app/Controllers/Panel/Password.php`, `app/Commands/AdminPassword.php`, `app/Config/Session.php` (`expiration` 28800).

## 3. Data siswa dan import — Selesai

Daftar siswa dengan cari dan filter kelas, tambah, ubah, hapus (presensinya ikut terhapus), import `.xlsx`/`.xls`/`.csv` sampai 5 MB. Bila ada baris salah, seluruh file dibatalkan.

Bukti: `app/Controllers/Panel/Siswa.php`, `app/Services/ImportSiswa.php`, `app/Models/SiswaModel.php`, `app/Views/panel/siswa/`.

## 4. Scan QR *local-first* — Selesai di kode, belum diuji di sekolah

Halaman kamera dengan jsQR 1.4.0. Sebelum 07.00 Hadir, 07.00–07.59 Terlambat, mulai 08.00 ditolak. Hari Minggu dan tanggal libur ditolak. Scan ulang tidak mengubah catatan pertama. Scan diperiksa di perangkat, disimpan di browser, lalu dikirim ke server paling banyak 100 scan setiap 5 detik; server memeriksa ulang dengan jam scan dari perangkat. Bunyi berbeda untuk setiap hasil, dengan tombol "Suara".

Bukti: `app/Controllers/Panel/Scan.php`, `app/Services/Presensi.php` (`dataScan()`, `scanBanyak()`, `scan()`, `statusScan()`), `app/Views/panel/scan.php`, `public/aset/js/scan.js`, `public/aset/vendor/jsqr/`.

Yang belum: uji dengan kartu QR sekolah, webcam, dan HP lewat HTTPS, termasuk saat koneksi putus (lihat tahap 9).

## 5. Rekap harian dan status manual — Selesai

Rekap per tanggal dengan tombol hari sebelumnya/berikutnya dan "Hari ini", saring per kelas dan per status, cari nama atau NIS. Alpa dihitung saat rekap ditampilkan (tanpa cron). Admin mengubah status lewat jendela "Ubah status", termasuk Kosongkan. Unduh Excel dan PDF.

Bukti: `app/Controllers/Panel/Rekap.php`, `Presensi::rekap()` dan `Presensi::ubah()`, `app/Views/panel/rekap.php`, `app/Views/panel/rekap_pdf.php`, `public/aset/js/rekap.js`.

## 6. Libur — Selesai

Admin menandai dan menghapus tanggal libur. Hari Minggu otomatis libur.

Bukti: `app/Controllers/Panel/Libur.php`, `app/Models/LiburModel.php`, `Presensi::libur()`.

## 7. Pengujian otomatis — Ada, perlu dijalankan di Laragon

63 pengujian PHPUnit: 33 unit (`tests/unit/`) dan 30 dengan database (`tests/database/`). Menurut catatan terakhir semuanya lulus di MySQL 8.0 (sebelum helper format WA, Rupiah, dan persen yang tidak dipakai dihapus), tetapi belum dijalankan di Laragon dengan MySQL 8.4.

Yang belum: jalankan `composer test` di Laragon dan catat hasilnya.

## 8. Pemasangan di server sekolah — Sebagian

Server Windows + Laragon + Nginx + MySQL sudah ada di `https://spensada.web.id`, tetapi masih menjalankan versi lama aplikasi. File `env-example-production` sudah ada.

Langkah:

1. Di GitHub, jadikan `main` cabang utama bila belum, dan hapus cabang lama.
2. Di server: `git checkout main`, `git pull`, `composer install --no-dev`.
3. `copy env-example-production .env`, lalu `php spark key:generate`.
4. Buat database kosong `absensi` (tabel versi lama tidak bisa dipindahkan).
5. `php spark migrate`, lalu `php spark admin:password`.
6. Pastikan situs terbuka lewat `https` (kamera hanya jalan lewat `https`).

## 9. Uji coba di sekolah — Belum

1. Import data siswa asli.
2. Scan kartu QR sekolah dengan webcam laptop dan HP di Chrome/Edge.
3. Cabut kabel jaringan di tengah scan, lalu pasang lagi. Pastikan semua scan terkirim.
4. Cek rekap hari itu di HP, coba saringan Alpa dan "Ubah status", unduh Excel dan PDF.

## 10. Mulai dipakai harian — Belum

Bagikan kartu QR ke semua siswa, isi tanggal libur semester ini, lalu mulai presensi pagi.
