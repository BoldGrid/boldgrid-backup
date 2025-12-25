=== Total Upkeep – WordPress Backup Plugin plus Restore & Migrate by BoldGrid ===
Contributors: boldgrid, joemoto, imh_brad, rramo012, bgnicolepaschen, jamesros161, joe9663, weaponx13, jessecowens
Tags: backup, cloud backup, database backup, restore, wordpress backup
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 5.4
Stable tag: 1.17.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automated backups, remote backup to Amazon S3 and Google Drive, stop website crashes before they happen and more. Total Upkeep is the backup solution you need.

== Description ==

**Total Upkeep is more than just a "backup plugin." It can help stop website crashes before they even happen.**

Website data loss can happen even if you're doing everything "right," like keeping your WordPress and plugins updated or having a backup plugin installed. There's so many things outside of your control that could totally wipe out your website without any warning.

To keep your website safe, you'll need more than just a "backup plugin." You need a backup solution that helps prevent catastrophic data loss in the first place, and if the worst occurs, provides you with the tools to easily, quickly and painlessly get your site up and running again.

Total Upkeep is a full 360 solution that keeps your data safe and protects your website from catastrophic data loss. Features include:

* Automated and manual backups
* Full file and database backup or customize settings based on your needs
* Remote backups via FTP / SFTP, Amazon S3 and Google Drive (Premium)
* Total Upkeep checks to ensure that your web server has the necessary features to properly create backup archives, which protects the integrity of your backups
* Clone, duplicate and/or migrate your site with just a few clicks
* Site Check monitors your site for issues that could lead to site crashes, provides a tool set to restore your site even if your WordPress installation is inaccessible
* Auto rollback feature creates a backup before updates, restores your site to the last backup if an update fails
* Create staging sites to test new plugins or themes with Total Upkeep + Cloud WordPress

**I'm Intrigued! Tell Me More About Your Features...**

**Supports Both Automated and Manual Backups**
Simply select a date and time for automatic backups and Total Upkeep will create a backup archive of your entire WordPress installation and its database. If a full website or database backup isn't required, you can choose to backup only certain files, folders and database tables.

**Remote Backups**
Safely store backups remotely via FTP / SFTP. The premium version of Total Upkeep allows you to automatically upload archives to Amazon S3 or Google Drive.

**Easy Site Restoration and Website Migration**
Download, restore, or delete backups with a single click from the Total Upkeep Dashboard.

*Restore Your Website Even If WordPress Is Down*
Use our restoration script to restore a backup from outside of WordPress in the event WordPress itself has been corrupted.

*Restore Entire Backup Archive or Single Files*
Need to restore only one file from a backup? Total Upkeep Premium gives you the option to restore a single file within the backup browser.

*Historical Versions*
Do you have a file that needs to be restored, but not sure which backup it's in? The Historical Versions feature allows you to view a list of all of your backups containing that file and provides an easy way to restore the file.

**Easily Clone or Migrate Your Websites**
Total Upkeep allows you to easily clone a website and migrate it to another WordPress installation with just a few clicks. Install Total Upkeep on both WordPress sites, create a backup archive on the first site, then restore that backup on your second site using a protected link - simply copy and paste the link! It's the easiest and fastest way to duplicate / clone your website.

**Site Check**
Site Check monitors your site at specified intervals for errors that could lead to catastrophic data loss. If Site Check detects an error, it will send you an email alert and auto restore your website using your latest backup.

*Built-in Diagnostic Tools*
Site Check keeps a running historical error log, allowing you to easily diagnose and troubleshoot website issues.

*Powerful Website Restoration Functionality*
You can configure Site Check's settings from the Total Upkeep settings page, or via command line. If your site is totally non-functional, you can use the command line to easily restore your website from the last full backup archive.

**Auto Rollback**
You can set Total Upkeep to automatically backup your site before updates, and automatically rollback your site to the last backup if an update fails. Automatic updates can be set for WordPress core, plugins, and themes individually.

