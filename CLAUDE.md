# CLAUDE.md — Spensada

Entry point for Claude Code and every sub-agent working in this repository.
The files imported below are loaded automatically at session start. Keep
this file short; put details in `.claude/memory/`.

## Read first

- Product: **Spensada** (product name). School: **SMP 1 DAWE**. Details: `.claude/memory/project.md`.
- The spec is `docs/spesifikasi.md` (Indonesian, one file) and is the source of truth. The app is deliberately minimal: one admin, morning QR attendance, daily recap. Do not add features that are not in the spec without the owner's approval.
- If an instruction is ambiguous, or conflicts with `docs/` or with `.claude/memory/decisions.md`: **stop and ask the owner**. Do not guess, do not silently pick one side.

## Hard rules (short version)

1. **Chat in English**, always — even when the owner writes in Indonesian.
2. **Code, comments, commit messages, PRs, and agent prompts in English.**
3. **UI text in friendly, non-technical Indonesian.** URL slugs in Indonesian.
4. **No Claude attribution** anywhere: no `Co-Authored-By`, no `Claude-Session`, no "Generated with Claude Code". Commits are authored by the repo owner only.
5. **One phase = one branch**, small commits, `type(scope): summary` format. `main` is the only long-lived branch.
6. **Stay inside your assigned files.** No unrequested refactors, renames, or new dependencies.
7. **All user-facing dates, times, numbers, and money go through `app/Helpers/format_helper.php`.**
8. **Update `.claude/memory/progress.md`** at the end of every task that changes the repo.

## Imported memory

@.claude/memory/project.md
@.claude/memory/language.md
@.claude/memory/agent-rules.md
@.claude/memory/git-workflow.md
@.claude/memory/locale-format.md
@.claude/memory/glossary.md
@.claude/memory/decisions.md
@.claude/memory/progress.md
