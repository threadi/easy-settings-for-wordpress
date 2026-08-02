<?php
/**
 * This file holds the parser to build the settings object graph from a JSON configuration.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Json;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Base_Object;
use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Fields\Button;
use easySettingsForWordPress\Fields\FieldTable;
use easySettingsForWordPress\Fields\MultiField;
use easySettingsForWordPress\Fields\SelectPostTypeObject;
use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\View_Base;

/**
 * Builds Settings -> Page -> Tab -> Section -> Setting -> Field_Base from a decoded JSON
 * configuration that follows settings.schema.json.
 *
 * Usage: Settings::set_json() delegates here. Do not call this class directly unless you
 * need finer control (e.g. applying only part of a configuration).
 */
class Parser extends Base_Object {
	/**
	 * Registry of all settings created so far, keyed by their name.
	 *
	 * Used to resolve "depends" references, which may point to a setting that has not
	 * been created yet at the time the depending on field is parsed (forward reference).
	 *
	 * @var array<string,Setting>
	 */
	private array $setting_registry = array();

	/**
	 * Deferred "depends" entries, resolved after the whole tree has been built.
	 *
	 * @var array<int,array{field:Field_Base,setting_name:string,value:mixed}>
	 */
	private array $deferred_depends = array();

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object to build the graph on.
	 */
	private function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Apply a decoded JSON configuration to the given settings object.
	 *
	 * @param Settings            $settings_obj The settings object to configure.
	 * @param array<string,mixed> $config The decoded configuration (see settings.schema.json).
	 *
	 * @return bool True if no errors occurred, false otherwise (check $settings_obj->get_errors()).
	 */
	public static function apply( Settings $settings_obj, array $config ): bool {
		return ( new self( $settings_obj ) )->run( $config );
	}

	/**
	 * Run the actual parsing.
	 *
	 * @param array<string,mixed> $config The decoded configuration.
	 *
	 * @return bool
	 */
	private function run( array $config ): bool {
		// bail if required top-level keys are missing.
		foreach ( array( 'slug', 'menu_slug', 'tabs' ) as $required_key ) {
			if ( ! isset( $config[ $required_key ] ) ) {
				$this->get_settings_obj()->add_error(
					'json_missing_key',
					sprintf( 'Required top-level key "%s" is missing in the JSON configuration.', $required_key )
				);
				return false;
			}
		}

		// basic settings-object properties.
		$this->get_settings_obj()->set_slug( (string) $config['slug'] );

		if ( isset( $config['plugin_slug'] ) ) {
			$this->get_settings_obj()->set_plugin_slug( (string) $config['plugin_slug'] );
		}
		if ( isset( $config['title'] ) ) {
			$this->get_settings_obj()->set_title( (string) $config['title'] );
		}
		if ( isset( $config['menu_title'] ) ) {
			$this->get_settings_obj()->set_menu_title( (string) $config['menu_title'] );
		}
		if ( isset( $config['menu_parent_slug'] ) ) {
			$this->get_settings_obj()->set_menu_parent_slug( (string) $config['menu_parent_slug'] );
		}
		if ( isset( $config['menu_icon'] ) ) {
			$this->get_settings_obj()->set_menu_icon( (string) $config['menu_icon'] );
		}
		if ( isset( $config['menu_position'] ) ) {
			$this->get_settings_obj()->set_menu_position( (int) $config['menu_position'] );
		}
		if ( isset( $config['capability'] ) ) {
			$this->get_settings_obj()->set_capability( (string) $config['capability'] );
		}
		if ( isset( $config['auto_save'] ) ) {
			$this->get_settings_obj()->set_auto_save( (string) $config['auto_save'] );
		}
		if ( isset( $config['show_settings_link_in_plugin_list'] ) ) {
			$this->get_settings_obj()->show_settings_link_in_plugin_list( (bool) $config['show_settings_link_in_plugin_list'] );
		}
		if ( isset( $config['view'] ) ) {
			$this->get_settings_obj()->set_view( (string) $config['view'] );
		}
		if ( isset( $config['styling'] ) ) {
			$view = $this->get_settings_obj()->get_views()->get_view();
			if ( $view instanceof View_Base ) {
				$view->set_styling( (string) $config['styling'] );
			}
		}

		// setting the menu slug auto-creates the page with this name (see Settings::set_menu_slug()).
		$this->get_settings_obj()->set_menu_slug( (string) $config['menu_slug'] );

		// get the auto-created page. All tabs are attached to it, as this mirrors what
		// register_fields() expects (it walks Settings::get_pages() -> Page::get_tabs()).
		$page = $this->get_settings_obj()->get_page( (string) $config['menu_slug'] );
		if ( ! $page instanceof Page ) {
			$this->get_settings_obj()->add_error(
				'json_page_missing',
				sprintf( 'The page for "menu_slug" "%s" could not be created.', $config['menu_slug'] )
			);
			return false;
		}

		// build the tabs.
		$tabs      = is_array( $config['tabs'] ) ? $config['tabs'] : array();
		$tab_index = 0;
		foreach ( $tabs as $tab_config ) {
			if ( ! is_array( $tab_config ) ) {
				continue;
			}
			$this->build_tab( $page, $tab_config, $tab_index * 10 );
			++$tab_index;
		}

		// resolve the default tab, if set.
		if ( isset( $config['default_tab'] ) ) {
			$default_tab = $page->get_tab( (string) $config['default_tab'] );
			if ( $default_tab instanceof Tab ) {
				$page->set_default_tab( $default_tab );
				$this->get_settings_obj()->set_default_tab( $default_tab );
			} else {
				$this->get_settings_obj()->add_error(
					'json_default_tab_unknown',
					sprintf( 'default_tab "%s" does not match any tab name.', $config['default_tab'] )
				);
			}
		}

		// resolve all "depends" references now that every setting exists.
		foreach ( $this->deferred_depends as $depend ) {
			if ( ! isset( $this->setting_registry[ $depend['setting_name'] ] ) ) {
				$this->get_settings_obj()->add_error(
					'json_depends_unknown_setting',
					sprintf( 'A field depends on unknown setting "%s".', $depend['setting_name'] )
				);
				continue;
			}
			$depend['field']->add_depend( $this->setting_registry[ $depend['setting_name'] ], $depend['value'] );
		}

		// return true if no errors occurred.
		return ! $this->get_settings_obj()->has_errors();
	}

