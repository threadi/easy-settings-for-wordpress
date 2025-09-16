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
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;

/**
 * Object to handle a multiselect field for multi-single setting.
 */
class MultiSelect extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Multiselect';

	/**
	 * The options for this field.
	 *
	 * @var array
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

		// get values.
		$values = (array) get_option( $setting->get_name(), array() );

		?>
        <select multiple="multiple" id="<?php echo esc_attr( $setting->get_name() ); ?>" name="<?php echo esc_attr( $setting->get_name() ); ?>[]" class="widefat <?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width<?php echo $this->is_sortable() ? ' custom-sortable' : ''; ?>" title="<?php echo esc_attr( $this->get_title() ); ?>" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>">
			<?php
			foreach ( $this->get_options() as $key => $label ) {
				?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php echo ( in_array( (string) $key, $values, true ) ? ' selected="selected"' : '' ); ?>><?php echo esc_html( $label ); ?></option>
				<?php
			}
			?>
		</select>
		<?php

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}

        // add field for dependent fields if sortable is enabled.
        if( $this->is_sortable() ) {
            echo '<input type="hidden" name="' . esc_attr( $setting->get_name() ) . '_helper" value="" data-depends="' . esc_attr( $this->get_depend() ) . '">';
        }
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return mixed
	 */
	public function sanitize_callback( mixed $value ): array {
		// bail if value is null.
		if ( is_null( $value ) ) {
			return array();
		}

		// return the value.
		return (array) $value;
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
}
