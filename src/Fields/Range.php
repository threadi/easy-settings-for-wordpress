<?php
/**
 * This file holds an object for a range field with a minimum and a maximum value.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;

/**
 * Object to handle a range field: a slider with two handles to choose a minimum and a maximum value.
 *
 * The value is stored as array( 'min' => …, 'max' => … ). Both values are integers if
 * the bounds and the step of this field are whole numbers, otherwise they are floats.
 */
class Range extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Range';

	/**
	 * The lowest value the user could choose.
	 *
	 * @var int|float
	 */
	private int|float $min = 0;

	/**
	 * The highest value the user could choose.
	 *
	 * @var int|float
	 */
	private int|float $max = 100;

	/**
	 * The step value. 0 allows any value.
	 *
	 * @var int|float
	 */
	private int|float $step = 1;

	/**
	 * The minimal distance between the chosen minimum and maximum.
	 *
	 * @var int|float
	 */
	private int|float $min_gap = 0;

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

		// get the value and bring it into a valid shape (without the readonly logic of the sanitize callback).
		$value = $this->get_display_value( $setting, $setting->get_default() );
		$value = $this->normalize_value( $value, $this->get_fallback_value( $setting->get_default() ) );

		// get the bounds.
		list( $min, $max ) = $this->get_bounds();

		// get the name and translations.
		$name         = $setting->get_name();
		$translations = $this->get_settings_obj()->get_translations();
		$label_min    = $translations['range_minimum'];
		$label_max    = $translations['range_maximum'];
		if ( '' !== $this->get_title() ) {
			$label_min .= ': ' . $this->get_title();
			$label_max .= ': ' . $this->get_title();
		}

		// show hidden fields if this is set to readonly.
		if ( $this->is_readonly() ) {
			?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[min]" value="<?php echo esc_attr( $this->format_number( $value['min'] ) ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[max]" value="<?php echo esc_attr( $this->format_number( $value['max'] ) ); ?>">
			<?php
		}

		// collect the attributes both range inputs share.
		$shared  = ' min="' . esc_attr( $this->format_number( $min ) ) . '"';
		$shared .= ' max="' . esc_attr( $this->format_number( $max ) ) . '"';
		$shared .= ' step="' . esc_attr( $this->get_step() > 0 ? $this->format_number( $this->get_step() ) : 'any' ) . '"';
		$shared .= $this->is_readonly() ? ' disabled="disabled"' : '';

		?>
		<div class="esfw-range<?php echo esc_attr( $this->is_readonly() ? ' is-disabled' : '' ); ?>"
			title="<?php echo esc_attr( $this->get_title() ); ?>"
			data-min-gap="<?php echo esc_attr( $this->format_number( $this->get_min_gap() ) ); ?>"
			data-depends="<?php echo esc_attr( $this->get_depend() ); ?>"
			style="<?php echo esc_attr( $this->get_fill_style( $value ) ); ?>"
		>
			<div class="esfw-range__slider">
				<div class="esfw-range__track"><div class="esfw-range__fill"></div></div>
				<input type="range" class="esfw-range__input esfw-range__input--min"
					id="<?php echo esc_attr( $name ); ?>"
					name="<?php echo esc_attr( $name ); ?>[min]"
					value="<?php echo esc_attr( $this->format_number( $value['min'] ) ); ?>"
					aria-label="<?php echo esc_attr( $label_min ); ?>"
					<?php echo $shared; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
				>
				<input type="range" class="esfw-range__input esfw-range__input--max"
					id="<?php echo esc_attr( $name ); ?>-max"
					name="<?php echo esc_attr( $name ); ?>[max]"
					value="<?php echo esc_attr( $this->format_number( $value['max'] ) ); ?>"
					aria-label="<?php echo esc_attr( $label_max ); ?>"
					<?php echo $shared; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
				>
			</div>
			<div class="esfw-range__values">
				<label class="esfw-range__value">
					<span><?php echo esc_html( $translations['range_minimum'] ); ?></span>
					<input type="number" class="esfw-range__number esfw-range__number--min"
						value="<?php echo esc_attr( $this->format_number( $value['min'] ) ); ?>"
						aria-label="<?php echo esc_attr( $label_min ); ?>"
						<?php echo $shared; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					>
				</label>
				<label class="esfw-range__value">
					<span><?php echo esc_html( $translations['range_maximum'] ); ?></span>
					<input type="number" class="esfw-range__number esfw-range__number--max"
						value="<?php echo esc_attr( $this->format_number( $value['max'] ) ); ?>"
						aria-label="<?php echo esc_attr( $label_max ); ?>"
						<?php echo $shared; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
					>
				</label>
			</div>
		</div>
		<?php

		// show optional description for this field.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * Returns array( 'min' => …, 'max' => … ): both values are numbers within the
	 * bounds of this field, snapped to the step, min <= max and at least min_gap apart.
	 * Missing or invalid parts are taken from the default of the setting or, if
	 * that is not usable, from the bounds of this field.
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return array{min: int|float, max: int|float}
	 */
	public function default_sanitize_callback( mixed $value ): array {
		// get the default of the setting, if this field belongs to one.
		$setting       = $this->get_setting();
		$default_value = $setting instanceof Setting ? $setting->get_default() : null;

		// return the normalized value.
		return $this->normalize_value( $value, $this->get_fallback_value( $default_value ) );
	}

	/**
	 * Return the REST schema for this field.
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'min' => array( 'type' => 'number' ),
				'max' => array( 'type' => 'number' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Return the value to use if nothing (valid) is given.
	 *
	 * This is the given default value, if it is usable, otherwise the full range of this field.
	 *
	 * @param mixed $default_value The default value of the setting.
	 *
	 * @return array{min: int|float, max: int|float}
	 */
	public function get_fallback_value( mixed $default_value = null ): array {
		// get the bounds as the base.
		list( $min, $max ) = $this->get_bounds();
		$full_range        = array(
			'min' => $this->cast_number( $min ),
			'max' => $this->cast_number( $max ),
		);

		// bail if no usable default is given.
		if ( ! is_array( $default_value ) || ! isset( $default_value['min'], $default_value['max'] ) ) {
			return $full_range;
		}

		// return the default, normalized against the full range.
		return $this->normalize_value( $default_value, $full_range );
	}

	/**
	 * Bring a value into a valid shape.
	 *
	 * @param mixed                                 $value    The value.
	 * @param array{min: int|float, max: int|float} $fallback The value to use for missing or invalid parts.
	 *
	 * @return array{min: int|float, max: int|float}
	 */
	public function normalize_value( mixed $value, array $fallback ): array {
		// use the fallback for anything that is not an array.
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		// get both numbers, or the fallback for each missing / invalid one.
		$lower = $this->parse_number( $value['min'] ?? null );
		$upper = $this->parse_number( $value['max'] ?? null );
		$lower = null === $lower ? (float) $fallback['min'] : $lower;
		$upper = null === $upper ? (float) $fallback['max'] : $upper;

		// snap them to the steps and into the bounds.
		$lower = $this->snap( $lower );
		$upper = $this->snap( $upper );

		// swap them if they are in the wrong order.
		if ( $lower > $upper ) {
			list( $lower, $upper ) = array( $upper, $lower );
		}

		// keep the minimal distance between both.
		$gap = $this->get_min_gap();
		if ( $gap > 0 && ( $upper - $lower ) < $gap - $this->get_epsilon() ) {
			list( $min, $max ) = $this->get_bounds();

			if ( $gap >= $max - $min ) {
				// the gap does not fit into the range: use the full range.
				$lower = $min;
				$upper = $max;
			} else {
				// move the maximum up, or, if not possible, the minimum down.
				$upper = $this->round_number( $lower + $gap );
				if ( $upper > $max ) {
					$upper = $max;
					$lower = $this->round_number( $max - $gap );
				}
			}
		}

		// return the resulting value.
		return array(
			'min' => $this->cast_number( $lower ),
			'max' => $this->cast_number( $upper ),
		);
	}

	/**
	 * Return the bounds of this field in the right order.
	 *
	 * @return array{0: int|float, 1: int|float}
	 */
	public function get_bounds(): array {
		$min = $this->get_min();
		$max = $this->get_max();

		// guard against a misconfigured range (min > max).
		if ( $min > $max ) {
			return array( $max, $min );
		}

		return array( $min, $max );
	}

	/**
	 * Return the number of decimals used by this field.
	 *
	 * It is the highest number of decimals of min, max and step. Without a step
	 * (any value is allowed), null is returned.
	 *
	 * @return int|null
	 */
	public function get_precision(): ?int {
		// bail if any value is allowed.
		if ( $this->get_step() <= 0 ) {
			return null;
		}

		return max(
			$this->count_decimals( $this->get_min() ),
			$this->count_decimals( $this->get_max() ),
			$this->count_decimals( $this->get_step() )
		);
	}

	/**
	 * Return whether the values of this field are stored as integers.
	 *
	 * @return bool
	 */
	public function uses_integers(): bool {
		return 0 === $this->get_precision();
	}

	/**
	 * Return a number as string for the use in HTML attributes, without exponent and locale.
	 *
	 * @param int|float $number The number.
	 *
	 * @return string
	 */
	public function format_number( int|float $number ): string {
		// integers can be used as they are.
		if ( is_int( $number ) ) {
			return (string) $number;
		}

		// use the precision of this field, or a generous one if any value is allowed.
		$precision = $this->get_precision() ?? 10;
		$formatted = sprintf( '%.' . $precision . 'F', $number );

		// remove trailing zeros of the decimals.
		if ( str_contains( $formatted, '.' ) ) {
			$formatted = rtrim( rtrim( $formatted, '0' ), '.' );
		}

		// avoid "-0".
		return '-0' === $formatted ? '0' : $formatted;
	}

	/**
	 * Return the min value.
	 *
	 * @return int|float
	 */
	public function get_min(): int|float {
		return $this->min;
	}

	/**
	 * Set the lowest value the user could choose.
	 *
	 * @param int|float $min The min value.
	 *
	 * @return void
	 */
	public function set_min( int|float $min ): void {
		$this->min = $min;
	}

	/**
	 * Return the max value.
	 *
	 * @return int|float
	 */
	public function get_max(): int|float {
		return $this->max;
	}

	/**
	 * Set the highest value the user could choose.
	 *
	 * @param int|float $max The max value.
	 *
	 * @return void
	 */
	public function set_max( int|float $max ): void {
		$this->max = $max;
	}

	/**
	 * Return the step value.
	 *
	 * @return int|float
	 */
	public function get_step(): int|float {
		return $this->step;
	}

	/**
	 * Set the step value (e.g. 0.5). 0 allows any value.
	 *
	 * @param int|float $step The step value.
	 *
	 * @return void
	 */
	public function set_step( int|float $step ): void {
		$this->step = max( 0, $step );
	}

	/**
	 * Return the minimal distance between the chosen minimum and maximum.
	 *
	 * @return int|float
	 */
	public function get_min_gap(): int|float {
		return $this->min_gap;
	}

	/**
	 * Set the minimal distance between the chosen minimum and maximum. 0 allows both to be equal.
	 *
	 * @param int|float $min_gap The minimal distance.
	 *
	 * @return void
	 */
	public function set_min_gap( int|float $min_gap ): void {
		$this->min_gap = max( 0, $min_gap );
	}

	/**
	 * Return a number from the given value, or null if it is not a number.
	 *
	 * Accepts numbers and numeric strings, also with a comma as decimal separator.
	 *
	 * @param mixed $value The value.
	 *
	 * @return float|null
	 */
	private function parse_number( mixed $value ): ?float {
		// accept a comma as decimal separator in strings.
		if ( is_string( $value ) ) {
			$value = str_replace( ',', '.', trim( $value ) );
		}

		// bail if this is not a number (this also drops booleans and arrays).
		if ( ( ! is_int( $value ) && ! is_float( $value ) && ! is_string( $value ) ) || ! is_numeric( $value ) ) {
			return null;
		}

		$number = (float) $value;

		// bail on INF and NAN.
		if ( ! is_finite( $number ) ) {
			return null;
		}

		return $number;
	}

	/**
	 * Snap a number to the steps of this field (counted from min) and clamp it into the bounds.
	 *
	 * @param float $number The number.
	 *
	 * @return float
	 */
	private function snap( float $number ): float {
		list( $min, $max ) = $this->get_bounds();
		$step              = (float) $this->get_step();

		// snap to the steps, counted from the lower bound (like the browser does).
		if ( $step > 0 ) {
			$number = $min + round( ( $number - $min ) / $step ) * $step;
		}

		// round away floating point noise.
		$number = $this->round_number( $number );

		// clamp into the bounds.
		return (float) max( $min, min( $max, $number ) );
	}

	/**
	 * Round a number to the precision of this field.
	 *
	 * @param float $number The number.
	 *
	 * @return float
	 */
	private function round_number( float $number ): float {
		$precision = $this->get_precision();

		// without precision (any value allowed), only remove floating point noise.
		if ( null === $precision ) {
			return round( $number, 10 );
		}

		return round( $number, $precision );
	}

	/**
	 * Return a number as integer if this field uses integers, otherwise as float.
	 *
	 * @param int|float $number The number.
	 *
	 * @return int|float
	 */
	private function cast_number( int|float $number ): int|float {
		if ( $this->uses_integers() ) {
			return (int) round( $number );
		}

		return (float) $number;
	}

	/**
	 * Return a tolerance for comparisons of floats.
	 *
	 * @return float
	 */
	private function get_epsilon(): float {
		$precision = $this->get_precision() ?? 10;
		return 10.0 ** ( -$precision - 1 );
	}

	/**
	 * Return the number of decimals of a number.
	 *
	 * @param int|float $number The number.
	 *
	 * @return int
	 */
	private function count_decimals( int|float $number ): int {
		// integers and whole floats have no decimals.
		if ( is_int( $number ) || floor( $number ) === $number ) {
			return 0;
		}

		// format it without exponent and count the decimals without trailing zeros.
		$formatted = rtrim( sprintf( '%.10F', $number ), '0' );
		$position  = strpos( $formatted, '.' );

		return false === $position ? 0 : strlen( $formatted ) - $position - 1;
	}

	/**
	 * Return the inline style with the position of the filled part of the slider.
	 *
	 * @param array{min: int|float, max: int|float} $value The value.
	 *
	 * @return string
	 */
	private function get_fill_style( array $value ): string {
		list( $min, $max ) = $this->get_bounds();
		$range             = $max - $min;

		// avoid a division by zero.
		if ( $range <= 0 ) {
			return '--esfw-range-from: 0%; --esfw-range-to: 100%;';
		}

		$from = ( $value['min'] - $min ) / $range * 100;
		$to   = ( $value['max'] - $min ) / $range * 100;

		return sprintf( '--esfw-range-from: %.4F%%; --esfw-range-to: %.4F%%;', $from, $to );
	}
}
