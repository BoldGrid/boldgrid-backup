<?php
/**
 * Icon markup must exist before translated strings are loaded.
 *
 * @package Boldgrid_Backup
 */

/**
 * Class: Test_Boldgrid_Backup_Admin_Core_Lang
 */
class Test_Boldgrid_Backup_Admin_Core_Lang extends WP_UnitTestCase {

	/**
	 * Premium reads icon_warning during plugins_loaded, before init.
	 */
	public function test_icon_markup_is_set_without_translated_strings() {
		$core       = new Boldgrid_Backup_Admin_Core();
		$core->lang = array();

		$core->set_icon_markup();

		$this->assertSame(
			'<span class="dashicons dashicons-warning yellow"></span> ',
			$core->lang['icon_warning']
		);
		$this->assertSame(
			'<span class="dashicons dashicons-yes green"></span> ',
			$core->lang['icon_success']
		);
		$this->assertSame( '&#10003;', $core->lang['checkmark'] );
		$this->assertArrayNotHasKey( 'All', $core->lang );
	}

	/**
	 * set_lang() replaces the lang array and must keep the icon markup.
	 */
	public function test_set_lang_keeps_icon_warning() {
		$core = new Boldgrid_Backup_Admin_Core();

		$core->set_lang();

		$this->assertSame(
			'<span class="dashicons dashicons-warning yellow"></span> ',
			$core->lang['icon_warning']
		);
		$this->assertArrayHasKey( 'All', $core->lang );
	}
}
