<?php
/**
 * File with helper functions for settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
use Composer\InstalledVersions;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Object with helper tasks for settings.
 */
class Helper {
	/**
	 * Return list of possible field types.
	 *
	 * @return array<int,string>
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
	 * Return field object by type name.
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

			// create the object.
			$obj = new $field_name();

			// bail if object is not a "Field_Base" object.
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
	 * @return array<int,string>
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

	/**
	 * Add new entry with its key on specific position in an array.
	 *
	 * @param array<int|string,mixed> $array_to_change The array we want to change.
	 * @param mixed                   $key The position where the new array should be added.
	 * @param array<int|string,mixed> $array_to_add The new array, which should be added.
	 *
	 * @return array<int|string,mixed>
	 */
	public static function add_array_in_array_on_position( array $array_to_change, mixed $key, array $array_to_add ): array {
		$index = array_search( $key, array_keys( $array_to_change ), true );

		// key is not found, add to the end of the array.
		if ( false === $index ) {
			$array_to_change = array_merge( $array_to_change, $array_to_add );
		} else {
			// split the array into two parts and insert a new element between them.
			$array_to_change = array_merge(
				array_slice( $array_to_change, 0, $index + 1, true ),
				$array_to_add,
				array_slice( $array_to_change, $index + 1, null, true )
			);
		}

		return $array_to_change;
	}

	/**
	 * Return the next free index in an array.
	 *
	 * @param array<int,mixed> $source The source array.
	 * @param int              $index The index where we start to search.
	 *
	 * @return int
	 */
	public static function get_next_free_index_in_array( array $source, int $index ): int {
		// set max iteration.
		$max = 0;
		if ( ! empty( $source ) ) {
			$max = absint( max( array_keys( $source ) ) );
		}

		// loop through the possible iterations.
		for ( $i = $index; $i < $max; ++$i ) {
			// bail is index does exist.
			if ( isset( $source[ $i ] ) ) {
				continue;
			}

			// return this index as it is free.
			return $i;
		}

		// return default index for empty arrays.
		if ( empty( $source ) ) {
			return 10;
		}

		// return the max key + 10.
		return $max + 10;
	}

	/**
	 * Return the version of the given file.
	 *
	 * With WP_DEBUG enabled its @filemtime().
	 * Without this it's the composer package-version.
	 *
	 * @param string   $filepath The absolute path to the requested file.
	 * @param Settings $settings_obj The settings object.
	 * @return string
	 */
	public static function get_file_version( string $filepath, Settings $settings_obj ): string {
		// check for WP_DEBUG.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return (string) filemtime( $filepath );
		}

		// get the composer package version, which as been set in release.
		$version = (string) InstalledVersions::getPrettyVersion( 'threadi/easy-settings-for-wordpress' );

		/**
		 * Filter the used file version (for JS- and CSS-files, which get enqueued).
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 *
		 * @param string $version The composer package version.
		 * @param string $filepath The absolute path to the requested file.
		 */
		return apply_filters( $settings_obj->get_slug() . '_file_version', $version, $filepath );
	}

	/**
	 * Create JSON from a given array.
	 *
	 * @param array<string|int,mixed>|WP_Error $source The source array.
	 * @param int                              $flag Flags to use for this JSON.
	 *
	 * @return string
	 */
	public static function get_json( array|WP_Error $source, int $flag = 0 ): string {
		// create JSON.
		$json = wp_json_encode( $source, $flag );

		// bail if creating the JSON failed.
		if ( ! $json ) {
			return '';
		}

		// return the resulting JSON-string.
		return $json;
	}
}
