<?php
/**
 * File: config.activity.php
 *
 * @link https://www.boldgrid.com
 * @since 1.7.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// Prevent direct calls.
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$lang = array(
	'thanks_text' => 'Thanks! A new page should have opened to the BoldGrid Backup ratings page on WordPress.org. You will need to log in to your WordPress.org account before you can post a review. If the page didn\'t open, please click the following link: <a href="https://wordpress.org/support/plugin/post-and-page-builder/reviews/" target="_blank">https://wordpress.org/support/plugin/post-and-page-builder/reviews/</a>',
	'feel_good_value' => 'If you feel you\'re getting really good value from the BoldGrid Backup plugin, could you do us a favor and rate us 5 stars on WordPress?',
);

$slides = array(
	'thanks' => array(
		'text' => $lang['thanks_text'],
	),
	'maybe_later' => array(
		'text' => 'No problem, maybe now is not a good time. We want to be your WordPress backup plugin of choice. If you\'re experiencing a problem or want to make a suggestion, please <a href="https://www.boldgrid.com/feedback" target="_blank">click here</a>.',
	),
	'already_did' => array(
		'text' => 'Thank you for the previous rating! You can help us to continue improving the BoldGrid Backup plugin by reporting a bug or submiting a feature request by <a href="https://www.boldgrid.com/feedback" target="_blank">clicking here</a>. Thank you for using the BoldGrid Backup plugin!',
	),
);

/*
 * Decisions
 *
 * @param string text   The text used as the decision.
 * @param string link   A link to navigate to if the decision is clicked.
 * @param string slide  The name of a slide to show after clicking this decision.
 * @param int    snooze A number to indicate how long a prompt should be snoozed
 *                      for if the decision is selected. If no snooze is set, the
 *                      decision will dismiss the prompt.
 */
$decisions = array(
	'sure_will' => array(
		'text' => 'Yes, I sure will!',
		'link' => 'https://wordpress.org/support/plugin/boldgrid-backup/reviews/',
		'slide' => 'thanks',
	),
	'maybe_still_testing' => array(
		'text' => 'Maybe later, I\'m still testing the plugin.',
		// 'snooze' => WEEK_IN_SECONDS,
		'snooze' => 1,
		'slide' => 'maybe_later',
	),
	'already_did' => array(
		'text' => 'I already did',
		'slide' => 'already_did',
	),
);

return array(
	// Set a title or description for your backup.
	'update_archive_attributes' => array(
		'threshold' => 5,
		'prompt' => array(
			'plugin' => BOLDGRID_BACKUP_KEY,
			'name' => 'update_archive_attributes',
			'slides' => array(
				'start' => array(
					'text' => 'We hope that you\'re finding adding titles and descriptions to your backups helpful in keeping things organized. ' . $lang['feel_good_value'],
					'decisions' => array(
						$decisions['sure_will'],
						$decisions['maybe_still_testing'],
						$decisions['already_did'],
					),
				),
				'thanks' => $slides['thanks'],
				'maybe_later' => $slides['maybe_later'],
				'already_did' => $slides['already_did'],
			),
		),
	),
	// Download a backup to your local machine.
	'download_to_local_machine' => array(
		'threshold' => 5,
		'prompt' => array(
			'plugin' => BOLDGRID_BACKUP_KEY,
			'name'	 => 'download_to_local_machine',
			'slides' => array(
				'start' => array (
					'text' => 'We\'re glad to see you\'re keeping your backups safe and downloading them to your local machine! ' . $lang['feel_good_value'],
					'decisions' => array(
						$decisions['sure_will'],
						$decisions['maybe_still_testing'],
						$decisions['already_did'],
					),
				),
				'maybe_later' => $slides['maybe_later'],
				'thanks' => $slides['thanks'],
				'already_did' => $slides['already_did'],
			),
		),
	),
);
