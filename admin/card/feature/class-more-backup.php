<?php
/**
 * MoreBackup class.
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
class MoreBackup extends \Boldgrid\Library\Library\Ui\Feature {
	/**
	 * Init.
	 *
	 * @since xxx
	 */
	public function init() {
		$this->icon = '<span class="dashicons dashicons-vault"></span>';

		$this->title = esc_html__( 'More BoldGrid Backup Features', 'boldgrid-backup' );

		$this->content = '<p>' . wp_kses(
			sprintf(
				__( 'With BoldGrid Backup Premium, you can store backups on %1$s and %2$s, restore individual files with just a click, and more!', 'boldgrid-backup' ),
				'<span class="bgbkup-remote-logo bgbkup-gdrive-logo" title="Google Drive"></span>',
				'<span class="bgbkup-remote-logo amazon-s3-logo" title="Amazon S3"></span>'
			),
			[ 'span' => [ 'class' => [], 'title' => [] ] ]
		) . '</p>';
	}
}
