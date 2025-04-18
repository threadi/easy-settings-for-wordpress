<?php
/**
 * File with helper functions for settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object with helper tasks for settings.
 */
class Helper {
	/**
	 * Return list of possible field types.
	 *
	 * @return array
	 */
	public static function get_field_types(): array {
		return array(
			'easySettingsForWordPress\Fields\Checkbox',
			'easySettingsForWordPress\Fields\MultiSelect',
			'easySettingsForWordPress\Fields\Number',
			'easySettingsForWordPress\Fields\Select',
			'easySettingsForWordPress\Fields\Value',
		);
	}

	/**
	 * Get field object by type name.
	 *
	 * @param string $type_name The type name.
	 *
	 * @return false|Field_Base
	 */
	public static function get_field_by_type_name( string $type_name ): false|Field_Base {
		// bail if type name is empty.
		if ( empty( $type_name ) ) {
			return false;
		}

		// check each field type.
		foreach ( self::get_field_types() as $field_name ) {
			// bail if object does not exist.
			if ( ! class_exists( $field_name ) ) {
				continue;
			}

			// create object.
			$obj = new $field_name();

			// bail if object is not a Field_Base.
			if ( ! $obj instanceof Field_Base ) {
				continue;
			}

			// compare its name with the searched one.
			if ( $type_name !== $obj->get_type_name() ) {
				continue;
			}

			// return resulting object.
			return $obj;
		}

		// return false if not object could be found.
		return false;
	}

	/**
	 * Check if given type is valid for setting.
	 *
	 * @param string $type The type to check.
	 *
	 * @return bool
	 */
	public static function is_setting_type_valid( string $type ): bool {
		return in_array( $type, self::get_setting_types(), true );
	}

	/**
	 * Return list of valid data types for settings. They are given by WordPress.
	 *
	 * @source https://developer.wordpress.org/reference/functions/register_setting/
	 *
	 * @return array
	 */
	private static function get_setting_types(): array {
		return array(
			'string',
			'boolean',
			'integer',
			'number',
			'array',
			'object',
		);
	}
}
