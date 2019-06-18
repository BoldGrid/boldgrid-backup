=== BoldGrid Backup ===
Contributors: boldgrid, joemoto, imh_brad, rramo012, timph, bgnicolepaschen
Tags: backup, cloud backup, database backup, restore, wordpress backup
Requires at least: 4.4
Tested up to: 5.2
Requires PHP: 5.4
Stable tag: 1.10.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BoldGrid Backup provides WordPress backup and restoration with update protection.

== Description ==

The BoldGrid Backup Plugin will backup your entire WordPress site with just a couple of clicks right in your WordPress dashboard. Just select a time and day for backups to run automatically, or manually create a backup at any time with a single click.

Try out the [BoldGrid Backup Plugin](https://www.boldgrid.com/central/get-it-now?plugins=boldgrid-backup&redirect_url=wp-admin%2Fadmin.php%3Fpage%3Dboldgrid-backup) on Cloud WordPress to see for yourself!

== Features ==

The following features are available, of which you can find additional info for in the screenshots section below:
* Schedule backups.
* Create backups automatically before WordPress updates.
* Store backups offsite.
* Customize which files and folders are backed up.
* Customize which tables are backed up.
* WordPress core automatic update control.
* Auto Rollback after WordPress, plugin, and theme updates.
* Progress bar showing how far along your backup is.
* Receive emails when backups are created and restored.
* View a list of all of your backups, both on your Web Server and FTP/SFTP server.
* One click to upload a backup to remote storage.
* Add titles and descriptions to your backups.
* Easily view the files in a backup using the Backup Browser.
* View the tables and the number of records per table contained in a backup.
* View all the details of a backup, including whether it is a scheduled backup or a backup triggered by a user.
* Configure retention settings (only keep X number of backups).
* Protect important backups by excluding them from retention settings.
* Download and Upload backups using protected links, which makes transferring a website from one host to another an much easier process.

== Frequently Asked Questions ==

= Where can I find more help? =

If you have any questions on getting started with BoldGrid Backup, please visit our [Getting Started Guide](https://www.boldgrid.com/support/boldgrid-backup/).
We also suggest joining our [Team Orange User Group community](https://www.facebook.com/groups/BGTeamOrange) for free support, tips and tricks.

== Screenshots ==

1. Easily schedule backups using Cron or WP Cron. Set a time of day, select the days of the week, and BoldGrid Backup will automate backups for you. You will receive an email after each backup has been completed.
2. Automatically perform a backup before WordPress auto updates itself. This feature hooks into the [pre_auto_update](https://developer.wordpress.org/reference/hooks/pre_auto_update/) action.
3. After a scheduled backup completes, you can have it uploaded automatically to an FTP / SFTP server. Users who upgrade to premium can also store backups on Amazon S3.
4. You can configure which files and folders and include in your backups. "Include" and "Exclude" filters are easy to set up, and you can click the "Preview" button to get a listing of which files and folders will actually be included in your backup.
5. Have tables you don't want to back up? Within the list of database tables, uncheck the tables you want to be excluded from backups, and they won't be included.
6. Take control of how WordPress automatically updates itself. Select whether to auto update for major updates, minor updates, development updates, and/or translation updates.
7. Select which of your plugins to have automatically updated when updates are available.
8. Select which of your themes to have automatically updated when updates are available.
9. Before upgrading WordPress, or any plugins or themes, backup your site. After the upgrade, test your site for any issues. If any issues are found, you can one-click restore your website or wait for the countdown to end and your site will automatically restore itself.
10. For large sites, backups can sometimes take a bit of time to complete. During backups, a progress bar is shown to keep you updated on the backup's status.
11. When backups are completed, or when a backup is restored, BoldGrid Backup will send you an email.
12. The Backup Archives page will list all of your backups, and show you where each backup is stored (Web Server, FTP/SFTP, etc).
13. When viewing the details of a backup, click the "Upload" button to easily upload the backup archive to one of your remote storage providers, such as an FTP server.
14. To help keep your backups organized, you can add titles and descriptions to each backup.
15. Use the Backup Browser to view what files are contained in each of your backups.
16. You can also use the Backup Browser to see which database tables are included in the backup and compare the # records to your current database.
17. The right sidebar of the Backup Archive Details page shows information about a backup, including who made the backup, what was backed up, how long the backup took, and more.
18. You can configure retention settings (only keep X number of backups) so that disk space used by your Web Server and/or your FTP/SFTP to store backups does not grow out of control.
19. For backups you don't want to be deleted by your retention settings, you can configure them to be saved and not deleted when the retention process deletes the backup.
20. Migrating websites from one host to another only takes a few steps. On the source server, generate a protected link for which a backup can be downloaded. Then, on the destination server, upload a backup using that protected link. All that's left is clicking restore!

== Installation ==

= Minimum Requirements =

* PHP 5.4 or higher
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* A Cron system with the "crontab" utility, or WP Cron.
* A WP_Filesystem FS_METHOD being "direct".

= Manually =
1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.

== Changelog ==

= 1.10.1 In progress =
* Update:      Updated Travis CI config and dev dependencies.
* Update:      Moved auto-rollback setting to the auto-updates page.

= 1.10.0 =

Release date: June 10th, 2019

* New feature: Added settings section, logging, and email notifications for Site Check (bgbkup-cli).
* Bug fix:     Ensure archive exists before attempting to upload via ftp.
* Update:      Updated dependencies.
* Update:      Updated content for the failed Site Check email notification message.
* Update:      Removed duplicate build for toggles dependency.

= 1.9.3 =

Release date: Apr 30th, 2019

* Bug fix:     Avoid "Cannot close ZIP archive file" error by skipping files that are unreadable.
* Bug fix:     Ensure adequate permissions before attempting any restoration.
* Update:      Add source to Get Premium nav item.
* Update:      Fixed FTP support (when using FTPES: Explicit FTP over SSL/TLS).

= 1.9.2 =

Release date: Apr 16th, 2019

* Bug fix:     Prevent duplicate emails when backups are made before an auto update.

= 1.9.1 =

Release date: Apr 2nd, 2019

* New feature: Users can now specify a custom folder name for FTP uploads.
* Update:      Increased precision of "Archive file size" value within progress bar to better show a backup is still occurring and didn't freeze.
* Bug fix:     Honor bgbkup-cli method argument.

= 1.9.0 =

Release date: Mar 26th, 2019

* New feature: Added emergency/standalone restoration CLI process.
* Update:      When storing backups in wp-content dir, make "boldgrid_backup" dir name more unique.
* Bug fix:     Ensure library's activity class is available before using it.

= 1.8.0 =

Release date: Feb 14th, 2019

* New feature: Added WP-CLI support for backup schedule.
* Update:      Change thickbox background color to inform user something is loading.
* Update:      Misc updates required for next version of BoldGrid Backup Premium.

= 1.7.2 =

Release date: Jan 15th, 2019

* Update:      Improved journey for downloading the premium plugin.
* Update:      Reduced the number of FTP connections made on the settings page.
* Update:      Overhauled this readme file, added more info on features and added screenshots.
* Update:      New system that asks user for bug fixes / new features, or requests plugin rating.

= 1.7.1 =

Release date: Dec 18th, 2018

* Update:      Added PHP version to compatibility checks.
* Bug fix:     Hide certain update notices in storage configuration modal.

= 1.7.0 =

Release date: Dec 4th, 2018

* New feature: Added auto-update settings for individual plugins and themes.
* New feature: Added limited-lifetime download links for archive files.
* New feature: Added import backup archive from URL address.
* New feature: Added progress bar to show the status of backups.
* New feature: Adding the ability to set and title and description to a backup.
* New feature: Adding the ability to flag a backup as being protected (excluded from retention).
* Update:      Update protection is now valid for 1 hour after a full backup from the WordPress Updates or Plugins page.
* Update:      Made the Backup Archives page the default page in the admin menu.
* Bug fix:     Set a default backup directory if the path in settings is not valid.  Remove filters before fixing home and siteurl on restore.
* Bug fix:     Some HTML was caught in translations.
* Bug fix:     Duplicate emails were sent when a backup was complete, fixed.
* Bug fix:     Preserve timestamp on ftp/sftp uploads.
* Bug fix:     Fixed CLI support detection on some EA4 servers.
* Update:      Save settings and reload to the current section.
* Update:      Reorganized settings sections.

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
* Compatibility: PHP Script used to backup the database, rather than system commands.
* Improvement: Update admin pages to use WP UI/UX standards.
* Improvement: Improved UI in regards to time zones.
* Improvement: Failed items on Preflight Check page are highlighted in red.
* Improvement: Send an email if a backup fails via cron.
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

* Bug fix: Fixed undefined property when the pre-flight test fails.
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
* Bug fix: Fixed method of locating the home directory.

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

* Update: Update support URLs.
* Update: Close session on gathering disk space API call.
* Bug fix: Fixed missing link in an email template.
* Bug fix: Uncaught TypeError: wp.template is not a function.
* Testing: Tested on WordPress 4.7.

= 1.3.3 =

Release Date: December 20th, 2016

* Update: Show backup limits to users.
* Update: Misc notices.
* Update: Disable backup now button.
* Update: Prevent backup if the account is too large.

= 1.3.2 =

Release Date: December 6th, 2016

* Update: Move backups when changing backup directory.
* Update: Improve time to calculate disk space.
* Bug fix: Added double-quote encapsulation to the password in the mysqldump defaults file.
* Bug fix: Typo fix.


= 1.3.1 =

Release Date: November 15th, 2016

* Update: Modify 'last created archive' message with a link to archives.
* Update: Modify backup success message with a link to settings.
* Update: Modify BoldGrid Backup menus.
* Update: Adjust display of preflight check.
* Update: Free limitations to days of the week.
* Update: Free limitations to retention.
* Update: Standard tooltips.
* Update: Add intro message to the Archive page.
* Update: Modify backup id section on the archives page.
* Update: Modify Backup Site messages.
* Update: Cache disk space data.
* Update: Add free / premium messages next to disk / database sizes.
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
* Misc: Updated readme.txt for Tested up to 4.6.

= 1.2 =

Release Date: August 9th, 2016

* New feature: Added XHProf for optional PHP profiling.  Can be enabled in "config.local.php" by setting "xhprof" to true.
* Bug fix: Fixed auto-update action hook.
* Bug fix: Changed restore and delete buttons to POST forms, to resolve an issue with people reloading the restoration URL.
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

* New feature: Added setting for a notification email address.
* New feature: Added setting for backup directory.
* New feature: Cancel auto-rollback if restoration is performed.
* New feature: Added Rollback Site Now button in the rollback notice.
* New feature: Made it possible to change siteurl and retain matched archives (backups made as of this update).
* New feature: Added capability for auto-updates by BoldGrid API response.
* Redesign:    Formatted the Functionality Test page.
* Bug fix: Removed PHP SAPI check in the cron script.
* Bug fix: Restoration cron did not always complete.
* Bug fix: Better aligned rollback countdown timer with the cron job.
* Bug fix: Provided message for empty archive list.
* Bug fix: Rollback information is now removed after the timer reaches 0:00.
* Bug fix: Test for crontab now works when crontab is empty.
* Bug fix: Now closing PHP session on backup, download, and restore, so that other PHP requests from the client may load.
* Testing: Tested on WordPress 4.5.3.

= 1.0 =

Release Date: June 21st, 2016

* Initial public release.
