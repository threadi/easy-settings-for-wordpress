<?php
/**
 * File to handle export of settings.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

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
		add_action( 'admin_action_' . $this->get_action_name(), array( $this, 'run' ) );
	}

	/**
	 * Return the name of the admin action for the export of these settings.
	 *
	 * Contains the slug, so multiple plugins using this package do not run the export of each other.
	 *
	 * @return string
	 */
	public function get_action_name(): string {
		return 'esfw_settings_export_' . sanitize_key( $this->get_settings_obj()->get_slug() );
	}

	/**
	 * Return the nonce action for the export of these settings.
	 *
	 * @return string
	 */
	private function get_nonce_action(): string {
		return 'esfw-settings-export-' . sanitize_key( $this->get_settings_obj()->get_slug() );
	}

	/**
	 * Run the export and
	 *
	 * @return void
	 */
	public function run(): void {
		// check referer.
		check_admin_referer( $this->get_nonce_action(), 'nonce' );

		// bail if capability is not given.
		if ( ! current_user_can( $this->get_settings_obj()->get_capability() ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ), 403 ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- uses the WordPress core translation.
		}

		// get the settings as array.
		$settings = $this->get_settings_obj()->get_settings();

		// bail if list is empty.
		if ( empty( $settings ) ) {
			wp_safe_redirect( (string) wp_get_referer() );
			exit;
		}

		// get the data to export.
		$export_settings = $this->get_export_data();

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
				'action' => $this->get_action_name(),
				'nonce'  => wp_create_nonce( $this->get_nonce_action() ),
			),
			get_admin_url() . 'admin.php'
		);
	}

	/**
	 * Return the settings to export as a "name => value" map.
	 *
	 * Skips settings that opted out of export and applies the export filter –
	 * this is the exact payload run() streams as a download.
	 *
	 * @return array<string,mixed>
	 */
	public function get_export_data(): array {
		$export_settings = array();

		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			if ( $setting->is_export_prevented() ) {
				continue;
			}
			$export_settings[ $setting->get_name() ] = $setting->get_value();
		}

		/**
		 * Filter the exported settings.
		 *
		 * @since 1.14.0 Available since 1.14.0.
		 *
		 * @param array<string,mixed> $export_settings The settings to export.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_export_settings', $export_settings );
	}
}
