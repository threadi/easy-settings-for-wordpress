<?php
/**
 * File for an object to handle the DataView to show settings in the backend.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Views;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\View_Base;

/**
 * Object to hold handle the DataView to show settings in the backend.
 */
class DataView extends View_Base {
	/**
	 * The internal name of this styling.
	 *
	 * @var string
	 */
	protected string $name = 'dataview';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;

		// use hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js_and_css' ) );
	}

	/**
	 * Add own JS and CSS for the backend.
	 *
	 * @param string $hook The requested hook.
	 * @return void
	 */
	public function add_js_and_css( string $hook ): void {
		// bail if not the menu slug is called.
		if ( ! $this->get_settings_obj()->enqueue_styles_and_scripts( $hook ) ) {
			return;
		}

		// get the asset file.
		$asset_file = $this->get_settings_obj()->get_path() . 'build/index.asset.php';

		// get the "WP_Filesystem".
		$wp_filesystem = Helper::get_wp_filesystem();

		// bail if the file is missing.
		if ( ! $wp_filesystem->exists( $asset_file ) ) {
			return;
		}

		// include it.
		$asset = include $asset_file;

		// enqueue the script.
		wp_enqueue_script(
			$this->get_settings_obj()->get_slug() . '-dataview',
			$this->get_settings_obj()->get_url() .  'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			array(
				'in_footer' => true,
			)
		);
	}

	/**
	 * Return the configuration used for the dataviews.
	 *
	 * @return array<string,mixed>
	 */
	private function get_configuration(): array {
		return array(
			'slug' => $this->get_settings_obj()->get_slug(),
			'fields' => $this->get_fields(),
			'form' => $this->get_forms()
		);
	}

	/**
	 * Return a list of all settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_fields(): array {
		$fields = array();
		foreach( $this->get_settings_obj()->get_settings() as $setting ) {
			// get the data view settings.
			$dataview = $setting->get_dataview();

			// bail if no data view settings are given.
			if( empty( $dataview ) ) {
				continue;
			}

			// add the field to the dataview.
			$fields[] = $dataview;
		}

		/**
		 * Filter the list of available fields for dataview.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array<string,mixed> $fields List of fields.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_dataview_fields', $fields );
	}

	/**
	 * Return the forms to defined how the fields are presented in dataview.
	 *
	 * @return array<string,mixed>
	 */
	private function get_forms(): array {
		// get all fields.
		$fields = array_column( $this->get_fields(), 'id' );

		// prepare the forms.
		$forms = array(
			'fields' => array(
				array(
					'id' => 'main_settings',
                    'label' => 'Settings',
                    'children' => $fields,
                    'layout' => array( 'type' => 'card', 'withHeader' => false ),
				)
			),
		);

		/**
		 * Filter the list of forms for dataview.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array<string,mixed> $forms List of forms.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_dataview_form', $forms );
	}

	/**
	 * Output this view.
	 *
	 * @return void
	 */
	public function display(): void {
		echo '<div class="wrap" id="easy-settings-for-wordpress-settings" data-config="' . esc_attr( Helper::get_json( $this->get_configuration() ) ) . '">Loading ..</div>';
	}
}
