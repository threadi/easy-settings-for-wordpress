<?php
/**
 * This file holds an object to display multiple fields of one type to collect a list of entries.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;

/**
 * Object to display multiple fields of one type to collect a list of entries.
 */
class MultiField extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'MultiField';

	/**
	 * The field to display.
	 *
	 * @var Field_Base
	 */
	private Field_Base $field;

	/**
	 * The quantity of fields.
	 *
	 * @var int
	 */
	private int $quantity = 1;

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

		// get values.
		$values = (array) get_option( $setting->get_name(), array() );

		// show the fields in a loop.
		for( $q = 0;$q<$this->get_quantity();$q++ ) {
			// create a custom setting we need to show the field.
			$field_setting = new Setting();
			$field_setting->set_name( $setting->get_name() . '[' . $q . ']' );
			$field_setting->add_custom_var( 'value', isset( $values[$q] ) ? $values[$q] : '' );

			// get the field object.
			$obj = $this->get_field();
			$obj->set_title( $this->get_title() . ' #' . $q+1 );

			// show the field.
			$obj->display( array( 'setting' => $field_setting ) );
		}

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * Return the field.
	 *
	 * @return Field_Base
	 */
	private function get_field(): Field_Base {
		return $this->field;
	}

	/**
	 * Set the field which will be displayed multiple times.
	 *
	 * @param Field_Base $field The field to display.
	 *
	 * @return void
	 */
	public function set_field( Field_Base $field ): void {
		$this->field = $field;
	}

	/**
	 * Return the quantity to use.
	 *
	 * @return int
	 */
	private function get_quantity(): int {
		return $this->quantity;
	}

	/**
	 * Set quantity of fields to show.
	 *
	 * @param int $quantity The quantity.
	 *
	 * @return void
	 */
	public function set_quantity( int $quantity ): void {
		$this->quantity = $quantity;
	}
}
