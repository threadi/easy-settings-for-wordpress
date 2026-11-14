/**
 * Search in the settings for the classic view.
 *
 * Filters the settings of the actual tab, lists matches in other tabs and shows the number of matches on the tabs.
 * The configuration is provided by PHP in window.esfwSearchConfig (see Search::get_view_config()).
 */
import domReady from '@wordpress/dom-ready';

import { escapeHtml, getDependText, getResultUrl, highlight, search } from './utils/search';

domReady( () => {
  const config = window.esfwSearchConfig;
  const input = document.getElementById( 'esfw-search-input' );
  const wrap = input ? input.closest( '.easy-settings-for-wordpress' ) : null;

  // bail if anything is missing.
  if ( ! config || ! input || ! wrap ) {
    return;
  }

  const labels = config.labels ?? {};
  const index = config.index ?? [];
  const content = wrap.querySelector( '.tab-content' );
  const results = wrap.querySelector( '.esfw-search-results' );
  const rowSelector = 'table.form-table > tbody > tr, table.form-table > tr';
  const visibleRowSelector = 'table.form-table > tbody > tr:not(.esfw-search-hidden):not(.hide), table.form-table > tr:not(.esfw-search-hidden):not(.hide)';
  const rows = content ? Array.from( content.querySelectorAll( rowSelector ) ) : [];
  let query = '';
  let timeout = null;

  /**
   * Return whether the entry is shown in the actual tab.
   *
   * @param {Object} entry The entry.
   * @return {boolean} True if it is in the actual tab.
   */
  const isInActualTab = ( entry ) =>
    entry.page === config.page && entry.tab === config.tab && entry.subtab === config.subtab;

  /**
   * Return the row of a setting in the actual tab.
   *
   * @param {string} rowClass The CSS class of the row.
   * @return {HTMLElement|undefined} The row.
   */
  const getRow = ( rowClass ) => rows.find( ( row ) => rowClass && row.classList.contains( rowClass ) );

  /**
   * Highlight the given row for a short time and scroll to it.
   *
   * @param {HTMLElement} row The row.
   */
  const highlightRow = ( row ) => {
    row.classList.remove( 'esfw-search-hidden' );
    const section = row.closest( '.esfw-search-section' );
    if ( section ) {
      section.classList.remove( 'esfw-search-hidden' );
    }
    row.scrollIntoView( { block: 'center' } );
    row.classList.add( 'esfw-search-highlight' );
    setTimeout( () => row.classList.remove( 'esfw-search-highlight' ), 2500 );
  };

  /**
   * Mark the search in the title of the given row (only plain text titles).
   *
   * @param {HTMLElement} row The row.
   */
  const markTitle = ( row ) => {
    const th = row.querySelector( 'th' );
    const label = th ? th.querySelector( 'label' ) ?? th : null;
    if ( ! label || label.children.length > 0 ) {
      return;
    }
    if ( undefined === label.dataset.esfwTitle ) {
      label.dataset.esfwTitle = label.textContent;
    }
    label.innerHTML = highlight( label.dataset.esfwTitle, query );
  };

  /**
   * Remove the marks from all titles.
   */
  const unmarkTitles = () => {
    wrap.querySelectorAll( '[data-esfw-title]' ).forEach( ( label ) => {
      label.textContent = label.dataset.esfwTitle;
      delete label.dataset.esfwTitle;
    } );
  };

  /**
   * Return the links of the tab navigation with their tab and sub-tab.
   *
   * @return {Array} List of objects with element, tab and subtab.
   */
  const getNavLinks = () =>
    Array.from( wrap.querySelectorAll( 'nav a[href]' ) )
      .map( ( element ) => {
        const url = new URL( element.href, window.location.href );
        return {
          element,
          url,
          page: url.searchParams.get( 'page' ) ?? '',
          tab: url.searchParams.get( 'tab' ) ?? '',
          subtab: url.searchParams.get( 'subtab' ) ?? '',
        };
      } )
      .filter( ( link ) => '' !== link.tab );

  /**
   * Show the number of matches on the links of the tab navigation and keep the search in their URLs.
   *
   * @param {Object[]} matches The matching entries.
   */
  const updateNavigation = ( matches ) => {
    getNavLinks().forEach( ( link ) => {
      // remove the former number.
      link.element.querySelectorAll( '.esfw-search-count' ).forEach( ( el ) => el.remove() );

      // keep the search in the URL of the tab.
      if ( query ) {
        link.url.searchParams.set( 'esfw_search', query );
      } else {
        link.url.searchParams.delete( 'esfw_search' );
      }
      link.element.href = link.url.toString();

      // count the matches of this tab (and sub-tab).
      const count = matches.filter(
        ( entry ) =>
          'setting' === entry.type &&
          entry.page === link.page &&
          entry.tab === link.tab &&
          ( '' === link.subtab || entry.subtab === link.subtab )
      ).length;
      if ( ! query || 0 === count ) {
        return;
      }
      const badge = document.createElement( 'span' );
      badge.className = 'esfw-search-count';
      badge.textContent = String( count );
      link.element.appendChild( badge );
    } );
  };

  /**
   * Show the list of matches, which are not visible in the actual tab.
   *
   * @param {Object[]} matches      All matching entries.
   * @param {Object[]} hiddenHere   Matches in this tab, which are hidden by a dependency.
   * @param {number}   visibleCount Number of visible matches in this tab.
   */
  const updateResults = ( matches, hiddenHere, visibleCount ) => {
    if ( ! results ) {
      return;
    }

    // bail without search.
    if ( ! query ) {
      results.hidden = true;
      results.innerHTML = '';
      return;
    }

    let html = '';

    // no visible match in this tab.
    if ( 0 === visibleCount && 0 === hiddenHere.length ) {
      html += `<p class="esfw-search-empty">${ escapeHtml( labels.no_results_tab ) }</p>`;
    }

    // matches in this tab, which are hidden by another setting.
    if ( hiddenHere.length ) {
      html += '<ul class="esfw-search-list">';
      hiddenHere.forEach( ( entry ) => {
        html += `<li><strong>${ highlight( entry.title, query ) }</strong>`;
        ( entry.depends ?? [] ).forEach( ( depend ) => {
          html += `<span class="esfw-search-depend">${ escapeHtml( getDependText( depend, labels ) ) } <button type="button" class="button-link esfw-search-goto" data-row="${ escapeHtml( depend.row ) }">${ escapeHtml( labels.go_to.replace( '%1$s', depend.title ) ) }</button></span>`;
        } );
        html += '</li>';
      } );
      html += '</ul>';
    }

    // matches in other tabs: settings, and tabs without matching settings.
    const settingTabs = new Set(
      matches.filter( ( entry ) => 'setting' === entry.type ).map( ( entry ) => `${ entry.page }/${ entry.tab }/${ entry.subtab }` )
    );
    const other = matches.filter( ( entry ) => {
      if ( 'setting' === entry.type ) {
        return ! isInActualTab( entry );
      }
      const key = `${ entry.page }/${ entry.tab }/${ entry.subtab }`;
      return ! isInActualTab( entry ) && ! settingTabs.has( key );
    } );
    if ( other.length ) {
      html += `<h2>${ escapeHtml( labels.other_results.replace( '%1$d', String( other.length ) ) ) }</h2><ul class="esfw-search-list">`;
      other.slice( 0, 50 ).forEach( ( entry ) => {
        const path = ( entry.path ?? [] ).join( ' › ' );
        html += `<li>${ path ? `<span class="esfw-search-path">${ escapeHtml( path ) }</span>` : '' }<a href="${ escapeHtml( getResultUrl( entry, query ) ) }"${ entry.external ? ' target="_blank" rel="noopener noreferrer"' : '' }>${ highlight( entry.title, query ) }</a>`;
        if ( 'setting' === entry.type && entry.description ) {
          html += `<span class="esfw-search-description">${ highlight( entry.description.length > 160 ? entry.description.substring( 0, 157 ) + '…' : entry.description, query ) }</span>`;
        }
        html += '</li>';
      } );
      html += '</ul>';
    }

    results.innerHTML = html;
    results.hidden = '' === html;
  };

  /**
   * Run the search for the actual input.
   */
  const run = () => {
    query = input.value.trim();
    wrap.classList.toggle( 'esfw-search-is-active', '' !== query );
    unmarkTitles();

    // without search: show everything again.
    if ( ! query ) {
      wrap.querySelectorAll( '.esfw-search-hidden' ).forEach( ( el ) => el.classList.remove( 'esfw-search-hidden' ) );
      updateNavigation( [] );
      updateResults( [], [], 0 );
      return;
    }

    // get the matches.
    const matches = search( index, query );

    // get the visible matches and the matches hidden by a dependency in this tab.
    const visibleRows = new Set();
    const hiddenHere = [];
    matches
      .filter( ( entry ) => 'setting' === entry.type && isInActualTab( entry ) )
      .forEach( ( entry ) => {
        const row = getRow( entry.row );
        if ( ! row ) {
          return;
        }
        if ( row.classList.contains( 'hide' ) ) {
          hiddenHere.push( entry );
          return;
        }
        visibleRows.add( row );
      } );

    // filter the rows and mark the titles.
    rows.forEach( ( row ) => row.classList.toggle( 'esfw-search-hidden', ! visibleRows.has( row ) ) );
    visibleRows.forEach( markTitle );

    // hide sections and save buttons without visible rows.
    if ( content ) {
      content.querySelectorAll( '.esfw-search-section' ).forEach( ( section ) => {
        section.classList.toggle( 'esfw-search-hidden', ! section.querySelector( visibleRowSelector ) );
      } );
      content.querySelectorAll( 'form' ).forEach( ( form ) => {
        form.querySelectorAll( 'p.submit' ).forEach( ( button ) => button.classList.toggle( 'esfw-search-hidden', ! form.querySelector( visibleRowSelector ) ) );
      } );
    }

    updateNavigation( matches );
    updateResults( matches, hiddenHere, visibleRows.size );
  };

  // run the search during input.
  input.addEventListener( 'input', () => {
    clearTimeout( timeout );
    timeout = setTimeout( run, 120 );
  } );

  // clear it via escape.
  input.addEventListener( 'keydown', ( event ) => {
    if ( 'Escape' === event.key && '' !== input.value ) {
      input.value = '';
      run();
    }
  } );

  // the search field could also be cleared via its own button.
  input.addEventListener( 'search', run );

  // go to a setting, on which a match depends.
  if ( results ) {
    results.addEventListener( 'click', ( event ) => {
      const button = event.target.closest( '.esfw-search-goto' );
      if ( ! button ) {
        return;
      }
      const row = getRow( button.dataset.row );
      if ( row ) {
        highlightRow( row );
      }
    } );
  }

  // use the search and the highlighted setting from the URL.
  const params = new URLSearchParams( window.location.search );
  const initial = params.get( 'esfw_search' );
  if ( initial ) {
    input.value = initial;
    run();
  }
  const target = params.get( 'esfw_highlight' );
  if ( target ) {
    const entry = index.find( ( item ) => 'setting' === item.type && item.name === target && isInActualTab( item ) );
    const row = entry ? getRow( entry.row ) : undefined;
    if ( row && ! row.classList.contains( 'hide' ) ) {
      highlightRow( row );
    } else if ( entry && entry.depends?.length ) {
      const dependRow = getRow( entry.depends[ 0 ].row );
      if ( dependRow ) {
        highlightRow( dependRow );
      }
    }
  }
} );
