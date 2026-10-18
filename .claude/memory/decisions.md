# Owner decisions outside docs/

Decisions the owner gave in chat that still apply. Superseded decisions are
deleted, not archived. Format: ID, date, decision, status.

Status values: **ACTIVE** (follow it), **NEEDS CONFIRMATION** (default
applied by Claude; owner must confirm).

| ID | Date | Decision | Status |
|---|---|---|---|
| D-01 | 2026-10-10 | Production server is **Windows + Laragon + Nginx + MySQL**, PHP 8.3+, at `https://spensada.web.id`. | ACTIVE |
| D-03 | 2026-10-10 | Use the most capable PDF and spreadsheet libraries (mPDF, PhpSpreadsheet) and minified front-end files. No minifier library and no build step. | ACTIVE |
| D-05 | 2026-10-10 | English for chat, code comments, commits, agent prompts; Indonesian for UI text, URLs, and `docs/`. Names defined in `docs/spesifikasi.md` keep their Indonesian spelling (`language.md`). | ACTIVE |
| D-06 | 2026-10-10 | No Claude attribution; the repo owner is the only commit author. Old commits authored by "Claude" stay; history is not rewritten. | ACTIVE |
| D-08 | 2026-10-10 | All pages responsive (desktop and phone). | ACTIVE |
| D-10 | 2026-10-10 | License **MIT**, copyright Arumi Studios. Compatible with mPDF (GPL-2.0-only). | ACTIVE |
| D-11 | 2026-10-17 | **Minimal app**: 1 admin, students (NIS, name, class), morning QR scan (before 07:00 Hadir, 07:00–08:00 Terlambat, after 08:00 closed, missing = Alpa), manual Izin/Sakit/Dispensasi, holidays (Mon–Sat school days), daily recap Excel + PDF, standard Bootstrap. School name is a config value, not an admin setting. `docs/spesifikasi.md` is the only spec. | ACTIVE |
| D-12 | 2026-10-18 | **No QR card printing; local-first scan.** The school makes cards outside the app (QR = NIS). The scan page judges scans in the browser (data loaded with the page, clock corrected to the server), keeps them in `localStorage`, and sends batches of ≤ 100 every 5 s; the server re-checks each scan with its device time (≤ 5 min ahead of the server). Owner chose background batches over sending only after the session ends. Spec §2.1. | ACTIVE |
| D-13 | 2026-10-18 | **Scan page sounds.** Web Audio tones (no files, no library) for Hadir, Terlambat, already recorded, error, and warnings; "Suara" toggle kept in `localStorage`. Spec §2.2. | ACTIVE |
| D-14 | 2026-10-18 | Docs overhaul: stages are remapped from scratch in `docs/tahapan.md` and checked against code, not old notes. Old phases (FASE-00 to FASE-02) no longer apply. Outdated docs and notes are deleted, not archived. | ACTIVE |
| D-15 | 2026-10-18 | **Scan page view.** "Layar penuh" button (kiosk view inside the scan page, large text, no separate app), Screen Wake Lock while the page is open, camera picker when there is more than 1 camera (kept in `localStorage`), last result stays until the next scan. Spec §2.3. | ACTIVE |
| D-16 | 2026-10-18 | **Only 1 branch: `main`.** All old branches are merged and deleted; from now on work is committed straight to `main`, with no feature branches and no PRs (`git-workflow.md`). | ACTIVE |
| D-17 | 2026-10-18 | **Sample data for trying production.** `php spark data:contoh` fills a database without students with 200 sample students (`contoh/siswa-contoh.csv`), 10 school days of attendance, 3 holidays, and the admin if missing; `--kosongkan` deletes all students, attendance and holidays. Sample QR cards `contoh/kartu-qr-contoh.pdf` are a test file made outside the app, not a card-printing feature (D-12 stays). The site address is not public yet, so trying it in production is fine. Spec §6. | ACTIVE |
