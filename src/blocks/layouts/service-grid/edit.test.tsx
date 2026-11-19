import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';
import { TEMPLATE } from './template';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

const renderEdit = (
	attributes: { palette?: string; order?: string } = {}
) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { palette: 'default', order: 'left', ...attributes } }
			setAttributes={ setAttributes }
		/>
	);

	return setAttributes;
};

describe( 'Service grid edit', () => {
	afterEach( cleanup );

	it( 'holds its locked columns, seeded from its template', () => {
		renderEdit();

		const inner = screen.getByTestId( 'inner-blocks' );

		expect( inner.parentElement?.className ).toBe(
			'jmc-section__container'
		);
		expect( JSON.parse( inner.dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [ 'jmc/column' ],
			template: TEMPLATE,
			templateLock: true,
		} );
	} );

	it.each( [
		[ 'default', 'jmc-section jmc-service-grid' ],
		[ 'inverse', 'jmc-section jmc-service-grid jmc-palette--inverse' ],
		[ 'secondary', 'jmc-section jmc-service-grid jmc-palette--secondary' ],
		[ 'unknown', 'jmc-section jmc-service-grid' ],
	] )( 'styles the %s palette', ( palette, classes ) => {
		renderEdit( { palette } );

		expect( document.querySelector( 'section' )?.className ).toBe(
			classes
		);
	} );

	it( 'changes the palette', () => {
		const setAttributes = renderEdit();

		fireEvent.change( screen.getByRole( 'combobox', { name: 'PALETTE' } ), {
			target: { value: 'secondary' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			palette: 'secondary',
		} );
	} );
} );
