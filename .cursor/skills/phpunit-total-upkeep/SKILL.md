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
| Test library | `WP_TESTS_DIR` → account path (commonly `$HOME/wordpress-tests-lib`) |
| WordPress core under test | account path (commonly `$HOME/wordpress`); keep version-aligned with the tests lib |
| Plugin under test | this repository |

Do not assume `/tmp/wordpress` or `/tmp/wordpress-tests-lib`. Stage upgrade/repair downloads under `$HOME/tmp/` — never system `/tmp/`.

## Commands

From the plugin root (after `composer install`):

```bash
export WP_TESTS_DIR="${WP_TESTS_DIR:-$HOME/wordpress-tests-lib}"

# Full suite
XDEBUG_MODE=off ./vendor/bin/phpunit --debug --no-coverage

# With coverage (requires Xdebug)
./vendor/bin/phpunit --debug

# Single file
XDEBUG_MODE=off ./vendor/bin/phpunit --debug --no-coverage \
  tests/admin/test-class-boldgrid-backup-admin-auto-updates.php
```

Optional scaffold via `bin/install-wp-tests.sh` — set `TMPDIR`, `WP_TESTS_DIR`, and `WP_CORE_DIR` to account paths first (see [`CLAUDE.md`](../../../CLAUDE.md)).

Config: [`phpunit.xml`](../../../phpunit.xml). Bootstrap: [`tests/bootstrap.php`](../../../tests/bootstrap.php).

## Tips

- Archive/restore tests can leave the test WordPress core missing files (e.g. `wp-includes/theme.php`). If bootstrap fatals after a suite, repair the test core before chasing product bugs.
- Suite writes must not touch live crontab; existing tests block crontab writes — keep that invariant.
- Prefer adding tests next to the area under change (`tests/admin/`, `tests/cli/`, `tests/rest/`).
- Standalone CLI tests that do not bootstrap WordPress may run with plain `php` and need no `WP_TESTS_DIR`.

## MockBuilder API (PHPUnit 7 + 9)

Travis runs **PHPUnit 7** on PHP 7.4 (locked via `composer.json` / `platform.php`) and **PHPUnit 9.6** on PHP 8.5. Mock helpers must work on both.

- Use `setMethods( array( '…' ) )` — present in PHPUnit 7 and still works on 9.6.
- Do **not** use `onlyMethods()` / `addMethods()` — those are PHPUnit 8+ and fatal on the 7.4 job (`Call to undefined method …::onlyMethods()`).
- Prefer matching existing suite mocks in `tests/admin/test-class-boldgrid-backup-admin-auto-updates.php` and `tests/admin/test-class-boldgrid-backup-admin-db-import.php`.

## Scratch

Log full suite output to `.cursor/working/ENG7-####-phpunit.log`. Use `$HOME/tmp/` only for cross-repo probes.
