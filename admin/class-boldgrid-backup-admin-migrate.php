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
		$this->log         = new Boldgrid_Backup_Admin_Log( $this->backup_core );
		$this->log->init( 'v2-transfers' );

		$this->util  = new Boldgrid_Backup_Admin_Migrate_Util( $this );
		$this->tx    = new Boldgrid_Backup_Admin_Migrate_Tx( $this );
		$this->rx    = new Boldgrid_Backup_Admin_Migrate_Rx( $this );
	}
}
