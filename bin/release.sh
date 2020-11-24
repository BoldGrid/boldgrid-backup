#!/usr/bin/env bash

# Cleanup uneeded .gitignore files.
find . -name ".gitignore" -type f -delete

# Create a tag in the Wordpress.org SVN repo when after your build succeeds via Travis.
# https://github.com/BoldGrid/wordpress-tag-sync
chmod +x ./node_modules/@boldgrid/wordpress-tag-sync/release.sh && ./node_modules/@boldgrid/wordpress-tag-sync/release.sh;