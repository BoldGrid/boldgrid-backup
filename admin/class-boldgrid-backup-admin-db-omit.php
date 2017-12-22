<?php
/**
 * Database Omit class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Db Omit Class.
 *
 * @since 1.5.3
 */
class Boldgrid_Backup_Admin_Db_Omit {

	/**
	 * The core class object.
	 *
	 * @since  1.5.3
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Default type.
	 *
	 * Usually 'full' or 'custom' backup.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    string
	 */
	public $default_type = 'full';

	/**
	 * Valid types.
	 *
	 * Usually 'full' or 'custom' backup.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $valid_types = array( 'full', 'custom' );

	/**
	 * Constructor.
	 *
	 * @since 1.5.3
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Generate a section for email alerts including information about tables
	 * excluded.
	 *
	 * @since 1.5.4
	 *
	 * @param  array $info
	 * @return string
	 */
	public function email_part( $info ) {
		$body = '';

		// Include database tables excluded.
		if( isset( $info['table_exclude'] ) ) {
			$body .= "\n" . __( 'DATABASE SETTINGS', 'boldgrid-backup' ) . "\n";

			$tables_excluded = empty( $info['table_exclude'] ) ? __( 'None', 'boldgrid-backup' ) : implode( ',', $info['table_exclude'] );
			$body .= sprintf( esc_html__( 'Tables Excluded: %1$s', 'boldgrid-backup' ), $tables_excluded ) . "\n";
		}

		return $body;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.5.4
	 */
	public function enqueue_scripts() {
		$handle = 'boldgrid-backup-admin-table-include';
		wp_register_script( $handle,
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-table-include.js',
			array( 'jquery' ),
			BOLDGRID_BACKUP_VERSION,
			false
		);
		$translation = array(
		);
		wp_localize_script( $handle, 'BoldGridBackupAdminTableInclude', $translation );
		wp_enqueue_script( $handle );

// 		// Enqueue CSS for folder exclude functionality.
// 		wp_enqueue_style(
// 			$handle,
// 			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-folder-exclude.css', array(),
// 			BOLDGRID_BACKUP_VERSION
// 		);
	}

	/**
	 * Get our exluded tables list.
	 *
	 * @since 1.5.3
	 *
	 * @return array
	 */
	public function get_excluded_tables() {

		/*
		 * Determine if the user is doing "backup site now" and they selected to
		 * backup all tables (full backup).
		 */
		$backup_now_full = $this->core->is_backup_now && 'full' === $this->get_post_type();

		if( $this->core->is_archiving_update_protection || $backup_now_full ) {
			$excluded_tables = array();
		} elseif( $this->core->is_backup_now && isset( $_POST['include_tables'] ) ) {
			$excluded_tables = $this->get_from_post();
		} else {
			$settings = $this->core->settings->get_settings();
			$excluded_tables = $settings['exclude_tables'];
		}

		$excluded_tables = is_array( $excluded_tables ) ? $excluded_tables : array();

		return $excluded_tables;
	}

	/**
	 * Get the tables we need to backup.
	 *
	 * We first get all the prefixed tables, and then we remove the tables from
	 * that list that the user specifically set not to backup.
	 *
	 * @since 1.5.3
	 *
	 * @return array
	 */
	public function get_filtered_tables() {
		$prefixed_tables = $this->core->db_get->prefixed();

		// If we're creating a backup for update protection, backup all tables.
		if( $this->core->is_archiving_update_protection ) {
			return $prefixed_tables;
		}

		$exclude_tables = $this->get_excluded_tables();

		foreach( $prefixed_tables as $key => $table ) {
			if( in_array( $table, $exclude_tables ) ) {
				unset( $prefixed_tables[$key] );
			}
		}

		return $prefixed_tables;
	}

	/**
	 * From post, get an array of tables to exlucde.
	 *
	 * We are submitting via post "include_tables", however we use this data to
	 * then calculate "exclude_tables".
	 *
	 * @since 1.5.4
	 *
	 * @return array
	 */
	public function get_from_post() {
		$exclude_tables = array();
		$include_tables = ! empty( $_POST['include_tables'] ) ? $_POST['include_tables'] : array();
		$all_tables = $this->core->db_get->prefixed();

		/*
		 * Loop through every table we have.
		 *
		 * If the table we want to
		 */
		foreach( $all_tables as $table ) {
			if( ! in_array( $table, $include_tables ) ) {
				$exclude_tables[] = $table;
			}
		}

		return $exclude_tables;
	}

	/**
	 * Get value of 'table_inclusion_type' from $_POST.
	 *
	 * @since 1.5.4
	 *
	 * @return string
	 */
	public function get_post_type() {
		$key = 'table_inclusion_type';
		return ! empty( $_POST[$key] ) && in_array( $_POST[$key], $this->valid_types, true ) ? $_POST[$key] : $this->default_type;
	}

	/**
	 * Format an array of tables.
	 *
	 * Creates checkboxes for each table.
	 *
	 * @since 1.5.3
	 *
	 * @return string
	 */
	public function format_prefixed_tables() {
		$exclude_tables = $this->get_excluded_tables();

		$tables = $this->core->db_get->prefixed();
		$return = '';

		foreach( $tables as $table ) {
			$checked = in_array( $table, $exclude_tables ) ? '' : 'checked';
			$return .= sprintf(
				'<div title="%1$s"><input value="%1$s" name="include_tables[]" type="checkbox" %2$s /> %1$s</div>',
				esc_html( $table ),
				$checked
			);
		}

		return $return;
	}

	/**
	 * Determine if we are omitting all tables from the backup.
	 *
	 * @since 1.5.3
	 *
	 * @return bool
	 */
	public function is_omit_all() {
		$exclude_tables = $this->get_excluded_tables();
		$prefixed_tables = $this->core->db_get->prefixed();

		$diff = array_diff( $prefixed_tables, $exclude_tables );

		return empty( $diff );
	}
}
