import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';

import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

const TEMPLATE = [
	[
		'jm/column',
		{},
		[
			[
				'jm/heading',
				{
					level: '2',
				},
			],
		],
	],
	[
		'jm/column',
		{},
		[
			[ 'jm/paragraph', {} ],
			[ 'jm/button', {} ],
		],
	],
];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jm-palette--inverse',
	secondary: 'jm-palette--secondary',
	left: 'jm-column-left',
	right: 'jm-column-right',
};

const ALLOWED_BLOCKS = [ 'jm/column' ];

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette, order } = attributes;
	const blockProps = useBlockProps( {
		className: [ 'jm-section', 'jm-cta', VARIANT_CLASSES[ palette ] ]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: [ 'jm-section__container', VARIANT_CLASSES[ order ] ],
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: true,
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
				<div { ...innerBlocksProps } />
			</section>
		</>
	);
}
