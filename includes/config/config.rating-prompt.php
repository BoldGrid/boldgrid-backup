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
		'href'   => array(),
		'target' => array(),
	),
);

$lang = array(
	'feel_good_value' => __( 'If you feel you\'re getting really good value from the BoldGrid Backup plugin, could you do us a favor and rate us 5 stars on WordPress?', 'boldgrid-backup' ),
);

$default_prompt = array(
	'plugin' => BOLDGRID_BACKUP_KEY,
	'name'   => 'REPLACE_THIS_NAME',
	'slides' => array(
		'start'       => array(
			'text'      => $lang['feel_good_value'],
			/*
			 * Decisions
			 *
			 * @param string text   The text used as the decision.
			 * @param string link   A link to navigate to if the decision is clicked.
			 * @param string slide  The name of a slide to show after clicking this decision.
			 * @param int    snooze A number to indicate how long a prompt should be snoozed for if
			 *                      the decision
			 *                      is selected. If no snooze is set, the decision will dismiss the
			 *                      prompt.
			 */
			'decisions' => array(
				'sure_will'           => array(
					'text'  => __( 'Yes, I sure will!', 'boldgrid-backup' ),
					'link'  => 'https://wordpress.org/support/plugin/boldgrid-backup/reviews/',
					'slide' => 'thanks',
				),
				'maybe_still_testing' => array(
					'text'   => __( 'Maybe later, I\'m still testing the plugin.', 'boldgrid-backup' ),
					'snooze' => WEEK_IN_SECONDS,
					'slide'  => 'maybe_later',
				),
				'already_did'         => array(
					'text'  => __( 'I already did', 'boldgrid-backup' ),
					'slide' => 'already_did',
				),
			),
		),
		'thanks'      => array(
			'text' => sprintf(
				wp_kses(
					/* translators: The URL to the boldgrid-backup plugin in the plugin repo. */
					__( 'Thanks! A new page should have opened to the BoldGrid Backup ratings page on WordPress.org. You will need to log in to your WordPress.org account before you can post a review. If the page didn\'t open, please click the following link: <a href="%1$s" target="_blank">%1$s</a>', 'boldgrid-backup' ),
					$allowed_tags
				),
				'https://wordpress.org/support/plugin/boldgrid-backup/reviews/'
			),
		),
		'maybe_later' => array(
			'text' => sprintf(
				wp_kses(
					/* translators: The URL to submit boldgrid-backup bug reports and feature requests. */
					__( 'No problem, maybe now is not a good time. We want to be your WordPress backup plugin of choice. If you\'re experiencing a problem or want to make a suggestion, please %1$sclick here%2$s.', 'boldgrid-backup' ),
					$allowed_tags
				),
				'<a href="https://www.boldgrid.com/feedback" target="_blank">',
				'</a>'
			),
		),
		'already_did' => array(
			'text' => sprintf(
				wp_kses(
					/* translators: The URL to submit boldgrid-backup bug reports and feature requests. */
					__( 'Thank you for the previous rating! You can help us to continue improving the BoldGrid Backup plugin by reporting any bugs or submitting feature requests %1$shere%2$s. Thank you for using the BoldGrid Backup plugin!', 'boldgrid-backup' ),
					$allowed_tags
				),
				'<a href="https://www.boldgrid.com/feedback" target="_blank">',
				'</a>'
			),
		),
	),
);

// Set a title or description for your backup.
$title_description_prompt                            = $default_prompt;
$title_description_prompt['name']                    = 'update_title_description';
$title_description_prompt['slides']['start']['text'] = __( 'We hope that you\'re finding adding titles and descriptions to your backups helpful in keeping things organized.', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'];

// Download a backup to your local machine.
$download_prompt                            = $default_prompt;
$download_prompt['name']                    = 'download_to_local_machine';
$download_prompt['slides']['start']['text'] = __( 'We\'re glad to see you\'re keeping your backups safe and downloading them to your local machine!', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'];

// Create any type of backup.
$any_backup_prompt                            = $default_prompt;
$any_backup_prompt['name']                    = 'any_backup_created';
$any_backup_prompt['slides']['start']['text'] = __( 'It looks like you\'ve created 10 backups with the BoldGrid Backup Plugin!', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'];

return array(
	'update_title_description'  => array(
		'threshold' => 10,
		'prompt'    => $title_description_prompt,
	),
	'download_to_local_machine' => array(
		'threshold' => 2,
		'prompt'    => $download_prompt,
	),

	'any_backup_created'        => array(
		'threshold' => 10,
		'prompt'    => $any_backup_prompt,
	),
);
