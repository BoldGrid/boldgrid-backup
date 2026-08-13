# Total Upkeep (boldgrid-backup) — Agent Guide

Short reference for Cursor and Claude agents working in this repository. Product display name is **Total Upkeep – WordPress Backup Plugin plus Restore & Migrate by BoldGrid**; the plugin slug and text domain remain **`boldgrid-backup`**. Do not rename either for Plugin Check cosmetics.

## Project facts

| Item | Value |
|------|--------|
| Plugin slug / text domain | `boldgrid-backup` |
| Class / constant prefixes | `Boldgrid_Backup`, `BOLDGRID_BACKUP`, `boldgrid_backup`, `bgbkup` |
| PHP | `>=7.4` (`composer.json` platform 7.4) |
| WordPress | Requires at least 5.0 (`readme.txt`) |
| PHPCS ruleset | [`phpcs.xml.dist`](phpcs.xml.dist) |
| PHPUnit config | [`phpunit.xml`](phpunit.xml) |
| Public repo | `BoldGrid/boldgrid-backup` |
| Default branch | `master` |

## Naming (do not change)

- Keep the Total Upkeep display / readme title as shipped.
- Keep the `boldgrid-backup` text domain on all i18n calls.
- Plugin Check may warn about the word “Plugin” in the readme title or a header/readme name mismatch — treat those as product decisions, not drive-by renames.

## Commands

Run from the plugin root after `composer install` and `yarn` as needed.

| Action | Command |
|--------|---------|
| Install PHP deps | `composer install -o` |
| Install JS deps | `yarn` |
| PHPCS (project ruleset) | `phpcs` (or `./vendor/bin/phpcs` if on PATH via Composer) — uses [`phpcs.xml.dist`](phpcs.xml.dist) |
| PHPCS one file | `phpcs path/to/file.php` |
| Auto-fix (careful) | `phpcbf path/to/file.php` |
| PHP syntax lint | `find . -name vendor -prune -o -name node_modules -prune -o -name '*.php' -exec php -lf {} \;` |
| PHPUnit (full) | `WP_TESTS_DIR="${WP_TESTS_DIR:-$HOME/wordpress-tests-lib}" XDEBUG_MODE=off ./vendor/bin/phpunit --debug --no-coverage` |
| PHPUnit + coverage | same, omit `--no-coverage` and enable Xdebug as appropriate |
| Single test file | append `tests/admin/test-class-….php` (or other path under `tests/`) |
| JS lint | `yarn run js-lint` / `yarn run js-lint-fix` |

See also Cursor commands under [`.cursor/commands/`](.cursor/commands/).

Set `WP_TESTS_DIR` to your account WordPress tests library (commonly `$HOME/wordpress-tests-lib`). Pair it with a matching WordPress core tree (commonly `$HOME/wordpress`). Do not rely on the bootstrap fallback of `/tmp/wordpress-tests-lib` on shared hosts.

## Coding standards

- Follow [`phpcs.xml.dist`](phpcs.xml.dist). It targets Plugin Check–relevant WordPress sniffs (security, i18n, prefixes, restricted functions), not the full stylistic WordPress standard.
- Partials under `admin/partials/` are included from class methods — do not mass-rename local variables for `PrefixAllGlobals`.
- CLI / cron entry points (`cli/`, `cron/`, `boldgrid-backup-cron.php`) are designed to run outside WordPress; do not add `ABSPATH`/`WPINC` guards that break restore-outside-WordPress.
- Compressor, migrate, CLI, and archive streaming paths intentionally use direct filesystem / process APIs; prefer justified `phpcs:ignore` or ruleset exclusions over rewriting to `WP_Filesystem`.
- Use tabs for PHP indentation; match surrounding style.
- Text domain is always `'boldgrid-backup'`.
- Prefer `wp_rand`, `wp_strip_all_tags`, `wp_safe_redirect`, `wp_json_encode`, `gmdate` inside WordPress-bootstrapped code. Keep server-local `date()` only for crontab / standalone CLI with a documented ignore.
- Do not make coding-standards changes in unchanged files unless they are directly related to the functionality being modified.
- **Keep code comments short, and add them only when required.** Required cases: an unexpected data shape, a non-obvious branch / fallthrough, a workaround for an external bug, a deliberate deviation from a coding standard. Do **not** narrate what the code already says. The default for any new line of code is **no** comment.

