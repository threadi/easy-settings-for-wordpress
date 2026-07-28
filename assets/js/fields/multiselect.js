import { FormTokenField } from '@wordpress/components';

/**
 * Create the custom multiselect field.
 *
 * @param {Array} options The available options.
 * @return {Function} The Edit component.
 */
export function createMultiSelectEdit( options ) {
  const labelByValue = Object.fromEntries( options.map( ( o ) => [ o.value, o.label ] ) );
  const valueByLabel = Object.fromEntries( options.map( ( o ) => [ o.label, o.value ] ) );

  return function MultiSelectEdit( { field, data, onChange } ) {
    const currentValues = field.getValue( { item: data } ) ?? [];
    const currentLabels = currentValues.map( ( v ) => labelByValue[ v ] ?? v );

    const handleChange = ( newLabels ) => {
      onChange( {
        [ field.id ]: newLabels.map( ( label ) => valueByLabel[ label ] ?? label ),
      } );
    };

    return (
      <FormTokenField
        label={ field.label }
        value={ currentLabels }
        suggestions={ options.map( ( o ) => o.label ) }
        onChange={ handleChange }
        __next40pxDefaultSize
      />
    );
  };
}
