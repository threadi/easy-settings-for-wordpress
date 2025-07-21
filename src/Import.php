<?php
/**
 * File to handle import of settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Fields\Button;

/**
 * Initialize the import support.
 */
class Import {

    /**
     * Instance of actual object.
     *
     * @var ?Import
     */
    private static ?Import $instance = null;

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
     * @return Import
     */
    public static function get_instance(): Import {
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
        add_action( 'admin_action_settings_import', array( $this, 'import_via_request' ) );
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
        // bail if page is used where we do not use it.
        if ( ! in_array( $hook, array( 'media-new.php', 'appearance_page_easy-settings-for-wordpress', 'post.php', 'settings_page_' . Settings::get_instance()->get_slug() . '_settings' ), true ) ) {
            return;
        }

        // backend-JS.
        wp_enqueue_script(
            'esfw-import-admin',
            Settings::get_instance()->get_url() . 'Files/import.js',
            array( 'jquery', 'easy-dialog-for-wordpress' ),
            filemtime( __DIR__ . '/Files/import.js' ),
            true
        );

        // add php-vars to our js-script.
        wp_localize_script(
            'esfw-import-admin',
            'settingsImportJsVars',
            array(
                'ajax_url'                           => admin_url( 'admin-ajax.php' ),
                'settings_import_file_nonce'         => wp_create_nonce( 'settings-import' ),
                'title_settings_import_file_missing' => __( 'Required file missing', 'easy-settings-for-wordpress' ),
                'text_settings_import_file_missing'  => __( 'Please choose a JSON-file with settings to import.', 'easy-settings-for-wordpress' ),
                'lbl_ok'                             => __( 'OK', 'easy-settings-for-wordpress' ),
            )
        );
    }

    /**
     * Add import settings.
     *
     * @param Settings $settings_obj The settings object.
     * @param Section  $section The section where the import should be placed.
     *
     * @return void
     */
    public function add_settings( Settings $settings_obj, Section $section ): void {
        // create import dialog.
        $dialog = array(
            'title'   => __( 'Import plugin settings', 'easy-settings-for-wordpress' ),
            'texts'   => array(
                '<p><strong>' . __( 'Click on the button below to chose your JSON-file with the settings.', 'easy-settings-for-wordpress' ) . '</strong></p>',
                '<input type="file" accept="application/json" name="import_settings_file" id="import_settings_file">',
            ),
            'buttons' => array(
                array(
                    'action'  => 'settings_import_file();',
                    'variant' => 'primary',
                    'text'    => __( 'Import now', 'easy-settings-for-wordpress' ),
                ),
                array(
                    'action'  => 'closeDialog();',
                    'variant' => 'secondary',
                    'text'    => __( 'Cancel', 'easy-settings-for-wordpress' ),
                ),
            ),
        );

        // add setting.
        $setting = $settings_obj->add_setting( 'import_settings' );
        $setting->set_section( $section );
        $setting->set_autoload( false );
        $setting->prevent_export( true );
        $field = new Button();
        $field->set_title( __( 'Import', 'easy-settings-for-wordpress' ) );
        $field->set_button_title( __( 'Import now', 'easy-settings-for-wordpress' ) );
        $field->add_class( 'easy-dialog-for-wordpress' );
        $field->set_custom_attributes( array( 'data-dialog' => wp_json_encode( $dialog ) ) );
        $setting->set_field( $field );
    }

