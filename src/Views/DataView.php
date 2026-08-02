<?php
/**
 * File for an object to handle the DataView to show settings in the backend.
 *
 * Only usable with WordPress 7.0 or newer.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Views;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\View_Base;

/**
 * Object to hold handle the DataView to show settings in the backend.
 */
class DataView extends View_Base {
	/**
	 * The internal name of this styling.
	 *
	 * @var string
	 */
	protected string $name = 'dataview';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;

		// use hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js_and_css' ) );
	}

	/**
	 * Add own JS and CSS for the backend.
	 *
	 * @param string $hook The requested hook.
	 * @return void
	 */
	public function add_js_and_css( string $hook ): void {
		// bail if not the menu slug is called.
		if ( ! $this->get_settings_obj()->enqueue_styles_and_scripts( $hook ) ) {
			return;
		}

		// get the asset file.
		$asset_file = $this->get_settings_obj()->get_path() . 'build/index.asset.php';

		// get the "WP_Filesystem".
		$wp_filesystem = Helper::get_wp_filesystem();

		// bail if the file is missing.
		if ( ! $wp_filesystem->exists( $asset_file ) ) {
			return;
		}

		// include it.
		$asset = include $asset_file;

		// add the dialog script as dependency.
		$dependencies   = $asset['dependencies'];
		$dependencies[] = 'easy-dialog-for-wordpress';

		// enqueue the script.
		wp_enqueue_script(
			$this->get_settings_obj()->get_slug() . '-dataview',
			$this->get_settings_obj()->get_url() . 'build/index.js',
			$dependencies,
			$asset['version'],
			array(
				'in_footer' => true,
			)
		);
	}

	/**
	 * Return the configuration used for the dataviews.
	 *
	 * @return array<string,mixed>
	 */
	private function get_configuration(): array {
		// get the translations.
		$translations = $this->settings_obj->get_translations();

		// return the configuration for the view.
		return array(
			'slug'                => $this->get_settings_obj()->get_slug(),
			'title'               => $this->get_settings_obj()->get_title(),
			'fields'              => $this->get_fields(),
			'tabs'                => $this->get_tabs_config(),
			'auto_save'           => $this->get_settings_obj()->get_auto_save(),
			'save_title'          => $translations['save_title'],
			'settings_saved'      => $translations['settings_saved'],
			'settings_save_error' => $translations['settings_save_error'],
		);
	}

	/**
	 * Return a list of all settings.
	 *
	 * @return array<int,mixed>
	 */
	private function get_fields(): array {
		$fields = array();
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			// get the data view settings.
			$dataview = $setting->get_dataview();

			// bail if no data view settings are given.
			if ( empty( $dataview ) ) {
				continue;
			}

			// add the field to the dataview.
			$fields[] = $dataview;
		}

		/**
		 * Filter the list of available fields for dataview.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array<int,mixed> $fields List of fields.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_dataview_fields', $fields );
	}

	/**
	 * Return the (possibly nested) tab tree for the dataview.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_tabs_config(): array {
		// field ids per section.
		$fields_by_section = array();
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			$dataview = $setting->get_dataview();
			if ( empty( $dataview ) ) {
				continue;
			}
			$section = $setting->get_section();
			if ( ! $section instanceof Section ) {
				continue;
			}
			$fields_by_section[ spl_object_id( $section ) ][] = $dataview['id'];
		}

		// collect the root tabs from the settings object AND from every page.
		$root_tabs = array();
		$seen      = array();
		$add_root  = static function ( $tab ) use ( &$root_tabs, &$seen ) {
			if ( $tab instanceof Tab && ! isset( $seen[ spl_object_id( $tab ) ] ) ) {
				$seen[ spl_object_id( $tab ) ] = true;
				$root_tabs[]                   = $tab;
			}
		};

		// tabs attached directly to the settings object.
		foreach ( $this->get_settings_obj()->get_tabs() as $tab ) {
			$add_root( $tab );
		}

		// tabs attached to pages.
		foreach ( $this->get_settings_obj()->get_pages() as $page ) {
			foreach ( $page->get_tabs() as $tab ) {
				$add_root( $tab );
			}
		}

		// build the (possibly nested) tree from the roots.
		$tabs = array();
		foreach ( $root_tabs as $tab ) {
			$tabs[] = $this->build_tab_node( $tab, $fields_by_section );
		}

		/**
		 * Filter the tab tree used for the dataview.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array<int,array<string,mixed>> $tabs List of tabs.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_dataview_tabs', $tabs );
	}

	/**
	 * Build the dataview node for a single tab, recursing into sub-tabs.
	 *
	 * @param Tab                          $tab               The tab.
	 * @param array<int,array<int,string>> $fields_by_section Field ids keyed by section object id.
	 * @return array<string,mixed>
	 */
	private function build_tab_node( Tab $tab, array $fields_by_section ): array {
		$node = array(
			'name'        => $tab->get_name(),
			'label'       => $tab->get_title(),
			'hide_save'   => $tab->is_save_hidden(),
			'description' => $tab->get_description(),
		);

		// use the URL if set.
		$url = $tab->get_url();
		if ( '' !== $url ) {
			$node['url']    = $url;
			$node['target'] = $tab->get_url_target();
			return $node;
		}

		// has sub-tabs -> nest and stop here.
		$sub_tabs = $tab->get_tabs();
		if ( ! empty( $sub_tabs ) ) {
			$node['tabs'] = array();
			foreach ( $sub_tabs as $sub_tab ) {
				$node['tabs'][] = $this->build_tab_node( $sub_tab, $fields_by_section );
			}
			return $node;
		}

		// leaf -> sections with their fields.
		$node['sections'] = array();
		foreach ( $tab->get_sections() as $section ) {
			$node['sections'][] = array(
				'name'   => $section->get_name(),
				'label'  => $section->get_title(),
				'fields' => $fields_by_section[ spl_object_id( $section ) ] ?? array(),
			);
		}

		return $node;
	}

	/**
	 * Output this view.
	 *
	 * @return void
	 */
	public function display(): void {
		echo '<div class="wrap" id="easy-settings-for-wordpress-settings" data-config="' . esc_attr( Helper::get_json( $this->get_configuration() ) ) . '">Loading ..</div>';
	}

	/**
	 * Return whether this view is usable.
	 *
	 * @return bool
	 */
	public function is_usable(): bool {
		global $wp_version;

		// check if the WordPress version is 7.0 or newer.
		$result = version_compare( $wp_version, '7.0', '>=' );

		// bail if version does match.
		if ( $result ) {
			return true;
		}

		// log this as error if this specific view has been requested.
		if ( $this->get_settings_obj()->get_views()->is_view_requested() ) {
			$this->get_settings_obj()->add_error( 'double_section_name', 'DataView requires WordPress 7.0 or newer.' );
		}

		// mark this view as not usable.
		return false;
	}
}