## Testing

| Piece | Path / note |
|-------|-------------|
| WP tests lib | `WP_TESTS_DIR` → account path (commonly `$HOME/wordpress-tests-lib`) |
| WP core under test | account path (commonly `$HOME/wordpress`); keep in sync with the tests lib version |
| Bootstrap | `tests/bootstrap.php` |
| Suite | `tests/` |
| Install helper | `bin/install-wp-tests.sh` (set `WP_TESTS_DIR` / `WP_CORE_DIR` / `TMPDIR` away from system `/tmp/` on shared hosts) |

If the test core is missing files (e.g. `wp-includes/theme.php`), restore the WordPress test install before trusting archive/restore failures — those tests can leave the tree damaged. When re-downloading core or the tests lib, stage archives under the account cross-project scratch dir (`$HOME/tmp/`) — never system `/tmp/`.

MockBuilder: use `setMethods()` (not `onlyMethods()` / `addMethods()`). Travis PHP 7.4 runs PHPUnit 7; PHP 8.5 runs 9.6 — see [`.cursor/skills/phpunit-total-upkeep/SKILL.md`](.cursor/skills/phpunit-total-upkeep/SKILL.md) and [`.cursor/rules/phpunit-mock-api.mdc`](.cursor/rules/phpunit-mock-api.mdc).

## Contribution process

- All changes must be submitted via pull requests.
- Public-facing work may originate from GitHub issues; internal work from Jira (`ENG7-####`).
- Ensure each pull request references its originating issue and includes a clear description of the change.
- For internal work: Jira parent Task/Bug/Story + **Dev** / **Review** sub-tasks; branch `ENG7-####-short-slug`; PR title includes the Jira key.
- Put full diagnosis on Jira; keep public GitHub surfaces free of devel hostnames, QA usernames, internal paths, and security-embargo detail.

## Security

- **Never put security-related information anywhere that could be public.** This is a strict rule, not a guideline.
- Treat the following surfaces as public-by-default and keep them free of security details: commit messages, commit bodies, branch names, tag names, PR titles, PR descriptions, PR review comments, PR inline/code comments, GitHub issue titles and bodies, GitHub issue comments, GitHub Discussions, release notes, `readme.txt`, source-code comments, and any other artifact that ships in the repo or is visible on the public GitHub project. Even on a draft PR or a private fork, assume content may become public.
- "Security-related information" includes vulnerability descriptions, attack vectors, exploit steps or payloads, affected versions, CVE/GHSA/Patchstack/finding IDs, reporter identities, severity assessments, embargoed disclosures, and phrasing that telegraphs "this commit fixes a security issue."
- Commit messages and PR descriptions for security fixes must use neutral, refactor/hardening language. Save the real context for the private Jira ticket or the GitHub Security Advisory (GHSA) draft.
- Keep sensitive descriptive content in the internal Jira ticket and/or the draft GHSA. Cross-link the public PR ↔ private Jira (and PR ↔ GHSA when applicable) but do not copy sensitive content back to the public side.
- If sensitive content has already been posted publicly, do not just edit/delete it — GitHub retains edit history. Follow the remediation skills under `.claude/skills/`.
- Report vulnerabilities privately to BoldGrid maintainers — never via public issues or PRs.
- When in doubt, default to silence on the public side and ask maintainers in Jira/Slack before posting.

### Security advisories (GHSAs) — naming and Jira-link conventions

Every GHSA created against this repo, and every PR opened inside a GHSA's temporary private fork (TPF), is paired with a Jira ticket (`ENG7-####`):

