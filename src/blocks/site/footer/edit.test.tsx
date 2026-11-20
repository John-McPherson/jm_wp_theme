import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

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

describe( 'Footer edit', () => {
	afterEach( cleanup );

	it( 'holds the footer sections, keeping all but the navigation', () => {
		renderEdit();

		const inner = screen.getByTestId( 'inner-blocks' );

		expect( inner.parentElement?.className ).toBe(
			'jmc-footer__container'
		);
		expect( JSON.parse( inner.dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [
				'jmc/trust-credentials',
				'jmc/footer-business',
				'jmc/footer-navigation',
				'jmc/footer-legal',
			],
			template: [
				[ 'jmc/trust-credentials', { lock: { remove: true } } ],
				[ 'jmc/footer-business', { lock: { remove: true } } ],
				[ 'jmc/footer-navigation', {} ],
				[ 'jmc/footer-legal', { lock: { remove: true } } ],
			],
		} );
	} );

	it.each( [
		[ 'default', 'jmc-footer__inner' ],
		[ 'inverse', 'jmc-footer__inner jmc-palette--inverse' ],
		[ 'secondary', 'jmc-footer__inner jmc-palette--secondary' ],
		[ 'unknown', 'jmc-footer__inner' ],
	] )( 'styles the %s palette', ( palette, classes ) => {
		renderEdit( palette );

		expect(
			screen.getByTestId( 'inner-blocks' ).parentElement?.parentElement
				?.className
		).toBe( classes );
	} );

	it( 'changes the palette', () => {
		const setAttributes = renderEdit( 'inverse' );

		fireEvent.change( screen.getByRole( 'combobox', { name: 'PALETTE' } ), {
			target: { value: 'default' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( { palette: 'default' } );
	} );
} );
