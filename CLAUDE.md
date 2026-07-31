# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Total Upkeep** (slug / text domain: `boldgrid-backup`) is a WordPress backup, restore, and migrate plugin by BoldGrid. It provides automated and manual backups, remote storage, site check / auto-rollback, and CLI / cron restore paths that can run outside a bootstrapped WordPress install.

- **PHP compatibility**: `>=7.4` (enforced via `composer.json` platform config)
- **WordPress compatibility**: 5.0+
- **Current version**: see `Version` in `boldgrid-backup.php` / `Stable tag` in `readme.txt` (do not bump casually outside a release process)
- **Do not rename** the Total Upkeep display name or the `boldgrid-backup` text domain for Plugin Check cosmetics

Also read [`AGENTS.md`](AGENTS.md) for coding standards, security/public-surface rules, scratch-path conventions, and skill indexes.

## Commands

### Install dependencies
```bash
composer install -o
yarn
```

### PHP linting
```bash
# Syntax check all PHP files (excludes vendor/node_modules)
find . -name vendor -prune -o -name node_modules -prune -o -name '*.php' -exec php -lf {} \;

# Plugin Check–aligned coding standards (project ruleset)
phpcs
# or, if phpcs is not on PATH:
./vendor/bin/phpcs
```

Ruleset: [`phpcs.xml.dist`](phpcs.xml.dist). Prefer this over the older `yarn run php-codesniffer` script (WordPress-Docs/Extra via node_modules).

### JS linting
```bash
yarn run js-lint
yarn run js-lint-fix
```

### Running tests

Tests require a WordPress test environment. Prefer account paths (not system `/tmp/`):

```bash
# Optional scaffold (override dirs on shared hosts)
export TMPDIR="${TMPDIR:-$HOME/tmp}"
export WP_TESTS_DIR="${WP_TESTS_DIR:-$HOME/wordpress-tests-lib}"
export WP_CORE_DIR="${WP_CORE_DIR:-$HOME/wordpress}"
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
```

Run the full PHPUnit suite from the plugin root:
```bash
WP_TESTS_DIR="${WP_TESTS_DIR:-$HOME/wordpress-tests-lib}" XDEBUG_MODE=off \
  ./vendor/bin/phpunit --debug --no-coverage
```

Run a single test file:
```bash
WP_TESTS_DIR="${WP_TESTS_DIR:-$HOME/wordpress-tests-lib}" XDEBUG_MODE=off \
  ./vendor/bin/phpunit --debug --no-coverage \
  tests/admin/test-class-boldgrid-backup-admin-auto-updates.php
```

Standalone CLI / unit scripts that do not bootstrap WordPress may run with plain `php` and need no `WP_TESTS_DIR`.

## Architecture

### Layout

| Path | Role |
|------|------|
| `boldgrid-backup.php` | Plugin bootstrap; constants, activation hooks |
| `includes/` | Core loader, activator/deactivator, archiver/restorer, download |
| `admin/` | Admin UI, settings, cron UI, compressors, DB dump/import, dashboards |
| `admin/partials/` | Views included from class methods (not global scope) |
| `cli/` | Standalone CLI entry points (may run outside WordPress) |
| `cron/` | Cron helpers / restore-outside-WordPress |
| `boldgrid-backup-cron.php` | Cron entry used outside a normal WP request |
| `rest/` | REST API controllers |
| `tests/` | PHPUnit suite (`tests/bootstrap.php`) |

### Naming

Classes use the `Boldgrid_Backup_*` prefix (WordPress classic style, not PSR-4 namespaces under `src/`). Text domain and option/hook prefixes stay on `boldgrid-backup` / `boldgrid_backup` / `bgbkup` as configured in `phpcs.xml.dist`.

### Bootstrap flow

1. `boldgrid-backup.php` — defines version/path constants and boots the plugin when WordPress loads it.
2. `includes/class-boldgrid-backup.php` — loads dependencies, i18n, and registers admin hooks via the loader.
3. Admin feature classes under `admin/` implement backup, restore, settings, and notice flows.
4. CLI / cron paths intentionally avoid assuming a full WordPress admin bootstrap so emergency restore can work when WP is broken.

### Coding standards notes

- Follow [`phpcs.xml.dist`](phpcs.xml.dist) (Plugin Check–relevant sniffs). Do not mass-rename locals in `admin/partials/` for `PrefixAllGlobals`.
- Do not add `ABSPATH`/`WPINC` guards to `cli/`, `cron/`, or `boldgrid-backup-cron.php` that break out-of-WP restore.
- Prefer justified `phpcs:ignore` or ruleset exclusions for intentional filesystem / process usage in compressor, migrate, CLI, and archive streaming paths.
- Indentation: tabs. Strings: single quotes unless interpolation is required.
- Do not make unrelated coding-standards fixes in changed files.

### Contribution notes

- All changes require a pull request referencing a GitHub or Jira issue.
- Internal work: Jira parent + Dev/Review sub-tasks; branch `ENG7-####-short-slug`.
- Public surfaces: no devel hostnames, QA usernames, internal paths, or security-embargo detail — see Security in [`AGENTS.md`](AGENTS.md).

### Security reporting

Report vulnerabilities privately to BoldGrid maintainers. Do not file public issues or PRs that describe exploit detail. Use `.claude/skills/pr-content-to-jira` and `.claude/skills/move-pr-to-private-ghsa` when relocating sensitive public-PR content or moving a fix into a GHSA temporary private fork.

## Working files

- Project scratch: `.cursor/working/` (gitignored). Cross-project scratch: `$HOME/tmp/`. Never system `/tmp/`.
- Filename conventions and security rules: see [`AGENTS.md`](AGENTS.md).
