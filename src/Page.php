<?php
/**
 * This file represents a single page for settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single page in settings.
 */
class Page extends Base_Object {
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
		 * @since 1.7.0 Available since 1.7.0.
		 * @param string $name The name.
		 * @param Page $instance The page-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_page_name', $name, $instance );
	}

	/**
	 * Add a tab with its settings for this setting object.
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

		// if the position is used, search for the next free index.
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
		 * @since 1.7.0 Available since 1.7.0.
		 * @param array<int,Tab> $tabs List of tabs.
		 * @param Page $instance The settings-object.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_tabs', $tabs, $instance );
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
}
