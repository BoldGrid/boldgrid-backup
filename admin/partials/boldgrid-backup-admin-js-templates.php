<?php
/**
 * BoldGrid Backup admin js templates.
 *
 * @summary js templates.
 *
 * @since 1.3.1
 */
?>

<script type="text/html" id="tmpl-boldgrid-backup-sizes">
	<strong>{{data.lang.website_size}}</strong> {{data.disk_space_hr[3]}} ({{{data.messages.disk}}})<br />
	<strong>{{data.lang.database_size}}</strong> {{data.db_size_hr}} ({{{data.messages.db}}})
</script>