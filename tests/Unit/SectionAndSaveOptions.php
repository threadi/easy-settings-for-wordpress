<?php
/**
 * Tests for collapsible sections, lock_form_on_save and reload/redirect on save.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Fields\Text;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test section collapse flags and save-related options.
 */
class SectionAndSaveOptions extends easySettingsForWordPressTest {
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
	}

	/**
	 * Section is not collapsible by default.
	 *
	 * @return void
	 */
	public function test_section_default_not_collapsible(): void {
		$section = new Section( $this->settings_obj );
		$section->set_name( 'main' );

		$this->assertFalse( $section->is_collapsible() );
		$this->assertFalse( $section->is_collapsed() );
	}

	/**
	 * set_collapsible( true ) enables collapsible without collapsing.
	 *
	 * @return void
	 */
	public function test_section_set_collapsible(): void {
		$section = new Section( $this->settings_obj );
		$section->set_name( 'main' );
		$section->set_collapsible( true );

		$this->assertTrue( $section->is_collapsible() );
		$this->assertFalse( $section->is_collapsed() );
	}

	/**
	 * set_collapsed( true ) also forces collapsible.
	 *
	 * @return void
	 */
	public function test_section_set_collapsed_implies_collapsible(): void {
		$section = new Section( $this->settings_obj );
		$section->set_name( 'main' );
		$section->set_collapsed( true );

		$this->assertTrue( $section->is_collapsed() );
		$this->assertTrue( $section->is_collapsible() );
	}

	/**
	 * lock_form_on_save defaults to true and can be disabled.
	 *
	 * @return void
	 */
	public function test_lock_form_on_save_default_and_toggle(): void {
		$this->assertTrue( $this->settings_obj->should_lock_form_on_save() );

		$this->settings_obj->set_lock_form_on_save( false );
		$this->assertFalse( $this->settings_obj->should_lock_form_on_save() );

		$this->settings_obj->set_lock_form_on_save( true );
		$this->assertTrue( $this->settings_obj->should_lock_form_on_save() );
	}

	/**
	 * Setting reload_on_save / redirect_on_save defaults and setters.
	 *
	 * @return void
	 */
	public function test_setting_reload_and_redirect_on_save(): void {
		$setting = $this->settings_obj->add_setting( 'my_option' );

		$this->assertFalse( $setting->should_reload_on_save() );
		$this->assertSame( '', $setting->get_redirect_on_save() );

		$setting->set_reload_on_save( true );
		$this->assertTrue( $setting->should_reload_on_save() );

		$setting->set_redirect_on_save( 'https://example.com/after-save' );
		$this->assertSame( 'https://example.com/after-save', $setting->get_redirect_on_save() );
	}

	/**
	 * get_dataview() exposes reload_on_save and redirect_on_save when set.
	 *
	 * @return void
	 */
	public function test_setting_dataview_includes_reload_flags(): void {
		$setting = $this->settings_obj->add_setting( 'my_option' );
		$setting->set_type( 'string' );

		$field = new Text( $this->settings_obj );
		$field->set_title( 'My option' );
		$setting->set_field( $field );

		// without flags: keys absent or falsey depending on implementation.
		$config = $setting->get_dataview();
		$this->assertIsArray( $config );
		$this->assertArrayNotHasKey( 'reload_on_save', $config );
		$this->assertArrayNotHasKey( 'redirect_on_save', $config );

		$setting->set_reload_on_save( true );
		$config = $setting->get_dataview();
		$this->assertTrue( $config['reload_on_save'] );

		$setting->set_redirect_on_save( 'https://example.com/r' );
		$config = $setting->get_dataview();
		$this->assertSame( 'https://example.com/r', $config['redirect_on_save'] );
	}

	/**
	 * Tab can own a collapsible section (integration via add_section).
	 *
	 * @return void
	 */
	public function test_tab_section_collapsible_via_api(): void {
		$page = $this->settings_obj->add_page( 'test-page' );
		$tab  = $page->add_tab( 'general', 10 );
		$tab->set_title( 'General' );

		$section = $tab->add_section( 'secure', 10 );
		$section->set_title( 'Secure settings' );
		$section->set_collapsible( true );
		$section->set_collapsed( true );

		$sections = $tab->get_sections();
		$this->assertNotEmpty( $sections );

		$found = null;
		foreach ( $sections as $s ) {
			if ( 'secure' === $s->get_name() ) {
				$found = $s;
				break;
			}
		}
		$this->assertInstanceOf( Section::class, $found );
		$this->assertTrue( $found->is_collapsible() );
		$this->assertTrue( $found->is_collapsed() );
	}
}
