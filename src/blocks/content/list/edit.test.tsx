import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

const renderEdit = ( listStyle: unknown = 'icon', icon = 'check' ) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { listStyle: listStyle as string, icon } }
			setAttributes={ setAttributes }
		/>
	);

	return setAttributes;
};

const list = () =>
	screen.getByTestId( 'inner-blocks' ).parentElement as HTMLElement;

describe( 'List edit', () => {
	afterEach( cleanup );

	it( 'holds list items, starting with one', () => {
		renderEdit();

		expect(
			JSON.parse(
				screen.getByTestId( 'inner-blocks' ).dataset.settings ?? '{}'
			)
		).toEqual( {
			allowedBlocks: [ 'jmc/list-item' ],
			template: [ [ 'jmc/list-item' ] ],
			templateLock: false,
		} );
	} );

	it.each( [
		[ 'icon', 'UL', 'jmc-list jmc-list--icon', 'list' ],
		[ 'bullet', 'UL', 'jmc-list jmc-list--bullet', null ],
		[ 'number', 'OL', 'jmc-list jmc-list--number', null ],
		[ 'unknown', 'UL', 'jmc-list jmc-list--icon', 'list' ],
		[ undefined, 'UL', 'jmc-list jmc-list--icon', 'list' ],
	] )(
		'renders the %s style as a %s',
		( listStyle, tagName, classes, role ) => {
			renderEdit( listStyle );

			expect( list().tagName ).toBe( tagName );
			expect( list().className ).toBe( classes );
			expect( list().getAttribute( 'role' ) ).toBe( role );
		}
	);

	it( 'changes the style', () => {
		const setAttributes = renderEdit( 'bullet' );
		const style = screen.getByRole( 'combobox', {
			name: 'Style',
		} ) as HTMLSelectElement;

		expect(
			Array.from( style.options, ( option ) => option.value )
		).toEqual( [ 'icon', 'bullet', 'number' ] );

		fireEvent.change( style, { target: { value: 'number' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { listStyle: 'number' } );
	} );

	it( 'offers icons only for icon lists', () => {
		renderEdit( 'number' );

		expect( screen.queryByRole( 'combobox', { name: 'Icon' } ) ).toBeNull();
	} );

	it( 'changes the icon', () => {
		const setAttributes = renderEdit( 'icon', 'zap' );
		const icon = screen.getByRole( 'combobox', {
			name: 'Icon',
		} ) as HTMLSelectElement;

		expect( icon.value ).toBe( 'zap' );

		fireEvent.change( icon, { target: { value: 'plus' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { icon: 'plus' } );
	} );
} );
