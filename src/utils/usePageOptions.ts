import { useEntityRecords } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

import toPageOptions, { PageOption } from './pageOptions';

/**
 * Published pages as combobox options, ordered by title.
 *
 * @return Page options, and whether they are still loading.
 */
export default function usePageOptions(): {
	options: PageOption[];
	isResolving: boolean;
} {
	const { records, isResolving } = useEntityRecords( 'postType', 'page', {
		per_page: -1,
		status: 'publish',
		orderby: 'title',
		order: 'asc',
		_fields: 'id,title',
	} );

	return {
		options: toPageOptions( records, __( '(no title)', 'jmc-theme' ) ),
		isResolving: Boolean( isResolving ),
	};
}
