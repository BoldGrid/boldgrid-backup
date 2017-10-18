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
	 * Get a list of all tables based on system prefix.
	 *
	 * @since 1.5.3
	 *
	 * @global $wpdb;
	 *
	 * @return array
	 */
	public function get_prefixed_tables() {
		global $wpdb;

		$sql = sprintf( 'SHOW TABLES LIKE "%1$s%%"', $wpdb->prefix );

		$results = $wpdb->get_results( $sql, ARRAY_N );

		$prefix_tables = array();

		foreach( $results as $k => $v ) {
			$prefix_tables[] = $v[0];
		}

		return $prefix_tables;
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
		$settings = $this->core->settings->get_settings();
		$exclude_tables = $settings['exclude_tables'];

		$tables = $this->get_prefixed_tables();
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