    /**
     * Run import via request.
     *
     * @return void
     */
    public function import_via_ajax(): void {
        // check nonce.
        check_ajax_referer( 'settings-import', 'nonce' );

        // create dialog for response.
        $dialog = array(
            'detail' => array(
                'title'   => __( 'Error during import', 'easy-settings-for-wordpress' ),
                'texts'   => array(
                    '<p><strong>' . __( 'The file could not be imported!', 'easy-settings-for-wordpress' ) . '</strong></p>',
                ),
                'buttons' => array(
                    array(
                        'action'  => 'closeDialog();',
                        'variant' => 'primary',
                        'text'    => __( 'OK', 'easy-settings-for-wordpress' ),
                    ),
                ),
            ),
        );

        // bail if no file is given.
        if ( ! isset( $_FILES ) ) {
            $dialog['detail']['texts'][1] = '<p>' . __( 'No file was uploaded.', 'easy-settings-for-wordpress' ) . '</p>';
            wp_send_json( $dialog );
        }

        // bail if file has no size.
        if ( isset( $_FILES['file']['size'] ) && 0 === $_FILES['file']['size'] ) {
            $dialog['detail']['texts'][1] = '<p>' . __( 'The uploaded file is no size.', 'easy-settings-for-wordpress' ) . '</p>';
            wp_send_json( $dialog );
        }

        // bail if file type is not JSON.
        if ( isset( $_FILES['file']['type'] ) && 'application/json' !== $_FILES['file']['type'] ) {
            $dialog['detail']['texts'][1] = '<p>' . __( 'The uploaded file is not a valid JSON-file.', 'easy-settings-for-wordpress' ) . '</p>';
            wp_send_json( $dialog );
        }

        // allow JSON-files.
        add_filter( 'upload_mimes', array( $this, 'allow_json' ) );

        // bail if file type is not JSON.
        if ( isset( $_FILES['file']['name'] ) ) {
            $filetype = wp_check_filetype( sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ) );
            if ( 'json' !== $filetype['ext'] ) {
                $dialog['detail']['texts'][1] = '<p>' . __( 'The uploaded file does not have the file extension <i>.json</i>.', 'easy-settings-for-wordpress' ) . '</p>';
                wp_send_json( $dialog );
            }
        }

        // bail if no tmp_name is available.
        if ( ! isset( $_FILES['file']['tmp_name'] ) ) {
            $dialog['detail']['texts'][1] = '<p>' . __( 'The uploaded file could not be saved. Contact your hoster about this problem.', 'easy-settings-for-wordpress' ) . '</p>';
            wp_send_json( $dialog );
        }

        // bail if uploaded file is not readable.
        if ( isset( $_FILES['file']['tmp_name'] ) && ! file_exists( sanitize_text_field( $_FILES['file']['tmp_name'] ) ) ) {
            $dialog['detail']['texts'][1] = '<p>' . __( 'The uploaded file could not be saved. Contact your hoster about this problem.', 'easy-settings-for-wordpress' ) . '</p>';
            wp_send_json( $dialog );
        }

        // get WP Filesystem-handler for read the file.
        require_once ABSPATH . '/wp-admin/includes/file.php';
        \WP_Filesystem();
        global $wp_filesystem;
        $file_content = $wp_filesystem->get_contents( sanitize_text_field( wp_unslash( $_FILES['file']['tmp_name'] ) ) );

        // convert JSON to array.
        $settings_array = json_decode( $file_content, ARRAY_A );

        // bail if JSON-code does not contain one of our settings.
        if ( ! isset( $settings_array[ Settings::get_instance()->get_settings()[0]->get_name() ] ) ) {
            $dialog['detail']['texts'][1] = '<p>' . __( 'The uploaded file is not a valid JSON-file with settings for this plugin.', 'easy-settings-for-wordpress' ) . '</p>';
            wp_send_json( $dialog );
        }

        /**
         * Run additional tasks before running the import of settings.
         *
         * @since 2.0.0 Available since 2.0.0.
         */
        do_action( Settings::get_instance()->get_slug() . '_settings_import' );

        // get the settings object.
        $settings_obj = Settings::get_instance();

        // import the settings.
        foreach ( $settings_array as $field_name => $field_value ) {
            // check if given setting is used in this plugin.
            if ( ! $settings_obj->get_setting( $field_name ) ) {
                continue;
            }

            // update this setting.
            update_option( $field_name, $field_value );
        }

        // return that import was successfully.
        $dialog['detail']['title']                = __( 'Settings have been imported', 'easy-settings-for-wordpress' );
        $dialog['detail']['texts'][0]             = '<p><strong>' . __( 'Import has been run successfully.', 'easy-settings-for-wordpress' ) . '</strong></p>';
        $dialog['detail']['texts'][1]             = '<p>' . __( 'The new settings are now active. Click on the button below to reload the page and see the settings.', 'easy-settings-for-wordpress' ) . '</p>';
        $dialog['detail']['buttons'][0]['action'] = 'location.reload();';
        wp_send_json( $dialog );
    }

    /**
     * Allow SVG as file-type.
     *
     * @param array $file_types List of file types.
     *
     * @return array
     */
    public function allow_json( array $file_types ): array {
        $new_filetypes         = array();
        $new_filetypes['json'] = 'application/json';
        return array_merge( $file_types, $new_filetypes );
    }
}
