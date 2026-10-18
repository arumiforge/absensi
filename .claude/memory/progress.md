# Progress

Update this file at the end of every task that changes the repo: keep
"Current state", "Next" and "Open questions" current, and keep the log to the
last 10 entries. Stage status lives in `docs/tahapan.md`; update it there too.

## Current state (2026-10-18)

- The minimal QR attendance app (D-11) is on `main`, with local-first scan (D-12), scan sounds (D-13), and the reworked recap page. QR cards are made outside the app. Stages 1–6 of `docs/tahapan.md` are done in code; 68 PHPUnit tests, all passing on MySQL 8.0 (2026-10-18), not yet run on Laragon.
- Sample data for trying the app (D-17): `php spark data:contoh` (200 students from `contoh/siswa-contoh.csv`, 10 school days of attendance, 3 holidays, admin if missing); `--kosongkan` removes students, attendance and holidays. Sample QR cards: `contoh/kartu-qr-contoh.pdf`.
- The scan page was checked end to end in Chromium with a fake camera (scan → batch send → database row) and works; no bug found. Production (spensada.web.id) runs v1.4.0 but has `app.CSPEnabled = true`, which adds a second CSP that blocks Bootstrap icons; `env-example-production` now sets it to false.
- spensada.web.id still ran the old full app from `main` before the minimal app was merged, so `/login` returned 500. The server needs `main` pulled and a fresh database.
- Docs: `docs/spesifikasi.md` (what the app does) and `docs/tahapan.md` (stages and status). Nothing else in `docs/`.

## Next

1. Owner: run `composer test` in Laragon (stage 7).
2. Owner, on the server (stage 8): `git pull`, `composer install --no-dev`, `copy env-example-production .env`, `php spark key:generate`, empty database `absensi`, `php spark migrate`, `php spark data:contoh`; try scanning with `contoh/kartu-qr-contoh.pdf` on a school day before 08.00.
3. Owner: trial at school (stage 9): `php spark data:contoh --kosongkan`, import real students, scan the school's own QR cards with webcam and phone, pull the network cable mid-scan and plug it back in, check the recap on a phone.

## Open questions for the owner

- None.

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-18 | `main` | D-17: sample data command `data:contoh`, 200 sample students, sample QR cards PDF, ready-to-copy `env-example-production` (`app.CSPEnabled = false`). Checked the scan page end to end; no bug found. 68 PHPUnit tests pass. |
| 2026-10-18 | `main` | Owner deleted the 6 old branches; `main` is the only branch on GitHub. |
| 2026-10-18 | `main` | D-16: only `main` from now on; checked that all 6 old branches are fully merged (0 commits ahead of `main`, no open PRs); branch rule updated in `CLAUDE.md` and `git-workflow.md`. |
| 2026-10-18 | `claude/perbaikan-scan-kios-1tdsxm` | Scan page fixes (shared queue across tabs, re-scan after a server rejection, server errors not shown as lost connection, camera-stopped message) and D-15: full screen, wake lock, camera picker. App version 1.4.0. |
| 2026-10-18 | `claude/docs-overhaul-draft-1-wouicz` | Docs overhaul draft 2: owner approved deleting the unused landscape logo, `ikon-512.png`, and `format_wa()`/`format_rupiah()`/`format_percent()` with their tests. |
| 2026-10-18 | `claude/docs-overhaul-draft-1-wouicz` | Docs overhaul draft 1: new stage map `docs/tahapan.md` checked against code; removed outdated decisions, phase rules, and notes from memory (D-14). |
| 2026-10-18 | `fix/rekap-page` | Recap page rework: previous/next day, status filter chips with counts, search, shared "Ubah status" dialog, phone layout. 64 PHPUnit tests pass. |
| 2026-10-18 | `main` | Scan page sounds (D-13). |
| 2026-10-18 | `main` | Removed QR card printing; local-first scan with batch sending (D-12). |
| 2026-10-18 | `main` | Merged the minimal app into `main`, keeping history. |
