<?php
/**
 * This file holds an object for a single select field.
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
 * Object to handle a select field for single setting.
 */
class Select extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Select';

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

		// get value.
		$value = (string) get_option( $setting->get_name(), '' );

		?>
		<select id="<?php echo esc_attr( $setting->get_name() ); ?>" name="<?php echo esc_attr( $setting->get_name() ); ?>" class="widefat <?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width" title="<?php echo esc_attr( $this->get_title() ); ?>" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"<?php echo ( $this->is_readonly() ? ' disabled="disabled"' : '' ); ?>>
			<?php
			foreach ( $this->get_options() as $key => $label ) {
				?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php echo ( $value === (string) $key ? ' selected="selected"' : '' ); ?>><?php echo esc_html( $label ); ?></option>
				<?php
			}
			?>
		</select>
		<?php

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * Return the options for this field.
	 *
	 * @return array
	 */
	public function get_options(): array {
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
