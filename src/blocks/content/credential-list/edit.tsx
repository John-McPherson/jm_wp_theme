import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

const ALLOWED_BLOCKS = [ 'jmc/credential' ];

// A new list starts with one empty credential, ready for a logo.
const TEMPLATE: [ string ][] = [ [ 'jmc/credential' ] ];

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'jmc-credential-list',
		role: 'list',
	} );

	// Credentials can be added, removed and reordered even though the section locks its own layout.
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: TEMPLATE,
		templateLock: false,
		orientation: 'horizontal',
	} );

	return <ul { ...innerBlocksProps } />;
}
