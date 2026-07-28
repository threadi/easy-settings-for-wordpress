import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { NoticeList, SnackbarList } from '@wordpress/components';

/**
 * Use normal notices.
 *
 * @returns {JSX.Element|null}
 * @constructor
 */
const Notices = () => {
  const { removeNotice } = useDispatch( noticesStore );
  const notices = useSelect( ( select ) => select( noticesStore ).getNotices() );

  const defaultNotices = notices.filter( ( notice ) => notice.type !== 'snackbar' );

  if ( defaultNotices.length === 0 ) {
    return null;
  }

  return <NoticeList notices={ defaultNotices } onRemove={ removeNotice } />;
};

/**
 * Use modern flyouts.
 *
 * @returns {JSX.Element|null}
 * @constructor
 */
const SnackbarNotices = () => {
  const { removeNotice } = useDispatch( noticesStore );
  const notices = useSelect( ( select ) => select( noticesStore ).getNotices() );

  const snackbarNotices = notices.filter( ( notice ) => notice.type === 'snackbar' );

  if ( snackbarNotices.length === 0 ) {
    return null;
  }

  return (
     <SnackbarList notices={ snackbarNotices } onRemove={ removeNotice } className="esfw-settings-snackbars" />
  );
};

export { Notices, SnackbarNotices };
