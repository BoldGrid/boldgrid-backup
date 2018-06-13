<?php
/**
 * File: days-of-week.php
 *
 * Days of the week.
 *
 * @link https://www.boldgrid.com
 * @since 1.6.0
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;

ob_start();
?>

<div class="bg-box schedule-dow">
	<div class="bg-box-top">
		<?php esc_html_e( 'Days of the Week', 'boldgrid-backup' ); ?>
	</div>
	<div class="bg-box-bottom">
		<input id='dow-sunday' type='checkbox' name='dow_sunday' value='1'
		<?php
		if ( ! empty( $settings['schedule']['dow_sunday'] ) ) {
			echo ' checked'; }
		?>
		/><?php esc_html_e( 'Sunday', 'boldgrid-backup' ); ?><br />
		<input id='dow-monday' type='checkbox' name='dow_monday' value='1'
		<?php
		if ( ! empty( $settings['schedule']['dow_monday'] ) ) {
			echo ' checked'; }
		?>
		/><?php esc_html_e( 'Monday', 'boldgrid-backup' ); ?><br />
		<input id='dow-tuesday' type='checkbox' name='dow_tuesday' value='1'
		<?php
		if ( ! empty( $settings['schedule']['dow_tuesday'] ) ) {
			echo ' checked'; }
		?>
		/><?php esc_html_e( 'Tuesday', 'boldgrid-backup' ); ?><br />
		<input id='dow-wednesday' type='checkbox' name='dow_wednesday' value='1'
		<?php
		if ( ! empty( $settings['schedule']['dow_wednesday'] ) ) {
			echo ' checked'; }
		?>
		/><?php esc_html_e( 'Wednesday', 'boldgrid-backup' ); ?><br />
		<input id='dow-thursday' type='checkbox' name='dow_thursday' value='1'
		<?php
		if ( ! empty( $settings['schedule']['dow_thursday'] ) ) {
			echo ' checked'; }
		?>
		/><?php esc_html_e( 'Thursday', 'boldgrid-backup' ); ?><br />
		<input id='dow-friday' type='checkbox' name='dow_friday' value='1'
		<?php
		if ( ! empty( $settings['schedule']['dow_friday'] ) ) {
			echo ' checked'; }
		?>
		/><?php esc_html_e( 'Friday', 'boldgrid-backup' ); ?><br />
		<input id='dow-saturday' type='checkbox' name='dow_saturday' value='1'
		<?php
		if ( ! empty( $settings['schedule']['dow_saturday'] ) ) {
			echo ' checked'; }
		?>
		/><?php esc_html_e( 'Saturday', 'boldgrid-backup' ); ?>

		<br /><br />

		<div class='hidden' id='no-backup-days'>
			<p><span class="dashicons dashicons-warning yellow"></span>
			<?php
				esc_html_e( 'Backup will not occur if no days are selected.', 'boldgrid-backup' );
			?>
			</p>
		</div>

		<?php
			$url = $this->core->configs['urls']['resource_usage'];

			echo '<div id="use-sparingly" class="hidden"><p><span class="dashicons dashicons-warning yellow"></span> ';

			printf(
				wp_kses(
					// translators: 1: HTML markup.
					__(
						'Backups use resources and <a href="%s" target="_blank">must pause your site</a> momentarily. Use sparingly.',
						'boldgrid-backup'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				),
				esc_url( $url )
			);

			echo '</p></div>';
			?>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
