<?php
/**
 * Test the sanitize-callback of every field type against unsafe input.
 *
 * Place this file in tests/Unit/FieldsSanitize.php - it will be picked up
 * automatically by phpunit.xml.dist (directory scan of tests/Unit/).
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the sanitize-callback of each field.
 */
class FieldsSanitize extends easySettingsForWordPressTest {

	/**
	 * The test slug.
	 *
	 * @var string
	 */
	private static string $slug = 'test-slug';

	/**
	 * Payload that must never survive sanitizing unchanged.
	 *
	 * @var string
	 */
	private static string $unsafe_string = '<script>alert(1)</script>"><img src=x onerror=alert(1)>';

	/**
	 * Provide one field instance plus a payload matching its expected input shape.
	 *
	 * @return iterable
	 */
	public function get_fields_with_payload(): iterable {
		$settings_obj = new Settings( self::$plugin_handle );
		$settings_obj->set_slug( self::$slug );

		foreach ( Helper::get_field_types() as $classname ) {
			if ( ! class_exists( $classname ) ) {
				continue;
			}

			/**
			 * The field object to test.
			 *
			 * @var Field_Base $obj
			 */
			$obj = new $classname( $settings_obj );

			// pick a payload matching the shape the field actually expects.
			$payload = match ( $obj->get_type_name() ) {
				// numeric fields: value should be forced to an integer.
				'Checkbox', 'Button', 'Number', 'File' => self::$unsafe_string,
				// fields that store an associative array of scalars.
				'Checkboxes', 'MultiSelect' => array( 'key' => self::$unsafe_string ),
				// fields that store a list of nested arrays (rows).
				'FieldTable', 'Table', 'MultiField', 'Files' => array(
					array( 'field' => self::$unsafe_string ),
				),
				// everything else is a plain string field.
				default => self::$unsafe_string,
			};

			yield $obj->get_type_name() => array( $obj, $payload );
		}
	}

	/**
	 * No sanitize-callback may let a <script> tag pass through unchanged -
	 * neither as a plain string nor nested inside an array/row.
	 *
	 * This is a regression test for the "sanitize_callback" just returns the
	 * value unchanged" issue found in several field types.
	 *
	 * @param Field_Base $obj The field to test.
	 * @param mixed      $payload The unsafe payload to sanitize.
	 *
	 * @dataProvider get_fields_with_payload
	 * @return void
	 */
	public function test_sanitize_removes_script_tags( Field_Base $obj, mixed $payload ): void {
		$callback = $obj->get_sanitize_callback();
		$result   = call_user_func( $callback, $payload );

		$this->assert_no_script_tag( $result, $obj->get_type_name() );
	}

	/**
	 * Numeric field types must actually cast to int, not just pass a string through.
	 *
	 * @return iterable
	 */
	public function get_numeric_fields(): iterable {
		$settings_obj = new Settings( self::$plugin_handle );
		$settings_obj->set_slug( self::$slug );

		foreach ( array( 'Checkbox', 'Button', 'Number' ) as $type_name ) {
			$classname = 'easySettingsForWordPress\\Fields\\' . $type_name;
			yield $type_name => array( new $classname( $settings_obj ) );
		}
	}

	/**
	 * @param Field_Base $obj The field to test.
	 *
	 * @dataProvider get_numeric_fields
	 * @return void
	 */
	public function test_sanitize_casts_to_int( Field_Base $obj ): void {
		$callback = $obj->get_sanitize_callback();

		$this->assertSame( 0, call_user_func( $callback, self::$unsafe_string ) );
		$this->assertSame( 5, call_user_func( $callback, '5' ) );
		$this->assertIsInt( call_user_func( $callback, null ) );
	}

	/**
	 * Recursively assert that no <script> tag survived the sanitizing.
	 *
	 * Every call path must end in a real assertion, even for empty arrays or
	 * scalar non-string results (int/bool/null) - otherwise PHPUnit marks the
	 * test as "risky" (no assertions performed) instead of green or red.
	 *
	 * @param mixed  $value The (recursively) sanitized value.
	 * @param string $type_name Name of the tested field type, used for a readable failure message.
	 *
	 * @return void
	 */
	private function assert_no_script_tag( mixed $value, string $type_name ): void {
		if ( is_array( $value ) ) {
			// An empty array is a valid, safe result (e.g., MultiSelect on null) - still assert something.
			if ( array() === $value ) {
				$this->assertSame( array(), $value );
				return;
			}

			foreach ( $value as $item ) {
				$this->assert_no_script_tag( $item, $type_name );
			}
			return;
		}

		// Cast scalars (int/float/bool/null) to string too, so e.g. absint()-results
		// are still covered by a real assertion instead of being silently skipped.
		$string_value = is_scalar( $value ) ? (string) $value : '';

		$this->assertStringNotContainsString(
			'<script',
			$string_value,
			'Field type "' . $type_name . '" did not strip a <script> tag during sanitizing - default_sanitize_callback() likely just returns the raw value.'
		);
	}
}
