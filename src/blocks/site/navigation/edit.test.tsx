import { useEntityRecord, useEntityRecords } from '@wordpress/core-data';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( {
	useEntityRecord: vi.fn(),
	useEntityRecords: vi.fn(),
} ) );

type Menu = { id: number; title: { rendered: string } };

let menus: Menu[] | null;
let isResolving: boolean;
let isResolvingMenu: boolean;

// Saved menu content by ID, as core-data returns it in the edit context.
const CONTENT: Record< number, string > = {
	5:
		'<!-- wp:navigation-link {"label":"Home","url":"/","id":2} /-->' +
		'<!-- wp:navigation-submenu {"label":"Services","url":"/services/"} -->' +
		'<!-- wp:navigation-link {"label":"Rewires","url":"/rewires/"} /-->' +
		'<!-- /wp:navigation-submenu -->' +
		'<!-- wp:navigation-submenu {"label":"About"} -->' +
		'<!-- wp:navigation-link {"label":"Team","url":"/team/"} /-->' +
		'<!-- /wp:navigation-submenu -->',
	9: '<!-- wp:navigation-link {"label":"Contact","url":"/contact/"} /-->',
};

const renderEdit = ( navigationId?: number ) => {
	const setAttributes = vi.fn();

	render(
		<Edit attributes={ { navigationId } } setAttributes={ setAttributes } />
	);

	return setAttributes;
};

const navigation = () =>
	document.querySelector( 'nav.jmc-navigation' ) as HTMLElement;

const listItems = () =>
	Array.from(
		navigation().querySelectorAll( ':scope > .jmc-navigation__list > li' )
	);

const menuSelect = () =>
	screen.queryByRole( 'combobox', {
		name: 'NAVIGATION',
	} ) as HTMLSelectElement | null;

