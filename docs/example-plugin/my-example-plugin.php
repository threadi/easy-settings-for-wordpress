<?php
/**
 * Plugin Name:       My Example Plugin
 * Description:       This plugin demonstrates the usage of the composer package threadi/easy-settings-for-wordpress.
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://www.example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-example-plugin
 *
 * @package my-example-plugin
 */

declare(strict_types=1);

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Settings;

// embed the composer packages.
require __DIR__ . '/vendor/autoload.php';

/**
 * Register the settings during plugin activation.
 */
function my_example_plugin_activation(): void {
	// load the settings.
	my_example_plugin_init();

	// initiate the settings.
	my_example_plugin_get_settings_object()->activation();
}
register_activation_hook( __FILE__, 'my_example_plugin_activation' );

/**
 * Initialize the settings.
 */
function my_example_plugin_init(): void {
	/**
	 * Configure the basic settings object.
	 */
	$settings_obj = my_example_plugin_get_settings_object();
	$settings_obj->set_view( 'dataview' );

	// get the settings page.
	$page = $settings_obj->get_page( $settings_obj->get_menu_slug() );

	// add our first tab.
	$tab = $page->add_tab( 'my-example-plugin-settings-tab', 10 );
	$tab->set_title( __( 'Settings', 'my-example-plugin' ) );

	// add our first section.
	$section = $tab->add_section( 'my-example-plugin-settings-section', 10 );

	// add our first setting.
	$setting = $settings_obj->add_setting( 'my_example_checkbox' );
	$setting->set_section( $section );
	$field = new Checkbox( $settings_obj );
	$field->set_title( __( 'My example checkbox', 'my-example-plugin' ) );
	$setting->set_field( $field );

	/**
	 * Initiate the settings object.
	 */
	$settings_obj->init();
}
add_action( 'init', 'my_example_plugin_init' );

/**
 * Return the settings object.
 *
 * @return Settings
 */
function my_example_plugin_get_settings_object(): Settings {
	/**
	 * Variable for the object.
	 */
	static $settings = null;

	/**
	 * Get the object one time.
	 */
	if ( null === $settings ) {
		$settings = new Settings( __FILE__ );
	}

	// return it.
	return $settings;
}
