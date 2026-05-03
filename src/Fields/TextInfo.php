<?php
/**
 * This file holds an object to output a simple text without any form field.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;

/**
 * Object to handle the output a simple text without any form field.
 */
class TextInfo extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'TextInfo';

	/**
	 * Return the HTML-code to display this field.
	 *
	 * @param array<string,mixed> $attr Attributes for this field.
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

		// check if paragraphs should be added.
		$add_paragraphs = strpos( $this->get_description(), '<p>' );

		// output the value.
		echo '<div data-depends="' . esc_attr( $this->get_depend() ) . '">';
		echo ( $add_paragraphs ? '<p>' : '' ) . wp_kses_post( $this->get_description() ) . ( $add_paragraphs ? '<p>' : '' );
		echo '</div>';
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return mixed
	 */
	public function default_sanitize_callback( mixed $value ): mixed {
		return $value;
	}
}
