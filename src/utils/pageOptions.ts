export type PageOption = {
	label: string;
	value: string;
};

type PageRecord = {
	id?: unknown;
	title?: { raw?: unknown; rendered?: unknown };
};

/**
 * Map page records from the REST API to combobox options.
 *
 * Uses the raw title (core-data fetches pages in the edit context) and falls
 * back to the rendered one. Records without a positive integer ID are
 * dropped, and untitled pages are labelled by ID so they can be told apart.
 *
 * @param records       Page records, as returned by `useEntityRecords`.
 * @param untitledLabel Label for pages without a title.
 *
 * @return Options in record order.
 */
export default function toPageOptions(
	records: unknown,
	untitledLabel = '(no title)'
): PageOption[] {
	if ( ! Array.isArray( records ) ) {
		return [];
	}

	return records.flatMap( ( record: PageRecord | null ) => {
		const id = record?.id;

		if ( typeof id !== 'number' || ! Number.isInteger( id ) || id <= 0 ) {
			return [];
		}

		const raw = record?.title?.raw ?? record?.title?.rendered;
		const title = typeof raw === 'string' ? raw.trim() : '';

		return [
			{
				label: title !== '' ? title : `${ untitledLabel } #${ id }`,
				value: String( id ),
			},
		];
	} );
}
