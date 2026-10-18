# Progress

Update this file at the end of every task that changes the repo: keep
"Current state", "Next" and "Open questions" current, and keep the log to the
last 10 entries.

## Current state (2026-10-18)

- 2026-10-18: recap page rework merged (arumiforge/absensi#3): day navigation, status filter, search, shared "Ubah status" dialog, phone layout. 64 PHPUnit tests pass.
- 2026-10-18: QR card printing removed and the scan page made local-first (D-12): scans are judged in the browser, kept in `localStorage`, and sent in background batches (≤ 100 every 5 s); the server re-checks each scan with its device time. 62 PHPUnit tests pass (MySQL 8.0 sandbox); browser smoke test with a fake camera (online, offline, reload, resend) passes.
- 2026-10-18: the minimal app is now on `main` (merged from `claude/fase-01-akun-akses-6jkkp8`, history kept). Before this, spensada.web.id ran the old full app from `main` against the minimal app's database setup, so `/login` and `/logo` returned 500. Old branches `claude/fase-01-akun-akses-6jkkp8`, `sky/gracious-gates-xd73xv`, `ground/clever-wright-mwofec` still to be deleted by the owner (the session could not delete them).
- Total overhaul (D-11) on branch `sky/gracious-gates-xd73xv`: the app is now 1 admin + students + morning QR scan + manual status + holidays + daily recap (Excel, PDF) + QR cards. Spec: `docs/spesifikasi.md`. 56 PHPUnit tests pass locally (MySQL 8.0 sandbox). Browser smoke test with a fake camera feed: scan, manual status, downloads all work.
- Everything from the old phases (accounts per role, portal, school years, placements, attributes, photos, WA, activity log, system check, login lockout, kiosk plans) and `docs/00`–`docs/15` were removed.

## Next

1. Owner: set `main` as the GitHub default branch if it is not yet. On the server: `git checkout main`, `git pull`, `composer install --no-dev`.
2. Owner: on the server, `copy env-example-production .env` and `php spark key:generate`, then create a fresh empty database `absensi` (old tables cannot be migrated), run `php spark migrate` and `php spark admin:password` on Laragon, run `composer test`, and test scanning with the school's own QR cards (QR = NIS) and webcam/phone over HTTPS.
3. Owner: after pulling the 2026-10-18 changes, run `composer install --no-dev` on the server (drops chillerlan/php-qrcode). On the scan page: scan, pull the network cable, scan again, plug it back in, and check the recap (try the Alpa filter and the Ubah status window on a phone).

## Open questions for the owner

- D-07: the landscape logo says "PRIMA" — it is no longer used by any page; keep or delete the file?
- OQ-20: production server location and HTTPS certificate (the camera needs HTTPS).

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-18 | `fix/rekap-page` | Recap page rework: previous/next day, status filter chips with counts, search, one shared "Ubah status" dialog (`rekap.js`), compact phone layout, distinct status colours, filters kept after saving. HTML for 288 students 467 KB → 273 KB. 64 PHPUnit tests pass; browser flow check passes. |
| 2026-10-18 | `main` | Owner confirmed the scan sounds (D-13). |
| 2026-10-18 | `main` | Scan page sounds (D-13): Web Audio tones per result, one warning when sending starts failing, "Suara" toggle. Browser check with recorded tones passes. |
| 2026-10-18 | `main` | Removed QR card printing (route, view, chillerlan/php-qrcode). Local-first scan: `Presensi::dataScan()` + `scanBanyak()`, JSON batch `POST /panel/scan`, `scan.js` with localStorage queue, server-clock offset, retry and "Kirim sekarang". Spec §2.1 added. |
| 2026-10-18 | `main` | Found why spensada.web.id showed 500 (server ran old `main`, minimal app only on `claude/fase-01-akun-akses-6jkkp8`). Merged the minimal app into `main` keeping history. Branch deletion was refused in the session; owner deletes them on GitHub. |
| 2026-10-17 | `sky/gracious-gates-xd73xv` | Added `env-example-production` for https://spensada.web.id (database `absensi`, root without password; key generated on the server). |
| 2026-10-17 | `sky/gracious-gates-xd73xv` | Total overhaul to the minimal QR attendance app (D-11). New schema (`admin`, `siswa`, `presensi`, `libur`), Presensi service, scan page with jsQR, recap Excel/PDF, QR cards, student import, holidays, password change, `admin:password` command. Removed old features, docs, and tests. |
| 2026-10-10 | `claude/fase-01-akun-akses-6jkkp8` | FASE-02 L02-01 to L02-09 (now removed by D-11). |
| 2026-10-10 | `claude/fase-01-akun-akses-6jkkp8` | FASE-01 L01-01 to L01-08 (now removed by D-11). |
| 2026-10-10 | `claude/tahap-1-uh4a06` | FASE-00 foundation: config, MySQLi driver (+07:00), error pages, Bootstrap, format helper (kept). |
