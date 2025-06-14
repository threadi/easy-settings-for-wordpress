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
class Page {
	/**
	 * The internal name of this page.
	 *
	 * @var string
	 */
	private string $name = '';

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
	 */
	public function __construct() {}

	/**
	 * Return the internal name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		$name = $this->name;

		/**
		 * Filter the name of a tabs object.
		 *
		 * @since 1.7.0 Available since 1.7.0.
		 * @param string $name The name.
		 * @param Page $this The page-object.
		 */
		return apply_filters( Settings::get_instance()->get_slug() . '_settings_page_name', $name, $this );
	}

	/**
	 * Set internal name.
	 *
	 * @param string $name The name to use.
	 *
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
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
            $tab_obj = new Tab();
            $tab_obj->set_name( $tab );
        }

        // add the tab to the list of tabs of these settings.
        $this->tabs[$position] = $tab_obj;

        // return the tab object.
        return $tab_obj;
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
            // bail if this is not a Tab object.
            if ( ! $tab_obj instanceof Tab ) {
                continue;
            }

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

		/**
		 * Filter the list of setting tabs.
		 *
		 * @since 1.7.0 Available since 1.7.0.
		 * @param array<int,Tab> $tabs List of tabs.
		 * @param Settings $this The settings-object.
		 */
		return apply_filters( Settings::get_instance()->get_slug() . '_settings_tabs', $tabs, $this );
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
        foreach( $this->get_tabs() as $index => $tab ) {
            // bail if tab does not match.
            if( $tab->get_name() !== $tab_to_delete->get_name() ) {
                continue;
            }

            // remove tab from list.
            unset( $this->tabs[$index] );
        }
    }
}