	/**
	 * Build a tab (top-level on a Page, or nested inside another Tab) and its children.
	 *
	 * @param Page|Tab            $parent_object The page (top-level tab) or tab (nested tab) this tab belongs to.
	 * @param array<string,mixed> $tab_config The tab configuration.
	 * @param int                 $position The position among its siblings.
	 *
	 * @return void
	 */
	private function build_tab( Page|Tab $parent_object, array $tab_config, int $position ): void {
		// add the tab.
		$tab = $parent_object->add_tab( isset( $tab_config['name'] ) ? (string) $tab_config['name'] : '', $position );

		// set title.
		if ( isset( $tab_config['title'] ) ) {
			$tab->set_title( (string) $tab_config['title'] );
		}

		// set description.
		if ( isset( $tab_config['description'] ) ) {
			$tab->set_description( (string) $tab_config['description'] );
		}

		// set position.
		if ( isset( $tab_config['position'] ) ) {
			$tab->set_position( (int) $tab_config['position'] );
		}

		// set to show it in menu.
		if ( isset( $tab_config['show_in_menu'] ) ) {
			$tab->set_show_in_menu( (bool) $tab_config['show_in_menu'] );
		}

		// set to hide the save button.
		if ( isset( $tab_config['hide_save'] ) ) {
			$tab->set_hide_save( (bool) $tab_config['hide_save'] );
		}

		// set to remove the link on the tab.
		if ( isset( $tab_config['not_linked'] ) ) {
			$tab->set_not_linked( (bool) $tab_config['not_linked'] );
		}

		// set the URL.
		if ( isset( $tab_config['url'] ) ) {
			$tab->set_url( (string) $tab_config['url'] );
		}

		// set the target URL.
		if ( isset( $tab_config['url_target'] ) ) {
			$tab->set_url_target( (string) $tab_config['url_target'] );
		}

		// add the sections in this tab.
		if ( ! empty( $tab_config['sections'] ) && is_array( $tab_config['sections'] ) ) {
			$section_index = 0;
			foreach ( $tab_config['sections'] as $section_config ) {
				if ( ! is_array( $section_config ) ) {
					continue;
				}
				$this->build_section( $tab, $section_config, $section_index * 10 );
				++$section_index;
			}
		}

		// add nested sub-tabs.
		if ( ! empty( $tab_config['tabs'] ) && is_array( $tab_config['tabs'] ) ) {
			$sub_tab_index = 0;
			foreach ( $tab_config['tabs'] as $sub_tab_config ) {
				if ( ! is_array( $sub_tab_config ) ) {
					continue;
				}
				$this->build_tab( $tab, $sub_tab_config, $sub_tab_index * 10 );
				++$sub_tab_index;
			}
		}
	}

