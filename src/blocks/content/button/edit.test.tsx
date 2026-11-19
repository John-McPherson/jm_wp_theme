import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import type { ButtonAttributes } from '../../../components/ButtonEditor';
import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

const setAttributesSeen = vi.hoisted( () => ( { current: null as unknown } ) );

vi.mock( '../../../components/ButtonEditor', () => ( {
	default: ( props: {
		className?: string;
		attributes: object;
		setAttributes: unknown;
	} ) => {
		setAttributesSeen.current = props.setAttributes;

		return (
			<div
				data-testid="button-editor"
				data-class-name={ props.className ?? '' }
				data-attributes={ JSON.stringify( props.attributes ) }
			/>
		);
	},
} ) );

const attributes: ButtonAttributes = {
	text: 'Call us',
	buttonType: 'secondary',
	linkType: 'phone',
	link: { url: '', opensInNewTab: false },
	emailSubject: '',
	emailBody: '',
	whatsappMessage: '',
};

describe( 'Button edit', () => {
	afterEach( cleanup );

	it( 'edits the button with the shared button editor', () => {
		const setAttributes = vi.fn();

		render(
			<Edit attributes={ attributes } setAttributes={ setAttributes } />
		);

		const editor = screen.getByTestId( 'button-editor' );

		expect( JSON.parse( editor.dataset.attributes ?? '{}' ) ).toEqual(
			attributes
		);
		expect( editor.dataset.className ).toBe( '' );
		expect( setAttributesSeen.current ).toBe( setAttributes );
	} );

	it( 'passes the block’s class names to the button', () => {
		render(
			<Edit
				attributes={ { ...attributes, className: 'is-style-wide' } }
				setAttributes={ vi.fn() }
			/>
		);

		expect( screen.getByTestId( 'button-editor' ).dataset.className ).toBe(
			'is-style-wide'
		);
	} );
} );
