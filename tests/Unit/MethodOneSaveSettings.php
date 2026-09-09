<?php
/**
 * Tests for Methods\One::save_settings() covering the various data formats
 * that can arrive via $_POST (scalar, list, associative, nested arrays)
 * and the option_page guard.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tests\Unit;

use easySettingsForWordPress\Fields\Checkboxes;
use easySettingsForWordPress\Fields\FieldTable;
use easySettingsForWordPress\Fields\MultiField;
use easySettingsForWordPress\Fields\Text;
use easySettingsForWordPress\Methods\One;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tests\easySettingsForWordPressTest;

/**
 * Object to test the "One" method's save_settings() with realistic $_POST payloads.
 */
class MethodOneSaveSettings extends easySettingsForWordPressTest {
	/**
	 * The settings object under test.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * The tab name used as "option_page" during save.
	 *
	 * @var string
	 */
	private string $tab_name = 'general';

	/**
	 * Set up a settings object with one tab/section and a mix of field types.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->settings_obj = new Settings( self::$plugin_handle );

		$page    = $this->settings_obj->add_page( 'test-page' );
		$tab     = $page->add_tab( $this->tab_name, 10 );
		$section = $tab->add_section( 'main', 10 );

		// a plain scalar field.
		$text_setting = $this->settings_obj->add_setting( 'esfw_text' );
		$text_setting->set_section( $section );
		$text_field = new Text( $this->settings_obj );
		$text_setting->set_field( $text_field );

		// a checkbox-group field (associative sanitize target, classic list submission).
		$checkboxes_setting = $this->settings_obj->add_setting( 'esfw_checkboxes' );
		$checkboxes_setting->set_section( $section );
		$checkboxes_field = new Checkboxes( $this->settings_obj );
		$checkboxes_field->set_options(
			array(
				'opt_a' => 'Option A',
				'opt_b' => 'Option B',
				'opt_c' => 'Option C',
			)
		);
		$checkboxes_setting->set_field( $checkboxes_field );

		// a MultiField of Checkboxes -> produces a *nested* array of arrays,
		// which is exactly the shape that broke array_map('sanitize_text_field', ...).
		$combo_setting = $this->settings_obj->add_setting( 'esfw_combo' );
		$combo_setting->set_section( $section );
		$inner_checkboxes = new Checkboxes( $this->settings_obj );
		$inner_checkboxes->set_options(
			array(
				'x' => 'X',
				'y' => 'Y',
			)
		);
		$combo_field = new MultiField( $this->settings_obj );
		$combo_field->set_field( $inner_checkboxes );
		$combo_field->set_quantity( 2 );
		$combo_setting->set_field( $combo_field );

		// a FieldTable field: the cells are individual settings that have no
		// section of their own (only the table's own setting does).
		$table_setting = $this->settings_obj->add_setting( 'esfw_table' );
		$table_setting->set_section( $section );
		$table_field = new FieldTable( $this->settings_obj );
		$table_field->set_columns( array( 'Name', 'Value' ) );
		$table_field->add_row();

		$cell_1 = $this->settings_obj->add_setting( 'esfw_table_r0_c0' );
		$cell_1->set_field( new Text( $this->settings_obj ) );
		$table_field->add_setting( $cell_1, 0, 0 );

		$cell_2 = $this->settings_obj->add_setting( 'esfw_table_r0_c1' );
		$cell_2->set_field( new Text( $this->settings_obj ) );
		$table_field->add_setting( $cell_2, 0, 1 );

		$table_setting->set_field( $table_field );

		// remove any leftover option from a previous test.
		delete_option( 'esfw_' . $this->settings_obj->get_slug() . '_settings' );
	}

	/**
	 * Reset $_POST after each test so other tests are not affected.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Build the One method instance under test.
	 *
	 * @return One
	 */
	private function get_method(): One {
		return new One( $this->settings_obj );
	}

