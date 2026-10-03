<?php
/**
 * Test that readonly fields keep their stored value when settings are saved.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Fields\MultiSelect;
use easySettingsForWordPress\Fields\Text;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the readonly protection in Field_Base::get_sanitize_callback().
 */
class ReadonlyKeepValue extends easySettingsForWordPressTest {
	/**
	 * The test slug.
	 *
	 * @var string
	 */
	private static string $slug = 'readonly-test-slug';

	/**
	 * Name of the option used in these tests.
	 *
	 * @var string
	 */
	private static string $option_name = 'esfw_readonly_test_roles';

	/**
	 * The settings object under test.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * Set up a fresh settings object and a stored value for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->settings_obj = new Settings( self::$plugin_handle );
		$this->settings_obj->set_slug( self::$slug );

		// the stored value which must survive a save of a readonly field.
		update_option( self::$option_name, array( 'editor', 'author' ) );
	}

	/**
	 * Clean up request data, filters and the option after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unset( $_POST['option_page'] );
		remove_all_filters( self::$slug . '_setting_readonly_keep_value' );
		delete_option( self::$option_name );

		parent::tear_down();
	}

	/**
	 * Create a setting with a MultiSelect field.
	 *
	 * @param bool $readonly Whether the field is readonly.
	 *
	 * @return MultiSelect
	 */
	private function get_field( bool $readonly ): MultiSelect {
		$setting = $this->settings_obj->add_setting( self::$option_name );
		$setting->set_type( 'array' );
		$setting->set_default( array() );

		$field = new MultiSelect( $this->settings_obj );
		$field->set_options(
			array(
				'administrator' => 'Administrator',
				'editor'        => 'Editor',
				'author'        => 'Author',
			)
		);
		$field->set_readonly( $readonly );
		$setting->set_field( $field );

		return $field;
	}

	/**
	 * Simulate the POST of a settings form (options.php or method "One").
	 *
	 * @return void
	 */
	private function simulate_form_save(): void {
		$_POST['option_page'] = self::$slug; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Setting::set_field() must assign the setting to the field, otherwise the protection has nothing to read from.
	 *
	 * @return void
	 */
	public function test_field_knows_its_setting(): void {
		$field = $this->get_field( true );

		$this->assertInstanceOf( Setting::class, $field->get_setting() );
		$this->assertSame( self::$option_name, $field->get_setting()->get_name() );
	}

	/**
	 * A readonly field keeps the stored value on a form save, whatever is submitted.
	 *
	 * @return void
	 */
	public function test_readonly_keeps_stored_value_on_form_save(): void {
		$field = $this->get_field( true );
		$this->simulate_form_save();

		$callback = $field->get_sanitize_callback();

		// the old JSON string from the hidden field (the original bug).
		$this->assertSame( array( 'editor', 'author' ), $callback( '["editor","author"]' ) );

		// a manipulated request trying to grant more rights.
		$this->assertSame( array( 'editor', 'author' ), $callback( array( 'administrator' ) ) );

		// nothing submitted at all (disabled field).
		$this->assertSame( array( 'editor', 'author' ), $callback( null ) );
	}

	/**
	 * The protection also applies to a custom sanitize callback set by a developer.
	 *
	 * @return void
	 */
	public function test_readonly_keeps_stored_value_with_custom_callback(): void {
		$field = $this->get_field( true );
		$field->set_sanitize_callback( static fn(): array => array( 'custom' ) );
		$this->simulate_form_save();

		$this->assertSame( array( 'editor', 'author' ), call_user_func( $field->get_sanitize_callback(), array( 'administrator' ) ) );
	}

	/**
	 * Without a form save (update_option() in code, import), a readonly field is sanitized normally.
	 *
	 * @return void
	 */
	public function test_readonly_sanitizes_normally_outside_form_save(): void {
		$field = $this->get_field( true );

		$this->assertSame( array( 'administrator' ), call_user_func( $field->get_sanitize_callback(), array( 'administrator' ) ) );
	}

	/**
	 * A field which is not readonly is sanitized normally, also on a form save.
	 *
	 * @return void
	 */
	public function test_not_readonly_sanitizes_normally_on_form_save(): void {
		$field = $this->get_field( false );
		$this->simulate_form_save();

		$callback = $field->get_sanitize_callback();

		$this->assertSame( array( 'administrator' ), $callback( array( 'administrator' ) ) );

		// values not in the options are still removed.
		$this->assertSame( array( 'editor' ), $callback( array( 'editor', 'hacker' ) ) );
	}

	/**
	 * The filter can switch off the protection.
	 *
	 * @return void
	 */
	public function test_filter_can_disable_protection(): void {
		$field = $this->get_field( true );
		$this->simulate_form_save();
		add_filter( self::$slug . '_setting_readonly_keep_value', '__return_false' );

		$this->assertSame( array( 'administrator' ), call_user_func( $field->get_sanitize_callback(), array( 'administrator' ) ) );
	}

	/**
	 * Readonly set via the "_setting_readonly" filter is evaluated at save time.
	 *
	 * @return void
	 */
	public function test_readonly_via_filter_is_respected(): void {
		$field = $this->get_field( false );
		$this->simulate_form_save();
		add_filter( self::$slug . '_setting_readonly', '__return_true' );

		$result = call_user_func( $field->get_sanitize_callback(), array( 'administrator' ) );
		remove_all_filters( self::$slug . '_setting_readonly' );

		$this->assertSame( array( 'editor', 'author' ), $result );
	}

	/**
	 * A readonly field without an assigned setting (e.g. inner field of a MultiField) is sanitized normally.
	 *
	 * @return void
	 */
	public function test_readonly_without_setting_sanitizes_normally(): void {
		$field = new Text( $this->settings_obj );
		$field->set_readonly( true );
		$this->simulate_form_save();

		$this->assertSame( 'hello', call_user_func( $field->get_sanitize_callback(), '<b>hello</b>' ) );
	}

	/**
	 * If nothing is stored yet, the default of the setting is kept.
	 *
	 * @return void
	 */
	public function test_readonly_falls_back_to_default(): void {
		delete_option( self::$option_name );
		$field = $this->get_field( true );
		$field->get_setting()->set_default( array( 'administrator' ) );
		$this->simulate_form_save();

		$this->assertSame( array( 'administrator' ), call_user_func( $field->get_sanitize_callback(), array( 'editor' ) ) );
	}
}
