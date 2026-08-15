import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { applyFilters, doAction } from '@wordpress/hooks';
import { store as noticesStore } from '@wordpress/notices';

export const useSettings = ( props ) => {
  const [ settings, setSettings ] = useState( {} );
  const [ isSaving, setIsSaving ] = useState( false );
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
      } );
    } );
  }, [] );

  const saveSettings = () => {
    if ( isSaving ) {
      return;
    }

    setIsSaving( true );

    const settingsToSave = applyFilters(
      'esfw.settingsPage.beforeSave',
      settings,
      props.config
    );

    apiFetch( {
      path: '/wp/v2/settings',
      method: 'POST',
      data: settingsToSave,
    } )
      .then( () => {
        doAction( 'esfw.settingsPage.afterSave', settingsToSave, props.config );

        // Per-setting reload / redirect (only if value actually changed).
        const fields = props.config.fields ?? [];
        let redirectUrl = null;
        let shouldReload = false;

        for ( const field of fields ) {
          const id = field.id;
          if ( ! ( id in settingsToSave ) ) {
            continue;
          }

          const prev = initialSettingsRef.current[ id ];
          const next = settingsToSave[ id ];
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
        }

        // Baseline für künftige Saves aktualisieren.
        initialSettingsRef.current = { ...settingsToSave };

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

        createSuccessNotice( props.config.settings_saved, { type: 'snackbar' } );
        setIsSaving( false );
      } )
      .catch( ( error ) => {
        createErrorNotice( props.config.settings_save_error, { type: 'snackbar' } );
        doAction( 'esfw.settingsPage.saveError', error, props.config );
        setIsSaving( false );
      } );
  };

  return [ settings, setSettings, saveSettings, isSaving ];
};
