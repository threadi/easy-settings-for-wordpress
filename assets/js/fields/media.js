import { MediaEdit } from '@wordpress/fields';

/**
 * Allowed file extensions per media library types.
 *
 * Key: the library types as JSON (e.g. '["text/csv"]').
 * Value: the comma-separated list of extensions (e.g. "csv").
 *
 * @type {Map<string, string>}
 */
const extensionsByLibraryTypes = new Map();

/**
 * Whether the uploader window of wp.media is already patched.
 *
 * @type {boolean}
 */
let isUploaderWindowPatched = false;

/**
 * Restrict the uploader of media frames showing the given library types to the given extensions.
 *
 * MediaEdit (via MediaUpload from @wordpress/media-utils) creates its own
 * wp.media frame and only passes the allowed types as library filter. The
 * plupload instance of a frame is created in UploaderWindow.ready() and copies
 * wp.Uploader.defaults at this moment. So we wrap ready() once: if the frame
 * shows library types we know, the extensions are set in the defaults only for
 * the time of this call. Frames with other library types stay untouched.
 *
 * @param {string[]} allowedTypes      The library types of the field.
 * @param {string}   allowedExtensions The comma-separated list of allowed extensions.
 */
function registerUploadRestriction( allowedTypes, allowedExtensions ) {
  // bail if there is nothing to restrict.
  if ( ! Array.isArray( allowedTypes ) || ! allowedTypes.length || ! allowedExtensions ) {
    return;
  }

  // remember the extensions for these library types.
  extensionsByLibraryTypes.set( JSON.stringify( allowedTypes ), allowedExtensions );

  // bail if already patched.
  if ( isUploaderWindowPatched ) {
    return;
  }

  // bail if wp.media or wp.Uploader are not available.
  const UploaderWindow = window.wp?.media?.view?.UploaderWindow;
  if ( ! UploaderWindow || ! window.wp?.Uploader?.defaults ) {
    return;
  }

  isUploaderWindowPatched = true;

  const originalReady = UploaderWindow.prototype.ready;
  UploaderWindow.prototype.ready = function ( ...args ) {
    const libraryTypes = this.controller?.options?.library?.type;
    const extensions = libraryTypes ? extensionsByLibraryTypes.get( JSON.stringify( libraryTypes ) ) : undefined;

    // use the default behaviour for all other frames.
    if ( ! extensions ) {
      return originalReady.apply( this, args );
    }

    const defaults = window.wp.Uploader.defaults;
    const originalFilters = defaults.filters;

    defaults.filters = {
      ...originalFilters,
      mime_types: [ { title: '', extensions } ],
    };

    try {
      return originalReady.apply( this, args );
    } finally {
      defaults.filters = originalFilters;
    }
  };
}

/**
 * Create the custom media field.
 *
 * @param {Object}   config                   The media configuration.
 * @param {boolean}  config.multiple          Whether multiple files can be chosen.
 * @param {string[]} config.allowedTypes      The MIME types to filter the media library with.
 * @param {string}   config.allowedExtensions The comma-separated list of extensions allowed for uploads.
 * @return {Function} The Edit component.
 */
export function createMediaFieldEdit( { multiple, allowedTypes, allowedExtensions } ) {
  return function CustomMediaEdit( props ) {
    const { onChange, field } = props;

    // restrict uploads in the media modal to the allowed extensions.
    registerUploadRestriction( allowedTypes, allowedExtensions );

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
        className="esfw-file-choose"
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
