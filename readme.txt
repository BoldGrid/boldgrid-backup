=== BoldGrid Backup ===
Contributors: imh_brad, joemoto, rramo012, timph
Tags: inspiration,customization,build,create,design,seo,search engine optimization
Requires at least: 4.3
Tested up to: 4.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BoldGrid Backup provides WordPress backup and restoration with update protection.

== Description ==

BoldGrid Backup provides WordPress backup and restoration with update protection.

== Requirements ==

* PHP 5.3 or higher
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* MySQL with the "mysqldump" utility.
* PHP ZipArchive (zip).
* A Cron system with the "crontab" utility.

== Installation ==

1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.

2. Activate the plugin through the Plugins menu in WordPress.

== Changelog ==

= 1.3.1 In progress =
* Misc:			JIRA WPB-2503	Added plugin requirements to readme.txt file.

= 1.3 =
* Update:						Bump version.

= 1.2.3 =
* Misc:			JIRA WPB-2344	Updated readme.txt for Tested up to 4.6.1.
* Bug fix:		JIRA WPB-2303	Added handling for restoration if site URL changed.  Fixed upload button in Chrome.
* Bug fix:		JIRA WPB-2336	Load BoldGrid settings from the correct WP option (site/blog).
* Bug fix:		JIRA WPB-2347	Fixed typo in archive deletion confirmation dialogue.
* Update:		JIRA WPB-2368	Set version constant from plugin file.

= 1.2.2 =
* Bug fix:		JIRA WPB-2314	Fixed errors during deactivation.
* Bug fix:		JIRA WPB-2311	Update class was not getting current plugin version.
* New feature:	JIRA WPB-2280	Added the ability to upload a backup archive.

= 1.2.1 =
* Bug fix:		JIRA WPB-2199	Updates via adminajax now updates the rollback timer.
* Misc:			JIRA WPB-2256	Updated readme.txt for Tested up to: 4.6.

= 1.2 =
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

= 1.0.2 =
* Rework:		JIRA WPB-1931	Removed notice for staged pending rollback.

= 1.0.1 =
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

= 1.0 =
* Initial public release.

== Upgrade Notice ==
