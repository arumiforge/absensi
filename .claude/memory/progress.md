# Progress

Update this file at the end of every task that changes the repo: keep
"Current state", "Next" and "Open questions" current, and keep the log to the
last 10 entries. Stage status lives in `docs/tahapan.md`; update it there too.

## Current state (2026-10-18)

- The minimal QR attendance app (D-11) is on `main`. Stages 1–7 of `docs/tahapan.md` are done in code; 56 PHPUnit tests exist (last run passed on MySQL 8.0, not yet on Laragon).
- Docs: `docs/spesifikasi.md` (what the app does) and `docs/tahapan.md` (stages and status). Nothing else in `docs/`.

## Next

1. Owner: run `composer test` in Laragon (stage 8).
2. Owner: set up the school server (stage 9): `copy env-example-production .env`, `php spark key:generate`, empty database `absensi`, `php spark migrate`, `php spark admin:password`, HTTPS certificate.
3. Owner: trial at school (stage 10): import real students, print one page of QR cards, scan with webcam and phone over HTTPS, download the recap.

## Open questions for the owner

- The landscape logo (`logo-landscape.*`, reads "PRIMA") and `ikon-512.png` are not used by any page. Keep or delete?
- `format_wa()`, `format_rupiah()`, `format_percent()` in `format_helper.php` are unused. Keep or delete (with their tests)?
- Production server location and HTTPS certificate source (the camera needs HTTPS).

## Log

| Date | Branch | Work |
|---|---|---|
| 2026-10-18 | `claude/docs-overhaul-draft-1-wouicz` | Docs overhaul draft 1: new stage map `docs/tahapan.md` checked against code; removed outdated decisions, phase rules, and notes from memory (D-12). |
| 2026-10-17 | `sky/gracious-gates-xd73xv` | Added `env-example-production` for https://spensada.web.id. |
| 2026-10-17 | `sky/gracious-gates-xd73xv` | Total overhaul to the minimal QR attendance app (D-11). |
