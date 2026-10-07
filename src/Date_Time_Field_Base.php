<?php
/**
 * File for an object to handle the shared tasks of the date and time fields.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the shared tasks of the fields "Date", "Time" and "DateTime".
 *
 * The values are stored as strings in a fixed format without any timezone:
 *
 * - Date:     Y-m-d       (e.g. "2026-12-24")
 * - Time:     H:i         (e.g. "12:00")
 * - DateTime: Y-m-d H:i   (e.g. "2026-12-24 12:00")
 *
 * Seconds (":s") are only part of the time if a step is set that is not a
 * multiple of 60. The values have to be interpreted in the timezone of the
 * website, see wp_timezone().
 */
abstract class Date_Time_Field_Base extends Field_Base {

	/**
	 * The type of the HTML input field.
	 *
	 * @var string
	 */
	protected string $input_type = 'date';

	/**
	 * Whether the value contains a date.
	 *
	 * @var bool
	 */
	protected bool $has_date = true;

	/**
	 * Whether the value contains a time.
	 *
	 * @var bool
	 */
	protected bool $has_time = false;

	/**
	 * The min value.
	 *
	 * @var string
	 */
	private string $min = '';

	/**
	 * The max value.
	 *
	 * @var string
	 */
	private string $max = '';

	/**
	 * The step value. 0 to use the default of the browser.
	 *
	 * @var int
	 */
	private int $step = 0;

	/**
	 * The value.
	 *
	 * @var string
	 */
	private string $value = '';

	/**
	 * Show the label with the field.
	 *
	 * @var bool
	 */
	private bool $with_label = false;

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

		// get value.
		$value = $this->normalize( get_option( $setting->get_name(), $setting->get_default() ) );

		// use value from object, if set.
		if ( '' !== $this->get_value() ) {
			$value = $this->get_value();
		}

		// show the label with the field.
		if ( $this->get_with_label() ) {
			?><label for="<?php echo esc_attr( $setting->get_name() ); ?>"><?php echo esc_html( $this->get_title() ); ?></label>
			<?php
		}

		?>
		<input type="<?php echo esc_attr( $this->get_input_type() ); ?>" id="<?php echo esc_attr( $setting->get_name() ); ?>"
				name="<?php echo esc_attr( $setting->get_name() ); ?>"
				value="<?php echo esc_attr( $this->get_input_value( $value ) ); ?>"
				<?php
				echo ( '' !== $this->get_min() ? ' min="' . esc_attr( $this->get_input_value( $this->get_min() ) ) . '"' : '' );
				echo ( '' !== $this->get_max() ? ' max="' . esc_attr( $this->get_input_value( $this->get_max() ) ) . '"' : '' );
				echo ( $this->get_step() > 0 ? ' step="' . absint( $this->get_step() ) . '"' : '' );
				echo ( $this->is_readonly() ? ' readonly="readonly"' : '' );
				?>
				class="<?php echo esc_attr( $this->get_settings_obj()->get_slug() ); ?>-field-width"
				title="<?php echo esc_attr( $this->get_title() ); ?>"
				data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"
		>
		<?php

		// show optional description for this field.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * Only a valid value in the format of this field is accepted, everything
	 * else results in an empty string. A valid value is limited to the
	 * configured range of min and max.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return string
	 */
	public function default_sanitize_callback( mixed $value ): string {
		// bring the value in the format of this field.
		$value = $this->normalize( $value );

		// bail if no valid value is given.
		if ( '' === $value ) {
			return '';
		}

		// read the configured bounds.
		$min = $this->get_min();
		$max = $this->get_max();

		// a time may use a range that crosses midnight (e.g. from 22:00 to 06:00).
		if ( ! $this->has_date && '' !== $min && '' !== $max && $min > $max ) {
			return ( $value >= $min || $value <= $max ) ? $value : $min;
		}

		// limit to the range, the format is sortable as string.
		if ( '' !== $min && $value < $min ) {
			return $min;
		}
		if ( '' !== $max && $value > $max ) {
			return $max;
		}

		// return the resulting value.
		return $value;
	}

