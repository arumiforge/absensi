# Agent work boundaries

These rules apply to the main session and to every sub-agent. They matter
most when several agents work in parallel.

## Boundaries

1. **Assigned files only.** An agent changes only the files listed in its task. If it needs another file, it stops and reports which file and why.
2. **No drive-by changes.** No refactoring, renaming, reformatting, reordering, or "tidying" anything outside the task — even if it looks wrong. Report it instead.
3. **No new dependencies without the owner's approval.** That includes Composer packages, vendor JS/CSS, fonts, and PHP extensions. Ask first; after approval, record it in `docs/spesifikasi.md` §6.
4. **Ambiguous or conflicting? Stop and ask.** If the task is unclear, or conflicts with `docs/` or `decisions.md`, stop and ask the owner. Do not guess and do not pick a side silently.
5. **Docs first.** If behavior must differ from `docs/`, the doc is updated (with the owner's approval) before the code.
6. **Minimal scope.** Build only what `docs/spesifikasi.md` describes. Ask before adding a feature, page, or setting.
7. **Reuse, don't duplicate.** Search for an existing helper, service, view component, or label before writing a new one.
8. **Shared helpers are mandatory.** Formatting via `format_helper.php`; status labels via `App\Services\Presensi::STATUS`.
9. **Sub-agents do not run git.** No commit, push, branch, merge, rebase, or stash. The lead session reviews and commits.
10. **Verify before reporting done.** Run the relevant tests (`composer test`) and report the real result, including failures.

## Splitting work for parallel agents

The lead session:

- Splits tasks so **no two agents touch the same file**. Shared files (routes, `Autoload.php`, layouts, migrations) are edited by the lead only, before or after the parallel step.
- Gives each agent a full, self-contained prompt (template below). Agents do not see the lead's conversation.
- Reviews every diff, runs tests, then commits each agent's work as its own small commit.

## Prompt template for a parallel agent

```text
You are working in the Spensada repo (CodeIgniter 4, PHP 8.3+, MySQL, Bootstrap 5).
Read CLAUDE.md and .claude/memory/*.md first. They override your defaults.

Task: <one sentence>
Spec to follow: <docs/spesifikasi.md §...>

Files you may create or change (nothing else):
- <path>
- <path>

Files you may read but must not change:
- <path>

Rules:
- Comments and code identifiers not defined in docs: English.
- UI text: friendly, non-technical Indonesian ("Anda" for staff, "kamu" for students).
- URL slugs: Indonesian, from .claude/memory/glossary.md.
- Dates, times, numbers, money: only via format_helper.php.
- No new dependencies. No refactors or renames outside your files. No git commands.
- If anything is ambiguous or conflicts with docs/, stop and report the question instead of guessing.

Done when:
- <acceptance criteria / AC IDs>
- Tests pass: <command>

Report back: files changed, tests run with results, open questions.
```
