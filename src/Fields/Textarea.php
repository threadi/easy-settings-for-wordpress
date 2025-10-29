<?php
/**
 * This file holds an object for a single textarea field.
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
 * Object to handle a textarea field for single setting.
 */
class Textarea extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Textarea';

	/**
	 * The placeholder.
	 *
	 * @var string
	 */
	private string $placeholder = '';

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

		?>
		<textarea id="<?php echo esc_attr( $setting->get_name() ); ?>"
		       name="<?php echo esc_attr( $setting->get_name() ); ?>"
		       placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>"
			<?php
			echo ( $this->is_readonly() ? ' disabled="disabled"' : '' );
			?>
			   class="widefat <?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width"
			   title="<?php echo esc_attr( $this->get_title() ); ?>"
			   data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"
		><?php echo esc_html( get_option( $setting->get_name(), '' ) ); ?></textarea>
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
	public function sanitize_callback( mixed $value ): string {
		// bail if value is null.
		if ( is_null( $value ) ) {
			return '';
		}

		// return the value.
		return $value;
	}

	/**
	 * Return the placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return $this->placeholder;
	}

	/**
	 * Set the placeholder.
	 *
	 * @param string $placeholder The placeholder to use.
	 *
	 * @return void
	 */
	public function set_placeholder( string $placeholder ): void {
		$this->placeholder = $placeholder;
	}
}
