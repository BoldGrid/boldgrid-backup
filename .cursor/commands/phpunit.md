# Run PHPUnit

From the Total Upkeep plugin root (`boldgrid-backup`):

1. Read `AGENTS.md` and `.cursor/skills/phpunit-total-upkeep/SKILL.md` if present.
2. Confirm `WP_TESTS_DIR` / `~/wordpress-tests-lib` and `~/wordpress` exist.
3. Run:

```bash
wpphpunit
```

Or a single file the user named via:

```bash
WP_TESTS_DIR=/home/joec/wordpress-tests-lib/ XDEBUG_MODE=off \
  ~/.config/composer/vendor/bin/phpunit --debug --no-coverage path/to/test.php
```

4. Report pass/fail counts. If bootstrap fails on missing WP core files, say the test install needs repair before attributing failures to the plugin.
