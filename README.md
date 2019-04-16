# BoldGrid Backup #

BoldGrid Backup provides WordPress backup and restoration with update protection.

## Description ##

WordPress backup and restoration with update protection.

## Installation ##

### Minimum Requirements ###

* PHP 5.4 or higher
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* A Cron system with the "crontab" utility, or WP Cron.
* A WP_Filesystem FS_METHOD being "direct".

### Manually ###
1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.

## Development ##

Before you can use the development version of this plugin you must install the dependencies.

```
yarn install
composer install -o
gulp
```
