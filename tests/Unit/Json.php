<?php
/**
 * Test the JSON handling (Settings::set_json / set_json_by_path and Json\Parser).
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;
use WP_Error;

/**
 * Object to test building a settings graph from a JSON configuration.
 */
class Json extends easySettingsForWordPressTest {
	/**
	 * The test slug.
	 *
	 * @var string
	 */
	private static string $slug = 'json-test-slug';

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
	 * Return a minimal, valid configuration as a PHP array.
	 *
	 * @return array<string,mixed>
	 */
	private function base_config(): array {
		return array(
			'slug'       => self::$slug,
			'menu_slug'  => 'test-menu',
			'title'      => 'Test Title',
			'menu_title' => 'Test Menu',
			'auto_save'  => 'change',
			'tabs'       => array(
				array(
					'name'     => 'general',
					'title'    => 'General',
					'sections' => array(
						array(
							'name'     => 'main',
							'title'    => 'Main',
							'settings' => array(
								array(
									'name'    => 'my_text',
									'type'    => 'string',
									'default' => 'hello',
									'help'    => 'Some help.',
									'field'   => array(
										'type'        => 'Text',
										'title'       => 'My Text',
										'description' => 'A text field.',
									),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Encode a config array and hand it to the settings object.
	 *
	 * @param array<string,mixed> $config The configuration.
	 *
	 * @return bool The result of set_json().
	 */
	private function apply( array $config ): bool {
		return $this->settings_obj->set_json( (string) wp_json_encode( $config ) );
	}

	/**
	 * Assert that the settings object collected a specific error code.
	 *
	 * @param string $code The expected error code.
	 *
	 * @return void
	 */
	private function assert_has_error_code( string $code ): void {
		$this->assertTrue( $this->settings_obj->has_errors() );

		$errors = $this->settings_obj->get_errors();
		$this->assertInstanceOf( WP_Error::class, $errors );
		$this->assertContains( $code, $errors->get_error_codes() );
	}

	/**
	 * A malformed JSON string is rejected with the "json_invalid" error.
	 *
	 * @return void
	 */
	public function test_invalid_json_string_adds_error(): void {
		$result = $this->settings_obj->set_json( '{ this is : not json' );

		$this->assertFalse( $result );
		$this->assert_has_error_code( 'json_invalid' );
	}

	/**
	 * Valid JSON that does not decode into an array (e.g. a bare number) is rejected.
	 *
	 * @return void
	 */
	public function test_non_array_json_adds_error(): void {
		$result = $this->settings_obj->set_json( '42' );

		$this->assertFalse( $result );
		$this->assert_has_error_code( 'json_invalid' );
	}

	/**
	 * A configuration missing a required top-level key is rejected.
	 *
	 * @return void
	 */
	public function test_missing_required_key_adds_error(): void {
		$config = $this->base_config();
		unset( $config['tabs'] );

		$result = $this->apply( $config );

		$this->assertFalse( $result );
		$this->assert_has_error_code( 'json_missing_key' );
	}

	/**
	 * A minimal valid configuration builds without errors and sets the base properties.
	 *
	 * @return void
	 */
	public function test_valid_minimal_config(): void {
		$result = $this->apply( $this->base_config() );

		$this->assertTrue( $result );
		$this->assertFalse( $this->settings_obj->has_errors() );

		// base properties.
		$this->assertSame( self::$slug, $this->settings_obj->get_slug() );
		$this->assertSame( 'Test Title', $this->settings_obj->get_title() );
		$this->assertSame( 'Test Menu', $this->settings_obj->get_menu_title() );
		$this->assertSame( 'change', $this->settings_obj->get_auto_save() );

		// the page is auto-created from menu_slug.
		$page = $this->settings_obj->get_page( 'test-menu' );
		$this->assertInstanceOf( Page::class, $page );

		// the tab exists on the page.
		$tab = $page->get_tab( 'general' );
		$this->assertInstanceOf( Tab::class, $tab );
		$this->assertSame( 'General', $tab->get_title() );

		// the section exists in the tab.
		$this->assertCount( 1, $tab->get_sections() );

		// the setting exists on the settings object.
		$this->assertInstanceOf( Setting::class, $this->settings_obj->get_setting( 'my_text' ) );
	}

	/**
	 * A setting and its field carry over all of their configured properties.
	 *
	 * @return void
	 */
	public function test_setting_and_field_are_built(): void {
		$this->apply( $this->base_config() );

		$setting = $this->settings_obj->get_setting( 'my_text' );
		$this->assertInstanceOf( Setting::class, $setting );
		$this->assertSame( 'string', $setting->get_type() );
		$this->assertSame( 'hello', $setting->get_default() );
		$this->assertSame( 'Some help.', $setting->get_help() );
		$this->assertInstanceOf( Section::class, $setting->get_section() );

		$field = $setting->get_field();
		$this->assertInstanceOf( Field_Base::class, $field );
		$this->assertSame( 'Text', $field->get_type_name() );
		$this->assertSame( 'My Text', $field->get_title() );
		$this->assertSame( 'A text field.', $field->get_description() );
	}

	/**
	 * Options of an option-based field (Select) are parsed onto the field.
	 *
	 * @return void
	 */
	public function test_select_options_are_parsed(): void {
		$config = $this->base_config();

		$config['tabs'][0]['sections'][0]['settings'][0]['field'] = array(
			'type'    => 'Select',
			'title'   => 'Choose',
			'options' => array(
				'a' => array( 'label' => 'Option A' ),
				'b' => array( 'label' => 'Option B' ),
			),
		);

		$this->apply( $config );

		$field = $this->settings_obj->get_setting( 'my_text' )->get_field();
		$this->assertInstanceOf( Select::class, $field );

		$options = $field->get_options();
		$this->assertArrayHasKey( 'a', $options );
		$this->assertArrayHasKey( 'b', $options );
	}

	/**
	 * Nested sub-tabs are attached to their parent tab.
	 *
	 * @return void
	 */
	public function test_nested_subtabs_are_built(): void {
		$config = $this->base_config();

		$config['tabs'][0]['tabs'] = array(
			array(
				'name'     => 'sub',
				'title'    => 'Sub Tab',
				'sections' => array(
					array(
						'name'     => 'sub_main',
						'settings' => array(
							array(
								'name'  => 'sub_setting',
								'field' => array( 'type' => 'Text' ),
							),
						),
					),
				),
			),
		);

		$this->apply( $config );

		$parent = $this->settings_obj->get_page( 'test-menu' )->get_tab( 'general' );
		$this->assertInstanceOf( Tab::class, $parent );

		$sub_tabs = $parent->get_tabs();
		$this->assertCount( 1, $sub_tabs );

		$sub = array_values( $sub_tabs )[0];
		$this->assertInstanceOf( Tab::class, $sub );
		$this->assertSame( 'Sub Tab', $sub->get_title() );
	}

	/**
	 * A tab's URL and its target are parsed (used for external link tabs).
	 *
	 * @return void
	 */
	public function test_tab_url_and_target(): void {
		$config = $this->base_config();

		$config['tabs'][0]['url']        = 'https://example.com/docs';
		$config['tabs'][0]['url_target'] = '_blank';

		$this->apply( $config );

		$tab = $this->settings_obj->get_page( 'test-menu' )->get_tab( 'general' );
		$this->assertInstanceOf( Tab::class, $tab );
		$this->assertSame( 'https://example.com/docs', $tab->get_url() );
		$this->assertSame( '_blank', $tab->get_url_target() );
	}

	/**
	 * The "hide_save" flag on a tab is parsed.
	 *
	 * @return void
	 */
	public function test_tab_hide_save(): void {
		$config = $this->base_config();

		$config['tabs'][0]['hide_save'] = true;

		$this->apply( $config );

		$tab = $this->settings_obj->get_page( 'test-menu' )->get_tab( 'general' );
		$this->assertInstanceOf( Tab::class, $tab );
		$this->assertTrue( $tab->is_save_hidden() );
	}

	/**
	 * A "depends" reference to a setting defined later in the JSON resolves
	 * without an error (forward reference / deferred resolution).
	 *
	 * @return void
	 */
	public function test_depends_forward_reference_resolves(): void {
		$config = $this->base_config();

		$config['tabs'][0]['sections'][0]['settings'] = array(
			array(
				'name'  => 'depending',
				'field' => array(
					'type'    => 'Text',
					'depends' => array( 'controller' => '1' ),
				),
			),
			array(
				'name'  => 'controller',
				'field' => array( 'type' => 'Checkbox' ),
			),
		);

		$result = $this->apply( $config );

		$this->assertTrue( $result );
		$this->assertFalse( $this->settings_obj->has_errors() );
	}

	/**
	 * A "depends" reference to an unknown setting is reported as an error.
	 *
	 * @return void
	 */
	public function test_depends_unknown_setting_adds_error(): void {
		$config = $this->base_config();

		$config['tabs'][0]['sections'][0]['settings'][0]['field']['depends'] = array( 'does_not_exist' => '1' );

		$this->apply( $config );

		$this->assert_has_error_code( 'json_depends_unknown_setting' );
	}

	/**
	 * An unknown field type is reported as an error.
	 *
	 * @return void
	 */
	public function test_unknown_field_type_adds_error(): void {
		$config = $this->base_config();

		$config['tabs'][0]['sections'][0]['settings'][0]['field']['type'] = 'NoSuchField';

		$this->apply( $config );

		$this->assert_has_error_code( 'json_field_type_unknown' );
	}

	/**
	 * A setting without a name is reported as an error.
	 *
	 * @return void
	 */
	public function test_setting_without_name_adds_error(): void {
		$config = $this->base_config();

		$config['tabs'][0]['sections'][0]['settings'][0] = array( 'type' => 'string' );

		$this->apply( $config );

		$this->assert_has_error_code( 'json_setting_name_missing' );
	}

	/**
	 * A "default_tab" that matches no tab is reported as an error.
	 *
	 * @return void
	 */
	public function test_default_tab_unknown_adds_error(): void {
		$config = $this->base_config();

		$config['default_tab'] = 'nonexistent';

		$this->apply( $config );

		$this->assert_has_error_code( 'json_default_tab_unknown' );
	}

	/**
	 * A missing file path is reported via set_json_by_path().
	 *
	 * @return void
	 */
	public function test_set_json_by_path_missing_file(): void {
		$result = $this->settings_obj->set_json_by_path( '/this/does/not/exist-esfw.json' );

		$this->assertFalse( $result );
		$this->assert_has_error_code( 'json_not_readable' );
	}

	/**
	 * A readable file with valid JSON is parsed via set_json_by_path().
	 *
	 * @return void
	 */
	public function test_set_json_by_path_reads_valid_file(): void {
		$path = (string) tempnam( sys_get_temp_dir(), 'esfw' );
		file_put_contents( $path, (string) wp_json_encode( $this->base_config() ) );

		$result = $this->settings_obj->set_json_by_path( $path );

		if ( file_exists( $path ) ) {
			unlink( $path );
		}

		$this->assertTrue( $result );
		$this->assertInstanceOf( Setting::class, $this->settings_obj->get_setting( 'my_text' ) );
	}
}
