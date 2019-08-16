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
class Cloud_Wordpress extends \Boldgrid\Library\Library\Ui\Feature { //phpcs:ignore
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$this->icon = '<span class="dashicons dashicons-cloud"></span>';

		$this->title = __( 'Cloud WordPress', 'boldgrid-backup' );

		$this->content = '<p>' . __( 'Create a fully functional free WordPress demo in just a few clicks. Easily design, build, test and share your WordPress website with clients or teams.', 'boldgrid-backup' ) . '</p>';
	}
}
