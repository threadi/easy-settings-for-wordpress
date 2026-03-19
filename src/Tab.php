<?php
/**
 * This file represents a single tab for settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single tab in settings.
 */
class Tab extends Base_Object {
	/**
	 * The title of this tab.
	 *
	 * @var string
	 */
	private string $title = '';

	/**
	 * The description of this tab.
	 *
	 * @var string
	 */
	private string $description = '';

	/**
	 * List of sections on this tab.
	 *
	 * @var array<int,Section>
	 */
	private array $sections = array();

	/**
	 * List of settings on this tab.
	 *
	 * @var array<int,Setting>
	 */
	private array $settings = array();

	/**
	 * Callback for the tab.
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * URL for tab navigation.
	 *
	 * @var string
	 */
	private string $url = '';

	/**
	 * Target-attribute-value for URL.
	 *
	 * @var string
	 */
	private string $url_target = '_self';

	/**
	 * Tab class.
	 *
	 * @var string
	 */
	private string $tab_class = '';

	/**
	 * Show this tab in menu instead as tab.
	 *
	 * @var bool
	 */
	private bool $show_in_menu = false;

	/**
	 * Hide the save-button.
	 *
	 * @var bool
	 */
	private bool $hide_save = false;

	/**
	 * Do not link this tab.
	 *
	 * @var bool
	 */
	private bool $not_linked = false;

	/**
	 * The position of the tab.
	 *
	 * @var int
	 */
	private int $position = 0;

	/**
	 * List of tabs.
	 *
	 * @var array<int,Tab>
	 */
	private array $tabs = array();

	/**
	 * Set the default tab.
	 *
	 * @var Tab|null
	 */
	private ?Tab $default_tab = null;

	/**
	 * The page slug.
	 *
	 * @var Page|false
	 */
	private Page|false $page = false;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Return the internal name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		$name = $this->name;

		$instance = $this;
		/**
		 * Filter the name of a tabs object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $name The name.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_tab_name', $name, $instance );
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
		 * Filter the title of a tabs object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $title The title.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->settings_obj->get_slug() . '_settings_tab_title', $title, $instance );
	}

	/**
	 * Set the title.
	 *
	 * @param string $title The title to use.
	 *
	 * @return void
	 */
	public function set_title( string $title ): void {
		$this->title = $title;
	}

	/**
	 * Return the description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		$description = $this->description;

		$instance = $this;
		/**
		 * Filter the description of a tabs object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $title The title.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_tab_description', $description, $instance );
	}

	/**
	 * Set the description.
	 *
	 * @param string $description The description to use.
	 *
	 * @return void
	 */
	public function set_description( string $description ): void {
		$this->description = $description;
	}

