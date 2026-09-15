/**
 * Render a single nested field (a MultiField entry or a FieldTable cell).
 *
 * The top-level fields are rendered by the DataViews DataForm, which calls each
 * field's Edit component with `{ field, data, onChange }` and expects
 * `onChange( { [field.id]: value } )`. Nested fields are rendered manually, so
 * this component bridges the two worlds:
 *
 * - For a type with a custom Edit component (from getEditComponent), it builds a
 *   synthetic field + data pair and unwraps the component's onChange back into a
 *   plain value.
 * - For a DataViews-native type (text, textarea, select, radio, number,
 *   password, boolean) it renders an equivalent WordPress control directly,
 *   since there is no DataForm here to fall back on.
 *
 * In every case the caller gets a plain `onChange( value )`.
 */
import {
  CheckboxControl,
  RadioControl,
  SelectControl,
  TextControl,
  TextareaControl,
} from '@wordpress/components';

import { getEditComponent } from './editor-registry';

/**
 * Return a type-appropriate empty value for a descriptor.
 *
 * @param {Object} descriptor The field descriptor.
 * @return {*} The empty value.
 */
export function emptyValueFor( descriptor ) {
  switch ( descriptor?.type ) {
    case 'boolean':
      return false;
    case 'integer':
      return 0;
    case 'esfw-multiselect':
      return [];
    case 'esfw-checkboxes':
      return {};
    case 'media':
      return descriptor.multiple ? [] : 0;
    case 'esfw-post-select':
      return 0;
    default:
      return '';
  }
}

/**
 * @param {Object}   props            Component props.
 * @param {Object}   props.descriptor The field descriptor.
 * @param {*}        props.value      The current value.
 * @param {Function} props.onChange   Called with the new value.
 * @param {string}   [props.label]    Optional label override.
 * @param {boolean}  [props.hideDescription] Suppress the field description.
 * @return {JSX.Element|null} The control.
 */
export function InnerFieldControl( {
  descriptor,
  value,
  onChange,
  label,
  hideDescription = false,
} ) {
  if ( ! descriptor ) {
    return null;
  }

  const effectiveLabel = label ?? descriptor.label;
  const EditComponent = getEditComponent( descriptor );
  const fieldName = descriptor.id || '';
  const wrapperClassName = fieldName
    ? `esfw-field esfw-field--${ fieldName }`
    : 'esfw-field';

  if ( EditComponent ) {
    const syntheticId = descriptor.id || '__inner__';
    const syntheticField = {
      ...descriptor,
      id: syntheticId,
      label: effectiveLabel,
      description: hideDescription ? undefined : descriptor.description,
      getValue: ( { item } ) => ( item ? item[ syntheticId ] : value ),
    };
    const syntheticData = { [ syntheticId ]: value };

    const handleChange = ( edits ) => {
      if (
        edits &&
        Object.prototype.hasOwnProperty.call( edits, syntheticId )
      ) {
        onChange( edits[ syntheticId ] );
        return;
      }
      // fall back to the first provided value (defensive).
      const values = Object.values( edits ?? {} );
      onChange( values.length ? values[ 0 ] : value );
    };

    return (
      <div className={ wrapperClassName }>
        <EditComponent field={ syntheticField } data={ syntheticData } onChange={ handleChange } />
      </div>
    );
  }

  // DataViews-native types: render an equivalent control directly.
  if ( descriptor.type === 'boolean' ) {
    return (
      <div className={ wrapperClassName }>
        <CheckboxControl
          label={ effectiveLabel }
          checked={ !! value }
          onChange={ onChange }
          disabled={ !! descriptor.readOnly }
          __nextHasNoMarginBottom
        />
      </div>
    );
  }

  if ( descriptor.type === 'integer' ) {
    return (
      <div className={ wrapperClassName }>
        <TextControl
          type="number"
          label={ effectiveLabel }
          value={ value ?? '' }
          onChange={ ( newValue ) =>
            onChange( newValue === '' ? 0 : parseInt( newValue, 10 ) )
          }
          disabled={ !! descriptor.readOnly }
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
      </div>
    );
  }

  if ( descriptor.Edit === 'textarea' ) {
    return (
      <div className={ wrapperClassName }>
        <TextareaControl
          label={ effectiveLabel }
          value={ value ?? '' }
          onChange={ onChange }
          disabled={ !! descriptor.readOnly }
          __nextHasNoMarginBottom
        />
      </div>
    );
  }

  if ( descriptor.Edit === 'select' ) {
    return (
      <div className={ wrapperClassName }>
        <SelectControl
          label={ effectiveLabel }
          value={ value ?? '' }
          options={ ( descriptor.elements ?? [] ).map( ( element ) => ( {
            label: element.label,
            value: element.value,
          } ) ) }
          onChange={ onChange }
          disabled={ !! descriptor.readOnly }
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
      </div>
    );
  }

  if ( descriptor.Edit === 'radio' ) {
    return (
      <div className={ wrapperClassName }>
        <RadioControl
          label={ effectiveLabel }
          selected={ value ?? '' }
          options={ ( descriptor.elements ?? [] ).map( ( element ) => ( {
            label: element.label,
            value: element.value,
          } ) ) }
          onChange={ onChange }
          disabled={ !! descriptor.readOnly }
        />
      </div>
    );
  }

  const inputType = descriptor.Edit === 'password' ? 'password' : 'text';

  return (
    <div className={ wrapperClassName }>
      <TextControl
        type={ inputType }
        label={ effectiveLabel }
        value={ value ?? '' }
        onChange={ onChange }
        disabled={ !! descriptor.readOnly }
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    </div>
  );
}
