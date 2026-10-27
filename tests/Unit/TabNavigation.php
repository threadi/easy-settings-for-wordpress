<?php
/**
 * Tests for the visibility of the tab navigation (no navigation for a single tab).
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Page;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;
use easySettingsForWordPress\Views\DataView;

/**
 * Object to test the visibility of the tab navigation.
 */
class TabNavigation extends easySettingsForWordPressTest {
	/**
	 * The settings object under test.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * The settings page.
	 *
	 * @var Page
	 */
	private Page $page_obj;

	/**
	 * Set up a fresh settings object with a page for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->settings_obj = new Settings( self::$plugin_handle );
		$this->settings_obj->set_slug( 'tab-navigation-test' );
		$this->settings_obj->set_menu_slug( 'tab-navigation-test' );
		$page = $this->settings_obj->get_page( 'tab-navigation-test' );
		$this->assertInstanceOf( Page::class, $page );
		$this->page_obj = $page;
	}

	/**
	 * Return whether the navigation for the tabs of the page is visible.
	 *
	 * @return bool
	 */
	private function is_visible(): bool {
		return $this->settings_obj->is_tab_navigation_visible( $this->page_obj->get_tabs() );
	}

	/**
	 * A single tab is shown without navigation by default.
	 *
	 * @return void
	 */
	public function test_single_tab_has_no_navigation(): void {
		$this->page_obj->add_tab( 'general', 10 );

		$this->assertFalse( $this->settings_obj->is_show_single_tab() );
		$this->assertFalse( $this->is_visible() );
	}

	/**
	 * The navigation could be forced for a single tab.
	 *
	 * @return void
	 */
	public function test_single_tab_navigation_could_be_forced(): void {
		$this->page_obj->add_tab( 'general', 10 );
		$this->settings_obj->set_show_single_tab( true );

		$this->assertTrue( $this->settings_obj->is_show_single_tab() );
		$this->assertTrue( $this->is_visible() );
	}

	/**
	 * More than one tab is shown with navigation.
	 *
	 * @return void
	 */
	public function test_multiple_tabs_have_navigation(): void {
		$this->page_obj->add_tab( 'general', 10 );
		$this->page_obj->add_tab( 'advanced', 20 );

		$this->assertTrue( $this->is_visible() );
	}

	/**
	 * Tabs which are hidden or shown as menu item are not counted.
	 *
	 * @return void
	 */
	public function test_hidden_tabs_are_not_counted(): void {
		$this->page_obj->add_tab( 'general', 10 );
		$hidden = $this->page_obj->add_tab( 'hidden', 20 );
		$hidden->set_tab_class( 'hidden' );
		$menu = $this->page_obj->add_tab( 'menu', 30 );
		$menu->set_show_in_menu( true );

		$this->assertFalse( $this->is_visible() );
		$this->assertSame( array( 'general' ), array_map( static fn( $tab ) => $tab->get_name(), $this->settings_obj->get_visible_tabs( $this->page_obj->get_tabs() ) ) );
	}

	/**
	 * A single tab with sub-tabs keeps its navigation.
	 *
	 * @return void
	 */
	public function test_single_tab_with_sub_tabs_has_navigation(): void {
		$tab = $this->page_obj->add_tab( 'general', 10 );
		$tab->add_tab( 'sub', 10 );

		$this->assertTrue( $this->is_visible() );
	}

	/**
	 * The decision could be changed via filter.
	 *
	 * @return void
	 */
	public function test_filter(): void {
		$this->page_obj->add_tab( 'general', 10 );

		add_filter( 'tab-navigation-test_settings_show_tab_navigation', '__return_true' );
		$this->assertTrue( $this->is_visible() );
		remove_filter( 'tab-navigation-test_settings_show_tab_navigation', '__return_true' );

		$this->assertFalse( $this->is_visible() );
	}

	/**
	 * The DataView gets whether to show the navigation and the name of the single tab.
	 *
	 * @return void
	 */
	public function test_dataview_configuration(): void {
		$this->page_obj->add_tab( 'general', 10 );
		$view = new DataView( $this->settings_obj );

		$configuration = $view->get_configuration( 'tab-navigation-test' );
		$this->assertFalse( $configuration['tab_navigation'] );
		$this->assertSame( 'general', $configuration['single_tab'] );

		// forced navigation.
		$this->settings_obj->set_show_single_tab( true );
		$configuration = $view->get_configuration( 'tab-navigation-test' );
		$this->assertTrue( $configuration['tab_navigation'] );
		$this->assertSame( '', $configuration['single_tab'] );
	}

	/**
	 * The setting could be configured via JSON and is serialized back.
	 *
	 * @return void
	 */
	public function test_json(): void {
		$config = array(
			'slug'            => 'tab-navigation-json',
			'menu_slug'       => 'tab-navigation-json',
			'title'           => 'Title',
			'show_single_tab' => true,
			'tabs'            => array(
				array(
					'name'  => 'general',
					'title' => 'General',
				),
			),
		);
		$settings_obj = new Settings( self::$plugin_handle );
		$this->assertTrue( $settings_obj->set_json( (string) wp_json_encode( $config ) ) );
		$this->assertTrue( $settings_obj->is_show_single_tab() );
		$this->assertTrue( $settings_obj->to_config()['show_single_tab'] );
	}
}
