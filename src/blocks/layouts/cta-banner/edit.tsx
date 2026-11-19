import { __ } from '@wordpress/i18n';

import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';

import bindFields from '../../../utils/bindFields';

import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

import { TEMPLATE, CONTACT_TEMPLATE } from './template';

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

const CONTACT_ORDER_OPTIONS = [
	{ label: __( 'Contact Details Left', 'jmc-theme' ), value: 'left' },
	{ label: __( 'Contact Details Right', 'jmc-theme' ), value: 'right' },
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette, order, layout } = attributes;
	const isStacked = 'stacked' === layout;
	const template = 'contact' === layout ? CONTACT_TEMPLATE : TEMPLATE;

	// The section no longer re-syncs to its template (that would discard blocks
	// authors add to the columns), so switching between the button and contact
	// variations rebuilds the columns here instead.
	const { replaceInnerBlocks } = useDispatch( blockEditorStore );
	const previousTemplate = useRef( template );

	useEffect( () => {
		if ( previousTemplate.current === template ) {
			return;
		}

		previousTemplate.current = template;
		replaceInnerBlocks(
			clientId,
			createBlocksFromInnerBlocksTemplate( template )
		);
	}, [ template, clientId, replaceInnerBlocks ] );

	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-cta',
			isStacked && 'jmc-cta--stacked',
			'contact' === layout && 'jmc-cta--contact',
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
			template,
			// The two columns stay put; each column unlocks its own content (see jmc/column).
			templateLock: 'insert',
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
							options={
								'contact' === layout
									? CONTACT_ORDER_OPTIONS
									: ORDER_OPTIONS
							}
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
