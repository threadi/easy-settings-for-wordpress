/**
 * The results of the search in the settings for the DataView.
 *
 * Shows the matching settings of all tabs with their fields, grouped by tab and section, so they could be changed
 * directly. Settings, which are hidden by another setting, are shown with a hint and the setting they depend on.
 */
import {
  __experimentalHeading as Heading,
  __experimentalText as Text,
  __experimentalVStack as VStack,
  Card,
  CardBody,
  CardHeader,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews';
import { useEffect, useMemo } from '@wordpress/element';

import { getDependText, getResultUrl, highlight, search } from '../utils/search';
import { getVisibleFieldIds } from '../utils/visibility';

/**
 * Render the results of the search.
 *
 * @param {Object}   props           Component props.
 * @param {Object}   props.config    The configuration of the search.
 * @param {string}   props.query     The search.
 * @param {Array}    props.fields    All field definitions.
 * @param {Object}   props.settings  The current settings values.
 * @param {Function} props.onChange  Change handler.
 * @param {string}   props.highlight   Name of the setting to highlight.
 * @param {Function} props.onHasFields Called with true, if the results contain fields, which could be saved.
 * @return {JSX.Element} The results.
 */
export function SearchResults( { config, query, fields, settings, onChange, highlight: highlightName, onHasFields } ) {
  const labels = config.labels ?? {};
  const fieldIds = useMemo( () => new Set( fields.map( ( f ) => f.id ) ), [ fields ] );

  // get the matches grouped by tab and section.
  const groups = useMemo( () => {
    const list = [];
    const byKey = {};
    const settingTabs = new Set();
    const matches = search( config.index, query );
    matches
      .filter( ( entry ) => 'setting' === entry.type )
      .forEach( ( entry ) => settingTabs.add( `${ entry.page }/${ entry.tab }/${ entry.subtab }` ) );

    matches.forEach( ( entry ) => {
      // show tabs only, if none of their settings matches.
      if ( 'setting' !== entry.type && settingTabs.has( `${ entry.page }/${ entry.tab }/${ entry.subtab }` ) ) {
        return;
      }
      const path = 'setting' === entry.type ? entry.path ?? [] : [ ...( entry.path ?? [] ), entry.title ];
      const key = path.join( ' › ' );
      if ( ! byKey[ key ] ) {
        byKey[ key ] = { key, entries: [] };
        list.push( byKey[ key ] );
      }
      byKey[ key ].entries.push( entry );
    } );
    return list;
  }, [ config.index, query ] );

  // tell the page, whether the results contain fields, which could be saved.
  const hasFields = groups.some( ( group ) =>
    group.entries.some( ( entry ) => 'setting' === entry.type && fieldIds.has( entry.name ) )
  );
  useEffect( () => {
    onHasFields?.( hasFields );
  }, [ hasFields, onHasFields ] );

  // scroll to the setting to highlight.
  useEffect( () => {
    if ( ! highlightName ) {
      return;
    }
    const element = document.querySelector( `.esfw-field--${ CSS.escape( highlightName ) }` );
    if ( element ) {
      element.scrollIntoView( { block: 'center' } );
      element.classList.add( 'esfw-search-highlight' );
      setTimeout( () => element.classList.remove( 'esfw-search-highlight' ), 2500 );
    }
  }, [ highlightName ] );

  // no matches.
  if ( ! groups.length ) {
    return (
      <Card className="esfw-settings-section esfw-search-empty">
        <CardBody>
          <Text>{ labels.no_results }</Text>
        </CardBody>
      </Card>
    );
  }

  // each field is shown only once, also if several matches depend on it.
  const rendered = new Set();

  /**
   * Render a single field (only once on the page).
   *
   * @param {string} fieldId The field id.
   * @return {JSX.Element|null} The field.
   */
  const renderField = ( fieldId ) => {
    if ( rendered.has( fieldId ) ) {
      return null;
    }
    rendered.add( fieldId );
    return (
      <div key={ fieldId } className={ `esfw-field esfw-field--${ fieldId }` }>
        <DataForm data={ settings } fields={ fields } form={ { fields: [ fieldId ] } } onChange={ onChange } />
      </div>
    );
  };

  return (
    <VStack spacing={ 4 } className="esfw-search-results-dataview">
      { groups.map( ( group ) => (
        <Card key={ group.key } className="esfw-settings-section">
          <CardHeader>
            <Heading level={ 2 }>
              <span dangerouslySetInnerHTML={ { __html: highlight( group.key, query ) } } />
            </Heading>
          </CardHeader>
          <CardBody>
            <VStack spacing={ 5 }>
              { group.entries.map( ( entry ) => {
                // link to tabs and settings without field in this view.
                if ( 'setting' !== entry.type || ! fieldIds.has( entry.name ) ) {
                  return (
                    <div key={ `${ entry.type }-${ entry.name }` } className="esfw-search-link">
                      <a href={ getResultUrl( entry, query ) } target={ entry.external ? '_blank' : undefined } rel={ entry.external ? 'noopener noreferrer' : undefined } dangerouslySetInnerHTML={ { __html: highlight( 'tab' === entry.type ? labels.open_tab : entry.title, query ) } } />
                    </div>
                  );
                }

                // show the field, if it is visible.
                if ( getVisibleFieldIds( [ entry.name ], fields, settings ).length ) {
                  return renderField( entry.name );
                }

                // show the hint and the settings, on which it depends.
                return (
                  <div key={ entry.name } className="esfw-search-hidden-field">
                    <Text weight={ 600 }>
                      <span dangerouslySetInnerHTML={ { __html: highlight( entry.title, query ) } } />
                    </Text>
                    { ( entry.depends ?? [] ).map( ( depend ) => (
                      <div key={ depend.name }>
                        <Text className="esfw-search-depend">{ getDependText( depend, labels ) }</Text>
                        { getVisibleFieldIds( [ depend.name ], fields, settings ).length > 0 && renderField( depend.name ) }
                      </div>
                    ) ) }
                  </div>
                );
              } ) }
            </VStack>
          </CardBody>
        </Card>
      ) ) }
    </VStack>
  );
}
