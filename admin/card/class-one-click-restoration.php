<?php
/**
 * OneClickRestoration class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.12.4
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: OneClickRestoration
 *
 * This class is responsible for rendering the "One Click Restoration" card 
 * on this plugin's Premium Cards Page.
 *
 * @since 1.12.4
 */
class OneClickRestoration extends \Boldgrid\Library\Library\Ui\Premiums {
	/**
	 * Init.
	 *
	 * @since 1.12.4
	 */
	public function init() {
		$this->id = 'bgbkup_one_click_restoration';

		$this->title = esc_html__( 'One Click File Restorations', 'boldgrid-backup' );

		$this->footer = esc_html__( 'Restore Backup files quickly and easily.', 'boldgrid-backup' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$this->icon = '<span class="dashicons dashicons-undo"></span>';

        $this->link = array(
		    "url" => "#",
		    "text" => "Setup Guide"
		);
	}
}
