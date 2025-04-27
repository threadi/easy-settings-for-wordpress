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
}
