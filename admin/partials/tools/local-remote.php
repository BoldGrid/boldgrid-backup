<?php
/**
 * Show "Auto Updates" on settings page.
 *
 * @since 1.5.4
 */

defined( 'WPINC' ) ? : die;

// https://github.com/cbschuld/Browser.php
include_once BOLDGRID_BACKUP_PATH . '/vendor/cbschuld/browser.php/lib/Browser.php';
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

	$local_info_markup .= sprintf( '<li><strong>%1$s</strong>: %2$s</li>', $info['title'], $info['value'] );
}

$server_info = array(
	array(
		'title' => __( 'Server Name', 'boldgrid-backup' ),
		'key' => 'SERVER_NAME',
	),
	array(
		'title' => __( 'Server IP Address', 'boldgrid-backup' ),
		'key' => 'SERVER_ADDR',
	),
	array(
		'title' => __( 'Server Type / OS', 'boldgrid-backup' ),
		'key' => 'SERVER_SOFTWARE',
	),
);

$server_info_markup = '';
foreach ( $server_info as $info ) {
	if ( empty( $_SERVER[ $info['key'] ] ) ) {
		continue;
	}

	$server_info_markup .= sprintf( '<li><strong>%1$s</strong>: %2$s</li>', $info['title'], $_SERVER[ $info['key'] ] );
}

printf( '
	<h2>%1$s</h2>
	<p>%2$s</p>
	<p>%3$s</p>
	<hr />',
	__( 'Where should I store my backups?', 'boldgrid-backup' ),
	__( 'Throughout the BoldGrid Backup plugin, you will see references to <strong>Local Machine</strong>, <strong>Web Server</strong>, and <strong>Remote Storage</strong>. These are all locations you can save your backup archives to.', 'boldgrid-backup' ),
	__( 'Continue reading below to find out more about each. It is recommended to store backup archives in at least 2 different storage locations.', 'boldgrid-backup' )
);



printf( '<h3>%1$s</h3>', __( 'Local Machine', 'boldgrid-backup' ) );

printf(
	'<p>%1$s</p>',
	__( 'Your <strong>Local Machine</strong> is the device you are using right now to access the internet. It could be a desktop, laptop, tablet, or even a smart phone.', 'boldgrid-backup' )
);

if ( ! empty( $local_info_markup ) ) {
	printf( '
		<p>%1$s</p>
		%2$s',
		__( 'We are able to see the following information about your <strong>Local Machine</strong>:', 'boldgrid-backup' ),
		$local_info_markup
	);
}

echo '<hr />';

printf( '<h3>%1$s</h3>', __( 'Web Server', 'boldgrid-backup' ) );

printf(
	'<p>%1$s</p>',
	__( 'The <strong>Web Server</strong> is the server where your WordPress website lives. You usually pay your web hosting provider monthly or yearly for hosting.', 'boldgrid-backup' )
);

if ( ! empty( $server_info_markup ) ) {
	printf( '
		<p>%1$s</p>
		%2$s',
		__( 'We are able to see the following information about your <strong>Web Server</strong>:', 'boldgrid-backup' ),
		$server_info_markup
	);
}

echo '<hr />';

printf( '<h3>%1$s</h3>', __( 'Remote Storage', 'boldgrid-backup' ) );

printf(
	'<p>%1$s</p>',
	__( '<strong>Remote Storage</strong> providers are servers other than your <em>Local Machine</em> and <em>Web Server</em> where you can store files. For example, <em>FTP</em>, <em>SFTP</em>, and <em>Amazon S3</em> are all considered Remote Storage Providers.', 'boldgrid-backup' )
);

if ( ! $this->core->config->is_premium_done ) {
	printf( '
		<div class="bg-box-bottom premium wp-clearfix">
			%1$s
			%2$s
		</div>',
		$this->core->go_pro->get_premium_button(),
		__( 'Upgrade to <strong>BoldGrid Backup Premium</strong> to gain access to more <em>Remote Storage Providers</em>.', 'boldgrid-backup' )
	);
}


$output = ob_get_contents();
ob_end_clean();
return $output;

