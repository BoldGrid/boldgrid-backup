<?php
public function is_valid_db_prefix( $prefix ) {
	return is_string( $prefix ) && 1 === preg_match( '/\A[A-Za-z0-9_]+\z/', $prefix );
}
