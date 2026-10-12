<?php
/**
 * This file holds an object for a single password field.
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
 * Object to handle a text field for single setting.
 */
class Password extends Field_Base {
	/**
	 * Value the DataView sends to delete the stored value (only if the delete option is enabled).
	 *
	 * The REST settings endpoint cannot transport the checkbox of the classic form, so this
	 * marker is used instead. It is never stored.
	 */
	public const DELETE_TOKEN = '__esfw_delete_stored_value__';

	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Password';

	/**
	 * The placeholder.
	 *
	 * @var string
	 */
	private string $placeholder = '';

	/**
	 * The value.
	 *
	 * @var string
	 */
	private string $value = '';

	/**
	 * Show the label with the field.
	 *
	 * @var bool
	 */
	private bool $with_label = false;

	/**
	 * Show a checkbox to delete the stored value (disabled by default).
	 *
	 * @var bool
	 */
	private bool $delete_option = false;

	/**
	 * Custom label for the checkbox to delete the stored value.
	 *
	 * @var string
	 */
	private string $delete_option_label = '';

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

		// get the setting object.
		$setting = $attr['setting'];

		// never output the stored secret into the page.
		$value        = '';
		$placeholder  = $this->get_placeholder();
		$is_stored    = false;
		$translations = $this->get_settings_obj()->get_translations();

		// use value from object, if set (e.g. inner field of a MultiField, which needs the value to keep it).
		if ( '' !== $this->get_value() ) {
			$value = $this->get_value();
		} else {
			$stored    = get_option( $setting->get_name(), '' );
			$is_stored = is_string( $stored ) && '' !== $stored;

			// show a masked placeholder, if a password is stored.
			if ( $is_stored && '' === $placeholder ) {
				$placeholder = '••••••••';
			}
		}

		// show the label with the checkbox.
		if ( $this->get_with_label() ) {
			?><label for="<?php echo esc_attr( $setting->get_name() ); ?>"><?php echo esc_html( $this->get_title() ); ?></label>
			<?php
		}

		?>
		<input type="password" id="<?php echo esc_attr( $setting->get_name() ); ?>"
				name="<?php echo esc_attr( $setting->get_name() ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				autocomplete="new-password"
				<?php
				echo ( $this->is_readonly() ? ' readonly="readonly"' : '' );
				?>
				class="widefat <?php echo esc_attr( $this->get_settings_obj()->get_slug() ); ?>-field-width"
				title="<?php echo esc_attr( $this->get_title() ); ?>"
				data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"
		>
		<?php

		// show hint and the optional checkbox to delete the stored value.
		if ( $is_stored ) {
			echo '<p class="description">' . esc_html( $translations['password_stored_hint'] ) . '</p>';
			if ( $this->has_delete_option() && ! $this->is_readonly() ) {
				?>
				<label><input type="checkbox" name="<?php echo esc_attr( $this->get_clear_field_name( $setting ) ); ?>" value="1"> <?php echo esc_html( $this->get_delete_option_label() ); ?></label>
				<?php
			}
		}

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return string
	 */
	public function default_sanitize_callback( mixed $value ): string {
		// bail if value is not a scalar (null, arrays, objects).
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		// do not use sanitize_text_field() here: it removes "%xx", tags, line breaks and
		// surrounding whitespace and would silently change secrets like "ab%41cd" or "p<ss".
		// The value is never output unescaped, so only invalid UTF-8 and control chars are removed.
		$value = wp_check_invalid_utf8( (string) $value, true );
		return (string) preg_replace( '/[\x00-\x1F\x7F]/', '', $value );
	}

	/**
	 * Return the sanitize callback.
	 *
	 * In addition to the readonly handling of the parent, an empty value submitted via
	 * the settings form or the REST settings endpoint keeps the stored password, because
	 * the stored password is never sent to the browser. The stored password is only
	 * removed if the optional "delete" checkbox is enabled and has been checked.
	 *
	 * @return callable
	 */
	public function get_sanitize_callback(): callable {
		$callback = parent::get_sanitize_callback();

		return function ( mixed $value ) use ( $callback ): mixed {
			$setting = $this->get_setting();

			// handle the delete marker of the DataView: delete only if enabled, otherwise keep the stored value.
			if ( $setting instanceof Setting && self::DELETE_TOKEN === $value && $this->is_settings_save_request() ) {
				if ( $this->has_delete_option() ) {
					return '';
				}
				$stored = $this->get_stored_value();
				return is_string( $stored ) ? $stored : '';
			}

			// only for regular settings saved via form or REST, not for programmatic updates.
			if ( $setting instanceof Setting && ( is_null( $value ) || '' === $value ) && $this->is_settings_save_request() ) {
				// delete the password, if requested via the enabled checkbox.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified by the save handler.
				if ( $this->has_delete_option() && ! empty( $_POST[ $this->get_clear_field_name( $setting ) ] ) ) {
					return '';
				}

				// keep the stored password.
				$stored = $this->get_stored_value();
				if ( is_string( $stored ) && '' !== $stored ) {
					return $stored;
				}
			}

			return call_user_func( $callback, $value );
		};
	}

