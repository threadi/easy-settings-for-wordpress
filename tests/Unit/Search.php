<?php
/**
 * Tests for the search in the settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Fields\Text;
use easySettingsForWordPress\Json\Parser;
use easySettingsForWordPress\Json\Serializer;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Search as SearchObj;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;
use easySettingsForWordPress\Views\DataView;

/**
 * Object to test the search in the settings.
 */
class Search extends easySettingsForWordPressTest {
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
	 * Set up a settings object with two tabs, a sub-tab and some settings.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->settings_obj = new Settings( self::$plugin_handle );
		$this->settings_obj->set_slug( 'search-test' );
		$this->settings_obj->set_menu_slug( 'search-test' );
		$page = $this->settings_obj->get_page( 'search-test' );
		$this->assertInstanceOf( Page::class, $page );
		$this->page_obj = $page;

		// first tab with a section and three settings.
		$general = $this->page_obj->add_tab( 'general', 10 );
		$general->set_title( 'General' );
		$section = $general->add_section( 'general_main', 10 );
		$section->set_title( 'Export of files' );

		$export = $this->settings_obj->add_setting( 'search_test_export' );
		$export->set_section( $section );
		$field = new Checkbox( $this->settings_obj );
		$field->set_title( 'Enable export' );
		$field->set_description( 'New files are exported to the <strong>external storage</strong>.' );
		$export->set_field( $field );
		$export->set_search_keywords( 'offload, cloud' );

		$folders = $this->settings_obj->add_setting( 'search_test_folders' );
		$folders->set_section( $section );
		$field = new Text( $this->settings_obj );
		$field->set_title( 'Structure of the folders' );
		$field->add_depend( $export, 1 );
		$folders->set_field( $field );

		$interval = $this->settings_obj->add_setting( 'search_test_interval' );
		$interval->set_section( $section );
		$field = new Select( $this->settings_obj );
		$field->set_title( 'Interval' );
		$field->set_options(
			array(
				'hourly' => 'Stündlich',
				'daily'  => 'Täglich',
			)
		);
		$interval->set_field( $field );

		// second tab with a sub-tab.
		$status = $this->page_obj->add_tab( 'status', 20 );
		$status->set_title( 'Status' );
		$logs = $status->add_tab( 'logs', 10 );
		$logs->set_title( 'Logs' );
		$logs->set_search_keywords( array( 'errors', ' debug ' ) );

