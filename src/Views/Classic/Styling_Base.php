<?php
/**
 * File for the base object for any styling of classic settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Views\Classic;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Base_Object;
use easySettingsForWordPress\Tab;

/**
 * Base object for any styling of classic settings.
 */
class Styling_Base extends Base_Object {
	/**
	 * Add our styling.
	 *
	 * @return void
	 */
	public function add_styles(): void {}

	/**
	 * Show the navigation for this styling.
	 *
	 * @return void
	 */
	public function show_nav(): void {}

	/**
	 * Output the HTML-code for this styling.
	 *
	 * @param Tab $tab The tab to show.
	 *
	 * @return void
	 */
	public function show_content( Tab $tab ): void {}
}
