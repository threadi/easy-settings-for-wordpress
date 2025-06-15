<?php
/**
 * This file holds an object for a single file field.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;

/**
 * Object to handle a file field for single setting.
 */
class File extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'File';

	/**
	 * The add file title.
	 *
	 * @var string
	 */
	private string $add_file_title = '';

	/**
	 * The add file title.
	 *
	 * @var string
	 */
	private string $remove_file_title = '';

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

		// get the setting object.
		$setting = $attr['setting'];

		// output.
		$image_id = absint( get_option( $setting->get_name(), 0 ) );
		if ( $image_id > 0 ) {
			// get image source.
			$image = wp_get_attachment_image_src( $image_id );
			if( ! is_array( $image ) ) {
				$image = array();
			}
			?>
			<a href="#" class="esfw-settings-image-choose"><img src="<?php echo esc_url( isset( $image[0] ) ? $image[0] : '' ); ?>" alt="" /></a>
			<a href="#" class="esfw-settings-image-remove"><?php echo esc_html( $this->get_remove_file_title() ); ?></a>
			<input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo absint( $image_id ); ?>" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>">
			<?php
		} else {
			?>
			<a href="#" class="esfw-settings-image-choose"><?php echo esc_html( $this->get_add_file_title() ); ?></a>
			<a href="#" class="esfw-settings-image-remove" style="display:none"><?php echo esc_html( $this->get_remove_file_title() ); ?></a>
			<input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>">
			<?php
		}
		?>
		<?php

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * Return the add file title.
	 *
	 * @return string
	 */
	private function get_add_file_title(): string {
		return $this->add_file_title;
	}

	/**
	 * Set the title to adding a file.
	 *
	 * @param string $title The title.
	 *
	 * @return void
	 */
	public function set_add_file_title( string $title ): void {
		$this->add_file_title = $title;
	}

	/**
	 * Return the removing file title.
	 *
	 * @return string
	 */
	private function get_remove_file_title(): string {
		return $this->remove_file_title;
	}

	/**
	 * Set the title to removing a file.
	 *
	 * @param string $title The title.
	 *
	 * @return void
	 */
	public function set_remove_file_title( string $title ): void {
		$this->remove_file_title = $title;
	}
}
