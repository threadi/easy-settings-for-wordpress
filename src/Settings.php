<?php
/**
 * This file holds the setting functions for this plugin.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Initialize the settings object.
 */
class Settings {
	/**
	 * The slug.
	 *
	 * @var string
	 */
	private string $slug = '';

	/**
	 * The plugin slug.
	 *
	 * @var string
	 */
	private string $plugin_slug = '';

	/**
	 * List of pages.
	 *
	 * @var array<int,Page>
	 */
	private array $pages = array();

	/**
	 * List of tabs.
	 *
	 * @var array<int,Tab>
	 */
	private array $tabs = array();

	/**
	 * List of settings.
	 *
	 * @var array<int,Setting>
	 */
	private array $settings = array();

	/**
	 * Set the default tab.
	 *
	 * @var Tab|null
	 */
	private ?Tab $default_tab = null;

	/**
	 * The menu title.
	 *
	 * @var string
	 */
	private string $menu_title = '';

	/**
	 * The menu position.
	 *
	 * @var int
	 */
	private int $menu_position = 10;

	/**
	 * The title.
	 *
	 * @var string
	 */
	private string $title = '';

	/**
	 * The menu slug.
	 *
	 * @var string
	 */
	private string $menu_slug = '';

	/**
	 * The parent menu slug.
	 *
	 * @var string
	 */
	private string $menu_parent_slug = 'options-general.php';

	/**
	 * The menu icon.
	 *
	 * @var string
	 */
	private string $menu_icon = '';

	/**
	 * The capability to show and edit settings.
	 *
	 * @var string
	 */
	private string $capability = 'manage_options';

	/**
	 * The callback for the menu.
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * The used URL of this composer package.
	 *
	 * @var string
	 */
	private string $url = '';

	/**
	 * The used path of this composer package.
	 *
	 * @var string
	 */
	private string $path = '';

	/**
	 * The path of the plugin that is using this instance.
	 *
	 * @var string
	 */
	private string $plugin_path;

	/**
	 * Show the settings link in plugin list.
	 *
	 * @var bool
	 */
	private bool $show_settings_link_in_plugin_list = false;

	/**
	 * List of translations.
	 *
	 * @var array<string,string>
	 */
	private array $translations = array();

	/**
	 * Auto-save mode for the DataView.
	 *
	 * - 'off': settings are only saved via the Save button (default).
	 * - 'change': settings are saved automatically (debounced) after every field change.
	 * - 'tab_change': settings are saved automatically whenever the user switches to another tab.
	 *
	 * @var string
	 */
	private string $auto_save = 'off';

	/**
	 * List of errors.
	 *
	 * @var WP_Error|null
	 */
	protected ?WP_Error $errors = null;

	/**
	 * The import object.
	 *
	 * @var Import
	 */
	private Import $import_obj;

	/**
	 * The export object.
	 *
	 * @var Export
	 */
	private Export $export_obj;

	/**
	 * The views object.
	 *
	 * @var Views
	 */
	private Views $views;

	/**
	 * Constructor, not used as this a Singleton object.
	 *
	 * @param string $plugin_path The plugin path (use __FILE__ for it).
	 */
	public function __construct( string $plugin_path ) {
		try {
			$this->plugin_path = $plugin_path;
			$this->path        = trailingslashit( dirname( $plugin_path ) ) . 'vendor/threadi/easy-settings-for-wordpress/';
			$this->url         = trailingslashit( plugins_url( '', $this->path ) ) . 'easy-settings-for-wordpress/';

			// get import and export object.
			$this->import_obj = new Import( $this );
			$this->export_obj = new Export( $this );

			// prepare the methods.
			Methods::get_instance()->set_settings_obj( $this );

			// prepare the views.
			$this->views = new Views( $this );
		} catch ( \Exception $e ) {
			return;
		}
	}

	/**
	 * Initialize the settings.
	 *
	 * @return void
	 */
	public function init(): void {
		// run activation of settings during the plugin activation.
		register_activation_hook( $this->get_plugin_path(), array( $this, 'activation' ) );

		// get the method to use and run their init tasks.
		$base_method = Methods::get_instance()->get_method();

		// bail if no method is set.
		if ( ! $base_method instanceof Method_Base ) {
			return;
		}

		// run its init tasks.
		$base_method->init();

		// initiate import and export.
		$this->get_import_obj()->init();
		$this->get_export_obj()->init();

		// use hooks.
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_dialog' ) );

		// use our own hooks.
		add_filter( $this->get_slug() . '_settings_tab_sections', array( $this, 'sort' ), PHP_INT_MAX );

		// show settings link in plugin list.
		add_filter( 'plugin_action_links_' . plugin_basename( $this->get_plugin_path() ), array( $this, 'add_setting_link' ) );
	}

