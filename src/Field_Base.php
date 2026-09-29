<?php
/**
 * File for an object to handle basic field methods and tasks.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle basic field methods and tasks.
 */
class Field_Base {

	/**
	 * Set settings object.
	 *
	 * @var Settings
	 */
	protected Settings $settings_obj;

	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = '';

	/**
	 * The title / label for this field.
	 *
	 * @var string
	 */
	private string $title = '';

	/**
	 * The description for this field.
	 *
	 * @var string
	 */
	private string $description = '';

	/**
	 * Readonly marker.
	 *
	 * @var bool
	 */
	private bool $readonly = false;

	/**
	 * The sanitize callback.
	 *
	 * @var callable|null
	 */
	private $sanitize_callback;

	/**
	 * Depending setting.
	 *
	 * @var array<string,mixed>
	 */
	private array $depends = array();

	/**
	 * Whether this field should be registered (false) or not (true).
	 *
	 * @var bool
	 */
	protected bool $do_not_register = false;

	/**
	 * The setting this field belongs to.
	 *
	 * @var Setting|false
	 */
	private Setting|false $setting = false;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Return the HTML-code to display this field.
	 *
	 * @param array<string,mixed> $attr Attributes for this field.
	 *
	 * @return void
	 */
	public function display( array $attr ): void {
		// bail if attributes are empty.
		if ( empty( $attr ) ) {
			return;
		}

		// echo an empty string.
		echo '';
	}

	/**
	 * Return title for this field.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Set title for this field.
	 *
	 * @param string $title The title to use.
	 *
	 * @return void
	 */
	public function set_title( string $title ): void {
		$this->title = $title;
	}

	/**
	 * Return description for this field.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Set description for this field.
	 *
	 * @param string $description The description to use.
	 *
	 * @return void
	 */
	public function set_description( string $description ): void {
		$this->description = $description;
	}

	/**
	 * Return whether this field is set to readonly.
	 *
	 * @return bool
	 */
	public function is_readonly(): bool {
		$instance = $this;
		/**
		 * Filter the readonly setting for the actual setting.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param bool $readonly The actual value.
		 * @param Field_Base $instance The field object.
		 */
		return apply_filters( $this->settings_obj->get_slug() . '_setting_readonly', $this->readonly, $instance );
	}

	/**
	 * Set description for this field.
	 *
	 * @param bool $readonly_value The readonly marker.
	 *
	 * @return void
	 */
	public function set_readonly( bool $readonly_value ): void {
		$this->readonly = $readonly_value;
	}

	/**
	 * Return the callback for this field.
	 *
	 * @return callable
	 */
	public function get_callback(): callable {
		return array( $this, 'display' );
	}

	/**
	 * Return the sanitize callback.
	 *
	 * The callback is wrapped: while this field is readonly and the value comes from
	 * a settings form or the REST settings endpoint, the stored value is kept.
	 *
	 * @return callable
	 */
	public function get_sanitize_callback(): callable {
		// get the configured callback or our default callback.
		$callback = ! \is_null( $this->sanitize_callback ) ? $this->sanitize_callback : array( $this, 'default_sanitize_callback' );

		return function ( mixed $value ) use ( $callback ): mixed {
			// keep the stored value for readonly fields.
			if ( $this->should_keep_stored_value() ) {
				return $this->get_stored_value();
			}

			// otherwise run the real sanitize callback.
			return call_user_func( $callback, $value );
		};
	}

	/**
	 * Return whether the stored value must be kept instead of the submitted one.
	 *
	 * Only applies to requests that save settings (settings form or REST settings
	 * endpoint), so programmatic update_option() calls and the import still work.
	 *
	 * @return bool
	 */
	protected function should_keep_stored_value(): bool {
		// bail if field is not readonly.
		if ( ! $this->is_readonly() ) {
			return false;
		}

		// bail if no setting is assigned (e.g. inner fields of a MultiField).
		if ( ! $this->get_setting() instanceof Setting ) {
			return false;
		}

		// check for a save of a settings form (options.php or method "One").
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- only a check, the nonce is verified by the save handler.
		$keep = isset( $_POST['option_page'] );

		// check for a save via the REST settings endpoint (DataView).
		if ( ! $keep && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
			$keep  = str_starts_with( $route, '/wp/v2/settings' );
		}

		$instance = $this;
		/**
		 * Filter whether a readonly field keeps its stored value on save.
		 *
		 * @since 3.6.3 Available since 3.6.3.
		 * @param bool       $keep     True to keep the stored value.
		 * @param Field_Base $instance The field object.
		 */
		return (bool) apply_filters( $this->get_settings_obj()->get_slug() . '_setting_readonly_keep_value', $keep, $instance );
	}

