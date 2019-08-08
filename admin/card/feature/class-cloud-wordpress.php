<?php
/**
 * Cloud_WordPress class.
 *
 * @link       https://www.boldgrid.com
 * @since      xxx
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card\Feature;

/**
 * Class: Cloud_WordPress
 *
 * @since xxx
 */
class Cloud_WordPress extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$this->icon = '<span class="dashicons dashicons-cloud"></span>';

		$this->title = __( 'Cloud WordPress', 'boldgrid-backup' );

		$this->content = '<p>' . __( 'Create a fully functional free WordPress instance in the cloud with just a few clicks.', 'boldgrid-backup' ) . '</p>';
	}
}