describe( 'Navigation edit', () => {
	beforeEach( () => {
		menus = [
			{ id: 5, title: { rendered: 'Primary' } },
			{ id: 9, title: { rendered: 'Secondary' } },
		];
		isResolving = false;

		isResolvingMenu = false;

		vi.mocked( useEntityRecords ).mockImplementation(
			() =>
				( { records: menus, isResolving } ) as ReturnType<
					typeof useEntityRecords
				>
		);
		vi.mocked( useEntityRecord ).mockImplementation(
			( _kind, _name, id ) =>
				( {
					record: CONTENT[ Number( id ) ]
						? { content: { raw: CONTENT[ Number( id ) ] } }
						: undefined,
					isResolving: isResolvingMenu,
				} ) as ReturnType< typeof useEntityRecord >
		);
	} );

	afterEach( () => {
		cleanup();
		vi.mocked( useEntityRecords ).mockReset();
		vi.mocked( useEntityRecord ).mockReset();
	} );

	it( 'reads the published menus', () => {
		renderEdit( 5 );

		expect( useEntityRecords ).toHaveBeenCalledWith(
			'postType',
			'wp_navigation',
			{ per_page: 100, status: 'publish' }
		);
	} );

	it( 'offers the menus by title and changes the menu', () => {
		const setAttributes = renderEdit( 5 );

		expect(
			Array.from( menuSelect()?.options ?? [], ( option ) => [
				option.textContent,
				option.value,
			] )
		).toEqual( [
			[ 'Primary', '5' ],
			[ 'Secondary', '9' ],
		] );
		expect( menuSelect()?.value ).toBe( '5' );

		fireEvent.change( menuSelect() as HTMLSelectElement, {
			target: { value: '9' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( { navigationId: '9' } );
	} );

	it( 'selects the first menu when none is chosen', () => {
		const setAttributes = renderEdit();

		expect( setAttributes ).toHaveBeenCalledWith( { navigationId: 5 } );
	} );

	it( 'keeps the chosen menu', () => {
		const setAttributes = renderEdit( 9 );

		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'waits for the menus before choosing one, showing a spinner', () => {
		isResolving = true;

		const setAttributes = renderEdit();

		expect( setAttributes ).not.toHaveBeenCalled();
		expect( menuSelect() ).toBeNull();
		expect(
			screen
				.getByRole( 'region', { name: 'Settings' } )
				.querySelector( '.components-spinner' )
		).toBeTruthy();
	} );

	it.each( [
		[ 'no menus', [] ],
		[ 'no records', null ],
	] )( 'says so when there are %s', ( _label, records ) => {
		menus = records;

		const setAttributes = renderEdit();

		expect( setAttributes ).not.toHaveBeenCalled();
		expect(
			Array.from( menuSelect()?.options ?? [], ( option ) => [
				option.textContent,
				option.value,
			] )
		).toEqual( [ [ 'No navigation menus found', '' ] ] );
	} );

	it( 'reads the chosen menu', () => {
		renderEdit( 5 );

		expect( useEntityRecord ).toHaveBeenCalledWith(
			'postType',
			'wp_navigation',
			5,
			{ enabled: true }
		);
	} );

	it( 'renders the frontend markup as the block, with the call to action last', () => {
		renderEdit( 5 );

		const nav = navigation();
		const list = nav.querySelector( ':scope > ul.jmc-navigation__list' );
		const toggle = nav.querySelector( ':scope > .jmc-navigation__toggle' );
		const cta = listItems().at( -1 );

		expect( nav.className ).toBe( 'jmc-navigation' );
		expect( toggle?.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( toggle?.getAttribute( 'aria-controls' ) ).toBe( list?.id );
		expect(
			listItems().map( ( item ) => [
				item.className,
				item.querySelector( 'a, span' )?.textContent,
			] )
		).toEqual( [
			[ 'jmc-navigation__item', 'Home' ],
			[
				'jmc-navigation__item jmc-navigation__item--has-submenu',
				'Services',
			],
			[
				'jmc-navigation__item jmc-navigation__item--has-submenu',
				'About',
			],
			[ 'jmc-navigation__item jmc-navigation__item--cta', undefined ],
		] );
		expect( cta?.firstElementChild ).toBe(
			screen.getByTestId( 'inner-blocks' )
		);
		expect(
			JSON.parse(
				screen.getByTestId( 'inner-blocks' ).dataset.settings ?? '{}'
			)
		).toEqual( {
			allowedBlocks: [ 'jmc/header-cta' ],
			template: [ [ 'jmc/header-cta', { lock: { remove: true } } ] ],
		} );
		expect( screen.queryByRole( 'button', { name: /add/i } ) ).toBeNull();
	} );

	it( 'previews the enhanced state, with submenus closed behind toggles', () => {
		renderEdit( 5 );

		expect( navigation().classList.contains( 'no-js' ) ).toBe( false );

		const [ , services, about ] = listItems();

		const servicesToggle = services.querySelector(
			'.jmc-navigation__submenu-toggle'
		);
		const servicesMenu = services.querySelector(
			'ul.jmc-navigation__submenu'
		) as HTMLUListElement;

		expect( servicesToggle?.textContent ).toBe( 'Services submenu' );
		expect( servicesToggle?.getAttribute( 'aria-expanded' ) ).toBe(
			'false'
		);
		expect( servicesToggle?.getAttribute( 'aria-controls' ) ).toBe(
			servicesMenu.id
		);
		expect( servicesMenu.hidden ).toBe( true );
		expect(
			servicesMenu
				.querySelector(
					'li.jmc-navigation__submenu-item > a.jmc-navigation__submenu-link'
				)
				?.getAttribute( 'href' )
		).toBe( '/rewires/' );

		// A parent without a URL shows its label, and its toggle carries it.
		const aboutToggle = about.querySelector(
			'.jmc-navigation__submenu-toggle'
		);
		const aboutMenu = about.querySelector( 'ul.jmc-navigation__submenu' );

		expect( about.querySelector( '.jmc-navigation__label' ) ).toBeTruthy();
		expect( aboutToggle?.className ).toBe(
			'jmc-navigation__submenu-toggle jmc-navigation__submenu-toggle--label'
		);
		expect( aboutToggle?.textContent ).toBe( 'About' );
		expect( aboutMenu?.id ).not.toBe( servicesMenu.id );
	} );

	it( 'keeps the menu inert, and the call to action editable', () => {
		renderEdit( 5 );

		const [ home, services, about, cta ] = listItems();

		for ( const item of [ home, services, about ] ) {
			expect( item.hasAttribute( 'inert' ) ).toBe( true );
		}

		expect(
			navigation()
				.querySelector( '.jmc-navigation__toggle' )
				?.hasAttribute( 'inert' )
		).toBe( true );
		expect( cta.closest( '[inert]' ) ).toBeNull();
	} );

	it( 'updates the preview when another menu is chosen', () => {
		const { rerender } = render(
			<Edit
				attributes={ { navigationId: 5 } }
				setAttributes={ vi.fn() }
			/>
		);

		rerender(
			<Edit
				attributes={ { navigationId: 9 } }
				setAttributes={ vi.fn() }
			/>
		);

		expect(
			listItems().map(
				( item ) => item.querySelector( 'a' )?.textContent
			)
		).toEqual( [ 'Contact', undefined ] );
	} );

	it( 'shows a spinner while the menu loads', () => {
		isResolvingMenu = true;

		renderEdit( 5 );

		expect(
			listItems()[ 0 ].querySelector( '.components-spinner' )
		).toBeTruthy();
	} );

	it( 'keeps the call to action when no menu is chosen', () => {
		menus = [];

		renderEdit();

		expect( useEntityRecord ).toHaveBeenCalledWith(
			'postType',
			'wp_navigation',
			undefined,
			{ enabled: false }
		);
		expect( listItems().map( ( item ) => item.className ) ).toEqual( [
			'jmc-navigation__item jmc-navigation__item--cta',
		] );
	} );
} );