	/**
	 * Build a section and its settings.
	 *
	 * @param Tab                 $tab The tab this section belongs to.
	 * @param array<string,mixed> $section_config The section configuration.
	 * @param int                 $position The position among its siblings.
	 *
	 * @return void
	 */
	private function build_section( Tab $tab, array $section_config, int $position ): void {
		$section = $tab->add_section( isset( $section_config['name'] ) ? (string) $section_config['name'] : '', $position );

		if ( isset( $section_config['title'] ) ) {
			$section->set_title( (string) $section_config['title'] );
		}
		if ( isset( $section_config['hidden'] ) ) {
			$section->set_hidden( (bool) $section_config['hidden'] );
		}

		if ( ! empty( $section_config['settings'] ) && is_array( $section_config['settings'] ) ) {
			foreach ( $section_config['settings'] as $setting_config ) {
				if ( ! is_array( $setting_config ) ) {
					continue;
				}
				$setting = $this->build_setting( $setting_config );
				if ( false !== $setting ) {
					$setting->set_section( $section );
				}
			}
		}
	}

	/**
	 * Build a single setting, including its field.
	 *
	 * Used both for settings inside a section and for settings nested inside a
	 * FieldTable cell (which are not assigned to a section).
	 *
	 * @param array<string,mixed> $setting_config The setting configuration.
	 *
	 * @return false|Setting
	 */
	private function build_setting( array $setting_config ): false|Setting {
		if ( empty( $setting_config['name'] ) ) {
			$this->get_settings_obj()->add_error( 'json_setting_name_missing', 'A setting is missing its "name".' );
			return false;
		}

		$name    = (string) $setting_config['name'];
		$setting = $this->get_settings_obj()->add_setting( $name );

		if ( isset( $setting_config['type'] ) ) {
			$setting->set_type( (string) $setting_config['type'] );
		}
		if ( array_key_exists( 'default', $setting_config ) ) {
			$setting->set_default( $setting_config['default'] );
		}
		if ( isset( $setting_config['help'] ) ) {
			$setting->set_help( (string) $setting_config['help'] );
		}
		if ( isset( $setting_config['autoload'] ) ) {
			$setting->set_autoload( (bool) $setting_config['autoload'] );
		}
		if ( isset( $setting_config['prevent_export'] ) ) {
			$setting->prevent_export( (bool) $setting_config['prevent_export'] );
		}
		if ( isset( $setting_config['show_in_rest'] ) ) {
			$setting->set_show_in_rest( $setting_config['show_in_rest'] );
		}

		if ( ! empty( $setting_config['field'] ) && is_array( $setting_config['field'] ) ) {
			$field = $this->build_field( $setting_config['field'] );
			if ( false !== $field ) {
				$setting->set_field( $field );
			}
		}

		// register for "depends" resolution.
		$this->setting_registry[ $name ] = $setting;

		// return the resulting settings.
		return $setting;
	}

