<?php
/**
 * This file represents a single setting in the plugin settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single setting.
 */
class Setting {
    /**
     * The internal name of this setting.
     *
     * @var string
     */
    private string $name = '';

    /**
     * The section this setting belongs to.
     *
     * @var ?Section
     */
    private ?Section $section = null;

    /**
     * The field object.
     *
     * @var ?Field_Base
     */
    private ?Field_Base $field = null;

    /**
     * The type.
     *
     * @var string
     */
    private string $type = 'string';

    /**
     * The default value.
     *
     * @var mixed
     */
    private mixed $default = null;

    /**
     * Show in REST API.
     *
     * @var bool
     */
    private bool $show_in_rest = false;

    /**
     * Read callback.
     *
     * @var array|string
     */
    private array|string $read_callback = array();

    /**
     * Save callback.
     *
     * @var array|string
     */
    private array|string $save_callback = array();

    /**
     * Export prevent marker.
     *
     * @var bool
     */
    private bool $prevent_export = false;

    /**
     * Autoload.
     *
     * @var bool
     */
    private bool $autoload = true;

    /**
     * Help text for this setting.
     *
     * @var string
     */
    private string $help = '';

    /**
     * The custom vars.
     *
     * @var array<string,mixed>
     */
    private array $vars = array();

    /**
     * Do not register.
     *
     * @var bool
     */
    private bool $do_not_register = false;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Return the internal name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Set internal name.
     *
     * @param string $name The name to use.
     *
     * @return void
     */
    public function set_name( string $name ): void {
        $this->name = $name;
    }

    /**
     * Return the field object.
     *
     * @return Field_Base|null
     */
    public function get_field(): ?Field_Base {
        return $this->field;
    }

    /**
     * Set the field to this setting.
     *
     * If $field is array it should contain:
     * - type => one of: Checkbox, MultiSelect, Number, Select (required)
     * - title => the title to use
     * - description => the description to show
     *
     * @param array|Field_Base $field The field to use or its configuration as array.
     *
     * @return false|Field_Base
     */
    public function set_field( array|Field_Base $field ): false|Field_Base {
        // initialize the field object value.
        $field_obj = false;

        // if value is an array, create the field object first.
        if ( is_array( $field ) ) {
            // bail if array does not contain a type setting.
            if ( empty( $field['type'] ) ) {
                return false;
            }

            // get the object for the given field type.
            $field_obj = Helper::get_field_by_type_name( $field['type'] );

            // bail if no object could be found.
            if ( ! $field_obj instanceof Field_Base ) {
                return false;
            }

            // set configuration.
            $field_obj->set_title( ! empty( $field['title'] ) ? $field['title'] : '' );
            $field_obj->set_description( ! empty( $field['description'] ) ? $field['description'] : '' );
        }

        // if value is a Field_Base object, use it.
        if ( $field instanceof Field_Base ) {
            $field_obj = $field;
        }

        // bail if $tab_obj is not set.
        if ( ! $field_obj instanceof Field_Base ) {
            return false;
        }

        // add the field to this setting.
        $this->field = $field_obj;

        // return the field object.
        return $field_obj;
    }

    /**
     * Return the section.
     *
     * @return Section|null
     */
    public function get_section(): Section|null {
        return $this->section;
    }

    /**
     * Set the section this setting will be assigned to.
     *
     * @param Section $section_obj The section this setting will be assigned to.
     *
     * @return void
     */
    public function set_section( Section $section_obj ): void {
        $this->section = $section_obj;
    }

    /**
     * Return the default value for this setting.
     *
     * @return mixed
     */
    public function get_default(): mixed {
        return $this->default;
    }

    /**
     * Set the default value for this setting.
     *
     * @param mixed $default_value The default value.
     *
     * @return void
     */
    public function set_default( mixed $default_value ): void {
        $this->default = $default_value;
    }

    /**
     * Return type of this setting (e.g. "boolean" or "string").
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Set the type of this setting (one of: "boolean", "integer", "string", "number", "array", "object").
     *
     * @param string $type The type.
     *
     * @return void
     */
    public function set_type( string $type ): void {
        // bail if given type is not supported.
        if ( ! Helper::is_setting_type_valid( $type ) ) {
            return;
        }

        // set the type.
        $this->type = $type;
    }

    /**
     * Return whether to show this setting in REST API.
     *
     * @return bool
     */
    public function is_show_in_rest(): bool {
        return $this->show_in_rest;
    }

    /**
     * Set show in REST API for this setting.
     *
     * @param boolean $show_in_rest True to show in rest.
     *
     * @return void
     */
    public function set_show_in_rest( bool $show_in_rest ): void {
        $this->show_in_rest = $show_in_rest;
    }

