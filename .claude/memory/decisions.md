# Owner decisions outside docs/

Decisions the owner gave in chat that are not (yet) in `docs/`. Format:
ID, date, decision, conflict with `docs/`, status.

Status values: **ACTIVE** (follow it), **DOCS PENDING** (follow it, but the
listed docs still say otherwise and must be updated before the affected
phase starts), **NEEDS CONFIRMATION** (default applied by Claude; owner
must confirm).

Until a DOCS PENDING item is synced, do not start work that depends on the
conflicting part — ask the owner which applies.

| ID | Date | Decision | Conflicts with | Status |
|---|---|---|---|---|
| D-01 | 2026-10-10 | Production server is **Windows + Laragon + Nginx + MySQL**, PHP 8.3+. | `docs/00` §7.2 and `docs/07` ARS-01–ARS-05, §16, `docs/12` §16 describe a Linux VPS (DECISION, Session 6). | ACTIVE — owner confirmed 2026-10-10; docs synced 2026-10-10 (`docs/07` §16 rewritten for Windows). Sub-questions in OQ-20. |
| D-02 | 2026-10-10 | UI uses **Bootstrap 5** (minified files, served locally). | `docs/08` UI-08 "own CSS with tokens" (DECISION), UI-71 CSS budget 40 KB, UI-73/UI-74 file list and class names; `docs/07` ARS-10 library table. | ACTIVE — owner confirmed 2026-10-10: Bootstrap 5.3 locally + one app CSS file (`spensada.css`) with the `docs/08` tokens; UI-71 budget measured gzipped (CSS ≤ 50 KB, JS ≤ 40 KB); kiosk keeps its own CSS. Docs synced 2026-10-10. |
| D-03 | 2026-10-10 | Use the most capable PDF and spreadsheet libraries, minified front-end libraries. | None: mPDF and PhpSpreadsheet are already chosen in `docs/07` ARS-10. A PHP/JS minifier library is **not** approved yet (no build step per ARS-11). | ACTIVE |
| D-04 | 2026-10-10 | **One branch per implementation phase**, small commits; `main` is the only long-lived branch. | `docs/14` RM-11 / RM-06: one branch and one PR per feature (DECISION, Session 10). | ACTIVE — owner confirmed 2026-10-10; docs synced 2026-10-10 (`docs/14` RM-06, RM-11, RM-13). |
| D-05 | 2026-10-10 | English for chat, code comments, commits, agent prompts; Indonesian for UI text and URLs. | Docs name tables, controllers, methods, and CSS classes in Indonesian (`docs/06`, `docs/09` RT-20, `docs/08` UI-74). Keep doc-defined names, English for everything else (`language.md`). | ACTIVE — owner confirmed 2026-10-10 |
| D-06 | 2026-10-10 | No Claude attribution; the repo owner is the only commit author. | Past commits on `main` are authored by "Claude"; history is not rewritten. | ACTIVE |
| D-07 | 2026-10-10 | School: SMP 1 DAWE. Product: Spensada. Provided emblem = logo + favicon; provided landscape image = landscape logo. | `docs/01` FR-MD-08: school name, address, logo are admin settings — the provided values are seed defaults. The landscape image reads "PRIMA SMP 1 DAWE KUDUS", not "Spensada". | ACTIVE — owner confirmed 2026-10-10: seed name `SMP 1 DAWE` and address; empty `sekolah_logo` means the bundled emblem. Docs synced (`docs/06` §6.1). Still to confirm: "PRIMA" wording. |
| D-08 | 2026-10-10 | Student area is mobile-first; staff/admin areas responsive. | None (`docs/08` UI-18). | ACTIVE |
| D-09 | 2026-10-10 | One shared helper for dates, times, numbers, money (`format_helper.php`), mandatory everywhere. Adds `dd/mm/yyyy` and `Rp` formats. | None (`docs/08` UI-55 asks for one helper). `docs/08` §9.3 does not list `dd/mm/yyyy` or Rupiah yet. | ACTIVE |
| D-10 | 2026-10-10 | Project license: open source, choice delegated to Claude → **MIT**, copyright holder Arumi Studios. Compatible with mPDF (GPL-2.0-only); GPL-3.0/AGPL-3.0 rejected as incompatible. chillerlan/php-qrcode used under its MIT option. | None. Recorded in `docs/07` §2.7, `docs/14` §2.4, `docs/15` §2.1. `LICENSE` and README are replaced in FASE-00 step L00-01. | ACTIVE |
| D-11 | 2026-10-17 | **Total overhaul to a minimal app**: 1 admin, students (NIS, name, class), morning QR scan by camera (before 07:00 Hadir, 07:00–08:00 Terlambat, after 08:00 closed and missing = Alpa), manual Izin/Sakit/Dispensasi, holidays (Mon–Sat school days), daily recap Excel + PDF, QR cards, standard Bootstrap. All other features and `docs/00`–`docs/15` removed; `docs/spesifikasi.md` replaces them. New libraries approved: mPDF, chillerlan/php-qrcode, jsQR. | Supersedes D-02 (custom `spensada.css` tokens dropped), D-04 (phases), D-07 (school name is a config value, no admin setting), D-09 (`dd/mm/yyyy`/Rupiah formats stay in the helper but are unused). D-01, D-03, D-05, D-06, D-08, D-10 still apply. | ACTIVE — owner confirmed 2026-10-17 |
| D-12 | 2026-10-18 | **No QR card printing; local-first scan.** `/panel/siswa/kartu` and `chillerlan/php-qrcode` removed; the school makes cards outside the app (QR = NIS). The scan page judges scans in the browser (data loaded with the page, clock corrected to the server), keeps them in `localStorage`, and sends batches of ≤ 100 every 5 s; the server re-checks each scan with its device time (≤ 5 min ahead of the server). Owner chose background batches over sending only after the session ends. | Supersedes the QR-card part of D-11 and its approval of chillerlan/php-qrcode. `docs/spesifikasi.md` §1, §2.1, §5, §6 synced. | ACTIVE — owner confirmed 2026-10-18 |
| D-13 | 2026-10-18 | **Scan page sounds.** Web Audio tones (no files, no library) for Hadir, Terlambat, already recorded, error, and warnings (server rejection, sending starts failing, page from yesterday); "Suara" toggle kept in `localStorage`. Claude picked the events and tones; owner asked for "sound for several things". | None. `docs/spesifikasi.md` §2.2. | ACTIVE — owner confirmed the chosen sounds 2026-10-18 |
