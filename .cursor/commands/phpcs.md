# Run PHPCS (Plugin Check–aligned)

From the Total Upkeep plugin root (`boldgrid-backup`):

1. Read `AGENTS.md` and `.cursor/skills/phpcs-plugin-check/SKILL.md` if present.
2. Run:

```bash
phpcs --report=source
# or: ./vendor/bin/phpcs --report=source
```

3. Optionally target paths the user named.
4. Summarize findings by sniff; do not rename the product or text domain to clear trademark warnings.
5. Fix genuine defects; leave intentional CLI/cron/filesystem exclusions alone.
6. Write intermediate reports under `.cursor/working/` (project scratch), not system `/tmp/`.
