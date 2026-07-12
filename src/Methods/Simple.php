<?php
/**
 * File for the simple method to handle settings.
 *
 * Simple means:
 * Every setting will be saved in its own option entry.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Methods;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Method_Base;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;

/**
 * Object to handle the simple method to handle settings.
 */
class Simple extends Method_Base {
	/**
	 * The internal name of this method.
	 *
	 * @var string
	 */
	protected string $name = 'simple';

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
		// register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'rest_api_init', array( $this, 'register_settings' ) );

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
	 * Run this tasks during activation of the plugin.
	 *
	 * @return void
	 */
	public function activation(): void {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// bail if default value is empty.
			if ( ! $setting->is_default_set() ) {
				continue;
			}

			// bail if option is already set.
			if ( false !== get_option( $setting->get_name(), false ) ) {
				continue;
			}

			// add the option.
			add_option( $setting->get_name(), $setting->get_default(), '', $setting->is_autoloaded() );

			// update the option to trigger callbacks.
			update_option( $setting->get_name(), $setting->get_default() );
		}
	}

	/**
	 * Delete all settings.
	 *
	 * @return void
	 */
	public function delete_settings(): void {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// remove our filter.
			remove_filter( 'option_' . $setting->get_name(), array( $this, 'sanitize_option' ) );
			if ( $setting->has_read_callback() ) {
				remove_filter( 'option_' . $setting->get_name(), $setting->get_read_callback() );
			}

			// unregister this setting.
			unregister_setting( $setting->get_name(), $setting->get_name() );

			// delete the option.
			delete_option( $setting->get_name() );
		}
	}

	/**
	 * Register settings of all tabs configured within this settings object.
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

			// collect arguments.
			$args = array(
				'type'         => $setting->get_type(),
				'default'      => $setting->get_default(),
				'show_in_rest' => $setting->get_show_in_rest(),
			);

			// if field is set, add its sanitizing callback.
			$field_obj = $setting->get_field();
			if ( $field_obj instanceof Field_Base ) {
				$args['sanitize_callback'] = $field_obj->get_sanitize_callback();
			}

			// register the setting.
			register_setting(
				$tab->get_name(),
				$setting->get_name(),
				$args
			);

			// sanitize the option before any output.
			add_filter( 'option_' . $setting->get_name(), array( $this, 'sanitize_option' ), 10, 2 );

			// run the custom callback after reading an option.
			if ( $setting->has_read_callback() ) {
				add_filter( 'option_' . $setting->get_name(), $setting->get_read_callback() );
			}

			// run the custom callback before updating an option.
			if ( $setting->has_save_callback() ) {
				add_filter( 'pre_update_option_' . $setting->get_name(), $setting->get_save_callback(), 10, 3 );
			}
		}
	}

	/**
	 * Return whether this method already has settings data saved.
	 *
	 * @return bool
	 */
	public function has_data(): bool {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// bail if at least one setting has an own option entry.
			if ( false !== get_option( $setting->get_name(), false ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Migrate existing settings to this method.
	 *
	 * @param Method_Base $old_method Object of the old method.
	 *
	 * @return void
	 */
	public function migrate( Method_Base $old_method ): void {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			$settings_name = $setting->get_name();

			// read the value using the OLD method's own logic.
			$value = $old_method->get_setting_value( $settings_name );

			// save it as its own option entry.
			update_option( $settings_name, $this->sanitize_option( $value, $settings_name ) );
		}
	}
}
