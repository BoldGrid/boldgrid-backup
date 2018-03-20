# BoldGrid Backup #
**Contributors:** imh_brad, joemoto, rramo012, timph
**Tags:** backup, restore, migrate, migration
**Requires at least:** 4.4
**Tested up to:** 4.9.4
**License:** GPLv2 or later
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

BoldGrid Backup provides WordPress backup and restoration with update protection.

## Description ##

BoldGrid Backup provides WordPress backup and restoration with update protection.

## Requirements ##

* PHP 5.3 or higher
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* MySQL with the "mysqldump" utility.
* PHP ZipArchive (zip).
* A Cron system with the "crontab" utility.

## Installation ##

1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.

2. Activate the plugin through the Plugins menu in WordPress.

## Changelog ##

### 1.6.0 ###
* New feature:  JIRA BGBKUP-70  Premium is now detected from the Connect Key license data.
* New feature:  JIRA BGBKUP-75  Added plugin framework library for the Connect Key prompt/notice.
* New feature:  JIRA BGBKUP-72  Restore only a database from a backup.

### 1.5.3 ###
* New feature:  JIRA BGBKUP-43  Allow browsing of archive.
* New feature:  JIRA BGBKUP-46  Add setting for include tables to the settings page.
* New feature:  JIRA BGBKUP-47  Omit tables during backup.
* Bug fix:      JIRA BGBKUP-54  Auto rollback not working.

### 1.5.2 ###
* New feature:  JIRA WPB-3516   Auto rollback via wp cron.
* Update:       JIRA WPB-3522   Show more detailed error messages when archiving fails.
* New feature:  JIRA BGBKUP-29  One click upload to Amazon S3.
* Bug fix:      JIRA WPB-3524   Cannot connect to database on plesk.
* New feature:  JIRA WPB-3539   Amazon S3 basic retention.
* Bug fix:      JIRA WPB-3534   Avoid node_modules timeout when calculating disk space.
* Bug fix:      JIRA BGBKUP-36  PclZip fails on broken symlinks.
* Bug fix:      JIRA WPB-3526   Total size: 0.00 B.
* Bug fix:      JIRA BGBKUP-37  Warning: scandir - Permission denied.
* Bug fix:      JIRA WPB-3512   Show error when user tries to upload file larger than what is accepted.
* Update:       JIRA BGBKUP-38  Add verbiage about WPCron not being precise.
* Update:       JIRA BGBKUP-34  If local storage not selected, delete backup after remote uploads.
* Update:       JIRA BGBKUP-35  As a user, I can receive an email after Amazon S3 upload.
* Bug fix:      JIRA BGBKUP-21  Wrong sql file is restored.
* Update:       JIRA WPB-3529   Send email if backup fails via cron.
* Update:       JIRA WPB-3529   Use ZipArchive by default.
* Bug fix:      JIRA WPB-3528   Backup: Cannot change backup directory.

### 1.5.1 ###
* Update		JIRA WPB-3480	Use wp filesystem when extracting backups.
* Update		JIRA WPB-3483	Separate the current php_zip archiving code.
* New feature:	JIRA WPB-3502	Create PclZip class for archiving.
* Update:		JIRA WPB-3503	If functionality tests fail, flag failures in red.
* Update:		JIRA WPB-3504	Use a script to create mysqldump rather than rely on system command.

### 1.5 ###
* Update:						Bump version.

### 1.3.12 ###
* Update:		JIRA WPB-3292	Updated plugin URI.

### 1.3.11 ###
* New feature:	JIRA WPB-3262	Added auto-update settings for plugins and themes.
* Bug fix:		JIRA WPB-3211	Skip node_modules paths when creating archives.

### 1.3.10 ###
* Bug fix:		JIRA WPB-3169	Fixed undefined property when pre-flight test fails.
* Bug fix:		JIRA WPB-3168	Fixed an undefined index when home dir is not writable.
* Bug fix:		JIRA WPB-3161	Fixed auto plugin update.

### 1.3.9 ###
* Bug fix:		JIRA WPB-3151	Added check and load before using get_plugin_data() for updates.

### 1.3.8 ###
* Bug fix:		JIRA WPB-2981	After migrating a site via boldgrid-backup, the backup directory was not updated if invalid.

