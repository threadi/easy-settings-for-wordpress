<?php
/**
 * File to handle export of settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the export of settings.
 */
class Export extends Base_Object {

	/**
	 * Constructor for this object.
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
		add_action( 'admin_action_settings_export', array( $this, 'run' ) );
	}

	/**
	 * Run the export and
	 *
	 * @return void
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function run(): void {
		// check referer.
		check_admin_referer( 'settings-export', 'nonce' );

		// get the settings as array.
		$settings = $this->get_settings_obj()->get_settings();

		// bail if list is empty.
		if ( empty( $settings ) ) {
			wp_safe_redirect( (string) wp_get_referer() );
			exit;
		}

		// array for the export.
		$export_settings = array();

		// convert this array to a simple one with "setting_name" > "value".
		foreach ( $settings as $settings_obj ) {
			// bail if export is prevented.
			if ( $settings_obj->is_export_prevented() ) {
				continue;
			}

			// add to export array.
			$export_settings[ $settings_obj->get_name() ] = $settings_obj->get_value();
		}

		/**
		 * Filter the exported settings.
		 *
		 * @since 1.14.0 Available since 1.14.0.
		 *
		 * @param array<string,mixed> $export_settings The settings to export.
		 */
		$export_settings = apply_filters( $this->get_settings_obj()->get_slug() . '_settings_export_settings', $export_settings );

		// create the filename for JSON-download-file.
		$filename = gmdate( 'YmdHi' ) . '_' . get_option( 'blogname' ) . '_settings.json';
		/**
		 * File the filename for JSON-download of all settings.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param string $filename The generated filename.
		 */
		$filename = apply_filters( $this->get_settings_obj()->get_slug() . '_settings_export_filename', $filename );

		// set header for response as JSON-download.
		header( 'Content-type: application/json' );
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
		echo wp_json_encode( $export_settings );
		exit;
	}

	/**
	 * Return the export URL.
	 *
	 * @return string
	 */
	public function get_download_url(): string {
		return add_query_arg(
			array(
				'action' => 'settings_export',
				'nonce'  => wp_create_nonce( 'settings-export' ),
			),
			get_admin_url() . 'admin.php'
		);
	}
}
