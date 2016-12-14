<?php
/**
 * A file containing email templates
 *
 * @since 1.3.3
 */

return array(
	'fail_size' => array(
		'subject' => __( 'Backup failed for %1$s', 'boldgrid-backup' ),
		'body' => __(
'Hello,

BoldGrid Backup attempted to create a backup of your WordPress site, however failed with the
following message:
%1$s

For further details, please access the BoldGrid Backup Settings page within your WordPress Dashboard.

Best regards,

The BoldGrid Backup plugin', 'boldgrid-backup' ),
	),
)
?>