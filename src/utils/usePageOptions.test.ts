import { useEntityRecords } from '@wordpress/core-data';
import { afterEach, describe, expect, it, vi } from 'vitest';

import usePageOptions from './usePageOptions';

vi.mock( '@wordpress/core-data', () => ( {
	useEntityRecords: vi.fn(),
} ) );

const mockRecords = ( records: unknown, isResolving: unknown ) =>
	vi.mocked( useEntityRecords ).mockReturnValue( {
		records,
		isResolving,
	} as unknown as ReturnType< typeof useEntityRecords > );

describe( 'usePageOptions', () => {
	afterEach( () => {
		vi.mocked( useEntityRecords ).mockReset();
	} );

	it( 'requests every published page ordered by title', () => {
		mockRecords( null, true );

		usePageOptions();

		expect( useEntityRecords ).toHaveBeenCalledWith( 'postType', 'page', {
			per_page: -1,
			status: 'publish',
			orderby: 'title',
			order: 'asc',
			_fields: 'id,title',
		} );
	} );

	it( 'returns no options while the pages are loading', () => {
		mockRecords( null, true );

		expect( usePageOptions() ).toEqual( {
			options: [],
			isResolving: true,
		} );
	} );

	it( 'maps the loaded pages to options, labelling untitled pages', () => {
		mockRecords(
			[
				{ id: 4, title: { raw: 'About' } },
				{ id: 9, title: { raw: '' } },
			],
			false
		);

		expect( usePageOptions() ).toEqual( {
			options: [
				{ label: 'About', value: '4' },
				{ label: '(no title) #9', value: '9' },
			],
			isResolving: false,
		} );
	} );

	it( 'treats a missing resolving flag as loaded', () => {
		mockRecords( [], undefined );

		expect( usePageOptions().isResolving ).toBe( false );
	} );
} );
