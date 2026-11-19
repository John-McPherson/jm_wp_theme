import { useEntityRecords } from '@wordpress/core-data';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { useEntityRecords: vi.fn() } ) );
vi.mock( '@wordpress/server-side-render', () => ( {
	ServerSideRender: ( props: object ) => (
		<div data-testid="preview" data-props={ JSON.stringify( props ) } />
	),
} ) );
vi.mock( './editor.scss', () => ( {} ) );

type Menu = { id: number; title: { rendered: string } };

let menus: Menu[] | null;
let isResolving: boolean;

const renderEdit = ( navigationId?: number ) => {
	const setAttributes = vi.fn();

	render(
		<Edit attributes={ { navigationId } } setAttributes={ setAttributes } />
	);

	return setAttributes;
};

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

		vi.mocked( useEntityRecords ).mockImplementation(
			() =>
				( { records: menus, isResolving } ) as ReturnType<
					typeof useEntityRecords
				>
		);
	} );

	afterEach( () => {
		cleanup();
		vi.mocked( useEntityRecords ).mockReset();
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

	it( 'previews the menu on the server, with links disabled', () => {
		renderEdit( 5 );

		const preview = screen.getByTestId( 'preview' );

		expect( JSON.parse( preview.dataset.props ?? '{}' ) ).toEqual( {
			block: 'jmc/navigation',
			attributes: { navigationId: 5 },
		} );
		expect( preview.closest( '[inert]' ) ).toBeTruthy();
	} );

	it( 'holds the header call to action after the menu', () => {
		renderEdit( 5 );

		const inner = screen.getByTestId( 'inner-blocks' );
		const wrapper = document.querySelector( '.jmc-navigation-editor' );

		expect( inner.parentElement?.className ).toBe(
			'jmc-navigation__inner-blocks'
		);
		expect( wrapper?.lastElementChild ).toBe( inner.parentElement );
		expect( JSON.parse( inner.dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [ 'jmc/header-cta' ],
			template: [ [ 'jmc/header-cta', { lock: { remove: true } } ] ],
		} );
		expect( screen.queryByRole( 'button', { name: /add/i } ) ).toBeNull();
	} );
} );
