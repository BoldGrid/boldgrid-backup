<?php
/**
 * More_Boldgrid class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.11.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card\Feature;

/**
 * Class: More_Boldgrid
 *
 * @since 1.11.0
 */
class More_Boldgrid extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$this->icon = '<span class="dashicons dashicons-admin-plugins"></span>';

		$this->title = esc_html__( 'More Premium BoldGrid Plugins', 'boldgrid-backup' );

		$this->content = '<p>' . esc_html__( 'Gain access to all BoldGrid premium plugins and services. This includes Post and Page Builder Premium, which offers premium Blocks and native sliders.', 'boldgrid-backup' ) . '</p>';
	}
}
