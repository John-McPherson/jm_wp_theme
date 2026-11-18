import { describe, expect, it } from 'vitest';

import toPageOptions from './pageOptions';

describe( 'toPageOptions', () => {
	it( 'maps records to options in order, preferring the raw title', () => {
		expect(
			toPageOptions( [
				{
					id: 2,
					title: {
						raw: 'Fit-outs & more',
						rendered: 'Fit-outs &amp; more',
					},
				},
				{ id: 1, title: { rendered: 'Rewires' } },
			] )
		).toEqual( [
			{ label: 'Fit-outs & more', value: '2' },
			{ label: 'Rewires', value: '1' },
		] );
	} );

	it( 'labels untitled pages by ID', () => {
		expect(
			toPageOptions( [ { id: 7, title: { raw: '  ' } } ], 'Untitled' )
		).toEqual( [ { label: 'Untitled #7', value: '7' } ] );
	} );

	it.each( [ undefined, null, 'pages', {} ] )(
		'returns nothing for %j',
		( records ) => {
			expect( toPageOptions( records ) ).toEqual( [] );
		}
	);

	it( 'drops records without a positive integer ID', () => {
		expect(
			toPageOptions( [
				null,
				{ id: 0 },
				{ id: -1 },
				{ id: '3' },
				{ id: 1.5 },
				{ id: 4 },
			] )
		).toEqual( [ { label: '(no title) #4', value: '4' } ] );
	} );
} );
