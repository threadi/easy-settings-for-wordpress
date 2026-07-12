<?php
/**
 * File to handle the methods to handle settings we support.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the methods to handle settings we support.
 */
class Methods extends Base_Object {
	/**
	 * The default method.
	 *
	 * @var string
	 */
	private string $method_name = 'simple';

	/**
	 * The actual method object.
	 *
	 * @var Method_Base|false
	 */
	private Method_Base|false $method = false;

	/**
	 * Instance of actual object.
	 *
	 * @var ?Methods
	 */
	private static ?Methods $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return instance of this object as singleton.
	 *
	 * @return Methods
	 */
	public static function get_instance(): Methods {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set the settings object.
	 *
	 * @param Settings $settings_obj The object.
	 *
	 * @return void
	 */
	public function set_settings_obj( Settings $settings_obj ): void {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Return the list of possible styling objects.
	 *
	 * @return array<int,string>
	 */
	private function get_methods_as_objects(): array {
		$list = array(
			'\easySettingsForWordPress\Methods\Simple',
			'\easySettingsForWordPress\Methods\One',
		);

		/**
		 * Filter the list of possible styling for settings.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array $list The list.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_methods', $list );
	}

	/**
	 * Set the method to use by its name.
	 *
	 * @param string $method_name The method name.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function set_method( string $method_name ): void {
		$this->method_name = $method_name;
	}

	/**
	 * Return the method to use.
	 *
	 * @return false|Method_Base
	 */
	public function get_method(): false|Method_Base {
		// return the already chosen method.
		if ( $this->method instanceof Method_Base ) {
			return $this->method;
		}

		// get the method.
		$this->method = $this->get_method_by_name( $this->method_name );

		// return the object.
		return $this->method;
	}

	/**
	 * Return the method by its name.
	 *
	 * @param string $requested_method_name The name of the requested method.
	 *
	 * @return Method_Base|false
	 */
	private function get_method_by_name( string $requested_method_name ): Method_Base|false {
		// get the method by the configured method name.
		foreach ( $this->get_methods_as_objects() as $method_name ) {
			// bail if the class name does not exist.
			if ( ! class_exists( $method_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $method_name( $this->get_settings_obj() );

			// bail if an object is not Schedules_Base.
			if ( ! $obj instanceof Method_Base ) {
				continue;
			}

			// bail if name does not match.
			if ( $obj->get_name() !== $requested_method_name ) {
				continue;
			}

			// return this object.
			return $obj;
		}

		// return false as no object could be found.
		return false;
	}

	/**
	 * Migrate the settings from one method to another.
	 *
	 * Hint:
	 * This method has to be called by the plugin, which uses this package for their settings
	 * and want to change the way settings will be saved on an appropriate place.
	 * E.g., during the plugin update.
	 *
	 * @param string $old_method_name The name of the previous method.
	 *
	 * @return void
	 */
	public function migrate_method( string $old_method_name ): void {
		// get the active method.
		$method = $this->get_method();

		// bail if no method could be loaded.
		if ( ! $method instanceof Method_Base ) {
			return;
		}

		// bail if old and new are identical.
		if ( $method->get_name() === $old_method_name ) {
			return;
		}

		// bail if the new method already has its own data - migration already happened.
		if ( $method->has_data() ) {
			return;
		}

		// get the previous method by its name.
		$old_method = $this->get_method_by_name( $old_method_name );

		// bail if old method could not be found.
		if ( ! $old_method instanceof Method_Base ) {
			return;
		}

		// migrate the settings to the new method.
		$method->migrate( $old_method );

		// delete the previous settings.
		$old_method->delete_settings();
	}
}
