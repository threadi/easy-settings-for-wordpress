<?php
/**
 * File for an object to handle basic methods tasks.
 *
 * The methods should use the default get_option() and update_option() for each setting.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle basic methods tasks.
 */
class Method_Base {
	/**
	 * Set settings object.
	 *
	 * @var Settings
	 */
	protected Settings $settings_obj;

	/**
	 * The internal name of this method.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Return the settings object to use.
	 *
	 * @return Settings
	 */
	public function get_settings_obj(): Settings {
		return $this->settings_obj;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Run this tasks during activation of the plugin.
	 *
	 * @return void
	 */
	public function activation(): void {}

	/**
	 * Delete all settings.
	 *
	 * @return void
	 */
	public function delete_settings(): void {}

	/**
	 * Sanitize our own option values before output.
	 *
	 * @param mixed  $value The value.
	 * @param string $option The option-name.
	 *
	 * @return mixed
	 */
	public function sanitize_option( mixed $value, string $option ): mixed {
		// get field settings.
		$field_settings = $this->get_settings_obj()->get_setting( $option );

		// bail if setting could not be found.
		if ( ! $field_settings ) {
			return $value;
		}

		// bail if no type is set.
		if ( empty( $field_settings->get_type() ) ) {
			return $value;
		}

		// bail if given type is not supported.
		if ( ! Helper::is_setting_type_valid( $field_settings->get_type() ) ) {
			return $value;
		}

		// if type is a string, secure for string.
		if ( 'string' === $field_settings->get_type() ) {
			return (string) $value;
		}

		// if type is a boolean, secure for boolean.
		if ( 'boolean' === $field_settings->get_type() ) {
			return (bool) $value;
		}

		// if type is an object, secure for the object.
		if ( 'object' === $field_settings->get_type() ) {
			return (object) $value;
		}

		// if type is array, secure for an array.
		if ( 'array' === $field_settings->get_type() ) {
			// if it is an array, use it 1:1.
			if ( is_array( $value ) ) {
				return $value;
			}

			// secure the value.
			return (array) $value;
		}

		// if type is int, secure value for an integer.
		if ( 'integer' === $field_settings->get_type() || 'number' === $field_settings->get_type() ) {
			return absint( $value );
		}

		// return the value.
		return $value;
	}

	/**
	 * Return the internal name of this method.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Return the value of a single setting as it is stored by this method.
	 *
	 * Used e.g., during migration to read data from the method we are migrating away from.
	 *
	 * @param string $setting_name The internal name of the setting.
	 *
	 * @return mixed
	 */
	public function get_setting_value( string $setting_name ): mixed {
		// default: each setting has its own option entry.
		return get_option( $setting_name );
	}

	/**
	 * Return whether this method already has settings data saved.
	 *
	 * Used to prevent running the migration more than once.
	 *
	 * @return bool
	 */
	public function has_data(): bool {
		return false;
	}

	/**
	 * Migrate existing settings to this method.
	 *
	 * @param Method_Base $old_method Object of the old method.
	 *
	 * @return void
	 */
	public function migrate( Method_Base $old_method ): void {}
}
