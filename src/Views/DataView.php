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

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\View_Base;
use Throwable;

/**
 * Objects to handle the DataView to show settings in the backend.
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
		// bail if the menu slug is not called.
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
	 * Return the configuration used for the DataView.
	 *
	 * @return array<string,mixed>
	 */
	private function get_configuration(): array {
		// get the translations.
		$translations = $this->settings_obj->get_translations();

		// return the configuration for the view.
		return array(
			'slug'                    => $this->get_settings_obj()->get_slug(),
			'title'                   => $this->get_settings_obj()->get_title(),
			'fields'                  => $this->get_fields(),
			'tabs'                    => $this->get_tabs_config(),
			'auto_save'               => $this->get_settings_obj()->get_auto_save(),
			'lock_form_on_save'       => $this->get_settings_obj()->should_lock_form_on_save(),
			'save_title'              => $translations['save_title'],
			'settings_saved'          => $translations['settings_saved'],
			'settings_saved_redirect' => $translations['settings_saved_redirect'],
			'settings_saved_reload'   => $translations['settings_saved_reload'],
			'settings_save_error'     => $translations['settings_save_error'],
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

			// special case for fields which render their content themselves.
			if ( 'esfw-table' === $dataview['type'] ) {
				$dataview['content'] = (string) $this->get_field_content( $setting );
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

		// collect the root tabs from the settings object AND from the requested page.
		$root_tabs = array();
		$seen      = array();
		$add_root  = static function ( $tab ) use ( &$root_tabs, &$seen ) {
			if ( $tab instanceof Tab && ! isset( $seen[ spl_object_id( $tab ) ] ) ) {
				$seen[ spl_object_id( $tab ) ] = true;
				$root_tabs[]                   = $tab;
			}
		};

		// tabs attached directly to the settings object.
		$settings_tabs = $this->get_settings_obj()->get_tabs();
		ksort( $settings_tabs );
		foreach ( $settings_tabs as $tab ) {
			$add_root( $tab );
		}

		// get the requested page, the same way the classic view does it.
		$page     = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$page_obj = is_null( $page ) ? false : $this->get_settings_obj()->get_page( $page );

		if ( $page_obj instanceof Page ) {
			// limit the tabs to the ones assigned to the requested page, respecting their position.
			$page_tabs = $page_obj->get_tabs();
			ksort( $page_tabs );
			foreach ( $page_tabs as $tab ) {
				$add_root( $tab );
			}
		} else {
			// no (valid) page requested -> fall back to tabs of every page.
			foreach ( $this->get_settings_obj()->get_pages() as $page_object ) {
				$page_tabs = $page_object->get_tabs();
				ksort( $page_tabs );
				foreach ( $page_tabs as $tab ) {
					$add_root( $tab );
				}
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
	 * @param ?string                      $parent_tab_name   The parent tab's name, if $tab is a sub-tab (null for a root tab).
	 * @return array<string,mixed>
	 */
	private function build_tab_node( Tab $tab, array $fields_by_section, ?string $parent_tab_name = null ): array {
		$node = array(
			'name'        => $tab->get_name(),
			'label'       => $tab->get_title(),
			'hide_save'   => $tab->is_save_hidden(),
			'description' => wp_kses_post( $tab->get_description() ),
			'classes'     => $tab->get_tab_class(),
		);

		// use the URL if set.
		$url = $tab->get_url();
		if ( '' !== $url ) {
			$node['url']    = $url;
			$node['target'] = $tab->get_url_target();
			return $node;
		}

		// a custom callback replaces the standard rendering: capture its output
		// and hand it to the tab as HTML (the same way section callbacks work).
		if ( $tab->has_custom_callback() ) {
			$node['content'] = $this->get_tab_content( $tab, $parent_tab_name );
		}

		// has sub-tabs -> nest and stop here.
		$sub_tabs = $tab->get_tabs();
		if ( ! empty( $sub_tabs ) ) {
			$node['tabs'] = array();
			foreach ( $sub_tabs as $sub_tab ) {
				$node['tabs'][] = $this->build_tab_node( $sub_tab, $fields_by_section, $tab->get_name() );
			}
			return $node;
		}

		// leaf -> sections with their fields.
		$node['sections'] = array();
		foreach ( $tab->get_sections() as $section ) {
			// bail if section is hidden.
			if( $section->is_hidden() ) {
				continue;
			}

			// add the section.
			$node['sections'][] = array(
				'name'        => $section->get_name(),
				'label'       => $section->get_title(),
				'content'     => $this->get_section_content( $section, $tab->get_name(), $parent_tab_name ),
				'fields'      => $fields_by_section[ spl_object_id( $section ) ] ?? array(),
				'collapsible' => $section->is_collapsible(),
				'collapsed'   => $section->is_collapsed(),
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
		// get the translations.
		$translations = $this->get_settings_obj()->get_translations();

		try {
			$config_json = Helper::get_json(
				$this->get_configuration(),
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			);
		} catch ( Throwable $e ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . $translations['dataview_config_failure'] . ' ' . esc_html( $e->getMessage() ) . '</p></div></div>';
			return;
		}

		if ( '' === $config_json ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . $translations['dataview_config_error'] . '</p></div></div>';
			return;
		}

		echo '<div class="wrap" id="easy-settings-for-wordpress-settings">' . wp_kses_post( $this->get_settings_obj()->get_error_help() ) . '</div>';

		// add the script with the JSON config.
		$handle = $this->get_settings_obj()->get_slug() . '-dataview';
		wp_add_inline_script(
			$handle,
			'window.esfwSettingsConfig = ' . $config_json . ';',
			'before'
		);
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

	/**
	 * Return the HTML a custom tab callback produces.
	 *
	 * In the classic view a tab is rendered by its callback (the default one
	 * outputs the tab's sections and fields; a developer-provided one outputs
	 * whatever it wants, e.g., a log table). The DataView renders in React and
	 * cannot run PHP callbacks, so for a custom callback we capture its output
	 * here (this runs in the admin) and hand it to the tab as HTML.
	 *
	 * @param Tab     $tab             The tab.
	 * @param ?string $parent_tab_name The parent tab's name, if $tab is a sub-tab (null for a root tab).
	 * @return string
	 */
	private function get_tab_content( Tab $tab, ?string $parent_tab_name = null ): string {
		$callback = $tab->get_callback();

		// Classic admin UI (most notably WP_List_Table) builds its filter, sort
		// and pagination links with add_query_arg()/remove_query_arg(), which -
		// without an explicit base URL - fall back to the CURRENT request's URL.
		// Since every tab's callback runs once, up front, during the single
		// initial page load (not only when that tab is actually being viewed),
		// those links would otherwise be built against whichever tab happened to
		// be in the URL at that moment and lose the "tab"/"subtab" parameters for
		// every other tab. Make the request look like it targets THIS tab (and,
		// for a sub-tab, its parent) while the callback runs, matching how the
		// DataView reads/writes the URL: "tab" is the root tab, "subtab" is the
		// nested one (see settings-page.js / tab-node.js).
		$original_get         = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$original_request_uri = $_SERVER['REQUEST_URI'] ?? '';

		if ( null === $parent_tab_name ) {
			// root tab: set "tab", make sure no stale "subtab" from the real request leaks in.
			$query_args   = array( 'tab' => $tab->get_name() );
			$request_uri  = remove_query_arg( 'subtab', $original_request_uri );
		} else {
			// sub-tab: keep the parent as "tab", this tab becomes "subtab".
			$query_args  = array(
				'tab'    => $parent_tab_name,
				'subtab' => $tab->get_name(),
			);
			$request_uri = $original_request_uri;
		}

		$_SERVER['REQUEST_URI'] = add_query_arg( $query_args, $request_uri );
		$_GET                   = array_merge( $_GET, $query_args ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( null === $parent_tab_name ) {
			unset( $_GET['subtab'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		try {
			// capture the callback output.
			ob_start();
			$callback();
			$content = ob_get_clean();
		} finally {
			// restore the original request context for the next tab / the rest of the page.
			$_GET                    = $original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$_SERVER['REQUEST_URI']  = $original_request_uri;
		}

		if ( ! $content ) {
			return '';
		}
		return $content;
	}

	/**
	 * Return the HTML a section callback produces.
	 *
	 * @param Section $section         The section.
	 * @param string  $tab_name        The name of the tab this section belongs to.
	 * @param ?string $parent_tab_name The parent tab's name, if $tab_name is a sub-tab (null for a root tab).
	 * @return string
	 */
	private function get_section_content( Section $section, string $tab_name, ?string $parent_tab_name = null ): string {
		$callback = $section->get_callback();

		// same reasoning as get_tab_content(): a section callback can build links
		// (e.g. via a WP_List_Table) with add_query_arg(), which needs the request
		// to look like it targets this section's tab while the callback runs.
		$original_get         = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$original_request_uri = $_SERVER['REQUEST_URI'] ?? '';

		if ( null === $parent_tab_name ) {
			$query_args  = array( 'tab' => $tab_name );
			$request_uri = remove_query_arg( 'subtab', $original_request_uri );
		} else {
			$query_args  = array(
				'tab'    => $parent_tab_name,
				'subtab' => $tab_name,
			);
			$request_uri = $original_request_uri;
		}

		$_SERVER['REQUEST_URI'] = add_query_arg( $query_args, $request_uri );
		$_GET                   = array_merge( $_GET, $query_args ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( null === $parent_tab_name ) {
			unset( $_GET['subtab'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		try {
			// capture the callback output, mirroring the arguments WordPress passes
			// to an add_settings_section() callback.
			ob_start();
			$callback(
				array(
					'id'       => $section->get_name(),
					'title'    => $section->get_title(),
					'callback' => $callback,
				)
			);
			$content = ob_get_clean();
		} finally {
			$_GET                    = $original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$_SERVER['REQUEST_URI']  = $original_request_uri;
		}

		return (string) $content;
	}

	/**
	 * Return the HTML a field's own callback produces.
	 *
	 * Some fields (currently: Table) already fully render themselves,
	 * including working per-entry actions, via their display() method - the
	 * same one the classic view calls. Rather than duplicating that rendering
	 * in React, capture it here the same way tab and section callbacks are
	 * captured.
	 *
	 * @param Setting $setting The setting whose field should render itself.
	 * @return string
	 */
	private function get_field_content( Setting $setting ): string {
		$field = $setting->get_field();
		if ( ! $field instanceof Field_Base ) {
			return '';
		}

		$callback = $field->get_callback();

		// same reasoning as get_tab_content() / get_section_content(): a field
		// that renders a WP_List_Table (or similar) builds its action / filter /
		// pagination links with add_query_arg(), which needs the request to look
		// like it targets this field's tab while the callback runs.
		$original_get         = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$original_request_uri = $_SERVER['REQUEST_URI'] ?? '';
		$original_screen      = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$tab_name        = null;
		$parent_tab_name = null;
		$section         = $setting->get_section();
		if ( $section instanceof Section ) {
			$tab = $section->get_tab();
			if ( $tab instanceof Tab ) {
				$tab_name = $tab->get_name();
				// Detect whether this tab is nested as a sub-tab of another tab.
				foreach ( $this->get_settings_obj()->get_tabs() as $root_tab ) {
					if ( ! $root_tab instanceof Tab ) {
						continue;
					}
					foreach ( $root_tab->get_tabs() as $sub_tab ) {
						if ( $sub_tab instanceof Tab && $sub_tab->get_name() === $tab_name ) {
							$parent_tab_name = $root_tab->get_name();
							break 2;
						}
					}
				}
				// Also check tabs that live under pages.
				if ( null === $parent_tab_name ) {
					foreach ( $this->get_settings_obj()->get_pages() as $page_object ) {
						foreach ( $page_object->get_tabs() as $root_tab ) {
							if ( ! $root_tab instanceof Tab ) {
								continue;
							}
							foreach ( $root_tab->get_tabs() as $sub_tab ) {
								if ( $sub_tab instanceof Tab && $sub_tab->get_name() === $tab_name ) {
									$parent_tab_name = $root_tab->get_name();
									break 3;
								}
							}
						}
					}
				}
			}
		}

		if ( null !== $tab_name ) {
			if ( null === $parent_tab_name ) {
				$query_args  = array( 'tab' => $tab_name );
				$request_uri = remove_query_arg( 'subtab', $original_request_uri );
			} else {
				$query_args  = array(
					'tab'    => $parent_tab_name,
					'subtab' => $tab_name,
				);
				$request_uri = $original_request_uri;
			}

			$_SERVER['REQUEST_URI'] = add_query_arg( $query_args, $request_uri );
			$_GET                   = array_merge( $_GET, $query_args ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( null === $parent_tab_name ) {
				unset( $_GET['subtab'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		// WP_List_Table (used by the Table field) needs a current screen. When
		// DataView builds its config the screen may not be set yet, which makes
		// the list table render intermittently empty or error out. Provide a
		// temporary screen so the capture is stable across reloads.
		$screen_was_set = false;
		if ( null === $original_screen && function_exists( 'set_current_screen' ) ) {
			$screen_id = 'settings_page_' . $this->get_settings_obj()->get_menu_slug();
			set_current_screen( $screen_id );
			$screen_was_set = true;
		}

		$content = '';
		try {
			// capture the callback output, mirroring the $attr argument WordPress
			// passes to a field's display() method elsewhere in this library.
			ob_start();
			$callback( array( 'setting' => $setting ) );
			$captured = ob_get_clean();
			if ( is_string( $captured ) && '' !== $captured ) {
				$content = $captured;
			}
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Never let a single field break the whole DataView config. Surface
			// the error in the field so it is visible in the UI / data-config.
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			$content = '<!-- esfw-table render error: ' . esc_html( $e->getMessage() ) . ' -->';
		} finally {
			$_GET                   = $original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$_SERVER['REQUEST_URI'] = $original_request_uri;
			if ( $screen_was_set && function_exists( 'set_current_screen' ) ) {
				// Restore previous state (null screen).
				$GLOBALS['current_screen'] = $original_screen;
			}
		}

		return $content;
	}
}