	/**
	 * Build a field object from its configuration, applying type-specific properties.
	 *
	 * @param array<string,mixed> $field_config The field configuration (discriminated by "type").
	 *
	 * @return false|Field_Base
	 */
	private function build_field( array $field_config ): false|Field_Base {
		if ( empty( $field_config['type'] ) ) {
			$this->get_settings_obj()->add_error( 'json_field_type_missing', 'A field is missing its "type".' );
			return false;
		}

		// keep the original (JSON-facing) type for the switch in apply_type_specific_properties().
		$type      = (string) $field_config['type'];
		$field_obj = Helper::get_field_by_type_name( $type, $this->settings_obj );

		if ( ! $field_obj instanceof Field_Base ) {
			$this->get_settings_obj()->add_error(
				'json_field_type_unknown',
				sprintf( 'A field with the type "%s" is unknown.', $type )
			);
			return false;
		}

		// common properties.
		if ( isset( $field_config['title'] ) ) {
			$field_obj->set_title( (string) $field_config['title'] );
		}
		if ( isset( $field_config['description'] ) ) {
			$field_obj->set_description( (string) $field_config['description'] );
		}
		if ( isset( $field_config['readonly'] ) ) {
			$field_obj->set_readonly( (bool) $field_config['readonly'] );
		}
		if ( ! empty( $field_config['depends'] ) && is_array( $field_config['depends'] ) ) {
			foreach ( $field_config['depends'] as $depend_setting_name => $depend_value ) {
				$this->deferred_depends[] = array(
					'field'        => $field_obj,
					'setting_name' => (string) $depend_setting_name,
					'value'        => $depend_value,
				);
			}
		}

		// type-specific properties.
		$this->apply_type_specific_properties( $type, $field_obj, $field_config );

		// return the resulting field object.
		return $field_obj;
	}

	/**
	 * Apply the properties specific to a single field type.
	 *
	 * @param string              $type The field type name (e.g. "Text", "Select", ...).
	 * @param Field_Base          $field_obj The field object to configure.
	 * @param array<string,mixed> $field_config The field configuration.
	 *
	 * @return void
	 */
	private function apply_type_specific_properties( string $type, Field_Base $field_obj, array $field_config ): void {
		switch ( $type ) {
			case 'Text':
			case 'Password':
				if ( isset( $field_config['placeholder'] ) && method_exists( $field_obj, 'set_placeholder' ) ) {
					$field_obj->set_placeholder( (string) $field_config['placeholder'] );
				}
				if ( isset( $field_config['value'] ) ) {
					$field_obj->set_value( $field_config['value'] );
				}
				if ( isset( $field_config['with_label'] ) && method_exists( $field_obj, 'set_with_label' ) ) {
					$field_obj->set_with_label( (bool) $field_config['with_label'] );
				}
				break;

			case 'Textarea':
				if ( isset( $field_config['placeholder'] ) && method_exists( $field_obj, 'set_placeholder' ) ) {
					$field_obj->set_placeholder( (string) $field_config['placeholder'] );
				}
				break;

			case 'Checkbox':
				if ( isset( $field_config['with_label'] ) && method_exists( $field_obj, 'set_with_label' ) ) {
					$field_obj->set_with_label( (bool) $field_config['with_label'] );
				}
				break;

			case 'Checkboxes':
			case 'Radio':
			case 'Select':
			case 'MultiSelect':
			case 'PermalinkSlug':
				if ( isset( $field_config['options'] ) && is_array( $field_config['options'] ) && method_exists( $field_obj, 'set_options' ) ) {
					$field_obj->set_options( $this->normalize_options( $field_config['options'] ) );
				}
				if ( 'MultiSelect' === $type && isset( $field_config['sortable'] ) && method_exists( $field_obj, 'set_sortable' ) ) {
					$field_obj->set_sortable( (bool) $field_config['sortable'] );
				}
				if ( 'PermalinkSlug' === $type && isset( $field_config['list_title'] ) && method_exists( $field_obj, 'set_list_title' ) ) {
					$field_obj->set_list_title( (string) $field_config['list_title'] );
				}
				break;

			case 'Number':
				if ( isset( $field_config['min'] ) && method_exists( $field_obj, 'set_min' ) ) {
					$field_obj->set_min( (int) $field_config['min'] );
				}
				if ( isset( $field_config['max'] ) && method_exists( $field_obj, 'set_max' ) ) {
					$field_obj->set_max( (int) $field_config['max'] );
				}
				if ( isset( $field_config['step'] ) && method_exists( $field_obj, 'set_step' ) ) {
					$field_obj->set_step( (int) $field_config['step'] );
				}
				break;

			case 'File':
				if ( isset( $field_config['add_file_title'] ) && method_exists( $field_obj, 'set_add_file_title' ) ) {
					$field_obj->set_add_file_title( (string) $field_config['add_file_title'] );
				}
				if ( isset( $field_config['remove_file_title'] ) && method_exists( $field_obj, 'set_remove_file_title' ) ) {
					$field_obj->set_remove_file_title( (string) $field_config['remove_file_title'] );
				}
				if ( isset( $field_config['file_types'] ) && is_array( $field_config['file_types'] ) && method_exists( $field_obj, 'set_file_types' ) ) {
					$field_obj->set_file_types( array_map( 'strval', $field_config['file_types'] ) );
				}
				break;

			case 'Files':
				if ( isset( $field_config['add_file_title'] ) && method_exists( $field_obj, 'set_add_file_title' ) ) {
					$field_obj->set_add_file_title( (string) $field_config['add_file_title'] );
				}
				if ( isset( $field_config['file_types'] ) && is_array( $field_config['file_types'] ) && method_exists( $field_obj, 'set_file_types' ) ) {
					$field_obj->set_file_types( array_map( 'strval', $field_config['file_types'] ) );
				}
				break;

			case 'Table':
				if ( isset( $field_config['table_options'] ) && is_array( $field_config['table_options'] ) && method_exists( $field_obj, 'set_table_options' ) ) {
					$field_obj->set_table_options( $field_config['table_options'] );
				}
				break;

			case 'FieldTable':
				if ( $field_obj instanceof FieldTable ) {
					$this->apply_field_table_properties( $field_obj, $field_config );
				}
				break;

			case 'MultiField':
				if ( $field_obj instanceof MultiField ) {
					$this->apply_multi_field_properties( $field_obj, $field_config );
				}
				break;

			case 'Button':
				if ( $field_obj instanceof Button ) {
					$this->apply_button_properties( $field_obj, $field_config );
				}
				break;

			case 'SelectPostTypeObject':
				if ( $field_obj instanceof SelectPostTypeObject ) {
					$this->apply_select_post_type_object_properties( $field_obj, $field_config );
				}
				break;

			case 'Value':
				if ( array_key_exists( 'value', $field_config ) ) {
					$field_obj->set_value( $field_config['value'] );
				}
				break;
		}
	}

