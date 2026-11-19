import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/server-side-render', () => ( {
	ServerSideRender: ( props: object ) => (
		<div data-testid="preview" data-props={ JSON.stringify( props ) } />
	),
} ) );

describe( 'Site logo edit', () => {
	afterEach( cleanup );

	it( 'previews the logo on the server, with its home link disabled', () => {
		render( <Edit attributes={ { className: 'is-style-mono-dark' } } /> );

		const preview = screen.getByTestId( 'preview' );

		expect( JSON.parse( preview.dataset.props ?? '{}' ) ).toEqual( {
			block: 'jmc/site-logo',
			attributes: { className: 'is-style-mono-dark' },
		} );
		expect( preview.closest( '[inert]' ) ).toBeTruthy();
	} );

	it( 'explains the logo styles and its accessible name', () => {
		render( <Edit attributes={ {} } /> );

		const settings = screen.getByRole( 'region', { name: 'Settings' } );

		expect( settings.textContent ).toContain(
			'Choose the logo colours under Styles.'
		);
		expect( settings.textContent ).toContain(
			'Screen readers announce the logo link with the Site Title.'
		);
	} );
} );
