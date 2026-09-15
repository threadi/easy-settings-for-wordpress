import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { applyFilters, doAction } from '@wordpress/hooks';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Collect human-readable messages from a REST/apiFetch error.
 *
 * A failed POST to /wp/v2/settings rejects with a WP_Error-shaped object. The
 * top-level message covers the common single-field case (e.g. "The X property
 * has an invalid stored value..."); per-parameter problems may additionally
 * sit under data.details or additional_errors.
 *
 * @param {Object} error The rejected error object.
 * @return {Array<string>} The de-duplicated messages.
 */
function collectErrorMessages( error ) {
  const messages = [];
  const push = ( message ) => {
    if ( message && ! messages.includes( message ) ) {
      messages.push( message );
    }
  };

  push( error?.message );

  const details = error?.data?.details;
  if ( details && typeof details === 'object' ) {
    Object.values( details ).forEach( ( detail ) => push( detail?.message ) );
  }

  if ( Array.isArray( error?.additional_errors ) ) {
    error.additional_errors.forEach( ( detail ) => push( detail?.message ) );
  }

  return messages;
}

/**
 * Load, hold and persist settings for the DataView page.
 *
 * @param {Object}   props
 * @param {Object}   props.config
 * @param {Function} [props.onSoftReload]
 * @return {[Object, Function, Function, boolean, boolean]} settings, setSettings, saveSettings, isSaving, isLoading
 */
export const useSettings = ( props ) => {
  const [ settings, setSettings ] = useState( {} );
  const [ isSaving, setIsSaving ] = useState( false );
  const [ isLoading, setIsLoading ] = useState( true );
  const initialSettingsRef = useRef( {} );

  const { createSuccessNotice, createErrorNotice } = useDispatch( noticesStore );

  useEffect( () => {
    apiFetch( { path: '/wp/v2/settings', method: 'OPTIONS' } ).then( ( schema ) => {
      const loadedFields = schema?.schema?.properties ?? {};
      const fields = Object.keys( loadedFields ).filter(
        ( key ) => loadedFields[ key ]?.[ props.config.slug ] === true
      );

      if ( fields.length === 0 ) {
        setSettings( {} );
        setIsLoading( false );
        return;
      }

      apiFetch( { path: '/wp/v2/settings' } ).then( ( wpSettings ) => {
        const values = {};
        fields.forEach( ( key ) => {
          if ( key in wpSettings ) {
            values[ key ] = wpSettings[ key ];
          }
        } );
        initialSettingsRef.current = values;
        setSettings( values );
        setIsLoading( false );
      } );
    } );
  }, [] );

  /**
   * Persist settings to the REST API.
   *
   * @param {Object}  [options]
   * @param {Object}  [options.values]      Values to save (defaults to current state).
   * @param {boolean} [options.softReload]  Force soft-reload after a successful save.
   * @param {boolean} [options.silent]      Skip the success snackbar.
   * @return {void}
   */
  const saveSettings = ( options = {} ) => {
    if ( isSaving ) {
      return;
    }

    setIsSaving( true );

    const source = options.values ?? settings;

    const settingsToSave = applyFilters(
      'esfw.settingsPage.beforeSave',
      Object.fromEntries(
        Object.entries( source ).filter( ( [ key, value ] ) =>
          null !== value || null !== initialSettingsRef.current[ key ]
        )
      ),
      props.config
    );

    apiFetch( {
      path: '/wp/v2/settings',
      method: 'POST',
      data: settingsToSave,
    } )
      .then( async ( response ) => {
        doAction( 'esfw.settingsPage.afterSave', settingsToSave, props.config );

        const fields = props.config.fields ?? [];

        const persisted = { ...source, ...settingsToSave };
        fields.forEach( ( field ) => {
          if ( response && field.id in response ) {
            persisted[ field.id ] = response[ field.id ];
          }
        } );
        setSettings( persisted );

        let redirectUrl = null;
        let shouldReload = false;
        let shouldSoftReload = !! options.softReload;

        for ( const field of fields ) {
          const id = field.id;
          if ( ! ( id in persisted ) ) {
            continue;
          }

          const prev = initialSettingsRef.current[ id ];
          const next = persisted[ id ];
          if ( JSON.stringify( prev ) === JSON.stringify( next ) ) {
            continue;
          }

          if ( field.redirect_on_save ) {
            redirectUrl = field.redirect_on_save;
            break;
          }
          if ( field.reload_on_save ) {
            shouldReload = true;
          }
          if ( field.soft_reload_on_save ) {
            shouldSoftReload = true;
          }
        }

        initialSettingsRef.current = { ...persisted };

        if ( redirectUrl ) {
          createSuccessNotice( props.config.settings_saved_redirect, { type: 'snackbar' } );
          window.location.assign( redirectUrl );
          return;
        }
        if ( shouldReload ) {
          createSuccessNotice( props.config.settings_saved_reload, { type: 'snackbar' } );
          window.location.reload();
          return;
        }

        if ( shouldSoftReload && typeof props.onSoftReload === 'function' ) {
          try {
            await props.onSoftReload( persisted );
          } catch ( e ) {
            // error in config-fetch.
          }
          if ( ! options.silent ) {
            createSuccessNotice( props.config.settings_saved, { type: 'snackbar' } );
          }
          setIsSaving( false );
          return;
        }

        const settingsErrors = Array.isArray( response?.esfw_settings_errors )
          ? response.esfw_settings_errors
          : [];

        if ( settingsErrors.length > 0 ) {
          settingsErrors.forEach( ( settingsError ) => {
            createErrorNotice( settingsError.message, { type: 'snackbar' } );
          } );
        } else if ( ! options.silent ) {
          createSuccessNotice( props.config.settings_saved, { type: 'snackbar' } );
        }

        setIsSaving( false );
      } )
      .catch( ( error ) => {
        doAction( 'esfw.settingsPage.saveError', error, props.config );

        const technicalMessages = props.config.developer_mode
          ? collectErrorMessages( error )
          : [];

        if ( technicalMessages.length > 0 ) {
          const prefix = props.config.settings_save_error_details ?? '';
          technicalMessages.forEach( ( message ) => {
            createErrorNotice(
              prefix ? `${ prefix } ${ message }` : message,
              { type: 'snackbar' }
            );
          } );
        } else {
          createErrorNotice( props.config.settings_save_error, {
            type: 'snackbar',
          } );
        }

        setIsSaving( false );
      } );
  };

  return [ settings, setSettings, saveSettings, isSaving, isLoading ];
};