	/**
	 * Build a FieldTable: its columns and the settings placed into each cell.
	 *
	 * @param FieldTable          $field_obj The FieldTable field object (Fields\FieldTable).
	 * @param array<string,mixed> $field_config The field configuration.
	 *
	 * @return void
	 */
	private function apply_field_table_properties( FieldTable $field_obj, array $field_config ): void {
		if ( isset( $field_config['columns'] ) && is_array( $field_config['columns'] ) ) {
			$field_obj->set_columns( array_map( 'strval', $field_config['columns'] ) );
		}

		if ( empty( $field_config['cells'] ) || ! is_array( $field_config['cells'] ) ) {
			return;
		}

		foreach ( $field_config['cells'] as $row_index => $row ) {
			// one add_row() call per row, mirroring Fields\FieldTable::add_row().
			$field_obj->add_row();

			if ( ! is_array( $row ) ) {
				continue;
			}

			foreach ( $row as $column_index => $cell_settings ) {
				if ( ! is_array( $cell_settings ) ) {
					continue;
				}

				foreach ( $cell_settings as $cell_setting_config ) {
					if ( ! is_array( $cell_setting_config ) ) {
						continue;
					}

					$cell_setting = $this->build_setting( $cell_setting_config );
					if ( false !== $cell_setting ) {
						$field_obj->add_setting( $cell_setting, (int) $row_index, (int) $column_index );
					}
				}
			}
		}
	}

