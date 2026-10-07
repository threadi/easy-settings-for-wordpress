<?php
/**
 * Test the date and time fields (Date, DateTime and Time).
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Fields\Date;
use easySettingsForWordPress\Fields\DateTime;
use easySettingsForWordPress\Fields\Time;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the format, the validation and the output of the date and time fields.
 */
class DateTimeFields extends easySettingsForWordPressTest {
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
		$this->settings_obj->set_slug( 'test-slug' );
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
	 * Return the output of a field for a setting with the given stored value.
	 *
	 * @param Field_Base $field The field.
	 * @param string     $name The name of the setting.
	 * @param string     $value The stored value.
	 *
	 * @return string
	 */
	private function get_output( Field_Base $field, string $name, string $value ): string {
		update_option( $name, $value );

		$setting = new Setting( $this->settings_obj );
		$setting->set_name( $name );
		$setting->set_field( $field );

		ob_start();
		$field->display( array( 'setting' => $setting ) );
		return (string) ob_get_clean();
	}

	/**
	 * Valid values are kept in the format of each field.
	 *
	 * @return void
	 */
	public function test_valid_values_are_kept(): void {
		$this->assertSame( '12:00', $this->sanitize( new Time( $this->settings_obj ), '12:00' ) );
		$this->assertSame( '2026-12-24', $this->sanitize( new Date( $this->settings_obj ), '2026-12-24' ) );
		$this->assertSame( '2026-12-24 12:00', $this->sanitize( new DateTime( $this->settings_obj ), '2026-12-24 12:00' ) );
	}

	/**
	 * The value of the HTML input "datetime-local" (with "T") is saved with a space.
	 *
	 * @return void
	 */
	public function test_datetime_accepts_the_input_format(): void {
		$this->assertSame( '2026-12-24 12:00', $this->sanitize( new DateTime( $this->settings_obj ), '2026-12-24T12:00' ) );
	}

	/**
	 * Seconds are removed as long as no step requires them.
	 *
	 * @return void
	 */
	public function test_seconds_are_removed_without_matching_step(): void {
		$this->assertSame( '12:00', $this->sanitize( new Time( $this->settings_obj ), '12:00:30' ) );
		$this->assertSame( '2026-12-24 12:00', $this->sanitize( new DateTime( $this->settings_obj ), '2026-12-24T12:00:30' ) );

		// a step of 15 minutes does not need seconds.
		$field = new Time( $this->settings_obj );
		$field->set_step( 900 );
		$this->assertSame( '12:15', $this->sanitize( $field, '12:15:00' ) );
	}

	/**
	 * Seconds are saved if the step is not a multiple of 60.
	 *
	 * @return void
	 */
	public function test_seconds_are_saved_with_matching_step(): void {
		$field = new Time( $this->settings_obj );
		$field->set_step( 1 );
		$this->assertSame( '12:00:30', $this->sanitize( $field, '12:00:30' ) );
		$this->assertSame( '12:00:00', $this->sanitize( $field, '12:00' ) );

		$field = new DateTime( $this->settings_obj );
		$field->set_step( 30 );
		$this->assertSame( '2026-12-24 12:00:30', $this->sanitize( $field, '2026-12-24T12:00:30' ) );
	}

	/**
	 * Provide values which are not valid for the given field.
	 *
	 * @return iterable
	 */
	public function get_invalid_values(): iterable {
		yield 'time: empty' => array( Time::class, '' );
		yield 'time: null' => array( Time::class, null );
		yield 'time: array' => array( Time::class, array( '12:00' ) );
		yield 'time: integer' => array( Time::class, 1200 );
		yield 'time: hour out of range' => array( Time::class, '24:00' );
		yield 'time: minute out of range' => array( Time::class, '12:60' );
		yield 'time: missing leading zero' => array( Time::class, '9:00' );
		yield 'time: a date' => array( Time::class, '2026-12-24' );
		yield 'time: script' => array( Time::class, '12:00<script>alert(1)</script>' );
		yield 'time: trailing line' => array( Time::class, "12:00\n13:00" );
		yield 'date: not existing day' => array( Date::class, '2026-02-30' );
		yield 'date: no leap year' => array( Date::class, '2026-02-29' );
		yield 'date: month out of range' => array( Date::class, '2026-13-01' );
		yield 'date: other format' => array( Date::class, '24.12.2026' );
		yield 'date: with time' => array( Date::class, '2026-12-24 12:00' );
		yield 'date: script' => array( Date::class, '<script>alert(1)</script>' );
		yield 'datetime: only date' => array( DateTime::class, '2026-12-24' );
		yield 'datetime: only time' => array( DateTime::class, '12:00' );
		yield 'datetime: not existing day' => array( DateTime::class, '2026-02-30 12:00' );
		yield 'datetime: hour out of range' => array( DateTime::class, '2026-12-24 25:00' );
	}

