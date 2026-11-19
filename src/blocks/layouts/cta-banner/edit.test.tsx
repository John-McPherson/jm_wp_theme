import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';
import { CONTACT_TEMPLATE, TEMPLATE } from './template';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/blocks', () => ( {
	createBlocksFromInnerBlocksTemplate: ( template: unknown ) => ( {
		builtFrom: template,
	} ),
} ) );

const replaceInnerBlocks = vi.hoisted( () => vi.fn() );

vi.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( { replaceInnerBlocks } ),
} ) );

type Attributes = { palette?: string; order?: string; layout?: string };

const element = ( attributes: Attributes, setAttributes = vi.fn() ) => (
	<Edit
		attributes={ { palette: 'default', order: 'left', ...attributes } }
		setAttributes={ setAttributes }
		clientId="cta-1"
	/>
);

const section = () => document.querySelector( 'section' ) as HTMLElement;

const container = () =>
	screen.getByTestId( 'inner-blocks' ).parentElement as HTMLElement;

const innerBlocks = () =>
	JSON.parse( screen.getByTestId( 'inner-blocks' ).dataset.settings ?? '{}' );

const orderOptions = () =>
	Array.from(
		(
			screen.getByRole( 'combobox', {
				name: 'ORDER',
			} ) as HTMLSelectElement
		 ).options,
		( option ) => option.textContent
	);

describe( 'CTA banner edit', () => {
	afterEach( () => {
		cleanup();
		replaceInnerBlocks.mockReset();
	} );

	it( 'holds two fixed columns from the button template', () => {
		render( element( {} ) );

		expect( innerBlocks() ).toEqual( {
			allowedBlocks: [ 'jmc/column' ],
			template: TEMPLATE,
			templateLock: 'insert',
		} );
	} );

	it( 'uses the contact template for the contact layout', () => {
		render( element( { layout: 'contact' } ) );

		expect( innerBlocks().template ).toEqual( CONTACT_TEMPLATE );
	} );

	it.each( [
		[ 'side by side', {}, 'jmc-section jmc-cta' ],
		[
			'stacked',
			{ layout: 'stacked' },
			'jmc-section jmc-cta jmc-cta--stacked',
		],
		[
			'contact',
			{ layout: 'contact' },
			'jmc-section jmc-cta jmc-cta--contact',
		],
		[
			'inverse',
			{ palette: 'inverse' },
			'jmc-section jmc-cta jmc-palette--inverse',
		],
		[
			'secondary',
			{ palette: 'secondary' },
			'jmc-section jmc-cta jmc-palette--secondary',
		],
	] )( 'styles the %s section', ( _label, attributes, classes ) => {
		render( element( attributes ) );

		expect( section().className ).toBe( classes );
	} );

	it.each( [
		[ 'left', 'jmc-section__container jmc-column-left' ],
		[ 'right', 'jmc-section__container jmc-column-right' ],
		[ 'unknown', 'jmc-section__container' ],
	] )( 'orders the columns %s', ( order, classes ) => {
		render( element( { order } ) );

		expect( container().className ).toBe( classes );
	} );

	it( 'ignores the order when stacked', () => {
		render( element( { layout: 'stacked', order: 'right' } ) );

		expect( container().className ).toBe( 'jmc-section__container' );
		expect( screen.queryByText( 'Order Settings' ) ).toBeNull();
	} );

	it.each( [
		[ 'button', {}, [ 'Buttons Left', 'Buttons Right' ] ],
		[
			'contact',
			{ layout: 'contact' },
			[ 'Contact Details Left', 'Contact Details Right' ],
		],
	] )(
		'names the %s column in the order options',
		( _label, attributes, labels ) => {
			render( element( attributes ) );

			expect( orderOptions() ).toEqual( labels );
		}
	);

	it( 'changes the palette and order', () => {
		const setAttributes = vi.fn();

		render( element( {}, setAttributes ) );

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

	describe( 'switching variations', () => {
		it( 'keeps the columns on first render', () => {
			render( element( { layout: 'contact' } ) );

			expect( replaceInnerBlocks ).not.toHaveBeenCalled();
		} );

		it( 'rebuilds the columns when switching to and from the contact layout', () => {
			const { rerender } = render( element( {} ) );

			rerender( element( { layout: 'contact' } ) );

			expect( replaceInnerBlocks ).toHaveBeenLastCalledWith( 'cta-1', {
				builtFrom: CONTACT_TEMPLATE,
			} );

			rerender( element( { layout: 'stacked' } ) );

			expect( replaceInnerBlocks ).toHaveBeenLastCalledWith( 'cta-1', {
				builtFrom: TEMPLATE,
			} );
			expect( replaceInnerBlocks ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'keeps the columns when the template does not change', () => {
			const { rerender } = render( element( {} ) );

			rerender( element( { layout: 'stacked' } ) );
			rerender( element( { layout: 'stacked', palette: 'inverse' } ) );

			expect( replaceInnerBlocks ).not.toHaveBeenCalled();
		} );
	} );
} );
