<?php
/**
 * This file represents a single section within a tab for settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single section within a tab in settings.
 */
class Section extends Base_Object {
	/**
	 * The title of this section.
	 *
	 * @var string
	 */
	private string $title = '';

	/**
	 * The assigned tab.
	 *
	 * @var Tab|false
	 */
	private Tab|false $tab = false;

	/**
	 * Setting this section belongs to.
	 *
	 * @var Settings|null
	 */
	private ?Settings $setting = null;

	/**
	 * The callback for section header.
	 *
	 * @var callable
	 */
	private $callback = '__return_true';

	/**
	 * The page object.
	 *
	 * @var Page
	 */
	private Page $page;

	/**
	 * Set hidden section.
	 *
	 * @var bool
	 */
	private bool $hidden = false;

	/**
	 * Is collapsible.
	 *
	 * @var bool
	 */
	private bool $collapsible = false;

	/**
	 * Is collapsed.
	 *
	 * @var bool
	 */
	private bool $collapsed = false;

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
		 * Filter the name of a section object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $name The name.
		 * @param Section $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_section_name', $name, $instance );
	}

	/**
	 * Return the internal name.
	 *
	 * @return string
	 */
	public function get_title(): string {
		$title = $this->title;

		$instance = $this;
		/**
		 * Filter the title of a section object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param string $title The title.
		 * @param Section $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_section_title', $title, $instance );
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
	 * Return the settings this section belongs to.
	 *
	 * @return ?Settings
	 */
	public function get_setting(): ?Settings {
		$settings = $this->setting;

		$instance = $this;
		/**
		 * Filter the settings object of a section object.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param ?Settings $settings The settings.
		 * @param Section $instance The tab-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_section_setting', $settings, $instance );
	}

	/**
	 * Set the settings this section belongs to.
	 *
	 * @param Settings $settings_obj The settings object this section belongs to.
	 *
	 * @return void
	 */
	public function set_setting( Settings $settings_obj ): void {
		$this->setting = $settings_obj;
	}

	/**
	 * Return the callback.
	 *
	 * @return callable
	 */
	public function get_callback(): callable {
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
	 * Return the tab this section is assigned to.
	 *
	 * @return Tab|false
	 */
	public function get_tab(): Tab|false {
		return $this->tab;
	}

	/**
	 * Set the tab this section is assigned to.
	 *
	 * @param Tab $tab The tab object.
	 *
	 * @return void
	 */
	public function set_tab( Tab $tab ): void {
		$this->tab = $tab;
	}

	/**
	 * Return the page this section is assigned to.
	 *
	 * @return Page|false
	 */
	public function get_page(): Page|false {
		return $this->page;
	}

	/**
	 * Set page this section is assigned to.
	 *
	 * @param Page $page The page object.
	 *
	 * @return void
	 */
	public function set_page( Page $page ): void {
		$this->page = $page;
	}

	/**
	 * Return whether this section is hidden.
	 *
	 * @return bool
	 */
	public function is_hidden(): bool {
		return $this->hidden;
	}

	/**
	 * Set this section as hidden.
	 *
	 * @param bool $hidden True if this section should be hidden.
	 *
	 * @return void
	 */
	public function set_hidden( bool $hidden ): void {
		$this->hidden = $hidden;
	}

	/**
	 * Set collapsible.
	 *
	 * @param bool $collapsible True to make is collapsible.
	 *
	 * @return void
	 */
	public function set_collapsible( bool $collapsible ): void {
		$this->collapsible = $collapsible;
		if ( $collapsible && '__return_true' === $this->callback ) {
			$this->callback = array( $this, 'render_collapsible_marker' );
		}
	}

	/**
	 * Marker between WP's <h2> and the form-table (classic view).
	 */
	public function render_collapsible_marker(): void {
		// get the collapsed state.
		$collapsed = $this->get_settings_obj()->get_effective_section_collapsed( $this );

		// return the marker.
		printf(
			'<span class="esfw-collapsible-marker" data-section="%s" data-collapsed="%s" hidden></span>',
			esc_attr( $this->get_name() ),
			$collapsed ? '1' : '0'
		);
	}

	/**
	 * Return whether this section is collapsible.
	 *
	 * @return bool
	 */
	public function is_collapsible(): bool {
		return $this->collapsible;
	}

	/**
	 * Set collapsed.
	 *
	 * @param bool $collapsed True to set it collapsed.
	 *
	 * @return void
	 */
	public function set_collapsed( bool $collapsed ): void {
		$this->collapsed = $collapsed;
		if ( $collapsed ) {
			$this->collapsible = true;
		}
	}

	/**
	 * Return whether this section is collapsed.
	 *
	 * @return bool
	 */
	public function is_collapsed(): bool {
		return $this->collapsed;
	}
}
