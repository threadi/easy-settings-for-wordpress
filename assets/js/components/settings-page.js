/**
 * The main settings page component.
 */
import {
  __experimentalVStack as VStack,
  Card,
  CardBody,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';

import { useSettings } from '../hooks/use-settings';
import { mapFields } from '../fields';
import { findTabPath } from '../utils/tabs';
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
  const [ settings, setSettings, saveSettings ] = useSettings( props );
  const [ hideSave, setHideSave ] = useState( false );

  const hasUserEditedRef = useRef( false );
  const autoSaveTimeoutRef = useRef( null );

  const config = props.config;
  const tabs = config.tabs ?? [];

  // resolve the deep-linked tab path from the URL.
  const activeTabPath = useMemo( () => {
    const params = new URLSearchParams( window.location.search );
    const requestedTab = params.get( 'tab' );
    if ( ! requestedTab ) {
      return null;
    }
    return findTabPath( tabs, requestedTab );
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
    hasUserEditedRef.current = true;
    setSettings( ( current ) => ( { ...current, ...edits } ) );
  };

  // auto-save on change (debounced).
  useEffect( () => {
    if ( config.auto_save !== 'change' || ! hasUserEditedRef.current ) {
      return;
    }
    clearTimeout( autoSaveTimeoutRef.current );
    autoSaveTimeoutRef.current = setTimeout( () => {
      saveSettings();
    }, 1000 );
    return () => clearTimeout( autoSaveTimeoutRef.current );
  }, [ settings, config.auto_save ] );

  // auto-save on tab change.
  const handleTabChange = () => {
    if ( config.auto_save === 'tab_change' && hasUserEditedRef.current ) {
      saveSettings();
    }
  };

  return (
    <>
      <SettingsTitle title={ config.title } />
      <Notices />
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
      { ! hideSave && (
        <SaveButton title={ config.save_title } onClick={ saveSettings } />
      ) }
      <SnackbarNotices />
    </>
  );
};
