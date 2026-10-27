<?php
/**
 * Tests for the field "Cards".
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Fields\Cards;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the field "Cards".
 */
class CardsField extends easySettingsForWordPressTest {
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
		$this->settings_obj->set_slug( 'cards-test' );
	}

	/**
	 * Clean up the stored values.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( 'cards_test' );
		parent::tear_down();
	}

	/**
	 * Return a cards field with a setting and the options used in the tests.
	 *
	 * @return Cards
	 */
	private function get_field(): Cards {
		$field = new Cards( $this->settings_obj );
		$field->set_title( 'Choose a service' );
		$field->set_options(
			array(
				'simple' => 'Simple',
				'basic'  => array(
					'label'       => 'Basic',
					'description' => '<p>Free. <a href="https://example.com/" class="button">Help</a></p><script>alert(1)</script>',
					'icon'        => 'https://example.com/logo.svg',
					'badge'       => 'Active',
				),
				'pro'    => array(
					'label'       => 'Pro',
					'description' => '<p>Needs an API key.</p>',
					'disabled'    => true,
				),
			)
		);
		$setting = $this->settings_obj->add_setting( 'cards_test' );
		$setting->set_type( 'string' );
		$setting->set_default( 'basic' );
		$setting->set_field( $field );
		return $field;
	}

	/**
	 * Return the HTML output of a field.
	 *
	 * @param Cards $field The field.
	 *
	 * @return string
	 */
	private function get_html( Cards $field ): string {
		ob_start();
		$field->display( array( 'setting' => $field->get_setting() ) );
		return (string) ob_get_clean();
	}

	/**
	 * The field could be found by its type name (e.g. for JSON configurations).
	 *
	 * @return void
	 */
	public function test_type_name(): void {
		$field = \easySettingsForWordPress\Helper::get_field_by_type_name( 'Cards', $this->settings_obj );
		$this->assertInstanceOf( Cards::class, $field );
		$this->assertSame( 'Cards', $field->get_type_name() );
	}

	/**
	 * Options as simple label and as array are normalized to cards.
	 *
	 * @return void
	 */
	public function test_get_cards(): void {
		$cards = $this->get_field()->get_cards( 'basic' );

		$this->assertCount( 3, $cards );
		$this->assertSame(
			array(
				'value'       => 'simple',
				'label'       => 'Simple',
				'description' => '',
				'icon'        => '',
				'badge'       => '',
				'disabled'    => false,
			),
			$cards[0]
		);
		$this->assertSame( 'Active', $cards[1]['badge'] );
		$this->assertSame( 'https://example.com/logo.svg', $cards[1]['icon'] );
		$this->assertTrue( $cards[2]['disabled'] );
	}

	/**
	 * A disabled option is not disabled if it is the actual value.
	 *
	 * @return void
	 */
	public function test_disabled_option_is_usable_if_actual_value(): void {
		$cards = $this->get_field()->get_cards( 'pro' );
		$this->assertFalse( $cards[2]['disabled'] );
	}

	/**
	 * The classic view shows each option as card with the actual value checked.
	 *
	 * @return void
	 */
	public function test_classic_output(): void {
		$field = $this->get_field();
		update_option( 'cards_test', 'basic' );

		$html = $this->get_html( $field );

		// the list.
		$this->assertStringContainsString( 'class="esfw-cards esfw-cards-cards_test"', $html );
		$this->assertStringContainsString( '<legend class="screen-reader-text">Choose a service</legend>', $html );

		// the actual value is checked and marked.
		$this->assertMatchesRegularExpression( '/<div class="esfw-card is-selected">\s*<input type="radio" id="cards_testbasic"/', $html );
		$this->assertMatchesRegularExpression( '/value="basic"\s+checked="checked"/', $html );

		// label, icon and badge.
		$this->assertStringContainsString( '<label class="esfw-card__label" for="cards_testbasic">Basic</label>', $html );
		$this->assertStringContainsString( '<img class="esfw-card__icon" src="https://example.com/logo.svg" alt="">', $html );
		$this->assertStringContainsString( '<span class="esfw-card__badge">Active</span>', $html );

		// the description keeps its links, but no scripts.
		$this->assertStringContainsString( '<a href="https://example.com/" class="button">Help</a>', $html );
		$this->assertStringContainsString( 'aria-describedby="cards_testbasic-description"', $html );
		$this->assertStringNotContainsString( '<script>', $html );

		// the disabled option could not be chosen.
		$this->assertMatchesRegularExpression( '/value="pro"\s+disabled="disabled"/', $html );
		$this->assertStringContainsString( '<div class="esfw-card is-disabled">', $html );

		// no columns configured.
		$this->assertStringNotContainsString( 'data-columns', $html );
	}

	/**
	 * The number of columns is set as attribute and CSS variable.
	 *
	 * @return void
	 */
	public function test_columns(): void {
		$field = $this->get_field();
		$field->set_columns( 3 );
		$this->assertSame( 3, $field->get_columns() );
		$this->assertStringContainsString( 'data-columns="3" style="--esfw-cards-columns: 3"', $this->get_html( $field ) );

		// negative values are not allowed.
		$field->set_columns( -1 );
		$this->assertSame( 0, $field->get_columns() );
	}

	/**
	 * A readonly field disables all options and keeps the value in a hidden field.
	 *
	 * @return void
	 */
	public function test_readonly(): void {
		$field = $this->get_field();
		$field->set_readonly( true );
		update_option( 'cards_test', 'simple' );

		$html = $this->get_html( $field );
		$this->assertStringContainsString( '<input type="hidden" name="cards_test" value="simple">', $html );
		$this->assertMatchesRegularExpression( '/value="simple"\s+disabled="disabled"\s+checked="checked"/', $html );
	}

	/**
	 * Only known and not disabled options could be saved.
	 *
	 * @return void
	 */
	public function test_sanitize(): void {
		$field = $this->get_field();
		update_option( 'cards_test', 'basic' );

		// allowed option.
		$this->assertSame( 'simple', $field->default_sanitize_callback( 'simple' ) );

		// unknown option and invalid types.
		$this->assertSame( '', $field->default_sanitize_callback( 'unknown' ) );
		$this->assertSame( '', $field->default_sanitize_callback( array( 'simple' ) ) );

		// disabled option keeps the actual value.
		$this->assertSame( 'basic', $field->default_sanitize_callback( 'pro' ) );

		// a disabled option which is the actual value could be saved again.
		update_option( 'cards_test', 'pro' );
		$this->assertSame( 'pro', $field->default_sanitize_callback( 'pro' ) );
	}

	/**
	 * The DataView gets the cards with filtered descriptions.
	 *
	 * @return void
	 */
	public function test_dataview_descriptor(): void {
		$field = $this->get_field();
		$field->set_columns( 2 );
		update_option( 'cards_test', 'basic' );

		$setting = $field->get_setting();
		$this->assertInstanceOf( Setting::class, $setting );

		$configuration = $setting->get_dataview();
		$this->assertSame( 'cards_test', $configuration['id'] );
		$this->assertSame( 'esfw-cards', $configuration['type'] );
		$this->assertSame( 'Choose a service', $configuration['label'] );
		$this->assertSame( 2, $configuration['columns'] );
		$this->assertCount( 3, $configuration['options'] );

		// values, labels and states.
		$this->assertSame( array( 'simple', 'basic', 'pro' ), array_column( $configuration['options'], 'value' ) );
		$this->assertSame( 'Active', $configuration['options'][1]['badge'] );
		$this->assertTrue( $configuration['options'][2]['disabled'] );

		// the description keeps its HTML, but no scripts.
		$this->assertStringContainsString( '<a href="https://example.com/" class="button">Help</a>', $configuration['options'][1]['description'] );
		$this->assertStringNotContainsString( '<script>', $configuration['options'][1]['description'] );
	}

	/**
	 * The field could be configured via JSON and is serialized back.
	 *
	 * @return void
	 */
	public function test_json(): void {
		$json = array(
			'slug'      => 'cards-json',
			'menu_slug' => 'cards-json',
			'tabs'      => array(
				array(
					'name'     => 'general',
					'sections' => array(
						array(
							'name'     => 'main',
							'settings' => array(
								array(
									'name'  => 'cards_json',
									'type'  => 'string',
									'field' => array(
										'type'    => 'Cards',
										'title'   => 'Cards',
										'columns' => 3,
										'options' => array(
											'a' => 'Option A',
											'b' => array(
												'label'    => 'Option B',
												'badge'    => 'New',
												'disabled' => true,
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);
		$this->assertTrue( $this->settings_obj->set_json( (string) wp_json_encode( $json ) ) );

		$field = $this->settings_obj->get_setting( 'cards_json' )->get_field();
		$this->assertInstanceOf( Cards::class, $field );
		$this->assertSame( 3, $field->get_columns() );
		$this->assertSame( 'New', $field->get_cards()[1]['badge'] );

		// serialize it back.
		$config = $this->settings_obj->to_config();
		$field  = $config['tabs'][0]['sections'][0]['settings'][0]['field'];
		$this->assertSame( 'Cards', $field['type'] );
		$this->assertSame( 3, $field['columns'] );
		$this->assertSame( 'Option A', $field['options']['a'] );
	}
}
