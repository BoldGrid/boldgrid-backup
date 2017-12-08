<?php
/**
 * BoldGrid Backup admin js templates.
 *
 * @summary js templates.
 *
 * @since 1.3.1
 */

defined( 'WPINC' ) ? : die;
?>

<script type="text/html" id="tmpl-boldgrid-backup-sizes">
	<#
		var dbPercentage = data.db_size / data.db_limit * 100,
			diskPercentage = data.disk_space[3] / data.disk_limit * 100;
	#>
	<table>
		<tr>
			<td><strong>{{data.lang.website_size}}</strong></td>
			<td>
				{{data.disk_space_hr[3]}} {{data.lang.of}} {{data.disk_limit_hr}} ({{{data.messages.disk}}})
				<div class='total-size'>
					<div class='usage size-{{data.messages.diskUsageClass}}' style='width:{{diskPercentage}}%;'></div>
				</div>
			</td>
		</tr>
		<tr>
			<td><strong>{{data.lang.database_size}}</strong></td>
			<td>
				{{data.db_size_hr}} {{data.lang.of}} {{data.db_limit_hr}} ({{{data.messages.db}}})
				<div class='total-size'>
					<div class='usage size-{{data.messages.dbUsageClass}}' style='width:{{dbPercentage}}%;'></div>
				</div>
			</td>
		</tr>
	</table>

	<#
		// If we have a message for the user that they cannot backup their site, display it.
		if( data.messages.notSupported !== undefined ) {
	#>
			<p class='not-supported'><span class="dashicons dashicons-warning red"></span> {{data.messages.notSupported}}</p>
	<#
		}
	#>
</script>