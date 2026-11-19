import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import usePageOptions from '../utils/usePageOptions';
import PageListInput from './PageListInput';

vi.mock( '../utils/usePageOptions', () => ( { default: vi.fn() } ) );

const options = [
	{ label: 'About', value: '4' },
	{ label: 'Rewiring', value: '9' },
	{ label: 'Fuse boxes', value: '12' },
];

const mockOptions = ( isResolving = false ) =>
	vi.mocked( usePageOptions ).mockReturnValue( { options, isResolving } );

const renderList = ( value: number[] ) => {
	const set = vi.fn();

	render( <PageListInput value={ value } set={ set } label="Add a page" /> );

	return set;
};

const titles = () =>
	Array.from(
		document.querySelectorAll( '.jmc-page-list-input__title' ),
		( title ) => title.textContent
	);

const button = ( name: string ) =>
	screen.getByRole( 'button', { name } ) as HTMLButtonElement;

describe( 'PageListInput', () => {
	beforeEach( () => mockOptions() );

	afterEach( cleanup );

	it( 'lists nothing until pages are chosen', () => {
		renderList( [] );

		expect( screen.queryByRole( 'list' ) ).toBeNull();
		expect(
			screen.getByRole( 'combobox', { name: 'Add a page' } )
		).toBeTruthy();
	} );

	it( 'lists chosen pages by title, in order', () => {
		renderList( [ 12, 4 ] );

		expect( titles() ).toEqual( [ 'Fuse boxes', 'About' ] );
	} );

	it( 'labels pages that are not published by ID', () => {
		renderList( [ 4, 77 ] );

		expect( titles() ).toEqual( [ 'About', 'Unavailable page #77' ] );
		expect( button( 'Remove Unavailable page #77' ) ).toBeTruthy();
	} );

	it( 'shows pages as loading until the options arrive', () => {
		mockOptions( true );

		renderList( [ 77 ] );

		expect( titles() ).toEqual( [ 'Loading…' ] );
	} );

	it( 'moves a page up or down', () => {
		const set = renderList( [ 4, 9, 12 ] );

		fireEvent.click( button( 'Move Rewiring up' ) );
		fireEvent.click( button( 'Move Rewiring down' ) );

		expect( set ).toHaveBeenNthCalledWith( 1, [ 9, 4, 12 ] );
		expect( set ).toHaveBeenNthCalledWith( 2, [ 4, 12, 9 ] );
	} );

	it( 'disables moving the first page up and the last page down', () => {
		const set = renderList( [ 4, 9 ] );

		expect(
			button( 'Move About up' ).getAttribute( 'aria-disabled' )
		).toBe( 'true' );
		expect(
			button( 'Move Rewiring down' ).getAttribute( 'aria-disabled' )
		).toBe( 'true' );
		expect(
			button( 'Move About down' ).getAttribute( 'aria-disabled' )
		).toBeNull();

		fireEvent.click( button( 'Move About up' ) );

		expect( set ).not.toHaveBeenCalled();
	} );

	it( 'removes a page', () => {
		const set = renderList( [ 4, 9, 12 ] );

		fireEvent.click( button( 'Remove Rewiring' ) );

		expect( set ).toHaveBeenCalledWith( [ 4, 12 ] );
	} );

	it( 'adds a page to the end, offering only pages not yet chosen', () => {
		const set = renderList( [ 4 ] );

		fireEvent.focus( screen.getByRole( 'combobox' ) );
		fireEvent.change( screen.getByRole( 'combobox' ), {
			target: { value: 'u' },
		} );

		expect(
			screen
				.getAllByRole( 'option' )
				.map( ( option ) => option.textContent )
		).toEqual( [ 'Fuse boxes' ] );

		fireEvent.click( screen.getByRole( 'option', { name: 'Fuse boxes' } ) );

		expect( set ).toHaveBeenCalledWith( [ 4, 12 ] );
	} );
} );
