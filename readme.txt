=== BoldGrid Backup ===
Contributors: boldgrid, joemoto, imh_brad, rramo012, timph, bgnicolepaschen
Tags: boldgrid, backup, restore, migrate, migration
Requires at least: 4.4
Tested up to: 5.0.0
Requires PHP: 5.3
Stable tag: 1.6.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BoldGrid Backup provides WordPress backup and restoration with update protection.

== Description ==

WordPress backup and restoration with update protection.

== Installation ==

= Minimum Requirements =

* PHP 5.3 or higher
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* A Cron system with the "crontab" utility, or WP Cron.

= Manually =
1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.

== Changelog ==

= 1.7.0 In progress =

* New feature: Added auto-update settings for individual plugins and themes.
* New feature: Added limited-lifetime download links for archive files.
* New feature: Added import backup archive from URL address.
* New feature: Added progress bar to show status of backups.
* New feature: Adding the ability to set and title and description to a backup.
* New feature: Adding the ability to flag a backup as being proteced (excluded from retention).
* Update:      Update protection is now valid for 1 hour after a full backup from the WordPress Updates or Plugins page.
* Update:      Made the Backup Archives page the default page in the admin menu.
* Bug fix:     Set a default backup directory if path in settings is not valid.  Remove filters before fixing home and siteurl on restore.
* Bug fix:     Some HTML was caught in translations.
* Bug fix:     Duplicate emails were sent when a backup was complete, fixed.
* Bug fix:     Preserve timestamp on ftp / sftp uploads.
* Update:      Save settings and reload to the current section.

= 1.6.5 =

Release Date: July 31st, 2018

* New feature: Preflight test to see if server time zone matches cron time.
* Bug fix: argv variables missing, Enabled register_argc_argv within cron command.
* Bug fix: Failed crons now write to log.

= 1.6.4 =

Release Date: July 17th, 2018

* Bug fix: Fixed and improved php-cli detection.
* Bug fix: Fixed "Undefined index 'plugins'" bug.
* Update: Cleaned up codebase to pass PHP CodeSniffer.

= 1.6.3 =

Release Date: June 11th, 2018

* Bug fix: System Cron detection failed in some environments.
* Update:  Clarified verbiage on setting up Premium connect key.

= 1.6.2 =

Release Date: May 25th, 2018

* Update: Detect and use available resources to trigger cron tasks.  Added cURL support.

= 1.6.1 =

Release Date: May 24th, 2018

* Update: Ran PHPCBF to beautify PHP code.
* Update: $_POST sanitization
* Update: Cron system updated to avoid calling core files directly

= 1.6.0 =

Release Date: April 11th, 2018

* New feature: Archive browser, the ability to see what's in a backup.
* New feature: Database browser, the ability to see at a high level what's in a backup.
* New feature: 1 click restore database only.
* New feature: FTP / SFTP support added.
* New feature: Control which files and database tables are backed up.
* Compatibility: PclZip support added for creating archives.
* Compatibility: WP Cron support added for scheduled backups.
* Compatibility: PHP Script used to backup database, rather than system commands.
* Improvement: Update admin pages to use WP UI/UX standards.
* Improvement: Improved UI in regards to time zones.
* Improvement: Failed items on Preflight Check page are highlighted in red.
* Improvement: Send email if backup fails via cron.
* Improvement: More details in Preflight Check to help with troubleshooting.
* Bug fix: Bug fixed with auto restoration feature.

= 1.5 =

Release Date: August 8th, 2017

* Update: Bump version.

= 1.3.12 =

Release Date: July 20th, 2017

* Update: Updated plugin URI.

Release Date: June 27th, 2017

= 1.3.11 =
* New feature: Added auto-update settings for plugins and themes.
* Bug fix: Skip node_modules paths when creating archives.

= 1.3.10 =

Release Date: May 16th, 2017

* Bug fix: Fixed undefined property when pre-flight test fails.
* Bug fix: Fixed an undefined index when home dir is not writable.
* Bug fix: Fixed auto plugin update.

= 1.3.9 =

Release Date: May 2nd, 2017

* Bug fix: Added check and load before using get_plugin_data() for updates.

= 1.3.8 =

Release Date: April 4th, 2017

* Bug fix: After migrating a site via boldgrid-backup, the backup directory was not updated if invalid.

= 1.3.7 =

Release Date: February 20th, 2017

* Bug fix: Fixed issue when installing plugins from the Tools Import page.
* Bug fix: Fixed check for system tar and zip.
* Bug fix: Fixed method of locating home directory.

= 1.3.6 =

Release Date: February 9th, 2017

* Update: Show how long the site was paused for.
* Update: Auto show move backups message.
* Bug fix: Fixed plugin update checks for some scenarios (WP-CLI, Plesk, etc).

