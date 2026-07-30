# Total Upkeep (boldgrid-backup) — Agent Guide

Short reference for Cursor agents working in this repository. Product display name is **Total Upkeep – WordPress Backup Plugin plus Restore & Migrate by BoldGrid**; the plugin slug and text domain remain **`boldgrid-backup`**. Do not rename either for Plugin Check cosmetics.

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

| Action | Command |
|--------|---------|
| PHPCS (project ruleset) | `~/.config/composer/vendor/bin/phpcs` (or `phpcs` if on PATH) — uses [`phpcs.xml.dist`](phpcs.xml.dist) |
| PHPCS one file | `phpcs path/to/file.php` |
| Auto-fix (careful) | `phpcbf path/to/file.php` |
| PHP syntax lint | `phplint` (shell alias) or `find . -name vendor -prune -o -name node_modules -prune -o -name '*.php' -exec php -lf {} \;` |
| PHPUnit (full) | `wpphpunit` from plugin root |
| PHPUnit + coverage | `wpphpunitcov` |
| PHPUnit explicit | `WP_TESTS_DIR=/home/joec/wordpress-tests-lib/ XDEBUG_MODE=off ~/.config/composer/vendor/bin/phpunit --debug --no-coverage` |
| Single test file | same as explicit, append `tests/admin/test-class-….php` |
| JS deps | `yarn` |
| Composer deps | `composer install` |

See also Cursor commands under [`.cursor/commands/`](.cursor/commands/).

## Coding standards

- Follow [`phpcs.xml.dist`](phpcs.xml.dist). It targets Plugin Check–relevant WordPress sniffs (security, i18n, prefixes, restricted functions), not the full stylistic WordPress standard.
- Partials under `admin/partials/` are included from class methods — do not mass-rename local variables for `PrefixAllGlobals`.
- CLI / cron entry points (`cli/`, `cron/`, `boldgrid-backup-cron.php`) are designed to run outside WordPress; do not add `ABSPATH`/`WPINC` guards that break restore-outside-WordPress.
- Compressor, migrate, CLI, and archive streaming paths intentionally use direct filesystem / process APIs; prefer justified `phpcs:ignore` or ruleset exclusions over rewriting to `WP_Filesystem`.
- Use tabs for PHP indentation; match surrounding style.
- Text domain is always `'boldgrid-backup'`.
- Prefer `wp_rand`, `wp_strip_all_tags`, `wp_safe_redirect`, `wp_json_encode`, `gmdate` inside WordPress-bootstrapped code. Keep server-local `date()` only for crontab / standalone CLI with a documented ignore.

## Testing

| Piece | Path / note |
|-------|-------------|
| WP tests lib | `WP_TESTS_DIR` → `~/wordpress-tests-lib` |
| WP core under test | `~/wordpress` |
| Bootstrap | `tests/bootstrap.php` |
| Suite | `tests/` |

If the test core is missing files (e.g. `wp-includes/theme.php`), restore the WordPress test install before trusting archive/restore failures — those tests can leave the tree damaged.

## Agent skills & rules

| Path | Use when |
|------|----------|
| [`.cursor/skills/phpcs-plugin-check/SKILL.md`](.cursor/skills/phpcs-plugin-check/SKILL.md) | Running or fixing Plugin Check / PHPCS / WPCS findings |
| [`.cursor/skills/phpunit-total-upkeep/SKILL.md`](.cursor/skills/phpunit-total-upkeep/SKILL.md) | Running or extending PHPUnit |
| [`.cursor/rules/total-upkeep-dev.mdc`](.cursor/rules/total-upkeep-dev.mdc) | Always-on project conventions for agents |
| [`.cursor/commands/phpcs.md`](.cursor/commands/phpcs.md) | Slash-style: run PHPCS |
| [`.cursor/commands/phpunit.md`](.cursor/commands/phpunit.md) | Slash-style: run PHPUnit |

## Scratch & privacy

- Agent scratch: `.cursor/working/` (gitignored). Do not use `/tmp/`.
- Public GitHub (PRs, commits, issues): no devel hostnames, QA usernames, internal paths, or security-embargo detail. Put full diagnosis on Jira (`imh-internal`).
- Jira + PR workflow: parent Task/Bug/Story + Dev / Review sub-tasks; reference the Jira key in the PR title.

## Related internal work

Plugin Directory / secret-rotation work is tracked separately (e.g. ENG7-4653). Coding-standards / Plugin Check cleanup is its own ticket (e.g. ENG7-4668). Do not conflate the two in public PR copy.
