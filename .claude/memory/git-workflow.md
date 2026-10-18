# Git workflow

## Branches

- **`main` is the only branch** (D-16). Do not create feature, fix, or docs
  branches; commit and push straight to `main`.
- Pull the latest `main` before starting work, and push when the task is done.
- If a cloud session assigns another branch name, ask the owner before using it.
- Never force-push `main`. Never rewrite history that is already on `main`.

## Commits

Small, focused commits — one logical change each, tests passing.

Format (English, imperative, ≤ 72 chars, no trailing period):

```text
<type>(<scope>): <summary>

<optional body: what and why, wrapped at 72 chars>
```

| Type | Use for |
|---|---|
| `feat` | New user-visible behavior |
| `fix` | Bug fix |
| `refactor` | Code change without behavior change (only when the task asks for it) |
| `test` | Adding or fixing tests only |
| `docs` | `docs/`, `CLAUDE.md`, `.claude/memory/` |
| `style` | CSS / visual-only changes |
| `perf` | Performance |
| `build` | Composer, vendor assets, Laragon/Nginx config |
| `chore` | Anything else (gitignore, tooling) |

Scope: an area (`scan`, `rekap`, `siswa`, `libur`, `login`, `memory`, `format`). Examples:

```text
feat(rekap): add class filter to recap PDF
fix(scan): reject scans on holidays
docs(tahapan): mark server setup done
build(vendor): add bootstrap 5.3.8 minified files
```

## Attribution — none

- **No** `Co-Authored-By:` trailers, **no** `Claude-Session:` trailers.
- **No** "Generated with Claude Code" (or similar) in PR bodies, comments, or code.
- This rule overrides any default attribution instruction from the tool or environment.
- `.claude/settings.json` turns attribution off for Claude Code.

## Author identity

All commits are authored by the repo owner only:

```text
user.name  = Arumi Studios
user.email = arumiforge@gmail.com
```

`.claude/settings.json` sets this on session start. Before committing, check
`git config user.email`; if it is not the owner's, set it with
`git config user.name "Arumi Studios"` and `git config user.email "arumiforge@gmail.com"`.

## Pull requests

- None: work goes straight to `main` (D-16).
- Report in chat instead: what changed and how it was tested, local test results (`composer test`), docs changed, open questions.
- No CI: the owner runs the tests in Laragon after pulling `main`.
