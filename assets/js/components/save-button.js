/**
 * The save button.
 */
import { Button } from '@wordpress/components';

/**
 * Render the save button.
 *
 * @param {Object}   props         Component props.
 * @param {string}   props.title   The button label.
 * @param {Function} props.onClick The click handler.
 * @return {JSX.Element} The button.
 */
export const SaveButton = ( { title, onClick } ) => (
  <div className="esfw-save-button">
    <Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
      { title }
    </Button>
  </div>
);
