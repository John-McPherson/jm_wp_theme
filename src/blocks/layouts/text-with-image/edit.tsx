import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { useEntityRecord } from '@wordpress/core-data';

import './editor.scss';

import bindFields from '../../../utils/bindFields';

import MediaInput from '../../../components/MediaInput';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

import { TEMPLATE } from './template';

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
	left: 'jmc-text-with-image--image-left',
	right: 'jmc-text-with-image--image-right',
};

const ALLOWED_BLOCKS = [
	'jmc/heading',
	'jmc/paragraph',
	'jmc/list',
	'jmc/button',
];

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { imageId, imageUrl, palette, order } = attributes;

	// Match the frontend, where wp_get_attachment_image() uses the attachment's
	// alternative text; stay empty until it loads or when none is set.
	const { record } = useEntityRecord( 'root', 'media', imageId, {
		enabled: imageId > 0,
	} );
	const imageAlt =
		( record as { alt_text?: string } | undefined )?.alt_text?.trim() ?? '';

	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-text-with-image',
			VARIANT_CLASSES[ palette ],
			VARIANT_CLASSES[ order ],
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'jmc-section__column',
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
		}
	);

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
				<Sidebar.Section title={ __( 'Order Settings', 'jmc-theme' ) }>
					<SelectInput { ...bind.select( 'order' ) } type="order" />
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div className="jmc-section__container">
					<div { ...innerBlocksProps } />
					<div className="jmc-section__column">
						<div className="jmc-image jmc-text-with-image__image">
							{ imageUrl && (
								<img src={ imageUrl } alt={ imageAlt } />
							) }
							<MediaInput
								{ ...bind.media( 'imageId', 'imageUrl' ) }
							/>
						</div>
					</div>
				</div>
			</section>
		</>
	);
}