	/**
	 * Return the given value in the format this field stores its value.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return string The value or an empty string if it is not valid.
	 */
	public function normalize( mixed $value ): string {
		// bail if value is not a string.
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		// bail if no value is set (e.g. field left untouched).
		if ( '' === $value ) {
			return '';
		}

		// build the pattern for the parts this field consists of.
		$date_pattern = '(?<date>\d{4}-\d{2}-\d{2})';
		$time_pattern = '(?<hour>[01]\d|2[0-3]):(?<minute>[0-5]\d)(?::(?<second>[0-5]\d)(?:\.\d{1,3})?)?';
		if ( $this->has_date && $this->has_time ) {
			$pattern = $date_pattern . '[T ]' . $time_pattern;
		} else {
			$pattern = $this->has_date ? $date_pattern : $time_pattern;
		}

		// bail if the value does not match the format.
		if ( 1 !== preg_match( '/^' . $pattern . '$/', $value, $matches ) ) {
			return '';
		}

		$parts = array();

		// get the date.
		if ( $this->has_date ) {
			$date       = $matches['date'] ?? '';
			$date_parts = array_map( 'intval', explode( '-', $date ) );

			// bail if this date does not exist (e.g. the 30th of february).
			if ( 3 !== count( $date_parts ) || ! checkdate( $date_parts[1], $date_parts[2], $date_parts[0] ) ) {
				return '';
			}

			$parts[] = $date;
		}

		// get the time.
		if ( $this->has_time ) {
			$time = ( $matches['hour'] ?? '00' ) . ':' . ( $matches['minute'] ?? '00' );
			if ( $this->has_seconds() ) {
				$time .= ':' . ( ! empty( $matches['second'] ) ? $matches['second'] : '00' );
			}

			$parts[] = $time;
		}

		// return the value in the format of this field.
		return implode( ' ', $parts );
	}

	/**
	 * Return the given value in the format the HTML input field expects.
	 *
	 * @param string $value The value in the format of this field.
	 *
	 * @return string
	 */
	protected function get_input_value( string $value ): string {
		// the field "datetime-local" separates date and time by "T".
		return str_replace( ' ', 'T', $value );
	}

	/**
	 * Return the type of the HTML input field.
	 *
	 * @return string
	 */
	public function get_input_type(): string {
		return $this->input_type;
	}

	/**
	 * Return whether the stored time contains seconds.
	 *
	 * @return bool
	 */
	public function has_seconds(): bool {
		return $this->has_time && 0 !== $this->get_step() % 60;
	}

	/**
	 * Return the min value.
	 *
	 * @return string
	 */
	public function get_min(): string {
		return $this->normalize( $this->min );
	}

	/**
	 * Set minimum value for this field.
	 *
	 * @param string $min The min value in the format of this field.
	 *
	 * @return void
	 */
	public function set_min( string $min ): void {
		$this->min = $min;
	}

	/**
	 * Return the max value.
	 *
	 * @return string
	 */
	public function get_max(): string {
		return $this->normalize( $this->max );
	}

	/**
	 * Set maximum value for this field.
	 *
	 * @param string $max The max value in the format of this field.
	 *
	 * @return void
	 */
	public function set_max( string $max ): void {
		$this->max = $max;
	}

	/**
	 * Return the step value.
	 *
	 * @return int
	 */
	public function get_step(): int {
		return $this->step;
	}

	/**
	 * Set step value for this field.
	 *
	 * A field with a time uses seconds (e.g. 900 for steps of 15 minutes), a
	 * field with only a date uses days.
	 *
	 * @param int $step The step value, 0 to use the default of the browser.
	 *
	 * @return void
	 */
	public function set_step( int $step ): void {
		$this->step = max( 0, $step );
	}

	/**
	 * Return the value.
	 *
	 * @return string
	 */
	private function get_value(): string {
		return $this->value;
	}

	/**
	 * Set the value.
	 *
	 * @param mixed $value The value.
	 *
	 * @return void
	 */
	public function set_value( mixed $value ): void {
		$this->value = $this->normalize( $value );
	}

	/**
	 * Return whether we show the label with the field.
	 *
	 * @return bool
	 */
	private function get_with_label(): bool {
		return $this->with_label;
	}

	/**
	 * Set to show the label with the field.
	 *
	 * @param bool $with_label True to show the label.
	 *
	 * @return void
	 */
	public function set_with_label( bool $with_label ): void {
		$this->with_label = $with_label;
	}

	/**
	 * Return the REST schema for this field.
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array {
		return array(
			'type' => 'string',
		);
	}
}
