import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

// Sections whose columns authors fill themselves, with the blocks each allows.
// Columns in other sections inherit the section's lock and keep its template.
const EDITABLE_COLUMNS: Record< string, string[] > = {
	'jmc/cta-banner': [
		'jmc/heading',
		'jmc/paragraph',
		'jmc/list',
		'jmc/buttons',
		'jmc/contact-details',
	],
};

// `@wordpress/block-editor` ships without types here; just the selectors this block reads.
type BlockEditorSelectors = {
	getBlockRootClientId: ( clientId: string ) => string | null;
	getBlockName: ( clientId: string ) => string | null;
};

type Props = {
	clientId: string;
};

export default function Edit( { clientId }: Props ) {
	const parentName = useSelect(
		( select ) => {
			const editor = select(
				blockEditorStore
			) as unknown as BlockEditorSelectors;
			const parentId = editor.getBlockRootClientId( clientId );

			return parentId ? editor.getBlockName( parentId ) : null;
		},
		[ clientId ]
	);

	const allowedBlocks = parentName ? EDITABLE_COLUMNS[ parentName ] : null;

	const blockProps = useBlockProps( {
		className: 'jmc-section__column',
	} );

	const innerBlocksProps = useInnerBlocksProps(
		blockProps,
		allowedBlocks ? { allowedBlocks, templateLock: false } : {}
	);

	return <div { ...innerBlocksProps } />;
}
