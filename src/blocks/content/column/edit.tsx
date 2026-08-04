import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function Edit() {
	const blockProps = useBlockProps( {
		className: 'jm-section__column',
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps );

	return <div { ...innerBlocksProps } />;
}
