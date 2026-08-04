import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';

import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

const TEMPLATE = [
	[
		'jmc/column',
		{},
		[
			[ 'jmc/paragraph', { variant: 'label' } ],
			[ 'jmc/heading', { level: '2' } ],
		],
	],
	[
		'jmc/column',
		{},
		[
			[ 'jmc/service-card' ],
			[ 'jmc/service-card' ],
			[ 'jmc/service-card' ],
			[ 'jmc/service-card' ],
		],
	],
];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
};

const ALLOWED_BLOCKS = [ 'jmc/column' ];

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette, order } = attributes;
	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-service-grid',
			VARIANT_CLASSES[ palette ],
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: [ 'jmc-section__container', VARIANT_CLASSES[ order ] ],
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
				<Sidebar.Section
					title={ __( 'Palette Settings', 'jmc-theme' ) }
				>
					<SelectInput
						{ ...bind.select( 'palette' ) }
						type="palette"
					/>
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div { ...innerBlocksProps } />
			</section>
		</>
	);
}
