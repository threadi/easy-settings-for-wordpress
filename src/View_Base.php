<?php
/**
 * File for the main object for any view of settings.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single setting.
 */
class View_Base extends Base_Object {
	/**
	 * Initialize this view (e.g. register its hooks).
	 *
	 * Only called for the view which is actually used.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Output this view.
	 *
	 * @return void
	 */
	public function display(): void {}

	/**
	 * Output the tab detail content.
	 *
	 * @param Tab $tab The tab.
	 *
	 * @return void
	 */
	public function show_content( Tab $tab ): void {}

	/**
	 * Return whether this view is usable.
	 *
	 * @return bool
	 */
	public function is_usable(): bool {
		return true;
	}

	/**
	 * Set the styling to use by its name.
	 *
	 * @param string $styling The styling name.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function set_styling( string $styling ): void {}
}
