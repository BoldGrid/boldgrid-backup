# Total Upkeep #

[![Build Status](https://github.com/BoldGrid/boldgrid-backup/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/BoldGrid/boldgrid-backup/actions/workflows/ci.yml)


Total Upkeep provides WordPress backup and restoration with update protection.

## Description ##

WordPress backup and restoration with update protection.

## Installation ##

### Minimum Requirements ###

* PHP 7.4 or higher
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* A Cron system with the "crontab" utility, or WP Cron.
* A WP_Filesystem FS_METHOD being "direct".

### Manually ###
1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.

## Development ##

Before you can use the development version of this plugin you must install the dependencies.

```
yarn
composer install -o
```

GitHub Actions (`.github/workflows/ci.yml`) runs PHP syntax lint, PHPUnit, and JavaScript lint on PHP 7.4 and PHP 8.5 for pull requests and pushes to `master`. PHP 7.4 uses the locked PHPUnit 7; PHP 8.5 uses PHPUnit 9.6. A tag push on BoldGrid/boldgrid-backup publishes the WordPress.org tag and the GitHub release zip after both jobs pass. The WordPress.org step reads Actions secrets `WP_USERNAME` and `WP_PASSWORD`. The GitHub release step uses the built-in `github.token`. `.github/workflows/security-checks.yml` runs Semgrep, Conftest settings fixtures, and the `Boldgrid_Backup` security PHPCS ruleset on pull requests and pushes to `master`. That ruleset is separate from `phpcs.xml.dist`.
