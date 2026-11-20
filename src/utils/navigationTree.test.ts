import { parse } from '@wordpress/block-serialization-default-parser';
import { describe, expect, it } from 'vitest';

import navigationTree from './navigationTree';

// The same cases as `NavigationHelperTest.php`, so the editor preview and the
// frontend agree on which items a menu renders.
const block = (
	blockName: string,
	attrs: unknown = {},
	innerBlocks: unknown[] = []
) => ( { blockName, attrs, innerBlocks } );

describe( 'navigationTree', () => {
	it( 'nests one submenu level, keeping structure and order', () => {
		expect(
			navigationTree( [
				block( 'core/navigation-link', {
					id: 12,
					label: 'Home',
					url: '/',
				} ),
				block(
					'core/navigation-submenu',
					{ id: 24, label: ' Services ', url: '/services/' },
					[
						block( 'core/navigation-link', {
							id: 25,
							label: 'Domestic',
							url: '/services/domestic/',
						} ),
						block( 'core/navigation-link', {
							label: 'Commercial',
							url: '/services/commercial/',
						} ),
					]
				),
			] )
		).toEqual( [
			{ id: 12, title: 'Home', url: '/', children: [] },
			{
				id: 24,
				title: 'Services',
				url: '/services/',
				children: [
					{ id: 25, title: 'Domestic', url: '/services/domestic/' },
					{
						id: 0,
						title: 'Commercial',
						url: '/services/commercial/',
					},
				],
			},
		] );
	} );

	it( 'keeps a submenu parent without a URL as a toggle-only item', () => {
		const [ item ] = navigationTree( [
			block( 'core/navigation-submenu', { label: 'About' }, [
				block( 'core/navigation-link', {
					label: 'Team',
					url: '/team/',
				} ),
			] ),
		] );

		expect( item.url ).toBe( '' );
		expect( item.children[ 0 ].title ).toBe( 'Team' );
	} );

	it( 'drops items that cannot become a link or a toggle', () => {
		expect(
			navigationTree( [
				block( 'core/paragraph', { label: 'Not a link', url: '/' } ),
				block( 'core/navigation-link', { label: 'Missing URL' } ),
				block( 'core/navigation-link', {
					label: '   ',
					url: '/blank-label/',
				} ),
				block( 'core/navigation-link', {
					label: [ 'bad' ],
					url: '/array-label/',
				} ),
				block( 'core/navigation-submenu', { label: 'Empty submenu' } ),
				block(
					'core/navigation-submenu',
					{ label: 'Children without URLs' },
					[ block( 'core/navigation-link', { label: 'No URL' } ) ]
				),
				block( 'core/navigation-link', 'malformed' ),
				'not-a-block',
			] )
		).toEqual( [] );
	} );

	it( 'treats a submenu with a URL but no valid children as a link', () => {
		expect(
			navigationTree( [
				block(
					'core/navigation-submenu',
					{ label: 'Contact', url: '/contact/' },
					[ block( 'core/paragraph' ) ]
				),
			] )
		).toEqual( [
			{ id: 0, title: 'Contact', url: '/contact/', children: [] },
		] );
	} );

	it( 'flattens deeper branches into the submenu', () => {
		const [ item ] = navigationTree( [
			block(
				'core/navigation-submenu',
				{ label: 'Services', url: '/services/' },
				[
					block(
						'core/navigation-submenu',
						{ label: 'Domestic', url: '/services/domestic/' },
						[
							block(
								'core/navigation-submenu',
								{
									label: 'Rewiring',
									url: '/services/domestic/rewiring/',
								},
								[
									block( 'core/navigation-link', {
										label: 'Full rewire',
										url: '/services/domestic/rewiring/full/',
									} ),
								]
							),
						]
					),
					block( 'core/navigation-link', {
						label: 'Commercial',
						url: '/services/commercial/',
					} ),
				]
			),
		] );

		expect( item.children.map( ( child ) => child.title ) ).toEqual( [
			'Domestic',
			'Rewiring',
			'Full rewire',
			'Commercial',
		] );
		expect( item.children[ 0 ] ).not.toHaveProperty( 'children' );
	} );

	it( 'reads a menu parsed from its saved content', () => {
		expect(
			navigationTree(
				parse(
					'<!-- wp:navigation-link {"label":"Home","url":"/","id":2} /-->' +
						'<!-- wp:navigation-submenu {"label":"About"} -->' +
						'<!-- wp:navigation-link {"label":"Team","url":"/team/"} /-->' +
						'<!-- /wp:navigation-submenu -->'
				)
			)
		).toEqual( [
			{ id: 2, title: 'Home', url: '/', children: [] },
			{
				id: 0,
				title: 'About',
				url: '',
				children: [ { id: 0, title: 'Team', url: '/team/' } ],
			},
		] );
	} );

	it.each( [ undefined, null, 'menu', {} ] )(
		'returns nothing for %j',
		( blocks ) => {
			expect( navigationTree( blocks ) ).toEqual( [] );
		}
	);
} );
