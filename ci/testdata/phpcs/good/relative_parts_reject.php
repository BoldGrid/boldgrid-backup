<?php
private function safe_relative_parts( $relative_path ) {
	if ( '..' === $relative_path ) {
		return false;
	}
	return $relative_path;
}
