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
 *
 * @link https://github.com/BoldGrid/library/wiki/Library-RatingPrompt
 */

// Prevent direct calls.
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$allowed_tags = array(
	'a' => array(
		'href' => array(),
		'target' => array(),
	),
);


$lang = array(
	'thanks_text' => sprintf(
		wp_kses(
			__( 'Thanks! A new page should have opened to the BoldGrid Backup ratings page on WordPress.org. You will need to log in to your WordPress.org account before you can post a review. If the page didn\'t open, please click the following link: <a href="%1$s" target="_blank">%1$s</a>', 'boldgrid-backup' ),
			$allowed_tags
			),
		'https://wordpress.org/support/plugin/boldgrid-backup/reviews/'
	),
	'not_good_time' => sprintf(
		wp_kses(
			__( 'No problem, maybe now is not a good time. We want to be your WordPress backup plugin of choice. If you\'re experiencing a problem or want to make a suggestion, please %1$sclick here%2$s.', 'boldgrid-backup' ),
			$allowed_tags
			),
		'<a href="https://www.boldgrid.com/feedback" target="_blank">',
		'</a>'
	),
	'thanks_previous' => sprintf(
		wp_kses(
			__( 'Thank you for the previous rating! You can help us to continue improving the BoldGrid Backup plugin by reporting any bugs or submitting feature requests %1$shere%2$s. Thank you for using the BoldGrid Backup plugin!', 'boldgrid-backup' ),
			$allowed_tags
			),
		'<a href="https://www.boldgrid.com/feedback" target="_blank">',
		'</a>'
	),
	'feel_good_value' => __( 'If you feel you\'re getting really good value from the BoldGrid Backup plugin, could you do us a favor and rate us 5 stars on WordPress?', 'boldgrid-backup' ),
);

$slides = array(
	'thanks' => array(
		'text' => $lang['thanks_text'],
	),
	'maybe_later' => array(
		'text' => $lang['not_good_time'],
	),
	'already_did' => array(
		'text' => $lang['thanks_previous'],
	),
);

/*
 * Decisions
 *
 * @param string text   The text used as the decision.
 * @param string link   A link to navigate to if the decision is clicked.
 * @param string slide  The name of a slide to show after clicking this decision.
 * @param int    snooze A number to indicate how long a prompt should be snoozed for if the decision
 *                      is selected. If no snooze is set, the decision will dismiss the prompt.
 */
$decisions = array(
	'sure_will' => array(
		'text' => __( 'Yes, I sure will!', 'boldgrid-backup' ),
		'link' => 'https://wordpress.org/support/plugin/boldgrid-backup/reviews/',
		'slide' => 'thanks',
	),
	'maybe_still_testing' => array(
		'text' => __( 'Maybe later, I\'m still testing the plugin.', 'boldgrid-backup' ),
		// 'snooze' => WEEK_IN_SECONDS,
		'snooze' => 1,
		'slide' => 'maybe_later',
	),
	'already_did' => array(
		'text' => __( 'I already did', 'boldgrid-backup' ),
		'slide' => 'already_did',
	),
);

return array(
	// Set a title or description for your backup.
	'update_archive_attributes' => array(
		'threshold' => 10,
		'prompt' => array(
			'plugin' => BOLDGRID_BACKUP_KEY,
			'name' => 'update_title_description',
			'slides' => array(
				'start' => array(
					'text' => __( 'We hope that you\'re finding adding titles and descriptions to your backups helpful in keeping things organized.', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'],
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
					'text' => __( 'We\'re glad to see you\'re keeping your backups safe and downloading them to your local machine!', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'],
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
	// Create any type of backup.
	'any_backup_created' => array(
		'threshold' => 10,
		'prompt' => array(
			'plugin' => BOLDGRID_BACKUP_KEY,
			'name'	 => 'any_backup_created',
			'slides' => array(
				'start' => array (
					'text' => __( 'It looks like you\'ve created 10 backups with the BoldGrid Backup Plugin!', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'],
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
