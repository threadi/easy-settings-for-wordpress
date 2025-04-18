<?php
/**
 * This file holds an object for a single text field.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;

/**
 * Object to handle a text field for single setting.
 */
class Text extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Text';

	/**
	 * Return the HTML-code to display this field.
	 *
	 * @param array $attr Attributes for this field.
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

		// get the field object.
		$field = $setting->get_field();

		?>
		<input type="text" id="<?php echo esc_attr( $setting->get_name() ); ?>"
				name="<?php echo esc_attr( $setting->get_name() ); ?>"
				value="<?php echo esc_attr( get_option( $setting->get_name(), '' ) ); ?>"
			<?php
			echo ( $field->is_readonly() ? ' disabled="disabled"' : '' );
			?>
				class="<?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width"
				title="<?php echo esc_attr( $field->get_title() ); ?>"
		>
		<?php

		// show optional description for this checkbox.
		if ( ! empty( $field->get_description() ) ) {
			echo '<p>' . wp_kses_post( $field->get_description() ) . '</p>';
		}
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return string
	 */
	public function sanitize_callback( mixed $value ): string {
		// bail if value is null.
		if ( is_null( $value ) ) {
			return '';
		}

		// return the value.
		return $value;
	}
}
