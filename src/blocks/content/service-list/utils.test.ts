import { describe, expect, it } from 'vitest';

import { chosenPageIds, isLockedToChosen, listParentId } from './utils';

describe( 'listParentId', () => {
	it.each( [
		[
			'chosen parent wins',
			{ parentId: 40, postId: 30, postType: 'page', servicesPageId: 10 },
			40,
		],
		[
			'edited page',
			{ parentId: 0, postId: 30, postType: 'page', servicesPageId: 10 },
			30,
		],
		[
			'non-page falls back',
			{ parentId: 0, postId: 30, postType: 'post', servicesPageId: 10 },
			10,
		],
		[
			'no context falls back',
			{
				parentId: 0,
				postId: undefined,
				postType: undefined,
				servicesPageId: 10,
			},
			10,
		],
		[
			'malformed parent ignored',
			{
				parentId: '40',
				postId: 30,
				postType: 'page',
				servicesPageId: 10,
			},
			30,
		],
		[
			'nothing resolves',
			{ parentId: -1, postId: 0, postType: 'page', servicesPageId: 0 },
			0,
		],
	] )( '%s', ( _name, args, expected ) => {
		expect( listParentId( args ) ).toBe( expected );
	} );
} );

describe( 'isLockedToChosen', () => {
	it( 'locks when there is no parent', () => {
		expect( isLockedToChosen( 0, undefined ) ).toBe( true );
	} );

	it( 'is unknown while children load', () => {
		expect( isLockedToChosen( 30, undefined ) ).toBeUndefined();
	} );

	it( 'locks when the parent has no children', () => {
		expect( isLockedToChosen( 30, 0 ) ).toBe( true );
	} );

	it( 'unlocks when the parent has children', () => {
		expect( isLockedToChosen( 30, 1 ) ).toBe( false );
	} );
} );

describe( 'chosenPageIds', () => {
	it.each( [
		[ undefined, [] ],
		[ '12,13', [] ],
		[
			[ 13, 12, 13 ],
			[ 13, 12 ],
		],
		[ [ 0, -1, 1.5, '12', null, 14 ], [ 14 ] ],
	] )( 'normalises %j', ( value, expected ) => {
		expect( chosenPageIds( value ) ).toEqual( expected );
	} );
} );
