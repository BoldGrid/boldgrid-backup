<?php
public function update_table_prefix( $new_db_prefix ) {
	$escaped     = str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $new_db_prefix );
	$replacement = '$table_prefix = \'' . $escaped . '\';';
	return $replacement;
}