- **Advisory summary** starts with `{JIRA_KEY}: ` followed by the descriptive title.
- **Advisory description** starts with the bare line `Jira: https://imh-internal.atlassian.net/browse/{JIRA_KEY}` followed by a blank line.
- **TPF private PR title** uses the same `{JIRA_KEY}: {descriptive title}` prefix.
- Operational details live in `.claude/skills/move-pr-to-private-ghsa/SKILL.md` and `.claude/skills/pr-content-to-jira/SKILL.md`.

## Working files (scratch paths)

| Scope | Location |
|-------|----------|
| Project / task scratch (this repo) | `.cursor/working/` at the repository root (gitignored) |
| Cross-project / account-global scratch | `$HOME/tmp/` (e.g. WordPress tarballs, clones, probes that span repos) |
| Never | system `/tmp/` (shared hosts; other users' files can block renames) |

- Leave scratch files in place for traceability; the project directory is gitignored so they will not pollute commits.
- **Prefix task-specific scratch files** so concurrent workflows do not collide. Always include the GitHub `{REPO}` slug when a PR is involved (`{REPO}` = right-hand half of `org/repo`, lowercased — e.g. `boldgrid-backup`, or `boldgrid-backup-ghsa-…` for a TPF).
  - **PR-driven** (no Jira yet) → `{REPO}-{PR}-{role}.{ext}`
  - **Jira-driven** (no PR yet) → `{KEY}-{role}.{ext}` (e.g. `ENG7-4668-summary.md`)
  - **Both** → `{KEY}-{REPO}-{PR}-{role}.{ext}`
  - **GitHub-issue-only** → `gh-issue-{N}-{REPO}-{role}.{ext}`
  - **Persistent playbooks** → `PLAYBOOK-{topic}.md` or committed skills under `.claude/skills/` / `.cursor/skills/`
  - **Cross-cutting inventories** → `INVENTORY-{topic}.{ext}`

## Agent skills, rules, and commands

| Path | Use when |
|------|----------|
| [`.cursor/skills/phpcs-plugin-check/SKILL.md`](.cursor/skills/phpcs-plugin-check/SKILL.md) | Running or fixing Plugin Check / PHPCS / WPCS findings |
| [`.cursor/skills/phpunit-total-upkeep/SKILL.md`](.cursor/skills/phpunit-total-upkeep/SKILL.md) | Running or extending PHPUnit |
| [`.cursor/rules/total-upkeep-dev.mdc`](.cursor/rules/total-upkeep-dev.mdc) | Always-on project conventions for Cursor agents |
| [`.cursor/commands/phpcs.md`](.cursor/commands/phpcs.md) | Slash-style: run PHPCS |
| [`.cursor/commands/phpunit.md`](.cursor/commands/phpunit.md) | Slash-style: run PHPUnit |
| [`.claude/skills/pr-content-to-jira/SKILL.md`](.claude/skills/pr-content-to-jira/SKILL.md) | Move sensitive descriptive content off a public PR into Jira |
| [`.claude/skills/move-pr-to-private-ghsa/SKILL.md`](.claude/skills/move-pr-to-private-ghsa/SKILL.md) | Move an in-progress public security-fix PR into a GHSA TPF |
| [`.claude/skills/repost-pr-reviews-to-tpf/SKILL.md`](.claude/skills/repost-pr-reviews-to-tpf/SKILL.md) | Repost public-PR review threads onto the TPF PR |
| [`CLAUDE.md`](CLAUDE.md) | Claude Code project guidance (commands + architecture) |

Long-lived operational playbooks live under `.claude/skills/` (Claude) and `.cursor/skills/` (Cursor). Read the relevant skill at the start of a matching task before improvising.

## Related internal work

Plugin Directory / secret-rotation work is tracked separately from coding-standards / Plugin Check cleanup. Do not conflate the two in public PR copy.
