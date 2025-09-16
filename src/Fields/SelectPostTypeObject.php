<?php
/**
 * This file holds an object for a single field to select a single post type object.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;
use WP_Post;

/**
 * Object to handle a select field for single setting.
 */
class SelectPostTypeObject extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'SelectPostTypeObject';

	/**
	 * The button title.
	 *
	 * @var string
	 */
	private string $button_title = '';

	/**
	 * The popup title.
	 *
	 * @var string
	 */
	private string $popup_title = '';

	/**
	 * The popup description.
	 *
	 * @var string
	 */
	private string $popup_description = '';

	/**
	 * The REST endpoint to use.
	 *
	 * @var string
	 */
	private string $endpoint = '';

	/**
	 * The limit for the results.
	 *
	 * @var int
	 */
	private int $limit = 5;

	/**
	 * The chosen title.
	 *
	 * @var string
	 */
	private string $chosen_title = '';

	/**
	 * The label title.
	 *
	 * @var string
	 */
	private string $label_title = '';

	/**
	 * The placeholder.
	 *
	 * @var string
	 */
	private string $placeholder = '';

	/**
	 * The cancel button title.
	 *
	 * @var string
	 */
	private string $cancel_button_title = '';

	/**
	 * Return the HTML-code to display this field.
	 *
	 * @param array $attr Attributes for this field.
	 *
	 * @return void
	 */
	public function display( array $attr ): void {
		// bail if no attributes are set.
		if ( empty( $attr ) ) {
			return;
		}

		// bail if no setting object is set.
		if ( empty( $attr['setting'] ) ) {
			return;
		}

		// bail if field is not a Setting object.
		if ( ! $attr['setting'] instanceof Setting ) {
			return;
		}

		// bail if no REST endpoint is set.
		if( empty( $this->get_endpoint() ) ) {
			return;
		}

		// get the setting object.
		$setting = $attr['setting'];

		// get value.
		$value = absint( get_option( $setting->get_name(), 0 ) );

		?><input type="hidden" id="<?php echo esc_attr( $setting->get_name() ); ?>" name="<?php echo esc_attr( $setting->get_name() ); ?>" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>" value="<?php echo absint( $value ); ?>">
		<input type="button" class="button button-primary esfw-settings-open-popup" popovertarget="esfw-settings-popover-<?php echo esc_attr( $setting->get_name() ); ?>" popovertargetaction="toggle" value="<?php echo esc_attr( $this->get_button_title() ); ?>">
		<?php

		// show info about chosen object.
		if( $value > 0 ) {
			// get the post object.
			$post = get_post( $value );

			// show only if post could be loaded.
			if( $post instanceof WP_Post ) {
				// get the permalink of this post.
				$url = get_permalink( $post->ID );
				if( ! $url ) {
					$url = '';
				}

				?>
				<p class="esfw-settings-post-type-chosen"><?php echo esc_html( $this->get_chosen_title() ); ?>: <a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $post->post_title ); ?></a></p>
				<?php
			}
		}

		// output the popup where the user could search and select.
		?>
		<div id="esfw-settings-popover-<?php echo esc_attr( $setting->get_name() ); ?>" popover class="esfw-settings-overlay">
			<h2><?php echo esc_html( $this->get_popup_title() ); ?></h2>
			<?php echo wp_kses_post( $this->get_popup_description() ); ?>
			<label for="esfw-settings-popover-<?php echo esc_attr( $setting->get_name() ); ?>-search"><?php echo esc_html( $this->get_label_title() ); ?></label>
			<input type="text" name="esfw-settings-popover-<?php echo esc_attr( $setting->get_name() ); ?>-search" id="esfw-settings-popover-<?php echo esc_attr( $setting->get_name() ); ?>-search" placeholder="<?php echo esc_attr( $this->get_placeholder() ); ?>" value="" class="widefat esfw-settings-post-type-search" data-field="<?php echo esc_attr( $setting->get_name() ); ?>" data-endpoint="<?php echo esc_attr( $this->get_endpoint() ); ?>" data-limit="<?php echo esc_attr( $this->get_limit() ); ?>" data-chosen-title="<?php echo esc_attr( $this->get_chosen_title() ); ?>">
			<div class="esfw-settings-post-type-listing"></div>
			<input type="button" popovertarget="esfw-settings-popover-<?php echo esc_attr( $setting->get_name() ); ?>" popovertargetaction="hide" value="<?php echo esc_attr( $this->get_cancel_button_title() ); ?>" class="button button-primary esfw-settings-overlay-closing">
		</div>
		<?php


		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * Return the button title.
	 *
	 * @return string
	 */
	private function get_button_title(): string {
		return $this->button_title;
	}

	/**
	 * Set button title.
	 *
	 * @param string $title The title to set.
	 *
	 * @return void
	 */
	public function set_button_title( string $title ): void {
		$this->button_title = $title;
	}

	/**
	 * Return the popup title.
	 *
	 * @return string
	 */
	private function get_popup_title(): string {
		return $this->popup_title;
	}

	/**
	 * Set popup title.
	 *
	 * @param string $title The title to set.
	 *
	 * @return void
	 */
	public function set_popup_title( string $title ): void {
		$this->popup_title = $title;
	}

	/**
	 * Return the popup description.
	 *
	 * @return string
	 */
	private function get_popup_description(): string {
		return $this->popup_description;
	}

	/**
	 * Set popup description.
	 *
	 * @param string $description The title to set.
	 *
	 * @return void
	 */
	public function set_popup_description( string $description ): void {
		$this->popup_description = $description;
	}

	/**
	 * Return the limit for the results.
	 *
	 * @return int
	 */
	private function get_limit(): int {
		return $this->limit;
	}

	/**
	 * Set the limit for the results.
	 *
	 * @param int $limit The limit.
	 *
	 * @return void
	 */
	public function set_limit( int $limit ): void {
		$this->limit = $limit;
	}

	/**
	 * Return the endpoint.
	 *
	 * @return string
	 */
	private function get_endpoint(): string {
		return $this->endpoint;
	}

	/**
	 * Set the REST endpoint this field should request.
	 *
	 * @param string $endpoint The endpoint to use.
	 *
	 * @return void
	 */
	public function set_endpoint( string $endpoint ): void {
		$this->endpoint = $endpoint;
	}

	/**
	 * Return the chosen title.
	 *
	 * @return string
	 */
	private function get_chosen_title(): string {
		return $this->chosen_title;
	}

	/**
	 * Set the chosen title.
	 *
	 * @param string $chosen_title The chosen title.
	 *
	 * @return void
	 */
	public function set_chosen_title( string $chosen_title ): void {
		$this->chosen_title = $chosen_title;
	}

	/**
	 * Return the label title.
	 *
	 * @return string
	 */
	private function get_label_title(): string {
		return $this->label_title;
	}

	/**
	 * Set the label title.
	 *
	 * @param string $label_title The label title.
	 *
	 * @return void
	 */
	public function set_label_title( string $label_title ): void {
		$this->label_title = $label_title;
	}

	/**
	 * Return the placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return $this->placeholder;
	}

	/**
	 * Set the placeholder.
	 *
	 * @param string $placeholder The placeholder to use.
	 *
	 * @return void
	 */
	public function set_placeholder( string $placeholder ): void {
		$this->placeholder = $placeholder;
	}

	/**
	 * Return the cancel button title.
	 *
	 * @return string
	 */
	private function get_cancel_button_title(): string {
		return $this->cancel_button_title;
	}

	/**
	 * Set cancel button title.
	 *
	 * @param string $title The title to set.
	 *
	 * @return void
	 */
	public function set_cancel_button_title( string $title ): void {
		$this->cancel_button_title = $title;
	}
}
