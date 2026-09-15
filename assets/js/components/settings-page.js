/**
 * The main settings page component.
 */
import {
  __experimentalVStack as VStack,
  Card,
  CardBody,
  Spinner,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews';
import { useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';

import { useSettings } from '../hooks/use-settings';
import { mapFields } from '../fields';
import { getVisibleFieldIds } from '../utils/visibility';
import { Notices, SnackbarNotices } from './notices';
import { SaveButton } from './save-button';
import { SettingsTitle } from './settings-title';
import { TabNode } from './tab-node';

/**
 * Render the settings page.
 *
 * @param {Object} props        Component props.
 * @param {Object} props.config The settings configuration from PHP.
 * @return {JSX.Element} The page.
 */
export const SettingsPage = ( props ) => {
  // Config lives in state so a soft-reload can replace fields/tabs without a full page reload.
  const [ config, setConfig ] = useState( props.config );
  const [ configVersion, setConfigVersion ] = useState( 0 );

  /**
   * Re-fetch the DataView configuration from the REST API and apply it.
   * Called after a successful save when a field requested a soft-reload.
   *
   * @param {Object} _persistedSettings Settings already written by useSettings.
   * @return {Promise<void>}
   */
  const handleSoftReload = useCallback(
    async ( _persistedSettings ) => {
      const path = config.rest_config_path;
      if ( ! path ) {
        return;
      }

      const request = /^https?:\/\//i.test( path ) || path.includes( 'rest_route=' )
        ? { url: path }
        : { path };

      const fresh = await apiFetch( request );

      setConfig( ( prev ) => ( {
        ...prev,
        ...fresh,
        fields: fresh.fields ?? prev.fields,
        tabs: fresh.tabs ?? prev.tabs,
      } ) );

      // Bump version so DataForm instances remount with the new field descriptors.
      setConfigVersion( ( version ) => version + 1 );
    },
    [ config.rest_config_path ]
  );

  const [ settings, setSettings, saveSettings, isSaving, isLoading ] = useSettings( {
    ...props,
    config,
    onSoftReload: handleSoftReload,
  } );

  const [ hideSave, setHideSave ] = useState( false );

  const hasUserEditedRef = useRef( false );
  const autoSaveTimeoutRef = useRef( null );

  const tabs = config.tabs ?? [];
  const lockFormOnSave = config.lock_form_on_save !== false;

  // Resolve the deep-linked tab path from the URL (classic-compatible).
  const activeTabPath = useMemo( () => {
    const params = new URLSearchParams( window.location.search );
    const tab = params.get( 'tab' );
    if ( ! tab ) {
      return null;
    }

    const path = [ tab ];
    const subtab = params.get( 'subtab' );
    if ( subtab ) {
      path.push( subtab );
    }

    // Validate the structure.
    const main = tabs.find( ( t ) => t.name === tab );
    if ( ! main ) {
      return null;
    }
    if ( subtab ) {
      const sub = ( main.tabs ?? [] ).find( ( t ) => t.name === subtab );
      if ( ! sub ) {
        return [ tab ]; // open main tab
      }
    }

    return path;
  }, [ tabs ] );

  // Map field types to their custom Edit components.
  const fields = useMemo( () => mapFields( config ), [ config.fields ] );

  // Initialize the dialog script and notify listeners on mount.
  useEffect( () => {
    document.body.dispatchEvent( new Event( 'easy-dialog-for-wordpress-reinit' ) );
    doAction( 'esfw.settingsPage.mounted', { config, settings } );
  }, [] );

  /**
   * Handle DataForm edits.
   * If a changed field has soft_reload_on_save, save immediately and soft-reload the config.
   *
   * @param {Object} edits Partial settings object from DataForm.
   */
  const onFormChange = ( edits ) => {
    if ( lockFormOnSave && isSaving ) {
      return;
    }

    hasUserEditedRef.current = true;

    const next = { ...settings, ...edits };
    setSettings( next );

    const needsSoftReload = Object.keys( edits ).some( ( id ) => {
      const field =
        fields.find( ( f ) => f.id === id ) ||
        ( config.fields ?? [] ).find( ( f ) => f.id === id );
      return !! field?.soft_reload_on_save;
    } );

    if ( needsSoftReload ) {
      // Persist right away and refresh field descriptors (readOnly, visibility, …).
      saveSettings( {
        values: next,
        softReload: true,
        silent: true,
      } );
    }
  };

  // Auto-save on change, if enabled (skipped while a soft-reload save is in flight via isSaving).
  useEffect( () => {
    if ( config.auto_save !== 'change' || ! hasUserEditedRef.current || isSaving ) {
      return;
    }
    clearTimeout( autoSaveTimeoutRef.current );
    autoSaveTimeoutRef.current = setTimeout( () => {
      saveSettings();
    }, 1000 );
    return () => clearTimeout( autoSaveTimeoutRef.current );
  }, [ settings, config.auto_save, isSaving ] );

  // Auto-save on tab change.
  const handleTabChange = () => {
    if ( config.auto_save === 'tab_change' && hasUserEditedRef.current ) {
      saveSettings();
    }
  };

  const formRef = useRef( null );

  useEffect( () => {
    const el = formRef.current;
    if ( ! el || ! lockFormOnSave ) {
      return;
    }
    if ( isSaving ) {
      el.setAttribute( 'inert', '' );
    } else {
      el.removeAttribute( 'inert' );
    }
  }, [ isSaving, lockFormOnSave ] );

  return (
    <>
      <SettingsTitle title={ config.title } />
      <Notices />
      { isLoading ? (
        <div className="esfw-settings-loading">
          <Spinner />
        </div>
      ) : (
        <div
          ref={ formRef }
          className={
            lockFormOnSave && isSaving
              ? 'esfw-settings-form esfw-settings-form--saving'
              : 'esfw-settings-form'
          }
          aria-busy={ ( lockFormOnSave && isSaving ) || undefined }
        >
          { tabs.length > 0 ? (
            <TabNode
              node={ { tabs } }
              fields={ fields }
              settings={ settings }
              onChange={ onFormChange }
              onActiveLeafChange={ setHideSave }
              activeTabPath={ activeTabPath }
              onTabChange={ handleTabChange }
              configVersion={ configVersion }
            />
          ) : (
            <Card className="esfw-settings-section">
              <CardBody>
                <VStack spacing={ 5 }>
                  { getVisibleFieldIds(
                    fields.map( ( f ) => f.id ),
                    fields,
                    settings
                  ).map( ( fieldId ) => (
                    <DataForm
                      key={ `${ fieldId }-${ configVersion }` }
                      data={ settings }
                      fields={ fields }
                      form={ { fields: [ fieldId ] } }
                      onChange={ onFormChange }
                    />
                  ) ) }
                </VStack>
              </CardBody>
            </Card>
          ) }
        </div>
      ) }
      { ! hideSave && ! isLoading && (
        <SaveButton
          title={ config.save_title }
          onClick={ () => saveSettings() }
          isBusy={ isSaving }
          disabled={ isSaving }
        />
      ) }
      <SnackbarNotices />
    </>
  );
};
