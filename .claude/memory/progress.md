# Progress

Update this file at the end of every task that changes the repo: keep
"Current state", "Next" and "Open questions" current, and keep the log to the
last 10 entries. Stage status lives in `docs/tahapan.md`; update it there too.

## Current state (2026-10-18)

- The minimal QR attendance app (D-11) is on `main`, with local-first scan (D-12), scan sounds (D-13), and the reworked recap page. QR cards are made outside the app. Stages 1–6 of `docs/tahapan.md` are done in code; 63 PHPUnit tests (64 passed on MySQL 8.0 before the unused WA test was removed), not yet run on Laragon.
- spensada.web.id still ran the old full app from `main` before the minimal app was merged, so `/login` returned 500. The server needs `main` pulled and a fresh database.
- Docs: `docs/spesifikasi.md` (what the app does) and `docs/tahapan.md` (stages and status). Nothing else in `docs/`.

## Next

1. Owner: run `composer test` in Laragon (stage 7).
2. Owner, on the server (stage 8): `git checkout main`, `git pull`, `composer install --no-dev`, `copy env-example-production .env`, `php spark key:generate`, empty database `absensi`, `php spark migrate`, `php spark admin:password`; check HTTPS works.
3. Owner: trial at school (stage 9): import real students, scan the school's own QR cards with webcam and phone, pull the network cable mid-scan and plug it back in, check the recap on a phone.

## Open questions for the owner

- None.

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-18 | `main` | Owner deleted the 6 old branches; `main` is the only branch on GitHub. |
| 2026-10-18 | `main` | D-16: only `main` from now on; checked that all 6 old branches are fully merged (0 commits ahead of `main`, no open PRs); branch rule updated in `CLAUDE.md` and `git-workflow.md`. |
| 2026-10-18 | `claude/perbaikan-scan-kios-1tdsxm` | Scan page fixes (shared queue across tabs, re-scan after a server rejection, server errors not shown as lost connection, camera-stopped message) and D-15: full screen, wake lock, camera picker. App version 1.4.0. |
| 2026-10-18 | `claude/docs-overhaul-draft-1-wouicz` | Docs overhaul draft 2: owner approved deleting the unused landscape logo, `ikon-512.png`, and `format_wa()`/`format_rupiah()`/`format_percent()` with their tests. |
| 2026-10-18 | `claude/docs-overhaul-draft-1-wouicz` | Docs overhaul draft 1: new stage map `docs/tahapan.md` checked against code; removed outdated decisions, phase rules, and notes from memory (D-14). |
| 2026-10-18 | `fix/rekap-page` | Recap page rework: previous/next day, status filter chips with counts, search, shared "Ubah status" dialog, phone layout. 64 PHPUnit tests pass. |
| 2026-10-18 | `main` | Scan page sounds (D-13). |
| 2026-10-18 | `main` | Removed QR card printing; local-first scan with batch sending (D-12). |
| 2026-10-18 | `main` | Merged the minimal app into `main`, keeping history. |
| 2026-10-17 | `sky/gracious-gates-xd73xv` | Added `env-example-production` for https://spensada.web.id. |
