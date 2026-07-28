<?php
/**
 * File for the main object for any view of settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single setting.
 */
class View_Base extends Base_Object {
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
}
