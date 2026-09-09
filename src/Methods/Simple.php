<?php
/**
 * File for the simple method to handle settings.
 *
 * Simple means:
 * Every setting will be saved in its own option entry.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Methods;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Fields\FieldTable;
use easySettingsForWordPress\Method_Base;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;

/**
 * Object to handle the simple method to handle settings.
 */
class Simple extends Method_Base {
	/**
	 * The internal name of this method.
	 *
	 * @var string
	 */
	protected string $name = 'simple';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Run these tasks during activation of the plugin.
	 *
	 * @return void
	 */
	public function activation(): void {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// bail if default value is empty.
			if ( ! $setting->is_default_set() ) {
				continue;
			}

			// bail if option is already set.
			if ( false !== get_option( $setting->get_name(), false ) ) {
				continue;
			}

			// add the option.
			add_option( $setting->get_name(), $setting->get_default(), '', $setting->is_autoloaded() );

			// update the option to trigger callbacks.
			update_option( $setting->get_name(), $setting->get_default() );
		}
	}

	/**
	 * Delete all settings.
	 *
	 * @return void
	 */
	public function delete_settings(): void {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// remove our filter.
			remove_filter( 'option_' . $setting->get_name(), array( $this, 'sanitize_option' ) );
			if ( $setting->has_read_callback() ) {
				remove_filter( 'option_' . $setting->get_name(), $setting->get_read_callback() );
			}

			// get the section of this setting.
			$section = $setting->get_section();

			// if a section is set, get its tab to unregister the setting.
			if ( $section instanceof Section ) {
				// get the tab of this section.
				$tab = $section->get_tab();

				// use the tab to unregister this setting.
				if ( $tab instanceof Tab ) {
					// unregister this setting.
					unregister_setting( $tab->get_name(), $setting->get_name() );
				}
			}

			// delete the option.
			delete_option( $setting->get_name() );
		}
	}

	/**
	 * Register settings of all tabs configured within this settings object.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// bail if no settings are set.
		if ( ! $this->get_settings_obj()->has_settings() ) {
			return;
		}

		// Field-table cells have no section of their own (they live inside the
		// table, not in the section list), so the main loop below would treat
		// them as misconfigured. Register them here for the REST API - using
		// their owning table's tab - so the DataView can load and save them.
		// Collect their names so the main loop skips them.
		$handled_cells = array();
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// get the field object.
			$field_obj = $setting->get_field();

			// bail if we do not have a FieldTable field.
			if ( ! $field_obj instanceof FieldTable ) {
				continue;
			}

			// get the section this setting is assigned to.
			$section = $setting->get_section();

			// bail if no section is given.
			if ( ! $section instanceof Section ) {
				continue;
			}

			// get the tab of this section.
			$tab = $section->get_tab();

			// bail if no tab is given.
			if ( ! $tab instanceof Tab ) {
				continue;
			}

			// register every cell of this table under the table's tab.
			foreach ( $field_obj->get_cell_settings_flat() as $cell_setting ) {
				// bail if this setting should not be registered.
				if ( $cell_setting->should_not_be_registered() ) {
					continue;
				}

				// register the single setting.
				$this->register_single_setting( $cell_setting, $tab );

				// add it to the list of cells.
				$handled_cells[ $cell_setting->get_name() ] = true;
			}
		}

		// loop through the settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// bail if setting should not be registered.
			if ( $setting->should_not_be_registered() ) {
				continue;
			}

			// skip field-table cells already registered above.
			if ( isset( $handled_cells[ $setting->get_name() ] ) ) {
				continue;
			}

			// get the section.
			$section = $setting->get_section();

			// bail if section could not be read.
			if ( ! $section instanceof Section ) {
				// a field-table cell without a resolvable tab: skip silently,
				// it was already attempted above.
				if ( true === $setting->get_custom_var( 'esfw_field_table_cell' ) ) {
					continue;
				}

				// log this as error.
				$this->get_settings_obj()->add_error(
					'setting_missing_section',
					'A section is missing for a setting.',
					array(
						'setting' => $setting->get_name(),
					)
				);

				// do nothing more.
				continue;
			}

			// get the tab.
			$tab = $section->get_tab();

			// bail if tab could not be read.
			if ( ! $tab instanceof Tab ) {
				// log this as error.
				$this->get_settings_obj()->add_error(
					'setting_missing_tab',
					'A tab is missing for a setting.',
					array(
						'setting' => $setting->get_name(),
					)
				);

				// do nothing more.
				continue;
			}

			// register the setting.
			$this->register_single_setting( $setting, $tab );
		}

		// check for any updates to the settings.
		$this->get_settings_obj()->maybe_update();
	}

	/**
	 * Register a single setting for the Settings API and the REST API.
	 *
	 * Extracted so both regular (section-bound) settings and FieldTable cells -
	 * which have no section of their own - go through the exact same
	 * registration, schema building and callback wiring.
	 *
	 * @param Setting $setting The setting to register.
	 * @param Tab     $tab     The tab (settings group) to register it under.
	 *
	 * @return void
	 */
	private function register_single_setting( Setting $setting, Tab $tab ): void {
		// collect arguments.
		$args = array(
			'type'         => $setting->get_type(),
			'default'      => $setting->get_default(),
			'show_in_rest' => $setting->get_show_in_rest(),
		);

		// if field is set, add its sanitizing callback.
		$field_obj = $setting->get_field();
		if ( $field_obj instanceof Field_Base ) {
			$args['sanitize_callback'] = $field_obj->get_sanitize_callback();
		}

		// determine the type, refined by the field if it has one.
		$schema = array( 'type' => $setting->get_type() );

		// ... let the field refine it (e.g. object shape for Checkboxes) ...
		if ( $field_obj instanceof Field_Base ) {
			$schema = array_merge( $schema, $field_obj->get_rest_schema() );
		}

		// keep register_setting's own type in sync with the REST schema type.
		if ( isset( $schema['type'] ) ) {
			$args['type'] = $schema['type'];
		}

		// build the REST schema, unless the developer opted out of the REST API.
		if ( $setting->is_show_in_rest() ) {
			// ... and let an explicit show_in_rest schema from the developer win.
			if ( is_array( $args['show_in_rest'] ) && isset( $args['show_in_rest']['schema'] ) ) {
				$schema = array_merge( $schema, $args['show_in_rest']['schema'] );
			}

			// WordPress requires an item schema for arrays and a property schema for objects.
			if ( isset( $schema['type'] ) && 'array' === $schema['type'] && ! isset( $schema['items'] ) ) {
				$schema['items'] = array();
			}
			if ( isset( $schema['type'] ) && 'object' === $schema['type'] && ! isset( $schema['properties'] ) && ! isset( $schema['additionalProperties'] ) ) {
				$schema['additionalProperties'] = true;
			}

			// add the slug marker the DataView JS uses to discover its fields.
			$schema[ $this->get_settings_obj()->get_slug() ] = $field_obj instanceof Field_Base;

			// preserve a developer-provided show_in_rest array (name, prepare_callback, …).
			$show_in_rest = is_array( $args['show_in_rest'] ) ? $args['show_in_rest'] : array();

			// put the schema back, preserving any other show_in_rest keys.
			$show_in_rest['schema'] = $schema;
			$args['show_in_rest']   = $show_in_rest;
		}
		else {
			$args['show_in_rest'] = false;
		}

		// register the setting.
		register_setting(
			$tab->get_name(),
			$setting->get_name(),
			$args
		);

		// sanitize the option before any output.
		add_filter( 'option_' . $setting->get_name(), array( $this, 'sanitize_option' ), 10, 2 );

		// run the custom callback after reading an option.
		if ( $setting->has_read_callback() ) {
			add_filter( 'option_' . $setting->get_name(), $setting->get_read_callback() );
		}

		// run the custom callback before updating an option.
		if ( $setting->has_save_callback() ) {
			add_filter( 'pre_update_option_' . $setting->get_name(), $setting->get_save_callback(), 10, 3 );
		}
	}

	/**
	 * Return whether this method already has settings data saved.
	 *
	 * @return bool
	 */
	public function has_data(): bool {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// bail if at least one setting has an own option entry.
			if ( false !== get_option( $setting->get_name(), false ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Migrate existing settings to this method.
	 *
	 * @param Method_Base $old_method Object of the old method.
	 *
	 * @return void
	 */
	public function migrate( Method_Base $old_method ): void {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			$settings_name = $setting->get_name();

			// read the value using the OLD method's own logic.
			$value = $old_method->get_setting_value( $settings_name );

			// save it as its own option entry.
			update_option( $settings_name, $this->sanitize_option( $value, $settings_name ) );
		}
	}

	/**
	 * Add new or missing settings to the database.
	 *
	 * @return void
	 */
	public function update_settings(): void {
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			if ( ! $setting->is_default_set() ) {
				continue;
			}

			// add any new option.
			$added = add_option( $setting->get_name(), $setting->get_default(), '', $setting->is_autoloaded() );

			// if a new option was added, update it to trigger callbacks.
			if ( $added ) {
				update_option(
					$setting->get_name(),
					$setting->get_default()
				);
			}
		}
	}
}
