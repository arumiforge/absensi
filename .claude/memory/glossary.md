# Glossary — shared UI words and URL slugs

Every agent uses exactly these words. If `docs/spesifikasi.md` and this file
disagree, the spec wins — fix this file. Need a new word? Ask the owner.

## Screen words

| Code | Screen label | URL slug |
|---|---|---|
| `siswa` | Siswa | `/panel/siswa` |
| `kelas` | Kelas | `?kelas=` |
| `presensi` | Presensi | — |
| scan | Scan QR | `/panel/scan` |
| rekap | Rekap harian | `/panel/rekap` |
| `libur` | Libur | `/panel/libur` |
| import | Import siswa | `/panel/siswa/import` |
| password | Ganti password | `/panel/password` |

## Daily status (`presensi.status`)

| Code | Label |
|---|---|
| `hadir` | Hadir |
| `terlambat` | Terlambat |
| `izin` | Izin |
| `sakit` | Sakit |
| `dispensasi` | Dispensasi |
| `alpa` | Alpa |
| (no record) | Belum ada data |
| (manual: remove record) | Kosongkan |

## Buttons

Tambah siswa · Ubah · Ubah status · Simpan · Hapus · Batal · Cari · Tampilkan · Hari ini · Import · Import Excel · Unduh Excel · Unduh PDF · Kirim sekarang · Suara: nyala / Suara: mati / Nyalakan suara · Layar penuh / Keluar layar penuh · Login · Logout.

## URL rules

- Lowercase Indonesian words, hyphens, singular nouns.
- IDs are numeric database IDs, never NIS or names.
- Query params `snake_case` Indonesian (`tanggal`, `kelas`, `status`, `cari`); `page` is the only English one.
