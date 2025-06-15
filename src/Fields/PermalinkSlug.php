<?php
/**
 * This file holds an object for a permalink slug field.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;

/**
 * Object to handle fields for permalink slug setting.
 */
class PermalinkSlug extends Field_Base {
    /**
     * The type name.
     *
     * @var string
     */
    protected string $type_name = 'PermalinkSlug';

    /**
     * The options for this field.
     *
     * @var array
     */
    protected array $options = array();

    /**
     * The list title.
     *
     * @var string
     */
    private string $list_title;

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

        // output.
        ?>
        <input type="text" id="<?php echo esc_attr( $setting->get_name() ); ?>" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo esc_attr( $value ); ?>"
            <?php
            echo ! empty( $attr['placeholder'] ) ? ' placeholder="' . esc_attr( $this->get_placeholder() ) . '"' : '';
            echo ( $this->is_readonly() ? ' disabled="disabled"' : '' );
            ?>
               class="widefat" title="<?php echo esc_attr( $this->get_title() ); ?>"
               data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"
        >

        <div class="available-structure-<?php echo esc_attr( strtolower( $this->get_type_name() ) ); ?> hide-if-no-js">
            <fieldset>
                <legend><?php echo esc_html( $this->get_list_title() ); ?></legend>
                <ul role="list">
                    <?php
                    foreach ( $this->get_options() as $key => $label ) {
                        $placeholder = '%' . esc_html( $key ) . '%';

                        // set active class if placeholder exist in value.
                        $css_class = '';
                        if ( str_contains( $value, $placeholder ) ) {
                            $css_class = ' active';
                        }

                        // output button to add or remove the taxonomy from slug.
                        ?>
                        <li>
                            <button type="button" class="button button-secondary<?php echo esc_attr( $css_class ); ?>" aria-label="<?php echo esc_attr( $label ); ?>" data-target="<?php echo esc_attr( $setting->get_name() ); ?>" data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
                                <?php echo esc_html( $label ); ?>
                            </button>
                        </li>
                        <?php
                    }
                    ?>
                </ul>
            </fieldset>
        </div>

        <?php
        if ( ! empty( $attributes['description'] ) ) {
            echo '<p>' . wp_kses_post( $attributes['description'] ) . '</p>';
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
     * Return the list title.
     *
     * @return string
     */
    private function get_list_title(): string {
        return $this->list_title;
    }

    /**
     * Set the list title.
     *
     * @param string $title
     * @return void
     */
    public function set_list_title( string $title ): void {
        $this->list_title = $title;
    }
}