**Create Staging Sites with Total Upkeep + Cloud WordPress**
Cloud WordPress allows you to create a fully functional free WordPress demo (with or without BoldGrid) in just a few clicks. Use Total Upkeep to clone and migrate your website to a Cloud WordPress installation, where you can test themes, plugins and other website changes without fear of breaking your live site.

Try out the [Total Upkeep Plugin](https://www.boldgrid.com/central/get-it-now?plugins=boldgrid-backup&redirect_url=wp-admin%2Fadmin.php%3Fpage%3Dboldgrid-backup) on Cloud WordPress to see for yourself!

== Frequently Asked Questions ==

= How does Total Upkeep differ from other backup plugins? =

Total Upkeep is more than just a "backup plugin." It's a full 360 solution with tools and features that helps prevent website crashes from happening in the first place. In addition to all the features you've come to expect from a plugin with website backup functionality, Total Upkeep also offers:

* Site Check monitors your site for issues that could lead to site crashes, provides a toolset to restore your site even if your WordPress installation is inaccessible
* Auto rollback feature creates a backup before updates, restores your site to the last backup if an update fails
* Create staging sites to test new plugins or themes with Total Upkeep + Cloud WordPress

= What do I get with Total Upkeep Premium? =

Total Upkeep Premium gives you extra tools to prevent website crashes, as well as additional troubleshooting and diagnostic tools.

* Additional remote backup options (Amazon S3, Google Drive)
* Single file restoration
* Historical versions
* Search for recently modified files
* Save a copy of a file before updating
* Comprehensive update history

= Where can I find support for Total Upkeep? =

Have a problem? First, take a look at our [Getting Started](https://www.boldgrid.com/support/boldgrid-backup/) guide. If that doesn't answer your question, you can receive support for Total Upkeep at the [support forum](https://wordpress.org/support/plugin/boldgrid-backup/), the [Team Orange User Group](https://www.facebook.com/groups/BGTeamOrange/), our [comprehensive support library](https://www.boldgrid.com/support/), or our [official questions portal](https://www.boldgrid.com/feedback/communities/20-questions).

== Screenshots ==

1. Easily schedule backups using Cron or WP Cron. Set a time of day, select the days of the week, and Total Upkeep will automate backups for you. You will receive an email after each backup has been completed.
2. Automatically perform a backup before WordPress auto updates itself. This feature hooks into the [pre_auto_update](https://developer.wordpress.org/reference/hooks/pre_auto_update/) action.
3. After a scheduled backup completes, you can have it uploaded automatically to an FTP / SFTP server. Users who upgrade to premium can also store backups on Amazon S3.
4. You can configure which files and folders and include in your backups. "Include" and "Exclude" filters are easy to set up, and you can click the "Preview" button to get a listing of which files and folders will actually be included in your backup.
5. Have tables you don't want to back up? Within the list of database tables, uncheck the tables you want to be excluded from backups, and they won't be included.
6. Take control of how WordPress automatically updates itself. Select whether to auto update for major updates, minor updates, development updates, and/or translation updates.
7. Select which of your plugins to have automatically updated when updates are available.
8. Select which of your themes to have automatically updated when updates are available.
9. Before upgrading WordPress, or any plugins or themes, backup your site. After the upgrade, test your site for any issues. If any issues are found, you can one-click restore your website or wait for the countdown to end and your site will automatically restore itself.
10. For large sites, backups can sometimes take a bit of time to complete. During backups, a progress bar is shown to keep you updated on the backup's status.
11. When backups are completed, or when a backup is restored, Total Upkeep will send you an email.
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

* PHP 5.4 or higher.  PHP 7.4 or higher is recommended.
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* A Cron system with the "crontab" utility, or WP Cron.
* A WP_Filesystem FS_METHOD being "direct".

= Manually =
1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.

== Changelog ==

= 1.17.1 =
Release Date: Apr 14, 2025
* Bug Fix: Fix _load_textdomain_just_in_time notices [#624](https://github.com/BoldGrid/boldgrid-backup/issues/624)

= 1.17.0 =
Release Date: Mar 14, 2025
* New Feature: Direct Transfer feature added for live beta [#611](https://github.com/BoldGrid/boldgrid-backup/pull/611)
* Security Update: Add Compression Level validation to settings [#622](https://github.com/BoldGrid/boldgrid-backup/pull/622)

= 1.16.10 =
Release Date: Feb 26, 2025
* Bug Fix: Prevent old backlogged jobs from running due to a previous CRON bug.

= 1.16.9 =
Release Date: Feb 25, 2025
* Bug Fix: PHP Warning: Undefined array key “is_running” [#614](https://github.com/BoldGrid/boldgrid-backup/issues/614)
* Bug Fix: Fix scheduled jobs not running, and add extra logging [#612](https://github.com/BoldGrid/boldgrid-backup/issues/612)
* Security Update: Change from wp_remote_get to wp_safe_remote_get [#616](https://github.com/BoldGrid/boldgrid-backup/issues/616)

= 1.16.8 =
Release Date: Jan 15, 2025
* Bug Fix: Schedule settings shows no options when no cron is available. [#563](https://github.com/BoldGrid/boldgrid-backup/issues/563)
* Bug Fix: Update to Support Links [#607](https://github.com/BoldGrid/boldgrid-backup/issues/607)

= 1.16.7 =
Release Date: Nov 11, 2024
* Bug Fix: Added validation to cron_interval input [#606](https://github.com/BoldGrid/boldgrid-backup/pull/606)

= 1.16.6 =
Release Date: Nov 7, 2024
* Bug Fix: WP 6.7 - Function load_plugin_textdomain was called incorrectly [#603](https://github.com/BoldGrid/boldgrid-backup/issues/603)

= 1.16.5 =
Release Date: Sept 25th, 2024
* Bug Fix: Total Upkeep Disables Buttons in AIOSEO admin pages [#598](https://github.com/BoldGrid/boldgrid-backup/issues/598)
* Bug Fix: system_zip option no longer available [#599](https://github.com/BoldGrid/boldgrid-backup/issues/599)

= 1.16.4 =
Release Date: Aug 28th, 2024
* Improvement: Add /wp-content/cache to default exclusion rules [#486](https://github.com/BoldGrid/boldgrid-backup/issues/486)

= 1.16.3 =
Release Date: June 14th, 2024
* Bug Fix: Errors when setting up or using SFTP Remote storage [#593](https://github.com/BoldGrid/boldgrid-backup/issues/593)

= 1.16.2 =
Release Date: May 15th, 2024
* Bug Fix: Fix issues with depracated notices in PHP 8.2
* Bug Fix: Updated phpseclib to 3.0

= 1.16.1 =
Release Date: April 16th, 2024
* Update: Add additional logging to help determine what is triggering automatic backups [$586](https://github.com/BoldGrid/boldgrid-backup/issues/586)

= 1.16.0 =
Release Date: February 26, 2024
* New Feature: Add settings for cron interval for run-jobs.php [#584](https://github.com/BoldGrid/boldgrid-backup/issues/584)

= 1.15.10 =

Release date: February 7, 2024
* Bug Fix: User on composer based sites getting errors [#546](https://github.com/BoldGrid/boldgrid-backup/issues/546)

= 1.15.9 =
* Bug Fix: Fixed security issue with bgbkup-cli being executable from the web, when it should only be executable via cli.

= 1.15.8 =

Release date: January 9, 2024
* Bug Fix: Invalid regex character class in ftp setup [#576](https://github.com/BoldGrid/boldgrid-backup/issues/576)
* Bug Fix: Rework PDO Connections for Sockets [#574](https://github.com/BoldGrid/boldgrid-backup/pull/574)

= 1.15.7 =

Release date: July 17, 2023

* Update: Better handling when checking the WordPress installation size.
* Update: Updated translation POT file.

= 1.15.6 =

Release date: March 29th, 2023

* Update: Added additional logging for backups.
* Update: Added additional info to rest call regarding compatibility.

= 1.15.5 =

Release date: January 26th, 2023

* Bug fix: Set job status to running when run.

= 1.15.4 =

Release date: November 2nd, 2022

* Update: Fixing tdcron dependency.

= 1.15.3 =

Release date: November 1st, 2022

* Update: Updated dependencies.

= 1.15.2 =

Release date: May 27th, 2022

* Update: Updated dependencies.

= 1.15.1 =

Release date: May 18th, 2022

* Bug fix: Fixed bad rewrite rules on restorations due to cached permalink settings.

= 1.15.0 =

Release date: March 15th, 2022

* New feature: REST API calls for backup and settings management.
* Bug fix: posix_getpgid availability check.
* Update: Updated dependencies.

= 1.14.14 =

Release date: February 24th, 2022

* Update: Only show "backup in progress" notices for admins.
* Security fix: Permissions check added to heartbeat_received for backup progress.

= 1.14.13 =

Release date: July 22nd, 2021

* Update: Added a live log to the in progress bar.
* Update: Added a "cancel backup" link to the in progress bar.
* Update: Added "who / what triggered backup" to the in progress bar.
* Update: The in progress bar can now detect when a backup process has been killed.

= 1.14.12 =

Release date: April 13th, 2021

* Update: Improved output buffering when downloading a backup via ajax.
* Update: Added a download log.
* Update: Extra .git directories removed from vendor directory.

= 1.14.11 =

Release date: February 16th, 2021

* Bug fix: Improved check for available execution functions and disabled functions.
* Bug fix: Fixes js handling file / db backup filters on settings page.
* Update: Changed "download backup" feature to send chunked.
* Update: Fixed uasort usage.
* Update: Added "Dismiss" verbiage to "Please rate us!" notice.

= 1.14.10 =

Release date: December 14th, 2020

* Update: Added transfer log.
* Security fix: Fixes for restore-info.json file and cli/env-info.php script.

= 1.14.9 =

Release date: December 8th, 2020

* Bug fix: Fixed reset link for backup all tables.
* Bug fix: Fixed pagination buttons for file exclusion tool.
* Bug fix: Fixed several html escaping issues.
* Bug fix: Fixed file exclusion preview filter input.
* Bug fix: Fixed various jqmigrate warnings.

= 1.14.8 =

Release date: November 13th, 2020

* Update: Rebuild with composer 1.

= 1.14.7 =

Release date: November 12th, 2020

* Update: Prevent easy apache cache files in backups.
* Bug fix: Fixed nonce errors when downloading remote archives.

= 1.14.6 =

Release date: October 13th, 2020

* Update: Updated dependencies.
* Update: Added additional logged and filesystem analysis log.
* Bug fix: Fixed ftp bug.

= 1.14.5 =

Release date: September 22nd, 2020

* Update: Optimized functionality tests.
* Update: Optimized plugins and themes init in auto updates.

= 1.14.4 =

Release date: August 26th, 2020

* Bug fix: Invalid nonce when one click uploading to remote storage providers.
* Bug fix: Escaping / translation of "Remote Storage" help text on Archive Details page.
* Update: Allow auto update notice on updates page to be permanently dismissible.

= 1.14.3 =

Release date: August 12th, 2020

* Update: Updated Auto Update features to utilize the WordPress 5.5+ Auto Update UI.

= 1.14.2 =

Release date: July 22nd, 2020

* Bug fix: Fixed array_key_exists() warnings from auto-updates class.
* Bug fix: Fixed several invalid nonce errors.
* Bug fix: Fixed markup escaping in rating prompt.
* Bug fix: Fixed "backup site now" on archive page.

= 1.14.1 =

Release date: July 7th, 2020

* Bug fix: Auto Update Translation filter causes fatal error with JetPack active [#50]((https://github.com/BoldGrid/boldgrid-backup-premium/issues/50)

= 1.14.0 =

Release date: July 7th, 2020

* New feature: Timely Auto Updates - auto update WordPress, Plugins, and Themes after a set number of days.
* New feature: SystemZip Compression ratio - Modify System Zip process to address issues with exceeding php memory_limit and add option to set compression ratio.
* New feature: Added 'Backup Now' and 'Upload Backup' buttons to each Total Upkeep page.
* New feature: Added video guides to Premium Features page.
* Bug fix: Non Backup files should not be uploaded.
* Bug fix: Ensure user can CREATE VIEWS before restoring views.

= 1.13.12 =

Release date: July 6th, 2020

* Bug fix:    Site check emails sent regardless of user preference.

= 1.13.11 =

Release date: July 1st, 2020

* Bug fix:    Resolved file name conflict with certain security plugins.

= 1.13.10 =

Release date: June 23rd, 2020

* Update:     Updated dependencies.
* Bug fix:    Avoid fatal Boldgrid\Library\Library\Ui\Card on dashboard.

= 1.13.9 =

Release date: June 15th, 2020

* Update:     Updated retention logic for pre auto update backups.
* Bug fix:    Fixed "get all cron jobs" call for large crontabs.

= 1.13.8 =

Release date: June 9th, 2020

* Update:     Added orphaned file cleanup system.

= 1.13.7 =

Release date: June 4rd, 2020

* Update:     Create log file for local retention.

= 1.13.6 =

Release date: June 1st, 2020

* Bug fix:    Adjust the charset used when dumping database (derived from WordPress DB_CHARSET).

= 1.13.5 =

Release date: May 28th, 2020

* Update:     Create a restore log (like the backup log).
* Update:     Updated dependencies.
* Bug fix:    Avoid fatals on library issues.
* Bug fix:    Be more specific with data-toggle-target attribute.

= 1.13.4 =

Release date: May 21st, 2020

* Update:     If backup email fails, debug info written to log file.
* Update:     Definition added to allow for skipping email headers.

= 1.13.3 =

Release date: April 2nd, 2020

* Bug fix:    Database backups not working when using port other than 3306 (the Robert bug).

= 1.13.2 =

Release date: February 21st, 2020

* Bug fix:    Resolved activation bug during BoldGrid Inspirations deployment.

= 1.13.1 =

Release date: February 18th, 2020

* Update:      Added "Find modified files" card to Premium Features page.
* Update:      Allow error messages to be shown for remote storage providers.
* Update:      Updated dependencies.

= 1.13.0 =

Release date: February 6th, 2020

* Update:      Added new "Premium Features" dashboard page.
* Update:      Added support for system zip for generating backups.
* Update:      Updated dependencies.

= 1.12.6 =

Release date: January 16th, 2020

* Update:      Open logs full screen.
* Update:      Added additional info to the logs.
* Update:      Logs now listen for signals, can log when a script is killed.

= 1.12.5 =

Release date: January 14th, 2020

* Update:      Adding logging system.
* Update:      Updated dependencies.

= 1.12.4 =

Release date: January 10th, 2020

* Bug fix:     Escape table prefix when getting tables.
* Bug fix:     Include views when dumping the database.

= 1.12.3 =

Release date: December 19th, 2019

* Bug fix:     Only show plugin rename notice to active users.
* Update:      Updating link to admin page for entering BoldGrid Connect Key.

= 1.12.2 =

Release date: December 13th, 2019

* Bug fix:     Fixed filtering of archive attributes.

= 1.12.1 =

Release date: November 26th, 2019

* Bug fix:     Fixed sanitizing of ftp hostnames.

= 1.12.0 =

Release date: November 21th, 2019

* Update:      Renamed plugin from "BoldGrid Backup" to "Total Upkeep".
* New feature: Added support for database dump file encryption.

= 1.11.8 =

Release date: October 11th, 2019

* Update:      Updated dependencies to resolve possible pluggable function `wp_rand()` errors.

= 1.11.7 =

Release date: October 10th, 2019

* Update:      Updated backup directory suffix creation.
* Update:      Updated dependencies.

= 1.11.6 =

Release date: October 8th, 2019

* Bug fix:     Prevent fatals during activation when library classes are missing.
* Bug fix:     Fixed escaping of html on archives page when backup only exists remotely.
* Bug fix:     Don't show "Make your first backup" message while a backup is in progress.
* Bug fix:     Make notices on the Settings page dismissible.
* Bug fix:     Don't show "Update Protection" notice if we just updated something.
* Update:      Be default, have local storage enabled in remote settings.
* Update:      Avoid showing activation notice if users is redirected to the archives page.

= 1.11.5 =

Release date: October 1st, 2019

* Update:      Allow BoldGrid Backup Premium to define minimum free version.
* Update:      Updates to inform users DreamObjects is now available.

= 1.11.4 =

Release date: September 26th, 2019

* Bug fix:     Improve logic that checks if scheduled backups are enabled.

= 1.11.3 =

Release date: September 26th, 2019

* Bug fix:     Prevent warnings when user is logged in as a non admin.

= 1.11.2 =

Release date: September 17th, 2019

* Update:      Allow activation notice to be filtered.
* Update:      Reworked much of the js for "Backup site now" and "In progress".

= 1.11.1 =

Release date: September 10th, 2019

* Bug fix:     Fixed crontab entry removal on cancel of rollback from updating from version <=1.10.6 to >=1.11.0.
* Update:      Updated dependencies.

= 1.11.0 =

Release date: August 29th, 2019

* New feature: Added a transfers page.
* Bug fix:     Fixed "Use of undefined constant STDERR" warning for bgbkup-cli.
* Update:      Updated auto-rollback to use the CLI restoration process.
* Update:      Updated dependencies.
* Bug fix:     Fixed database table exclusion when none are selected.
* Bug fix:     Avoid zip close error by checking files before write/close.
* Bug fix:     Prevent the web server from killing the archive process.

= 1.10.6 =

Release date: August 1st, 2019

* Update:      Updated dependencies.

= 1.10.5 =

Release date: July 30th, 2019

* Update:      FTP mode detection taking too long; now saving mode.
* Bug fix:     Replaced cbschuld/browser.php with a custom solution to avoid class conflicts.
* Update:      Updated dependencies.

= 1.10.4 =

Release date: July 17th, 2019

* Bug fix:     Fixed get_execution_functions method so that disable_functions are properly removed.

= 1.10.3 =

Release date: July 2nd, 2019

* Update:      Moved BoldGrid RSS feed to the library.

= 1.10.2 =

Release date: July 1st, 2019

* Bug fix:     Fixed format of the Site Check cron entry.

= 1.10.1 =

Release date: Jun 18th, 2019

* Update:      Added helpful links to the plugin's row on Plugins > Install Plugins.
* Update:      Updating verbiage in several places to help inform user of Google Drive support.
* Update:      Show a getting started message to users after they've activated the plugin.
* Update:      Improved usability on "Backup Archives" page when there are no backups.
* New feature: Added a subpage for support.
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

* Bug fix: Updates via admin-ajax now updates the rollback timer.
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

== Upgrade Notice ==

= 1.12.0 =
BoldGrid Backup has become Total Upkeep.  Different name with the same great features.

= 1.14.10 =
Updating to Total Upkeep 1.14.10 will fix possible security issues related to the restore-info.json file and cli/env-info.php script.
