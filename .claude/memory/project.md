# Project facts

## Identity

| Item | Value |
|---|---|
| Product name | **Spensada** (write it this way in UI text; `SPENSADA` only in logos/headers styled in caps) |
| School name | **SMP 1 DAWE** (in UI sentences: "SMP 1 Dawe") |
| Location | Dawe, Kabupaten Kudus, Jawa Tengah |
| Coordinates | -6.7185892, 110.8779716 |
| Google Maps | https://www.google.com/maps/place/SMP+1+Dawe/@-6.7185892,110.8779716,17z/data=!4m6!3m5!1s0x2e70da226beac053:0x90c8d30d51a5435a!8m2!3d-6.7185892!4d110.8779716!16s%2Fg%2F1hm44gxbf?hl=id |
| Repo owner | Arumi Studios (`arumiforge`) — the only contributor |

The school name shown in the app comes from `Config\Spensada::$namaSekolah`;
never hard-code it into views.

## Brand assets

All in `public/aset/logo/` unless noted. Do not edit, recolor, or crop them.

| File | Use |
|---|---|
| `logo-sekolah.png` | School emblem (618×604, transparent). Login page and panel navbar. |
| `logo-landscape.webp`, `logo-landscape.png` | Landscape logo reading "PRIMA SMP 1 DAWE KUDUS". Not used by any page (open question in `progress.md`). |
| `ikon-192.png` | Square icon from the emblem. Error pages. |
| `ikon-512.png` | Square icon from the emblem. Not used by any page. |
| `favicon-32.png` | PNG favicon. |
| `public/favicon.ico` | Favicon (16, 32, 48 px). |
| `public/apple-touch-icon.png` | iOS home-screen icon (180 px, white background). |

Head tags for every layout:

```html
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/aset/logo/favicon-32.png" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
```

## Users and devices

| Users | Device focus |
|---|---|
| One admin (the only account) | Responsive: desktop and phone. |
| Scan page (`/panel/scan`) | Laptop webcam or phone camera, Chrome/Edge, HTTPS, online. |

Students have no account; they only show their QR card.

## Stack

| Layer | Choice |
|---|---|
| Backend | CodeIgniter 4 (4.7.x), PHP **8.3 or newer** |
| Database | MySQL 8.4 (MySQLi driver, `utf8mb4`) |
| Frontend | Server-rendered HTML + vanilla JS, **standard Bootstrap 5.3** (no custom CSS theme) |
| Assets | Minified vendor files (`*.min.css`, `*.min.js`) served from `public/aset/vendor/<lib>/<version>/`. No CDN. |
| Spreadsheet | `phpoffice/phpspreadsheet` (student import, recap XLSX) |
| PDF | `mpdf/mpdf` (recap PDF, QR cards) |
| QR | `chillerlan/php-qrcode` (cards), jsQR 1.4.0 in `public/aset/vendor/jsqr/` (camera) |
| Local dev | Windows + Laragon + Nginx + MySQL, `https://spensada.test` |
| Production | Windows + Laragon + Nginx + MySQL, `https://spensada.web.id` (see `decisions.md` D-01) |
| Time zone | `Asia/Jakarta` (WIB) everywhere |

Write technical instructions for Windows + Laragon + Nginx (PowerShell or
Laragon terminal), not Linux.
