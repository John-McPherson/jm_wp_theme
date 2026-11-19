import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

describe( 'Credential list edit', () => {
	afterEach( cleanup );

	it( 'is a list of credentials that authors can add to and reorder', () => {
		render( <Edit /> );

		const inner = screen.getByTestId( 'inner-blocks' );
		const list = inner.parentElement as HTMLElement;

		expect( list.tagName ).toBe( 'UL' );
		expect( list.className ).toBe( 'jmc-credential-list' );
		expect( list.getAttribute( 'role' ) ).toBe( 'list' );
		expect( JSON.parse( inner.dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [ 'jmc/credential' ],
			template: [ [ 'jmc/credential' ] ],
			templateLock: false,
			orientation: 'horizontal',
		} );
	} );
} );
