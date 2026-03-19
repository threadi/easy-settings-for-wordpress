<?php
/**
 * File to handle import of settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the import of settings.
 */
class Import extends Base_Object {

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
		add_action( 'admin_enqueue_scripts', array( $this, 'add_script' ) );
		add_action( 'wp_ajax_settings_import_file', array( $this, 'import_via_ajax' ) );
	}

	/**
	 * Add import scripts.
	 *
	 * @param string $hook The used hook.
	 *
	 * @return void
	 */
	public function add_script( string $hook ): void {
		// bail if styles and script should not be enqueued.
		if ( ! $this->get_settings_obj()->enqueue_styles_and_scripts( $hook ) ) {
			return;
		}

		// backend-JS.
		wp_enqueue_script(
			'esfw-import-admin',
			$this->get_settings_obj()->get_url() . 'assets/import.js',
			array( 'jquery', 'easy-dialog-for-wordpress' ),
			Helper::get_file_version( $this->get_settings_obj()->get_path() . '/assets/import.js', $this->get_settings_obj() ),
			true
		);

		// get the translations.
		$translations = $this->get_settings_obj()->get_translations();

		// add php-vars to our js-script.
		wp_localize_script(
			'esfw-import-admin',
			'settingsImportJsVars',
			array(
				'ajax_url'                           => admin_url( 'admin-ajax.php' ),
				'settings_import_file_nonce'         => wp_create_nonce( 'settings-import' ),
				'title_settings_import_file_missing' => $translations['title_settings_import_file_missing'],
				'text_settings_import_file_missing'  => $translations['text_settings_import_file_missing'],
				'lbl_ok'                             => $translations['lbl_ok'],
			)
		);
	}

	/**
	 * Run import via AJAX.
	 *
	 * @return void
	 */
	public function import_via_ajax(): void {
		// check nonce.
		check_ajax_referer( 'settings-import', 'nonce' );

		// get the translations.
		$translations = $this->get_settings_obj()->get_translations();

		// create a dialog for response.
		$dialog = array(
			'detail' => array(
				'title'   => $translations['dialog_import_error_title'],
				'texts'   => array(
					'<p><strong>' . $translations['dialog_import_error_text'] . '</strong></p>',
				),
				'buttons' => array(
					array(
						'action'  => 'closeDialog();',
						'variant' => 'primary',
						'text'    => $translations['lbl_ok'],
					),
				),
			),
		);

		// bail if no file is given.
		if ( empty( $_FILES ) ) {
			$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_no_file'] . '</p>';
			wp_send_json( $dialog );
		}

		// bail if file has no size.
		if ( isset( $_FILES['file']['size'] ) && 0 === $_FILES['file']['size'] ) {
			$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_no_size'] . '</p>';
			wp_send_json( $dialog );
		}

		// bail if file type is not JSON.
		if ( isset( $_FILES['file']['type'] ) && 'application/json' !== $_FILES['file']['type'] ) {
			$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_no_json'] . '</p>';
			wp_send_json( $dialog );
		}

		// allow JSON-files.
		add_filter( 'upload_mimes', array( $this, 'allow_json' ) );

		// bail if file type is not JSON.
		if ( isset( $_FILES['file']['name'] ) ) {
			$filetype = wp_check_filetype( sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ) );
			if ( 'json' !== $filetype['ext'] ) {
				$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_no_json_ext'] . '</p>';
				wp_send_json( $dialog );
			}
		}

		// bail if no tmp_name is available.
		if ( ! isset( $_FILES['file']['tmp_name'] ) ) {
			$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_not_saved'] . '</p>';
			wp_send_json( $dialog );
		}

		// bail if uploaded file is not readable.
		if ( isset( $_FILES['file']['tmp_name'] ) && ! file_exists( sanitize_text_field( $_FILES['file']['tmp_name'] ) ) ) {
			$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_not_saved'] . '</p>';
			wp_send_json( $dialog );
		}

		// get WP Filesystem-handler for read the file.
		require_once ABSPATH . '/wp-admin/includes/file.php';
		\WP_Filesystem();
		global $wp_filesystem;
		$file_content = $wp_filesystem->get_contents( sanitize_text_field( wp_unslash( $_FILES['file']['tmp_name'] ) ) );

		// convert JSON to array.
		$settings_array = json_decode( $file_content, true );

		// bail if JSON-code does not contain one of our settings.
		$setting_to_test = false;
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// bail if we have a setting to test found.
			if ( $setting_to_test instanceof Setting ) {
				continue;
			}

			// bail if export is prevented.
			if ( $setting->is_export_prevented() ) {
				continue;
			}

			// use this setting.
			$setting_to_test = $setting;
		}

		// bail if no setting to test was found.
		if ( ! $setting_to_test instanceof Setting ) {
			$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_not_our_json'] . '</p>';
			wp_send_json( $dialog );
		}

		if ( ! isset( $settings_array[ $setting_to_test->get_name() ] ) ) {
			$dialog['detail']['texts'][1] = '<p>' . $translations['dialog_import_error_not_our_json'] . '</p>';
			wp_send_json( $dialog );
		}

		/**
		 * Run additional tasks before running the import of settings.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param array $settings_array The settings to import.
		 */
		do_action( $this->get_settings_obj()->get_slug() . '_settings_import', $settings_array );

		// import the settings.
		foreach ( $settings_array as $field_name => $field_value ) {
			// check if given setting is used in this plugin.
			if ( ! $this->get_settings_obj()->get_setting( $field_name ) ) {
				continue;
			}

			// update this setting.
			update_option( $field_name, $field_value );
		}

		// return info that import was successfully.
		$dialog['detail']['title']                = $translations['dialog_import_success_title'];
		$dialog['detail']['texts'][0]             = '<p><strong>' . $translations['dialog_import_success_text'] . '</strong></p>';
		$dialog['detail']['texts'][1]             = '<p>' . $translations['dialog_import_success_text_2'] . '</p>';
		$dialog['detail']['buttons'][0]['action'] = 'location.reload();';
		wp_send_json( $dialog );
	}

	/**
	 * Allow SVG as file-type.
	 *
	 * @param array<string,string> $file_types List of file types.
	 *
	 * @return array<string,string>
	 */
	public function allow_json( array $file_types ): array {
		$new_filetypes         = array();
		$new_filetypes['json'] = 'application/json';
		return array_merge( $file_types, $new_filetypes );
	}
}
