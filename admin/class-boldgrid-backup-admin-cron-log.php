<?php
/**
 * File: class-boldgrid-backup-admin-cron-log.php
 *
 * @link https://www.boldgrid.com
 * @since 1.6.5
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Cron_Log
 *
 * @since 1.6.5
 */
class Boldgrid_Backup_Admin_Cron_Log {

	/**
	 * The core class object.
	 *
	 * @since 1.6.5
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * The full path to the log file.
	 *
	 * @since 1.6.5
	 * @access private
	 * @var string
	 */
	private $log_path;

	/**
	 * The name of our log file.
	 *
	 * @since 1.6.5
	 * @access static
	 * @var string
	 */
	public static $log_name = 'boldgrid-backup-cron.log';

	/**
	 * Constructor.
	 *
	 * @since 1.6.5
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;

		$this->log_path = trailingslashit( BOLDGRID_BACKUP_PATH ) . self::$log_name;
	}

	/**
	 * Add an entry to the log.
	 *
	 * This is a static method accessed outside of the scope of WordPress (boldgrid-backup-cron.php).
	 * Therefore, we do not have access to any WordPress specific functions.
	 *
	 * @since 1.6.5
	 *
	 * @param string $message A message to add to the log.
	 */
	public static function add_log( $message ) {
		$file = dirname( __DIR__ ) . '/' . self::$log_name;
		$data = array();

		// Get data already in the file.
		if ( file_exists( $file ) && is_readable( $file ) ) {
			$current_contents = file_get_contents( $file ); // phpcs:ignore
			$data             = empty( $current_contents ) ? array() : json_decode( $current_contents, true );
		}

		$data[] = array(
			'time'    => time(),
			'message' => $message,
			'read'    => false,
		);

		// Only keep the last 10 entries.
		$data = array_slice( $data, -10, 10 );

		file_put_contents( $file, json_encode( $data ) ); // phpcs:ignore
	}

	/**
	 * Display an admin notice to the user informing them of new cron notices.
	 *
	 * @since 1.6.5
	 */
	public function admin_notice() {
		if ( ! current_user_can( 'update_plugins' ) || ! $this->has_unread() || $this->on_log_page() ) {
			return;
		}

		$message = sprintf(
			wp_kses(
				/* translators: %1$s is the URL to view the cron log. */
				__( 'Your latest <em>scheduled backup</em> may not have finished successfully. For more info, please <a href="%1$s">click here</a> to see your latest <em>cron notices</em>.', 'boldgrid-backup' ),
				array(
					'a'      => array(
						'href' => array(),
					),
					'em'     => array(),
					'strong' => array(),
				)
			), esc_url( 'admin.php?page=boldgrid-backup-tools&section=section_cron_log' )
		);

		$this->core->notice->boldgrid_backup_notice( $message );
	}

	/**
	 * Get our log.
	 *
	 * @since 1.6.5
	 *
	 * @return array An array of log entries.
	 */
	public function get_log() {
		$log = array();

		if ( ! $this->core->wp_filesystem->exists( $this->log_path ) || ! $this->core->wp_filesystem->is_readable( $this->log_path ) ) {
			return $log;
		}

		$contents = $this->core->wp_filesystem->get_contents( $this->log_path );
		return json_decode( $contents, true );
	}

	/**
	 * Generate and return the markup displaying our log entries.
	 *
	 * @since 1.6.5
	 */
	public function get_markup() {

		// If we're getting the markup, the user is looking at it. Mark it as read.
		$this->set_as_read();

		$log = $this->get_log();

		$markup = '<h2>' . __( 'Latest Cron Notices', 'boldgrid-backup' ) . '</h2>
			<p>' . __( 'If a scheduled backup cron fails, an error message will be logged to the <em>cron notices</em> log file. You can view those log entries below:', 'boldgrid-backup' ) . '</p>';

		if ( empty( $log ) ) {
			$markup .= '<p><em>' . __( 'No entries in the log.', 'boldgrid-backup' ) . '</em></p>';
		} else {
			$markup .= '<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:150px;">' . __( 'Time', 'boldgrid-backup' ) . '</th>
						<th>' . __( 'Message', 'boldgrid-backup' ) . '</th>
					</tr>
				</thead>';

			foreach ( $log as $item ) {
				$this->core->time->init( $item['time'] );
				$time    = $this->core->time->get_span();
				$message = esc_html( $item['message'] );

				$markup .= sprintf( '<tr><th>%1$s</th><td>%2$s</td></tr>', $time, $message );
			}

			$markup .= '</table>';
		}

		return $markup;
	}

	/**
	 * Whether or not there are unread messages.
	 *
	 * @since 1.6.5
	 *
	 * @return bool
	 */
	public function has_unread() {
		$log = $this->get_log();

		if ( empty( $log ) ) {
			return false;
		}

		foreach ( $log as $item ) {
			if ( empty( $item['read'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether or not we are on the logs page.
	 *
	 * Specifically, if the user clicked "click here" to be taken to
	 * admin.php?page=boldgrid-backup-tools&section=section_cron_log
	 *
	 * @since 1.6.5
	 *
	 * @return bool
	 */
	public function on_log_page() {
		global $pagenow;

		$on_login_page = 'admin.php' === $pagenow;

		$params = array(
			array(
				'key'   => 'page',
				'value' => 'boldgrid-backup-tools',
			),
			array(
				'key'   => 'section',
				'value' => 'section_cron_log',
			),
		);

		foreach ( $params as $param ) {
			if ( empty( $_GET[ $param['key'] ] ) || $param['value'] !== $_GET[ $param['key'] ] ) { // phpcs:ignore
				$on_login_page = false;
			}
		}

		return $on_login_page;
	}

	/**
	 * Set the log as being read.
	 *
	 * @since 1.6.5
	 *
	 * @return bool Whether or not the log file was updated successfully.
	 */
	public function set_as_read() {
		$log = $this->get_log();
		if ( empty( $log ) ) {
			return true;
		}

		foreach ( $log as &$item ) {
			$item['read'] = true;
		}

		return $this->core->wp_filesystem->put_contents( $this->log_path, wp_json_encode( $log ) );
	}
}
