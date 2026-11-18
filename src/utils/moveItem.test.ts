import { describe, expect, it } from 'vitest';

import moveItem from './moveItem';

describe( 'moveItem', () => {
	it( 'moves an item up', () => {
		expect( moveItem( [ 1, 2, 3 ], 2, -1 ) ).toEqual( [ 1, 3, 2 ] );
	} );

	it( 'moves an item down', () => {
		expect( moveItem( [ 1, 2, 3 ], 0, 1 ) ).toEqual( [ 2, 1, 3 ] );
	} );

	it.each( [
		[ 0, -1 ],
		[ 2, 1 ],
		[ 5, -1 ],
		[ -1, 1 ],
	] )( 'ignores moving index %i by %i off the list', ( index, offset ) => {
		expect( moveItem( [ 1, 2, 3 ], index, offset ) ).toEqual( [ 1, 2, 3 ] );
	} );

	it( 'returns a new list', () => {
		const items = [ 1, 2 ];

		expect( moveItem( items, 0, 1 ) ).not.toBe( items );
		expect( items ).toEqual( [ 1, 2 ] );
	} );
} );
