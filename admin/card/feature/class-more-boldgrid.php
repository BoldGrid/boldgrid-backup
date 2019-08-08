<?php
/**
 * MoreBoldgrid class.
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
 * Class: MoreBackup
 *
 * @since xxx
 */
class MoreBoldgrid extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$this->icon = '<span class="dashicons dashicons-admin-plugins"></span>';

		$this->title = esc_html__( 'More Premium BoldGrid Plugins', 'boldgrid-backup' );

		$this->content = '<p>' . esc_html__( 'Gain access to all BoldGrid Premium Plugins. This includes the Premium Post and Page Builder plugin, which offers Premium Blocks and Native Sliders.', 'boldgrid-backup' ) . '</p>';
	}
}
