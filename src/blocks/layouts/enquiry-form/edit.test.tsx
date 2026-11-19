import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

type Props = Parameters< typeof Edit >[ 0 ];

const renderEdit = ( attributes: Partial< Props[ 'attributes' ] > = {} ) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			{ ...( {
				attributes: {
					palette: 'default',
					submitText: '',
					...attributes,
				},
				setAttributes,
			} as unknown as Props ) }
		/>
	);

	return setAttributes;
};

const fields = () =>
	Array.from(
		document.querySelectorAll(
			'.jmc-enquiry-form__field:not(.jmc-enquiry-form__field--checkbox)'
		)
	);

describe( 'Enquiry form edit', () => {
	afterEach( cleanup );

	it.each( [
		[ 'default', 'jmc-section jmc-enquiry-form' ],
		[ 'inverse', 'jmc-section jmc-enquiry-form jmc-palette--inverse' ],
		[ 'secondary', 'jmc-section jmc-enquiry-form jmc-palette--secondary' ],
		[ 'unknown', 'jmc-section jmc-enquiry-form' ],
	] )( 'styles the %s palette', ( palette, classes ) => {
		renderEdit( {
			palette: palette as Props[ 'attributes' ][ 'palette' ],
		} );

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

	it( 'previews the form’s fields in order, marking optional ones', () => {
		renderEdit();

		expect(
			fields().map(
				( field ) =>
					field.querySelector( '.jmc-enquiry-form__label' )
						?.textContent
			)
		).toEqual( [
			'Name',
			'Email address',
			'Phone number (optional)',
			'Postcode (optional)',
			'Service (optional)',
			'Message',
		] );
	} );

	it( 'previews short and multi-line inputs', () => {
		renderEdit();

		expect(
			fields().map(
				( field ) =>
					field.querySelector( '.jmc-enquiry-form__input' )?.className
			)
		).toEqual( [
			'jmc-enquiry-form__input',
			'jmc-enquiry-form__input',
			'jmc-enquiry-form__input',
			'jmc-enquiry-form__input jmc-enquiry-form__input--short',
			'jmc-enquiry-form__input',
			'jmc-enquiry-form__input jmc-enquiry-form__input--textarea',
		] );
	} );

	it( 'previews the consent checkbox', () => {
		renderEdit();

		expect(
			document.querySelector( '.jmc-enquiry-form__field--checkbox' )
				?.textContent
		).toBe( 'I agree to you using these details to reply to my enquiry.' );
	} );

	it( 'previews no real form controls', () => {
		renderEdit();

		expect(
			document.querySelectorAll(
				'.jmc-enquiry-form__form input, .jmc-enquiry-form__form textarea, .jmc-enquiry-form__form select'
			)
		).toHaveLength( 0 );
	} );

	it( 'edits the submit button label, with “Send enquiry” as the placeholder', () => {
		const setAttributes = renderEdit( { submitText: 'Send' } );
		const submit = screen.getByRole( 'textbox', { name: 'Send enquiry' } );

		expect( submit ).toHaveProperty( 'value', 'Send' );
		expect( submit.dataset.tagName ).toBe( 'span' );
		expect( submit.dataset.className ).toBe(
			'jmc-button jmc-enquiry-form__submit'
		);

		fireEvent.change( submit, { target: { value: 'Get a quote' } } );

		expect( setAttributes ).toHaveBeenCalledWith( {
			submitText: 'Get a quote',
		} );
	} );
} );
