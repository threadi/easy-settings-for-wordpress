<?php
/**
 * Test the settings export and import (value backup + restore).
 *
 * These tests exercise the testable cores Export::get_export_data() and
 * Import::import_data(); the web entry points run()/import_via_ajax() delegate
 * to them but terminate the request (exit / wp_die) and are not unit-tested.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Export;
use easySettingsForWordPress\Import;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test export and import of setting values.
 */
class ExportImport extends easySettingsForWordPressTest {
	/**
	 * The test slug.
	 *
	 * @var string
	 */
	private static string $slug = 'export-import-test';

	/**
	 * The settings object under test.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * Set up a fresh settings object for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->settings_obj = new Settings( self::$plugin_handle );
		$this->settings_obj->set_slug( self::$slug );
	}

	/**
	 * The download URL carries the export action and a nonce.
	 *
	 * @return void
	 */
	public function test_get_download_url_contains_action_and_nonce(): void {
		$url = ( new Export( $this->settings_obj ) )->get_download_url();

		$this->assertStringContainsString( 'action=settings_export', $url );
		$this->assertStringContainsString( 'nonce=', $url );
	}

	/**
	 * allow_json() adds the JSON mime type and keeps the existing ones.
	 *
	 * @return void
	 */
	public function test_allow_json_adds_json_mime(): void {
		$mimes = ( new Import( $this->settings_obj ) )->allow_json( array( 'txt' => 'text/plain' ) );

		$this->assertArrayHasKey( 'json', $mimes );
		$this->assertSame( 'application/json', $mimes['json'] );
		$this->assertArrayHasKey( 'txt', $mimes );
	}

	/**
	 * The export payload contains the saved values of the settings.
	 *
	 * @return void
	 */
	public function test_export_data_contains_saved_values(): void {
		$this->settings_obj->add_setting( 'field_a' );
		$this->settings_obj->add_setting( 'field_b' );
		update_option( 'field_a', 'value-a' );
		update_option( 'field_b', 'value-b' );

		$data = ( new Export( $this->settings_obj ) )->get_export_data();

		$this->assertArrayHasKey( 'field_a', $data );
		$this->assertArrayHasKey( 'field_b', $data );
		$this->assertSame( 'value-a', $data['field_a'] );
		$this->assertSame( 'value-b', $data['field_b'] );
	}

	/**
	 * Settings that opted out of export are not part of the payload.
	 *
	 * @return void
	 */
	public function test_export_data_skips_prevented_settings(): void {
		$this->settings_obj->add_setting( 'public_field' );
		$secret = $this->settings_obj->add_setting( 'secret_field' );
		$secret->prevent_export( true );
		update_option( 'public_field', 'shown' );
		update_option( 'secret_field', 'hidden' );

		$data = ( new Export( $this->settings_obj ) )->get_export_data();

		$this->assertArrayHasKey( 'public_field', $data );
		$this->assertArrayNotHasKey( 'secret_field', $data );
	}

	/**
	 * The export filter can modify the payload.
	 *
	 * @return void
	 */
	public function test_export_data_filter_is_applied(): void {
		$this->settings_obj->add_setting( 'field_a' );
		update_option( 'field_a', 'original' );

		$filter   = self::$slug . '_settings_export_settings';
		$callback = static function ( array $data ): array {
			$data['field_a'] = 'filtered';
			$data['injected'] = 'yes';
			return $data;
		};
		add_filter( $filter, $callback );

		$data = ( new Export( $this->settings_obj ) )->get_export_data();

		remove_filter( $filter, $callback );

		$this->assertSame( 'filtered', $data['field_a'] );
		$this->assertSame( 'yes', $data['injected'] );
	}

	/**
	 * Importing a payload updates the matching known settings.
	 *
	 * @return void
	 */
	public function test_import_data_updates_known_settings(): void {
		$this->settings_obj->add_setting( 'field_a' );

		( new Import( $this->settings_obj ) )->import_data( array( 'field_a' => 'imported' ) );

		$this->assertSame( 'imported', get_option( 'field_a' ) );
	}

	/**
	 * Importing keys that do not belong to this settings object is ignored.
	 *
	 * @return void
	 */
	public function test_import_data_ignores_unknown_settings(): void {
		// no settings registered on the object.
		( new Import( $this->settings_obj ) )->import_data( array( 'not_ours' => 'x' ) );

		$this->assertFalse( get_option( 'not_ours', false ) );
	}

	/**
	 * The import action fires with the settings that are about to be imported.
	 *
	 * @return void
	 */
	public function test_import_data_fires_action(): void {
		$this->settings_obj->add_setting( 'field_a' );

		$received = null;
		$action   = self::$slug . '_settings_import';
		$callback = static function ( $settings_array ) use ( &$received ): void {
			$received = $settings_array;
		};
		add_action( $action, $callback );

		( new Import( $this->settings_obj ) )->import_data( array( 'field_a' => 'imported' ) );

		remove_action( $action, $callback );

		$this->assertIsArray( $received );
		$this->assertArrayHasKey( 'field_a', $received );
	}

	/**
	 * Exporting and then re-importing restores the previous values (roundtrip).
	 *
	 * @return void
	 */
	public function test_roundtrip_export_then_import_restores_values(): void {
		$this->settings_obj->add_setting( 'field_a' );
		$this->settings_obj->add_setting( 'field_b' );
		update_option( 'field_a', 'a-original' );
		update_option( 'field_b', 'b-original' );

		// export the current state.
		$exported = ( new Export( $this->settings_obj ) )->get_export_data();

		// change the stored values.
		update_option( 'field_a', 'a-changed' );
		update_option( 'field_b', 'b-changed' );

		// import the previously exported state.
		( new Import( $this->settings_obj ) )->import_data( $exported );

		// the original values are restored.
		$this->assertSame( 'a-original', get_option( 'field_a' ) );
		$this->assertSame( 'b-original', get_option( 'field_b' ) );
	}
}
