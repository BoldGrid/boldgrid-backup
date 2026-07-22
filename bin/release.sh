#!/usr/bin/env bash

# Cleanup uneeded git content.
find . -name ".gitignore" -type f -delete
echo "Finding and deleting .git folders."
find vendor/ -name ".git" -type d -print -exec rm -rf {} +

# Never ship per-install secrets or restore-info in the release zip.
# (release packaging deletes .gitignore above, which would otherwise allow
# locally generated cli/verify-* and cron/restore-info-* into the artifact.)
echo "Removing per-install secret and restore-info files from release tree."
rm -f cli/verify-*.php cli/restore-locator.php cron/restore-info-*.json cron/restore-info.json

# Create a tag in the Wordpress.org SVN repo when after your build succeeds via Travis.
# https://github.com/BoldGrid/wordpress-tag-sync
chmod +x ./node_modules/@boldgrid/wordpress-tag-sync/release.sh && ./node_modules/@boldgrid/wordpress-tag-sync/release.sh;
