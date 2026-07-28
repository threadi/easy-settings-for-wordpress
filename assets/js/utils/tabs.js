/**
 * Find the path of tab-names from the root down to the tab with the given name.
 *
 * @param {Array}  tabs       The (sub-)tabs to search.
 * @param {string} targetName The tab name to find.
 * @param {Array}  pathSoFar  Internal recursion helper.
 * @return {string[]|null} The path, or null if not found.
 */
export function findTabPath( tabs, targetName, pathSoFar = [] ) {
  for ( const tab of tabs ) {
    const path = [ ...pathSoFar, tab.name ];

    if ( tab.name === targetName ) {
      return path;
    }

    if ( Array.isArray( tab.tabs ) && tab.tabs.length > 0 ) {
      const found = findTabPath( tab.tabs, targetName, path );
      if ( found ) {
        return found;
      }
    }
  }

  return null;
}
