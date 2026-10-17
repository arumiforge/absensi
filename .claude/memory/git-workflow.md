# Git workflow

## Branches

- `main` is the **only long-lived branch**.
- **One implementation phase = one branch**, created from the latest `main`:
  `fase-<NN>-<slug>`, e.g. `fase-00-fondasi`, `fase-02-sekolah-siswa`.
- Docs-only or memory-only changes: `docs/<slug>`.
- Merge to `main` through one PR per phase, then **delete the branch** (local and remote).
- If the environment assigns a branch name (cloud sessions), use it, and put the phase ID in the PR title.
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

Scope: an area (`scan`, `rekap`, `siswa`, `libur`, `memory`, `format`). Examples:

```text
feat(akn-01): add staff login form
fix(prs-05): count late arrivals after tolerance minute
docs(memory): record bootstrap decision
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

- Title: short English summary of the change.
- Body (English): what changed and how it was tested, local test results (`composer test`), docs changed, open questions.
- No CI: the owner runs the tests in Laragon before merging.
