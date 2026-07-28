/**
 * The settings page title.
 */
import { __experimentalHeading as Heading } from '@wordpress/components';

/**
 * Render the settings title.
 *
 * @param {Object} props       Component props.
 * @param {string} props.title The title to render.
 * @return {JSX.Element} The heading.
 */
export const SettingsTitle = ( { title } ) => (
  <Heading level={ 1 }>{ title }</Heading>
);
