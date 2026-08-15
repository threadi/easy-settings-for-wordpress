<?php
/**
 * This file holds the serializer that turns a settings object graph back into a
 * JSON-shaped configuration array (the inverse of Json\Parser).
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Json;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;

/**
 * Serializes Settings -> Page -> Tab -> Section -> Setting -> Field_Base back into
 * the configuration shape consumed by Json\Parser / Settings::set_json().
 *
 * Scope: this covers the round-trippable structural core (settings-object
 * properties, the tab/section/setting tree incl. nested tabs, external-link tabs,
 * and the common field properties plus options). Field types whose full state is
 * only reachable through private getters (FieldTable cells, MultiField templates,
 * Button data, SelectPostTypeObject internals) and "depends" wiring are not
 * reproduced; add dedicated public getters if you need those in the output.
 */
class Serializer {
	/**
	 * Serialize a settings object into a configuration array.
	 *
	 * @param Settings $settings The settings object to serialize.
	 *
	 * @return array<string,mixed>
	 */
	public static function to_config( Settings $settings ): array {
		// map section object-id => list of its settings (settings only know their section).
		$settings_by_section = array();
		foreach ( $settings->get_settings() as $setting ) {
			$section = $setting->get_section();
			if ( $section instanceof Section ) {
				$settings_by_section[ spl_object_id( $section ) ][] = $setting;
			}
		}

		// required base properties.
		$config = array(
			'slug'      => $settings->get_slug(),
			'menu_slug' => $settings->get_menu_slug(),
		);

		// optional scalar properties (only when set).
		$scalars = array(
			'plugin_slug'       => $settings->get_plugin_slug(),
			'title'             => $settings->get_title(),
			'menu_title'        => $settings->get_menu_title(),
			'menu_parent_slug'  => $settings->get_menu_parent_slug(),
			'capability'        => $settings->get_capability(),
			'auto_save'         => $settings->get_auto_save(),
			'lock_form_on_save' => $settings->should_lock_form_on_save(),
		);
		foreach ( $scalars as $key => $value ) {
			if ( '' !== $value ) {
				$config[ $key ] = $value;
			}
		}

		// default tab (by name).
		$default_tab = $settings->get_default_tab();
		if ( $default_tab instanceof Tab ) {
			$config['default_tab'] = $default_tab->get_name();
		}

		// the tab tree, from the real roots (settings-level + page-level).
		$config['tabs'] = array();
		foreach ( self::collect_root_tabs( $settings ) as $tab ) {
			$config['tabs'][] = self::tab_to_config( $tab, $settings_by_section );
		}

		// return the config.
		return $config;
	}

	/**
	 * Collect the root tabs from the settings object and from every page, de-duplicated.
	 *
	 * @param Settings $settings The settings object.
	 *
	 * @return array<int,Tab>
	 */
	private static function collect_root_tabs( Settings $settings ): array {
		$roots = array();
		$seen  = array();

		$add = static function ( $tab ) use ( &$roots, &$seen ): void {
			if ( $tab instanceof Tab && ! isset( $seen[ spl_object_id( $tab ) ] ) ) {
				$seen[ spl_object_id( $tab ) ] = true;
				$roots[]                       = $tab;
			}
		};

		foreach ( $settings->get_tabs() as $tab ) {
			$add( $tab );
		}
		foreach ( $settings->get_pages() as $page ) {
			foreach ( $page->get_tabs() as $tab ) {
				$add( $tab );
			}
		}

		return $roots;
	}

