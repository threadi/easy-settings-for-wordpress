/**
 * A single settings section rendered as a card.
 */
import {
  __experimentalHeading as Heading,
  __experimentalVStack as VStack,
  Card,
  CardBody,
  CardHeader,
} from '@wordpress/components';
import { DataForm } from '@wordpress/dataviews';
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

import { getVisibleFieldIds } from '../utils/visibility';

/**
 * Persist collapsed state for the current user when the plugin opted in.
 *
 * @param {string}  sectionName Section internal name.
 * @param {boolean} collapsed   Whether the section is now collapsed.
 */
function persistSectionCollapse( sectionName, collapsed ) {
  const cfg = typeof window !== 'undefined' ? window.esfwSettingsConfig : null;

  // bail if anything is missing.
  if ( ! cfg?.persist_section_collapse || ! sectionName || ! cfg.section_collapse_meta_key ) {
    return;
  }

  // get the current map.
  const currentMap = cfg.section_collapse_state || {};

  // submit the request.
  apiFetch( {
    path: '/wp/v2/users/me',
    method: 'POST',
    data: {
      meta: {
        [ cfg.section_collapse_meta_key ]: {
          ...currentMap,
          [ sectionName ]: collapsed,
        },
      },
    },
  } ).catch( () => {} );
}

/**
 * Render one section with its fields.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.section  The section (name, label, fields).
 * @param {Array}    props.fields   All field definitions.
 * @param {Object}   props.settings The current settings values.
 * @param {Function} props.onChange Change handler.
 * @return {JSX.Element} The section card.
 */
export function SectionCard( { section, fields, settings, onChange } ) {
  const collapsible = !! section.collapsible;
  const [ isOpen, setIsOpen ] = useState( ! section.collapsed );

  const onToggle = () => {
    setIsOpen( ( open ) => {
      const nextOpen = ! open;
      persistSectionCollapse( section.name, ! nextOpen );
      return nextOpen;
    } );
  };

  return (
    <Card className={
      'esfw-settings-section' +
      ( collapsible ? ' esfw-settings-section--collapsible' : '' ) +
      ( collapsible && ! isOpen ? ' is-collapsed' : '' )
    }>
      { section.label && (
        <CardHeader>
          { collapsible ? (
            <Button
              className="esfw-settings-section__toggle"
              onClick={ onToggle }
              aria-expanded={ isOpen }
              variant="tertiary"
            >
              <Heading level={ 3 }>{ section.label }</Heading>
              <span className="esfw-settings-section__chevron" aria-hidden="true">
                { isOpen ? '▾' : '▸' }
              </span>
            </Button>
          ) : (
            <Heading level={ 3 }>{ section.label }</Heading>
          ) }
        </CardHeader>
      ) }
      { ( ! collapsible || isOpen ) && (
        <CardBody>
          <VStack spacing={ 5 }>
            { section.content && (
              <div
                className="esfw-settings-section-content"
                dangerouslySetInnerHTML={ { __html: section.content } }
              />
            ) }
            { getVisibleFieldIds( section.fields, fields, settings ).map( ( fieldId ) => (
              <div
                key={ fieldId }
                className={ `esfw-field esfw-field--${ fieldId }` }
              >
                <DataForm
                  data={ settings }
                  fields={ fields }
                  form={ { fields: [ fieldId ] } }
                  onChange={ onChange }
                />
              </div>
            ) ) }
          </VStack>
        </CardBody>
      ) }
    </Card>
  );
}
