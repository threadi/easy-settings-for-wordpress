/**
 * Search in the index of the settings.
 *
 * Keep in sync with Search::get_words() and Search::get_score() in PHP.
 */

/**
 * Return the normalized words of the given text: lowercase, without accents ("ä" becomes "a", "ß" becomes "ss"),
 * split at all characters except letters, numbers and "_".
 *
 * @param {string} text The text.
 * @return {string[]} The words.
 */
export function getWords( text ) {
  return String( text ?? '' )
    .normalize( 'NFD' )
    .replace( /[̀-ͯ]/g, '' )
    .replace( /ß/g, 'ss' )
    .toLowerCase()
    .split( /[^\p{L}\p{N}_]+/u )
    .filter( Boolean );
}

/**
 * Return whether the given word of a search matches one of the given words of an entry.
 *
 * @param {string}   word  The word of the search.
 * @param {string[]} words The words of the entry.
 * @return {boolean} True if it matches.
 */
function isWordMatching( word, words ) {
  return words.some(
    ( entryWord ) => entryWord.startsWith( word ) || ( word.length >= 4 && entryWord.includes( word ) )
  );
}

/**
 * Return the score of an entry for the given words of a search (0 if it does not match).
 *
 * @param {Object}   entry The entry of the index.
 * @param {string[]} words The normalized words of the search.
 * @return {number} The score.
 */
export function getScore( entry, words ) {
  const parts = [
    [ getWords( entry.title ), 3 ],
    [ getWords( ( entry.path ?? [] ).join( ' ' ) ), 2 ],
    [ getWords( [ entry.description, entry.options, entry.keywords, entry.name ].join( ' ' ) ), 1 ],
  ];

  let score = 0;
  for ( const word of words ) {
    let best = 0;
    for ( const [ partWords, weight ] of parts ) {
      if ( weight > best && isWordMatching( word, partWords ) ) {
        best = weight;
      }
    }
    if ( 0 === best ) {
      return 0;
    }
    score += best;
  }
  return score;
}

/**
 * Return the matching entries of the index sorted by relevance (same score: order of the settings).
 *
 * @param {Object[]} index  The index.
 * @param {string}   search The search.
 * @return {Object[]} The matching entries.
 */
export function search( index, search ) {
  const words = getWords( search );
  if ( ! words.length ) {
    return [];
  }

  return ( index ?? [] )
    .map( ( entry, position ) => ( { entry, position, score: getScore( entry, words ) } ) )
    .filter( ( result ) => result.score > 0 )
    .sort( ( a, b ) => b.score - a.score || a.position - b.position )
    .map( ( result ) => result.entry );
}

/**
 * Escape the given text for HTML.
 *
 * @param {string} text The text.
 * @return {string} The escaped text.
 */
export function escapeHtml( text ) {
  return String( text ?? '' )
    .replace( /&/g, '&amp;' )
    .replace( /</g, '&lt;' )
    .replace( />/g, '&gt;' )
    .replace( /"/g, '&quot;' );
}

/**
 * Return the given text as HTML with the words of the search marked.
 *
 * Accents are ignored like in the search (e.g. "lösch" marks "Lösch" and "losch").
 *
 * @param {string} text   The text.
 * @param {string} search The search.
 * @return {string} The HTML.
 */
export function highlight( text, search ) {
  const value = String( text ?? '' );
  const words = getWords( search );
  if ( ! words.length || ! value ) {
    return escapeHtml( value );
  }

  // normalize each character on its own, so the positions stay the same as in the original text.
  const normalized = Array.from( value )
    .map( ( char ) => {
      const plain = char.normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).toLowerCase();
      return 1 === plain.length ? plain : char.toLowerCase();
    } )
    .join( '' );

  // mark all ranges of the words.
  const marked = new Array( value.length ).fill( false );
  for ( const word of words ) {
    let position = normalized.indexOf( word );
    while ( -1 !== position ) {
      for ( let i = position; i < position + word.length; i++ ) {
        marked[ i ] = true;
      }
      position = normalized.indexOf( word, position + word.length );
    }
  }

  // build the HTML.
  let html = '';
  let open = false;
  Array.from( value ).forEach( ( char, i ) => {
    if ( marked[ i ] && ! open ) {
      html += '<mark>';
      open = true;
    } else if ( ! marked[ i ] && open ) {
      html += '</mark>';
      open = false;
    }
    html += escapeHtml( char );
  } );
  if ( open ) {
    html += '</mark>';
  }
  return html;
}

/**
 * Return the text for the dependency of an entry.
 *
 * @param {Object} depend The dependency (name, title, enabled).
 * @param {Object} labels The labels of the search.
 * @return {string} The text.
 */
export function getDependText( depend, labels ) {
  return ( depend.enabled ? labels.depends_enabled : labels.depends ).replace( '%1$s', depend.title );
}

/**
 * Return the URL to show an entry for the given search (incl. the setting to highlight).
 *
 * @param {Object} entry  The entry.
 * @param {string} search The search.
 * @return {string} The URL.
 */
export function getResultUrl( entry, search ) {
  if ( 'setting' !== entry.type ) {
    return entry.url;
  }
  const url = new URL( entry.url, window.location.href );
  url.searchParams.set( 'esfw_search', search );
  url.searchParams.set( 'esfw_highlight', entry.name );
  return url.toString();
}
