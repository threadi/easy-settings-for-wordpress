import { BaseControl, Button, ColorIndicator, ColorPicker, Dropdown, __experimentalHStack as HStack } from '@wordpress/components';

/**
 * Create the custom color field.
 *
 * @return {Function} The Edit component.
 */
export function createColorEdit() {
  return function ColorEdit( { field, data, onChange } ) {
    const currentValue = field.getValue( { item: data } ) ?? '';

    // render the field label and description ourselves (like the other custom
    // fields do); the DataForm does not render them for custom Edit components.
    return (
      <div>
        { field.label && <label><BaseControl.VisualLabel>{ field.label }:</BaseControl.VisualLabel></label> }
        <HStack justify="flex-start" spacing={ 2 }>
          <Dropdown
            popoverProps={ { placement: 'bottom-start' } }
            renderToggle={ ( { isOpen, onToggle } ) => (
              <Button
                variant="secondary"
                onClick={ onToggle }
                aria-expanded={ isOpen }
              >
                <HStack spacing={ 2 } justify="flex-start">
                  { currentValue && <ColorIndicator colorValue={ currentValue } /> }
                  <span>Choose color</span>
                </HStack>
              </Button>
            ) }
            renderContent={ () => (
              <ColorPicker
                enableAlpha
                color={ currentValue }
                onChange={ ( newValue ) => onChange( { [ field.id ]: newValue } ) }
                defaultValue="#000"
              />
            ) }
          />
          { currentValue && (
            <span style={ { fontFamily: 'monospace' } }>{ currentValue }</span>
          ) }
        </HStack>
        { field.description && (
          <p className="components-base-control__help">{ field.description }</p>
        ) }
      </div>
    );
  };
}
