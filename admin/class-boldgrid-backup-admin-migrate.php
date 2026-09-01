<?php
/**
 * File: class-boldgrid-backup-admin-migrate.php
 * 
 * The main class for the BoldGrid Transfer plugin.
 * 
 * @link https://www.boldgrid.com
 * @since 1.17.0
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_Admin_Migrate
 * 
 * The main class for the BoldGrid Transfer plugin.
 *
 * @since 1.17.0
 */
class Boldgrid_Backup_Admin_Migrate {
	/**
	 * Plugin Configs
	 *
	 * @var array
	 *
	 * @since 1.17.0
	 */
	public $configs = array();

	/**
	 * Backup Core
	 *
	 * @var Boldgrid_Backup_Admin_Core
	 *
	 * @since 1.17.0
	 */
	public $backup_core;

	/**
	 * Log
	 *
	 * @var Boldgrid_Backup_Admin_Log
	 * 
	 * @since 1.17.0
	 */
	public $log;

	/**
	 * Boldgrid_Transfer_ Tx instance
	 *
	 * @var Boldgrid_Transfer_Tx
	 *
	 * @since 1.17.0
	 */
	public $tx;

	/**
	 * Boldgrid_Transfer_Rx instance
	 *
	 * @var Boldgrid_Transfer_Rx
	 *
	 * @since 1.17.0
	 */
	public $rx;

	/**
	 * Boldgrid_Transfer_Util instance
	 *
	 * @var Boldgrid_Transfer_Util
	 *
	 * @since 1.17.0
	 */
	public $util;

	/**
	 * Boldgrid_Transfer_Restore instance
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Restore
	 * 
	 * @since 1.17.0
	 */
	public $restore;

	/**
	 * Constructor
	 * 
	 * @param Boldgrid_Backup_Admin_Core $backup_core Backup Core object.
	 *
	 * @since 1.17.0
	 */
	public function __construct( $backup_core ) {
		$this->backup_core = $backup_core;
		$this->configs     = $backup_core->configs['direct_transfer'];

		$this->init_logging();

		$this->util    = new Boldgrid_Backup_Admin_Migrate_Util( $this );
		$this->tx      = new Boldgrid_Backup_Admin_Migrate_Tx( $this );
		$this->rx      = new Boldgrid_Backup_Admin_Migrate_Rx( $this );
		$this->restore = new Boldgrid_Backup_Admin_Migrate_Restore( $this );

		add_action(
			'wp_ajax_nopriv_boldgrid_backup_process_direct_transfer',
			array( $this, 'ajax_process_direct_transfer' )
		);
	}

	/**
	 * Initialize logging
	 * 
	 * @since 1.17.0
	 */
	public function init_logging() {
		$this->log = new \Boldgrid_Backup_Admin_Log( $this->backup_core );

		$active_transfer = get_option( $this->configs['option_names']['active_transfer'], false );

		/*
		 * Check the boldgrid_transfer_xfers option.
		 * if the active transfer is not in the list,
		 * or it is marked completed or cancelled, then
		 * reset the active transfer.
		 */
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

	/**
	 * Ajax Process Direct Transfer
	 * 
	 * This is run from the system cron
	 * to process transfers. This is run from both the
	 * sending and receiving sites, and depending on the conditions
	 * in the method, will process accordingly.
	 * 
	 * @since 1.17.0
	 */
	public function ajax_process_direct_transfer() {
		$this->log->add( 'Processing Transfers via System Cron.' );
		if ( ! $this->backup_core->cron->is_valid_call() ) {
			error_log( 'Invalid Cron Request' );
			wp_send_json_error( array( 'error' => true, 'message' => esc_html__( 'Invalid Cron Request', 'boldgrid-backup' ) ) );
			wp_die();
		}

		// If there are incomplete transfers on the receiving site, process them.
		if ( ! empty( $this->rx->get_incomplete_transfers() ) ) {
			$this->rx->process_transfers();
			wp_send_json_success();
			wp_die();
		}

		// If there are incomplete transfers on the sending site, process them.
		if ( ! empty( get_option( $this->configs['option_names']['active_tx'], false ) ) ) {
			$this->tx->process_transfers();
			wp_send_json_success();
			wp_die();
		}

		wp_send_json_success();
		wp_die();
	}
}
