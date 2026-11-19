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

type Menu = { id: number; title: { rendered: string } };

let menus: Menu[] | null;
let isResolving: boolean;

const renderEdit = ( navigationId = 0 ) => {
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

const options = () =>
	Array.from( menuSelect()?.options ?? [], ( option ) => [
		option.textContent,
		option.value,
	] );

describe( 'Footer navigation edit', () => {
	beforeEach( () => {
		menus = [
			{ id: 5, title: { rendered: 'Services' } },
			{ id: 9, title: { rendered: '' } },
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
		renderEdit();

		expect( useEntityRecords ).toHaveBeenCalledWith(
			'postType',
			'wp_navigation',
			{ per_page: 100, status: 'publish' }
		);
	} );

	it( 'offers no menu first, then the menus, naming untitled ones', () => {
		renderEdit( 5 );

		expect( options() ).toEqual( [
			[ 'Select a menu', '0' ],
			[ 'Services', '5' ],
			[ '(no title)', '9' ],
		] );
		expect( menuSelect()?.value ).toBe( '5' );
	} );

	it( 'offers only “Select a menu” without menus', () => {
		menus = null;

		renderEdit();

		expect( options() ).toEqual( [ [ 'Select a menu', '0' ] ] );
	} );

	it.each( [
		[ '9', 9 ],
		[ '0', 0 ],
	] )( 'saves the menu %s as the number %i', ( value, id ) => {
		const setAttributes = renderEdit( 5 );

		fireEvent.change( menuSelect() as HTMLSelectElement, {
			target: { value },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( { navigationId: id } );
	} );

	it( 'shows a spinner while the menus load', () => {
		isResolving = true;

		renderEdit();

		expect( menuSelect() ).toBeNull();
		expect(
			screen
				.getByRole( 'region', { name: 'Settings' } )
				.querySelector( '.components-spinner' )
		).toBeTruthy();
	} );

	it( 'explains that the navigation is hidden until it has links', () => {
		renderEdit();

		expect(
			screen.getByText(
				'The footer navigation is hidden on the site until a menu with at least one link is selected.'
			)
		).toBeTruthy();
	} );

	it( 'asks for a menu until one is chosen', () => {
		renderEdit();

		expect(
			document.querySelector( '.components-placeholder' )?.textContent
		).toContain( 'Choose a navigation menu in the block settings.' );
		expect( screen.queryByTestId( 'preview' ) ).toBeNull();
	} );

	it( 'previews the chosen menu on the server, with links disabled', () => {
		renderEdit( 5 );

		const preview = screen.getByTestId( 'preview' );

		expect( JSON.parse( preview.dataset.props ?? '{}' ) ).toEqual( {
			block: 'jmc/footer-navigation',
			attributes: { navigationId: 5 },
		} );
		expect( preview.closest( '[inert]' ) ).toBeTruthy();
		expect(
			document.querySelector( '.components-placeholder' )
		).toBeNull();
	} );
} );
