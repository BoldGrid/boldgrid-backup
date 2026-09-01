# Run PHPUnit

From the Total Upkeep plugin root (`boldgrid-backup`):

1. Read `AGENTS.md` and `.cursor/skills/phpunit-total-upkeep/SKILL.md` if present.
2. Confirm `WP_TESTS_DIR` points at your account WordPress tests library (commonly `$HOME/wordpress-tests-lib`) and that a matching WordPress core tree exists (commonly `$HOME/wordpress`).
3. Run:

```bash
export WP_TESTS_DIR="${WP_TESTS_DIR:-$HOME/wordpress-tests-lib}"
XDEBUG_MODE=off ./vendor/bin/phpunit --debug --no-coverage
```

Or a single file the user named:

```bash
XDEBUG_MODE=off ./vendor/bin/phpunit --debug --no-coverage path/to/test.php
```

4. Report pass/fail counts. If bootstrap fails on missing WP core files, say the test install needs repair before attributing failures to the plugin.
5. Log verbose output under `.cursor/working/` when useful; use `$HOME/tmp/` only for cross-project probes — never system `/tmp/`.
