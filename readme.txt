=== Total Upkeep – WordPress Backup Plugin plus Restore & Migrate by BoldGrid ===
Contributors: boldgrid, joemoto, imh_brad, rramo012, bgnicolepaschen, jamesros161, joe9663, weaponx13, jessecowens
Tags: backup, cloud backup, database backup, restore, wordpress backup
Requires at least: 5.0
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.17.5
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

* PHP 7.4 or higher.
* At least one of the following PHP execution functions enabled: "popen", "proc_open", "exec", "shell_exec", "passthru", or "system".
* A Cron system with the "crontab" utility, or WP Cron.
* A WP_Filesystem FS_METHOD being "direct".

= Manually =
1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the Plugins menu in WordPress.

== Changelog ==

For the full changelog, including releases before 1.17.0, see the changelog.txt file included with the plugin.

= 1.17.5 =
Release Date: Nov 25, 2026
* Improvement: Direct Transfer hardens staging path and database prefix handling. Thanks to Murad Akhmedov and Raphael P. Cigana for responsibly reporting these issues.
* Bug Fix: Direct Transfer no longer fails when the source response includes more than one Link header.
* Bug Fix: A crontab availability check no longer replaces unrelated cron jobs.
* Bug Fix: Skip repeated crontab format upgrades on every admin page load.
* Bug Fix: Network-activated installs are recognized as active.
* Bug Fix: A failed database restore records the error instead of causing a fatal error.
* Bug Fix: Common archive file extensions are excluded from default backups.
* Bug Fix: Avoid PHP warnings when reseller, archive, cron, or backup settings are missing or null.
* Bug Fix: Premium icon markup is available before translated strings load.
* Bug Fix: Skip FTP and FTPS connections when the saved host does not resolve.

= 1.17.4 =
Release Date: Jul 30, 2026
* Security Update: Rotate stored cron and CLI cancel secrets on upgrade so previously exposed credentials are no longer valid. Thanks to Jakub Herman for responsibly reporting this issue.

= 1.17.3 =
Release Date: Jul 23, 2026
* Security Update: Harden emergency restore metadata storage and cron restore authentication. Thanks to Jakub Herman for responsibly reporting this issue.
* Bug Fix: Fix FTP / FTPS disconnect handling on PHP 8.
* Bug Fix: Fix FTP remote upload status when directory listings include path prefixes.
* Bug Fix: Fix BoldGrid Library loading when using Composer 2.
* Bug Fix: Fix early translation loading notices on WordPress 6.7+.

= 1.17.2 =
Release Date: Mar 11, 2026
* Security Update: Increased security for canceling a pending rollback.

= 1.17.1 =
Release Date: Apr 14, 2025
* Bug Fix: Fix _load_textdomain_just_in_time notices [#624](https://github.com/BoldGrid/boldgrid-backup/issues/624)

= 1.17.0 =
Release Date: Mar 14, 2025
* New Feature: Direct Transfer feature added for live beta [#611](https://github.com/BoldGrid/boldgrid-backup/pull/611)
* Security Update: Add Compression Level validation to settings [#622](https://github.com/BoldGrid/boldgrid-backup/pull/622)

== Upgrade Notice ==

= 1.17.5 =
Fixes and hardening for Direct Transfer, crontab scheduling, restore, and default backup exclusions.

= 1.17.4 =
Security update recommended for all users. Rotates stored secrets on upgrade.

= 1.17.3 =
Security update recommended for all users.

= 1.12.0 =
BoldGrid Backup has become Total Upkeep.  Different name with the same great features.

= 1.14.10 =
Updating to Total Upkeep 1.14.10 will fix possible security issues related to the restore-info.json file and cli/env-info.php script.
