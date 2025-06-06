<?php
/**
 * This file holds an object for multiple radio fields.
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
 * Object to handle a checkbox for multiple radio fields.
 */
class Radio extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Radio';

	/**
	 * The options for this field.
	 *
	 * @var array
	 */
	protected array $options = array();

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

		// show each option.
		foreach( $this->get_options() as $key => $title ) {
			?>
			<div>
				<input type="radio" id="<?php echo esc_attr( $setting->get_name() . $key ); ?>"
				       name="<?php echo esc_attr( $setting->get_name() ); ?>"
				       value="<?php echo esc_attr( $key ); ?>"
					<?php
					echo ( $this->is_readonly() ? ' disabled="disabled"' : '' );
					echo ( $key === get_option( $setting->get_name(), '' ) ? ' checked="checked"' : '' );
					?>
					   class="<?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width"
					   title="<?php echo esc_attr( $this->get_title() ); ?>"
				>
				<label for="<?php echo esc_attr( $setting->get_name() . $key ); ?>"><?php echo esc_html( $title ) ; ?></label>
			</div>
			<?php
		}

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
	 * @return mixed
	 */
	public function sanitize_callback( mixed $value ): int {
		// bail if value is null.
		if ( is_null( $value ) ) {
			return 0;
		}

		// return the value.
		return absint( $value );
	}

	/**
	 * Return the options for this field.
	 *
	 * @return array
	 */
	private function get_options(): array {
		return $this->options;
	}

	/**
	 * Set the options for this field.
	 *
	 * @param array $options List of options.
	 *
	 * @return void
	 */
	public function set_options( array $options ): void {
		$this->options = $options;
	}
}
