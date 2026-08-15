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
export const SaveButton = ( { title, onClick, isBusy = false, disabled = false } ) => (
  <div className="esfw-save-button">
    <Button
      variant="primary"
      onClick={ onClick }
      isBusy={ isBusy }
      disabled={ disabled || isBusy }
      __next40pxDefaultSize
    >
      { title }
    </Button>
  </div>
);
