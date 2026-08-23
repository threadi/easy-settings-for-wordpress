import { BaseControl } from '@wordpress/components';

import { InnerFieldControl } from './inner-field';

/**
 * Create the FieldTable editor.
 *
 * A FieldTable is a layout container: each cell holds one or more independent
 * settings, each with its own option id. Unlike a MultiField (one option, an
 * array of values), the cell editors read from and write to their own top-level
 * setting ids in the shared data object.
 *
 * @param {Object} config         The field configuration.
 * @param {Array}  config.columns The column labels.
 * @param {Array}  config.rows    Rows -> columns -> array of cell descriptors.
 * @return {Function} The Edit component.
 */
export function createFieldTableEdit( { columns, rows } ) {
  const columnLabels = columns ?? [];
  const grid = rows ?? [];

  return function FieldTableEdit( { field, data, onChange } ) {
    return (
      <fieldset className="esfw-field-table">
        <legend>
          <BaseControl.VisualLabel>{ field.label }</BaseControl.VisualLabel>
        </legend>
        { field.description && (
          <p className="components-base-control__help">{ field.description }</p>
        ) }
        <table className="esfw-field-table__table">
          <thead>
            <tr>
              { columnLabels.map( ( label, index ) => (
                <th key={ index }>{ label }</th>
              ) ) }
            </tr>
          </thead>
          <tbody>
            { grid.map( ( row, rowIndex ) => (
              <tr key={ rowIndex }>
                { row.map( ( cell, columnIndex ) => (
                  <td key={ columnIndex }>
                    { ( cell ?? [] ).map( ( cellDescriptor ) => (
                      <InnerFieldControl
                        key={ cellDescriptor.id }
                        descriptor={ cellDescriptor }
                        value={ data?.[ cellDescriptor.id ] }
                        onChange={ ( newValue ) =>
                          onChange( { [ cellDescriptor.id ]: newValue } )
                        }
                        label={ cellDescriptor.label }
                      />
                    ) ) }
                  </td>
                ) ) }
              </tr>
            ) ) }
          </tbody>
        </table>
      </fieldset>
    );
  };
}
