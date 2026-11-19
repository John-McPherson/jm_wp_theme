import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';
import { TEMPLATE } from './template';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

type Attributes = {
	palette?: string;
	order?: string;
	imageId?: number;
	imageUrl?: string;
};

const renderEdit = ( attributes: Attributes = {} ) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { palette: 'default', order: 'left', ...attributes } }
			setAttributes={ setAttributes }
		/>
	);

	return setAttributes;
};

const section = () =>
	document.querySelector( '.jmc-text-with-image' ) as HTMLElement;

describe( 'Text with image edit', () => {
	afterEach( cleanup );

	it( 'holds text blocks, seeded from its template', () => {
		renderEdit();

		const inner = screen.getByTestId( 'inner-blocks' );

		expect( inner.parentElement?.className ).toBe( 'jmc-section__column' );
		expect( JSON.parse( inner.dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [
				'jmc/heading',
				'jmc/paragraph',
				'jmc/list',
				'jmc/button',
			],
			template: TEMPLATE,
			templateLock: false,
		} );
	} );

	it.each( [
		[
			'default',
			'left',
			'jmc-section jmc-text-with-image jmc-text-with-image--image-left',
		],
		[
			'inverse',
			'right',
			'jmc-section jmc-text-with-image jmc-palette--inverse jmc-text-with-image--image-right',
		],
		[
			'secondary',
			'left',
			'jmc-section jmc-text-with-image jmc-palette--secondary jmc-text-with-image--image-left',
		],
		[ 'unknown', 'unknown', 'jmc-section jmc-text-with-image' ],
	] )(
		'styles the %s palette with the image %s',
		( palette, order, classes ) => {
			renderEdit( { palette, order } );

			expect( section().className ).toBe( classes );
		}
	);

	it( 'changes the palette and order', () => {
		const setAttributes = renderEdit();

		fireEvent.change( screen.getByRole( 'combobox', { name: 'PALETTE' } ), {
			target: { value: 'inverse' },
		} );
		fireEvent.change( screen.getByRole( 'combobox', { name: 'ORDER' } ), {
			target: { value: 'right' },
		} );

		expect( setAttributes ).toHaveBeenNthCalledWith( 1, {
			palette: 'inverse',
		} );
		expect( setAttributes ).toHaveBeenNthCalledWith( 2, {
			order: 'right',
		} );
	} );

	it( 'shows the chosen image', () => {
		renderEdit( { imageId: 4, imageUrl: 'https://example.com/van.jpg' } );

		expect(
			document
				.querySelector( '.jmc-text-with-image__image img' )
				?.getAttribute( 'src' )
		).toBe( 'https://example.com/van.jpg' );
		expect(
			screen.getByRole( 'button', { name: 'Replace image' } )
		).toBeTruthy();
	} );

	it( 'shows no image until one is chosen, then saves its ID and URL', () => {
		const setAttributes = renderEdit();

		expect(
			document.querySelector( '.jmc-text-with-image__image img' )
		).toBeNull();

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Select image' } )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			imageId: 101,
			imageUrl: 'https://example.com/logo.png',
		} );
	} );
} );
