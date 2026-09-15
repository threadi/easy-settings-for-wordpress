import {
  __experimentalVStack as VStack,
  TabPanel,
} from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';

import { SectionCard } from './section-card';

/**
 * Renders HTML content and re-inits easy-dialog-for-wordpress handlers
 * so that .easy-dialog-for-wordpress links work after dangerouslySetInnerHTML.
 */
function HtmlContent( { className, html } ) {
  const ref = useRef( null );

  useEffect( () => {
    if ( ! html || ! ref.current ) {
      return;
    }
    // Re-bind click handlers for newly injected dialog links.
    document.body.dispatchEvent(
      new Event( 'easy-dialog-for-wordpress-reinit' )
    );
  }, [ html ] );

  return (
    <div
      ref={ ref }
      className={ className }
      dangerouslySetInnerHTML={ { __html: html } }
    />
  );
}

/**
 * Render a tab node.
 *
 * @param {Object} props The node props (see settings-page usage).
 * @return {JSX.Element} The rendered node.
 */
export function TabNode( {
                           node,
                           fields,
                           settings,
                           onChange,
                           depth = 0,
                           onActiveLeafChange,
                           inheritedHideSave = false,
                           activeTabPath,
                           onTabChange,
                           parentTabName,
                         } ) {
  // a parent tab's hide_save cascades down to all its (sub-)tabs.
  const effectiveHideSave = inheritedHideSave || !! node.hide_save;
  const isLeaf = ! ( Array.isArray( node.tabs ) && node.tabs.length > 0 );
  const isFirstSelect = useRef( true );
  const snapBackRef = useRef( false );
  const [ remountKey, setRemountKey ] = useState( 0 );
  const deepLinkTab = activeTabPath?.[ depth ];
  // Prefer the PHP-configured default, then the first non-link tab.
  const defaultContentTab =
    node.default_tab ??
    ( node.tabs ?? [] ).find( ( t ) => ! t.url )?.name;
  // Only honor a deep-link if it actually exists on THIS node (avoids empty
  // panels when switching main tabs while a stale subtab from another main
  // tab is still in activeTabPath).
  const resolvedInitialTab =
    deepLinkTab &&
    ( node.tabs ?? [] ).some( ( t ) => t.name === deepLinkTab && ! t.url )
      ? deepLinkTab
      : defaultContentTab;
  const [ activeContentTab, setActiveContentTab ] = useState(
    resolvedInitialTab
  );

  useEffect( () => {
    if ( isLeaf ) {
      onActiveLeafChange?.( effectiveHideSave );
    }
  }, [ isLeaf, effectiveHideSave, onActiveLeafChange ] );

  if ( ! isLeaf ) {
    return (
      <VStack spacing={ 4 }>
        { node.description && (
          <HtmlContent
            className="esfw-settings-tab-description"
            html={ node.description }
          />
        ) }
        { node.content && (
          <HtmlContent
            className="esfw-settings-tab-content"
            html={ node.content }
          />
        ) }
        <TabPanel
          key={ remountKey }
          className={ `esfw-settings-tabs esfw-settings-tabs--level-${ depth }` }
          tabs={ node.tabs.map( ( t ) => ( {
            name: t.name,
            title: t.url
              ? <span className="esfw-tab-title">{ t.label || t.name }</span>
              : ( t.label || t.name ),
            className: t.classes,
          } ) ) }
          initialTabName={ activeContentTab }
          onSelect={ ( tabName ) => {
            if ( snapBackRef.current ) {
              snapBackRef.current = false;
              return;
            }

            const wasFirstSelect = isFirstSelect.current;
            isFirstSelect.current = false;

            const selected = node.tabs.find( ( t ) => t.name === tabName );

            // link tab -> navigate instead of switching
            if ( selected?.url ) {
              if ( ! wasFirstSelect ) {
                const target = selected.target || '_self';
                if ( target === '_blank' ) {
                  window.open( selected.url, '_blank' );
                  snapBackRef.current = true;
                  setRemountKey( ( k ) => k + 1 );
                } else {
                  window.location.assign( selected.url );
                }
              }
              return;
            }

            setActiveContentTab( tabName );

            // create deep links.
            {
              const url = new URL( window.location.href );

              if ( 0 === depth ) {
                // Main-Tab: immer tab setzen, subtab entfernen
                url.searchParams.set( 'tab', tabName );
                url.searchParams.delete( 'subtab' );
              } else if ( depth === 1 ) {
                // Subtab: tab (Parent) + subtab setzen
                if ( parentTabName ) {
                  url.searchParams.set( 'tab', parentTabName );
                }
                const defaultSub =
                  node.default_tab ??
                  ( node.tabs ?? [] ).find( ( t ) => ! t.url )?.name ??
                  node.tabs?.[ 0 ]?.name;
                if ( tabName === defaultSub ) {
                  // Default-Subtab → Parameter weglassen (wie Classic)
                  url.searchParams.delete( 'subtab' );
                } else {
                  url.searchParams.set( 'subtab', tabName );
                }
              }
              // depth >= 2: klassisches Schema kennt keine weiteren Ebenen

              window.history.replaceState( {}, '', url );
            }

            if ( ! wasFirstSelect ) {
              onTabChange?.();
            }
          } }
        >
          { ( active ) => {
            const child = node.tabs.find( ( t ) => t.name === active.name );
            // link tabs have no panel.
            if ( ! child || child.url ) {
              return null;
            }
            return (
              <TabNode
                key={ child.name }
                node={ child }
                fields={ fields }
                settings={ settings }
                onChange={ onChange }
                depth={ depth + 1 }
                onActiveLeafChange={ onActiveLeafChange }
                inheritedHideSave={ effectiveHideSave }
                activeTabPath={ activeTabPath }
                onTabChange={ onTabChange }
                parentTabName={ child.name }
              />
            );
          } }
        </TabPanel>
      </VStack>
    );
  }

  return (
    <VStack spacing={ 6 }>
      { node.description && (
        <HtmlContent
          className="esfw-settings-tab-description"
          html={ node.description }
        />
      ) }
      { node.content && (
        <HtmlContent
          className="esfw-settings-tab-content"
          html={ node.content }
        />
      ) }
      { ( node.sections ?? [] ).map( ( section ) => (
        <SectionCard
          key={ section.name }
          section={ section }
          fields={ fields }
          settings={ settings }
          onChange={ onChange }
        />
      ) ) }
    </VStack>
  );
}
