<?php
/**
 * Basic tests for the methods object.
 *
 * @package external-files-in-media-library
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object for basic tests for the settings object.
 */
class Methods extends easySettingsForWordPressTest {
	/**
	 * Test for a settings object without any settings.
	 *
	 * @return void
	 */
	public function test_default_method(): void {
		$method = ( new \easySettingsForWordPress\Settings( self::$plugin_handle ) )->get_methods()->get_method();
		$this->assertInstanceOf( '\easySettingsForWordPress\Method_Base', $method );
		$this->assertEquals( 'simple', $method->get_name() );
	}
}
