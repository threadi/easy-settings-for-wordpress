import { Button, CheckboxControl, TextControl } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { seen, unseen } from '@wordpress/icons';

/**
 * Create the password field for the DataView.
 *
 * The stored value is never sent to the browser (the REST endpoint returns an empty
 * string), so this control:
 * - shows a masked placeholder and a hint if a value is stored,
 * - offers the "show value" toggle only for a newly entered value,
 * - keeps the stored value on save if the field is left empty (handled in PHP),
 * - optionally offers a checkbox to delete the stored value.
 *
 * @param {Object}  config              The field configuration.
 * @param {boolean} config.hasValue     Whether a value is stored.
 * @param {string}  config.placeholder  The placeholder if a value is stored.
 * @param {string}  config.storedHint   Hint shown if a value is stored.
 * @param {boolean} config.deleteOption Whether the delete checkbox is shown.
 * @param {string}  config.deleteLabel  Label of the delete checkbox.
 * @param {string}  config.deleteHint   Hint shown if the value will be deleted.
 * @param {string}  config.deleteToken  Value sent to delete the stored value.
 * @param {string}  config.showLabel    Label of the button to show the value.
 * @param {string}  config.hideLabel    Label of the button to hide the value.
 * @return {Function} The Edit component.
 */
export function createPasswordEdit( config ) {
  return function PasswordEdit( { field, data, onChange } ) {
    const value = field.getValue( { item: data } ) ?? '';
    const isDelete = value === config.deleteToken;
    const isReadOnly = !! field.readOnly;

    const [ hasStored, setHasStored ] = useState( !! config.hasValue );
    const [ isVisible, setIsVisible ] = useState( false );
    const previousValueRef = useRef( value );
    const isUserChangeRef = useRef( false );

    // A change of the value without user input comes from a save: the server returns
    // the masked (empty) value. Update the "stored" state accordingly.
    useEffect( () => {
      const previousValue = previousValueRef.current;
      previousValueRef.current = value;

      if ( isUserChangeRef.current ) {
        isUserChangeRef.current = false;
        return;
      }

      if ( '' !== value || previousValue === value ) {
        return;
      }

      if ( previousValue === config.deleteToken ) {
        setHasStored( false );
      } else if ( '' !== previousValue ) {
        setHasStored( true );
      }
      setIsVisible( false );
    }, [ value ] );

    const change = ( newValue ) => {
      isUserChangeRef.current = true;
      onChange( { [ field.id ]: newValue } );
    };

    const canToggle = '' !== value && ! isDelete;

    let hint = null;
    if ( isDelete ) {
      hint = config.deleteHint;
    } else if ( hasStored ) {
      hint = config.storedHint;
    }

    return (
      <div className="esfw-password">
        <div style={ { display: 'flex', alignItems: 'flex-end', gap: '4px' } }>
          <div style={ { flex: 1 } }>
            <TextControl
              type={ isVisible && canToggle ? 'text' : 'password' }
              label={ field.label }
              value={ isDelete ? '' : value }
              placeholder={ hasStored && ! isDelete ? config.placeholder : '' }
              autoComplete="new-password"
              onChange={ change }
              disabled={ isReadOnly || isDelete }
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
          </div>
          { canToggle && (
            <Button
              icon={ isVisible ? unseen : seen }
              label={ isVisible ? config.hideLabel : config.showLabel }
              onClick={ () => setIsVisible( ! isVisible ) }
              __next40pxDefaultSize
            />
          ) }
        </div>
        { ( field.description || hint ) && (
          <p className="components-base-control__help">
            { field.description }
            { field.description && hint && <br /> }
            { hint }
          </p>
        ) }
        { config.deleteOption && hasStored && ! isReadOnly && (
          <CheckboxControl
            label={ config.deleteLabel }
            checked={ isDelete }
            onChange={ ( checked ) => change( checked ? config.deleteToken : '' ) }
            __nextHasNoMarginBottom
          />
        ) }
      </div>
    );
  };
}
