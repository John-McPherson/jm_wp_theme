import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import useThemeIcons from '../../../utils/useThemeIcons';
import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '../../../utils/useThemeIcons', () => ( { default: vi.fn() } ) );

type Context = Parameters< typeof Edit >[ 0 ][ 'context' ];

const renderEdit = ( context: Context = {}, text = '' ) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { text } }
			setAttributes={ setAttributes }
			context={ context }
		/>
	);

	return setAttributes;
};

const icon = () => document.querySelector( '.jmc-list__icon' );

describe( 'List item edit', () => {
	beforeEach( () => {
		vi.mocked( useThemeIcons ).mockReturnValue( {
			'jmc/check': '<svg data-icon="check"></svg>',
			'jmc/zap': '<svg data-icon="zap"></svg>',
		} );
	} );

	afterEach( cleanup );

	it( 'edits the item text', () => {
		const setAttributes = renderEdit( {}, 'Fuse boards' );
		const text = screen.getByRole( 'textbox', { name: 'List item' } );

		expect( text ).toHaveProperty( 'value', 'Fuse boards' );
		expect( text.dataset.tagName ).toBe( 'span' );
		expect( text.dataset.className ).toBe( 'jmc-list__text' );
		expect( text.closest( 'li' )?.className ).toBe( 'jmc-list__item' );

		fireEvent.change( text, { target: { value: 'Rewires' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { text: 'Rewires' } );
	} );

	it( 'shows the list’s icon, decoratively, before the text', () => {
		renderEdit( { 'jmc/listStyle': 'icon', 'jmc/listIcon': 'zap' } );

		expect( icon()?.innerHTML ).toBe( '<svg data-icon="zap"></svg>' );
		expect( icon()?.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( icon()?.nextElementSibling ).toBe(
			screen.getByRole( 'textbox' )
		);
	} );

	it.each( [
		[ 'no list context', {} ],
		[
			'a malformed icon',
			{ 'jmc/listStyle': 'icon', 'jmc/listIcon': 'x' },
		],
	] )( 'falls back to the tick icon for %s', ( _label, context ) => {
		renderEdit( context );

		expect( icon()?.innerHTML ).toBe( '<svg data-icon="check"></svg>' );
	} );

	it( 'keeps an empty icon box until the icons load', () => {
		vi.mocked( useThemeIcons ).mockReturnValue( {} );

		renderEdit();

		expect( icon()?.innerHTML ).toBe( '' );
	} );

	it.each( [ 'bullet', 'number' ] )(
		'shows no icon in %s lists',
		( style ) => {
			renderEdit( { 'jmc/listStyle': style, 'jmc/listIcon': 'zap' } );

			expect( icon() ).toBeNull();
		}
	);
} );
