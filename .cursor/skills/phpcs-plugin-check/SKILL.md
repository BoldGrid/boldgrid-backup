---
name: phpcs-plugin-check
description: >-
  Run and fix Total Upkeep (boldgrid-backup) Plugin Check / PHPCS / WPCS findings
  using phpcs.xml.dist. Use when the user mentions Plugin Check, PHPCS, WPCS,
  coding standards, phpcs.xml.dist, text domain mismatches, PrefixAllGlobals,
  or WordPress.org plugin review lint.
---

# PHPCS / Plugin Check (Total Upkeep)

## Before changing code

1. Read [`AGENTS.md`](../../../AGENTS.md) and [`phpcs.xml.dist`](../../../phpcs.xml.dist).
2. Do **not** rename the product display name or the `boldgrid-backup` text domain to silence trademark / mismatched-name Plugin Check warnings.
3. Prefer real fixes over blanket `phpcs:ignore`. When ignoring, use the **current** sniff name (`WordPress.Security.*`, `WordPress.DB.PreparedSQL.*`) — never the retired `WordPress.XSS.*`, `WordPress.CSRF.*`, or bare `WordPress.VIP`.

## Run

```bash
# From plugin root (phpcs.xml.dist is the project ruleset)
phpcs
phpcs --report=source
phpcs path/to/file.php
# If phpcs is not on PATH after Composer install:
./vendor/bin/phpcs
```

The project ruleset is Plugin Check–aligned (security, i18n, prefixes, restricted functions). It intentionally excludes full WordPress style noise (Yoda, array syntax wars, etc.).

## Triage guide

| Finding class | Action |
|---------------|--------|
| `PrefixAllGlobals.NonPrefixedVariableFound` in `admin/partials/` | False positive (method-scoped includes). Covered by ruleset — do not mass-rename. |
| Dead `phpcs:ignore` (`WordPress.XSS` / `WordPress.CSRF` / `WordPress.VIP`) | Review site: fix if unsafe, else rename annotation to current sniff + short justification. |
| Missing / wrong text domain | Fix to `'boldgrid-backup'` (typos like `boldgrid-bacup` are real bugs). |
| CLI/cron missing `ABSPATH` | Intentional for out-of-WP restore. Do not break `cli/` / `cron/` / `boldgrid-backup-cron.php`. |
| Filesystem / `proc_open` / `curl` in compressor, migrate, CLI | Intentional. Use ruleset exclusion or documented ignore. |
| `date()` in crontab builders | Keep server-local `date()` with documented ignore; use `gmdate()` in WP-bootstrapped code. |

## After fixes

```bash
phpcs --report=summary
find . -name vendor -prune -o -name node_modules -prune -o -name '*.php' -exec php -lf {} \;
# If behavior changed, run PHPUnit (see phpunit-total-upkeep skill)
```

## Scratch

Write intermediate reports under `.cursor/working/` (gitignored), e.g. `ENG7-4668-phpcs-source.txt`. Cross-project probes go under `$HOME/tmp/` — never system `/tmp/`.
