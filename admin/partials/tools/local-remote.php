<?php
/**
 * File: local-remote.php
 *
 * Show "Auto Updates" on settings page.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/tools
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

defined( 'WPINC' ) || die;

// @link https://github.com/cbschuld/Browser.php
require_once BOLDGRID_BACKUP_PATH . '/vendor/cbschuld/browser.php/lib/Browser.php';
$browser = new Browser();

ob_start();

$local_info = array(
	array(
		'title' => __( 'Browser', 'boldgrid-backup' ),
		'value' => $browser->getBrowser() . ' ' . $browser->getVersion(),
	),
	array(
		'title' => __( 'Operating System', 'boldgrid-backup' ),
		'value' => $browser->getPlatform(),
	),
);

$local_info_markup = '';
foreach ( $local_info as $info ) {
	if ( empty( $info['value'] ) ) {
		continue;
	}

	$local_info_markup .= sprintf(
		'<li><strong>%1$s</strong>: %2$s</li>',
		esc_html( $info['title'] ),
		esc_html( $info['value'] )
	);
}

$server_info = array(
	array(
		'title' => __( 'Server Name', 'boldgrid-backup' ),
		'key'   => 'SERVER_NAME',
	),
	array(
		'title' => __( 'Server IP Address', 'boldgrid-backup' ),
		'key'   => 'SERVER_ADDR',
	),
	array(
		'title' => __( 'Server Type / OS', 'boldgrid-backup' ),
		'key'   => 'SERVER_SOFTWARE',
	),
);

$server_info_markup = '';
foreach ( $server_info as $info ) {
	if ( empty( $_SERVER[ $info['key'] ] ) ) {
		continue;
	}

	$server_info_markup .= sprintf( '<li><strong>%1$s</strong>: %2$s</li>', $info['title'], $_SERVER[ $info['key'] ] );
}

printf(
	'
	<h2>%1$s</h2>
	<p>%2$s</p>
	<p>%3$s</p>
	<hr />',
	esc_html__( 'Where should I store my backups?', 'boldgrid-backup' ),
	sprintf(
		// translators: 1: HTML strong open tag. 2: HTML strong close tag.
		esc_html__(
			'Throughout the BoldGrid Backup plugin, you will see references to %1$sLocal Machine%2$s, %1$sWeb Server%2$s, and %1$sRemote Storage%2$s. These are all locations you can save your backup archives to.',
			'boldgrid-backup'
		),
		'<strong>',
		'</strong>'
	),
	esc_html__(
		'Continue reading below to find out more about each. It is recommended to store backup archives in at least 2 different storage locations.',
		'boldgrid-backup'
	)
);

printf( '<h3>%1$s</h3>', esc_html__( 'Local Machine', 'boldgrid-backup' ) );

echo '<p>';
printf(
	// translators: 1: HTML strong open tag. 2: HTML strong close tag.
	esc_html__(
		'Your %1$sLocal Machine%2$s is the device you are using right now to access the internet. It could be a desktop, laptop, tablet, or even a smart phone.',
		'boldgrid-backup'
	),
	'<strong>',
	'</strong>'
);
echo '</p>';

if ( ! empty( $local_info_markup ) ) {
	printf(
		'
		<p>%1$s</p>
		%2$s',
		sprintf(
			// translators: 1: HTML strong open tag. 2: HTML strong close tag.
			esc_html__(
				'We are able to see the following information about your %1$sLocal Machine%2$s:',
				'boldgrid-backup'
			),
			'<strong>',
			'</strong>'
		),
		$local_info_markup // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	);
}

echo '<hr />';

printf( '<h3>%1$s</h3>', esc_html__( 'Web Server', 'boldgrid-backup' ) );

echo '<p>';
printf(
	// translators: 1: HTML strong open tag. 2: HTML strong close tag.
	esc_html__(
		'The %1$sWeb Server%2$s is the server where your WordPress website lives. You usually pay your web hosting provider monthly or yearly for hosting.',
		'boldgrid-backup'
	),
	'<strong>',
	'</strong>'
);
echo '</p>';

if ( ! empty( $server_info_markup ) ) {
	printf(
		'
		<p>%1$s</p>
		%2$s',
		sprintf(
			// translators: 1: HTML strong open tag. 2: HTML strong close tag.
			esc_html__(
				'We are able to see the following information about your %1$sWeb Server%2$s:',
				'boldgrid-backup'
			),
			'<strong>',
			'</strong>'
		),
		$server_info_markup // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	);
}

echo '<hr />';

printf( '<h3>%1$s</h3>', esc_html__( 'Remote Storage', 'boldgrid-backup' ) );

echo '<p>';
printf(
	// translators: 1: HTML strong open tag, 2: HTML strong close tag, 3: HTML em open tag, 4: HTML em close tag.
	esc_html__(
		'%1$sRemote Storage%2$s providers are servers other than your %3$sLocal Machine%4$s and %3$sWeb Server%4$s where you can store files. For example, %3$sFTP%4$s, %3$sSFTP%4$s, and %3$sAmazon S3%4$s are all considered Remote Storage Providers.',
		'boldgrid-backup'
	),
	'<strong>',
	'</strong>',
	'<em>',
	'</em>'
);
echo '</p>';

if ( ! $this->core->config->is_premium_done ) {
	printf(
		'
		<div class="bg-box-bottom premium wp-clearfix">
			%1$s
			%2$s
		</div>',
		$this->core->go_pro->get_premium_button(), // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		sprintf(
			// translators: 1: HTML strong open tag, 2: HTML strong close tag, 3: HTML em open tag, 4: HTML em close tag.
			esc_html__(
				'Upgrade to %1$sBoldGrid Backup Premium%2$s to gain access to more %3$sRemote Storage Providers%4$s.',
				'boldgrid-backup'
			),
			'<strong>',
			'</strong>',
			'<em>',
			'</em>'
		)
	);
}

$output = ob_get_contents();
ob_end_clean();

return $output;
