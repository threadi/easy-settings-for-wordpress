<?php
/**
 * This file holds an object to output a simple text without any form field.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

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
	 * Whether this field should be registered (false) or not (true).
	 *
	 * @var bool
	 */
	protected bool $do_not_register = true;

	/**
	 * Callback for the tab.
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * The generated description, as the field reads it more than once.
	 *
	 * @var string|null
	 */
	private ?string $generated = null;

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

		// use the callback, if set.
		if ( null !== $this->callback ) {
			if ( \is_null( $this->generated ) ) {
				$description     = \call_user_func( $this->callback );
				$this->generated = \is_string( $description ) ? $description : '';
			}
			echo wp_kses_post( $this->generated );
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
	 * @return string
	 */
	public function default_sanitize_callback( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			$value = '';
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Set the callback.
	 *
	 * @param callable $callback The callback.
	 *
	 * @return void
	 */
	public function set_callback( callable $callback ): void {
		$this->callback = $callback;
	}
}
