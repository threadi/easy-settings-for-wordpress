<?php
/**
 * Test the Range field (slider with two handles for a minimum and a maximum value).
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Fields\MultiField;
use easySettingsForWordPress\Fields\Range;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the Range field.
 */
class RangeField extends easySettingsForWordPressTest {
	/**
	 * The settings object used to construct fields.
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
		$this->settings_obj->set_slug( 'range-field-test' );
	}

	/**
	 * Run a field's sanitize callback on a value.
	 *
	 * @param Field_Base $field The field.
	 * @param mixed      $value The value to sanitize.
	 *
	 * @return mixed
	 */
	private function sanitize( Field_Base $field, mixed $value ): mixed {
		return call_user_func( $field->get_sanitize_callback(), $value );
	}

	/**
	 * Return a Range field with the given configuration.
	 *
	 * @param int|float $min     The lowest value.
	 * @param int|float $max     The highest value.
	 * @param int|float $step    The step.
	 * @param int|float $min_gap The minimal gap.
	 *
	 * @return Range
	 */
	private function get_field( int|float $min = 0, int|float $max = 100, int|float $step = 1, int|float $min_gap = 0 ): Range {
		$field = new Range( $this->settings_obj );
		$field->set_min( $min );
		$field->set_max( $max );
		$field->set_step( $step );
		$field->set_min_gap( $min_gap );
		return $field;
	}

	/**
	 * Return the HTML output of a field.
	 *
	 * @param Field_Base $field   The field.
	 * @param Setting    $setting The setting.
	 *
	 * @return string
	 */
	private function get_html( Field_Base $field, Setting $setting ): string {
		ob_start();
		$field->display( array( 'setting' => $setting ) );
		return (string) ob_get_clean();
	}

	/**
	 * The field is known by its type name.
	 *
	 * @return void
	 */
	public function test_type_name(): void {
		$this->assertSame( 'Range', ( new Range( $this->settings_obj ) )->get_type_name() );
	}

	/**
	 * A valid value is kept, with integers for whole bounds and steps.
	 *
	 * @return void
	 */
	public function test_valid_value_is_kept(): void {
		$this->assertSame( array( 'min' => 20, 'max' => 80 ), $this->sanitize( $this->get_field(), array( 'min' => 20, 'max' => 80 ) ) );
		$this->assertSame( array( 'min' => 20, 'max' => 80 ), $this->sanitize( $this->get_field(), array( 'min' => '20', 'max' => '80' ) ) );
	}

	/**
	 * Both values may be equal without a gap.
	 *
	 * @return void
	 */
	public function test_equal_values_without_gap(): void {
		$this->assertSame( array( 'min' => 50, 'max' => 50 ), $this->sanitize( $this->get_field(), array( 'min' => 50, 'max' => 50 ) ) );
	}

	/**
	 * Values in the wrong order are swapped.
	 *
	 * @return void
	 */
	public function test_values_are_swapped(): void {
		$this->assertSame( array( 'min' => 20, 'max' => 80 ), $this->sanitize( $this->get_field(), array( 'min' => 80, 'max' => 20 ) ) );
	}

	/**
	 * Values outside the bounds are clamped, also with negative bounds.
	 *
	 * @return void
	 */
	public function test_values_are_clamped(): void {
		$this->assertSame( array( 'min' => 0, 'max' => 100 ), $this->sanitize( $this->get_field(), array( 'min' => -50, 'max' => 500 ) ) );
		$this->assertSame( array( 'min' => -10, 'max' => -5 ), $this->sanitize( $this->get_field( -10, 10 ), array( 'min' => -99, 'max' => -5 ) ) );
	}

	/**
	 * Values are snapped to the step, counted from min.
	 *
	 * @return void
	 */
	public function test_values_are_snapped_to_step(): void {
		$this->assertSame( array( 'min' => 20, 'max' => 90 ), $this->sanitize( $this->get_field( 0, 100, 10 ), array( 'min' => 23, 'max' => 87 ) ) );
		$this->assertSame( array( 'min' => 5, 'max' => 25 ), $this->sanitize( $this->get_field( 5, 105, 10 ), array( 'min' => 7, 'max' => 24 ) ) );
	}

