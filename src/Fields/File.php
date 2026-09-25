<?php
/**
 * This file holds an object for a single file field.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Setting;

/**
 * Object to handle a file field for a single setting.
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
	 * List of allowed file types.
	 *
	 * @var array<int,string>
	 */
	private array $file_types = array(
		'image',
	);

	/**
	 * Return the HTML-code to display this field.
	 *
	 * @param array<string,mixed> $attr Attributes for this field.
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

		// resolve the configured file types into MIME types (for the library) and extensions (for the upload).
		$resolved_file_types = Helper::resolve_file_types( $this->get_file_types() );
		$library_types       = ! empty( $resolved_file_types['mime_types'] ) ? $resolved_file_types['mime_types'] : $this->get_file_types();
		$file_extensions     = implode( ',', $resolved_file_types['extensions'] );

		// output.
		$image_id = absint( get_option( $setting->get_name(), 0 ) );
		if ( $image_id > 0 ) {
			// get image source (or the MIME type icon for non-images).
			$image = wp_get_attachment_image_src( $image_id, 'thumbnail', true );
			if ( ! is_array( $image ) ) {
				$image = array();
			}
			$is_image = wp_attachment_is_image( $image_id );
			?>
			<a href="#" class="esfw-settings-file-choose" data-file-types="<?php echo esc_attr( Helper::get_json( $library_types ) ); ?>" data-file-extensions="<?php echo esc_attr( $file_extensions ); ?>"><img src="<?php echo esc_url( isset( $image[0] ) ? $image[0] : '' ); ?>" alt=""<?php echo $is_image ? '' : ' class="esfw-file-icon"'; ?> />
			<?php
			if ( ! $is_image ) {
				?>
				<span class="esfw-file-name"><?php echo esc_html( wp_basename( (string) get_attached_file( $image_id ) ) ); ?></span>
				<?php
			}
			?>
			</a>
			<a href="#" class="esfw-settings-file-remove button"><?php echo esc_html( $this->get_remove_file_title() ); ?></a>
			<input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo absint( $image_id ); ?>" data-depends="<?php echo esc_attr( $this->get_depend() ); ?>">
			<?php
		} else {
			?>
			<a href="#" class="esfw-settings-file-choose button" data-file-types="<?php echo esc_attr( Helper::get_json( $library_types ) ); ?>" data-file-extensions="<?php echo esc_attr( $file_extensions ); ?>"><?php echo esc_html( $this->get_add_file_title() ); ?></a>
			<a href="#" class="esfw-settings-file-remove button" style="display:none"><?php echo esc_html( $this->get_remove_file_title() ); ?></a>
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

	/**
	 * Return the list of allowed file types.
	 *
	 * @return array<int,string>
	 */
	public function get_file_types(): array {
		return $this->file_types;
	}

	/**
	 * Set allowed file types.
	 *
	 * Each entry may be a MIME type ("text/csv"), a MIME type with wildcard ("image/*"),
	 * a MIME group ("image") or a file extension ("csv"). The list is used to filter the
	 * media library and to restrict uploads in the media modal.
	 *
	 * @param array<int,string> $file_types List of allowed file types.
	 *
	 * @return void
	 */
	public function set_file_types( array $file_types ): void {
		$this->file_types = $file_types;
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return int
	 */
	public function default_sanitize_callback( mixed $value ): int {
		// check the value.
		if ( ! is_scalar( $value ) ) {
			return 0;
		}

		// return the value.
		return absint( $value );
	}

	/**
	 * Return the REST schema for this field.
	 *
	 * The value is the attachment ID, stored as an integer (see
	 * default_sanitize_callback / absint). DataView's media control also
	 * emits the numeric ID, so expose it as integer.
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array {
		return array(
			'type' => 'integer',
		);
	}
}
