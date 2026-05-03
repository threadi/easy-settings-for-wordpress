<?php
/**
 * File to handle the main object for each test class.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests;

use WP_UnitTestCase;

/**
 * Object to handle the preparations for each test class.
 */
abstract class easySettingsForWordPressTest extends WP_UnitTestCase {
	/**
	 * Set the plugin handle.
	 *
	 * @var string
	 */
	protected static string $plugin_handle = __FILE__;

	/**
	 * Prepare the test environment for each test class.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// prepare to load just one time.
		if ( ! did_action('esfw_test_preparation_loaded') ) {
			// enable error reporting.
			error_reporting( E_ALL );

			// activate the light plugin.
			activate_plugin('easy-settings-for-wordpress-demo/easy-settings-for-wordpress-demo.php');

			// mark as loaded.
			do_action('esfw_test_preparation_loaded');
		}
	}
}