	/**
	 * Return the actual stored value of the setting this field belongs to.
	 *
	 * @return mixed
	 */
	protected function get_stored_value(): mixed {
		$setting = $this->get_setting();

		// bail if no setting is assigned.
		if ( ! $setting instanceof Setting ) {
			return null;
		}

		return get_option( $setting->get_name(), $setting->get_default() );
	}

	/**
	 * Set custom sanitize callback.
	 *
	 * @param callable $sanitize_callback The custom sanitize callback.
	 *
	 * @return void
	 */
	public function set_sanitize_callback( callable $sanitize_callback ): void {
		$this->sanitize_callback = $sanitize_callback;
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return mixed
	 */
	public function default_sanitize_callback( mixed $value ): mixed {
		_doing_it_wrong( __METHOD__, 'sanitize_callback() should be overridden.', '2.1.5' );
		return $value;
	}

	/**
	 * Return the field type name.
	 *
	 * @return string
	 */
	public function get_type_name(): string {
		return $this->type_name;
	}

	/**
	 * Return the setting this field belongs to.
	 *
	 * @return Setting|false
	 */
	public function get_setting(): Setting|false {
		return $this->setting;
	}

	/**
	 * Set the setting this field belongs to.
	 *
	 * @param Setting $setting The setting.
	 *
	 * @return void
	 */
	public function set_setting( Setting $setting ): void {
		$this->setting = $setting;
	}

	/**
	 * Return the configured fields this field depends on.
	 *
	 * @return string
	 */
	public function get_depend(): string {
		// bail if no dependent fields are set.
		if ( empty( $this->depends ) ) {
			return '';
		}

		// generate JSON of this setting.
		$json = wp_json_encode( $this->depends, JSON_FORCE_OBJECT );

		// bail if JSON could not be generated.
		if ( ! $json ) {
			return '';
		}

		// return the JSON string with the configuration.
		return $json;
	}

	/**
	 * Return the configured fields this field depends on as array.
	 *
	 * @return array<string,mixed>
	 */
	public function get_depend_as_array(): array {
		return $this->depends;
	}

	/**
	 * Add a setting this field depends on.
	 *
	 * This field will only be visible if this setting has the requested value.
	 *
	 * @param Setting $setting The setting this fields depends on.
	 * @param mixed   $value The value it must have.
	 *
	 * @return void
	 */
	public function add_depend( Setting $setting, mixed $value ): void {
		$this->depends[ $setting->get_name() ] = $value;
	}

	/**
	 * Return the placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return '';
	}

	/**
	 * Set the field value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return void
	 */
	public function set_value( mixed $value ): void {}

	/**
	 * Return the settings object to use.
	 *
	 * @return Settings
	 */
	public function get_settings_obj(): Settings {
		return $this->settings_obj;
	}

	/**
	 * Validate given values against given options.
	 *
	 * @param mixed                   $value The given value.
	 * @param array<string|int,mixed> $options The possible options.
	 * @param bool                    $multiple Whether these are multiple values (true) or not (false).
	 *
	 * @return array<string|int,mixed>|string
	 */
	protected function validate_against_options( mixed $value, array $options, bool $multiple = false ): array|string {
		// build the allow-list once, keys as strings (options may use int or string keys).
		$allowed_keys = array_map( 'strval', array_keys( $options ) );

		// single-value fields (Select, Radio, ...): value must be scalar and match one allowed key.
		if ( ! $multiple ) {
			if ( ! is_scalar( $value ) ) {
				return '';
			}
			$value = (string) $value;
			return in_array( $value, $allowed_keys, true ) ? $value : '';
		}

		// multi-value fields (MultiSelect, Checkboxes, ...): value must be an array of scalars.
		if ( ! is_array( $value ) ) {
			return array();
		}

		$validated = array();
		foreach ( $value as $entry ) {
			if ( ! is_scalar( $entry ) ) {
				continue; // drop injected nested arrays.
			}
			$entry = (string) $entry;
			if ( ! in_array( $entry, $allowed_keys, true ) ) {
				continue; // drop everything not in set_options().
			}
			$validated[] = $entry;
		}

		return array_values( array_unique( $validated ) );
	}

	/**
	 * Return whether this field type should not be registered.
	 *
	 * @return bool
	 */
	public function should_not_be_registered(): bool {
		return $this->do_not_register;
	}

	/**
	 * Return the REST schema fragment for this field type.
	 *
	 * Empty array => fall back to the scalar schema derived from the setting type.
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array {
		return array();
	}
}
