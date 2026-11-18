/**
 * The section's starting inner blocks.
 *
 * Kept apart from `edit.tsx` so tests can check it against each block's
 * `parent` and `ancestor` rules without loading the editor.
 */

export const TEMPLATE = [
	[
		'jmc/column',
		{},
		[
			[ 'jmc/paragraph', { variant: 'label' } ],
			[ 'jmc/heading', { level: '2' } ],
		],
	],
	[ 'jmc/column', {}, [ [ 'jmc/service-list' ] ] ],
];
