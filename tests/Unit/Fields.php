<?php
/**
 * Test the fields.
 *
 * @package external-files-in-media-library
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the fields.
 */
class Fields extends easySettingsForWordPressTest {
	/**
	 * The test slug.
	 *
	 * @var string
	 */
	private static string $slug = 'test-slug';

	/**
	 * The test name.
	 *
	 * @var string
	 */
	private static string $name = 'hallo_world';

	/**
	 * The test title.
	 *
	 * @var string
	 */
	private static string $title = 'Hallo World';

	/**
	 * The test description.
	 *
	 * @var string
	 */
	private static string $description = 'My Hallo World field description.';

	/**
	 * The test placeholder.
	 *
	 * @var string
	 */
	private static string $placeholder = 'Hallo World';

	/**
	 * Return the list of fields.
	 *
	 * @return iterable
	 */
	public function get_fields(): iterable {
		foreach ( \easySettingsForWordPress\Helper::get_field_types() as $classname ) {
			// get the settings object.
			$settings_obj = new \easySettingsForWordPress\Settings( self::$plugin_handle );
			$settings_obj->set_slug( self::$slug );

			// create the object.
			$obj = new $classname( $settings_obj );

			// set title.
			$obj->set_title( self::$title );

			// set description.
			$obj->set_description( self::$description );

			// set placeholder.
			if( method_exists( $obj, 'set_placeholder' ) ) {
				$obj->set_placeholder( self::$placeholder );
			}

			// prepare some special fields.
			if( 'FieldTable' === $obj->get_type_name() ) {
				$obj->set_columns( array( 'a' => 'b' ) );
			}
			if( 'SelectPostTypeObject' === $obj->get_type_name() ) {
				$obj->set_endpoint( 'a' );
			}

			// set a test setting.
			$setting_obj = new \easySettingsForWordPress\Setting( $settings_obj );
			$setting_obj->set_name( self::$name );
			$setting_obj->set_field( $obj );
			$obj->set_setting( $setting_obj );

			// return it.
			yield array( $obj );
		}
	}

	/**
	 * Test a single field given by the data provider.
	 *
	 * @param \easySettingsForWordPress\Field_Base $obj Object of the field.
	 *
	 * @dataProvider get_fields
	 * @return void
	 */
	public function test_field( \easySettingsForWordPress\Field_Base $obj ): void {
		// test basic settings.
		$this->assertIsString( $obj->get_title() );
		$this->assertEquals( self::$title, $obj->get_title() );
		$this->assertIsString( $obj->get_description() );
		$this->assertEquals( self::$description, $obj->get_description() );
		$this->assertIsString( $obj->get_type_name() );
		$this->assertNotEmpty( $obj->get_type_name() );
		$this->assertIsObject( $obj->get_setting() );
		$this->assertIsString( $obj->get_placeholder() );
		if( method_exists( $obj, 'set_placeholder' ) ) {
			$this->assertNotEmpty( $obj->get_placeholder() );
			$this->assertEquals( self::$placeholder, $obj->get_placeholder() );
		}

		// get the setting.
		$setting_obj = $obj->get_setting();

		// test it.
		$this->assertInstanceOf( '\easySettingsForWordPress\Setting', $setting_obj );
		$this->assertIsString( $setting_obj->get_name() );
		$this->assertEquals( self::$name, $setting_obj->get_name() );

		// get an empty output.
		ob_start();
		$obj->display( array() );
		$empty_content = ob_get_clean();

		// test it.
		$this->assertIsString( $empty_content );
		$this->assertEmpty( $empty_content );

		// get a valid output with configuration.
		ob_start();
		$obj->display( array( 'setting' => $obj->get_setting() ) );
		$filled_content = ob_get_clean();

		// test it.
		$this->assertIsString( $filled_content );
		$this->assertNotEmpty( $filled_content );
	}
}
