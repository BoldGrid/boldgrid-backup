<?php
/**
 * File: test-class-boldgrid-backup-admin-utility.php
 *
 * @link https://www.boldgrid.com
 * @since     SINCEVERSION
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/tests/admin
 * @copyright  BoldGrid
 * @version    $Id$
 * @author     BoldGrid <support@boldgrid.com>
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Utility
 *
 * @since SINCEVERSION
 */
class Test_Boldgrid_Backup_Admin_Utility extends WP_UnitTestCase {
	/**
	 * Test the database find and replace utility.
	 *
	 * @since SINCEVERSION
	 */
	public function test_db_find_replace() {
		$post_title   = 'cats and dogs';
		$post_content = 'Joe has 10 pet cats.';

		$new_post_title   = 'dogs and dogs';
		$new_post_content = 'Brad has 10 pet cats.';

		$title_find    = 'cats';
		$title_replace = 'dogs';

		$content_find    = 'Joe';
		$content_replace = 'Brad';

		$post_id = wp_insert_post( array(
			'post_title'   => $post_title,
			'post_content' => $post_content,
		) );

		// Ensure we created a post.
		$post = get_post( $post_id );
		$this->assertTrue( $post_title === $post->post_title );
		$this->assertTrue( $post_content === $post->post_content );

		// Find and replace on the title only.
		Boldgrid_Backup_Admin_Utility::db_find_replace( 'posts', 'post_title', $title_find, $title_replace );
		clean_post_cache( $post_id );
		$post = get_post( $post_id );

		// Ensure the post title updated.
		$this->assertTrue( $new_post_title === $post->post_title );

		// Ensure the post content was not touched.
		$this->assertTrue( $post_content === $post->post_content );

		// Find and replace on the content only.
		Boldgrid_Backup_Admin_Utility::db_find_replace( 'posts', 'post_content', $content_find, $content_replace );
		clean_post_cache( $post_id );
		$post = get_post( $post_id );

		// Ensure the post title was not touched.
		$this->assertTrue( $new_post_title === $post->post_title );

		// Ensure the post content was updated.
		$this->assertTrue( $new_post_content === $post->post_content );
	}

	/**
	 * Test the option find and replace function.
	 *
	 * @since SINCEVERSION
	 */
	public function test_option_find_replace() {
		// Save our option and ensure it's working as expected.
		update_option(
			'dow',
			array(
				'days_of_the_week' => array(
					1 => 'Monday',
					2 => 'Tuesday',
				),
			)
		);
		$option = get_option( 'dow' );
		$this->assertTrue( 'Monday' === $option['days_of_the_week'][1] );

		// Replace an entire value.
		Boldgrid_Backup_Admin_Utility::option_find_replace( 'Monday', 'Funday' );
		$option = get_option( 'dow' );
		$this->assertTrue( 'Funday' === $option['days_of_the_week'][1] );

		// Replace part of a value.
		Boldgrid_Backup_Admin_Utility::option_find_replace( 'day', 'daay' );
		$option = get_option( 'dow' );
		$this->assertTrue( 'Fundaay' === $option['days_of_the_week'][1] );
		$this->assertTrue( 'Tuesdaay' === $option['days_of_the_week'][2] );

		// Save our option and ensure it's working.
		$option_value = new stdClass();
		$option_value->days_of_the_week = new stdClass();
		$option_value->days_of_the_week->one = 'Monday';
		$option_value->days_of_the_week->two = 'Tuesday';
		update_option( 'dow', $option_value );
		$option = get_option( 'dow' );
		$this->assertTrue( 'Monday' === $option->days_of_the_week->one );

		// Replace an entire value.
		Boldgrid_Backup_Admin_Utility::option_find_replace( 'Monday', 'Funday' );
		$option = get_option( 'dow' );
		$this->assertTrue( 'Funday' === $option->days_of_the_week->one );

		// Replace part of a value.
		Boldgrid_Backup_Admin_Utility::option_find_replace( 'day', 'daay' );
		$option = get_option( 'dow' );
		$this->assertTrue( 'Fundaay' === $option->days_of_the_week->one );
		$this->assertTrue( 'Tuesdaay' === $option->days_of_the_week->two );
	}
}
