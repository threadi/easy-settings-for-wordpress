<?php
/**
 * File for the method "One" to handle settings.
 *
 * This method will save all settings in one singe option field.
 * It supports reading and updating their values in this single option field via get_option() and update_option().
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Methods;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Method_Base;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;

/**
 * Object to handle the method "One" to handle settings.
 */
class One extends Method_Base {
	/**
	 * The internal name of this method.
	 *
	 * @var string
	 */
	protected string $name = 'one';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// use hooks.
		add_filter( 'pre_update_option_' . $this->get_option_name(), array( $this, 'save_settings' ), 10, 0 );
		add_filter( 'allowed_options', array( $this, 'filter_allowed_options' ) );

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
	 * Return the name of the settings entry we use in the options table.
	 *
	 * @return string
	 */
	private function get_option_name(): string {
		$option_name = 'esfw_' . $this->get_settings_obj()->get_slug() . '_settings';

		/**
		 * Filter the option name to use.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param string $option_name The name of the option.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_option_name', $option_name );
	}

	/**
	 * Run this tasks during activation of the plugin.
	 *
	 * @return void
	 */
	public function activation(): void {
		// collect the settings.
		$settings = array();

		// loop through the settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// get the section.
			$section = $setting->get_section();

			// bail if section could not be read.
			if ( ! $section instanceof Section ) {
				continue;
			}

			// get the tab.
			$tab = $section->get_tab();

			// bail if tab could not be read.
			if ( ! $tab instanceof Tab ) {
				continue;
			}

			// get the settings name.
			$setting_name = $setting->get_name();

			// get the default value for this setting.
			$settings[ $setting_name ] = $this->sanitize_option( $setting->get_default(), $setting_name );
		}

		// add the single settings field.
		add_option( $this->get_option_name(), $settings, '', true );
	}

	/**
	 * Register the handling of get_option() and update_option() for each setting field,
	 * which should be read and write from the global settings field.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// bail if no settings are set.
		if ( ! $this->get_settings_obj()->has_settings() ) {
			return;
		}

		// loop through the settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// bail if setting should not be registered.
			if ( $setting->should_not_be_registered() ) {
				continue;
			}

			// get the option before any output.
			add_filter( 'pre_option_' . $setting->get_name(), array( $this, 'get_option' ), 10, 2 );

			// save the option in the main settings field.
			add_filter( 'pre_update_option_' . $setting->get_name(), array( $this, 'save_option' ), 10, 3 );
		}
	}

	/**
	 * Return the value of a single value from the global settings.
	 *
	 * @param mixed  $value The value to return.
	 * @param string $setting_name The name of the setting.
	 *
	 * @return mixed
	 */
	public function get_option( mixed $value, string $setting_name ): mixed {
		// get the global settings.
		$settings = get_option( $this->get_option_name() );

		// bail if this setting does not exist in the list of all settings.
		if ( ! isset( $settings[ $setting_name ] ) ) {
			return $value;
		}

		// get the value.
		$value = $settings[ $setting_name ];

		// get the settings object.
		$setting = $this->get_settings_obj()->get_setting( $setting_name );

		// run the custom callback after reading an option.
		if ( $setting instanceof Setting && $setting->has_read_callback() ) {
			$value = call_user_func( $setting->get_read_callback(), $value );
		}

		// return the settings value.
		return $value;
	}

	/**
	 * Save the given value in the global settings.
	 *
	 * @param mixed  $value The new value.
	 * @param mixed  $old_value The old value.
	 * @param string $setting_name The name of the setting.
	 *
	 * @return mixed
	 */
	public function save_option( mixed $value, mixed $old_value, string $setting_name ): mixed {
		// get the global settings.
		$settings = get_option( $this->get_option_name() );

		// sanitize the value of this setting.
		$value = $this->sanitize_option( $value, $setting_name );

		// get the settings object.
		$setting = $this->get_settings_obj()->get_setting( $setting_name );

		// run the custom callback before updating an option.
		if ( $setting instanceof Setting && $setting->has_save_callback() ) {
			$value = call_user_func( $setting->get_save_callback(), $value );
		}

		// add the setting.
		$settings[ $setting_name ] = $value;

		// save the global settings.
		update_option( $this->get_option_name(), $settings );

		// prevent the save process.
		return $old_value;
	}

