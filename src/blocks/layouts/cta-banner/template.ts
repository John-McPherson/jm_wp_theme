/**
 * The section's starting inner blocks.
 *
 * Kept apart from `edit.tsx` so tests can check it against each block's
 * `parent` and `ancestor` rules without loading the editor.
 */
import type { createBlocksFromInnerBlocksTemplate } from '@wordpress/blocks';

type Template = Parameters< typeof createBlocksFromInnerBlocksTemplate >[ 0 ];

export const TEMPLATE: Template = [
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
			// `jmc/buttons` seeds its own first button.
			[ 'jmc/buttons', {} ],
		],
	],
];

// Contact variation: a heading and supporting text beside the business contact
// details, which `order` places on the chosen side (right by default).
export const CONTACT_TEMPLATE: Template = [
	[
		'jmc/column',
		{},
		[
			[ 'jmc/heading', { level: '2' } ],
			[ 'jmc/paragraph', {} ],
		],
	],
	[ 'jmc/column', {}, [ [ 'jmc/contact-details', {} ] ] ],
];