    /**
     * Return whether this setting has a callback which should be run before saving it.
     *
     * @return bool
     */
    public function has_read_callback(): bool {
        return ! empty( $this->get_read_callback() );
    }

    /**
     * Return the save callback.
     *
     * @return array|string
     */
    public function get_read_callback(): array|string {
        return $this->read_callback;
    }

    /**
     * Set the read callback.
     *
     * @param array $read_callback
     *
     * @return void
     */
    public function set_read_callback( array $read_callback ): void {
        $this->read_callback = $read_callback;
    }

    /**
     * Return whether this setting has a callback which should be run before saving it.
     *
     * @return bool
     */
    public function has_save_callback(): bool {
        return ! empty( $this->get_save_callback() );
    }

    /**
     * Return the save callback.
     *
     * @return array|string
     */
    public function get_save_callback(): array|string {
        return $this->save_callback;
    }

    /**
     * Set the save callback.
     *
     * @param array|string $save_callback The save callback.
     *
     * @return void
     */
    public function set_save_callback( array|string $save_callback ): void {
        $this->save_callback = $save_callback;
    }

    /**
     * Return whether a default value is set.
     *
     * @return bool
     */
    public function is_default_set(): bool {
        return $this->get_default() !== null;
    }

    /**
     * Return the value of this setting.
     *
     * @return mixed
     */
    public function get_value(): mixed {
        return get_option( $this->get_name() );
    }

    /**
     * Return whether to prevent the export of this setting.
     *
     * @return bool
     */
    public function is_export_prevented(): bool {
        return $this->prevent_export;
    }

    /**
     * Set prevent the export of this setting.
     *
     * @param bool $prevent_export True to prevent the export.
     *
     * @return void
     */
    public function prevent_export( bool $prevent_export ): void {
        $this->prevent_export = $prevent_export;
    }

    /**
     * Return whether this setting has a help text.
     *
     * @return bool
     */
    public function has_help(): bool {
        return ! empty( $this->help );
    }

    /**
     * Return the help text for this setting.
     *
     * @return string
     */
    public function get_help(): string {
        return $this->help;
    }

    /**
     * Set the help text.
     *
     * @param string $help The help text.
     *
     * @return void
     */
    public function set_help( string $help ): void {
        $this->help = $help;
    }

    /**
     * Return whether to autoload this setting.
     *
     * @return bool
     */
    public function is_autoloaded(): bool {
        return $this->autoload;
    }

    /**
     * Set if this setting should be autoloaded (true) or not (false).
     *
     * @param bool $autoload The new autoload value.
     *
     * @return void
     */
    public function set_autoload( bool $autoload ): void {
        $this->autoload = $autoload;
    }

    /**
     * Return the custom var for this setting.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get_custom_var( string $key ): mixed {
        // bail if key does not exist on object.
        if( ! isset( $this->vars[ $key ] ) ) {
            return false;
        }

        // return the value assigned to the key.
        return $this->vars[ $key ];
    }

    /**
     * Add a custom var to this setting.
     *
     * @param string $key The key.
     * @param string $value The value.
     *
     * @return void
     */
    public function add_custom_var( string $key, mixed $value ): void {
        $this->vars[$key] = $value;
    }

    /**
     * Move a setting before another one.
     *
     * @param Setting $target_setting The setting before the actual object could be moved.
     *
     * @return void
     */
    public function move_before_setting( Setting $target_setting ): void {
        // get the settings object.
        $settings_obj = Settings::get_instance();

        // get all settings.
        $settings = $settings_obj->get_settings();

        // get position of target setting.
        $target_position = 0;
        $actual_position = 0;
        foreach( $settings as $index => $setting ) {
            // get the position for the search target setting.
            if( $setting->get_name() === $target_setting->get_name() ) {
                // get the index as position.
                $target_position = $index;
            }

            // get the position of the actual setting.
            if( $setting->get_name() === $this->get_name() ) {
                $actual_position = $index;
            }
        }

        // remove the setting from its original position.
        unset( $settings[$actual_position] );

        // add the setting on the new position.
        $settings = Helper::add_array_in_array_on_position( $settings, $target_position, array( $target_position => $this ) );

        // save the new settings.
        $settings_obj->set_settings( $settings );
    }

    /**
     * Return whether this setting should not be registered. It will only be used as field.
     *
     * @return bool
     */
    public function should_not_be_registered(): bool {
        return $this->do_not_register;
    }

    /**
     * Mark setting to not register it. It will only be used as field.
     *
     * @param bool $do_not_register True if setting should not be registered.
     *
     * @return void
     */
    public function do_not_register( bool $do_not_register ): void {
        $this->do_not_register = $do_not_register;
        $this->prevent_export( $do_not_register );
    }
}
