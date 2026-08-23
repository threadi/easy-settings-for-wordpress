import { BaseControl } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Render the table field.
 *
 * The Table field renders itself server-side - entries plus their configured
 * per-entry option links, exactly like the classic WP_List_Table view - and
 * hands the result to the DataView as ready-made HTML via field.content (see
 * Views/DataView.php::get_field_content()). This avoids duplicating that
 * rendering, including each entry's configurable action links, in React.
 *
 * @param {string} content The pre-rendered table HTML (may be empty).
 * @return {Function} The Edit component.
 */
export function createTableEdit( content ) {
  return function TableEdit( { field } ) {
    const ref = useRef( null );

    useEffect( () => {
      if ( ! content || ! ref.current ) {
        return;
      }
      // Re-bind click handlers in case an option link opens a dialog.
      document.body.dispatchEvent(
        new Event( 'easy-dialog-for-wordpress-reinit' )
      );
    }, [ content ] );

    if ( ! content ) {
      return null;
    }

    return (
      <fieldset>
        <legend><BaseControl.VisualLabel>{ field.label }</BaseControl.VisualLabel></legend>
        <div
          ref={ ref }
          className="esfw-table"
          dangerouslySetInnerHTML={ { __html: content } }
        />
      </fieldset>
    );
  };
}
