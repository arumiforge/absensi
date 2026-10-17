# Indonesian locale formats

Everything shown to users — views, flash messages, exports, PDF, WA messages —
uses these formats.

## The rule

**Use `app/Helpers/format_helper.php`. Never format by hand.** No `date()`,
`number_format()`, `IntlDateFormatter`, or string concatenation for
user-facing dates, times, numbers, or money. The helper is autoloaded
(`app/Config/Autoload.php`). If a format is missing, add it to the helper
(lead session only) with a test in `tests/unit/FormatHelperTest.php`.

| Function | Output |
|---|---|
| `format_date($d)` | `13 Oktober 2026` (sentences, forms) |
| `format_date($d, 'full')` | `Selasa, 13 Oktober 2026` (page titles, dashboard, slips, flyers) |
| `format_date($d, 'short')` | `13 Okt 2026` (tables, lists) |
| `format_date($d, 'day')` | `13 Okt` (tables within one year) |
| `format_date($d, 'numeric')` | `13/10/2026` (`dd/mm/yyyy`; compact fields, date-input hints) |
| `format_time($d)` | `07.32` (24-hour, dot separator) |
| `format_time($d, true)` | `07.32.05` (scan details and logs) |
| `format_datetime($d)` | `13 Okt 2026, 07.32` |
| `format_number(1024)` | `1.024` |
| `format_number(12.5, 1)` | `12,5` |
| `format_rupiah(1250000)` | `Rp 1.250.000` (negative: `-Rp 1.250.000`) |
| `format_percent(87.5)` | `88%` (whole number, half rounds up) |
| `format_wa('6281234567890')` | `0812-3456-7890` (stored `62…`, shown with `0` and hyphens) |

Inputs: `DateTimeInterface`, Unix timestamp, or a string. Strings without an
offset are read as WIB. Empty input returns `''`.

## Fixed values

- Time zone: **`Asia/Jakarta`** (WIB) for app, database session, and display. "WIB" label only on flyers, PDF, and public pages.
- Month abbreviations: Jan, Feb, Mar, Apr, Mei, Jun, Jul, Agu, Sep, Okt, Nov, Des.
- Thousands separator `.`, decimal separator `,`.
- Money: `Rp` + one space + amount, no decimals by default.
- School year: `2026/2027`. Semester: "Semester ganjil 2026/2027".
- WA numbers: stored as `62…`, shown as `0812-3456-7890`.

## Not user-facing (machine formats)

| Where | Format |
|---|---|
| URLs and query params | `YYYY-MM-DD`, month `YYYY-MM`, time `HH:MM` |
| Database | `DATE`, `DATETIME` in WIB |
| CSV export | `YYYY-MM-DD`, `HH:MM:SS` |
| File names | `YYYYMMDD` |
| XLSX | real date cells, not text |
| Import accepts | Excel date cell, `DD-MM-YYYY`, or `DD/MM/YYYY` |

## JavaScript

Browser code shows only text the server already formatted (for example the
scan result). Do not format dates or numbers in JavaScript.
