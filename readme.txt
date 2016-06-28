=== BoldGrid Backup ===
Contributors: imh_brad, joemoto, rramo012, timph
Tags: inspiration,customization,build,create,design,seo,search engine optimization
Requires at least: 4.3
Tested up to: 4.5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BoldGrid Backup provides WordPress backup and restoration with update protection.

== Description ==

BoldGrid Backup provides WordPress backup and restoration with update protection.

== Installation ==

1. Upload the entire boldgrid-backup folder to the /wp-content/plugins/ directory.

2. Activate the plugin through the Plugins menu in WordPress.

== Changelog ==

= 1.0.1 In progress =
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