	/**
	 * Return list of setting-objects assigned to this tab.
	 *
	 * @return array<int,Setting>
	 */
	public function get_settings(): array {
		$settings = $this->settings;

		$instance = $this;
		/**
		 * Filter the settings of a tabs object.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<int,Setting> $settings The settings.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_tab_settings', $settings, $instance );
	}

	/**
	 * Output single tab in backend settings.
	 *
	 * @return void
	 */
	public function display(): void {
		// show the tab description.
		if ( ! empty( $this->get_description() ) ) {
			echo wp_kses_post( $this->get_description() );
		}

		?>
		<form method="POST" action="<?php echo esc_url( get_admin_url() ); ?>options.php">
			<?php
			'options-general.php' !== $this->settings_obj->get_menu_parent_slug() ? settings_errors() : '';
			settings_fields( $this->get_name() );
			do_settings_sections( $this->get_name() );
			$this->is_save_hidden() ? '' : submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Return whether this is the current tab in backend settings view.
	 *
	 * @return bool
	 */
	public function is_current(): bool {
		// bail if this is not wp-admin.
		if ( ! is_admin() ) {
			return false;
		}

		// get tab from request.
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if no tab is set.
		if ( is_null( $tab ) ) {
			return false;
		}

		// return whether the name matches.
		return $this->get_name() === $tab;
	}

	/**
	 * Return whether this is the current sub-tab in backend settings view.
	 *
	 * @return bool
	 */
	public function is_current_sub_tab(): bool {
		// bail if this is not wp-admin.
		if ( ! is_admin() ) {
			return false;
		}

		// get tab from request.
		$tab = filter_input( INPUT_GET, 'subtab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if no tab is set.
		if ( is_null( $tab ) ) {
			return false;
		}

		// return whether the name matches.
		return $this->get_name() === $tab;
	}

	/**
	 * Return list of sections in this tab.
	 *
	 * @return array<int,Section>
	 */
	public function get_sections(): array {
		$sections = $this->sections;

		$instance = $this;
		/**
		 * Filter the sections of a tabs object.
		 *
		 * @since 1.8.0 Available since 1.8.0.
		 * @param array<int,Section> $sections The settings.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_tab_sections', $sections, $instance );
	}

	/**
	 * Add section to the list of sections in this tab.
	 *
	 * @param string|Section $section The section as object.
	 * @param int            $position The position.
	 *
	 * @return Section
	 */
	public function add_section( string|Section $section, int $position ): Section {
		// set the section object.
		$section_obj = $section;

		// if value is a string, create the tab object first.
		if ( is_string( $section ) ) {
			$section_obj = new Section( $this->settings_obj );
			$section_obj->set_name( $section );
		}

		// set the tab where this section is assigned to.
		$section_obj->set_tab( $this ); // @phpstan-ignore method.nonObject

		// if position is used, add + 1.
		if ( isset( $this->sections[ $position ] ) ) {
			$position = Helper::get_next_free_index_in_array( $this->sections, $position );
		}

		// add the section to the list of sections of this tab.
		$this->sections[ $position ] = $section_obj; // @phpstan-ignore assign.propertyType

		// return the tab object.
		return $section_obj; // @phpstan-ignore return.type
	}

	/**
	 * Return the callback.
	 *
	 * @return callable
	 */
	public function get_callback(): callable {
		// if callback is empty, use our default callback.
		if ( null === $this->callback ) {
			return array( $this, 'display' );
		}

		// return the callback.
		return $this->callback;
	}

	/**
	 * Set the callback.
	 *
	 * @param callable $callback The callback.
	 *
	 * @return void
	 */
	public function set_callback( callable $callback ): void {
		$this->callback = $callback;
	}

	/**
	 * Return the URL for the tab navigation.
	 *
	 * @return string
	 */
	public function get_url(): string {
		$url = $this->url;

		$instance = $this;
		/**
		 * Filter the URL of a tabs object.
		 *
		 * @since 1.8.0 Available since 1.8.0.
		 * @param string $url The settings.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->settings_obj->get_slug() . '_settings_tab_url', $url, $instance );
	}

	/**
	 * Set the URL for the tab navigation.
	 *
	 * @param string $url The URL.
	 *
	 * @return void
	 */
	public function set_url( string $url ): void {
		$this->url = $url;
	}

	/**
	 * Return the URL target.
	 *
	 * @return string
	 */
	public function get_url_target(): string {
		$url_target = $this->url_target;

		$instance = $this;
		/**
		 * Filter the URL target of a tabs object.
		 *
		 * @since 1.8.0 Available since 1.8.0.
		 * @param string $url_target The URL target.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_tab_url_target', $url_target, $instance );
	}

	/**
	 * Set the URL target.
	 *
	 * @param string $url_target The URL target.
	 *
	 * @return void
	 */
	public function set_url_target( string $url_target ): void {
		// bail if target does not have a valid value.
		if ( ! in_array( $url_target, array( '_self', '_blank', '_top', '_parent' ), true ) ) {
			return;
		}

		// set the target-value.
		$this->url_target = $url_target;
	}

	/**
	 * Return the tab class.
	 *
	 * @return string
	 */
	public function get_tab_class(): string {
		$tab_class = $this->tab_class;

		$instance = $this;
		/**
		 * Filter the class of a tabs object.
		 *
		 * @since 1.8.0 Available since 1.8.0.
		 * @param string $tab_class The tab class.
		 * @param Tab $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_tab_class', $tab_class, $instance );
	}

	/**
	 * Set the tab class.
	 *
	 * @param string $tab_class The class for the tab.
	 *
	 * @return void
	 */
	public function set_tab_class( string $tab_class ): void {
		$this->tab_class = $tab_class;
	}

	/**
	 * Return whether to show this tab in menu instead of tab in settings page.
	 *
	 * Works only if settings value for parent slug is not 'options-general.php'.
	 *
	 * @return bool
	 */
	public function is_show_in_menu(): bool {
		return $this->show_in_menu;
	}

	/**
	 * Show in the menu.
	 *
	 * @param bool $show_in_menu The value to use.
	 *
	 * @return void
	 */
	public function set_show_in_menu( bool $show_in_menu ): void {
		$this->show_in_menu = $show_in_menu;
	}

	/**
	 * Return a section of this tab by its name.
	 *
	 * @param string $section_name The name of the searched section.
	 *
	 * @return false|Section
	 */
	public function get_section( string $section_name ): false|Section {
		foreach ( $this->get_sections() as $section_obj ) {
			// bail if names do not match.
			if ( $section_obj->get_name() !== $section_name ) {
				continue;
			}

			return $section_obj;
		}

		// return false if object has not been found.
		return false;
	}

	/**
	 * Return whether to hide the save button.
	 * *
	 *
	 * @return bool
	 */
	public function is_save_hidden(): bool {
		return $this->hide_save;
	}

	/**
	 * Set hide the save button.
	 *
	 * @param bool $hide_save_button Hide the button (true) or not (false).
	 *
	 * @return void
	 */
	public function set_hide_save( bool $hide_save_button ): void {
		$this->hide_save = $hide_save_button;
	}

	/**
	 * Set not linked setting.
	 *
	 * @param bool $not_linked True to not link this tab.
	 *
	 * @return void
	 */
	public function set_not_linked( bool $not_linked ): void {
		$this->not_linked = $not_linked;
	}

	/**
	 * Return whether this tab should be linked (false) or not (true).
	 *
	 * @return bool
	 */
	public function is_not_linked(): bool {
		return $this->not_linked;
	}

	/**
	 * Return the position of this tab in the list of all tabs.
	 *
	 * @return int
	 */
	public function get_position(): int {
		return $this->position;
	}

	/**
	 * Set the position of this tab in the list of all tabs.
	 *
	 * @param int $position The position to use.
	 *
	 * @return void
	 */
	public function set_position( int $position ): void {
		$this->position = $position;
	}

	/**
	 * Return the assigned page object.
	 *
	 * @return Page|false
	 */
	public function get_page(): Page|false {
		return $this->page;
	}

	/**
	 * Set page this tab is assigned to.
	 *
	 * @param Page $page The page object.
	 *
	 * @return void
	 */
	public function set_page( Page $page ): void {
		$this->page = $page;
	}

	/**
	 * Add tab with its settings for this setting object.
	 *
	 * @param string|Tab $tab The tab object or its internal name.
	 * @param int        $position The position to use.
	 *
	 * @return Tab
	 */
	public function add_tab( string|Tab $tab, int $position ): Tab {
		// set the tab object.
		$tab_obj = $tab;

		// if value is a string, create the tab object first.
		if ( is_string( $tab ) ) {
			$tab_obj = new Tab( $this->settings_obj );
			$tab_obj->set_name( $tab );
		}

		// if position is used, search for the next free index.
		if ( isset( $this->tabs[ $position ] ) ) {
			$position = Helper::get_next_free_index_in_array( $this->tabs, $position );
		}

		// add the tab to the list of tabs of these settings.
		$this->tabs[ $position ] = $tab_obj; // @phpstan-ignore assign.propertyType

		// return the tab object.
		return $tab_obj; // @phpstan-ignore return.type
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
			// bail if names does not match.
			if ( $tab_obj->get_name() !== $tab_name ) {
				continue;
			}

			return $tab_obj;
		}

		// return false if not object could be found.
		return false;
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
		 * @since 1.10.0 Available since 1.10.0.
		 * @param array<int,Tab> $tabs List of tabs.
		 * @param Tab $instance The settings-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_subtabs', $tabs, $instance );
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
}
