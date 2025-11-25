<?php
/**
 * This file holds an object for a single number field.
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
 * Object to handle a number field for single setting.
 */
class Number extends Field_Base {
    /**
     * The type name.
     *
     * @var string
     */
    protected string $type_name = 'Number';

    /**
     * The min value.
     *
     * @var int
     */
    private int $min = 1;

    /**
     * The max value.
     *
     * @var int
     */
    private int $max = PHP_INT_MAX;

    /**
     * The step value.
     *
     * @var int
     */
    private int $step = 1;

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

        // show hidden field if this is set to readonly.
        if( $this->is_readonly() ) {
            ?><input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo absint( get_option( $setting->get_name(), $setting->get_default() ) ); ?>"><?php
        }

        ?>
        <input type="number" id="<?php echo esc_attr( $setting->get_name() ); ?>"
               name="<?php echo esc_attr( $setting->get_name() ); ?>"
               value="<?php echo absint( get_option( $setting->get_name(), $setting->get_default() ) ); ?>"
            <?php
            echo ( $this->is_readonly() ? ' disabled="disabled"' : '' );
            ?>
               min="<?php echo esc_attr( $this->get_min() ); ?>"
               max="<?php echo esc_attr( $this->get_max() ); ?>"
               step="<?php echo esc_attr( $this->get_step() ); ?>"
               class="<?php echo esc_attr( Settings::get_instance()->get_slug() ); ?>-field-width"
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
     * Return the min value.
     *
     * @return int
     */
    public function get_min(): int {
        return $this->min;
    }

    /**
     * Set minimum value for this field.
     *
     * @param int $min The min value.
     *
     * @return void
     */
    public function set_min( int $min ): void {
        $this->min = $min;
    }

    /**
     * Return the max value.
     *
     * @return int
     */
    public function get_max(): int {
        return $this->max;
    }

    /**
     * Set maximum value for this field.
     *
     * @param int $max The max value.
     *
     * @return void
     */
    public function set_max( int $max ): void {
        $this->max = $max;
    }

    /**
     * Return the step value.
     *
     * @return int
     */
    public function get_step(): int {
        return $this->step;
    }

    /**
     * Set step value for this field.
     *
     * @param int $step The step value.
     *
     * @return void
     */
    public function set_step( int $step ): void {
        $this->step = $step;
    }
}
