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

use WP_REST_Request;
use WP_REST_Server;

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
	public function init(): void {
		// register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_settings' ) );

		// make the classic Settings-API error helpers available for REST requests.
		add_filter( 'rest_pre_dispatch', array( $this, 'load_settings_api_helpers' ), 10, 3 );

		// register the settings during WP CLI run.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'init', array( $this, 'register_settings' ), 200 );
		}

		// register the settings during WP Cron run.
		if ( wp_doing_cron() ) {
			add_action( 'init', array( $this, 'register_settings' ), 200 );
		}
	}

	/**
	 * Run these tasks during activation of the plugin.
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
	 * Register the handling of get_option() and update_option() for each setting field,
	 * which should be read and write from the global settings field.
	 *
	 * @return void
	 */
	public function register_settings(): void {}

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

	/**
	 * Ensure the classic Settings-API helper functions are available during REST
	 * requests to the settings endpoint.
	 *
	 * The functions add_settings_error(), get_settings_errors() and settings_errors() live in
	 * wp-admin/includes/template.php, which is not loaded during a REST request.
	 * Sanitize/validation callbacks that rely on them would otherwise trigger a
	 * fatal error (HTTP 500) when settings are saved through the DataView.
	 *
	 * Hooked on rest_pre_dispatch so the file is loaded before the route callback
	 * (and thus before any sanitized callback) runs.
	 *
	 * @param mixed           $result  The pre-dispatch result. Passed through unchanged.
	 * @param WP_REST_Server  $server  The REST server instance.
	 * @param WP_REST_Request $request The current REST request.
	 *
	 * @return mixed
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function load_settings_api_helpers( mixed $result, WP_REST_Server $server, WP_REST_Request $request ): mixed {
		// bail if the helpers are already available.
		if ( function_exists( 'add_settings_error' ) ) {
			return $result;
		}

		// bail if this is not a request to the settings endpoint.
		if ( ! str_starts_with( $request->get_route(), '/wp/v2/settings' ) ) {
			return $result;
		}

		// load the file that defines add_settings_error(), get_settings_errors(), etc.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		return $result;
	}

	/**
	 * Add new or missing settings to the database.
	 *
	 * @return void
	 */
	public function update_settings(): void {}
}
