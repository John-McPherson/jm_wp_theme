import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { DropdownMenu } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { button, envelope, mobile, plus } from '@wordpress/icons';

import WhatsAppIcon from '../../../components/WhatsAppIcon';

const ALLOWED_BLOCKS = [ 'jmc/button' ];

const TEMPLATE: [ string ][] = [ [ 'jmc/button' ] ];

// `jmc/button` is hidden from the inserter, so buttons are added from here instead.
const NEW_BUTTONS = [
	{
		linkType: 'link',
		label: __( 'Button', 'jmc-theme' ),
		icon: button,
		text: '',
	},
	{
		linkType: 'phone',
		label: __( 'Phone button', 'jmc-theme' ),
		icon: mobile,
		text: __( 'Call us', 'jmc-theme' ),
	},
	{
		linkType: 'email',
		label: __( 'Email button', 'jmc-theme' ),
		icon: envelope,
		text: __( 'Email us', 'jmc-theme' ),
	},
	{
		linkType: 'whatsapp',
		label: __( 'WhatsApp button', 'jmc-theme' ),
		icon: WhatsAppIcon,
		text: __( 'Message us on WhatsApp', 'jmc-theme' ),
	},
] as const;

// `@wordpress/block-editor` ships without types here; just the selectors this block reads.
type BlockEditorSelectors = {
	getBlockCount: ( clientId: string ) => number;
	hasSelectedInnerBlock: ( clientId: string, deep?: boolean ) => boolean;
};

const blockEditor = ( select ): BlockEditorSelectors =>
	select( blockEditorStore ) as unknown as BlockEditorSelectors;

type AppenderProps = {
	clientId: string;
};

const Appender = ( { clientId }: AppenderProps ) => {
	const { insertBlock } = useDispatch( blockEditorStore );
	const count = useSelect(
		( select ) => blockEditor( select ).getBlockCount( clientId ),
		[ clientId ]
	);

	// A single "+" menu, like the core appender: the appender slot is too small for labelled buttons.
	return (
		<DropdownMenu
			className="jmc-buttons__appender"
			icon={ plus }
			label={ __( 'Add button', 'jmc-theme' ) }
			toggleProps={ { variant: 'primary', size: 'small' } }
			controls={ NEW_BUTTONS.map(
				( { linkType, label, text, icon } ) => ( {
					title: label,
					icon,
					onClick: () =>
						insertBlock(
							createBlock( 'jmc/button', { linkType, text } ),
							count,
							clientId
						),
				} )
			) }
		/>
	);
};

export default function Edit( { clientId, isSelected } ) {
	const hasSelectedButton = useSelect(
		( select ) =>
			blockEditor( select ).hasSelectedInnerBlock( clientId, true ),
		[ clientId ]
	);

	const blockProps = useBlockProps( {
		className: 'jmc-buttons',
	} );

	// Unlocked so authors can add, remove and reorder buttons inside a locked section.
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: TEMPLATE,
		templateLock: false,
		orientation: 'horizontal',
		renderAppender:
			isSelected || hasSelectedButton
				? () => <Appender clientId={ clientId } />
				: false,
	} );

	return <div { ...innerBlocksProps } />;
}