	/**
	 * Return the slug used for these settings.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Set settings-slug. This is e.g. used for custom filters.
	 *
	 * @param string $slug The settings slug.
	 *
	 * @return void
	 */
	public function set_slug( string $slug ): void {
		$this->slug = $slug;
	}

	/**
	 * Return the plugin slug used for these settings.
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return $this->plugin_slug;
	}

	/**
	 * Set the plugin slug.
	 *
	 * @param string $plugin_slug The given plugin slug.
	 *
	 * @return void
	 */
	public function set_plugin_slug( string $plugin_slug ): void {
		$this->plugin_slug = $plugin_slug;
	}

	/**
	 * Return list of tabs.
	 *
	 * @return array<int,Tab>
	 */
	public function get_tabs(): array {
		$tabs = $this->tabs;

		$instance = $this;
		/**
		 * Filter the list of setting tabs.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param array<int,Tab> $tabs List of tabs.
		 * @param Settings $instance The settings-object.
		 */
		return apply_filters( $this->get_slug() . '_settings_tabs', $tabs, $instance );
	}

	/**
	 * Add a tab with its settings for this setting object.
	 *
	 * @param string|Tab $tab The tab object or its internal name.
	 *
	 * @return Tab
	 */
	public function add_tab( string|Tab $tab ): Tab {
		// set the tab object.
		$tab_obj = $tab;

		// if value is a string, create the tab object first.
		if ( ! $tab_obj instanceof Tab ) {
			$tab_obj = new Tab( $this );
			$tab_obj->set_name( is_string( $tab ) ? $tab : '' );
		}

		// check for a duplicate name among this tab's sub-tabs.
		$name = $tab_obj->get_name();
		if ( '' !== $name && $this->has_sub_tab_with_name( $name ) ) {
			// prepare the message.
			$message = sprintf(
				'A sub-tab with the name "%s" has already been added to this tab.',
				$name
			);

			// log this error.
			$this->add_error( 'double_tab_name', $message, array( 'name' => $name ) );

			// return the tab object.
			return $tab_obj;
		}

		// add the tab to the list of tabs of these settings.
		$this->tabs[] = $tab_obj;

		// return the tab object.
		return $tab_obj;
	}

	/**
	 * Delete the given tab.
	 *
	 * @param Tab $tab_to_delete The tab to delete.
	 *
	 * @return void
	 */
	public function delete_tab( Tab $tab_to_delete ): void {
		foreach ( $this->get_tabs() as $index => $tab ) {
			// bail if tab does not match.
			if ( $tab->get_name() !== $tab_to_delete->get_name() ) {
				continue;
			}

			// remove tab from list.
			unset( $this->tabs[ $index ] );
		}
	}

