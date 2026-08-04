import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import './editor.scss';

import bindFields from '../../../utils/bindFields';

import MediaInput from '../../../components/MediaInput';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

const TEMPLATE = [
	[ 'jm/paragraph', { variant: 'label' } ],
	[ 'jm/heading', { level: '1', lockLevel: true } ],
	[ 'jm/paragraph' ],
	[ 'jm/button', { buttonType: 'secondary' } ],
];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
	left: 'jmc-text-with-image--image-left',
	right: 'jmc-text-with-image--image-right',
};

const ALLOWED_BLOCKS = [ 'jm/heading', 'jm/paragraph', 'jm/button' ];

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { imageUrl, palette, order } = attributes;
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
				<Sidebar.Section title={ __( 'Palette Settings', 'jm-theme' ) }>
					<SelectInput
						{ ...bind.select( 'palette' ) }
						type="palette"
					/>
				</Sidebar.Section>
				<Sidebar.Section title={ __( 'Order Settings', 'jm-theme' ) }>
					<SelectInput { ...bind.select( 'order' ) } type="order" />
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div className="jmc-section__container">
					<div { ...innerBlocksProps } />
					<div className="jmc-section__column">
						<div className="jmc-image jmc-text-with-image__image">
							{ imageUrl && <img src={ imageUrl } alt="#" /> }
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
