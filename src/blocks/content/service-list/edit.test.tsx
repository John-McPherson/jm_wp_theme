import { useEntityProp, useEntityRecords } from '@wordpress/core-data';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import usePageOptions from '../../../utils/usePageOptions';
import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( {
	useEntityProp: vi.fn(),
	useEntityRecords: vi.fn(),
} ) );
vi.mock( '@wordpress/server-side-render', () => ( {
	default: ( props: object ) => (
		<div data-testid="preview" data-props={ JSON.stringify( props ) } />
	),
} ) );
vi.mock( '../../../utils/usePageOptions', () => ( { default: vi.fn() } ) );

type Attributes = Parameters< typeof Edit >[ 0 ][ 'attributes' ];

const defaults: Attributes = {
	headingLevel: '3',
	moreText: '',
	source: 'children',
	parentId: 0,
	pageIds: [],
};

type Site = {
	// The `jmc_services_page_id` setting; undefined when it can't be read.
	servicesSetting?: unknown;
	// Pages published with the `services` slug.
	servicesBySlug?: { id: number }[];
	// Published children of the list's parent; undefined while loading.
	children?: { id: number }[];
};

let site: Site;

const renderEdit = (
	attributes: Partial< Attributes > = {},
	context: { postId?: number; postType?: string } = {}
) => {
	const setAttributes = vi.fn();

	render(
		<Edit
			attributes={ { ...defaults, ...attributes } }
			setAttributes={ setAttributes }
			context={ context }
		/>
	);

	return setAttributes;
};

type Query = Record< string, unknown >;

const recordsCall = ( key: 'slug' | 'parent' ) =>
	vi
		.mocked( useEntityRecords )
		.mock.calls.find( ( [ , , query ] ) => key in ( query as Query ) ) as [
		string,
		string,
		Query,
		{ enabled: boolean },
	];

const toggle = () =>
	screen.getByRole( 'checkbox', {
		name: 'Choose pages',
	} ) as HTMLInputElement;

const preview = () =>
	JSON.parse( screen.getByTestId( 'preview' ).dataset.props ?? '{}' );