	/**
	 * Build a MultiField: the nested field template it repeats.
	 *
	 * @param MultiField          $field_obj The MultiField field object (Fields\MultiField).
	 * @param array<string,mixed> $field_config The field configuration.
	 *
	 * @return void
	 */
	private function apply_multi_field_properties( MultiField $field_obj, array $field_config ): void {
		if ( ! empty( $field_config['field'] ) && is_array( $field_config['field'] ) ) {
			$nested_field = $this->build_field( $field_config['field'] );
			if ( false !== $nested_field ) {
				$field_obj->set_field( $nested_field );
			}
		}

		if ( isset( $field_config['quantity'] ) ) {
			$field_obj->set_quantity( (int) $field_config['quantity'] );
		}
	}

	/**
	 * Apply Button-specific properties.
	 *
	 * @param Button              $field_obj The Button field object (Fields\Button).
	 * @param array<string,mixed> $field_config The field configuration.
	 *
	 * @return void
	 */
	private function apply_button_properties( Button $field_obj, array $field_config ): void {
		if ( isset( $field_config['button_title'] ) ) {
			$field_obj->set_button_title( (string) $field_config['button_title'] );
		}
		if ( isset( $field_config['button_url'] ) ) {
			$field_obj->set_button_url( (string) $field_config['button_url'] );
		}
		if ( isset( $field_config['custom_attributes'] ) && is_array( $field_config['custom_attributes'] ) ) {
			$field_obj->set_custom_attributes( array_map( 'strval', $field_config['custom_attributes'] ) );
		}
		if ( isset( $field_config['classes'] ) && is_array( $field_config['classes'] ) ) {
			foreach ( $field_config['classes'] as $class_name ) {
				$field_obj->add_class( (string) $class_name );
			}
		}
		if ( isset( $field_config['data'] ) && is_array( $field_config['data'] ) ) {
			foreach ( $field_config['data'] as $data_key => $data_value ) {
				$field_obj->add_data( (string) $data_key, (string) $data_value );
			}
		}
	}

	/**
	 * Apply SelectPostTypeObject-specific properties.
	 *
	 * @param SelectPostTypeObject $field_obj The SelectPostTypeObject field object (Fields\SelectPostTypeObject).
	 * @param array<string,mixed>  $field_config The field configuration.
	 *
	 * @return void
	 */
	private function apply_select_post_type_object_properties( SelectPostTypeObject $field_obj, array $field_config ): void {
		if ( isset( $field_config['endpoint'] ) ) {
			$field_obj->set_endpoint( (string) $field_config['endpoint'] );
		}
		if ( isset( $field_config['button_title'] ) ) {
			$field_obj->set_button_title( (string) $field_config['button_title'] );
		}
		if ( isset( $field_config['popup_title'] ) ) {
			$field_obj->set_popup_title( (string) $field_config['popup_title'] );
		}
		if ( isset( $field_config['popup_description'] ) ) {
			$field_obj->set_popup_description( (string) $field_config['popup_description'] );
		}
		if ( isset( $field_config['limit'] ) ) {
			$field_obj->set_limit( (int) $field_config['limit'] );
		}
		if ( isset( $field_config['chosen_title'] ) ) {
			$field_obj->set_chosen_title( (string) $field_config['chosen_title'] );
		}
		if ( isset( $field_config['label_title'] ) ) {
			$field_obj->set_label_title( (string) $field_config['label_title'] );
		}
		if ( isset( $field_config['placeholder'] ) ) {
			$field_obj->set_placeholder( (string) $field_config['placeholder'] );
		}
		if ( isset( $field_config['cancel_button_title'] ) ) {
			$field_obj->set_cancel_button_title( (string) $field_config['cancel_button_title'] );
		}
	}

	/**
	 * Normalize an "options" map from JSON (object) into the array shape the field setters expect.
	 *
	 * JSON objects always decode to string keys in PHP; this passes them through as-is, since
	 * Select/MultiSelect/Checkboxes/Radio/PermalinkSlug all accept string (or int|string) keys.
	 *
	 * @param array<string,mixed> $options The options as decoded from JSON.
	 *
	 * @return array<string,mixed>
	 */
	private function normalize_options( array $options ): array {
		return $options;
	}
}
