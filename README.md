# Total Upkeep #

[![Build Status](https://travis-ci.org/BoldGrid/boldgrid-backup.svg?branch=master)](https://travis-ci.org/BoldGrid/boldgrid-backup)
[![Greenkeeper badge](https://badges.greenkeeper.io/BoldGrid/boldgrid-backup.svg)](https://greenkeeper.io/)

Total Upkeep provides WordPress backup and restoration with update protection.

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
yarn
composer install -o
```
