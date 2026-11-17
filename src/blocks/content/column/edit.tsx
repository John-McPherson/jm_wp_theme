import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'jmc-section__column',
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps );

	return <div { ...innerBlocksProps } />;
}