	/**
	 * Collect all settings for the global field during saving them.
	 *
	 * @return array<string,mixed>
	 */
	public function save_settings(): array {
		// check nonce.
		if ( isset( $_POST['esfw-nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['esfw-nonce'] ) ), 'esfw-nonce' ) ) {
			exit;
		}

		// bail if no settings are set.
		if ( ! $this->get_settings_obj()->has_settings() ) {
			return array();
		}

		// get the actual global settings.
		$settings = get_option( $this->get_option_name() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// loop through the settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// get the section.
			$section = $setting->get_section();

			// bail if section could not be read.
			if ( ! $section instanceof Section ) {
				continue;
			}

			// get the tab.
			$tab = $section->get_tab();

			// bail if tab could not be read.
			if ( ! $tab instanceof Tab ) {
				continue;
			}

			// bail if this setting is not on the requested option page.
			if ( filter_input( INPUT_POST, 'option_page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) !== $tab->get_name() ) {
				continue;
			}

			// get the settings name.
			$setting_name = $setting->get_name();

			// get its value.
			if ( isset( $_POST[ $setting_name ] ) && is_array( $_POST[ $setting_name ] ) ) {
				$value = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $setting_name ] ) );
			} else {
				$value = filter_input( INPUT_POST, $setting_name, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			}

			// secure the value.
			if ( ! is_array( $value ) ) {
				$value = trim( $value );
			}
			$value = wp_unslash( $value );

			// sanitize the value.
			$value = $this->sanitize_option( $value, $setting_name );

			// run the custom callback before updating an option.
			if ( $setting->has_save_callback() ) {
				$value = call_user_func( $setting->get_save_callback(), $value );
			}

			// save the value in the list of settings.
			$settings[ $setting_name ] = $value;
		}

		// return the resulting list of global settings.
		return $settings;
	}

	/**
	 * Delete all settings.
	 *
	 * @return void
	 */
	public function delete_settings(): void {
		delete_option( $this->get_option_name() );
	}

	/**
	 * As we do not register each setting, we must add them to the allowed list to run the saving process.
	 *
	 * Hints:
	 * We add each tab to the list with only the name of the global setting field.
	 * This way we use WP-own features to save settings, but also save all settings in one field.
	 *
	 * @param array<string,mixed> $allowed_options List of allowed options.
	 *
	 * @return array<string,mixed>
	 */
	public function filter_allowed_options( array $allowed_options ): array {
		// loop through the settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// get the section.
			$section = $setting->get_section();

			// bail if section could not be read.
			if ( ! $section instanceof Section ) {
				continue;
			}

			// get the tab.
			$tab = $section->get_tab();

			// bail if tab could not be read.
			if ( ! $tab instanceof Tab ) {
				continue;
			}

			// bail if this entry already exist.
			if ( isset( $allowed_options[ $tab->get_name() ] ) ) {
				continue;
			}

			// add this entry with the global option name.
			$allowed_options[ $tab->get_name() ] = array(
				$this->get_option_name(),
			);
		}

		// return the resulting list.
		return $allowed_options;
	}

	/**
	 * Return the value of a single setting from the merged option field.
	 *
	 * Only used during migration.
	 *
	 * @param string $setting_name The internal name of the setting.
	 *
	 * @return mixed
	 */
	public function get_setting_value( string $setting_name ): mixed {
		// get all settings.
		$settings = get_option( $this->get_option_name() );

		// bail if no settings could be loaded.
		if ( ! is_array( $settings ) || ! isset( $settings[ $setting_name ] ) ) {
			return false;
		}

		// return the value.
		return $settings[ $setting_name ];
	}

	/**
	 * Return whether this method already has settings data saved.
	 *
	 * @return bool
	 */
	public function has_data(): bool {
		return ! empty( get_option( $this->get_option_name() ) );
	}

	/**
	 * Migrate existing settings to this method.
	 *
	 * @return void
	 */
	/**
	 * Migrate existing settings to this method.
	 *
	 * @param Method_Base $old_method Object of the old method.
	 *
	 * @return void
	 */
	public function migrate( Method_Base $old_method ): void {
		// bail if the settings are already set.
		if ( ! empty( get_option( $this->get_option_name() ) ) ) {
			return;
		}

		// collect the settings.
		$settings = array();

		// loop through the settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			$settings_name = $setting->get_name();

			// read the value using the OLD method's own logic.
			$value = $old_method->get_setting_value( $settings_name );

			// sanitize and store it in our own (merged) format.
			$settings[ $settings_name ] = $this->sanitize_option( $value, $settings_name );
		}

		// remove our own filter to avoid unwanted side effects while saving.
		remove_filter( 'pre_update_option_' . $this->get_option_name(), array( $this, 'save_settings' ) );

		update_option( $this->get_option_name(), $settings );

		add_filter( 'pre_update_option_' . $this->get_option_name(), array( $this, 'save_settings' ), 10, 0 );
	}
}