describe( 'Service list edit', () => {
	beforeEach( () => {
		site = {
			servicesSetting: 0,
			servicesBySlug: [ { id: 20 } ],
			children: [ { id: 31 } ],
		};

		vi.mocked( useEntityProp ).mockImplementation(
			() =>
				[ site.servicesSetting, vi.fn(), undefined ] as ReturnType<
					typeof useEntityProp
				>
		);
		vi.mocked( useEntityRecords ).mockImplementation( ( (
			_kind,
			_name,
			query
		) => {
			if ( 'slug' in ( query as Query ) ) {
				return { records: site.servicesBySlug, hasResolved: true };
			}

			return {
				records: site.children ?? null,
				hasResolved: undefined !== site.children,
			};
		} ) as typeof useEntityRecords );
		vi.mocked( usePageOptions ).mockReturnValue( {
			options: [
				{ label: 'Services', value: '20' },
				{ label: 'Rewiring', value: '31' },
				{ label: 'Fuse boxes', value: '32' },
			],
			isResolving: false,
		} );
	} );

	afterEach( () => {
		cleanup();
		vi.mocked( useEntityProp ).mockReset();
		vi.mocked( useEntityRecords ).mockReset();
	} );

	describe( 'list parent', () => {
		it( 'uses the Services page from the site settings', () => {
			site.servicesSetting = 20;

			renderEdit();

			expect( useEntityProp ).toHaveBeenCalledWith(
				'root',
				'site',
				'jmc_services_page_id'
			);
			expect( recordsCall( 'slug' )[ 3 ] ).toEqual( { enabled: false } );
			expect( recordsCall( 'parent' ) ).toEqual( [
				'postType',
				'page',
				{ parent: 20, status: 'publish', per_page: 1, _fields: 'id' },
				{ enabled: true },
			] );
		} );

		it.each( [
			[ 'unset', 0 ],
			[ 'unreadable', undefined ],
		] )(
			'looks the Services page up by slug when the setting is %s',
			( _label, setting ) => {
				site.servicesSetting = setting;

				renderEdit();

				expect( recordsCall( 'slug' ) ).toEqual( [
					'postType',
					'page',
					{ slug: 'services', status: 'publish', _fields: 'id' },
					{ enabled: true },
				] );
				expect( recordsCall( 'parent' )[ 2 ].parent ).toBe( 20 );
			}
		);

		it( 'lists the children of the page being edited', () => {
			site.servicesSetting = 20;

			renderEdit( {}, { postId: 8, postType: 'page' } );

			expect( recordsCall( 'slug' )[ 3 ] ).toEqual( { enabled: false } );
			expect( recordsCall( 'parent' )[ 2 ].parent ).toBe( 8 );
		} );

		it( 'ignores an edited post that is not a page', () => {
			renderEdit( {}, { postId: 8, postType: 'post' } );

			expect( recordsCall( 'parent' )[ 2 ].parent ).toBe( 20 );
		} );

		it( 'prefers the parent chosen on the block', () => {
			renderEdit( { parentId: 31 }, { postId: 8, postType: 'page' } );

			expect( recordsCall( 'slug' )[ 3 ] ).toEqual( { enabled: false } );
			expect( recordsCall( 'parent' )[ 2 ].parent ).toBe( 31 );
		} );

		it( 'sets and clears the parent page', () => {
			const setAttributes = renderEdit( { parentId: 31 } );
			const parent = screen.getByRole( 'combobox', {
				name: 'Parent page',
			} );

			expect( parent ).toHaveProperty( 'value', 'Rewiring' );

			fireEvent.click(
				screen.getAllByRole( 'button', { name: 'Reset' } )[ 0 ]
			);

			expect( setAttributes ).toHaveBeenCalledWith( { parentId: 0 } );
		} );
	} );

	describe( 'page source', () => {
		it( 'lists child pages by default, with chosen pages optional', () => {
			const setAttributes = renderEdit();

			expect( toggle().checked ).toBe( false );
			expect( toggle().disabled ).toBe( false );
			expect(
				screen.queryByRole( 'combobox', { name: 'Add a page' } )
			).toBeNull();

			fireEvent.click( toggle() );

			expect( setAttributes ).toHaveBeenCalledWith( {
				source: 'chosen',
			} );
		} );

		it( 'switches back to child pages', () => {
			const setAttributes = renderEdit( { source: 'chosen' } );

			expect( toggle().checked ).toBe( true );

			fireEvent.click( toggle() );

			expect( setAttributes ).toHaveBeenCalledWith( {
				source: 'children',
			} );
		} );

		it( 'edits the chosen pages', () => {
			const setAttributes = renderEdit( {
				source: 'chosen',
				pageIds: [ 31, 32 ],
			} );

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Remove Rewiring' } )
			);

			expect( setAttributes ).toHaveBeenCalledWith( { pageIds: [ 32 ] } );
		} );

		it( 'drops malformed chosen page IDs', () => {
			renderEdit( {
				source: 'chosen',
				pageIds: [ 31, -1, '32', 31 ] as unknown as number[],
			} );

			expect(
				screen.getAllByRole( 'button', { name: /^Remove / } )
			).toHaveLength( 1 );
		} );

		it.each( [
			[ 'the parent has no published children', [ { id: 20 } ] ],
			[ 'there is no parent page', [] ],
		] )( 'switches to chosen pages when %s', ( _label, servicesBySlug ) => {
			site.children = [];
			site.servicesBySlug = servicesBySlug;

			const setAttributes = renderEdit();

			expect( setAttributes ).toHaveBeenCalledWith( {
				source: 'chosen',
			} );
			expect( toggle().checked ).toBe( true );
			expect( toggle().disabled ).toBe( true );
			expect(
				screen.getByText(
					'The parent page has no published child pages, so choose the pages to list.'
				)
			).toBeTruthy();
			expect(
				screen.getByRole( 'combobox', { name: 'Add a page' } )
			).toBeTruthy();
		} );

		it( 'skips the children query without a parent page', () => {
			site.servicesBySlug = [];

			renderEdit();

			expect( recordsCall( 'parent' )[ 3 ] ).toEqual( {
				enabled: false,
			} );
		} );

		it( 'keeps an already chosen source without saving it again', () => {
			site.children = [];

			const setAttributes = renderEdit( { source: 'chosen' } );

			expect( setAttributes ).not.toHaveBeenCalled();
		} );

		it( 'disables the toggle until the children have loaded', () => {
			site.children = undefined;

			const setAttributes = renderEdit();

			expect( setAttributes ).not.toHaveBeenCalled();
			expect( toggle().disabled ).toBe( true );
			expect( toggle().checked ).toBe( false );
		} );
	} );

	describe( 'card settings', () => {
		it( 'offers heading levels H2 to H6', () => {
			const setAttributes = renderEdit();
			const level = screen.getByRole( 'combobox', {
				name: 'HEADING_LEVEL',
			} );

			expect(
				Array.from(
					( level as HTMLSelectElement ).options,
					( option ) => option.textContent
				)
			).toEqual( [ 'H2', 'H3', 'H4', 'H5', 'H6' ] );

			fireEvent.change( level, { target: { value: '4' } } );

			expect( setAttributes ).toHaveBeenCalledWith( {
				headingLevel: '4',
			} );
		} );

		it( 'edits the link cue text, with “Learn more” as the placeholder', () => {
			const setAttributes = renderEdit( { moreText: 'View' } );
			const cue = screen.getByLabelText( 'Link cue text' );

			expect( cue.getAttribute( 'placeholder' ) ).toBe( 'Learn more' );
			expect( cue ).toHaveProperty( 'value', 'View' );

			fireEvent.change( cue, { target: { value: 'View services' } } );

			expect( setAttributes ).toHaveBeenCalledWith( {
				moreText: 'View services',
			} );
		} );
	} );

	describe( 'preview', () => {
		it( 'renders the block on the server with the edited page as context', () => {
			renderEdit( { moreText: 'View' }, { postId: 8, postType: 'page' } );

			expect( preview() ).toEqual( {
				block: 'jmc/service-list',
				attributes: { ...defaults, moreText: 'View' },
				urlQueryArgs: { post_id: 8 },
			} );
		} );

		it( 'passes no page outside a page', () => {
			renderEdit( {}, { postId: 8, postType: 'post' } );

			expect( preview().urlQueryArgs ).toEqual( {} );
		} );

		it( 'keeps preview links from leaving the editor', () => {
			renderEdit();

			expect(
				screen.getByTestId( 'preview' ).closest( '[inert]' )
			).toBeTruthy();
		} );
	} );
} );
