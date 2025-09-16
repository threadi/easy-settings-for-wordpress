<?php
/**
 * This file holds an object for a multiple files field.
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
 * Object to handle a multiple file field for single setting.
 */
class Files extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Files';

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

		// get value from config.
		$files = get_option( $setting->get_name() );

		if( ! empty( $files ) ) {
			?><ul><?php
			foreach ( $files as $file ) {
				// get the attachment object.
				$attachment = get_post( absint( $file ) );

				// bail if attachment could not be loaded.
				if( ! $attachment instanceof WP_Post ) {
					continue;
				}

				// bail if post type is not "attachment".
				if( 'attachment' !== $attachment->post_type ) {
					continue;
				}

				// prepare the setting without this file.
				$new_files = $files;

				// get position of this file in list.
				$index = array_search( (string)$attachment->ID, $new_files, true );

				// bail if none entry has been found.
				if( false === $index ) {
					continue;
				}

				// remove the file from list.
				unset( $new_files[$index] );

				// show the file entry.
				?><li class="esfw-settings-file esfw-settings-file-type-<?php echo esc_attr( sanitize_html_class( $attachment->post_mime_type ) ); ?>"><a href="<?php echo esc_url( get_edit_post_link( $attachment->ID ) ); ?>"><?php echo esc_html( $attachment->post_title ) ?></a> <a href="#" data-setting="<?php echo esc_attr( $setting->get_name() ); ?>" data-setting-value="<?php echo esc_attr( implode( ',', $new_files ) ); ?>" class="esfw-settings-files-choose-remove"><span class="dashicons dashicons-trash"></span></a></li><?php
			}
			?></ul><?php
		}

		// output.
		?>
		<a href="#" class="esfw-settings-files-choose" data-setting="<?php echo esc_attr( $setting->get_name() ); ?>"><?php echo esc_html( $this->get_add_file_title() ); ?></a>
		<input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo esc_attr( implode( ',', $files ) ); ?>" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>">
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
}