### 1.3.7 ###
* Bug fix:		JIRA WPB-2912	Fixed issue when installing plugins from the Tools Import page.
* Bug fix:		JIRA WPB-2915	Fixed check for system tar and zip.
* Bug fix:		JIRA WPB-2907	Fixed method of locating home directory.

### 1.3.6 ###
* Update:		JIRA WPB-2896	Show how long the site was paused for.
* Update:		JIRA WPB-2897	Auto show move backups message.
* Bug fix:		JIRA WPB-2892	Fixed plugin update checks for some scenarios (WP-CLI, Plesk, etc).

### 1.3.5 ###
* Bug fix:		JIRA WPB-2821	Fixed plugin update checks for some scenarios (WP-CLI, Plesk, etc).
* Bug fix:		JIRA WPB-2682	Backing up fails after 5 minutes.

### 1.3.4 ###
* Bug fix:		JIRA WPB-2755	Fixed missing link in email.
* Testing:		JIRA WPB-2744	Tested on WordPress 4.7.
* Update:		JIRA WPB-2733	Update support urls.
* Update:		JIRA WPB-2672	Close session on gathering disk space api call.
* Bug fix:		JIRA WPB-2756	Uncaught TypeError: wp.template is not a function.

### 1.3.3 ###
* Update:		JIRA WPB-2714	Show backup limits to users.
* Update:		JIRA WPB-2717	Misc notices.
* Update:		JIRA WPB-2719	Disable backup now button.
* Update:		JIRA WPB-2651	Prevent backup if account is too large.

### 1.3.2 ###
* Bug fix:		JIRA WPB-2657	Added double-quote encapsulation to password in mysqldump defaults file.
* Update:		JIRA WPB-2637	Move backups when changing backup directory.
* Bug fix:						Typo fix.
* Update:		JIRA WPB-2652	Improve time to calculate disk space.

### 1.3.1 ###
* Misc:			JIRA WPB-2503	Added plugin requirements to readme.txt file.
* Update:		JIRA WPB-2584	Modify 'last created archive' message with link to archives.
* Update:		JIRA WPB-2585	Modify backup success message with link to settings.
* Update:		JIRA WPB-2586	Modify BoldGrid Backup menus.
* Update:		JIRA WPB-2589	Adjust display of preflight check.
* Update:		JIRA WPB-2592	Free limitations to days of the week.
* Update:		JIRA WPB-2595	Free limitations to retention.
* Update:		JIRA WPB-2596	Standard tooltips.
* Update:		JIRA WPB-2605	Add intro message to Archive page.
* Update:		JIRA WPB-2607	Modify backup id section on archives page.
* Update:		JIRA WPB-2608	Modify Backup Site messages.
* Update:		JIRA WPB-2594	Cache disk space data.
* Update:		JIRA WPB-2620	Add free / premium messages next to disk / db sizes.

### 1.3 ###
* Update:						Bump version.

### 1.2.3 ###
* Misc:			JIRA WPB-2344	Updated readme.txt for Tested up to 4.6.1.
* Bug fix:		JIRA WPB-2303	Added handling for restoration if site URL changed.  Fixed upload button in Chrome.
* Bug fix:		JIRA WPB-2336	Load BoldGrid settings from the correct WP option (site/blog).
* Bug fix:		JIRA WPB-2347	Fixed typo in archive deletion confirmation dialogue.
* Update:		JIRA WPB-2368	Set version constant from plugin file.

### 1.2.2 ###
* Bug fix:		JIRA WPB-2314	Fixed errors during deactivation.
* Bug fix:		JIRA WPB-2311	Update class was not getting current plugin version.
* New feature:	JIRA WPB-2280	Added the ability to upload a backup archive.

### 1.2.1 ###
* Bug fix:		JIRA WPB-2199	Updates via adminajax now updates the rollback timer.
* Misc:			JIRA WPB-2256	Updated readme.txt for Tested up to: 4.6.

