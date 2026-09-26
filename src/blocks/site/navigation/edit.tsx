import { __ } from '@wordpress/i18n';
import { useEntityRecords } from '@wordpress/core-data';
import { Spinner } from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';

import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { records: menus = [], isResolving } = useEntityRecords(
		'postType',
		'wp_navigation',
		{ per_page: 100, status: 'publish' }
	);

	let navSelect = <Spinner />;

	if ( ! isResolving && menus ) {
		const navigationOptions = menus.map( ( menu ) => {
			return { label: menu.title.rendered, value: menu.id };
		} );
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
