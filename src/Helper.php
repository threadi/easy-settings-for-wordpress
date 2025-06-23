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

    /**
     * Add new entry with its key on specific position in array.
     *
     * @param array<int|string,mixed>|null $array_to_change The array we want to change.
     * @param mixed                          $key The position where the new array should be added.
     * @param array<int|string,mixed>      $array_to_add The new array which should be added.
     *
     * @return array<int|string,mixed>
     */
    public static function add_array_in_array_on_position( array|null $array_to_change, mixed $key, array $array_to_add ): array {
        $index = array_search( $key, array_keys( $array_to_change ), true );

        // key is not found, add to the end of the array
        if( $index === false ){
            $array_to_change = array_merge( $array_to_change, $array_to_add );
        }
        // split the array into two parts and insert a new element between them
        else{
            $array_to_change = array_merge(
                array_slice( $array_to_change, 0, $index + 1, true ),
                $array_to_add,
                array_slice( $array_to_change, $index + 1, null, true )
            );
        }

        return $array_to_change;
    }

    /**
     * Get the next free index in array.
     *
     * @param array $source The source array.
     * @param int   $index The index where we start to search.
     *
     * @return int
     */
    public static function get_next_free_index_in_array( array $source, int $index ): int {
        // set max iteration.
        $max = 0;
        if( ! empty( $source ) ) {
            $max = absint( max( array_keys($source ) ) );
        }

        // loop through the possible iterations.
        for ($i=$index; $i<$max; ++$i) {
            // bail is index does exist.
            if ( isset( $source[$i] ) ) {
                continue;
            }

            // return this index as it is free.
            return $i;
        }

        // return default index for empty arrays.
        if( empty( $source ) ) {
            return 10;
        }

        // return the max key + 10.
        return $max + 10;
    }
}
