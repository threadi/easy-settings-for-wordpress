import { MediaEdit } from '@wordpress/fields';

/**
 * Create the custom media field.
 *
 * @param {Object} config The media configuration.
 * @return {Function} The Edit component.
 */
export function createMediaFieldEdit( { multiple, allowedTypes } ) {
  return function CustomMediaEdit( props ) {
    const { onChange, field } = props;

    // ensure a cleared selection sends an explicit empty value instead of
    // dropping out of the request (undefined is stripped by JSON.stringify).
    const handleChange = ( edits ) => {
      if ( field?.readOnly ) {
        return;
      }
      const empty = multiple ? [] : 0;
      const next = { ...edits };
      Object.keys( next ).forEach( ( key ) => {
        if ( next[ key ] === undefined || next[ key ] === null ) {
          next[ key ] = empty;
        }
      } );
      onChange( next );
    };

    return (
      <div
        className="esfw-image-choose"
        style={ field?.readOnly ? { pointerEvents: 'none', opacity: 0.7 } : undefined }
      >
        <MediaEdit
          { ...props }
          onChange={ handleChange }
          multiple={ multiple }
          allowedTypes={ allowedTypes ?? [ 'image' ] }
        />
      </div>
    );
  };
}
