import { BaseControl, TextControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Round a number to the precision of the field (null: only remove floating point noise).
 *
 * @param {number}      number    The number.
 * @param {number|null} precision The number of decimals.
 * @return {number} The rounded number.
 */
function roundTo( number, precision ) {
  const decimals = null === precision || undefined === precision ? 10 : precision;
  return Number( Math.round( Number( number + 'e' + decimals ) ) + 'e-' + decimals );
}

/**
 * Snap a number to the steps (counted from the lower bound) and clamp it into the bounds.
 *
 * @param {number} number The number.
 * @param {Object} config The field configuration.
 * @return {number} The snapped number.
 */
function snap( number, config ) {
  let result = number;
  if ( config.step > 0 ) {
    result = config.min + Math.round( ( result - config.min ) / config.step ) * config.step;
  }
  result = roundTo( result, config.precision );
  return Math.min( config.max, Math.max( config.min, result ) );
}

/**
 * Bring a value into a valid shape after one handle has been moved.
 *
 * Mirrors Range::normalize_value() in PHP, which validates the value again on save.
 *
 * @param {Object} value  The value with "min" and "max".
 * @param {string} moved  Which part has been changed: "min" or "max".
 * @param {Object} config The field configuration.
 * @return {{min: number, max: number}} The value.
 */
function normalize( value, moved, config ) {
  let lower = snap( value.min, config );
  let upper = snap( value.max, config );
  const gap = config.minGap;

  if ( upper - lower < gap ) {
    if ( gap >= config.max - config.min ) {
      lower = config.min;
      upper = config.max;
    } else if ( 'min' === moved ) {
      lower = Math.max( config.min, roundTo( upper - gap, config.precision ) );
      upper = Math.max( upper, Math.min( config.max, roundTo( lower + gap, config.precision ) ) );
    } else {
      upper = Math.min( config.max, roundTo( lower + gap, config.precision ) );
      lower = Math.min( lower, Math.max( config.min, roundTo( upper - gap, config.precision ) ) );
    }
  }

  return { min: lower, max: upper };
}

/**
 * Number field for one of both values, committed when the input is a valid number
 * and corrected on blur (so typing e.g. "150" is not interrupted at "1").
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.label    The label.
 * @param {number}   props.value    The current value.
 * @param {Object}   props.config   The field configuration.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @param {Function} props.onCommit Called with the new number, returns the resulting value.
 * @return {JSX.Element} The control.
 */
function RangeNumber( { label, value, config, disabled, onCommit } ) {
  const [ text, setText ] = useState( String( value ) );
  const parse = ( input ) => parseFloat( String( input ).replace( ',', '.' ) );

  // follow changes from the slider, but do not touch an input that already means this value (e.g. "1.").
  useEffect( () => {
    setText( ( previous ) => ( parse( previous ) === value ? previous : String( value ) ) );
  }, [ value ] );

  return (
    <div className="esfw-range__value">
      <TextControl
        type="number"
        label={ label }
        value={ text }
        min={ config.min }
        max={ config.max }
        step={ config.step > 0 ? config.step : 'any' }
        disabled={ disabled }
        onChange={ ( newText ) => {
          setText( newText );
          const number = parse( newText );
          if ( ! isNaN( number ) && number >= config.min && number <= config.max ) {
            onCommit( number, false );
          }
        } }
        onBlur={ () => {
          const number = parse( text );
          // show the corrected value (snapped, in order and with the gap).
          setText( String( onCommit( isNaN( number ) ? value : number, true ) ) );
        } }
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    </div>
  );
}

/**
 * Create the range field: a slider with two handles for a minimum and a maximum value.
 *
 * @param {Object}      options           The configuration from PHP.
 * @param {number}      options.min       The lowest possible value.
 * @param {number}      options.max       The highest possible value.
 * @param {number}      options.step      The step (0 = any value).
 * @param {number}      options.minGap    The minimal distance between both values.
 * @param {number|null} options.precision The number of decimals (null = any).
 * @param {Object}      options.fallback  The value to use if nothing is stored.
 * @param {string}      options.minLabel  The label for the minimum.
 * @param {string}      options.maxLabel  The label for the maximum.
 * @return {Function} The Edit component.
 */
export function createRangeEdit( {
  min = 0,
  max = 100,
  step = 1,
  minGap = 0,
  precision = 0,
  fallback,
  minLabel = 'Minimum',
  maxLabel = 'Maximum',
} = {} ) {
  const config = {
    min: Number( min ),
    max: Number( max ),
    step: Number( step ) || 0,
    minGap: Number( minGap ) || 0,
    precision,
  };
  const fallbackValue = {
    min: Number( fallback?.min ?? config.min ),
    max: Number( fallback?.max ?? config.max ),
  };

  return function RangeEdit( { field, data, onChange } ) {
    const raw = field.getValue( { item: data } );
    const current = {
      min: isNaN( parseFloat( raw?.min ) ) ? fallbackValue.min : parseFloat( raw.min ),
      max: isNaN( parseFloat( raw?.max ) ) ? fallbackValue.max : parseFloat( raw.max ),
    };
    const disabled = !! field.readOnly;
    const span = config.max - config.min;
    const from = span > 0 ? ( ( current.min - config.min ) / span ) * 100 : 0;
    const to = span > 0 ? ( ( current.max - config.min ) / span ) * 100 : 100;

    const commit = ( part, number, strict = true ) => {
      const next = { ...current, [ part ]: number };
      // while typing, keep the entered number as it is; steps, order and gap are applied on blur.
      const value = strict ? normalize( next, part, config ) : next;
      onChange( { [ field.id ]: value } );
      return value[ part ];
    };

    const prefix = field.label ? `: ${ field.label }` : '';
    const classNames = [ 'esfw-range' ];
    if ( disabled ) {
      classNames.push( 'is-disabled' );
    }
    if ( span > 0 && current.min >= config.min + span / 2 ) {
      classNames.push( 'is-min-on-top' );
    }

    const rangeProps = {
      type: 'range',
      min: config.min,
      max: config.max,
      step: config.step > 0 ? config.step : 'any',
      disabled,
    };

    // render the field label and description ourselves (like the other custom
    // fields do); the DataForm does not render them for custom Edit components.
    return (
      <div>
        { field.label && <label><BaseControl.VisualLabel>{ field.label }:</BaseControl.VisualLabel></label> }
        <div
          className={ classNames.join( ' ' ) }
          style={ { '--esfw-range-from': `${ from }%`, '--esfw-range-to': `${ to }%` } }
        >
          <div className="esfw-range__slider">
            <div className="esfw-range__track"><div className="esfw-range__fill"></div></div>
            <input
              { ...rangeProps }
              className="esfw-range__input esfw-range__input--min"
              value={ current.min }
              aria-label={ minLabel + prefix }
              onChange={ ( event ) => commit( 'min', parseFloat( event.target.value ) ) }
            />
            <input
              { ...rangeProps }
              className="esfw-range__input esfw-range__input--max"
              value={ current.max }
              aria-label={ maxLabel + prefix }
              onChange={ ( event ) => commit( 'max', parseFloat( event.target.value ) ) }
            />
          </div>
          <div className="esfw-range__values">
            <RangeNumber
              label={ minLabel }
              value={ current.min }
              config={ config }
              disabled={ disabled }
              onCommit={ ( number, strict ) => commit( 'min', number, strict ) }
            />
            <RangeNumber
              label={ maxLabel }
              value={ current.max }
              config={ config }
              disabled={ disabled }
              onCommit={ ( number, strict ) => commit( 'max', number, strict ) }
            />
          </div>
        </div>
        { field.description && (
          <p className="components-base-control__help">{ field.description }</p>
        ) }
      </div>
    );
  };
}
