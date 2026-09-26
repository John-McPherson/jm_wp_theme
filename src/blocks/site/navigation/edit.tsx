import { __ } from '@wordpress/i18n';
import { useEntityRecords } from '@wordpress/core-data';
import { Spinner } from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import { useEffect } from '@wordpress/element';

import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { navigationId } = attributes;

	const { records: records = [], isResolving } = useEntityRecords(
		'postType',
		'wp_navigation',
		{ per_page: 100, status: 'publish' }
	);

	const menus = records ?? [];

	useEffect( () => {
		if ( isResolving || navigationId || 0 === menus.length ) {
			return;
		}

		setAttributes( { navigationId: menus[ 0 ].id } );
	}, [ navigationId, isResolving, , setAttributes ] );

	let navSelect = <Spinner />;

	if ( ! isResolving && menus.length ) {
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

			<ServerSideRender
				block="jmc/navigation"
				attributes={ attributes }
			/>
		</>
	);
}