	/**
	 * Invalid values result in an empty string.
	 *
	 * @param string $classname The class name of the field.
	 * @param mixed  $value The invalid value.
	 *
	 * @dataProvider get_invalid_values
	 * @return void
	 */
	public function test_invalid_values_become_empty( string $classname, mixed $value ): void {
		$this->assertSame( '', $this->sanitize( new $classname( $this->settings_obj ), $value ) );
	}

	/**
	 * A leap day is a valid date.
	 *
	 * @return void
	 */
	public function test_leap_day_is_valid(): void {
		$this->assertSame( '2028-02-29', $this->sanitize( new Date( $this->settings_obj ), '2028-02-29' ) );
	}

	/**
	 * Values outside the configured range are set to the nearest limit.
	 *
	 * @return void
	 */
	public function test_values_are_limited_to_range(): void {
		$field = new Time( $this->settings_obj );
		$field->set_min( '08:00' );
		$field->set_max( '18:00' );
		$this->assertSame( '08:00', $this->sanitize( $field, '06:30' ) );
		$this->assertSame( '18:00', $this->sanitize( $field, '23:15' ) );
		$this->assertSame( '12:00', $this->sanitize( $field, '12:00' ) );
		$this->assertSame( '', $this->sanitize( $field, '' ) );

		$field = new Date( $this->settings_obj );
		$field->set_min( '2026-01-01' );
		$field->set_max( '2026-12-31' );
		$this->assertSame( '2026-01-01', $this->sanitize( $field, '2025-12-31' ) );
		$this->assertSame( '2026-12-31', $this->sanitize( $field, '2027-01-01' ) );

		$field = new DateTime( $this->settings_obj );
		$field->set_min( '2026-01-01T08:00' );
		$this->assertSame( '2026-01-01 08:00', $this->sanitize( $field, '2026-01-01 07:59' ) );
		$this->assertSame( '2026-01-01 08:01', $this->sanitize( $field, '2026-01-01 08:01' ) );
	}

	/**
	 * A time range may cross midnight.
	 *
	 * @return void
	 */
	public function test_time_range_may_cross_midnight(): void {
		$field = new Time( $this->settings_obj );
		$field->set_min( '22:00' );
		$field->set_max( '06:00' );

		$this->assertSame( '23:30', $this->sanitize( $field, '23:30' ) );
		$this->assertSame( '05:00', $this->sanitize( $field, '05:00' ) );
		$this->assertSame( '22:00', $this->sanitize( $field, '12:00' ) );
	}

	/**
	 * An invalid limit is ignored.
	 *
	 * @return void
	 */
	public function test_invalid_limits_are_ignored(): void {
		$field = new Time( $this->settings_obj );
		$field->set_min( 'morning' );
		$field->set_max( '25:00' );

		$this->assertSame( '', $field->get_min() );
		$this->assertSame( '', $field->get_max() );
		$this->assertSame( '03:00', $this->sanitize( $field, '03:00' ) );
	}

	/**
	 * A negative step is not possible.
	 *
	 * @return void
	 */
	public function test_step_is_never_negative(): void {
		$field = new Time( $this->settings_obj );
		$field->set_step( -5 );

		$this->assertSame( 0, $field->get_step() );
	}

