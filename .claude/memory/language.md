# Language policy

| Where | Language | Notes |
|---|---|---|
| Chat replies to the owner | **English** | Always, even if the owner writes in Indonesian. Do not switch languages to match them. |
| Code comments, docblocks | **English** | |
| Commit messages, PR titles and bodies, branch names | **English** | Branch slugs may be Indonesian, e.g. `docs/tahapan`. |
| Prompts to sub-agents / parallel agents | **English** | Include the UI-text rule below in every prompt that touches views or messages. |
| Log messages, exception messages (internal) | **English** | Never shown to users as-is. |
| Test method names and test descriptions | **English** | |
| Files in `.claude/` and `CLAUDE.md` | **English** | |
| Project specs in `docs/` | Indonesian | Existing language; keep it when editing docs. |
| **UI text** (labels, buttons, menus, flash messages, validation errors, PDF, Excel) | **Indonesian** | Friendly, non-technical. See below. |
| **URL paths and query parameters** | **Indonesian** | Lowercase kebab-case paths, `snake_case` params. |

## Identifiers in code

- Existing Indonesian names keep their spelling: tables and columns
  (`docs/spesifikasi.md` §3), route paths and route names, controller and
  method names. Do not translate them.
- New technical names that are not user-facing (local variables, private
  helpers, internal classes) use English.
- Status: confirmed by the owner on 2026-10-10 — see `decisions.md` D-05.

## Writing UI text (Indonesian)

In short:

- Address the admin as **"Anda"**. Login page and PDF: formal.
- Short, active, friendly sentences. Buttons are verbs: "Simpan", "Kirim", "Batalkan".
- Error messages say what happened and what to do next. Never blame the user. Never show codes, stack traces, SQL, or English jargon ("invalid", "error", "null", "submit", "request").
- Use glossary words only (`glossary.md`), e.g. "Kelas" not "Rombel" on screen.
- Write numbers as digits: "2 akun", not "dua akun".

Examples:

| Bad | Good |
|---|---|
| `Error 422: validation failed` | "NISN harus 10 digit angka. Periksa lagi, lalu simpan." |
| "Data invalid!" | "Tanggal belum diisi. Pilih tanggal lebih dulu." |
| "Request timeout" | "Koneksi sedang lambat. Coba lagi beberapa saat lagi." |
| "Anda salah memasukkan password" | "Username atau password belum cocok. Coba lagi." |
| "Submit" | "Kirim" |

## Example: the same feature in each language

```php
// Holidays need a short reason so the recap can show it.
if ($keterangan === '' || mb_strlen($keterangan) > 100) {
    return redirect()->back()->withInput()
        ->with('galat', 'Isi keterangan libur, paling panjang 100 karakter.');
}
```

- Comment: English. Message: friendly Indonesian. Route name and variables from docs: unchanged.
- Commit: `fix(libur): require a holiday reason`
- URL: `/panel/libur`
