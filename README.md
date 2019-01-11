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

## Technical Documentation ##

### Auto Updates & Rollback Settings ###

#### Plugin Auto-Updates and Theme Auto-Updates

Taken from WordPress' [Configuring Automatic Background Updates](https://codex.wordpress.org/Configuring_Automatic_Background_Updates#Plugin_.26_Theme_Updates_via_Filter)

> By default, automatic background updates only happen for plugins and themes in special cases, as determined by the WordPress.org API response, which is controlled by the WordPress security team for patching critical vulnerabilities. To enable or disable updates in all cases, you can leverage the auto_update_$type filter, where $type would be replaced with "plugin" or "theme".

When these features are enabled, BoldGrid Backup will add *auto_update_plugin* and *auto_update_theme* filters so that *any plugin that has an update available* will update.

#### Auto Backup Before Updates

Before WordPress does any auto updates, it fires the [pre_auto_update](https://developer.wordpress.org/reference/hooks/pre_auto_update/) hook. If the user has the **Auto Backup Before Updates** option enabled, then a backup will occur before the auto update.

#### Auto Rollback

*Auto Rollback* is the feature within the BoldGrid Backup plugin that recommends making updates before performing any updates. If you disable this feature, then BoldGrid Backup will not recommend updates.

Example notice:
> BoldGrid Backup - Update Protection
>
> On this page you are able to update WordPress, Plugins, and Themes. It is recommended to backup your site before performing updates. If you perform a backup here, before performing updates, then an automatic rollback is possible.
>
> Update protection not available until you click Backup Site Now and a backup is created.
