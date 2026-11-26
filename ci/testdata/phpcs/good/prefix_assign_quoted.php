<?php
public function update_table_prefix( $new_db_prefix ) {
	$replacement = '$table_prefix = \'' . $new_db_prefix . '\';';
	return $replacement;
}
