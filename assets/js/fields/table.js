/**
 * Create the read-only table output.
 *
 * TODO Allow editing for fields in this table later.
 *
 * @return {Function} The Edit component.
 */
export function createTableEdit() {
  return function TableEdit( { field, data } ) {
    const rows = field.getValue( { item: data } ) ?? [];

    if ( rows.length === 0 ) {
      return null;
    }

    return (
      <table className="widefat striped">
        <tbody>
          { rows.map( ( row, index ) => (
            <tr key={ index }>
              <td>{ row }</td>
            </tr>
          ) ) }
        </tbody>
      </table>
    );
  };
}
