import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);

// Block names by client ID, and each block's parent.
const editor = vi.hoisted( () => ( {
	names: {} as Record< string, string >,
	parents: {} as Record< string, string | null >,
} ) );

vi.mock( '@wordpress/data', () => ( {
	useSelect: ( callback: ( select: unknown ) => unknown ) =>
		callback( () => ( {
			getBlockRootClientId: ( clientId: string ) =>
				editor.parents[ clientId ] ?? null,
			getBlockName: ( clientId: string ) =>
				editor.names[ clientId ] ?? null,
		} ) ),
} ) );

const renderIn = ( parentName: string | null ) => {
	editor.parents = { 'column-1': parentName ? 'section-1' : null };
	editor.names = parentName ? { 'section-1': parentName } : {};

	render( <Edit clientId="column-1" /> );
};

const inner = () => screen.getByTestId( 'inner-blocks' );

describe( 'Column edit', () => {
	afterEach( cleanup );

	it( 'renders a section column', () => {
		renderIn( 'jmc/service-grid' );

		expect( inner().parentElement?.className ).toBe(
			'jmc-section__column'
		);
	} );

	it( 'lets authors fill CTA banner columns with its content blocks', () => {
		renderIn( 'jmc/cta-banner' );

		expect( JSON.parse( inner().dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [
				'jmc/heading',
				'jmc/paragraph',
				'jmc/list',
				'jmc/buttons',
				'jmc/contact-details',
			],
			templateLock: false,
		} );
	} );

	it.each( [
		[ 'other sections', 'jmc/service-grid' ],
		[ 'a column without a parent', null ],
	] )( 'inherits the lock and template in %s', ( _label, parent ) => {
		renderIn( parent );

		expect( JSON.parse( inner().dataset.settings ?? '{}' ) ).toEqual( {} );
	} );
} );
