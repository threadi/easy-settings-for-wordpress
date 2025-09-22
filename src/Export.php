<?php
/**
 * File to handle export of settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Button;

/**
 * Initialize the export support.
 */
class Export {

	/**
	 * Instance of actual object.
	 *
	 * @var ?Export
	 */
	private static ?Export $instance = null;

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
	 * @return Export
	 */
	public static function get_instance(): Export {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
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
	 * Add export settings.
	 *
	 * @param Settings $settings_obj The settings object.
	 * @param Section  $section The section where the export should be placed.
	 *
	 * @return void
	 */
	public function add_settings( Settings $settings_obj, Section $section ): void {
		// create export URL.
		$export_url = add_query_arg(
			array(
				'action' => 'settings_export',
				'nonce'  => wp_create_nonce( 'settings-export' ),
			),
			get_admin_url() . 'admin.php'
		);

        // get the translations.
        $translations = Settings::get_instance()->get_translations();

		// create export dialog.
		$dialog = array(
			'title'   => $translations['dialog_export_title'],
			'texts'   => array(
				'<p><strong>' . $translations['dialog_export_text'] . '</strong></p>',
				'<p>' . $translations['dialog_export_text_2'] . '</p>',
			),
			'buttons' => array(
				array(
					'action'  => 'closeDialog();location.href="' . $export_url . '";',
					'variant' => 'primary',
					'text'    => $translations['dialog_export_button'],
				),
				array(
					'action'  => 'closeDialog();',
					'variant' => 'secondary',
					'text'    => $translations['lbl_cancel'],
				),
			),
		);

		// add setting.
		$setting = $settings_obj->add_setting( 'export_settings' );
		$setting->set_section( $section );
		$setting->set_autoload( false );
		$setting->prevent_export( true );
		$field = new Button();
		$field->set_title( $translations['export_title'] );
		$field->set_button_title( $translations['dialog_export_button'] );
		$field->set_button_url( $export_url );
		$field->add_class( 'easy-dialog-for-wordpress' );
		$field->set_custom_attributes( array( 'data-dialog' => (string)wp_json_encode( $dialog ) ) );
		$setting->set_field( $field );
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
		$settings = Settings::get_instance()->get_settings();

		// bail if list is empty.
		if ( empty( $settings ) ) {
			wp_safe_redirect( wp_get_referer() );
			exit;
		}

		// array for the export.
		$export_settings = array();

		// convert this array to a simple one with "setting_name" > "value".
		foreach ( $settings as $settings_obj ) {
			// bail if this setting is not a Setting object.
			if ( ! $settings_obj instanceof Setting ) {
				continue;
			}

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
        $export_settings = apply_filters( Settings::get_instance()->get_slug() . '_settings_export_settings', $export_settings );

		// create filename for JSON-download-file.
		$filename = gmdate( 'YmdHi' ) . '_' . get_option( 'blogname' ) . '_settings.json';
		/**
		 * File the filename for JSON-download of all settings.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param string $filename The generated filename.
		 */
		$filename = apply_filters( Settings::get_instance()->get_slug() . '_settings_export_filename', $filename );

		// set header for response as JSON-download.
		header( 'Content-type: application/json' );
		header( 'Content-Disposition: attachment; filename=' . sanitize_file_name( $filename ) );
		echo wp_json_encode( $export_settings );
		exit;
	}
}
