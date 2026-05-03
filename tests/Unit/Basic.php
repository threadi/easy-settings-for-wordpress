<?php
/**
 * Basic tests for the settings object.
 *
 * @package external-files-in-media-library
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object for basic tests for the settings object.
 */
class Basic extends easySettingsForWordPressTest {
	/**
	 * The settings object.
	 *
	 * @return void
	 */
	private \easySettingsForWordPress\Settings $settings_obj;

	/**
	 * Set up for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		$this->settings_obj = new \easySettingsForWordPress\Settings( self::$plugin_handle );
	}

	/**
	 * Test for a settings object without any settings.
	 *
	 * @return void
	 */
	public function test_no_settings(): void {
		// test it.
		$settings = $this->settings_obj->get_settings();
		$this->assertIsArray( $settings );
		$this->assertEmpty( $settings );
	}

	/**
	 * Test for the default capability.
	 *
	 * @return void
	 */
	public function test_default_capability(): void {
		// test it.
		$capability = $this->settings_obj->get_capability();
		$this->assertIsString( $capability );
		$this->assertNotEmpty( $capability );
		$this->assertEquals( 'manage_options', $capability );
	}

	/**
	 * Test for the default slug.
	 *
	 * @return void
	 */
	public function test_default_slug(): void {
		// test it.
		$slug = $this->settings_obj->get_slug();
		$this->assertIsString( $slug );
		$this->assertEmpty( $slug );
	}

	/**
	 * Test for the default title.
	 *
	 * @return void
	 */
	public function test_default_title(): void {
		// test it.
		$title = $this->settings_obj->get_title();
		$this->assertIsString( $title );
		$this->assertEmpty( $title );
	}

	/**
	 * Test for the default menu slug.
	 *
	 * @return void
	 */
	public function test_default_menu_slug(): void {
		// test it.
		$slug = $this->settings_obj->get_menu_slug();
		$this->assertIsString( $slug );
		$this->assertEmpty( $slug );
	}

	/**
	 * Test for the default menu slug.
	 *
	 * @return void
	 */
	public function test_default_menu_parent_slug(): void {
		// test it.
		$slug = $this->settings_obj->get_menu_parent_slug();
		$this->assertIsString( $slug );
		$this->assertNotEmpty( $slug );
		$this->assertEquals( 'options-general.php', $slug );
	}

	/**
	 * Test for the default menu title.
	 *
	 * @return void
	 */
	public function test_default_menu_title(): void {
		// test it.
		$slug = $this->settings_obj->get_menu_title();
		$this->assertIsString( $slug );
		$this->assertEmpty( $slug );
	}

	/**
	 * Test for the default menu title.
	 *
	 * @return void
	 */
	public function test_default_plugin_list_link(): void {
		// test it.
		$show_settings_link_in_plugin_list = $this->settings_obj->is_show_settings_link_in_plugin_list();
		$this->assertIsBool( $show_settings_link_in_plugin_list );
		$this->assertFalse( $show_settings_link_in_plugin_list );
	}
}
