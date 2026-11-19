import { useEntityProp, useEntityRecords } from '@wordpress/core-data';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
// Menus are stored as parsed blocks in JSON, so tests can describe them directly.
vi.mock( '@wordpress/blocks', () => ( {
	parse: ( raw: string ) => JSON.parse( raw ),
} ) );
vi.mock( '@wordpress/core-data', () => ( {
	useEntityProp: vi.fn(),
	useEntityRecords: vi.fn(),
} ) );

type Block = {
	name: string;
	attributes: Record< string, unknown >;
	innerBlocks: Block[];
};

const link = (
	label: unknown,
	url: unknown = '/privacy/',
	innerBlocks: Block[] = [],
	name = 'core/navigation-link'
): Block => ( { name, attributes: { label, url }, innerBlocks } );

let siteTitle: unknown;
let menus: { id: number; content: { raw: string } }[] | null;

const withMenu = ( blocks: Block[] ) => {
	menus = [ { id: 5, content: { raw: JSON.stringify( blocks ) } } ];
};

const renderEdit = ( copyrightHolder = '' ) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { copyrightHolder } }
			setAttributes={ setAttributes }
		/>
	);

	return setAttributes;
};

const labels = () =>
	Array.from(
		document.querySelectorAll( '.jmc-footer-legal__link' ),
		( item ) => item.textContent
	);

describe( 'Footer legal edit', () => {
	beforeEach( () => {
		siteTitle = 'Sparks Ltd';
		menus = null;

		vi.mocked( useEntityProp ).mockImplementation(
			() =>
				[ siteTitle, vi.fn(), undefined ] as ReturnType<
					typeof useEntityProp
				>
		);
		vi.mocked( useEntityRecords ).mockImplementation(
			() =>
				( { records: menus } ) as ReturnType< typeof useEntityRecords >
		);
	} );

	afterEach( () => {
		cleanup();
		vi.useRealTimers();
	} );

	it( 'credits the theme author', () => {
		renderEdit();

		expect(
			document.querySelector( '.jmc-footer-legal__credit' )?.textContent
		).toBe( 'Site created by John McPherson' );
	} );

	describe( 'copyright', () => {
		it( 'shows this year before the holder', () => {
			vi.useFakeTimers( { toFake: [ 'Date' ] } );
			vi.setSystemTime( new Date( '2031-03-04T12:00:00Z' ) );

			renderEdit( 'Sparks Electrical' );

			const holder = screen.getByRole( 'textbox', {
				name: 'Sparks Ltd',
			} );

			expect(
				document.querySelector( '.jmc-footer-legal__copyright' )
					?.firstChild?.textContent
			).toBe( '© 2031 ' );
			expect( holder ).toHaveProperty( 'value', 'Sparks Electrical' );
			expect( holder.dataset.tagName ).toBe( 'span' );
		} );

		it( 'edits the holder', () => {
			const setAttributes = renderEdit();

			fireEvent.change( screen.getByRole( 'textbox' ), {
				target: { value: 'Sparks Electrical' },
			} );

			expect( setAttributes ).toHaveBeenCalledWith( {
				copyrightHolder: 'Sparks Electrical',
			} );
		} );

		it.each( [
			[ 'empty', '' ],
			[ 'unreadable', undefined ],
		] )(
			'uses a generic placeholder when the site title is %s',
			( _label, title ) => {
				siteTitle = title;

				renderEdit();

				expect(
					screen.getByRole( 'textbox', { name: 'Copyright holder' } )
				).toBeTruthy();
			}
		);

		it( 'reads the site title', () => {
			renderEdit();

			expect( useEntityProp ).toHaveBeenCalledWith(
				'root',
				'site',
				'title'
			);
		} );
	} );

	describe( 'legal links', () => {
		it( 'reads the published Legal menu', () => {
			renderEdit();

			expect( useEntityRecords ).toHaveBeenCalledWith(
				'postType',
				'wp_navigation',
				{ slug: 'legal', status: 'publish', context: 'edit' }
			);
		} );

		it.each( [
			[ 'has not loaded', null ],
			[ 'does not exist', [] ],
		] )( 'hides the links while the menu %s', ( _label, records ) => {
			menus = records;

			renderEdit();

			expect( screen.queryByRole( 'navigation' ) ).toBeNull();
		} );

		it( 'hides the links when the menu has none', () => {
			withMenu( [] );

			renderEdit();

			expect( screen.queryByRole( 'navigation' ) ).toBeNull();
		} );

		it( 'lists the menu’s link labels in order, as text', () => {
			withMenu( [ link( 'Privacy policy' ), link( 'Cookies' ) ] );

			renderEdit();

			const nav = screen.getByRole( 'navigation', { name: 'Legal' } );

			expect( labels() ).toEqual( [ 'Privacy policy', 'Cookies' ] );
			expect( nav.querySelector( 'a' ) ).toBeNull();
		} );

		it( 'flattens submenus, with each submenu link before its children', () => {
			withMenu( [
				link(
					'Policies',
					'/policies/',
					[
						link( 'Privacy policy' ),
						link( 'Cookies', '/cookies/', [
							link( 'Cookie list' ),
						] ),
					],
					'core/navigation-submenu'
				),
				link( 'Terms' ),
			] );

			renderEdit();

			expect( labels() ).toEqual( [
				'Policies',
				'Privacy policy',
				'Cookies',
				'Cookie list',
				'Terms',
			] );
		} );

		it( 'trims labels and skips links without a label or URL', () => {
			withMenu( [
				link( '  Privacy policy  ' ),
				link( '' ),
				link( '   ' ),
				link( 'No URL', '' ),
				link( 'Blank URL', '  ' ),
				link( 7 ),
				link( 'Numeric URL', 7 ),
			] );

			renderEdit();

			expect( labels() ).toEqual( [ 'Privacy policy' ] );
		} );

		it( 'skips other blocks but keeps links nested inside them', () => {
			withMenu( [
				{
					name: 'core/page-list',
					attributes: { label: 'Pages', url: '/pages/' },
					innerBlocks: [ link( 'Privacy policy' ) ],
				},
				{
					name: 'core/spacer',
					attributes: {},
					innerBlocks: undefined as unknown as Block[],
				},
			] );

			renderEdit();

			expect( labels() ).toEqual( [ 'Privacy policy' ] );
		} );

		it( 'lists repeated labels separately', () => {
			withMenu( [ link( 'Privacy' ), link( 'Privacy', '/other/' ) ] );

			renderEdit();

			expect( labels() ).toEqual( [ 'Privacy', 'Privacy' ] );
		} );
	} );
} );
