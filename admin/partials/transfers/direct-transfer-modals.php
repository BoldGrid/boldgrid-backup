<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modals = array(
	sprintf('<div class="direct-transfer-modal" data-modal-id="restore-site" style="display: none;">
		<div class="modal-panel">
			<div class="modal-header">
				<h2>%1$s</h2>
			</div>
				<div class="direct-transfer-modal-content" class="modal-body">
					<p>%2$s %3$s %4$s %5$s</p>
					<p>%6$s</p>
					<div class="direct-transfer-modal-buttons">
						<button class="button button-primary" id="restore-site-yes">%7$s</button>
						<button class="button button-secondary direct-transfer-modal-close" id="restore-site-no">%8$s</button>
				</div>
			</div>
		</div>',
		esc_html__( 'Restore Site', 'boldgrid-backup' ),
		esc_html__( 'Restoring your site will overwrite existing data.', 'boldgrid-backup' ),
		esc_html__( 'You do not need to stay on this page during the restore process.', 'boldgrid-backup' ),
		esc_html__( 'When the restoration process is complete, you may be logged out.', 'boldgrid-backup' ),
		esc_html__( 'If so, you will need to re-login to your site using the login credentials of the source site.', 'boldgrid-backup' ),
		esc_html__( 'Do you want to proceed?', 'boldgrid-backup' ),
		esc_html__( 'Yes', 'boldgrid-backup' ),
		esc_html__( 'No', 'boldgrid-backup' )
	),
);

return implode( "\n", $modals );
