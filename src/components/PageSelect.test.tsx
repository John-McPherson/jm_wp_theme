import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import usePageOptions from '../utils/usePageOptions';
import PageSelect from './PageSelect';

vi.mock( '../utils/usePageOptions', () => ( { default: vi.fn() } ) );

const options = [
	{ label: 'About', value: '4' },
	{ label: 'Rewiring', value: '9' },
	{ label: 'Rewiring quotes', value: '12' },
];

const field = () => screen.getByRole( 'combobox', { name: 'Parent page' } );

const search = ( text: string ) => {
	fireEvent.focus( field() );
	fireEvent.change( field(), { target: { value: text } } );
};

const suggestions = () =>
	screen.queryAllByRole( 'option' ).map( ( option ) => option.textContent );

describe( 'PageSelect', () => {
	beforeEach( () => {
		vi.mocked( usePageOptions ).mockReturnValue( {
			options,
			isResolving: false,
		} );
	} );

	afterEach( cleanup );

	it( 'shows the selected page with its label and help', () => {
		render(
			<PageSelect
				value={ 9 }
				set={ vi.fn() }
				label="Parent page"
				help="Pick a page."
			/>
		);

		expect( field() ).toHaveProperty( 'value', 'Rewiring' );
		expect( screen.getByText( 'Pick a page.' ) ).toBeTruthy();
	} );

	it( 'shows no page for 0', () => {
		render(
			<PageSelect value={ 0 } set={ vi.fn() } label="Parent page" />
		);

		expect( field() ).toHaveProperty( 'value', '' );
	} );

	it( 'saves the chosen page ID as a number', () => {
		const set = vi.fn();

		render( <PageSelect value={ 0 } set={ set } label="Parent page" /> );

		search( 'Rewiring' );
		fireEvent.click(
			screen.getByRole( 'option', { name: 'Rewiring quotes' } )
		);

		expect( set ).toHaveBeenCalledWith( 12 );
	} );

	it( 'saves 0 when the page is cleared', () => {
		const set = vi.fn();

		render( <PageSelect value={ 4 } set={ set } label="Parent page" /> );

		fireEvent.click( screen.getByRole( 'button', { name: 'Reset' } ) );

		expect( set ).toHaveBeenCalledWith( 0 );
	} );

	it( 'leaves excluded pages out of the suggestions', () => {
		render(
			<PageSelect
				value={ 0 }
				set={ vi.fn() }
				label="Parent page"
				exclude={ [ 9 ] }
			/>
		);

		search( 'Re' );

		expect( suggestions() ).toEqual( [ 'Rewiring quotes' ] );
	} );
} );