= 1.3.5 =

Release Date: February 7th, 2017

* Bug fix: Fixed plugin update checks for some scenarios (WP-CLI, Plesk, etc).
* Bug fix: Backing up fails after 5 minutes.

= 1.3.4 =

Release Date: January 10th, 2017

* Update: Update support urls.
* Update: Close session on gathering disk space api call.
* Bug fix: Fixed missing link in email.
* Bug fix: Uncaught TypeError: wp.template is not a function.
* Testing: Tested on WordPress 4.7.

= 1.3.3 =

Release Date: December 20th, 2016

* Update: Show backup limits to users.
* Update: Misc notices.
* Update: Disable backup now button.
* Update: Prevent backup if account is too large.

= 1.3.2 =

Release Date: December 6th, 2016

* Update: Move backups when changing backup directory.
* Update: Improve time to calculate disk space.
* Bug fix: Added double-quote encapsulation to password in mysqldump defaults file.
* Bug fix: Typo fix.


= 1.3.1 =

Release Date: November 15th, 2016

* Update: Modify 'last created archive' message with link to archives.
* Update: Modify backup success message with link to settings.
* Update: Modify BoldGrid Backup menus.
* Update: Adjust display of preflight check.
* Update: Free limitations to days of the week.
* Update: Free limitations to retention.
* Update: Standard tooltips.
* Update: Add intro message to Archive page.
* Update: Modify backup id section on archives page.
* Update: Modify Backup Site messages.
* Update: Cache disk space data.
* Update: Add free / premium messages next to disk / db sizes.
* Misc: Added plugin requirements to readme.txt file.

= 1.3 =

Release Date: October 12th, 2016

* Update: Bump version.

= 1.2.3 =

Release Date: September 20th, 2016

* Bug fix: Added handling for restoration if site URL changed.  Fixed upload button in Chrome.
* Bug fix: Load BoldGrid settings from the correct WP option (site/blog).
* Bug fix: Fixed typo in archive deletion confirmation dialogue.
* Update: Set version constant from plugin file.
* Misc: Updated readme.txt for Tested up to 4.6.1.

= 1.2.2 =

Release Date: September 7th, 2016

* New feature: Added the ability to upload a backup archive.
* Bug fix: Fixed errors during deactivation.
* Bug fix: Update class was not getting current plugin version.

= 1.2.1 =

Release Date: August 23rd, 2016

* Bug fix: Updates via adminajax now updates the rollback timer.
* Misc: Updated readme.txt for Tested up to: 4.6.

= 1.2 =

Release Date: August 9th, 2016

* New feature: Added XHProf for optional PHP profiling.  Can be enabled in "config.local.php" by setting "xhprof" to true.
* Bug fix: Fixed auto-update action hook.
* Bug fix: Changed restore and delete buttons to POST forms, to resolve issue with people reloading the restore URL.
* Bug fix: Reworked error notices for restoration. Emptying archive list before updating after performing a backup.
* Bug fix: Disabled backup and restore buttons after starting a restoration.
* Bug fix: Removed homedir not writable notice; moved info to the functionality test page.
* Bug fix: Removed add cron action on activation.
* Redesign: Changed backup duration display seconds to 2 decimal places.
* Rework: Settings page will now load if functionality test fails.
* Rework: Cleanup for the rollback admin notice.
* Rework: Added a warning in the notice for restorations (may get logged-out).
* Rework: Moved cron methods to a new class.
* Rework: Reworked for translations.

= 1.0.2 =

Release Date: July 22nd, 2016

* Rework: Removed notice for staged pending rollback.

= 1.0.1 =

Release Date: July 6th, 2016

* New feature: Added setting for notification email address.
* New feature: Added setting for backup directory.
* New feature: Cancel auto-rollback if a restoration is performed.
* New feature: Added Rollback Site Now button in the rollback notice.
* New feature: Made it possible to change siteurl and retain matched archives (backups made as of this update).
* New feature: Added capability for auto-updates by BoldGrid API response.
* Redesign:	Formatted the Functionality Test page.
* Bug fix: Removed PHP SAPI check in cron script.
* Bug fix: Restoration cron did not always complete.
* Bug fix: Better aligned rollback countdown timer with cron job.
* Bug fix: Provided message for empty archive list.
* Bug fix: Rollback information is now removed after timer reaches 0:00.
* Bug fix: Test for crontab now works when crontab is empty.
* Bug fix: Now closing PHP session on backup, download, and restore, so that other PHP requests from the client may load.
* Testing: Tested on WordPress 4.5.3.

= 1.0 =

Release Date: June 21st, 2016

* Initial public release.

== Upgrade Notice ==


== Technical Documentation ==


= Auto Updates & Rollback Settings =

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
