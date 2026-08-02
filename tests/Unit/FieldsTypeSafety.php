<?php
/**
 * Test the numeric type-safety contract of the fields.
 *
 * These tests nail down the behavior of two hardening fixes:
 *  - Number::default_sanitize_callback(): keeps the sign and clamps into the
 *    configured [min, max] range (no more absint() sign mirroring).
 *  - the absint-based fields (Button, Checkbox, File, SelectPostTypeObject):
 *    reject non-scalar input with an explicit 0 (via an is_scalar() guard).
 *
 * They assume those fixes are applied; without them the tests fail by design.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Fields\Button;
use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\File;
use easySettingsForWordPress\Fields\Number;
use easySettingsForWordPress\Fields\SelectPostTypeObject;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the numeric type-safety of the fields.
 */
class FieldsTypeSafety extends easySettingsForWordPressTest {
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
	}

	/**
	 * Run a field's default sanitize callback on a value.
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
	 * Non-scalar input becomes the (default) minimum, not a coerced 1.
	 *
	 * @return void
	 */
	public function test_number_non_scalar_becomes_min(): void {
		$field = new Number( $this->settings_obj ); // default min = 0.

		$this->assertSame( 0, $this->sanitize( $field, array( 'x' => 1 ) ) );
		$this->assertSame( 0, $this->sanitize( $field, array() ) );
	}

	/**
	 * With the default min of 0, a negative value is clamped to 0 (not mirrored).
	 *
	 * @return void
	 */
	public function test_number_clamps_negative_to_default_min(): void {
		$field = new Number( $this->settings_obj );

		$this->assertSame( 0, $this->sanitize( $field, -5 ) );
	}

	/**
	 * A negative value within a negative range is kept as-is.
	 *
	 * @return void
	 */
	public function test_number_keeps_negative_within_range(): void {
		$field = new Number( $this->settings_obj );
		$field->set_min( -100 );

		$this->assertSame( -50, $this->sanitize( $field, -50 ) );
	}

	/**
	 * A value below the configured min is clamped to that min.
	 *
	 * @return void
	 */
	public function test_number_clamps_below_configured_min(): void {
		$field = new Number( $this->settings_obj );
		$field->set_min( -100 );

		$this->assertSame( -100, $this->sanitize( $field, -500 ) );
	}

	/**
	 * A value above the configured max is clamped to that max.
	 *
	 * @return void
	 */
	public function test_number_clamps_above_max(): void {
		$field = new Number( $this->settings_obj );
		$field->set_max( 10 );

		$this->assertSame( 10, $this->sanitize( $field, 999 ) );
	}

	/**
	 * A value inside the range passes through unchanged (also as a numeric string).
	 *
	 * @return void
	 */
	public function test_number_in_range_unchanged(): void {
		$field = new Number( $this->settings_obj );
		$field->set_min( -10 );
		$field->set_max( 10 );

		$this->assertSame( 7, $this->sanitize( $field, 7 ) );
		$this->assertSame( -3, $this->sanitize( $field, '-3' ) );
	}

	/**
	 * Decimal input is truncated to an integer (the field is integer-based).
	 *
	 * @return void
	 */
	public function test_number_truncates_decimal(): void {
		$field = new Number( $this->settings_obj );

		$this->assertSame( 3, $this->sanitize( $field, '3.7' ) );
	}

	/**
	 * The result is always an int, whatever the input.
	 *
	 * @return void
	 */
	public function test_number_result_is_always_int(): void {
		$field = new Number( $this->settings_obj );

		$this->assertIsInt( $this->sanitize( $field, '42' ) );
		$this->assertIsInt( $this->sanitize( $field, null ) );
		$this->assertIsInt( $this->sanitize( $field, array() ) );
	}

	/**
	 * The absint-based fields that must guard against non-scalar input.
	 *
	 * @return iterable<string,array{class-string}>
	 */
	public function scalar_guard_fields(): iterable {
		yield 'Button'               => array( Button::class );
		yield 'Checkbox'             => array( Checkbox::class );
		yield 'File'                 => array( File::class );
		yield 'SelectPostTypeObject' => array( SelectPostTypeObject::class );
	}

	/**
	 * Non-scalar input to an int field yields an explicit 0 (no "Array to int" coercion).
	 *
	 * @param string $classname The field class to test.
	 *
	 * @dataProvider scalar_guard_fields
	 * @return void
	 */
	public function test_int_field_rejects_non_scalar( string $classname ): void {
		$field = new $classname( $this->settings_obj );

		$this->assertSame( 0, $this->sanitize( $field, array( 1, 2 ) ) );
		$this->assertSame( 0, $this->sanitize( $field, array() ) );
	}

	/**
	 * Scalar input to an int field is cast through absint().
	 *
	 * @param string $classname The field class to test.
	 *
	 * @dataProvider scalar_guard_fields
	 * @return void
	 */
	public function test_int_field_casts_scalar( string $classname ): void {
		$field = new $classname( $this->settings_obj );

		$this->assertSame( 7, $this->sanitize( $field, '7' ) );
		$this->assertSame( 7, $this->sanitize( $field, 7 ) );
		$this->assertSame( 0, $this->sanitize( $field, 'abc' ) );
		$this->assertIsInt( $this->sanitize( $field, null ) );
	}
}
