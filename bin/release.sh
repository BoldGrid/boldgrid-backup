#!/usr/bin/env bash

# Cleanup uneeded git content.
find . -name ".gitignore" -type f -delete
echo "Finding and deleting .git folders."
find vendor/. -name ".git" -type d -exec echo {} +
find vendor/. -name ".git" -type d -exec rm -rf {} +

# Create a tag in the Wordpress.org SVN repo when after your build succeeds via Travis.
# https://github.com/BoldGrid/wordpress-tag-sync
chmod +x ./node_modules/@boldgrid/wordpress-tag-sync/release.sh && ./node_modules/@boldgrid/wordpress-tag-sync/release.sh;
