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
 * @author     BoldGrid <support@boldgrid.com>
 */

// phpcs:disable WordPress.VIP

defined( 'WPINC' ) || die;

ob_start();

preg_match(
	'/\(([^\)]+)\).+?(MSIE|(?!Gecko.+)Firefox|(?!AppleWebKit.+Chrome.+)Safari|(?!AppleWebKit.+)Chrome|AppleWebKit(?!.+Chrome|.+Safari)|Gecko(?!.+Firefox))(?: |\/)([\d\.apre]+)/',
	$_SERVER['HTTP_USER_AGENT'],
	$browser_info
);

$local_info = [
	[
		'title' => __( 'Browser', 'boldgrid-backup' ),
		'value' => ( ! empty( $browser_info[2] ) ? $browser_info[2] : __( 'Unknown browser', 'boldgrid-backup' ) ) .
			' ' .
			( ! empty( $browser_info[3] ) ? $browser_info[3] : __( 'Unknown version', 'boldgrid-backup' ) ),
	],
	[
		'title' => __( 'Operating System', 'boldgrid-backup' ),
		'value' => ! empty( $browser_info[1] ) ? $browser_info[1] : __( 'Unknown', 'boldgrid-backup' ),
	],
];

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

$server_info = [
	[
		'title' => __( 'Server Name', 'boldgrid-backup' ),
		'key'   => 'SERVER_NAME',
	],
	[
		'title' => __( 'Server IP Address', 'boldgrid-backup' ),
		'key'   => 'SERVER_ADDR',
	],
	[
		'title' => __( 'Server Type', 'boldgrid-backup' ),
		'key'   => 'SERVER_SOFTWARE',
	],
];

$server_info_markup = '';
foreach ( $server_info as $info ) {
	if ( empty( $_SERVER[ $info['key'] ] ) ) {
		continue;
	}

	$server_info_markup .= sprintf(
		'<li><strong>%1$s</strong>: %2$s</li>',
		$info['title'],
		$_SERVER[ $info['key'] ]
	);
}

if ( function_exists( 'php_uname' ) ) {
	$server_architecture = sprintf(
		'%1$s %2$s %3$s',
		php_uname( 's' ),
		php_uname( 'r' ),
		php_uname( 'm' )
	);
} else {
	$server_architecture = __( 'Unknown', 'boldgrid-backup' );
}

$server_info_markup .= sprintf(
	'<li><strong>%1$s</strong>: %2$s</li>',
	__( 'Server OS', 'boldgrid-backup' ),
	$server_architecture
);

printf(
	'
	<h2>%1$s</h2>
	<p>%2$s</p>
	<p>%3$s</p>
	<hr />',
	esc_html__( 'Where should I store my backups?', 'boldgrid-backup' ),
	sprintf(
		// translators: 1: HTML strong open tag. 2: HTML strong close tag, 3: Plugin title.
		esc_html__(
			'Throughout the %3$s plugin, you will see references to %1$sLocal Machine%2$s, %1$sWeb Server%2$s, and %1$sRemote Storage%2$s. These are all locations you can save your backup archives to.',
			'boldgrid-backup'
		),
		'<strong>',
		'</strong>',
		esc_html( BOLDGRID_BACKUP_TITLE )
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
		'%1$sRemote Storage%2$s providers are servers other than your %3$sLocal Machine%4$s and %3$sWeb Server%4$s where you can store files. For example, %3$sFTP%4$s, %5$s, and %6$s are all considered Remote Storage Providers.',
		'boldgrid-backup'
	),
	/* 1 */ '<strong>',
	/* 2 */ '</strong>',
	/* 3 */ '<em>',
	/* 4 */ '</em>',
	/* 5 */ '<span class="bgbkup-remote-logo bgbkup-gdrive-logo" title="' . esc_attr( __( 'Google Drive', 'boldgrid-backup' ) ) . '"></span>',
	/* 6 */ '<span class="bgbkup-remote-logo amazon-s3-logo" title="' . esc_attr( __( 'Amazon S3', 'boldgrid-backup' ) ) . '"></span>'
);
echo '</p>';

if ( ! $this->core->config->is_premium_done ) {
	$premium_url = $this->core->go_pro->get_premium_url( 'bgbkup-tools-faq-storage' );
	printf(
		'
		<div class="bg-box-bottom premium wp-clearfix">
			<p>%2$s</p>
			<p>%1$s</p>
		</div>',
		$this->core->go_pro->get_premium_button( $premium_url, esc_html__( 'Unlock Feature', 'boldgrid-backup' ) ), // phpcs:ignore WordPress.XSS.EscapeOutput, WordPress.Security.EscapeOutput
		sprintf(
			// translators: 1 Markup showing a "Google Drive" logo, 2 Markup showing an "Amazon S3" logo.
			esc_html__( 'Catastrophic data loss can happen at any time. Storing your archives in multiple secure locations will keep your website data safe and put your mind at ease. Upgrade now to enable automated remote backups to %1$s and %2$s', 'boldgrid-backup' ),
			'<span class="bgbkup-remote-logo bgbkup-gdrive-logo" title="Google Drive"></span>',
			'<span class="bgbkup-remote-logo amazon-s3-logo" title="Amazon S3"></span>'
		)
	);
}

$output = ob_get_contents();
ob_end_clean();

return $output;