	/**
	 * A plain scalar value is trimmed and stored as-is.
	 *
	 * @return void
	 */
	public function test_scalar_value_is_saved(): void {
		$_POST = array(
			'option_page'     => $this->tab_name,
			'esfw_text'       => '  hello world  ',
			'esfw_checkboxes' => array(),
			'esfw_combo'      => array(),
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame( 'hello world', $result['esfw_text'] );
	}

	/**
	 * A classic checkbox-group submission (sequential list of checked keys)
	 * is converted into the associative "key => 1" storage format.
	 *
	 * @return void
	 */
	public function test_checkbox_list_is_converted_to_assoc_array(): void {
		$_POST = array(
			'option_page'     => $this->tab_name,
			'esfw_text'       => 'value',
			'esfw_checkboxes' => array( 'opt_a', 'opt_c' ),
			'esfw_combo'      => array(),
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame(
			array(
				'opt_a' => 1,
				'opt_c' => 1,
			),
			$result['esfw_checkboxes']
		);
	}

	/**
	 * An unchecked checkbox group (key entirely absent from $_POST, as browsers
	 * never submit unchecked checkboxes) is stored as an empty array rather
	 * than throwing or leaving stale data behind.
	 *
	 * @return void
	 */
	public function test_missing_checkbox_key_results_in_empty_array(): void {
		$_POST = array(
			'option_page' => $this->tab_name,
			'esfw_text'   => 'value',
			'esfw_combo'  => array(),
			// 'esfw_checkboxes' intentionally not sent at all.
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame( array(), $result['esfw_checkboxes'] );
	}

	/**
	 * A nested array (MultiField wrapping Checkboxes) must not crash and must
	 * be sanitized entry by entry via the inner field's own callback.
	 *
	 * Before the fix this shape caused a TypeError because save_settings()
	 * ran array_map('sanitize_text_field', ...) over the *outer* array, and
	 * sanitize_text_field() cannot accept an array as one of its entries.
	 *
	 * @return void
	 */
	public function test_nested_array_value_is_sanitized_without_error(): void {
		$_POST = array(
			'option_page'     => $this->tab_name,
			'esfw_text'       => 'value',
			'esfw_checkboxes' => array(),
			'esfw_combo'      => array(
				array( 'x' ),
				array( 'x', 'y' ),
			),
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame(
			array(
				array( 'x' => 1 ),
				array(
					'x' => 1,
					'y' => 1,
				),
			),
			$result['esfw_combo']
		);
	}

	/**
	 * FieldTable cells have no section of their own; save_settings() must
	 * still resolve them via their owning table's tab and persist the
	 * per-cell values instead of silently skipping them.
	 *
	 * @return void
	 */
	public function test_field_table_cells_are_saved(): void {
		$_POST = array(
			'option_page'        => $this->tab_name,
			'esfw_text'          => 'value',
			'esfw_checkboxes'    => array(),
			'esfw_combo'         => array(),
			'esfw_table_r0_c0'   => 'Server',
			'esfw_table_r0_c1'   => 'example.com',
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame( 'Server', $result['esfw_table_r0_c0'] );
		$this->assertSame( 'example.com', $result['esfw_table_r0_c1'] );
	}

	/**
	 * Updating a FieldTable cell a second time must overwrite the previously
	 * stored value (the reported symptom: entered values never change).
	 *
	 * @return void
	 */
	public function test_field_table_cells_are_updated_on_resave(): void {
		update_option(
			'esfw_' . $this->settings_obj->get_slug() . '_settings',
			array( 'esfw_table_r0_c0' => 'Old value' )
		);

		$_POST = array(
			'option_page'      => $this->tab_name,
			'esfw_text'        => 'value',
			'esfw_checkboxes'  => array(),
			'esfw_combo'       => array(),
			'esfw_table_r0_c0' => 'New value',
			'esfw_table_r0_c1' => '',
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame( 'New value', $result['esfw_table_r0_c0'] );
	}

	/**
	 * Settings belonging to a different (or missing) option_page must not be
	 * touched, and their previously stored value must be preserved.
	 *
	 * @return void
	 */
	public function test_wrong_option_page_leaves_existing_values_untouched(): void {
		// seed an existing value directly in the merged option.
		update_option(
			'esfw_' . $this->settings_obj->get_slug() . '_settings',
			array(
				'esfw_text'        => 'unchanged',
				'esfw_table_r0_c0' => 'also unchanged',
			)
		);

		$_POST = array(
			'option_page'      => 'some-other-tab',
			'esfw_text'        => 'attempted overwrite',
			'esfw_table_r0_c0' => 'attempted overwrite',
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame( 'unchanged', $result['esfw_text'] );
		$this->assertSame( 'also unchanged', $result['esfw_table_r0_c0'] );
	}

	/**
	 * A completely missing option_page (e.g. malformed request) must not
	 * process any setting either.
	 *
	 * @return void
	 */
	public function test_missing_option_page_saves_nothing(): void {
		update_option(
			'esfw_' . $this->settings_obj->get_slug() . '_settings',
			array( 'esfw_text' => 'unchanged' )
		);

		$_POST = array(
			'esfw_text' => 'attempted overwrite',
		);

		$result = $this->get_method()->save_settings();

		$this->assertSame( 'unchanged', $result['esfw_text'] );
	}
}