	/**
	 * Serialize a single tab (and its sections / sub-tabs).
	 *
	 * @param Tab                           $tab The tab.
	 * @param array<int,array<int,Setting>> $settings_by_section Settings keyed by section object id.
	 *
	 * @return array<string,mixed>
	 */
	private static function tab_to_config( Tab $tab, array $settings_by_section ): array {
		$config = array( 'name' => $tab->get_name() );

		if ( '' !== $tab->get_title() ) {
			$config['title'] = $tab->get_title();
		}
		if ( '' !== $tab->get_description() ) {
			$config['description'] = $tab->get_description();
		}
		if ( $tab->is_save_hidden() ) {
			$config['hide_save'] = true;
		}
		if ( $tab->is_not_linked() ) {
			$config['not_linked'] = true;
		}
		if ( '' !== $tab->get_url() ) {
			$config['url'] = $tab->get_url();

			// url_target is only meaningful together with a url.
			if ( '' !== $tab->get_url_target() ) {
				$config['url_target'] = $tab->get_url_target();
			}
		}

		// sections.
		$sections = array();
		foreach ( $tab->get_sections() as $section ) {
			$sections[] = self::section_to_config( $section, $settings_by_section );
		}
		if ( ! empty( $sections ) ) {
			$config['sections'] = $sections;
		}

		// nested sub-tabs.
		$sub_tabs = array();
		foreach ( $tab->get_tabs() as $sub_tab ) {
			$sub_tabs[] = self::tab_to_config( $sub_tab, $settings_by_section );
		}
		if ( ! empty( $sub_tabs ) ) {
			$config['tabs'] = $sub_tabs;
		}

		return $config;
	}

	/**
	 * Serialize a section and its settings.
	 *
	 * @param Section                       $section The section.
	 * @param array<int,array<int,Setting>> $settings_by_section Settings keyed by section object id.
	 *
	 * @return array<string,mixed>
	 */
	private static function section_to_config( Section $section, array $settings_by_section ): array {
		$config = array( 'name' => $section->get_name() );

		if ( '' !== $section->get_title() ) {
			$config['title'] = $section->get_title();
		}
		if ( $section->is_hidden() ) {
			$config['hidden'] = true;
		}
		if ( $section->is_collapsible() ) {
			$config['collapsible'] = true;
		}
		if ( $section->is_collapsed() ) {
			$config['collapsed'] = true;
		}

		$section_settings = $settings_by_section[ spl_object_id( $section ) ] ?? array();
		if ( ! empty( $section_settings ) ) {
			$config['settings'] = array();
			foreach ( $section_settings as $setting ) {
				$config['settings'][] = self::setting_to_config( $setting );
			}
		}

		return $config;
	}

	/**
	 * Serialize a single setting (and its field).
	 *
	 * @param Setting $setting The setting.
	 *
	 * @return array<string,mixed>
	 */
	private static function setting_to_config( Setting $setting ): array {
		$config = array( 'name' => $setting->get_name() );

		if ( '' !== $setting->get_type() ) {
			$config['type'] = $setting->get_type();
		}
		if ( $setting->is_default_set() ) {
			$config['default'] = $setting->get_default();
		}
		if ( '' !== $setting->get_help() ) {
			$config['help'] = $setting->get_help();
		}

		// emitted explicitly so a reparse is a fixed point regardless of the default.
		$config['autoload'] = $setting->is_autoloaded();

		if ( $setting->is_export_prevented() ) {
			$config['prevent_export'] = true;
		}

		$show_in_rest = $setting->get_show_in_rest();
		if ( false !== $show_in_rest ) {
			$config['show_in_rest'] = $show_in_rest;
		}

		$field = $setting->get_field();
		if ( $field instanceof Field_Base ) {
			$config['field'] = self::field_to_config( $field );
		}

		if ( $setting->should_reload_on_save() ) {
			$config['reload_on_save'] = true;
		}
		if ( '' !== $setting->get_redirect_on_save() ) {
			$config['redirect_on_save'] = $setting->get_redirect_on_save();
		}

		return $config;
	}

	/**
	 * Serialize a field's common (round-trippable) properties.
	 *
	 * @param Field_Base $field The field.
	 *
	 * @return array<string,mixed>
	 */
	private static function field_to_config( Field_Base $field ): array {
		$config = array( 'type' => $field->get_type_name() );

		if ( '' !== $field->get_title() ) {
			$config['title'] = $field->get_title();
		}
		if ( '' !== $field->get_description() ) {
			$config['description'] = $field->get_description();
		}
		if ( $field->is_readonly() ) {
			$config['readonly'] = true;
		}

		// options for option-based fields (Select, Radio, Checkboxes, MultiSelect, PermalinkSlug).
		if ( method_exists( $field, 'get_options' ) ) {
			$options = $field->get_options();
			if ( ! empty( $options ) ) {
				$config['options'] = $options;
			}
		}

		return $config;
	}
}
