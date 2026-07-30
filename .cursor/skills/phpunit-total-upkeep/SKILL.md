---
name: phpunit-total-upkeep
description: >-
  Run and extend PHPUnit for Total Upkeep (boldgrid-backup). Use when the user
  asks to run tests, debug failing PHPUnit, add coverage for admin/CLI/cron
  behavior, or set WP_TESTS_DIR for this plugin.
---

# PHPUnit (Total Upkeep)

## Environment

| Role | Path |
|------|------|
| Test library | `~/wordpress-tests-lib` → `WP_TESTS_DIR` |
| WordPress core under test | `~/wordpress` (currently **7.0.2**) |
| Plugin under test | this repo (usually `~/public_html/wp-content/plugins/boldgrid-backup`) |

Do not assume `/tmp/wordpress` or `/tmp/wordpress-tests-lib`. Stage upgrade/repair downloads under `~/tmp/` — never system `/tmp/`.

## Commands

From the plugin root:

```bash
# Preferred aliases (devel-joec)
wpphpunit
wpphpunitcov

# Explicit
WP_TESTS_DIR=/home/joec/wordpress-tests-lib/ XDEBUG_MODE=off \
  ~/.config/composer/vendor/bin/phpunit --debug --no-coverage

# Single file
WP_TESTS_DIR=/home/joec/wordpress-tests-lib/ XDEBUG_MODE=off \
  ~/.config/composer/vendor/bin/phpunit --debug --no-coverage \
  tests/admin/test-class-boldgrid-backup-admin-auto-updates.php
```

Config: [`phpunit.xml`](../../../phpunit.xml). Bootstrap: [`tests/bootstrap.php`](../../../tests/bootstrap.php).

## Tips

- Archive/restore tests can leave `~/wordpress` missing core files (e.g. `wp-includes/theme.php`). If bootstrap fatals after a suite, repair the test core before chasing product bugs.
- Suite writes must not touch live crontab; existing tests block crontab writes — keep that invariant.
- Prefer adding tests next to the area under change (`tests/admin/`, `tests/cli/`, `tests/rest/`).
- Standalone CLI tests that do not bootstrap WordPress may run with plain `php` and need no `WP_TESTS_DIR`.

## Scratch

Log full suite output to `.cursor/working/ENG7-####-phpunit.log` (or `~/tmp/` only for cross-repo probes).
