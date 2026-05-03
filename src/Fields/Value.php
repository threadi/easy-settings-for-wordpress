<?php
/**
 * This file holds an object for a simple value output.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;

/**
 * Object to handle the output of a value for a setting.
 */
class Value extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Value';

	/**
	 * The value.
	 *
	 * @var mixed|null
	 */
	private mixed $value = null;

	/**
	 * Return the HTML code to display this field.
	 *
	 * @param array<string,mixed> $attr Attributes for this field.
	 *
	 * @return void
	 */
	public function display( array $attr ): void {
		// bail if no attributes are set.
		if ( empty( $attr ) ) {
			return;
		}

		// bail if no setting object is set.
		if ( empty( $attr['setting'] ) ) {
			return;
		}

		// bail if the field is not a "Setting" object.
		if ( ! $attr['setting'] instanceof Setting ) {
			return;
		}

		// get the setting object.
		$setting = $attr['setting'];

		// output the value.
		echo '<div data-depends="' . esc_attr( $this->get_depend() ) . '">' . wp_kses_post( $this->get_the_value( $setting->get_value() ) ) . '</div>';

		// show an optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * Return the value of this setting.
	 *
	 * @param mixed $value The value.
	 *
	 * @return mixed
	 */
	private function get_the_value( mixed $value ): mixed {
		if ( null === $this->value ) {
			return $value;
		}
		return $this->value;
	}

	/**
	 * Set the field value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return void
	 */
	public function set_value( mixed $value ): void {
		$this->value = $value;
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return mixed
	 */
	public function default_sanitize_callback( mixed $value ): mixed {
		return $value;
	}
}
