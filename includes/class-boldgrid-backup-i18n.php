<?php
/**
 * File: class-boldgrid-backup-i18n.php
 *
 * Define the internationalization functionality.
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link https://www.boldgrid.com
 * @since 1.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/includes
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Boldgrid_Backup_I18n
 *
 * Define the internationalization functionality.
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since 1.0
 */
class Boldgrid_Backup_I18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'boldgrid-backup', false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
