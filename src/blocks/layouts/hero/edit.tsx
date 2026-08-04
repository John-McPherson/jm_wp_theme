import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import './editor.scss';

import MediaInput from '../../../components/MediaInput';
import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

const TEMPLATE = [
	[ 'jmc/paragraph', { variant: 'label' } ],
	[ 'jmc/heading', { level: '1', lockLevel: true } ],
	[ 'jmc/paragraph' ],
	[ 'jmc/button', { buttonType: 'secondary' } ],
];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
};

const ALLOWED_BLOCKS = [ 'jmc/heading', 'jmc/paragraph', 'jmc/button' ];

export default function Edit( { attributes, setAttributes } ) {
	const { imageUrl, className, palette } = attributes;

	const bind = bindFields( attributes, setAttributes );

	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-hero',
			className,
			VARIANT_CLASSES[ palette ],
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
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
				<Sidebar.Section title={ __( 'Palette Settings', 'jm-theme' ) }>
					<SelectInput
						{ ...bind.select( 'palette' ) }
						type="palette"
					/>
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div { ...innerBlocksProps } />
				<div { ...imageProps }>
					<MediaInput { ...bind.media( 'imageId', 'imageUrl' ) } />
				</div>
			</section>
		</>
	);
}
