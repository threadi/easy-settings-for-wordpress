<?php
/**
 * This file holds an object for a single multi-select field.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Setting;

/**
 * Object to handle a multiselect field for multi-single setting.
 */
class MultiSelect extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'MultiSelect';

	/**
	 * The options for this field.
	 *
	 * @var array<int|string,string>
	 */
	protected array $options = array();

	/**
	 * Sortable marker.
	 *
	 * @var bool
	 */
	private bool $sortable = false;

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

		// get values.
		$values = (array) get_option( $setting->get_name(), array() );

		// show hidden field if this is set to readonly.
		if ( $this->is_readonly() ) {
			?><input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo esc_attr( Helper::get_json( $values ) ); ?>">
			<?php
		}

		?>
		<select multiple="multiple" id="<?php echo esc_attr( $setting->get_name() ); ?>" name="<?php echo esc_attr( $setting->get_name() ); ?>[]" class="widefat <?php echo esc_attr( $this->get_settings_obj()->get_slug() ); ?>-field-width<?php echo $this->is_sortable() ? ' custom-sortable' : ''; ?>" title="<?php echo esc_attr( $this->get_title() ); ?>" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"<?php echo ( $this->is_readonly() ? ' disabled="disabled"' : '' ); ?>>
			<?php
			foreach ( $this->get_options() as $key => $label ) {
				?>
				<option value="<?php echo esc_attr( (string) $key ); ?>"<?php echo ( in_array( (string) $key, $values, true ) ? ' selected="selected"' : '' ); ?>><?php echo esc_html( $label ); ?></option>
				<?php
			}
			?>
		</select>
		<?php

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}

		// add a field for dependent fields if sortable is enabled.
		if ( $this->is_sortable() ) {
			echo '<input type="hidden" name="' . esc_attr( $setting->get_name() ) . '_helper" value="" data-depends="' . esc_attr( $this->get_depend() ) . '">';
		}
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return array<string,mixed>
	 */
	public function default_sanitize_callback( mixed $value ): array {
		return $this->validate_against_options( $value, $this->get_options(), true ); // @phpstan-ignore return.type
	}

	/**
	 * Return the options for this field.
	 *
	 * @return array<int|string,string>
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * Set the options for this field.
	 *
	 * @param array<int|string,string> $options List of options.
	 *
	 * @return void
	 */
	public function set_options( array $options ): void {
		$this->options = $options;
	}

	/**
	 * Return whether this field is sortable.
	 *
	 * @return bool
	 */
	private function is_sortable(): bool {
		return $this->sortable;
	}

	/**
	 * Set sortable.
	 *
	 * @param bool $sortable Whether this is sortable (true) or not (false).
	 *
	 * @return void
	 */
	public function set_sortable( bool $sortable ): void {
		$this->sortable = $sortable;
	}

	/**
	 * Return the REST schema for this field.
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array {
		return array(
			'type'  => 'array',
			'items' => array( 'type' => 'string' ),
		);
	}
}
