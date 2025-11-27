<?php
/**
 * This file holds an object for multiple checkbox fields.
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
 * Object to handle a checkbox for multiple checkbox fields.
 */
class Checkboxes extends Field_Base {
    /**
     * The type name.
     *
     * @var string
     */
    protected string $type_name = 'Checkboxes';

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

        // get the values.
        $values = get_option( $setting->get_name(), array() );

        // show each option.
        foreach( $this->get_options() as $key => $settings ) {
            $title = '';
            $description = '';
            // if settings is a string, then it is the title.
            if( is_string( $settings ) ) {
                $title = $settings;
            }
            elseif( is_array( $settings ) ) {
                // otherwise it is an array and the title is the label.
                $title = $settings['label'];

                // get the description.
                $description = isset( $settings['description'] ) ? $settings['description'] : '';
            }

            // show hidden field if this is set to readonly.
            if( $this->is_readonly() ) {
                ?><input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>[<?php echo esc_attr( $key ); ?>]; ?>" value="<?php echo ( isset( $values[$key] ) ? 1 : 0 ); ?>"><?php
            }

            ?>
            <div>
                <input type="checkbox" id="<?php echo esc_attr( $setting->get_name() . $key ); ?>"
                       name="<?php echo esc_attr( $setting->get_name() ); ?>[<?php echo esc_attr( $key ); ?>]"
                       value="1"
                    <?php
                    echo ( $this->is_readonly() ? ' disabled="disabled"' : '' );
                    echo ( isset( $values[$key] ) ? ' checked="checked"' : '' );
                    ?>
                       class="<?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width"
                       title="<?php echo esc_attr( $this->get_title() ); ?>"
                >
                <label for="<?php echo esc_attr( $setting->get_name() . $key ); ?>"><?php echo wp_kses_post( $title ) ; ?></label>
                <?php if ( ! empty( $description ) ) { ?>
                    <p class="description"><?php echo wp_kses_post( $description ); ?></p>
                <?php } ?>
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
    public function sanitize_callback( mixed $value ): array {
        // bail if value is not an array.
        if ( ! is_array( $value ) ) {
            return array();
        }

        // return the value.
        return $value;
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
