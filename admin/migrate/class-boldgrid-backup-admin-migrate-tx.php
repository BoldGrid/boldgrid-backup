<?php
/**
 * File: class-boldgrid-backup-admin-migrate-tx.php
 * 
 * The main class for the transmitting ( tx ) of the Transfer process.
 * 
 * @link https://www.boldgrid.com
 * @since 1.17.0
 * @package Boldgrid_Transfer
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Transfer_Tx
 * 
 * The main class for the transmitting ( tx ) of the Transfer process.
 *
 * @since 1.17.0
 */
class Boldgrid_Backup_Admin_Migrate_Tx {
	/**
	 * Boldgrid_Transfer Core
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate
	 * 
	 * @since 1.17.0
	 */
	public $migrate_core;

	/**
	 * Util
	 * 
	 * @var Boldgrid_Backup_Admin_Migrate_Util
	 * 
	 * @since 1.17.0
	 */
	public $util;

	/**
	 * Boldgrid_Transfer_Admin constructor.
	 * 
	 * @param Boldgrid_Backup_Admin_Migrate $migrate_core
	 * 
	 * @since 1.17.0
	 */
	public function __construct( $migrate_core ) {
		$this->migrate_core  = $migrate_core;
		$this->util          = $this->migrate_core->util;
		$this->rest          = new Boldgrid_Backup_Admin_Migrate_Tx_Rest( $migrate_core );
		$this->add_hooks();
	}

	/**
	 * Add hooks
	 * 
	 * @since 1.17.0
	 */
	public function add_hooks() {
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );
		add_action( 'boldgrid_transfer_db_dump_cron', array( $this->rest, 'generate_db_dump' ) );
	}
}
