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

	const levelControl = () =>
		screen.queryByRole( 'combobox', { name: 'HEADING_LEVEL' } );

	it.each( [
		[ 'omitted', {} ],
		[ 'false', { lockLevel: false } ],
	] )( 'shows the level control when lockLevel is %s', ( _label, attrs ) => {
		renderEdit( attrs );

		expect( levelControl() ).not.toBeNull();
	} );

	it( 'hides the level control when lockLevel is true', () => {
		renderEdit( { level: '1', lockLevel: true } );

		expect( levelControl() ).toBeNull();
		expect( heading().dataset.tagName ).toBe( 'h1' );
	} );

	const optionValues = () =>
		Array.from(
			( levelControl() as HTMLSelectElement ).options,
			( option ) => option.value
		);

	it( 'offers H2 to H6, leaving H1 to the hero', () => {
		renderEdit( { level: '2' } );

		expect( optionValues() ).toEqual( [ '2', '3', '4', '5', '6' ] );
	} );

	it( 'keeps H1 as an option for an existing unlocked H1', () => {
		renderEdit( { level: '1' } );

		expect( optionValues() ).toEqual( [ '1', '2', '3', '4', '5', '6' ] );
		expect( ( levelControl() as HTMLSelectElement ).value ).toBe( '1' );
	} );

	it( 'changes the level from the sidebar', () => {
		const setAttributes = renderEdit( { level: '2' } );
		const level = levelControl() as HTMLSelectElement;

		expect( level.value ).toBe( '2' );

		fireEvent.change( level, { target: { value: '3' } } );

		expect( setAttributes ).toHaveBeenCalledWith( { level: '3' } );
	} );
} );
