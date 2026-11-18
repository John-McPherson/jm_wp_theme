import { useBlockProps } from '@wordpress/block-editor';
import { useEntityRecords } from '@wordpress/core-data';
import { Disabled, Placeholder, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ServerSideRender } from '@wordpress/server-side-render';

import bindFields from '../../../utils/bindFields';
import SelectInput from '../../../components/SelectInput';
import Sidebar from '../../../components/Sidebar';

type FooterNavigationAttributes = {
	navigationId: number;
	className?: string;
};

type EditProps = {
	attributes: FooterNavigationAttributes;
	setAttributes: (
		attributes: Partial< FooterNavigationAttributes >
	) => void;
};

type NavigationRecord = {
	id: number;
	title: { rendered: string };
};

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps();

	const { navigationId } = attributes;

	const { records, isResolving } = useEntityRecords(
		'postType',
		'wp_navigation',
		{ per_page: 100, status: 'publish' }
	);

	const bindNavigation = bindFields(
		attributes,
		( next: { navigationId: string } ) =>
			setAttributes( { navigationId: Number( next.navigationId ) || 0 } )
	);

	const options = [
		{ label: __( 'Select a menu', 'jmc-theme' ), value: '0' },
		...( ( records ?? [] ) as NavigationRecord[] ).map( ( menu ) => ( {
			label: menu.title.rendered || __( '(no title)', 'jmc-theme' ),
			value: String( menu.id ),
		} ) ),
	];

	return (
		<>
			<Sidebar>
				<Sidebar.Section
					title={ __( 'Navigation Settings', 'jmc-theme' ) }
				>
					{ isResolving ? (
						<Spinner />
					) : (
						<SelectInput
							{ ...bindNavigation.select( 'navigationId' ) }
							type="navigation"
							options={ options }
						/>
					) }
					<p>
						{ __(
							'The footer navigation is hidden on the site until a menu with at least one link is selected.',
							'jmc-theme'
						) }
					</p>
				</Sidebar.Section>
			</Sidebar>

			<div { ...blockProps }>
				{ navigationId ? (
					// Disabled keeps the menu links from leaving the editor.
					<Disabled>
						<ServerSideRender
							block="jmc/footer-navigation"
							attributes={ attributes }
						/>
					</Disabled>
				) : (
					<Placeholder
						icon="menu"
						label={ __( 'Footer Navigation', 'jmc-theme' ) }
						instructions={ __(
							'Choose a navigation menu in the block settings.',
							'jmc-theme'
						) }
					/>
				) }
			</div>
		</>
	);
}
