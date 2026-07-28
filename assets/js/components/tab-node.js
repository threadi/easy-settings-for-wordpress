/**
 * Recursive tab renderer: sub-tabs become nested TabPanels, leaf tabs render
 * their sections. Link tabs (with a URL) navigate instead of switching.
 */
import {
  __experimentalVStack as VStack,
  TabPanel,
} from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';

import { SectionCard } from './section-card';

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
} ) {
  // a parent tab's hide_save cascades down to all its (sub-)tabs.
  const effectiveHideSave = inheritedHideSave || !! node.hide_save;
  const isLeaf = ! ( Array.isArray( node.tabs ) && node.tabs.length > 0 );
  const isFirstSelect = useRef( true );
  const snapBackRef = useRef( false );
  const [ remountKey, setRemountKey ] = useState( 0 );
  const deepLinkTab = activeTabPath?.[ depth ];
  const firstContentTab = ( node.tabs ?? [] ).find( ( t ) => ! t.url )?.name;
  const [ activeContentTab, setActiveContentTab ] = useState(
    deepLinkTab ?? firstContentTab
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
          <div
            className="esfw-settings-tab-description"
            dangerouslySetInnerHTML={ { __html: node.description } }
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
            className: t.url ? 'esfw-tab--external' : undefined,
          } ) ) }
          initialTabName={ activeContentTab }
          onSelect={ ( tabName ) => {
            // swallow the mount-select fired after a snap-back.
            if ( snapBackRef.current ) {
              snapBackRef.current = false;
              return;
            }

            const wasFirstSelect = isFirstSelect.current;
            isFirstSelect.current = false;

            const selected = node.tabs.find( ( t ) => t.name === tabName );

            // link tab -> navigate instead of switching (never on mount-select).
            if ( selected?.url ) {
              if ( ! wasFirstSelect ) {
                const target = selected.target || '_self';
                if ( target === '_blank' ) {
                  // open elsewhere and snap back to the last content tab.
                  window.open( selected.url, '_blank' );
                  snapBackRef.current = true;
                  setRemountKey( ( k ) => k + 1 );
                } else {
                  window.location.assign( selected.url );
                }
              }
              return;
            }

            // real content tab: remember it for the snap-back.
            setActiveContentTab( tabName );

            if ( depth === 0 ) {
              const url = new URL( window.location.href );
              const defaultTabName = node.tabs[ 0 ]?.name;
              if ( tabName === defaultTabName ) {
                url.searchParams.delete( 'tab' );
              } else {
                url.searchParams.set( 'tab', tabName );
              }
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
        <div
          className="esfw-settings-tab-description"
          dangerouslySetInnerHTML={ { __html: node.description } }
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
