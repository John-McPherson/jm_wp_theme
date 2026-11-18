import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import metadata from './block.json';

type Attributes = {
	text?: string;
};

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null,
	// Backspace at the start of an item joins it onto the item before.
	merge: ( attributes: Attributes, attributesToMerge: Attributes ) => ( {
		text: ( attributes.text ?? '' ) + ( attributesToMerge.text ?? '' ),
	} ),
} as any );
