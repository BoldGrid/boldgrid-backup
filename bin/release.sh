#!/usr/bin/env bash

# Cleanup unneeded development and repository content.
#
# Keep this list explicit: production .htaccess files are hidden files too, but
# they are required to protect the standalone CLI and cron directories.
# wordpress-tag-sync removes some of these again in its SVN staging tree;
# removals here also cover items that tag-sync does not know about.
echo "Removing development-only files from release tree."
rm -rf .claude .cursor .github tests
rm -f \
	.distignore \
	.eslintignore \
	.eslintrc.js \
	.phpunit.result.cache \
	.prettierrc \
	.travis.yml \
	AGENTS.md \
	CLAUDE.md \
	phpcs.xml.dist \
	phpunit.xml

find . -name ".gitignore" -type f -delete
echo "Finding and deleting .git folders."
find vendor/ -name ".git" -type d -print -exec rm -rf {} +

# Never ship per-install secrets, restore-info, or other gitignored runtime
# artifacts. Deleting .gitignore above would otherwise allow locally generated
# files present on the CI/checkout disk into the staging copy.
echo "Removing per-install secret and restore-info files from release tree."
rm -f \
	cli/verify-*.php \
	cli/restore-locator.php \
	cli/bgbkup-cli.log \
	cron/restore-info-*.json \
	cron/restore-info.json \
	cron/cron-test.config \
	cron/cron-test.result \
	config.php \
	includes/config/*.local.php \
	boldgrid-backup-cron.log \
	error_log \
	yarn-error.log
rm -rf logs* reports/ build/ .vscode .project .settings .buildpath

# Create a tag in the Wordpress.org SVN repo when after your build succeeds via Travis.
# https://github.com/BoldGrid/wordpress-tag-sync
chmod +x ./node_modules/@boldgrid/wordpress-tag-sync/release.sh && ./node_modules/@boldgrid/wordpress-tag-sync/release.sh;
