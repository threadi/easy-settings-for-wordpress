<?php
/**
 * This file holds an object for a choice of one option shown as cards.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Setting;

/**
 * Object to handle a choice of one option, where each option is shown as card.
 *
 * Works like the Radio field (one value, validated against the options), but each option
 * could have a label, a description (HTML), an icon, a badge and could be disabled.
 *
 * Options could be given as:
 * - 'value' => 'Label'
 * - 'value' => array( 'label' => 'Label', 'description' => '<p>HTML</p>', 'icon' => 'https://…/logo.svg', 'badge' => 'Active', 'disabled' => false )
 */
class Cards extends Radio {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Cards';

	/**
	 * The number of columns. 0 means automatic depending on the available width.
	 *
	 * @var int
	 */
	protected int $columns = 0;

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

		// get the actual value as string (see Radio::display()).
		$value = $this->get_display_value( $setting, '' );
		$value = is_scalar( $value ) ? (string) $value : '';

		// show hidden field if this is set to readonly.
		if ( $this->is_readonly() ) {
			?><input type="hidden" name="<?php echo esc_attr( $setting->get_name() ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php
		}

		// collect the attributes for the list.
		$columns_attr = '';
		if ( $this->get_columns() > 0 ) {
			$columns_attr = ' data-columns="' . absint( $this->get_columns() ) . '" style="--esfw-cards-columns: ' . absint( $this->get_columns() ) . '"';
		}

		?>
		<div class="esfw-cards-field">
		<fieldset class="esfw-cards esfw-cards-<?php echo esc_attr( sanitize_html_class( $setting->get_name() ) ); ?>"<?php echo $columns_attr; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>>
			<legend class="screen-reader-text"><?php echo esc_html( $this->get_title() ); ?></legend>
			<?php
			foreach ( $this->get_cards( $value ) as $card ) {
				// get the IDs.
				$id             = $setting->get_name() . $card['value'];
				$description_id = $id . '-description';

				// get the classes for the card.
				$classes = array( 'esfw-card' );
				if ( $card['value'] === $value ) {
					$classes[] = 'is-selected';
				}
				if ( $card['disabled'] ) {
					$classes[] = 'is-disabled';
				}

				?>
				<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
					<input type="radio" id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $setting->get_name() ); ?>"
						value="<?php echo esc_attr( $card['value'] ); ?>"
						<?php
						echo ( $this->is_readonly() || $card['disabled'] ? ' disabled="disabled"' : '' );
						echo ( $card['value'] === $value ? ' checked="checked"' : '' );
						echo ( ! empty( $card['description'] ) ? ' aria-describedby="' . esc_attr( $description_id ) . '"' : '' );
						?>
						class="esfw-card__input <?php echo esc_attr( $this->get_settings_obj()->get_slug() ); ?>-field-width"
						data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"
					>
					<?php if ( ! empty( $card['badge'] ) ) { ?>
						<span class="esfw-card__badge"><?php echo esc_html( $card['badge'] ); ?></span>
					<?php } ?>
					<div class="esfw-card__header">
						<label class="esfw-card__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $card['label'] ); ?></label>
						<?php if ( ! empty( $card['icon'] ) ) { ?>
							<img class="esfw-card__icon" src="<?php echo esc_url( $card['icon'] ); ?>" alt="">
						<?php } ?>
					</div>
					<?php if ( ! empty( $card['description'] ) ) { ?>
						<div class="esfw-card__description" id="<?php echo esc_attr( $description_id ); ?>"><?php echo wp_kses_post( $card['description'] ); ?></div>
					<?php } ?>
				</div>
				<?php
			}
			?>
		</fieldset>
		<?php

		// show optional description for this field.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p class="description esfw-cards__help">' . wp_kses_post( $this->get_description() ) . '</p>';
		}

		?>
		</div>
		<?php
	}

	/**
	 * Return the options as a list of cards with all their properties.
	 *
	 * An option which is disabled could still be used if it is the actual value, otherwise saving the
	 * settings without any change would remove the actual value.
	 *
	 * @param string|null $value The actual value. Null to use the stored value.
	 *
	 * @return array<int,array{value: string, label: string, description: string, icon: string, badge: string, disabled: bool}>
	 */
	public function get_cards( ?string $value = null ): array {
		// get the actual value.
		if ( null === $value ) {
			$value = $this->get_stored_value_as_string();
		}

		// collect the cards.
		$cards = array();
		foreach ( $this->get_options() as $key => $settings ) {
			// options could be a simple label.
			if ( ! is_array( $settings ) ) {
				$settings = array( 'label' => (string) $settings );
			}

			$card = array(
				'value'       => (string) $key,
				'label'       => isset( $settings['label'] ) ? (string) $settings['label'] : '',
				'description' => isset( $settings['description'] ) ? (string) $settings['description'] : '',
				'icon'        => isset( $settings['icon'] ) ? (string) $settings['icon'] : '',
				'badge'       => isset( $settings['badge'] ) ? (string) $settings['badge'] : '',
				'disabled'    => ! empty( $settings['disabled'] ),
			);

			// the actual value could always be used.
			if ( $card['value'] === $value ) {
				$card['disabled'] = false;
			}

			$cards[] = $card;
		}

		// return the list of cards.
		return $cards;
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * Only options which are not disabled could be chosen. If a disabled option is requested,
	 * the actual value is kept.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return string
	 */
	public function default_sanitize_callback( mixed $value ): string {
		// validate the value against the options.
		$value = parent::default_sanitize_callback( $value );

		// get the stored value.
		$stored_value = $this->get_stored_value_as_string();

		// check if the requested option is disabled.
		foreach ( $this->get_cards( $stored_value ) as $card ) {
			if ( $card['value'] === $value && $card['disabled'] ) {
				return $stored_value;
			}
		}

		// return the value.
		return $value;
	}

	/**
	 * Return the stored value of the setting of this field as string, like it is compared with the options.
	 *
	 * @return string
	 */
	private function get_stored_value_as_string(): string {
		$value = $this->get_stored_value();
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Return the number of columns. 0 means automatic depending on the available width.
	 *
	 * @return int
	 */
	public function get_columns(): int {
		return $this->columns;
	}

	/**
	 * Set the number of columns. 0 means automatic depending on the available width.
	 *
	 * On small screens the cards are always shown in one column.
	 *
	 * @param int $columns The number of columns.
	 *
	 * @return void
	 */
	public function set_columns( int $columns ): void {
		$this->columns = max( 0, $columns );
	}
}
