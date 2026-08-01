<?php
/**
 * This file holds the management to view settings in the backend.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
use easySettingsForWordPress\Views\Classic;

defined( 'ABSPATH' ) || exit;

/**
 * Object to manage the view of settings in the backend.
 */
class Views extends Base_Object {
	/**
	 * The default view.
	 *
	 * @var string
	 */
	private string $view_name = 'classic';

	/**
	 * The actual method object.
	 *
	 * @var View_Base|false
	 */
	private View_Base|false $view = false;

	/**
	 * Mark that a specific view has been requested.
	 *
	 * @var bool
	 */
	private bool $view_is_requested = true;

	/**
	 * Constructor, not used as this a Singleton object.
	 *
	 * @param Settings $settings_object The main settings object.
	 */
	public function __construct( Settings $settings_object ) {
		$this->settings_obj = $settings_object;
	}

	/**
	 * Return list of available views.
	 *
	 * @return array<int,string>
	 */
	private function get_views_as_objects(): array {
		$views = array(
			'\easySettingsForWordPress\Views\Classic',
			'\easySettingsForWordPress\Views\DataView',
		);

		/**
		 * Filter the available views to show settings in the backend.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array<int,string> $views List of possible view objects.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_views', $views );
	}

	/**
	 * Return the view to use.
	 *
	 * @return false|View_Base
	 */
	public function get_view(): false|View_Base {
		// return the already chosen method.
		if ( $this->view instanceof View_Base ) {
			return $this->view;
		}

		// get the method.
		$this->view = $this->get_view_by_name( $this->view_name );

		// return the object.
		return $this->view;
	}

	/**
	 * Set the view to use by its name.
	 *
	 * @param string $view_name The view name.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function set_view( string $view_name ): void {
		$this->view_is_requested = true;
		$this->view_name = $view_name;
	}

	/**
	 * Show the configured view.
	 *
	 * @return void
	 */
	public function display(): void {
		// get the view.
		$view = $this->get_view();

		// bail if no view could be loaded.
		if( ! $view instanceof View_Base ) {
			return;
		}

		// return its output.
		$view->display();
	}

	/**
	 * Show the tab content in the actual view.
	 *
	 * @param Tab $tab The tab.
	 *
	 * @return void
	 */
	public function show_content( Tab $tab ): void {
		// get the view.
		$view = $this->get_view();

		// bail if no view could be loaded.
		if( ! $view instanceof View_Base ) {
			return;
		}

		// return its output.
		$view->show_content( $tab );
	}

	/**
	 * Return the method by its name.
	 *
	 * @param string $requested_method_name The name of the requested method.
	 *
	 * @return View_Base
	 */
	private function get_view_by_name( string $requested_method_name ): View_Base {
		// get the method by the configured method name.
		foreach ( $this->get_views_as_objects() as $method_name ) {
			// bail if the class name does not exist.
			if ( ! class_exists( $method_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $method_name( $this->get_settings_obj() );

			// bail if an object is not "View_Base".
			if ( ! $obj instanceof View_Base ) {
				continue;
			}

			// bail if name does not match.
			if ( $obj->get_name() !== $requested_method_name ) {
				continue;
			}

			// bail if view could not be used.
			if( ! $obj->is_usable() ) {
				continue;
			}

			// return this object.
			return $obj;
		}

		// if no view could be found, use the classic view.
		return new Classic( $this->get_settings_obj() );
	}

	/**
	 * Return whether a specific view has been requested.
	 *
	 * @internal Only for internal tasks.
	 *
	 * @return bool
	 */
	public function is_view_requested(): bool {
		return $this->view_is_requested;
	}
}
