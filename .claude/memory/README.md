# .claude/memory — project memory for Claude

This folder is Claude's long-term memory for this repository. It is committed
to git, so every session, every machine, and every sub-agent sees the same
rules. Root `CLAUDE.md` imports these files with `@path` lines, so Claude Code
loads them automatically.

| File | What it holds | Who updates it |
|---|---|---|
| `project.md` | Fixed facts: school, product, address, stack, server, users, brand assets | Owner, or Claude when the owner states a new fact |
| `language.md` | Which language to use where (chat, code, UI, URLs, agent prompts) | Owner |
| `agent-rules.md` | Work boundaries for every agent, plus the parallel-task prompt template | Owner |
| `git-workflow.md` | Branches, commit format, PRs, attribution, author identity | Owner |
| `locale-format.md` | Date, time, and number formats, and the mandatory helper | Owner |
| `glossary.md` | Shared UI words and URL slugs so all agents use the same terms | Claude, when docs add terms |
| `decisions.md` | Owner decisions made outside `docs/`, including open conflicts with `docs/` | Claude records, owner confirms |
| `progress.md` | Current state, last work done, next steps, open questions | Claude, at the end of every task |

Related files:

- `CLAUDE.md` (repo root) — entry point; keep it short.
- `.claude/settings.json` — shared Claude Code settings (attribution off, owner git identity).
- `CLAUDE.local.md` and `.claude/settings.local.json` — personal, git-ignored overrides.

## How to use it

- To add a rule: edit the matching file, keep it short, and commit with `docs(memory): ...`.
- To tell Claude something once and have it remembered: say "remember this" — Claude writes it into the right file here (not into its private memory).
- If a rule here conflicts with `docs/`, `docs/` wins until the owner decides; Claude records the conflict in `decisions.md`.
