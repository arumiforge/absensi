# Progress

Update this file at the end of every task that changes the repo: keep
"Current state", "Next" and "Open questions" current, and keep the log to the
last 10 entries.

## Current state (2026-10-17)

- Total overhaul (D-11) on branch `sky/gracious-gates-xd73xv`: the app is now 1 admin + students + morning QR scan + manual status + holidays + daily recap (Excel, PDF) + QR cards. Spec: `docs/spesifikasi.md`. 56 PHPUnit tests pass locally (MySQL 8.0 sandbox). Browser smoke test with a fake camera feed: scan, manual status, downloads all work.
- Everything from the old phases (accounts per role, portal, school years, placements, attributes, photos, WA, activity log, system check, login lockout, kiosk plans) and `docs/00`–`docs/15` were removed.

## Next

1. Owner: on the server, `copy env-example-production .env` and `php spark key:generate`, then create a fresh empty database `absensi` (old tables cannot be migrated), run `php spark migrate` and `php spark admin:password` on Laragon, run `composer test`, print one page of QR cards and test scanning with the school webcam/phone over HTTPS.

## Open questions for the owner

- D-07: the landscape logo says "PRIMA" — it is no longer used by any page; keep or delete the file?
- OQ-20: production server location and HTTPS certificate (the camera needs HTTPS).

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-17 | `sky/gracious-gates-xd73xv` | Added `env-example-production` for https://spensada.web.id (database `absensi`, root without password; key generated on the server). |
| 2026-10-17 | `sky/gracious-gates-xd73xv` | Total overhaul to the minimal QR attendance app (D-11). New schema (`admin`, `siswa`, `presensi`, `libur`), Presensi service, scan page with jsQR, recap Excel/PDF, QR cards, student import, holidays, password change, `admin:password` command. Removed old features, docs, and tests. |
| 2026-10-10 | `claude/fase-01-akun-akses-6jkkp8` | FASE-02 L02-01 to L02-09 (now removed by D-11). |
| 2026-10-10 | `claude/fase-01-akun-akses-6jkkp8` | FASE-01 L01-01 to L01-08 (now removed by D-11). |
| 2026-10-10 | `claude/tahap-1-uh4a06` | FASE-00 foundation: config, MySQLi driver (+07:00), error pages, Bootstrap, format helper (kept). |
| 2026-10-10 | `sky/busy-goodall-fswgci` | License decision D-10 (MIT). |
| 2026-10-10 | `sky/zen-heisenberg-d72c3e` | Added `CLAUDE.md`, `.claude/memory/`, `.claude/settings.json`, `format_helper.php`, logo/favicon/app icons. |
