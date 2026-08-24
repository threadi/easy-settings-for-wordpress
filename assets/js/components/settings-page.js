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
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';

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
 * @param {Object} props.config The settings configuration.
 * @return {JSX.Element} The page.
 */
export const SettingsPage = ( props ) => {
  const [ settings, setSettings, saveSettings, isSaving, isLoading ] = useSettings( props );
  const [ hideSave, setHideSave ] = useState( false );

  const hasUserEditedRef = useRef( false );
  const autoSaveTimeoutRef = useRef( null );

  const config = props.config;
  const tabs = config.tabs ?? [];
  const lockFormOnSave = config.lock_form_on_save !== false;

  // resolve the deep-linked tab path from the URL (classic-compatible).
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

    // validate the structure.
    const main = tabs.find( ( t ) => t.name === tab );
    if ( ! main ) {
      return null;
    }
    if ( subtab ) {
      const sub = ( main.tabs ?? [] ).find( ( t ) => t.name === subtab );
      if ( ! sub ) {
        return [ tab ]; // open main tab.
      }
    }

    return path;
  }, [ tabs ] );

  // map field types to their custom Edit components.
  const fields = useMemo( () => mapFields( config ), [ config.fields ] );

  // initialize the dialog script and notify listeners on mount.
  useEffect( () => {
    document.body.dispatchEvent( new Event( 'easy-dialog-for-wordpress-reinit' ) );
    doAction( 'esfw.settingsPage.mounted', { config, settings } );
  }, [] );

  // change handler that also flags a genuine user edit.
  const onFormChange = ( edits ) => {
    if ( lockFormOnSave && isSaving ) {
      return;
    }
    hasUserEditedRef.current = true;
    setSettings( ( current ) => ( { ...current, ...edits } ) );
  };

  // auto-save on change, if enabled.
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

  // auto-save on tab change.
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
        />
      ) : (
        <Card className="esfw-settings-section">
          <CardBody>
            <VStack spacing={ 5 }>
              { getVisibleFieldIds( fields.map( ( f ) => f.id ), fields, settings ).map( ( fieldId ) => (
                <DataForm
                  key={ fieldId }
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
          onClick={ saveSettings }
          isBusy={ isSaving }
          disabled={ isSaving }
        />
      ) }
      <SnackbarNotices />
    </>
  );
};
