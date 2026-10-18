# Indonesian locale formats

Everything shown to users — views, flash messages, recap Excel and PDF —
uses these formats.

## The rule

**Use `app/Helpers/format_helper.php`. Never format by hand.** No `date()`,
`number_format()`, `IntlDateFormatter`, or string concatenation for
user-facing dates, times, or numbers. The helper is autoloaded
(`app/Config/Autoload.php`). If a format is missing, add it to the helper
(lead session only) with a test in `tests/unit/FormatHelperTest.php`.

| Function | Output |
|---|---|
| `format_date($d)` | `13 Oktober 2026` (sentences, forms) |
| `format_date($d, 'full')` | `Selasa, 13 Oktober 2026` (page titles, recap headers) |
| `format_date($d, 'short')` | `13 Okt 2026` (tables, lists) |
| `format_date($d, 'day')` | `13 Okt` (tables within one year) |
| `format_date($d, 'numeric')` | `13/10/2026` (compact fields) |
| `format_time($d)` | `07.32` (24-hour, dot separator) |
| `format_time($d, true)` | `07.32.05` (scan details) |
| `format_datetime($d)` | `13 Okt 2026, 07.32` |
| `format_number(1024)` | `1.024` |
| `format_number(12.5, 1)` | `12,5` |

Inputs: `DateTimeInterface`, Unix timestamp, or a string. Strings without an
offset are read as WIB. Empty input returns `''`.

## Fixed values

- Time zone: **`Asia/Jakarta`** (WIB) for app, database session, and display. "WIB" label only in the recap PDF.
- Month abbreviations: Jan, Feb, Mar, Apr, Mei, Jun, Jul, Agu, Sep, Okt, Nov, Des.
- Thousands separator `.`, decimal separator `,`.

## Not user-facing (machine formats)

| Where | Format |
|---|---|
| URLs and query params | `YYYY-MM-DD` |
| Database | `DATE`, `TIME`, `DATETIME` in WIB |
| File names | `YYYYMMDD` (e.g. `rekap-presensi-20261013-7A.xlsx`) |

## JavaScript

Browser code shows only text the server already formatted (for example the
scan result). Do not format dates or numbers in JavaScript.
