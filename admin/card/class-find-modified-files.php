<?php
/**
 * Find Modified Files class.
 *
 * @link       https://www.boldgrid.com
 * @since      1.13.1
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Card
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 */

namespace Boldgrid\Backup\Admin\Card;

/**
 * Class: Find Modified Files.
 *
 * @since 1.13.1
 */
class Find_Modified_Files extends \Boldgrid\Library\Library\Ui\Card {
	/**
	 * Init.
	 *
	 * @since 1.13.1
	 */
	public function init() {
		$this->id = 'bgbkup_find_modified_files';

		$this->title = esc_html__( 'Find Modified Files', 'boldgrid-backup' );

		$this->icon = '<span class="dashicons dashicons-search"></span>';

		$this->footer = '
			<p>' .
			esc_html__(
				'Search for all files modified within a certain time period. You can also look for other versions of that file within your backups.',
				'boldgrid-backup'
			) .
			'</p>';

		$url = esc_url( 'https://www.boldgrid.com/support/total-upkeep/additional-tools/' );

		$this->links = '
				<a target="_blank" href=" ' . $url . '">' .
				esc_html__( 'Setup Guide' ) . '
				</a>';
	}
}
