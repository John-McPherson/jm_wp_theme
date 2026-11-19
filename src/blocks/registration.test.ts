/// <reference types="vite/client" />
import { registerBlockType } from '@wordpress/blocks';
import { beforeAll, describe, expect, it, vi } from 'vitest';

import { InnerBlocks } from '@wordpress/block-editor';

import WhatsAppIcon from '../components/WhatsAppIcon';

vi.mock( '@wordpress/blocks', () => ( { registerBlockType: vi.fn() } ) );
// Loading the editors only; they are not rendered here.
vi.mock(
	'@wordpress/block-editor',
	() => import( '../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { store: 'core' } ) );
vi.mock( '@wordpress/data', () => ( {} ) );
vi.mock( '@wordpress/server-side-render', () => ( {
	default: () => null,
	ServerSideRender: () => null,
} ) );

// Every block's metadata and registration script, keyed by its folder.
const metadata = import.meta.glob< { default: Record< string, any > } >(
	'./*/*/block.json',
	{ eager: true }
);
const scripts = import.meta.glob( './*/*/index.{ts,tsx}' );
const editors = import.meta.glob< { default: unknown } >( './*/*/edit.tsx', {
	eager: true,
} );

const folders = Object.keys( metadata )
	.map( ( path ) => path.replace( '/block.json', '' ) )
	.sort();

type Settings = Record< string, any >;

const SAVES_INNER_BLOCKS = [
	'jmc/buttons',
	'jmc/column',
	'jmc/credential-list',
	'jmc/list',
	'jmc/cta-banner',
	'jmc/hero',
	'jmc/service-grid',
	'jmc/text-with-image',
	'jmc/trust-credentials',
	'jmc/footer',
	'jmc/navigation',
];

// Settings each script registered, by block name. Kept here because the
// config restores mocks, clearing their calls, before each test.
const registered = new Map< string, Settings >();

const settingsFor = ( name: string ): Settings =>
	registered.get( name ) as Settings;

describe( 'block registration', () => {
	beforeAll( async () => {
		vi.mocked( registerBlockType ).mockImplementation( ( (
			name: string,
			settings: Settings
		) => {
			registered.set( name, settings );
		} ) as unknown as typeof registerBlockType );

		for ( const load of Object.values( scripts ) ) {
			await load();
		}
	} );

	it( 'has a registration script for every block', () => {
		expect(
			Object.keys( scripts )
				.map( ( path ) => path.replace( /\/index\.tsx?$/, '' ) )
				.sort()
		).toEqual( folders );
	} );

	describe.each( folders )( '%s', ( folder ) => {
		const block = metadata[ `${ folder }/block.json` ].default;

		it( 'registers its block.json metadata', () => {
			// Variations are checked below: some gain an icon component.
			const { variations, ...rest } = block;
			const settings = settingsFor( block.name );

			expect( settings ).toMatchObject( rest );
			expect( settings.variations?.length ).toBe( variations?.length );
		} );

		it( 'edits with its own editor', () => {
			expect( settingsFor( block.name ).edit ).toBe(
				editors[ `${ folder }/edit.tsx` ].default
			);
		} );

		// Rendered on the server: only inner blocks are saved in the post.
		it(
			SAVES_INNER_BLOCKS.includes( block.name )
				? 'saves its inner blocks'
				: 'saves nothing',
			() => {
				const saved = settingsFor( block.name ).save();

				if ( SAVES_INNER_BLOCKS.includes( block.name ) ) {
					expect( saved?.type ).toBe( InnerBlocks.Content );
				} else {
					expect( saved ).toBeNull();
				}
			}
		);
	} );

	it.each( [ 'jmc/button', 'jmc/header-cta' ] )(
		'gives the %s WhatsApp variation the WhatsApp logo',
		( name ) => {
			const { variations } = settingsFor( name );
			const json = Object.values( metadata ).find(
				( { default: block } ) => block.name === name
			)?.default.variations;

			expect( variations ).toHaveLength( json.length );
			variations.forEach( ( variation: Settings, index: number ) =>
				expect( variation ).toEqual(
					'whatsapp' === variation.name
						? { ...json[ index ], icon: WhatsAppIcon }
						: json[ index ]
				)
			);
			expect(
				variations.some(
					( variation: Settings ) => variation.icon === WhatsAppIcon
				)
			).toBe( true );
		}
	);

	describe( 'list items', () => {
		it.each( [
			[ { text: 'Fuse ' }, { text: 'boards' }, 'Fuse boards' ],
			[ {}, { text: 'boards' }, 'boards' ],
			[ { text: 'Fuse' }, {}, 'Fuse' ],
		] )(
			'merge %j and %j into “%s”',
			( attributes, attributesToMerge, text ) => {
				expect(
					settingsFor( 'jmc/list-item' ).merge(
						attributes,
						attributesToMerge
					)
				).toEqual( { text } );
			}
		);
	} );
} );
