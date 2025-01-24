<?php
/**
 * File: class-boldgrid-backup-admin-migrate.php
 * 
 * The main class for the BoldGrid Transfer plugin.
 * 
 * @link https://www.boldgrid.com
 * @since 0.0.1
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate
 * 
 * The main class for the BoldGrid Transfer plugin.
 *
 * @since 0.0.1
 */
class Boldgrid_Backup_Admin_Migrate {
	/**
	 * Plugin Configs
	 * 
	 * @var array
	 * 
	 * @since 0.0.1
	 */
	public $configs = array();
	/**
	 * Backup Core
	 * 
	 * @var Boldgrid_Backup_Admin_Core
	 * 
	 * @since 0.0.3
	 */
	public $backup_core;

	/**
	 * Log
	 * 
	 * @var Boldgrid_Backup_Admin_Log
	 */
	public $log;

	/**
	 * Boldgrid_Transfer_ Tx instance
	 * 
	 * @var Boldgrid_Transfer_Tx
	 * 
	 * @since 0.0.1
	 */
	public $tx;

	/**
	 * Boldgrid_Transfer_Rx instance
	 * 
	 * @var Boldgrid_Transfer_Rx
	 * 
	 * @since 0.0.1
	 */
	public $rx;

	/**
	 * Boldgrid_Transfer_Util instance
	 * 
	 * @var Boldgrid_Transfer_Util
	 * 
	 * @since 0.0.1
	 */
	public $util;

	/**
	 * Constructor
	 * 
	 * @since 0.0.1
	 */
	public function __construct( $backup_core ) {
		$this->backup_core = $backup_core;
		$this->configs     = $backup_core->configs['direct_transfer'];

		$this->init_logging();

		$this->util    = new Boldgrid_Backup_Admin_Migrate_Util( $this );
		$this->tx      = new Boldgrid_Backup_Admin_Migrate_Tx( $this );
		$this->rx      = new Boldgrid_Backup_Admin_Migrate_Rx( $this );
		$this->restore = new Boldgrid_Backup_Admin_Migrate_Restore( $this );
	}

	/**
	 * Initialize logging
	 * 
	 * @since 0.0.5
	 */
	public function init_logging() {
		$this->log = new \Boldgrid_Backup_Admin_Log( $this->backup_core );

		$active_transfer = get_option( $this->configs['option_names']['active_transfer'], false );

		// Check the boldgrid_transfer_xfers option.
		// if the active transfer is not in the list,
		// or it is marked completed or cancelled, then
		// reset the active transfer.
		$transfers = get_option( $this->configs['option_names']['transfers'], array() );
		if ( ! empty( $active_transfer ) && ! isset( $transfers[ $active_transfer ] ) ) {
			$active_transfer = false;
		} elseif ( ! empty( $active_transfer ) && ( 'completed' === $transfers[ $active_transfer ]['status'] || 'cancelled' === $transfers[ $active_transfer ]['status'] ) ) {
			$active_transfer = false;
		}

		if ( $active_transfer ) {
			$this->log->init( 'direct-transfer-' . $active_transfer );
		} else {
			update_option( $this->configs['option_names']['active_transfer'], false, false );
			$this->log->init( 'direct-transfers' );
		}
	}
}