	/**
	 * Decimal steps result in floats without floating point noise.
	 *
	 * @return void
	 */
	public function test_decimal_values(): void {
		$field = $this->get_field( 0, 10, 0.5 );

		$this->assertSame( array( 'min' => 1.5, 'max' => 7.5 ), $this->sanitize( $field, array( 'min' => 1.26, 'max' => '7.74' ) ) );
		$this->assertSame( array( 'min' => 0.0, 'max' => 10.0 ), $this->sanitize( $field, array( 'min' => 0, 'max' => 10 ) ) );

		$field = $this->get_field( 0, 1, 0.1 );
		$this->assertSame( array( 'min' => 0.3, 'max' => 0.7 ), $this->sanitize( $field, array( 'min' => 0.30000000000000004, 'max' => 0.7 ) ) );
	}

	/**
	 * Decimal bounds result in floats, even with a whole step.
	 *
	 * @return void
	 */
	public function test_decimal_bounds(): void {
		$field = $this->get_field( 0.5, 10.5, 1 );

		$this->assertFalse( $field->uses_integers() );
		$this->assertSame( array( 'min' => 1.5, 'max' => 10.5 ), $this->sanitize( $field, array( 'min' => 1.4, 'max' => 99 ) ) );
	}

	/**
	 * A comma is accepted as decimal separator.
	 *
	 * @return void
	 */
	public function test_comma_as_decimal_separator(): void {
		$this->assertSame( array( 'min' => 2.5, 'max' => 3.5 ), $this->sanitize( $this->get_field( 0, 10, 0.5 ), array( 'min' => '2,5', 'max' => ' 3,5 ' ) ) );
	}

	/**
	 * A step of 0 allows any value.
	 *
	 * @return void
	 */
	public function test_step_zero_allows_any_value(): void {
		$field = $this->get_field( 0, 10, 0 );

		$this->assertNull( $field->get_precision() );
		$this->assertSame( array( 'min' => 3.14159, 'max' => 9.0 ), $this->sanitize( $field, array( 'min' => 3.14159, 'max' => 9 ) ) );
	}

	/**
	 * A negative step is treated as 0.
	 *
	 * @return void
	 */
	public function test_negative_step(): void {
		$field = new Range( $this->settings_obj );
		$field->set_step( -5 );

		$this->assertSame( 0, $field->get_step() );
	}

	/**
	 * The minimal gap moves the maximum up.
	 *
	 * @return void
	 */
	public function test_min_gap_moves_max_up(): void {
		$this->assertSame( array( 'min' => 50, 'max' => 60 ), $this->sanitize( $this->get_field( 0, 100, 1, 10 ), array( 'min' => 50, 'max' => 55 ) ) );
	}

	/**
	 * The minimal gap moves the minimum down at the upper bound.
	 *
	 * @return void
	 */
	public function test_min_gap_moves_min_down_at_upper_bound(): void {
		$this->assertSame( array( 'min' => 90, 'max' => 100 ), $this->sanitize( $this->get_field( 0, 100, 1, 10 ), array( 'min' => 95, 'max' => 98 ) ) );
	}

	/**
	 * A gap which does not fit into the bounds results in the full range.
	 *
	 * @return void
	 */
	public function test_min_gap_larger_than_range(): void {
		$this->assertSame( array( 'min' => 0, 'max' => 100 ), $this->sanitize( $this->get_field( 0, 100, 1, 200 ), array( 'min' => 40, 'max' => 50 ) ) );
	}

	/**
	 * The minimal gap works with decimals.
	 *
	 * @return void
	 */
	public function test_min_gap_with_decimals(): void {
		$this->assertSame( array( 'min' => 1.0, 'max' => 1.5 ), $this->sanitize( $this->get_field( 0, 5, 0.1, 0.5 ), array( 'min' => 1, 'max' => 1.2 ) ) );
	}

