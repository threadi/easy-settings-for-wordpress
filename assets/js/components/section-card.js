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

import { getVisibleFieldIds } from '../utils/visibility';

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
  return (
    <Card className="esfw-settings-section">
      { section.label && (
        <CardHeader>
          <Heading level={ 3 }>{ section.label }</Heading>
        </CardHeader>
      ) }
      <CardBody>
        <VStack spacing={ 5 }>
          { section.content && (
            <div
              className="esfw-settings-section-content"
              dangerouslySetInnerHTML={ { __html: section.content } }
            />
          ) }
          { getVisibleFieldIds( section.fields, fields, settings ).map( ( fieldId ) => (
            <DataForm
              key={ fieldId }
              data={ settings }
              fields={ fields }
              form={ { fields: [ fieldId ] } }
              onChange={ onChange }
            />
          ) ) }
        </VStack>
      </CardBody>
    </Card>
  );
}
