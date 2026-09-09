<?php
/**
 * This file holds an object for a color chooser.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;

/**
 * Object to handle a checkbox for a color chooser.
 */
class Color extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Color';

	/**
	 * The options for this field.
	 *
	 * @var array<string,string|array<string,string>>
	 */
	protected array $options = array();

	/**
	 * Return the HTML-code to display this field.
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

		// bail if field is not a Setting object.
		if ( ! $attr['setting'] instanceof Setting ) {
			return;
		}

		// get the setting object.
		$setting = $attr['setting'];

		// get the value.
		$value = get_option( $setting->get_name(), '' );

		// show hidden field if this is set to readonly.
		if ( $this->is_readonly() ) {
			?><input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo ( 1 === absint( get_option( $setting->get_name(), 0 ) ) ? 1 : 0 ); ?>">
			<?php
		}

		?>
		<input type="color" id="<?php echo esc_attr( $setting->get_name() ); ?>"
				name="<?php echo esc_attr( $setting->get_name() ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
			<?php
			echo ( $this->is_readonly() ? ' disabled="disabled"' : '' );
			?>
				class="<?php echo esc_attr( $this->get_settings_obj()->get_slug() ); ?>-field-width"
				title="<?php echo esc_attr( $this->get_title() ); ?>"
				data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"
		>
		<?php

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return string
	 */
	public function default_sanitize_callback( mixed $value ): string {
		// bail if value is not a scalar.
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		// bail if no color is set (e.g. field left untouched).
		if ( '' === $value ) {
			return '';
		}

		// only accept valid hex colors: #rgb, #rgba, #rrggbb or #rrggbbaa.
		if ( 1 !== preg_match( '/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ) {
			return '';
		}

		// return the lower cased color hex.
		return strtolower( $value );
	}

	/**
	 * Return the REST schema for this field.
	 *
	 * The value is stored as an associative map ( key => 1 ), so it must be
	 * exposed as an object, not as a (sequential) array.
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array {
		return array(
			'type' => 'string',
		);
	}
}
