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
     * The placeholder.
     *
     * @var string
     */
    private string $placeholder = '';

    /**
     * The value.
     *
     * @var string
     */
    private string $value = '';

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
        $value = get_option( $setting->get_name(), '' );

        // use value from object, if set.
        if( ! empty( $this->get_value() ) ) {
            $value = $this->get_value();
        }

        ?>
        <input type="text" id="<?php echo esc_attr( $setting->get_name() ); ?>"
               name="<?php echo esc_attr( $setting->get_name() ); ?>"
               value="<?php echo esc_attr( $value ); ?>"
               placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>"
            <?php
            echo ( $this->is_readonly() ? ' disabled="disabled"' : '' );
            ?>
               class="widefat <?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width"
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

    /**
     * Return the value.
     *
     * @return string
     */
    private function get_value(): string {
        return $this->value;
    }

    /**
     * Set the value.
     *
     * @param mixed $value The value.
     *
     * @return void
     */
    public function set_value( mixed $value ): void {
        $this->value = (string) $value;
    }
}