		// a tab, which only links to another page.
		$link = $this->page_obj->add_tab( 'help', 30 );
		$link->set_title( 'Help' );
		$link->set_url( 'https://example.com/help' );
	}

	/**
	 * Return the entry of the index with the given type and name.
	 *
	 * @param string $type The type.
	 * @param string $name The name.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_entry( string $type, string $name ): ?array {
		foreach ( $this->settings_obj->get_search()->get_index() as $entry ) {
			if ( $type === $entry['type'] && $name === $entry['name'] ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Return the names of the results for the given search.
	 *
	 * @param string            $search The search.
	 * @param array<int,string> $types  The types.
	 *
	 * @return array<int,string>
	 */
	private function get_result_names( string $search, array $types = array() ): array {
		return array_column( $this->settings_obj->get_search()->get_results( $search, 0, $types ), 'name' );
	}

	/**
	 * The search and the command palette are opt-in.
	 *
	 * @return void
	 */
	public function test_opt_in(): void {
		$this->assertFalse( $this->settings_obj->is_search_enabled() );
		$this->assertFalse( $this->settings_obj->is_search_in_command_palette_enabled() );

		// the command palette requires the search.
		$this->settings_obj->set_search_in_command_palette( true );
		$this->assertFalse( $this->settings_obj->is_search_in_command_palette_enabled() );
		$this->settings_obj->set_search( true );
		$this->assertTrue( $this->settings_obj->is_search_enabled() );
		$this->assertTrue( $this->settings_obj->is_search_in_command_palette_enabled() );

		// the search could be changed via filter.
		add_filter( 'search-test_settings_search_enabled', '__return_false' );
		$this->assertFalse( $this->settings_obj->is_search_enabled() );
		$this->assertFalse( $this->settings_obj->is_search_in_command_palette_enabled() );
		remove_filter( 'search-test_settings_search_enabled', '__return_false' );
	}

	/**
	 * The DataView gets the configuration of the search only if it is enabled.
	 *
	 * @return void
	 */
	public function test_dataview_configuration(): void {
		$view   = new DataView( $this->settings_obj );
		$config = $view->get_configuration( 'search-test' );
		$this->assertSame( array( 'enabled' => false ), $config['search'] );

		$this->settings_obj->set_search( true );
		$config = $view->get_configuration( 'search-test' );
		$this->assertTrue( $config['search']['enabled'] );
		$this->assertNotEmpty( $config['search']['index'] );
		$this->assertSame( 'general', $config['search']['tab'] );
		$this->assertSame( 'Search settings', $config['search']['labels']['placeholder'] );
	}

	/**
	 * The index contains the settings with their texts, path, URL and dependencies.
	 *
	 * @return void
	 */
	public function test_index_of_settings(): void {
		$entry = $this->get_entry( 'setting', 'search_test_export' );
		$this->assertNotNull( $entry );
		$this->assertSame( 'Enable export', $entry['title'] );
		$this->assertSame( 'New files are exported to the external storage.', $entry['description'] );
		$this->assertSame( 'offload, cloud', $entry['keywords'] );
		$this->assertSame( array( 'General', 'Export of files' ), $entry['path'] );
		$this->assertSame( 'general', $entry['tab'] );
		$this->assertSame( '', $entry['subtab'] );
		$this->assertSame( 'search_test_export', $entry['row'] );
		$this->assertStringContainsString( 'page=search-test', $entry['url'] );
		$this->assertStringContainsString( 'tab=general', $entry['url'] );

		// the options of a select are searchable.
		$entry = $this->get_entry( 'setting', 'search_test_interval' );
		$this->assertNotNull( $entry );
		$this->assertSame( 'Stündlich, Täglich', $entry['options'] );

		// the dependency is known with its title and whether it is met.
		$entry = $this->get_entry( 'setting', 'search_test_folders' );
		$this->assertNotNull( $entry );
		$this->assertCount( 1, $entry['depends'] );
		$this->assertSame( 'Enable export', $entry['depends'][0]['title'] );
		$this->assertTrue( $entry['depends'][0]['enabled'] );
		$this->assertFalse( $entry['depends'][0]['met'] );
	}

	/**
	 * The index contains the tabs and sub-tabs, but not tabs with own URLs.
	 *
	 * @return void
	 */
	public function test_index_of_tabs(): void {
		$entry = $this->get_entry( 'tab', 'logs' );
		$this->assertNotNull( $entry );
		$this->assertSame( 'Logs', $entry['title'] );
		$this->assertSame( array( 'Status' ), $entry['path'] );
		$this->assertSame( 'errors, debug', $entry['keywords'] );
		$this->assertStringContainsString( 'subtab=logs', $entry['url'] );

		$this->assertNotNull( $this->get_entry( 'tab', 'general' ) );
		$this->assertNull( $this->get_entry( 'tab', 'help' ) );
	}

	/**
	 * Further entries could be added via filter.
	 *
	 * @return void
	 */
	public function test_filter_for_index(): void {
		$callback = static function ( array $entries ): array {
			$entries[] = array(
				'type'  => 'page',
				'name'  => 'tools',
				'title' => 'Tools',
				'url'   => 'https://example.com/tools',
			);
			$entries[] = array(
				'type'  => 'page',
				'name'  => 'without-url',
				'title' => 'Without URL',
			);
			return $entries;
		};
		add_filter( 'search-test_settings_search_index', $callback );
		$this->settings_obj->get_search()->reset_index();

		$entry = $this->get_entry( 'page', 'tools' );
		$this->assertNotNull( $entry );
		$this->assertSame( array(), $entry['path'] );
		$this->assertNull( $this->get_entry( 'page', 'without-url' ) );

		remove_filter( 'search-test_settings_search_index', $callback );
		$this->settings_obj->get_search()->reset_index();
	}

	/**
	 * The search finds settings by title, path, description, options, search terms and name.
	 *
	 * @return void
	 */
	public function test_results(): void {
		// title and prefix, case is ignored.
		$this->assertSame( array( 'search_test_export' ), $this->get_result_names( 'ENABLE exp', array( 'setting' ) ) );

		// description, search terms and options.
		$this->assertSame( array( 'search_test_export' ), $this->get_result_names( 'storage', array( 'setting' ) ) );
		$this->assertSame( array( 'search_test_export' ), $this->get_result_names( 'offload', array( 'setting' ) ) );
		$this->assertSame( array( 'search_test_interval' ), $this->get_result_names( 'stündlich' ) );

		// accents are ignored in both directions.
		$this->assertSame( array( 'search_test_interval' ), $this->get_result_names( 'stundlich' ) );
		$this->assertSame( array( 'search_test_interval' ), $this->get_result_names( 'TÄGL' ) );

		// the name of the setting.
		$this->assertSame( array( 'search_test_folders' ), $this->get_result_names( 'search_test_folders' ) );

		// parts of words with at least 4 characters.
		$this->assertSame( array( 'search_test_folders' ), $this->get_result_names( 'ructure' ) );
		$this->assertSame( array(), $this->get_result_names( 'uct' ) );

		// all words must match (in any part of the entry, e.g. the section and the title).
		$this->assertSame( array(), $this->get_result_names( 'interval storage' ) );
		$this->assertSame( array( 'search_test_interval' ), $this->get_result_names( 'export interval' ) );

		// the tab with its search terms.
		$this->assertSame( array( 'logs' ), $this->get_result_names( 'debug' ) );

		// empty search.
		$this->assertSame( array(), $this->get_result_names( '  ' ) );
	}

	/**
	 * Matches in the title count more than matches in the path, which count more than other matches.
	 *
	 * @return void
	 */
	public function test_order_of_results(): void {
		// "export" is in the title of the first setting and in the section of all three.
		$this->assertSame( array( 'search_test_export', 'search_test_folders', 'search_test_interval' ), $this->get_result_names( 'export', array( 'setting' ) ) );

		// the limit.
		$this->assertCount( 1, $this->settings_obj->get_search()->get_results( 'export', 1 ) );

		// the scores.
		$words = SearchObj::get_words( 'export' );
		$this->assertSame( 3, SearchObj::get_score( (array) $this->get_entry( 'setting', 'search_test_export' ), $words ) );
		$this->assertSame( 2, SearchObj::get_score( (array) $this->get_entry( 'setting', 'search_test_interval' ), $words ) );
	}

	/**
	 * The words are normalized independent of the language.
	 *
	 * @return void
	 */
	public function test_words(): void {
		$this->assertSame( array( 'losch', 'strasse', 'eml_export', '42' ), SearchObj::get_words( 'Lösch-Straße eml_export, 42!' ) );
	}

	/**
	 * The command palette gets the results with labels and URLs, which show and highlight the setting.
	 *
	 * @return void
	 */
	public function test_command_palette_results(): void {
		$results = $this->settings_obj->get_search()->get_command_palette_results( 'enable export' );
		$this->assertCount( 1, $results );
		$this->assertSame( 'Settings: Enable export (General › Export of files)', $results[0]['label'] );
		$this->assertStringContainsString( 'esfw_search=enable', $results[0]['url'] );
		$this->assertStringContainsString( 'esfw_highlight=search_test_export', $results[0]['url'] );

		// tabs link to the tab.
		$results = $this->settings_obj->get_search()->get_command_palette_results( 'debug' );
		$this->assertCount( 1, $results );
		$this->assertStringNotContainsString( 'esfw_search', $results[0]['url'] );
	}

	/**
	 * The REST route for the command palette is only registered if it is enabled.
	 *
	 * @return void
	 */
	public function test_rest_route(): void {
		$search = $this->settings_obj->get_search();

		// not enabled.
		$search->register_rest_routes();
		$this->assertArrayNotHasKey( '/search-test/v1/settings-search', rest_get_server()->get_routes() );

		// enabled.
		$this->settings_obj->set_search( true );
		$this->settings_obj->set_search_in_command_palette( true );
		$search->register_rest_routes();
		$this->assertArrayHasKey( '/search-test/v1/settings-search', rest_get_server()->get_routes() );
	}

	/**
	 * The search and the search terms could be set via JSON and are exported again.
	 *
	 * @return void
	 */
	public function test_json(): void {
		$settings_obj = new Settings( self::$plugin_handle );
		$applied      = Parser::apply(
			$settings_obj,
			array(
				'slug'                      => 'search-json-test',
				'menu_slug'                 => 'search-json-test',
				'search'                    => true,
				'search_in_command_palette' => true,
				'tabs'                      => array(
					array(
						'name'            => 'general',
						'title'           => 'General',
						'search_keywords' => 'main, basics',
						'sections'        => array(
							array(
								'name'     => 'main',
								'settings' => array(
									array(
										'name'            => 'search_json_test_field',
										'search_keywords' => array( 'alpha', 'beta' ),
										'field'           => array(
											'type'  => 'Text',
											'title' => 'A field',
										),
									),
								),
							),
						),
					),
				),
			)
		);
		$this->assertTrue( $applied );

		$this->assertTrue( $settings_obj->is_search_enabled() );
		$this->assertTrue( $settings_obj->is_search_in_command_palette_enabled() );
		$page = $settings_obj->get_page( 'search-json-test' );
		$this->assertInstanceOf( Page::class, $page );
		$tab = $page->get_tab( 'general' );
		$this->assertInstanceOf( Tab::class, $tab );
		$this->assertSame( array( 'main', 'basics' ), $tab->get_search_keywords() );
		$setting = $settings_obj->get_setting( 'search_json_test_field' );
		$this->assertNotFalse( $setting );
		$this->assertSame( array( 'alpha', 'beta' ), $setting->get_search_keywords() );

		// the serializer exports them again.
		$config = Serializer::to_config( $settings_obj );
		$this->assertTrue( $config['search'] );
		$this->assertTrue( $config['search_in_command_palette'] );
	}
}
