/**
 * The section's starting inner blocks.
 *
 * Kept apart from `edit.tsx` so tests can check it against each block's
 * `parent` and `ancestor` rules without loading the editor.
 */

export const TEMPLATE = [
	[ 'jmc/paragraph', { variant: 'label' } ],
	[ 'jmc/heading', { level: '1', lockLevel: true } ],
	[ 'jmc/paragraph' ],
	[ 'jmc/button', { buttonType: 'secondary' } ],
];
