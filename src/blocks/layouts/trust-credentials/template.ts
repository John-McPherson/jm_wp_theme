/**
 * The section's starting inner blocks.
 *
 * Kept apart from `edit.tsx` so tests can check it against each block's
 * `parent` and `ancestor` rules without loading the editor.
 */

// The section is just the list of credentials.
export const TEMPLATE = [ [ 'jmc/credential-list' ] ];