	/**
	 * Assign the setting and hide the stored value in the REST settings endpoint.
	 *
	 * @param Setting $setting The setting object.
	 *
	 * @return void
	 */
	public function set_setting( Setting $setting ): void {
		parent::set_setting( $setting );

		// adding the same callback twice has no effect, so this is safe for repeated calls.
		add_filter( 'rest_pre_get_setting', array( $this, 'mask_rest_value' ), 10, 3 );
	}

	/**
	 * Return an empty value for this password in GET /wp/v2/settings.
	 *
	 * Saving via REST still works: an empty value keeps the stored password (see get_sanitize_callback()).
	 *
	 * @param mixed               $result The value to return, null to use the default handling.
	 * @param string              $name   The REST name of the setting.
	 * @param array<string,mixed> $args   The arguments of the setting.
	 *
	 * @return mixed
	 */
	public function mask_rest_value( mixed $result, string $name, array $args ): mixed {
		$setting = $this->get_setting();

		// bail if this is not our setting.
		if ( ! $setting instanceof Setting || ! isset( $args['option_name'] ) || $setting->get_name() !== $args['option_name'] ) {
			return $result;
		}

		return '';
	}

	/**
	 * Return the name of the checkbox to delete the stored password.
	 *
	 * @param Setting $setting The setting object.
	 *
	 * @return string
	 */
	private function get_clear_field_name( Setting $setting ): string {
		return $setting->get_name() . '_esfw_clear';
	}

	/**
	 * Return the placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return $this->placeholder;
	}

	/**
	 * Set the placeholder.
	 *
	 * @param string $placeholder The placeholder to use.
	 *
	 * @return void
	 */
	public function set_placeholder( string $placeholder ): void {
		$this->placeholder = $placeholder;
	}

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	private function get_value(): string {
		return $this->value;
	}

	/**
	 * Set the value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return void
	 */
	public function set_value( mixed $value ): void {
		$this->value = (string) $value;
	}

	/**
	 * Return whether we show the label with the checkbox.
	 *
	 * @return bool
	 */
	private function get_with_label(): bool {
		return $this->with_label;
	}

	/**
	 * Set to show the label with the checkbox.
	 *
	 * @param bool $with_label True to show the label.
	 *
	 * @return void
	 */
	public function set_with_label( bool $with_label ): void {
		$this->with_label = $with_label;
	}

	/**
	 * Return whether the checkbox to delete the stored value is shown.
	 *
	 * @return bool
	 */
	public function has_delete_option(): bool {
		return $this->delete_option;
	}

	/**
	 * Show a checkbox to delete the stored value.
	 *
	 * Disabled by default: an empty field always keeps the stored value. Enable it, if
	 * users must be able to remove the value (e.g. to disconnect an API).
	 *
	 * @param bool $delete_option True to show the checkbox.
	 *
	 * @return void
	 */
	public function set_delete_option( bool $delete_option ): void {
		$this->delete_option = $delete_option;
	}

	/**
	 * Return the label for the checkbox to delete the stored value.
	 *
	 * @return string
	 */
	public function get_delete_option_label(): string {
		// use the custom label, if set.
		if ( '' !== $this->delete_option_label ) {
			return $this->delete_option_label;
		}

		// otherwise use the global translation.
		return $this->get_settings_obj()->get_translations()['password_delete_option'];
	}

	/**
	 * Set a custom label for the checkbox to delete the stored value, e.g. "Remove API key".
	 *
	 * @param string $label The label.
	 *
	 * @return void
	 */
	public function set_delete_option_label( string $label ): void {
		$this->delete_option_label = $label;
	}
}
