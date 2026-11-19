import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import type { ButtonAttributes } from '../../../components/ButtonEditor';
import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '../../../components/ButtonEditor', () => ( {
	default: ( props: { className?: string; attributes: object } ) => (
		<div
			data-testid="button-editor"
			data-class-name={ props.className }
			data-attributes={ JSON.stringify( props.attributes ) }
		/>
	),
} ) );

const attributes: ButtonAttributes = {
	text: 'Get a quote',
	buttonType: 'primary',
	linkType: 'link',
	link: { url: '/contact/', opensInNewTab: false },
	emailSubject: '',
	emailBody: '',
	whatsappMessage: '',
};

describe( 'Header CTA edit', () => {
	afterEach( cleanup );

	it( 'edits the button as a small header call to action', () => {
		render( <Edit attributes={ attributes } setAttributes={ vi.fn() } /> );

		const editor = screen.getByTestId( 'button-editor' );

		expect( editor.dataset.className ).toBe(
			'jmc-button--small jmc-header-cta'
		);
		expect( JSON.parse( editor.dataset.attributes ?? '{}' ) ).toEqual(
			attributes
		);
	} );

	it( 'keeps the block’s own class names', () => {
		render(
			<Edit
				attributes={ { ...attributes, className: 'is-style-outline' } }
				setAttributes={ vi.fn() }
			/>
		);

		expect( screen.getByTestId( 'button-editor' ).dataset.className ).toBe(
			'jmc-button--small jmc-header-cta is-style-outline'
		);
	} );

	it( 'explains the link types and when the button is hidden', () => {
		render( <Edit attributes={ attributes } setAttributes={ vi.fn() } /> );

		const settings = screen.getByRole( 'region', { name: 'Settings' } );

		expect( settings.textContent ).toContain( 'Call to action' );
		expect( settings.textContent ).toContain(
			'The button is hidden on the site when the label is empty'
		);
	} );
} );
