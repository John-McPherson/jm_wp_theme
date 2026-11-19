import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';
import { TEMPLATE } from './template';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

type Palette = Parameters< typeof Edit >[ 0 ][ 'attributes' ][ 'palette' ];

const renderEdit = ( palette: string = 'default' ) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { palette: palette as Palette } }
			setAttributes={ setAttributes }
		/>
	);

	return setAttributes;
};

describe( 'Trust credentials edit', () => {
	afterEach( cleanup );

	it( 'holds one credential list in a fixed layout', () => {
		renderEdit();

		const inner = screen.getByTestId( 'inner-blocks' );

		expect( inner.parentElement?.className ).toBe( 'jmc-section__column' );
		expect( inner.parentElement?.parentElement?.className ).toBe(
			'jmc-section__container'
		);
		expect( JSON.parse( inner.dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [ 'jmc/credential-list' ],
			template: TEMPLATE,
			templateLock: 'insert',
		} );
	} );

	it.each( [
		[ 'default', 'jmc-section jmc-trust-credentials' ],
		[ 'inverse', 'jmc-section jmc-trust-credentials jmc-palette--inverse' ],
		[
			'secondary',
			'jmc-section jmc-trust-credentials jmc-palette--secondary',
		],
		[ 'unknown', 'jmc-section jmc-trust-credentials' ],
	] )( 'styles the %s palette', ( palette, classes ) => {
		renderEdit( palette );

		expect( document.querySelector( 'section' )?.className ).toBe(
			classes
		);
	} );

	it( 'changes the palette', () => {
		const setAttributes = renderEdit();

		fireEvent.change( screen.getByRole( 'combobox', { name: 'PALETTE' } ), {
			target: { value: 'inverse' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( { palette: 'inverse' } );
	} );
} );
