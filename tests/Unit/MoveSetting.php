<?php
/**
 * Test to move a setting before or after another one.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test Setting::move_before_setting() and Setting::move_after_setting().
 */
class MoveSetting extends easySettingsForWordPressTest {
	/**
	 * The settings object under test.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * Set up a fresh settings object with the settings "a" to "e" for each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->settings_obj = new Settings( self::$plugin_handle );
		foreach ( array( 'a', 'b', 'c', 'd', 'e' ) as $name ) {
			$this->settings_obj->add_setting( $name );
		}
	}

	/**
	 * Return the names of all settings in their actual order.
	 *
	 * @return array<int,string>
	 */
	private function get_names(): array {
		return array_map( static fn( Setting $setting ): string => $setting->get_name(), $this->settings_obj->get_settings() );
	}

	/**
	 * Return a setting by its name.
	 *
	 * @param string $name The name.
	 *
	 * @return Setting
	 */
	private function get( string $name ): Setting {
		$setting = $this->settings_obj->get_setting( $name );
		$this->assertInstanceOf( Setting::class, $setting );
		return $setting;
	}

	/**
	 * Provide the moves with the expected order.
	 *
	 * @return iterable
	 */
	public function get_moves(): iterable {
		yield 'before: backward' => array( 'move_before_setting', 'd', 'b', array( 'a', 'd', 'b', 'c', 'e' ) );
		yield 'before: forward' => array( 'move_before_setting', 'a', 'd', array( 'b', 'c', 'a', 'd', 'e' ) );
		yield 'before: to the start' => array( 'move_before_setting', 'e', 'a', array( 'e', 'a', 'b', 'c', 'd' ) );
		yield 'before: already there' => array( 'move_before_setting', 'b', 'c', array( 'a', 'b', 'c', 'd', 'e' ) );
		yield 'before: itself' => array( 'move_before_setting', 'c', 'c', array( 'a', 'b', 'c', 'd', 'e' ) );
		yield 'after: backward' => array( 'move_after_setting', 'd', 'b', array( 'a', 'b', 'd', 'c', 'e' ) );
		yield 'after: forward' => array( 'move_after_setting', 'a', 'd', array( 'b', 'c', 'd', 'a', 'e' ) );
		yield 'after: to the end' => array( 'move_after_setting', 'a', 'e', array( 'b', 'c', 'd', 'e', 'a' ) );
		yield 'after: already there' => array( 'move_after_setting', 'c', 'b', array( 'a', 'b', 'c', 'd', 'e' ) );
		yield 'after: itself' => array( 'move_after_setting', 'c', 'c', array( 'a', 'b', 'c', 'd', 'e' ) );
	}

	/**
	 * A setting is placed directly before or after the target.
	 *
	 * @param string            $method The method to call.
	 * @param string            $name The name of the setting to move.
	 * @param string            $target The name of the target setting.
	 * @param array<int,string> $expected The expected order.
	 *
	 * @dataProvider get_moves
	 * @return void
	 */
	public function test_move( string $method, string $name, string $target, array $expected ): void {
		$this->get( $name )->$method( $this->get( $target ) );

		$this->assertSame( $expected, $this->get_names() );
		$this->assertSame( $this->get( $name ), $this->settings_obj->get_setting( $name ) );
	}

	/**
	 * The order is not changed if the target is not part of the settings.
	 *
	 * @return void
	 */
	public function test_unknown_target_does_not_change_the_order(): void {
		$unknown = new Setting( $this->settings_obj );
		$unknown->set_name( 'unknown' );

		$this->get( 'c' )->move_before_setting( $unknown );
		$this->get( 'c' )->move_after_setting( $unknown );

		$this->assertSame( array( 'a', 'b', 'c', 'd', 'e' ), $this->get_names() );
	}

	/**
	 * A setting which was rejected because of its duplicate name does not replace the original one.
	 *
	 * @return void
	 */
	public function test_rejected_duplicate_does_not_replace_the_original(): void {
		$original  = $this->get( 'b' );
		$duplicate = $this->settings_obj->add_setting( 'b' );
		$this->assertNotSame( $original, $duplicate );

		$duplicate->move_after_setting( $this->get( 'd' ) );
		$this->get( 'e' )->move_before_setting( $duplicate );

		$this->assertSame( array( 'a', 'b', 'c', 'd', 'e' ), $this->get_names() );
		$this->assertSame( $original, $this->settings_obj->get_setting( 'b' ) );
	}

	/**
	 * A setting which is not part of the settings is not added and does not remove another one.
	 *
	 * @return void
	 */
	public function test_unknown_setting_does_not_change_the_order(): void {
		$unknown = new Setting( $this->settings_obj );
		$unknown->set_name( 'unknown' );

		$unknown->move_before_setting( $this->get( 'c' ) );
		$unknown->move_after_setting( $this->get( 'c' ) );

		$this->assertSame( array( 'a', 'b', 'c', 'd', 'e' ), $this->get_names() );
	}
}
