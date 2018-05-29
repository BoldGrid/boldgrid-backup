<?php
/**
 * Display the Archive Browser section on the Archive Details page.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.3
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/archive-details
 */

defined( 'WPINC' ) ? : die;

$browser = '
	<div id="zip_browser" class="hidden" data-view-type="file">

		<div class="breadcrumbs" style="padding:8px 10px; background:#f5f5f5; border: 1px solid #e5e5e5; border-bottom:0px; border-top:0px;">
		</div>

		<div class="listing">
			<table>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>';

return $browser;


