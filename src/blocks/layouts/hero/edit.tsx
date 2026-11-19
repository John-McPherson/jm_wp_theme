import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { Disabled, ToggleControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

import MediaInput from '../../../components/MediaInput';
import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

import { TEMPLATE } from './template';

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
};

const ALLOWED_BLOCKS = [ 'jmc/heading', 'jmc/paragraph', 'jmc/button' ];

// Core Breadcrumbs, rendered as on the frontend; inc/breadcrumbs.php adds
// the home icon in place of the "Home" item.
const BREADCRUMB_ATTRIBUTES = {
	className: 'jmc-hero__breadcrumbs',
	showHomeItem: false,
};

export default function Edit( { attributes, setAttributes, context } ) {
	const { imageUrl, className, palette, showBreadcrumbs } = attributes;
	const { postId, postType } = context;

	const bind = bindFields( attributes, setAttributes );

	// Only child pages get a trail; outside a page (templates, patterns)
	// there is no ancestry to show.
	const [ parentId ] = useEntityProp(
		'postType',
		postType ?? 'page',
		'parent',
		postId
	);
	const hasBreadcrumbs =
		false !== showBreadcrumbs &&
		Boolean( postId ) &&
		0 < Number( parentId );

	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-hero',
			hasBreadcrumbs && 'jmc-hero--has-breadcrumbs',
			className,
			VARIANT_CLASSES[ palette ],
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const { children: innerBlocks, ...innerBlocksProps } = useInnerBlocksProps(
		{
			className: 'jmc-hero__text',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
		}
	);

	const imageProps = {
		className: 'jmc-hero__img',
		style: {
			'--background-image': imageUrl ? `url("${ imageUrl }")` : 'none',
		} as React.CSSProperties,
	};

	return (
		<>
			<Sidebar>
				<Sidebar.Section
					title={ __( 'Palette Settings', 'jmc-theme' ) }
				>
					<SelectInput
						{ ...bind.select( 'palette' ) }
						type="palette"
					/>
				</Sidebar.Section>
				<Sidebar.Section title={ __( 'Breadcrumbs', 'jmc-theme' ) }>
					<ToggleControl
						label={ __( 'Show breadcrumbs', 'jmc-theme' ) }
						help={ __(
							'On child pages, the trail to this page replaces the label.',
							'jmc-theme'
						) }
						checked={ false !== showBreadcrumbs }
						onChange={ ( checked: boolean ) =>
							setAttributes( { showBreadcrumbs: checked } )
						}
						__nextHasNoMarginBottom
					/>
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div { ...innerBlocksProps }>
					{ hasBreadcrumbs && (
						// Preview core Breadcrumbs for this page. Disabled keeps
						// its links from leaving the editor.
						<Disabled>
							<ServerSideRender
								key={ parentId }
								block="core/breadcrumbs"
								attributes={ BREADCRUMB_ATTRIBUTES }
								urlQueryArgs={ { post_id: postId } }
							/>
						</Disabled>
					) }
					{ innerBlocks }
				</div>
				<div { ...imageProps }>
					<MediaInput { ...bind.media( 'imageId', 'imageUrl' ) } />
				</div>
			</section>
		</>
	);
}
