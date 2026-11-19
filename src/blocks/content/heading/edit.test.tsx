import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

type Attributes = {
	text?: string;
	level?: string;
	lockLevel?: boolean;
	className?: string;
};

const renderEdit = (
	attributes: Attributes = {},
	context: Record< string, string > = {}
) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { text: '', level: '2', ...attributes } }
			setAttributes={ setAttributes }
			context={ context }
		/>
	);

	return setAttributes;
};

const heading = () => screen.getByRole( 'textbox' );

describe( 'Heading edit', () => {
	afterEach( cleanup );

	it( 'edits the heading text', () => {
		const setAttributes = renderEdit( { text: 'Our services' } );

		expect( heading() ).toHaveProperty( 'value', 'Our services' );

		fireEvent.change( heading(), { target: { value: 'What we do' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { text: 'What we do' } );
	} );

	it.each( [ '1', '2', '3', '4', '5', '6' ] )(
		'renders level %s as its heading element',
		( level ) => {
			renderEdit( { level } );

			expect( heading().dataset.tagName ).toBe( `h${ level }` );
		}
	);

	it.each( [
		[ 'outside a hero', {}, 'jmc-heading' ],
		[
			'in a hero',
			{ 'jmc/variant': 'hero' },
			'jmc-heading jmc-hero__text-heading',
		],
	] )( 'styles the wrapper %s', ( _label, context, classes ) => {
		renderEdit( {}, context );

		expect( ( heading().parentElement as HTMLElement ).className ).toBe(
			classes
		);
	} );

	it( 'keeps the block’s own class names first', () => {
		renderEdit( { className: 'is-style-underlined' } );

		expect( ( heading().parentElement as HTMLElement ).className ).toBe(
			'is-style-underlined jmc-heading'
		);
	} );

	// The level control is shown only for `lockLevel` headings; see the
	// follow-up on whether that is inverted. These tests pin how changing it works.
	it( 'changes the level from the sidebar', () => {
		const setAttributes = renderEdit( { level: '1', lockLevel: true } );
		const level = screen.getByRole( 'combobox', {
			name: 'HEADING_LEVEL',
		} ) as HTMLSelectElement;

		expect( level.value ).toBe( '1' );

		fireEvent.change( level, { target: { value: '3' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { level: '3' } );
	} );
} );