	/**
	 * Invalid values result in the full range if no default is set.
	 *
	 * @return void
	 */
	public function test_invalid_values_without_default(): void {
		$field = $this->get_field( 10, 90 );

		foreach ( array( 'foo', 42, null, true, array(), array( 'min' => 'x', 'max' => array( 1 ) ), array( 'min' => INF, 'max' => NAN ) ) as $value ) {
			$this->assertSame( array( 'min' => 10, 'max' => 90 ), $this->sanitize( $field, $value ) );
		}
	}

	/**
	 * Missing or invalid parts are taken from the default of the setting.
	 *
	 * @return void
	 */
	public function test_missing_parts_use_default(): void {
		$field   = $this->get_field();
		$setting = new Setting( $this->settings_obj );
		$setting->set_name( 'esfw_test_range_default' );
		$setting->set_default( array( 'min' => 20, 'max' => 80 ) );
		$setting->set_field( $field );

		$this->assertSame( array( 'min' => 30, 'max' => 80 ), $this->sanitize( $field, array( 'min' => 30 ) ) );
		$this->assertSame( array( 'min' => 20, 'max' => 70 ), $this->sanitize( $field, array( 'min' => 'x', 'max' => 70 ) ) );
		$this->assertSame( array( 'min' => 20, 'max' => 80 ), $this->sanitize( $field, 'foo' ) );
	}

	/**
	 * An invalid default of the setting is ignored or corrected.
	 *
	 * @return void
	 */
	public function test_invalid_default(): void {
		$field = $this->get_field();

		$this->assertSame( array( 'min' => 0, 'max' => 100 ), $field->get_fallback_value( 'foo' ) );
		$this->assertSame( array( 'min' => 0, 'max' => 100 ), $field->get_fallback_value( array( 'min' => 5 ) ) );
		$this->assertSame( array( 'min' => 10, 'max' => 100 ), $field->get_fallback_value( array( 'min' => 500, 'max' => 10 ) ) );
	}

	/**
	 * Misconfigured bounds (min > max) are used in the right order.
	 *
	 * @return void
	 */
	public function test_misconfigured_bounds(): void {
		$field = $this->get_field( 100, 0 );

		$this->assertSame( array( 0, 100 ), $field->get_bounds() );
		$this->assertSame( array( 0, 100 ), array_values( $this->sanitize( $field, 'foo' ) ) );
	}

	/**
	 * Numbers are formatted without exponent and trailing zeros.
	 *
	 * @return void
	 */
	public function test_format_number(): void {
		$field = $this->get_field( 0, 1, 0.01 );

		$this->assertSame( '5', $field->format_number( 5 ) );
		$this->assertSame( '0.5', $field->format_number( 0.5 ) );
		$this->assertSame( '0.01', $field->format_number( 0.01 ) );
		$this->assertSame( '0', $field->format_number( -0.0 ) );
		$this->assertSame( '0.00001', $this->get_field( 0, 1, 0 )->format_number( 0.00001 ) );
	}

	/**
	 * The REST schema describes an object with min and max.
	 *
	 * @return void
	 */
	public function test_rest_schema(): void {
		$schema = ( new Range( $this->settings_obj ) )->get_rest_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertSame( array( 'type' => 'number' ), $schema['properties']['min'] );
		$this->assertSame( array( 'type' => 'number' ), $schema['properties']['max'] );
		$this->assertFalse( $schema['additionalProperties'] );
	}

	/**
	 * The classic view shows both handles with the stored values.
	 *
	 * @return void
	 */
	public function test_display(): void {
		$field   = $this->get_field( 0, 10, 0.5 );
		$setting = $this->settings_obj->add_setting( 'esfw_test_range_display' );
		$setting->set_type( 'array' );
		$setting->set_field( $field );
		update_option( 'esfw_test_range_display', array( 'min' => 2.5, 'max' => 7 ) );

		$html = $this->get_html( $field, $setting );

		$this->assertMatchesRegularExpression( '/name="esfw_test_range_display\[min\]"\s+value="2.5"/', $html );
		$this->assertMatchesRegularExpression( '/name="esfw_test_range_display\[max\]"\s+value="7"/', $html );
		$this->assertStringContainsString( 'id="esfw_test_range_display"', $html );
		$this->assertStringContainsString( 'step="0.5"', $html );
		$this->assertStringContainsString( 'min="0"', $html );
		$this->assertStringContainsString( 'max="10"', $html );
		$this->assertStringContainsString( '--esfw-range-from: 25.0000%; --esfw-range-to: 70.0000%;', $html );
		$this->assertStringNotContainsString( 'type="hidden"', $html );

		delete_option( 'esfw_test_range_display' );
	}

