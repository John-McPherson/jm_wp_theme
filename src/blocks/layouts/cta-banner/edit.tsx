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
			[
				'jmc/heading',
				{
					level: '2',
				},
			],
		],
	],
	[
		'jmc/column',
		{},
		[
			[ 'jmc/paragraph', {} ],
			// No inner template here: the locked section re-syncs to its template, which
			// would drop any extra buttons. `jmc/buttons` seeds its own first button.
			[ 'jmc/buttons', {} ],
		],
	],
];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
	left: 'jmc-column-left',
	right: 'jmc-column-right',
};

const ALLOWED_BLOCKS = [ 'jmc/column' ];

// `order` places the second column (copy and buttons) on the chosen side.
const ORDER_OPTIONS = [
	{ label: __( 'Buttons Left', 'jmc-theme' ), value: 'left' },
	{ label: __( 'Buttons Right', 'jmc-theme' ), value: 'right' },
];

export default function Edit( { attributes, setAttributes } ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette, order, layout } = attributes;
	const isStacked = 'stacked' === layout;

	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-cta',
			isStacked && 'jmc-cta--stacked',
			VARIANT_CLASSES[ palette ],
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: [
				'jmc-section__container',
				// Column order only applies side by side.
				! isStacked && VARIANT_CLASSES[ order ],
			]
				.filter( Boolean )
				.join( ' ' ),
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
				{ ! isStacked && (
					<Sidebar.Section
						title={ __( 'Order Settings', 'jmc-theme' ) }
					>
						<SelectInput
							{ ...bind.select( 'order' ) }
							type="order"
							options={ ORDER_OPTIONS }
						/>
					</Sidebar.Section>
				) }
			</Sidebar>

			<section { ...blockProps }>
				<div { ...innerBlocksProps } />
			</section>
		</>
	);
}
