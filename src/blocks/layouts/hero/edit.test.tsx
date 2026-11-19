import { useEntityProp } from '@wordpress/core-data';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';
import { TEMPLATE } from './template';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { useEntityProp: vi.fn() } ) );
vi.mock( '@wordpress/server-side-render', () => ( {
	default: ( props: object ) => (
		<div data-testid="breadcrumbs" data-props={ JSON.stringify( props ) } />
	),
} ) );
vi.mock( './editor.scss', () => ( {} ) );

type Attributes = {
	imageId?: number;
	imageUrl?: string;
	palette?: string;
	className?: string;
	showBreadcrumbs?: boolean;
};

let parentId: unknown;

const renderEdit = (
	attributes: Attributes = {},
	context: { postId?: number; postType?: string } = {
		postId: 8,
		postType: 'page',
	}
) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { palette: 'default', ...attributes } }
			setAttributes={ setAttributes }
			context={ context }
		/>
	);

	return setAttributes;
};

const section = () => document.querySelector( 'section' ) as HTMLElement;

const breadcrumbs = () => screen.queryByTestId( 'breadcrumbs' );

describe( 'Hero edit', () => {
	beforeEach( () => {
		parentId = 0;

		vi.mocked( useEntityProp ).mockImplementation(
			() =>
				[ parentId, vi.fn(), undefined ] as ReturnType<
					typeof useEntityProp
				>
		);
	} );

	afterEach( () => {
		cleanup();
		vi.mocked( useEntityProp ).mockReset();
	} );

	it( 'holds a heading, paragraphs and buttons, seeded from its template', () => {
		renderEdit();

		const inner = screen.getByTestId( 'inner-blocks' );

		expect( inner.parentElement?.className ).toBe( 'jmc-hero__text' );
		expect( JSON.parse( inner.dataset.settings ?? '{}' ) ).toEqual( {
			allowedBlocks: [ 'jmc/heading', 'jmc/paragraph', 'jmc/button' ],
			template: TEMPLATE,
			templateLock: false,
		} );
	} );

	it.each( [
		[ 'default', 'jmc-section jmc-hero' ],
		[ 'inverse', 'jmc-section jmc-hero jmc-palette--inverse' ],
		[ 'secondary', 'jmc-section jmc-hero jmc-palette--secondary' ],
		[ 'unknown', 'jmc-section jmc-hero' ],
	] )( 'styles the %s palette', ( palette, classes ) => {
		renderEdit( { palette } );

		expect( section().className ).toBe( classes );
	} );

	it( 'keeps the block’s own class names', () => {
		renderEdit( { className: 'is-style-tall', palette: 'inverse' } );

		expect( section().className ).toBe(
			'jmc-section jmc-hero is-style-tall jmc-palette--inverse'
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

	describe( 'image', () => {
		it( 'shows the chosen image as the background', () => {
			renderEdit( {
				imageId: 4,
				imageUrl: 'https://example.com/van.jpg',
			} );

			const image = document.querySelector(
				'.jmc-hero__img'
			) as HTMLElement;

			expect( image.style.getPropertyValue( '--background-image' ) ).toBe(
				'url("https://example.com/van.jpg")'
			);
			expect(
				screen.getByRole( 'button', { name: 'Replace image' } )
			).toBeTruthy();
		} );

		it( 'has no background without an image', () => {
			renderEdit();

			expect(
				(
					document.querySelector( '.jmc-hero__img' ) as HTMLElement
				 ).style.getPropertyValue( '--background-image' )
			).toBe( 'none' );
		} );

		it( 'saves the chosen image’s ID and URL', () => {
			const setAttributes = renderEdit();

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Select image' } )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				imageId: 101,
				imageUrl: 'https://example.com/logo.png',
			} );
		} );
	} );

	describe( 'breadcrumbs', () => {
		it( 'reads the edited page’s parent', () => {
			renderEdit();

			expect( useEntityProp ).toHaveBeenCalledWith(
				'postType',
				'page',
				'parent',
				8
			);
		} );

		it( 'reads the parent as a page outside a post', () => {
			renderEdit( {}, {} );

			expect( useEntityProp ).toHaveBeenCalledWith(
				'postType',
				'page',
				'parent',
				undefined
			);
		} );

		it( 'previews core Breadcrumbs on a child page, before the content', () => {
			parentId = 3;

			renderEdit();

			expect( section().className ).toBe(
				'jmc-section jmc-hero jmc-hero--has-breadcrumbs'
			);
			expect(
				JSON.parse( breadcrumbs()?.dataset.props ?? '{}' )
			).toEqual( {
				block: 'core/breadcrumbs',
				attributes: {
					className: 'jmc-hero__breadcrumbs',
					showHomeItem: false,
				},
				urlQueryArgs: { post_id: 8 },
			} );
			expect( breadcrumbs()?.closest( '[inert]' ) ).toBeTruthy();
			expect(
				document.querySelector( '.jmc-hero__text' )?.firstElementChild
			).toBe( breadcrumbs()?.closest( '[inert]' ) );
		} );

		it.each( [
			[ 'a top-level page', 0, { postId: 8, postType: 'page' } ],
			[
				'an unresolved parent',
				undefined,
				{ postId: 8, postType: 'page' },
			],
			[ 'a template or pattern', 3, {} ],
		] )( 'shows no breadcrumbs on %s', ( _label, parent, context ) => {
			parentId = parent;

			renderEdit( {}, context );

			expect( breadcrumbs() ).toBeNull();
			expect( section().className ).toBe( 'jmc-section jmc-hero' );
		} );

		it( 'shows no breadcrumbs when they are switched off', () => {
			parentId = 3;

			renderEdit( { showBreadcrumbs: false } );

			expect( breadcrumbs() ).toBeNull();
		} );

		it.each( [
			[ 'on when unset', undefined, true ],
			[ 'off when switched off', false, false ],
		] )( 'shows the toggle %s', ( _label, showBreadcrumbs, checked ) => {
			renderEdit( { showBreadcrumbs } );

			expect(
				(
					screen.getByRole( 'checkbox', {
						name: 'Show breadcrumbs',
					} ) as HTMLInputElement
				 ).checked
			).toBe( checked );
		} );

		it( 'switches the breadcrumbs off', () => {
			const setAttributes = renderEdit();

			fireEvent.click(
				screen.getByRole( 'checkbox', { name: 'Show breadcrumbs' } )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				showBreadcrumbs: false,
			} );
		} );
	} );
} );
