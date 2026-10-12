<?php
/**
 * Tests for the example tab, which is added if no settings exist during init().
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object for tests of the example tab.
 */
class ExampleTab extends easySettingsForWordPressTest {
	/**
	 * Return the tab names of the settings page.
	 *
	 * @param Settings $settings_obj The settings object.
	 *
	 * @return array<int,string>
	 */
	private function get_tab_names( Settings $settings_obj ): array {
		$page = $settings_obj->get_page( $settings_obj->get_menu_slug() );
		return array_values( array_map( fn( $tab ) => $tab->get_name(), $page->get_tabs() ) );
	}

	/**
	 * Without any settings the example tab is shown.
	 *
	 * @return void
	 */
	public function test_example_without_settings(): void {
		$settings_obj = new Settings( self::$plugin_handle );
		$settings_obj->set_slug( 'example-test-1' );
		$settings_obj->set_menu_slug( 'example-test-1-settings' );
		$settings_obj->init();

		$this->assertSame( array( 'easy-settings-for-wordpress_example_tab' ), $this->get_tab_names( $settings_obj ) );
	}

	/**
	 * Settings added after init() remove the example tab and setting again.
	 *
	 * @return void
	 */
	public function test_example_removed_by_late_settings(): void {
		$settings_obj = new Settings( self::$plugin_handle );
		$settings_obj->set_slug( 'example-test-2' );
		$settings_obj->set_menu_slug( 'example-test-2-settings' );
		$settings_obj->init();

		// add own tab and setting after init().
		$page    = $settings_obj->get_page( $settings_obj->get_menu_slug() );
		$tab     = $page->add_tab( 'my-tab', 10 );
		$section = $tab->add_section( 'my-section', 10 );
		$setting = $settings_obj->add_setting( 'example_test_2_option' );
		$setting->set_section( $section );

		$this->assertSame( array( 'my-tab' ), $this->get_tab_names( $settings_obj ) );
		$this->assertNull( $page->get_default_tab() );
		$this->assertFalse( $settings_obj->get_setting( 'easy_settings_for_wordpress_example_setting' ) );
	}
}
