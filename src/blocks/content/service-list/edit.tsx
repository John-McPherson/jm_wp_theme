import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp, useEntityRecords } from '@wordpress/core-data';
import { Disabled, TextControl, ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

import bindFields from '../../../utils/bindFields';
import PageListInput from '../../../components/PageListInput';
import PageSelect from '../../../components/PageSelect';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';
import {
	ListSource,
	chosenPageIds,
	isLockedToChosen,
	listParentId,
} from './utils';

type Attributes = {
	headingLevel: string;
	moreText: string;
	source: ListSource;
	parentId: number;
	pageIds: number[];
};

type Props = {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
	context: {
		postId?: number;
		postType?: string;
	};
};

// H1 is reserved for the page title, so card titles start at H2.
const HEADING_LEVELS = [
	{ label: 'H2', value: '2' },
	{ label: 'H3', value: '3' },
	{ label: 'H4', value: '4' },
	{ label: 'H5', value: '5' },
	{ label: 'H6', value: '6' },
] as const;

/**
 * The Services page, as the server resolves it: the page chosen under
 * Settings → Reading, else the published page with the `services` slug.
 * Only administrators can read the setting, so others use the slug.
 *
 * @param enabled Whether the Services page is needed.
 */
const useServicesPageId = ( enabled: boolean ): number => {
	const [ configured ] = useEntityProp(
		'root',
		'site',
		'jmc_services_page_id'
	);
	const configuredId =
		enabled && Number( configured ) > 0 ? Number( configured ) : 0;

	const { records } = useEntityRecords(
		'postType',
		'page',
		{ slug: 'services', status: 'publish', _fields: 'id' },
		{ enabled: enabled && 0 === configuredId }
	);

	return configuredId > 0 ? configuredId : Number( records?.[ 0 ]?.id ) || 0;
};

export default function Edit( { attributes, setAttributes, context }: Props ) {
	const bind = bindFields( attributes, setAttributes );
	const blockProps = useBlockProps();

	const { source, parentId } = attributes;
	const pageIds = chosenPageIds( attributes.pageIds );
	const pageId = 'page' === context.postType ? context.postId : undefined;

	const servicesPageId = useServicesPageId(
		! ( parentId > 0 ) && ! ( Number( pageId ) > 0 )
	);
	const listParent = listParentId( {
		parentId,
		postId: context.postId,
		postType: context.postType,
		servicesPageId,
	} );

	const { records: children, hasResolved } = useEntityRecords(
		'postType',
		'page',
		{ parent: listParent, status: 'publish', per_page: 1, _fields: 'id' },
		{ enabled: listParent > 0 }
	);

	const locked = isLockedToChosen(
		listParent,
		hasResolved ? ( children ?? [] ).length : undefined
	);

	// Without children the list can only show chosen pages. Saving that keeps
	// the choice when children are published later.
	useEffect( () => {
		if ( true === locked && 'chosen' !== source ) {
			setAttributes( { source: 'chosen' } );
		}
	}, [ locked, source, setAttributes ] );

	const showChosen = 'chosen' === source || true === locked;

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Pages', 'jmc-theme' ) }>
					<PageSelect
						value={ parentId > 0 ? parentId : 0 }
						set={ ( id ) => setAttributes( { parentId: id } ) }
						label={ __( 'Parent page', 'jmc-theme' ) }
						help={ __(
							'List the child pages of this page. Leave empty to use the page being edited.',
							'jmc-theme'
						) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Choose pages', 'jmc-theme' ) }
						help={
							true === locked
								? __(
										'The parent page has no published child pages, so choose the pages to list.',
										'jmc-theme'
									)
								: __(
										'List the pages chosen below instead of the child pages.',
										'jmc-theme'
									)
						}
						checked={ showChosen }
						disabled={ false !== locked }
						onChange={ ( checked: boolean ) =>
							setAttributes( {
								source: checked ? 'chosen' : 'children',
							} )
						}
					/>
					{ showChosen && (
						<PageListInput
							value={ pageIds }
							set={ ( ids ) => setAttributes( { pageIds: ids } ) }
							label={ __( 'Add a page', 'jmc-theme' ) }
							help={ __(
								'Pages are listed in this order. Each needs an excerpt and a service icon.',
								'jmc-theme'
							) }
						/>
					) }
				</Sidebar.Section>
				<Sidebar.Section title={ __( 'Card Settings', 'jmc-theme' ) }>
					<SelectInput
						{ ...bind.select( 'headingLevel' ) }
						type="heading_level"
						options={ HEADING_LEVELS }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Link cue text', 'jmc-theme' ) }
						help={ __(
							'Shown beside the arrow on every card, for example “View services”. Leave empty for “Learn more”.',
							'jmc-theme'
						) }
						placeholder={ __( 'Learn more', 'jmc-theme' ) }
						value={ attributes.moreText }
						onChange={ bind.text( 'moreText' ).set }
					/>
				</Sidebar.Section>
			</Sidebar>

			{ /* Preview render.php output so cards always match the page data. */ }
			<div { ...blockProps }>
				{ /* Disabled keeps the card links from leaving the editor. */ }
				<Disabled>
					<ServerSideRender
						block="jmc/service-list"
						attributes={ attributes }
						// Gives the preview the edited page as context, as on the site.
						urlQueryArgs={
							Number( pageId ) > 0 ? { post_id: pageId } : {}
						}
					/>
				</Disabled>
			</div>
		</>
	);
}