### 1.2 ###
* Bug fix:		JIRA WPB-2218	Fixed auto-update action hook.
* Rework:		JIRA WPB-2209	Added a warning in the notice for restorations (may get logged-out).
* New feature:	JIRA WPB-2211	Added XHProf for optional PHP profiling.  Can be enabled in "config.local.php" by setting "xhprof" to true.
* Bug fix:		JIRA WPB-2201	Changed restore and delete buttons to POST forms, to resolve issue with people reloading the restore URL.
* Rework:		JIRA WPB-2197	Moved cron methods to a new class.
* Rework:		JIRA WPB-2087	Reworked for translations.
* Bug fix:		JIRA WPB-2087	Reworked error notices for restoration. Emptying archive list before updating after performing a backup.
* Bug fix:		JIRA WPB-2200	Disabled backup and restore buttons after starting a restoration.
* Redesign:		JIRA WPB-2188	Changed backup duration display seconds to 2 decimal places.
* Bug fix:		JIRA WPB-2194	Removed homedir not writable notice; moved info to the functionality test page.
* Bug fix:		JIRA WPB-2193	Removed add cron action on activation.
* Rework:		JIRA WPB-2063	Settings page will now load if functionality test fails.
* Rework:		JIRA WPB-2060	Cleanup for the rollback admin notice.

### 1.0.2 ###
* Rework:		JIRA WPB-1931	Removed notice for staged pending rollback.

### 1.0.1 ###
* Bug fix:		JIRA WPB-2079	Removed PHP SAPI check in cron script.
* New feature:	JIRA WPB-2061	Made it possible to change siteurl and retain matched archives (backups made as of this update).
* Bug fix:		JIRA WPB-2056	Restoration cron did not always complete.
* Bug fix:		JIRA WPB-2055	Better aligned rollback countdown timer with cron job.
* Redesign:		JIRA WPB-2053	Formatted the Functionality Test page.
* Bug fix:		JIRA WPB-2052	Provided message for empty archive list.
* New feature:	JIRA WPB-2062	Added setting for notification email address.
* New feature:	JIRA WPB-2063	Added setting for backup directory.
* New feature:	JIRA WPB-2064	Cancel auto-rollback if a restoration is performed.
* New feature:	JIRA WPB-2060	Added Rollback Site Now button in the rollback notice.
* Bug fix:		JIRA WPB-2057	Rollback information is now removed after timer reaches 0:00.
* New feature:	JIRA WPB-2037	Added capability for auto-updates by BoldGrid API response.
* Bug fix:		JIRA WPB-2054	Test for crontab now works when crontab is empty.
* Bug fix:		JIRA WPB-2051	Now closing PHP session on backup, download, and restore, so that other PHP requests from the client may load.
* Testing:		JIRA WPB-2046	Tested on WordPress 4.5.3.

### 1.0 ###
* Initial public release.

## Upgrade Notice ##


## Technical Documentation ##


### Auto Updates & Rollback Settings ###

*Plugin Auto-Updates* and *Theme Auto-Updates*
Taken from WordPress' [Configuring Automatic Background Updates](https://codex.wordpress.org/Configuring_Automatic_Background_Updates#Plugin_.26_Theme_Updates_via_Filter)
> By default, automatic background updates only happen for plugins and themes in special cases, as determined by the WordPress.org API response, which is controlled by the WordPress security team for patching critical vulnerabilities. To enable or disable updates in all cases, you can leverage the auto_update_$type filter, where $type would be replaced with "plugin" or "theme".

When these features are enabled, BoldGrid Backup will add **auto_update_plugin** and **auto_update_theme** filters so that *any plugin that has an update available* will update.


*Auto Backup Before Updates*
Before WordPress does any auto updates, it fires the [pre_auto_update](https://developer.wordpress.org/reference/hooks/pre_auto_update/) hook. If the user has the **Auto Backup Before Updates** option enabled, then a backup will occur before the auto update.


*Auto Rollback*
**Auto Rollback** is the feature within the BoldGrid Backup plugin that recommends making updates before performing any updates. If you disable this feature, then BoldGrid Backup will not recommend updates.

Example notice:
> BoldGrid Backup - Update Protection
> On this page you are able to update WordPress, Plugins, and Themes. It is recommended to backup your site before performing updates. If you perform a backup here, before performing updates, then an automatic rollback is possible.
> Update protection not available until you click Backup Site Now and a backup is created.