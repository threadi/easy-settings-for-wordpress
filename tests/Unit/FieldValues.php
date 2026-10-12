<?php
/**
 * Tests for the displayed values of Radio, Number and fields inside a MultiField.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Fields\MultiField;
use easySettingsForWordPress\Fields\Number;
use easySettingsForWordPress\Fields\Radio;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the displayed values of fields.
 */
class FieldValues extends easySettingsForWordPressTest {
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
		$this->settings_obj->set_slug( 'field-values-test' );
	}

	/**
	 * Return the HTML output of a field.
	 *
	 * @param object $field   The field.
	 * @param object $setting The setting.
	 *
	 * @return string
	 */
	private function get_html( object $field, object $setting ): string {
		ob_start();
		$field->display( array( 'setting' => $setting ) );
		return (string) ob_get_clean();
	}

	/**
	 * A radio option with a numeric key is shown as checked.
	 *
	 * @return void
	 */
	public function test_radio_numeric_key_is_checked(): void {
		$field = new Radio( $this->settings_obj );
		$field->set_options( array( '1' => 'One', '2' => 'Two' ) );
		$setting = $this->settings_obj->add_setting( 'field_values_radio' );
		$setting->set_field( $field );
		update_option( 'field_values_radio', '2' );

		$this->assertMatchesRegularExpression( '/value="2"\s+checked="checked"/', $this->get_html( $field, $setting ) );

		delete_option( 'field_values_radio' );
	}

	/**
	 * A negative number and a negative min are shown with their sign.
	 *
	 * @return void
	 */
	public function test_number_shows_negative_values(): void {
		$field = new Number( $this->settings_obj );
		$field->set_min( -10 );
		$setting = $this->settings_obj->add_setting( 'field_values_number' );
		$setting->set_field( $field );
		update_option( 'field_values_number', -5 );

		$html = $this->get_html( $field, $setting );
		$this->assertStringContainsString( 'value="-5"', $html );
		$this->assertStringContainsString( 'min="-10"', $html );

		delete_option( 'field_values_number' );
	}

	/**
	 * Inner fields of a MultiField show the stored entries.
	 *
	 * @return void
	 */
	public function test_multifield_shows_inner_values(): void {
		$inner = new Select( $this->settings_obj );
		$inner->set_options( array( 'a' => 'A', 'b' => 'B' ) );
		$field = new MultiField( $this->settings_obj );
		$field->set_field( $inner );
		$field->set_quantity( 2 );
		$setting = $this->settings_obj->add_setting( 'field_values_multifield' );
		$setting->set_field( $field );
		update_option( 'field_values_multifield', array( 'b', 'a' ) );

		$this->assertMatchesRegularExpression( '/value="b"\s*selected.*value="a"\s*selected/s', $this->get_html( $field, $setting ) );

		delete_option( 'field_values_multifield' );
	}

	/**
	 * Unchecked checkboxes inside a MultiField keep the position of the other entries.
	 *
	 * @return void
	 */
	public function test_multifield_keeps_positions(): void {
		$field = new MultiField( $this->settings_obj );
		$field->set_field( new Checkbox( $this->settings_obj ) );
		$field->set_quantity( 3 );

		$this->assertSame( array( 0, 1, 0 ), $field->default_sanitize_callback( array( 1 => '1' ) ) );
	}
}
