<?php
/**
 * Test the ordering of tabs by their position (API and JSON).
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test that tabs and sub-tabs are returned in the order of their position.
 */
class TabPosition extends easySettingsForWordPressTest {
	/**
	 * The settings object under test.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * Set up a fresh settings object for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->settings_obj = new Settings( self::$plugin_handle );
	}

	/**
	 * Return the names of the given tabs in their array order.
	 *
	 * @param array<int,Tab> $tabs List of tabs.
	 *
	 * @return array<int,string>
	 */
	private function get_names( array $tabs ): array {
		return array_values( array_map( static fn( Tab $tab ): string => $tab->get_name(), $tabs ) );
	}

	/**
	 * Return a minimal tab configuration for JSON.
	 *
	 * @param string   $name The tab name.
	 * @param int|null $position The position or null to omit it.
	 *
	 * @return array<string,mixed>
	 */
	private function tab_config( string $name, ?int $position = null ): array {
		$config = array(
			'name'  => $name,
			'title' => ucfirst( $name ),
		);

		if ( ! is_null( $position ) ) {
			$config['position'] = $position;
		}

		return $config;
	}

	/**
	 * Build the settings from a JSON config with the given top-level tabs.
	 *
	 * @param array<int,array<string,mixed>> $tabs The tab configurations.
	 *
	 * @return void
	 */
	private function apply_tabs( array $tabs ): void {
		$config = array(
			'slug'       => 'tab-position-test-slug',
			'menu_slug'  => 'test-menu',
			'title'      => 'Test Title',
			'menu_title' => 'Test Menu',
			'tabs'       => $tabs,
		);

		$this->assertTrue( $this->settings_obj->set_json( (string) wp_json_encode( $config ) ) );
	}

	/**
	 * Tabs added via Page::add_tab() are returned sorted by position, not by insertion order.
	 *
	 * @return void
	 */
	public function test_page_tabs_are_sorted_by_position(): void {
		$page = $this->settings_obj->add_page( 'test-page' );
		$page->add_tab( 'third', 30 );
		$page->add_tab( 'first', 10 );
		$page->add_tab( 'second', 20 );

		$this->assertSame( array( 'first', 'second', 'third' ), $this->get_names( $page->get_tabs() ) );
	}

	/**
	 * Sub-tabs added via Tab::add_tab() are returned sorted by position.
	 *
	 * @return void
	 */
	public function test_sub_tabs_are_sorted_by_position(): void {
		$page = $this->settings_obj->add_page( 'test-page' );
		$tab  = $page->add_tab( 'parent', 10 );
		$tab->add_tab( 'sub_b', 20 );
		$tab->add_tab( 'sub_a', 10 );

		$this->assertSame( array( 'sub_a', 'sub_b' ), $this->get_names( $tab->get_tabs() ) );
	}

	/**
	 * The effective position (after resolving collisions) is stored on the tab object.
	 *
	 * @return void
	 */
	public function test_effective_position_is_stored_on_tab(): void {
		$page   = $this->settings_obj->add_page( 'test-page' );
		$first  = $page->add_tab( 'first', 10 );
		$second = $page->add_tab( 'second', 10 );

		// the second tab could not use 10, so it got the next free index.
		$this->assertSame( 10, $first->get_position() );
		$this->assertNotSame( 10, $second->get_position() );

		// the stored position matches the array key.
		foreach ( $page->get_tabs() as $key => $tab ) {
			$this->assertSame( $key, $tab->get_position() );
		}

		// the tab added first stays in front.
		$this->assertSame( array( 'first', 'second' ), $this->get_names( $page->get_tabs() ) );
	}

	/**
	 * The "position" of top-level tabs in JSON defines their order.
	 *
	 * @return void
	 */
	public function test_json_tab_position_defines_order(): void {
		$this->apply_tabs(
			array(
				$this->tab_config( 'last', 50 ),
				$this->tab_config( 'first', 5 ),
				$this->tab_config( 'middle', 20 ),
			)
		);

		$page = $this->settings_obj->get_page( 'test-menu' );
		$this->assertSame( array( 'first', 'middle', 'last' ), $this->get_names( $page->get_tabs() ) );
		$this->assertSame( 5, $page->get_tab( 'first' )->get_position() );
		$this->assertSame( 50, $page->get_tab( 'last' )->get_position() );
	}

	/**
	 * The "position" of nested sub-tabs in JSON defines their order.
	 *
	 * @return void
	 */
	public function test_json_sub_tab_position_defines_order(): void {
		$parent         = $this->tab_config( 'parent' );
		$parent['tabs'] = array(
			$this->tab_config( 'sub_b', 20 ),
			$this->tab_config( 'sub_a', 10 ),
		);
		$this->apply_tabs( array( $parent ) );

		$tab = $this->settings_obj->get_page( 'test-menu' )->get_tab( 'parent' );
		$this->assertInstanceOf( Tab::class, $tab );
		$this->assertSame( array( 'sub_a', 'sub_b' ), $this->get_names( $tab->get_tabs() ) );
	}

	/**
	 * Without "position" the tabs keep the order of the JSON array.
	 *
	 * @return void
	 */
	public function test_json_without_position_keeps_array_order(): void {
		$this->apply_tabs(
			array(
				$this->tab_config( 'zulu' ),
				$this->tab_config( 'alpha' ),
				$this->tab_config( 'mike' ),
			)
		);

		$page = $this->settings_obj->get_page( 'test-menu' );
		$this->assertSame( array( 'zulu', 'alpha', 'mike' ), $this->get_names( $page->get_tabs() ) );
	}
}