	/**
	 * Without a stored value, the classic view shows the default.
	 *
	 * @return void
	 */
	public function test_display_uses_default(): void {
		$field   = $this->get_field();
		$setting = $this->settings_obj->add_setting( 'esfw_test_range_display_default' );
		$setting->set_type( 'array' );
		$setting->set_default( array( 'min' => 20, 'max' => 80 ) );
		$setting->set_field( $field );

		$html = $this->get_html( $field, $setting );

		$this->assertMatchesRegularExpression( '/\[min\]"\s+value="20"/', $html );
		$this->assertMatchesRegularExpression( '/\[max\]"\s+value="80"/', $html );
	}

	/**
	 * A readonly field submits its value via hidden fields and disables the visible ones.
	 *
	 * @return void
	 */
	public function test_display_readonly(): void {
		$field = $this->get_field();
		$field->set_readonly( true );
		$setting = $this->settings_obj->add_setting( 'esfw_test_range_readonly' );
		$setting->set_type( 'array' );
		$setting->set_field( $field );
		update_option( 'esfw_test_range_readonly', array( 'min' => 10, 'max' => 20 ) );

		$html = $this->get_html( $field, $setting );

		$this->assertStringContainsString( '<input type="hidden" name="esfw_test_range_readonly[min]" value="10">', $html );
		$this->assertStringContainsString( '<input type="hidden" name="esfw_test_range_readonly[max]" value="20">', $html );
		$this->assertStringContainsString( 'disabled="disabled"', $html );
		$this->assertStringContainsString( 'esfw-range is-disabled', $html );

		delete_option( 'esfw_test_range_readonly' );
	}

	/**
	 * Inside a MultiField each entry is sanitized as range.
	 *
	 * @return void
	 */
	public function test_multifield_sanitize(): void {
		$multi = new MultiField( $this->settings_obj );
		$multi->set_field( $this->get_field( 0, 10, 0.5 ) );
		$multi->set_quantity( 2 );

		$this->assertSame(
			array(
				array( 'min' => 1.5, 'max' => 2.0 ),
				array( 'min' => 0.0, 'max' => 10.0 ),
			),
			$this->sanitize( $multi, array( array( 'min' => 2, 'max' => 1.4 ) ) )
		);

		$schema = $multi->get_rest_schema();
		$this->assertSame( 'array', $schema['type'] );
		$this->assertSame( 'object', $schema['items']['type'] );
	}

	/**
	 * Inside a MultiField the classic view uses the name of the entry.
	 *
	 * @return void
	 */
	public function test_multifield_display(): void {
		$multi = new MultiField( $this->settings_obj );
		$multi->set_field( $this->get_field() );
		$multi->set_quantity( 2 );
		$setting = $this->settings_obj->add_setting( 'esfw_test_range_multi' );
		$setting->set_type( 'array' );
		$setting->set_field( $multi );
		update_option( 'esfw_test_range_multi', array( array( 'min' => 10, 'max' => 20 ) ) );

		$html = $this->get_html( $multi, $setting );

		$this->assertMatchesRegularExpression( '/name="esfw_test_range_multi\[0\]\[min\]"\s+value="10"/', $html );
		$this->assertMatchesRegularExpression( '/name="esfw_test_range_multi\[0\]\[max\]"\s+value="20"/', $html );
		$this->assertMatchesRegularExpression( '/name="esfw_test_range_multi\[1\]\[min\]"\s+value="0"/', $html );
		$this->assertMatchesRegularExpression( '/name="esfw_test_range_multi\[1\]\[max\]"\s+value="100"/', $html );

		delete_option( 'esfw_test_range_multi' );
	}

