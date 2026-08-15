<?php
/**
 * Test the structural roundtrip: config -> set_json() -> to_config().
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test serializing a settings graph back into a configuration.
 */
class JsonRoundTrip extends easySettingsForWordPressTest {
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
	 * A configuration that stays within the serializer's covered subset.
	 *
	 * @return array<string,mixed>
	 */
	private function roundtrip_config(): array {
		return array(
			'slug'       => 'rt-slug',
			'menu_slug'  => 'rt-menu',
			'title'      => 'RT Title',
			'menu_title' => 'RT Menu',
			'auto_save'  => 'change',
			'lock_form_on_save' => false,
			'tabs'       => array(
				array(
					'name'     => 'general',
					'title'    => 'General',
					'sections' => array(
						array(
							'name'     => 'main',
							'title'    => 'Main',
							'collapsible' => true,
							'collapsed'   => true,
							'settings' => array(
								array(
									'name'    => 's_text',
									'type'    => 'string',
									'default' => 'x',
									'help'    => 'Some help.',
									'reload_on_save' => true,
									'field'   => array(
										'type'        => 'Text',
										'title'       => 'A text',
										'description' => 'A description.',
									),
								),
								array(
									'name'  => 's_select',
									'type'  => 'string',
									'field' => array(
										'type'    => 'Select',
										'title'   => 'A select',
										'options' => array(
											'a' => array( 'label' => 'Option A' ),
											'b' => array( 'label' => 'Option B' ),
										),
									),
								),
							),
						),
					),
					'tabs'     => array(
						array(
							'name'     => 'sub',
							'title'    => 'Sub Tab',
							'sections' => array(
								array(
									'name'     => 'sub_main',
									'settings' => array(
										array(
											'name'  => 's_sub',
											'field' => array( 'type' => 'Text' ),
										),
									),
								),
							),
						),
					),
				),
				array(
					'name'       => 'external',
					'title'      => 'Docs',
					'url'        => 'https://example.com/docs',
					'url_target' => '_blank',
				),
			),
		);
	}

	/**
	 * Find a tab config by its name in a list of tabs.
	 *
	 * @param array<int,array<string,mixed>> $tabs The tabs to search.
	 * @param string                         $name The tab name.
	 *
	 * @return array<string,mixed>|null
	 */
	private function find_by_name( array $tabs, string $name ): ?array {
		foreach ( $tabs as $entry ) {
			if ( isset( $entry['name'] ) && $name === $entry['name'] ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * The serialized config reproduces the essential structure of the input.
	 *
	 * @return void
	 */
	public function test_to_config_reproduces_structure(): void {
		$this->assertTrue( $this->settings_obj->set_json( (string) wp_json_encode( $this->roundtrip_config() ) ) );
		$this->assertFalse( $this->settings_obj->has_errors() );

		$out = $this->settings_obj->to_config();

		// base props.
		$this->assertSame( 'rt-slug', $out['slug'] );
		$this->assertSame( 'rt-menu', $out['menu_slug'] );
		$this->assertSame( 'RT Title', $out['title'] );
		$this->assertSame( 'change', $out['auto_save'] );
		$this->assertFalse( $out['lock_form_on_save'] );

		// general tab.
		$general = $this->find_by_name( $out['tabs'], 'general' );
		$this->assertIsArray( $general );
		$this->assertSame( 'General', $general['title'] );

		// its section + settings.
		$section = $this->find_by_name( $general['sections'], 'main' );
		$this->assertIsArray( $section );
		$this->assertTrue( $section['collapsible'] );
		$this->assertTrue( $section['collapsed'] );

		$text = $this->find_by_name( $section['settings'], 's_text' );
		$this->assertIsArray( $text );
		$this->assertSame( 'string', $text['type'] );
		$this->assertSame( 'x', $text['default'] );
		$this->assertSame( 'Text', $text['field']['type'] );
		$this->assertTrue( $text['reload_on_save'] );

		$select = $this->find_by_name( $section['settings'], 's_select' );
		$this->assertIsArray( $select );
		$this->assertSame( 'Select', $select['field']['type'] );
		$this->assertArrayHasKey( 'a', $select['field']['options'] );
		$this->assertArrayHasKey( 'b', $select['field']['options'] );

		// nested sub-tab.
		$sub = $this->find_by_name( $general['tabs'], 'sub' );
		$this->assertIsArray( $sub );
		$this->assertSame( 'Sub Tab', $sub['title'] );

		// external link tab.
		$external = $this->find_by_name( $out['tabs'], 'external' );
		$this->assertIsArray( $external );
		$this->assertSame( 'https://example.com/docs', $external['url'] );
		$this->assertSame( '_blank', $external['url_target'] );
	}

	/**
	 * Serializing is a fixed point: re-parsing a serialized config and serializing
	 * it again yields the identical structure.
	 *
	 * @return void
	 */
	public function test_roundtrip_is_idempotent(): void {
		// first pass: input -> graph -> config.
		$this->settings_obj->set_json( (string) wp_json_encode( $this->roundtrip_config() ) );
		$config_a = $this->settings_obj->to_config();

		// second pass: config_a -> graph -> config_b, on a fresh object.
		$second = new Settings( self::$plugin_handle );
		$this->assertTrue( $second->set_json( (string) wp_json_encode( $config_a ) ) );
		$this->assertFalse( $second->has_errors() );
		$config_b = $second->to_config();

		// the serialization is stable.
		$this->assertSame( $config_a, $config_b );
	}
}
