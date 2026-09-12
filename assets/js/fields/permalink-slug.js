import { Button, TextControl } from '@wordpress/components';

/**
 * Create the custom permalink slug field.
 *
 * @param {Object} config The field configuration.
 * @return {Function} The Edit component.
 */
export function createPermalinkSlugEdit( { options, listTitle } ) {
  return function PermalinkSlugEdit( { field, data, onChange } ) {
    const currentValue = field.getValue( { item: data } ) ?? '';

    const insertPlaceholder = ( placeholder ) => {
      onChange( { [ field.id ]: currentValue + placeholder } );
    };

    return (
      <div>
        <TextControl
          label={ field.label }
          value={ currentValue }
          onChange={ ( value ) => onChange( { [ field.id ]: value } ) }
          disabled={ !! field.readOnly }
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
        { options.length > 0 && (
          <fieldset>
            <legend>{ listTitle }</legend>
            { options.map( ( option ) => (
              <Button
                key={ option.placeholder }
                variant="secondary"
                className={
                  currentValue.includes( option.placeholder ) ? 'active' : ''
                }
                onClick={ () => insertPlaceholder( option.placeholder ) }
                disabled={ !! field.readOnly }
              >
                { option.label }
              </Button>
            ) ) }
          </fieldset>
        ) }
      </div>
    );
  };
}
