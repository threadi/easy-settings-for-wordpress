<?php
/**
 * Test the settings in the demo plugin.
 *
 * @package external-files-in-media-library
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object for basic tests for the settings object.
 */
class Demo extends easySettingsForWordPressTest {
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
		$this->settings_obj = easy_settings_for_wordpress_demo_get_settings_object();
	}

	/**
	 * Test if settings are available.
	 *
	 * @return void
	 */
	public function test_settings(): void {
		$settings = $this->settings_obj->get_settings();
		$this->assertIsArray( $settings );
		$this->assertNotEmpty( $settings );
	}

	/**
	 * Test for the default capability.
	 *
	 * @return void
	 */
	public function test_capability(): void {
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
	public function test_slug(): void {
		// test it.
		$slug = $this->settings_obj->get_slug();
		$this->assertIsString( $slug );
		$this->assertNotEmpty( $slug );
		$this->assertEquals( 'demo_settings', $slug );
	}

	/**
	 * Test for the title.
	 *
	 * @return void
	 */
	public function test_title(): void {
		// test it.
		$title = $this->settings_obj->get_title();
		$this->assertIsString( $title );
		$this->assertNotEmpty( $title );
		$this->assertEquals( 'Demo Settings', $title );
	}

	/**
	 * Test for the menu slug.
	 *
	 * @return void
	 */
	public function test_menu_slug(): void {
		// test it.
		$slug = $this->settings_obj->get_menu_slug();
		$this->assertIsString( $slug );
		$this->assertNotEmpty( $slug );
		$this->assertEquals( 'demo-settings', $slug );
	}

	/**
	 * Test for the menu slug.
	 *
	 * @return void
	 */
	public function test_menu_parent_slug(): void {
		// test it.
		$slug = $this->settings_obj->get_menu_parent_slug();
		$this->assertIsString( $slug );
		$this->assertNotEmpty( $slug );
		$this->assertEquals( 'options-general.php', $slug );
	}

	/**
	 * Test for the menu title.
	 *
	 * @return void
	 */
	public function test_menu_title(): void {
		// test it.
		$slug = $this->settings_obj->get_menu_title();
		$this->assertIsString( $slug );
		$this->assertNotEmpty( $slug );
		$this->assertEquals( 'Demo Settings', $slug );
	}

	/**
	 * Test for the menu title.
	 *
	 * @return void
	 */
	public function test_plugin_list_link(): void {
		// test it.
		$show_settings_link_in_plugin_list = $this->settings_obj->is_show_settings_link_in_plugin_list();
		$this->assertIsBool( $show_settings_link_in_plugin_list );
		$this->assertTrue( $show_settings_link_in_plugin_list );
	}

	/**
	 * Test for the settings page.
	 *
	 * @return void
	 */
	public function test_setting_page(): void {
		// test it.
		$settings_page = $this->settings_obj->get_page( 'demo-settings' );
		$this->assertIsObject( $settings_page );
		$this->assertInstanceOf( '\easySettingsForWordPress\Page', $settings_page );
	}
}
