<?php
/**
 * File: time-of-day.php
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

$tz_info = $this->core->time->get_timezone_info();

ob_start();
?>

<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Time of Day', 'boldgrid-backup' ); ?>
	</div>
	<div class="bg-box-bottom">
		<select id='tod-h' name='tod_h'>
			<?php
			for ( $x = 1; $x <= 12; $x ++ ) {
				?>
			<option value='<?php echo esc_attr( $x ); ?>'
				<?php
				if ( ! empty( $settings['schedule']['tod_h'] ) && $x == $settings['schedule']['tod_h'] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					echo ' selected';
				}
				?>
			><?php echo esc_html( $x ); ?></option>
				<?php
			}
			?>
		</select>

		<select id='tod-m' name='tod_m'>
			<?php
			for ( $x = 0; $x <= 59; $x ++ ) {
				// Convert $x to a padded string.
				$x = str_pad( $x, 2, '0', STR_PAD_LEFT );
				?>
			<option value='<?php echo esc_attr( $x ); ?>'
				<?php
				if ( ! empty( $settings['schedule']['tod_m'] ) && $x === $settings['schedule']['tod_m'] ) {
					echo ' selected';
				}
				?>
			><?php echo esc_html( $x ); ?></option>
				<?php
			}
			?>
		</select>

		<select id='tod-a' name='tod_a'>
			<option value='AM'
				<?php
				if ( ! isset( $settings['schedule']['tod_a'] ) || 'PM' !== $settings['schedule']['tod_a'] ) {
					echo ' selected';
				}
				?>
				>AM</option>
			<option value='PM'
				<?php
				if ( isset( $settings['schedule']['tod_a'] ) && 'PM' === $settings['schedule']['tod_a'] ) {
					echo ' selected';
				}
				?>
				>PM</option>
		</select>

		<div style="vertical-align:middle;display:inline-block;">
			<?php
			echo $tz_info['markup_timezone'] . ' <em>' . $tz_info['markup_change'] . '</em>'; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			?>
		</div>

		<p class="wp-cron-notice hidden"><em>WP Cron runs on GMT time, which is currently <?php echo esc_html( date( 'l g:i a e' ) ); ?>.</em></p>
	</div>
</div>

<?php
$output = ob_get_contents();
ob_end_clean();
return $output;
