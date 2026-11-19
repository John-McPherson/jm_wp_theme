import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

const renderEdit = (
	attributes: { text?: string; variant?: string } = {},
	context: Record< string, string > = {}
) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { text: '', ...attributes } }
			setAttributes={ setAttributes }
			context={ context }
		/>
	);

	return setAttributes;
};

const paragraph = () => screen.getByRole( 'textbox' );

describe( 'Paragraph edit', () => {
	afterEach( cleanup );

	it( 'edits the text in a paragraph', () => {
		const setAttributes = renderEdit( { text: 'We cover Leeds.' } );

		expect( paragraph() ).toHaveProperty( 'value', 'We cover Leeds.' );
		expect( paragraph().dataset.tagName ).toBe( 'p' );

		fireEvent.change( paragraph(), { target: { value: 'And Bradford.' } } );

		expect( setAttributes ).toHaveBeenCalledWith( {
			text: 'And Bradford.',
		} );
	} );

	it.each( [
		[ 'a default paragraph', {}, {}, 'jmc-paragraph' ],
		[ 'a label', { variant: 'label' }, {}, 'jmc-paragraph jmc-label' ],
		[
			'a paragraph in a hero',
			{},
			{ 'jmc/variant': 'hero' },
			'jmc-paragraph jmc-hero__text-para',
		],
		[
			'a label in a hero',
			{ variant: 'label' },
			{ 'jmc/variant': 'hero' },
			'jmc-paragraph jmc-label jmc-hero__text-para',
		],
		[
			'unknown variants',
			{ variant: 'shout' },
			{ 'jmc/variant': 'banner' },
			'jmc-paragraph',
		],
	] )( 'styles %s', ( _label, attributes, context, classes ) => {
		renderEdit( attributes, context );

		expect( ( paragraph().parentElement as HTMLElement ).className ).toBe(
			classes
		);
	} );
} );
