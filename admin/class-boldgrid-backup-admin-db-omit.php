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
	 * Get our exluded tables list.
	 *
	 * @since 1.5.3
	 *
	 * @return array
	 */
	public function get_excluded_tables() {
		$settings = $this->core->settings->get_settings();

		$excluded_tables = $settings['exclude_tables'];

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

		$exclude_tables = $this->get_excluded_tables();

		foreach( $prefixed_tables as $key => $table ) {
			if( in_array( $table, $exclude_tables ) ) {
				unset( $prefixed_tables[$key] );
			}
		}

		return $prefixed_tables;
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
}
