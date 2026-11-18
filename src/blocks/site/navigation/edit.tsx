import { __ } from '@wordpress/i18n';
import { useEntityRecords } from '@wordpress/core-data';
import { Disabled, Spinner } from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import { useEffect, useMemo } from '@wordpress/element';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

import './editor.scss';

// The call to action renders as the last menu item, so it sits in the mobile
// dropdown and at the end of the desktop menu.
const ALLOWED_BLOCKS = [ 'jmc/header-cta' ];

const TEMPLATE = [ [ 'jmc/header-cta', { lock: { remove: true } } ] ];

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	// One wrapper holds the menu preview and the call to action, so the block
	// has a single element for selection, focus and toolbar positioning.
	const blockProps = useBlockProps( {
		className: 'jmc-navigation-editor',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'jmc-navigation__inner-blocks' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			renderAppender: false,
		}
	);

	const { navigationId } = attributes;

	const { records: records = [], isResolving } = useEntityRecords(
		'postType',
		'wp_navigation',
		{ per_page: 100, status: 'publish' }
	);

	const menus = useMemo( () => records ?? [], [ records ] );

	useEffect( () => {
		if ( isResolving || navigationId || 0 === menus.length ) {
			return;
		}

		setAttributes( { navigationId: menus[ 0 ].id } );
	}, [ navigationId, isResolving, menus, setAttributes ] );

	let navSelect = <Spinner />;

	if ( ! isResolving ) {
		const navigationOptions = menus.map( ( menu ) => {
			return { label: menu.title.rendered, value: menu.id };
		} );

		if ( navigationOptions.length === 0 ) {
			navigationOptions.push( {
				label: __( 'No navigation menus found', 'jmc-theme' ),
				value: '',
			} );
		}

		navSelect = (
			<SelectInput
				{ ...bind.select( 'navigationId' ) }
				type="navigation"
				options={ navigationOptions }
			/>
		);
	}

	return (
		<>
			<Sidebar>
				<Sidebar.Section
					title={ __( 'Navigation Settings', 'jmc-theme' ) }
				>
					{ navSelect }
				</Sidebar.Section>
			</Sidebar>

			<div { ...blockProps }>
				{ /* Disabled keeps the menu links from leaving the editor. */ }
				<Disabled>
					<ServerSideRender
						block="jmc/navigation"
						attributes={ attributes }
					/>
				</Disabled>

				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
