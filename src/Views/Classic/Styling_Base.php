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
	 * Output the HTML-code for the settings.
	 *
	 * @param Tab $tab The tab to show.
	 *
	 * @return void
	 */
	public function show_content( Tab $tab ): void {
		// show the tab description.
		if ( ! empty( $tab->get_description() ) ) {
			echo wp_kses_post( $tab->get_description() );
		}

		?>
		<form method="POST" action="<?php echo esc_url( get_admin_url() ); ?>options.php">
			<?php
			if ( 'options-general.php' !== $this->settings_obj->get_menu_parent_slug() ) {
				settings_errors();
			}
			settings_fields( $tab->get_name() );
			do_settings_sections( $tab->get_name() );
			if ( ! $tab->is_save_hidden() && $tab->has_tabs() ) {
				submit_button();
			}
			?>
		</form>
		<?php
	}
}