	/**
	 * Each field uses its matching HTML input with the stored value.
	 *
	 * @return void
	 */
	public function test_output_uses_matching_input(): void {
		$output = $this->get_output( new Time( $this->settings_obj ), 'esfw_test_time', '12:00' );
		$this->assertStringContainsString( 'type="time"', $output );
		$this->assertStringContainsString( 'name="esfw_test_time"', $output );
		$this->assertStringContainsString( 'value="12:00"', $output );

		$output = $this->get_output( new Date( $this->settings_obj ), 'esfw_test_date', '2026-12-24' );
		$this->assertStringContainsString( 'type="date"', $output );
		$this->assertStringContainsString( 'value="2026-12-24"', $output );

		$output = $this->get_output( new DateTime( $this->settings_obj ), 'esfw_test_datetime', '2026-12-24 12:00' );
		$this->assertStringContainsString( 'type="datetime-local"', $output );
		$this->assertStringContainsString( 'value="2026-12-24T12:00"', $output );
	}

	/**
	 * Limits, step and readonly are part of the output, an invalid stored value is not.
	 *
	 * @return void
	 */
	public function test_output_contains_limits_and_no_invalid_value(): void {
		$field = new DateTime( $this->settings_obj );
		$field->set_min( '2026-01-01 08:00' );
		$field->set_max( '2026-12-31 18:00' );
		$field->set_step( 900 );
		$field->set_readonly( true );

		$output = $this->get_output( $field, 'esfw_test_datetime', '"><script>alert(1)</script>' );

		$this->assertStringContainsString( 'min="2026-01-01T08:00"', $output );
		$this->assertStringContainsString( 'max="2026-12-31T18:00"', $output );
		$this->assertStringContainsString( 'step="900"', $output );
		$this->assertStringContainsString( 'readonly="readonly"', $output );
		$this->assertStringContainsString( 'value=""', $output );
		$this->assertStringNotContainsString( '<script', $output );
	}

	/**
	 * Without limits and step the attributes are not part of the output.
	 *
	 * @return void
	 */
	public function test_output_without_limits(): void {
		$output = $this->get_output( new Time( $this->settings_obj ), 'esfw_test_time', '12:00' );

		$this->assertStringNotContainsString( 'min=', $output );
		$this->assertStringNotContainsString( 'max=', $output );
		$this->assertStringNotContainsString( 'step=', $output );
		$this->assertStringNotContainsString( 'readonly', $output );
	}

	/**
	 * The configuration for the DataView contains the settings of the field.
	 *
	 * @return void
	 */
	public function test_dataview_configuration(): void {
		$field = new Time( $this->settings_obj );
		$field->set_title( 'Start time' );
		$field->set_min( '08:00' );
		$field->set_max( '18:00' );
		$field->set_step( 900 );

		$setting = new Setting( $this->settings_obj );
		$setting->set_name( 'esfw_test_time' );
		$setting->set_field( $field );

		$configuration = $setting->get_dataview();

		$this->assertSame( 'esfw_test_time', $configuration['id'] );
		$this->assertSame( 'esfw-datetime', $configuration['type'] );
		$this->assertSame( 'time', $configuration['input_type'] );
		$this->assertSame( '08:00', $configuration['min'] );
		$this->assertSame( '18:00', $configuration['max'] );
		$this->assertSame( 900, $configuration['step'] );
	}

	/**
	 * The fields can be configured via JSON.
	 *
	 * @return void
	 */
	public function test_fields_via_json(): void {
		$config = array(
			'slug'       => 'test-slug',
			'menu_slug'  => 'test-menu',
			'title'      => 'Test Title',
			'menu_title' => 'Test Menu',
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
									'name'    => 'esfw_json_time',
									'type'    => 'string',
									'default' => '12:00',
									'field'   => array(
										'type'  => 'Time',
										'title' => 'Start time',
										'min'   => '08:00',
										'max'   => '18:00',
										'step'  => 900,
									),
								),
							),
						),
					),
				),
			),
		);
		$this->assertTrue( $this->settings_obj->set_json( (string) wp_json_encode( $config ) ) );
		$this->assertFalse( $this->settings_obj->has_errors() );

		$setting = $this->settings_obj->get_setting( 'esfw_json_time' );
		$this->assertInstanceOf( Setting::class, $setting );

		$field = $setting->get_field();
		$this->assertInstanceOf( Time::class, $field );
		$this->assertSame( '08:00', $field->get_min() );
		$this->assertSame( '18:00', $field->get_max() );
		$this->assertSame( 900, $field->get_step() );
	}
}
