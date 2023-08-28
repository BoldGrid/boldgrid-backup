<?php
/**
 * File: class-boldgrid-backup-admin-auto-updates-logger.php
 *
 * @since      1.8.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Auto_Updates_Logger
 *
 * @since 1.8.0
 */
class Boldgrid_Backup_Admin_Auto_Updates_Logger {
	/**
	 * Core object.
	 *
	 * @var Boldgrid_Backup_Core
	 * @since 1.8.0
	 */
	public $core;

	/**
	 * Log object.
	 *
	 * @var Boldgrid_Backup_Admin_Log
	 * @since 1.8.0
	 */
	public $auto_update_log;

	/**
	 * Constructor.
	 *
	 * @since 1.8.0
	 */
	public function __construct() {
		add_action( 'upgrader_process_complete', array( $this, 'log_update' ), 10, 2 );
	}

	/**
	 * Log update information.
	 *
	 * @param WP_Upgrader $upgrader_object
	 * @param array       $options
	 */
	public function log_update( $upgrader_object, $options ) {
		$this->core            = apply_filters( 'boldgrid_backup_get_core', null );
		$this->auto_update_log = new Boldgrid_Backup_Admin_Log( $this->core );
		$this->auto_update_log->init( 'auto-update.log' );

		$this->auto_update_log->add( 'Upgrader Object: ' . wp_json_encode( $upgrader_object ) );
		$this->auto_update_log->add( 'Options: ' . wp_json_encode( $options ) );

		if ( isset( $options['action'] ) && 'update' === $options['action'] ) {
			$type = isset( $options['type'] ) ? $options['type'] : 'WP Core';

			$update_item = '';
			if ( isset( $options['temp_backup'] ) && ! empty( $options['temp_backup'] ) ) {
				$update_item = implode( ', ', $options['temp_backup'] );
			}

			$log_message = sprintf(
				'Automatic %s update for %s: complete.',
				$type,
				$update_item
			);

			$this->auto_update_log->add( $log_message );
		}
	}
}