	/**
	 * Return the title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		$title = $this->title;

		$instance = $this;
		/**
		 * Filter the title of settings object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $title The title.
		 * @param Settings $instance The settings-object.
		 */
		return apply_filters( $this->get_slug() . '_settings_title', $title, $instance );
	}

	/**
	 * Set the title.
	 *
	 * @param string $title The title.
	 *
	 * @return void
	 */
	public function set_title( string $title ): void {
		$this->title = $title;
	}

	/**
	 * Return the menu title.
	 *
	 * @return string
	 */
	public function get_menu_title(): string {
		$menu_title = $this->menu_title;

		$instance = $this;
		/**
		 * Filter the menu title of settings object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $menu_title The menu title.
		 * @param Settings $instance The settings-object.
		 */
		return apply_filters( $this->get_slug() . '_settings_menu_title', $menu_title, $instance );
	}

	/**
	 * Set the menu title.
	 *
	 * @param string $menu_title The menu title.
	 *
	 * @return void
	 */
	public function set_menu_title( string $menu_title ): void {
		$this->menu_title = $menu_title;
	}

	/**
	 * Return the slug.
	 *
	 * @return string
	 */
	public function get_menu_slug(): string {
		$menu_slug = $this->menu_slug;

		$instance = $this;
		/**
		 * Filter the menu slug of settings object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $menu_slug The menu slug.
		 * @param Settings $instance The settings-object.
		 */
		return apply_filters( $this->get_slug() . '_settings_menu_slug', $menu_slug, $instance );
	}

	/**
	 * Set the slug.
	 *
	 * @param string $menu_slug The slug.
	 *
	 * @return void
	 */
	public function set_menu_slug( string $menu_slug ): void {
		$this->menu_slug = $menu_slug;

		// add this as page.
		$this->add_page( $menu_slug );
	}

	/**
	 * Add the menu in the backend where the settings will be shown.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		global $submenu;

		// decide how to add the menu depending on given parent slug.
		switch ( $this->get_menu_parent_slug() ) {
			case 'options-general.php':
				add_options_page(
					$this->get_title(),
					$this->get_menu_title(),
					$this->get_capability(),
					$this->get_menu_slug(),
					$this->get_callback(),
					$this->get_menu_position()
				);
				break;
			case 'admin.php':
				add_menu_page(
					$this->get_title(),
					$this->get_menu_title(),
					$this->get_capability(),
					$this->get_menu_slug(),
					$this->get_callback(),
					$this->get_menu_icon(),
					$this->get_menu_position()
				);

				// check tabs for this setting whether they should be visible in the menu.
				foreach ( $this->get_tabs() as $tab ) {
					// bail if tab should not be visible in the menu.
					if ( ! $tab->is_show_in_menu() ) {
						continue;
					}

					// add the menu item.
					add_submenu_page(
						$this->get_menu_slug(),
						$tab->get_title(),
						$tab->get_title(),
						$this->get_capability(),
						$tab->get_name(),
						$tab->get_callback(),
						6 // TODO make it dynamic.
					);

					// change link in menu if it is an external URL.
					if ( ! empty( $tab->get_url() ) ) {
						foreach ( $submenu as $main_slug => $main_menu ) {
							// bail if main slug is not our settings slug.
							if ( $main_slug !== $this->get_menu_slug() ) {
								continue;
							}

							foreach ( $main_menu as $index => $menu ) {
								// bail if this is not our menu.
								if ( $tab->get_name() !== $menu[2] ) {
									continue;
								}

								$submenu[ $main_slug ][ $index ][2] = $tab->get_url();
							}
						}
					}
				}
				break;
			default:
				add_submenu_page(
					$this->get_menu_parent_slug(),
					$this->get_title(),
					$this->get_menu_title(),
					$this->get_capability(),
					$this->get_menu_slug(),
					$this->get_callback(),
					$this->get_menu_position()
				);
				break;
		}
	}

	/**
	 * Return the views object.
	 *
	 * @return Views
	 */
	public function get_views(): Views {
		return $this->views;
	}

	/**
	 * Show the navigation of settings.
	 *
	 * @return void
	 */
	public function display(): void {
		$this->get_views()->display();
	}

	/**
	 * Return the capability to show and change settings.
	 *
	 * @return string
	 */
	public function get_capability(): string {
		return $this->capability;
	}

	/**
	 * Set the capability to show and change settings.
	 *
	 * @param string $capability The capability.
	 *
	 * @return void
	 */
	public function set_capability( string $capability ): void {
		$this->capability = $capability;
	}

	/**
	 * Return the default tab.
	 *
	 * @return ?Tab
	 */
	public function get_default_tab(): ?Tab {
		return $this->default_tab;
	}

	/**
	 * Set the default tab.
	 *
	 * @param Tab $tab The tab for set as default tab.
	 *
	 * @return void
	 */
	public function set_default_tab( Tab $tab ): void {
		$this->default_tab = $tab;
	}

	/**
	 * Register the fields with its sections, visible in the backend.
	 *
	 * @return void
	 */
	public function register_fields(): void {
		// get the pages.
		$pages = $this->get_pages();

		// loop through the pages and register their settings from their tabs.
		$page_count = count( $pages );
		for ( $p = 0;$p < $page_count; $p++ ) {
			// get the page array entry.
			$page = $pages[ $p ];

			// get the tabs.
			$tabs = $page->get_tabs();

			// loop through the tabs and register their sections and settings.
			foreach ( $tabs as $tab ) {
				foreach ( $tab->get_tabs() as $sub_tab ) {
					$this->register_tab( $sub_tab );
				}
				$this->register_tab( $tab );
			}
		}
	}

	/**
	 * Register tab with its sub-tabs, sections and fields.
	 *
	 * @param Tab $tab The tab to use.
	 *
	 * @return void
	 */
	private function register_tab( Tab $tab ): void {
		// get the sections of this tab.
		$sections = $tab->get_sections();

		if ( function_exists( 'add_settings_section' ) ) {
			// loop through the sections of this tab.
			foreach ( $sections as $section ) {
				// do not show hidden sections.
				if ( $section->is_hidden() ) {
					continue;
				}

				// add the section.
				add_settings_section(
					$section->get_name(),
					$section->get_title(),
					$section->get_callback(),
					$tab->get_name()
				);
			}
		}

		// get the settings for this tab.
		$settings = $this->get_settings_for_tab( $tab );

		// loop through the settings.
		$settings_count = count( $settings );
		for ( $set = 0; $set < $settings_count; $set++ ) {
			// get the settings array entry.
			$setting = $settings[ $set ];

			// get the field object.
			$field = $setting->get_field();

			if ( $field instanceof Field_Base && function_exists( 'add_settings_field' ) && $setting->get_section() instanceof Section ) {
				// add the field for this setting.
				add_settings_field(
					$setting->get_name(),
					$field->get_title(),
					$field->get_callback(),
					$tab->get_name(),
					$setting->get_section()->get_name(),
					array(
						'setting'   => $setting,
						'class'     => sanitize_title( $setting->get_name() ),
						'label_for' => $setting->get_name(),
					)
				);
			}
		}
	}

	/**
	 * Return menu icon.
	 *
	 * @return string
	 */
	public function get_menu_icon(): string {
		$menu_icon = $this->menu_icon;

		$instance = $this;
		/**
		 * Filter the menu slug of settings object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $menu_icon The menu icon.
		 * @param Settings $instance The settings-object.
		 */
		return apply_filters( $this->get_slug() . '_settings_menu_icon', $menu_icon, $instance );
	}

	/**
	 * Set menu icon.
	 *
	 * @param string $menu_icon The menu icon. URL-path to file or "dashicon"-slug.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function set_menu_icon( string $menu_icon ): void {
		$this->menu_icon = $menu_icon;
	}

	/**
	 * Return menu parent slug.
	 *
	 * @return string
	 */
	public function get_menu_parent_slug(): string {
		$parent_menu_slug = $this->menu_parent_slug;

		$instance = $this;
		/**
		 * Filter the menu slug of settings object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $parent_menu_slug The parent menu slug.
		 * @param Settings $instance The settings-object.
		 */
		return apply_filters( $this->get_slug() . '_settings_parent_menu_slug', $parent_menu_slug, $instance );
	}

	/**
	 * Set the menu parent slug.
	 *
	 * @param string $menu_parent_slug The parent menu slug.
	 *
	 * @return void
	 */
	public function set_menu_parent_slug( string $menu_parent_slug ): void {
		$this->menu_parent_slug = $menu_parent_slug;
	}

	/**
	 * Return the callback for the settings menu.
	 *
	 * @return callable
	 */
	private function get_callback(): callable {
		// if callback is empty use our default callback.
		if ( null === $this->callback ) {
			return array( $this, 'display' );
		}

		// return the configured callback.
		return $this->callback;
	}

	/**
	 * Set the callback for the settings menu.
	 *
	 * @param callable $callback The callback.
	 *
	 * @return void
	 */
	public function set_callback( callable $callback ): void {
		$this->callback = $callback;
	}

	/**
	 * Return the setting object for given setting by name.
	 *
	 * @param string $option The settings internal name.
	 *
	 * @return false|Setting
	 */
	public function get_setting( string $option ): false|Setting {
		foreach ( $this->get_settings() as $setting ) {
			// bail if setting has not the searched name.
			if ( $option !== $setting->get_name() ) {
				continue;
			}

			// return the object.
			return $setting;
		}

		// return false if no setting with the given name could be found.
		return false;
	}

	/**
	 * Return the actual settings.
	 *
	 * @return array<int,Setting>
	 */
	public function get_settings(): array {
		return $this->settings;
	}

	/**
	 * Run this during activation of the plugin.
	 *
	 * @return void
	 */
	public function activation(): void {
		// get the default method.
		$method = Methods::get_instance()->get_method();

		// bail if method could not be loaded.
		if ( ! $method instanceof Method_Base ) {
			return;
		}

		// delete the settings saved by this method.
		$method->activation();
	}

	/**
	 * Delete all settings.
	 *
	 * @return void
	 */
	public function delete_settings(): void {
		// get the default method.
		$method = Methods::get_instance()->get_method();

		// bail if method could not be loaded.
		if ( ! $method instanceof Method_Base ) {
			return;
		}

		// delete the settings saved by this method.
		$method->delete_settings();
	}

	/**
	 * Add single setting.
	 *
	 * @param string|Setting $setting The settings object or its internal name.
	 *
	 * @return Setting
	 */
	public function add_setting( string|Setting $setting ): Setting {
		// set the setting object.
		$setting_obj = $setting;

		// if value is a string, create the tab object first.
		if ( ! $setting_obj instanceof Setting ) {
			$setting_obj = new Setting( $this );
			$setting_obj->set_name( is_string( $setting ) ? $setting : '' );
		}

		// check for a duplicate name across all already added settings.
		$name = $setting_obj->get_name();
		if ( '' !== $name && $this->has_setting_with_name( $name ) ) {
			$message = sprintf(
				'A setting with the name "%s" has already been added. Setting names must be unique across the whole settings object',
				$name
			);

			// log this error.
			$this->add_error(
				'double_setting_name',
				$message
			);

			// return the setting object.
			return $setting_obj;
		}

		// add the setting to the list of settings of this tab.
		$this->settings[] = $setting_obj;

		// return the tab object.
		return $setting_obj;
	}

	/**
	 * Check whether a setting with the given name has already been added.
	 *
	 * @param string $name The setting name to check.
	 *
	 * @return bool
	 */
	private function has_setting_with_name( string $name ): bool {
		foreach ( $this->settings as $existing_setting ) {
			if ( $existing_setting->get_name() === $name ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return settings for given tab.
	 *
	 * @param Tab $tab The tab as object.
	 *
	 * @return array<int,Setting>
	 */
	private function get_settings_for_tab( Tab $tab ): array {
		// list of settings for this tab.
		$tab_settings = array();

		// loop through the settings.
		foreach ( $this->get_settings() as $setting ) {
			// get the section.
			$section = $setting->get_section();

			// bail if section could not be loaded.
			if ( ! $section instanceof Section ) {
				continue;
			}

			// get the tab.
			$settings_tab = $section->get_tab();

			// bail if tab could not be loaded.
			if ( ! $settings_tab instanceof Tab ) {
				continue;
			}

			// bail if setting is not assigned to this tab.
			if ( $settings_tab->get_name() !== $tab->get_name() ) {
				continue;
			}

			// add this setting to the list.
			$tab_settings[] = $setting;
		}

		// return resulting list.
		return $tab_settings;
	}

	/**
	 * Return whether settings are available.
	 *
	 * @private Only used for internal tasks.
	 * @return bool
	 */
	public function has_settings(): bool {
		return ! empty( $this->get_settings() );
	}

	/**
	 * Return tab object by its name.
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @return false|Tab
	 */
	public function get_tab( string $tab_name ): false|Tab {
		foreach ( $this->get_tabs() as $tab_obj ) {
			// bail if name does not match.
			if ( $tab_obj->get_name() !== $tab_name ) {
				continue;
			}

			return $tab_obj;
		}

		// return false if not object could be found.
		return false;
	}

	/**
	 * Return section object by its name.
	 *
	 * @param string $section_name The section name.
	 *
	 * @return false|Section
	 */
	public function get_section( string $section_name ): false|Section {
		foreach ( $this->get_pages() as $page_obj ) {
			foreach ( $page_obj->get_tabs() as $tab_obj ) {
				foreach ( $tab_obj->get_sections() as $section_obj ) {
					// bail if names does not match.
					if ( $section_obj->get_name() !== $section_name ) {
						continue;
					}

					return $section_obj;
				}
			}
		}

		// return false if not object could be found.
		return false;
	}

	/**
	 * Return the used URL for this composer package.
	 *
	 * @return string
	 */
	public function get_url(): string {
		return $this->url;
	}

	/**
	 * Set the URL used.
	 *
	 * @param string $url The used URL.
	 * @return void
	 */
	public function set_url( string $url ): void {
		$this->url = $url;
	}

	/**
	 * Show settings link in plugin list.
	 *
	 * @return bool
	 */
	public function is_show_settings_link_in_plugin_list(): bool {
		return $this->show_settings_link_in_plugin_list;
	}

	/**
	 * Show settings link in plugin list.
	 *
	 * @param bool $show True to show the link.
	 *
	 * @return void
	 */
	public function show_settings_link_in_plugin_list( bool $show ): void {
		$this->show_settings_link_in_plugin_list = $show;
	}

	/**
	 * Add a link to plugin-settings in plugin-list.
	 *
	 * @param array<int,string> $links List of links.
	 * @return array<int,string>
	 */
	public function add_setting_link( array $links ): array {
		// bail if link should not be displayed in plugin list.
		if ( ! $this->is_show_settings_link_in_plugin_list() ) {
			return $links;
		}

		// get the translations.
		$translations = $this->get_translations();

		// add a link.
		$links[] = '<a href="' . esc_url( $this->get_settings_link() ) . '">' . $translations['plugin_settings_title'] . '</a>';

		// return resulting list of links.
		return $links;
	}

	/**
	 * Get URL for settings page.
	 *
	 * @return string
	 */
	public function get_settings_link(): string {
		switch ( $this->get_menu_parent_slug() ) {
			case 'options-general.php':
				return add_query_arg(
					array(
						'page' => $this->get_menu_slug(),
					),
					get_admin_url() . 'options-general.php'
				);
			case 'themes.php':
				return add_query_arg(
					array(
						'page' => $this->get_menu_slug(),
					),
					get_admin_url() . 'themes.php'
				);
		}

		// return empty string.
		return '#';
	}

	/**
	 * Return the menu position.
	 *
	 * @return int
	 */
	public function get_menu_position(): int {
		return $this->menu_position;
	}

	/**
	 * Set the menu position.
	 *
	 * @param int $menu_position The menu position.
	 *
	 * @return void
	 */
	public function set_menu_position( int $menu_position ): void {
		$this->menu_position = $menu_position;
	}

	/**
	 * Return the path to this composer package.
	 *
	 * It has a trailing flash.
	 *
	 * @return string
	 */
	public function get_path(): string {
		return trailingslashit( $this->path );
	}

	/**
	 * Set the path to use.
	 *
	 * @param string $path The path.
	 *
	 * @return void
	 */
	public function set_path( string $path ): void {
		$this->path = $path;
	}

	/**
	 * Sort an array by its given keys.
	 *
	 * @param array<int,mixed> $array_to_sort The array to sort.
	 *
	 * @return array<int,mixed>
	 */
	public function sort( array $array_to_sort ): array {
		// sort the array by its keys.
		ksort( $array_to_sort );

		// return resulting sorted array.
		return $array_to_sort;
	}

	/**
	 * Add a page to the settings.
	 *
	 * @param string|Page $page The page.
	 *
	 * @return Page
	 */
	public function add_page( string|Page $page ): Page {
		// get the object.
		$page_obj = $page;

		// create the object, if it is a string.
		if ( ! $page_obj instanceof Page ) {
			$page_obj = new Page( $this );
			$page_obj->set_name( is_string( $page ) ? $page : '' );
		}

		// check for a duplicate name among this tab's sub-tabs.
		$name = $page_obj->get_name();
		if ( '' !== $name && $this->has_page_with_name( $name ) ) {
			$message = sprintf(
				'A page with the name "%s" has already been added.',
				$name
			);

			// log this error.
			$this->add_error( 'double_page_name', $message, array( 'name' => $name ) );

			// return the page object.
			return $page_obj;
		}

		// add to the list.
		$this->pages[] = $page_obj;

		// return the page object.
		return $page_obj;
	}

	/**
	 * Return the requested page object.
	 *
	 * @param string $page_name The page name.
	 *
	 * @return false|Page
	 */
	public function get_page( string $page_name ): false|Page {
		foreach ( $this->get_pages() as $page_obj ) {
			// bail if names does not match.
			if ( $page_obj->get_name() !== $page_name ) {
				continue;
			}

			return $page_obj;
		}

		// check if given name is a generic WordPress page.
		if ( 'permalink' === $page_name ) {
			// create the object for this page.
			$page_obj = new Page( $this );
			$page_obj->set_name( $page_name );

			// and return it.
			return $page_obj;
		}

		// return false if not object could be found.
		return false;
	}

	/**
	 * Return list of pages.
	 *
	 * @return array<int,Page>
	 */
	public function get_pages(): array {
		$pages = $this->pages;

		$instance = $this;
		/**
		 * Filter the list of setting tabs.
		 *
		 * @since 1.7.0 Available since 1.7.0.
		 *
		 * @param array<int,Page> $pages List of pages.
		 * @param Settings $instance The settings-object.
		 */
		return apply_filters( $this->get_slug() . '_settings_pages', $pages, $instance );
	}

	/**
	 * Set the given array as settings.
	 *
	 * @param array<int,Setting> $settings List of settings.
	 *
	 * @return void
	 */
	public function set_settings( array $settings ): void {
		$this->settings = $settings;
	}

	/**
	 * Return whether a specific settings page is called.
	 *
	 * @param string $settings_page The requested settings page.
	 *
	 * @return bool
	 */
	public static function is_settings_page( string $settings_page ): bool {
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if no value is set.
		if ( is_null( $tab ) ) {
			return false;
		}

		// compare the values.
		return $tab === $settings_page;
	}

	/**
	 * Return the list of translations.
	 *
	 * @return array<string,string>
	 */
	public function get_translations(): array {
		// set the translations.
		$translations = array(
			'title_settings_import_file_missing' => 'A required file is missing',
			'text_settings_import_file_missing'  => 'Please choose a JSON-file with settings to import.',
			'lbl_ok'                             => 'OK',
			'lbl_cancel'                         => 'Cancel',
			'import_title'                       => 'Import',
			'dialog_import_error_title'          => 'Error during import',
			'dialog_import_error_text'           => 'The file could not be imported!',
			'dialog_import_error_no_file'        => 'No file was uploaded.',
			'dialog_import_error_no_size'        => 'The uploaded file is no size.',
			'dialog_import_error_no_json'        => 'The uploaded file is not a valid JSON-file.',
			'dialog_import_error_no_json_ext'    => 'The uploaded file does not have the file extension <i>.json</i>.',
			'dialog_import_error_not_saved'      => 'The uploaded file could not be saved. Contact your hoster about this problem.',
			'dialog_import_error_not_our_json'   => 'The uploaded file is not a valid JSON-file with settings for this plugin.',
			'dialog_import_success_title'        => 'Settings have been imported',
			'dialog_import_success_text'         => 'Import has been run successfully.',
			'dialog_import_success_text_2'       => 'The new settings are now active. Click on the button below to reload the page and see the settings.',
			'dialog_export_text'                 => 'Click on the button below to export the actual settings.',
			'dialog_export_text_2'               => 'You can import this JSON-file in other projects using this WordPress plugin or theme.',
			'table_options'                      => 'Options',
			'table_entry'                        => 'Entry',
			'table_no_entries'                   => 'No entries found.',
			'plugin_settings_title'              => 'Settings',
			'file_add_file'                      => 'Add file',
			'file_choose_file'                   => 'Choose file',
			'file_choose_image'                  => 'Upload or choose image',
			'drag_n_drop'                        => 'Hold to drag & drop',
			'settings_saved'                     => 'Settings saved.',
			'settings_save_error'                => 'Settings could not be saved.',
			'save_title'                         => 'Save',
		);

		// return combined list of translations.
		return array_merge( $translations, $this->translations );
	}

	/**
	 * Set the list of custom translations.
	 *
	 * @param array<string,string> $translations The translations.
	 *
	 * @return void
	 */
	public function set_translations( array $translations ): void {
		$this->translations = $translations;
	}

	/**
	 * Add the dialog script.
	 *
	 * @return void
	 */
	public function add_dialog(): void {
		// get the path to the easy dialog for WordPress package.
		$path = __DIR__ . '/../../easy-dialog-for-wordpress/';

		// bail if path does not exist.
		if ( ! file_exists( $path ) ) {
			return;
		}

		// get the URL.
		$url = trailingslashit( $this->get_url() ) . '../../../vendor/threadi/easy-dialog-for-wordpress/';

		// get assets path.
		$script_asset_path = $path . 'build/index.asset.php';

		// bail if assets does not exist.
		if ( ! file_exists( $script_asset_path ) ) {
			return;
		}

		// embed the dialog-components JS-script.
		$script_asset = require $script_asset_path;
		wp_enqueue_script(
			'easy-dialog-for-wordpress',
			$url . 'build/index.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// embed the dialog-components CSS-script.
		$admin_css      = $url . 'build/style-index.css';
		$admin_css_path = $path . 'build/style-index.css';
		wp_enqueue_style(
			'easy-dialog-for-wordpress',
			$admin_css,
			array( 'wp-components' ),
			Helper::get_file_version( $admin_css_path, $this )
		);
	}

	/**
	 * Return the import object.
	 *
	 * @return Import
	 * @noinspection PhpUnused
	 */
	public function get_import_obj(): Import {
		return $this->import_obj;
	}

	/**
	 * Return the export object.
	 *
	 * @return Export
	 */
	public function get_export_obj(): Export {
		return $this->export_obj;
	}

	/**
	 * Return whether styles and scripts of the settings handler should be enqueued.
	 *
	 * @param string $hook The used hook.
	 * @return bool
	 */
	public function enqueue_styles_and_scripts( string $hook ): bool {
		// get result.
		$result = ! empty( $this->get_menu_slug() ) && ( 'settings_page_' . $this->get_menu_slug() ) === $hook;

		/**
		 * Filter whether styles and script of the settings handler should be enqueued.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param bool $result The result.
		 * @param string $hook The hook to use.
		 */
		return apply_filters( $this->get_slug() . '_enqueue_styles_and_scripts', $result, $hook );
	}

	/**
	 * Return the plugin path.
	 *
	 * @private Only used for internal tasks.
	 * @return string
	 */
	public function get_plugin_path(): string {
		return $this->plugin_path;
	}

	/**
	 * Return the used settings for debug purposes.
	 *
	 * @return array<string,mixed>
	 */
	public function debug(): array {
		// get the method.
		$method      = Methods::get_instance()->get_method();
		$method_name = '';
		if ( $method instanceof Method_Base ) {
			$method_name = $method->get_name();
		}

		// get the view.
		$view = $this->views->get_view();
		$view_name = '';
		if( $view instanceof View_Base ) {
			$view_name = $view->get_name();
		}

		// return the settings.
		return array(
			'plugin_path' => $this->get_plugin_path(),
			'method'      => $method_name,
			'view'        => $view_name,
			'settings'    => $this->get_settings(),
		);
	}

	/**
	 * Migrate the settings from one method to another.
	 *
	 * Hint:
	 * This method has to be called by the plugin, which uses this package for their settings
	 * and want to change the way settings will be saved on an appropriate place.
	 * E.g., during the plugin update.
	 *
	 * @param string $old_method_name The name of the previous method.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function migrate_method( string $old_method_name ): void {
		Methods::get_instance()->migrate_method( $old_method_name );
	}

	/**
	 * Set the method to use.
	 *
	 * @param string $method_name The method name.
	 *
	 * @return void
	 */
	public function set_method( string $method_name ): void {
		Methods::get_instance()->set_method( $method_name );
	}

	/**
	 * Set the view to use.
	 *
	 * @param string $view_name The method name.
	 *
	 * @return void
	 */
	public function set_view( string $view_name ): void {
		$this->views->set_view( $view_name );
	}

	/**
	 * Return the configured auto-save mode.
	 *
	 * @return string
	 */
	public function get_auto_save(): string {
		return $this->auto_save;
	}

	/**
	 * Set the auto-save mode.
	 *
	 * @param string $auto_save One of 'off', 'change', 'tab_change'.
	 *
	 * @return void
	 */
	public function set_auto_save( string $auto_save ): void {
		if ( ! in_array( $auto_save, array( 'off', 'change', 'tab_change' ), true ) ) {
			// prepare the message.
			$message = sprintf(
				'Invalid auto-save mode "%s" given. Use "off", "change" or "tab_change".',
				$auto_save
			);

			// log this as error.
			$this->add_error(
				'auto_save_unknown',
				$message
			);

			// do nothing more.
			return;
		}
		$this->auto_save = $auto_save;
	}

	/**
	 * Add an error to the list.
	 *
	 * @param string              $code The error code.
	 * @param string              $message The error message.
	 * @param array<string,mixed> $data The error data.
	 *
	 * @return void
	 */
	public function add_error( string $code, string $message, array $data = array() ): void {
		// create a new error object, if not already set.
		if ( null === $this->errors ) {
			$this->errors = new WP_Error();
		}

		// add the error to the list.
		$this->errors->add(
			$code,
			$message,
			$data
		);

		/**
		 * Run tasks if an error is added to the list.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 *
		 * @param string $code The error code.
		 * @param string $message The message.
		 * @param array $data Data for the error.
		 */
		do_action( $this->get_slug() . '_error', $code, $message, $data );
	}

	/**
	 * Return errors.
	 *
	 * @return WP_Error|null
	 */
	public function get_errors(): ?WP_Error {
		return $this->errors;
	}

	/**
	 * Return whether errors have been occurred.
	 *
	 * @return bool
	 */
	public function has_errors(): bool {
		return $this->errors instanceof WP_Error
		       && $this->errors->has_errors();
	}

	/**
	 * Reset the list of errors.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnused
	 */
	public function clear_errors(): void {
		$this->errors = null;
	}

	/**
	 * Check whether a page with the given name has already been added.
	 *
	 * @param string $name The page name to check.
	 *
	 * @return bool
	 */
	public function has_page_with_name( string $name ): bool {
		foreach ( $this->pages as $existing_page ) {
			if ( $existing_page->get_name() === $name ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether a sub-tab with the given name has already been added.
	 *
	 * @param string $name The tab name to check.
	 *
	 * @return bool
	 */
	public function has_sub_tab_with_name( string $name ): bool {
		foreach ( $this->tabs as $existing_tab ) {
			if ( $existing_tab->get_name() === $name ) {
				return true;
			}
		}
		return false;
	}
}
