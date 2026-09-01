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

$allowed_tags = [
	'a' => [
		'href'   => [],
		'target' => [],
	],
];

$lang = [
	'feel_good_value' => sprintf(
		// translators: 1: Plugin title.
		esc_html__(
			'If you feel you\'re getting really good value from the %1$s plugin, could you do us a favor and rate us 5 stars on WordPress?',
			'boldgrid-backup'
		),
		BOLDGRID_BACKUP_TITLE
	),
];

$default_prompt = [
	'plugin' => BOLDGRID_BACKUP_KEY,
	'name'   => 'REPLACE_THIS_NAME',
	'slides' => [
		'start'       => [
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
			'decisions' => [
				'sure_will'           => [
					'text'  => esc_html__( 'Yes, I sure will!', 'boldgrid-backup' ),
					'link'  => 'https://wordpress.org/support/plugin/boldgrid-backup/reviews/',
					'slide' => 'thanks',
				],
				'maybe_still_testing' => [
					'text'   => esc_html__( 'Maybe later, I\'m still testing the plugin.', 'boldgrid-backup' ),
					'snooze' => WEEK_IN_SECONDS,
					'slide'  => 'maybe_later',
				],
				'already_did'         => [
					'text'  => esc_html__( 'I already did / Permanently dismiss this notice', 'boldgrid-backup' ),
					'slide' => 'already_did',
				],
			],
		],
		'thanks'      => [
			'text' => sprintf(
				wp_kses(
					// translators: 1: Plugin title, 2: An open and closed anchor tag linking to the boldgrid-backup plugin in the repo.
					esc_html__(
						'Thanks! A new page should have opened to the %1$s ratings page on WordPress.org. You will need to log in to your WordPress.org account before you can post a review. If the page didn\'t open, please click the following link: %2$s',
						'boldgrid-backup'
					),
					$allowed_tags
				),
				BOLDGRID_BACKUP_TITLE,
				'<a href="https://wordpress.org/support/plugin/boldgrid-backup/reviews/" target="_blank">https://wordpress.org/support/plugin/boldgrid-backup/reviews/</a>'
			),
		],
		'maybe_later' => [
			'text' => sprintf(
				wp_kses(
					// translators: 1: The URL to submit boldgrid-backup bug reports and feature requests.
					esc_html__(
						'No problem, maybe now is not a good time. We want to be your WordPress backup plugin of choice. If you\'re experiencing a problem or want to make a suggestion, please %1$sclick here%2$s.',
						'boldgrid-backup'
					),
					$allowed_tags
				),
				'<a href="https://www.boldgrid.com/feedback" target="_blank">',
				'</a>'
			),
		],
		'already_did' => [
			'text' => sprintf(
				wp_kses(
					// translators: 1: HTML opening anchor tag linking to submit boldgrid-backup bug reports and feature requests, 2: HTML closing anchor tag, 3: Plugin title.
					esc_html__(
						'Thank you for the previous rating! You can help us to continue improving the %3$s plugin by reporting any bugs or submitting feature requests %1$shere%2$s. Thank you for using the %3$s plugin!',
						'boldgrid-backup'
					),
					$allowed_tags
				),
				'<a href="https://www.boldgrid.com/feedback" target="_blank">',
				'</a>',
				BOLDGRID_BACKUP_TITLE
			),
		],
	],
];

// Set a title or description for your backup.
$title_description_prompt                            = $default_prompt;
$title_description_prompt['name']                    = 'update_title_description';
$title_description_prompt['slides']['start']['text'] = esc_html__( 'We hope that you\'re finding adding titles and descriptions to your backups helpful in keeping things organized.', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'];

// Download a backup to your local machine.
$download_prompt                            = $default_prompt;
$download_prompt['name']                    = 'download_to_local_machine';
$download_prompt['slides']['start']['text'] = esc_html__( 'We\'re glad to see you\'re keeping your backups safe and downloading them to your local machine!', 'boldgrid-backup' ) . ' ' . $lang['feel_good_value'];

// Create any type of backup.
$any_backup_prompt                            = $default_prompt;
$any_backup_prompt['name']                    = 'any_backup_created';
$any_backup_prompt['slides']['start']['text'] = sprintf(
	// translators: 1: Plugin title.
	esc_html__( 'It looks like you\'ve created 10 backups with the %1$s plugin!', 'boldgrid-backup' ),
	BOLDGRID_BACKUP_TITLE
) . ' ' . $lang['feel_good_value'];

return [
	'update_title_description'  => [
		'threshold' => 10,
		'prompt'    => $title_description_prompt,
	],
	'download_to_local_machine' => [
		'threshold' => 2,
		'prompt'    => $download_prompt,
	],
	'any_backup_created'        => [
		'threshold' => 10,
		'prompt'    => $any_backup_prompt,
	],
];