	/**
	 * The configuration for the DataView contains the settings of the field.
	 *
	 * @return void
	 */
	public function test_dataview_configuration(): void {
		$field = $this->get_field( 0, 10, 0.5, 1 );
		$field->set_title( 'Price' );

		$setting = new Setting( $this->settings_obj );
		$setting->set_name( 'esfw_test_range_dataview' );
		$setting->set_default( array( 'min' => 2, 'max' => 8 ) );
		$setting->set_field( $field );

		$configuration = $setting->get_dataview();

		$this->assertSame( 'esfw_test_range_dataview', $configuration['id'] );
		$this->assertSame( 'esfw-range', $configuration['type'] );
		$this->assertSame( 0, $configuration['min'] );
		$this->assertSame( 10, $configuration['max'] );
		$this->assertSame( 0.5, $configuration['step'] );
		$this->assertSame( 1, $configuration['min_gap'] );
		$this->assertSame( 1, $configuration['precision'] );
		$this->assertSame( array( 'min' => 2.0, 'max' => 8.0 ), $configuration['fallback'] );
		$this->assertSame( 'Minimum', $configuration['min_label'] );
		$this->assertSame( 'Maximum', $configuration['max_label'] );
	}

	/**
	 * Inside a MultiField the DataView gets the descriptor of the range.
	 *
	 * @return void
	 */
	public function test_dataview_configuration_in_multifield(): void {
		$multi = new MultiField( $this->settings_obj );
		$multi->set_field( $this->get_field( 0, 50 ) );

		$setting = new Setting( $this->settings_obj );
		$setting->set_name( 'esfw_test_range_dataview_multi' );
		$setting->set_field( $multi );

		$configuration = $setting->get_dataview();

		$this->assertSame( 'esfw-multifield', $configuration['type'] );
		$this->assertSame( 'esfw-range', $configuration['field']['type'] );
		$this->assertSame( array( 'min' => 0, 'max' => 50 ), $configuration['field']['fallback'] );
	}

	/**
	 * The labels could be translated.
	 *
	 * @return void
	 */
	public function test_translations(): void {
		$translations = $this->settings_obj->get_translations();

		$this->assertSame( 'Minimum', $translations['range_minimum'] );
		$this->assertSame( 'Maximum', $translations['range_maximum'] );
	}

	/**
	 * The field can be configured via JSON and is serialized back.
	 *
	 * @return void
	 */
	public function test_json(): void {
		$config = array(
			'slug'      => 'range-json',
			'menu_slug' => 'range-json',
			'title'     => 'Range JSON',
			'tabs'      => array(
				array(
					'name'     => 'general',
					'title'    => 'General',
					'sections' => array(
						array(
							'name'     => 'main',
							'title'    => 'Main',
							'settings' => array(
								array(
									'name'    => 'esfw_test_range_json',
									'type'    => 'array',
									'default' => array(
										'min' => 1.5,
										'max' => 3,
									),
									'field'   => array(
										'type'    => 'Range',
										'title'   => 'A range',
										'min'     => 0,
										'max'     => 5,
										'step'    => 0.5,
										'min_gap' => 1,
									),
								),
							),
						),
					),
				),
			),
		);

		$this->assertTrue( $this->settings_obj->set_json( (string) wp_json_encode( $config ) ) );

		$setting = $this->settings_obj->get_setting( 'esfw_test_range_json' );
		$this->assertInstanceOf( Setting::class, $setting );
		$field = $setting->get_field();
		$this->assertInstanceOf( Range::class, $field );
		$this->assertSame( 0, $field->get_min() );
		$this->assertSame( 5, $field->get_max() );
		$this->assertSame( 0.5, $field->get_step() );
		$this->assertSame( 1, $field->get_min_gap() );

		$out          = $this->settings_obj->to_config();
		$field_config = $out['tabs'][0]['sections'][0]['settings'][0]['field'];
		$this->assertSame( 'Range', $field_config['type'] );
		$this->assertSame( 0, $field_config['min'] );
		$this->assertSame( 5, $field_config['max'] );
		$this->assertSame( 0.5, $field_config['step'] );
		$this->assertSame( 1, $field_config['min_gap'] );
	}
}
