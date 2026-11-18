import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';

import { describe, expect, it } from 'vitest';

type BlockMetadata = {
	name: string;
	parent?: string[];
	ancestor?: string[];
};

type TemplateItem = [ string, Record< string, unknown >?, TemplateItem[]? ];

const BLOCKS_DIR = join( __dirname, '..' );
const LAYOUTS_DIR = __dirname;

const readMetadata = ( dir: string ): BlockMetadata =>
	JSON.parse( readFileSync( join( dir, 'block.json' ), 'utf8' ) );

// Every theme block's metadata, by block name.
const BLOCKS = new Map< string, BlockMetadata >(
	[ 'content', 'layouts', 'site' ].flatMap( ( group ) =>
		readdirSync( join( BLOCKS_DIR, group ) )
			.map( ( name ) => join( BLOCKS_DIR, group, name ) )
			.filter( ( dir ) => existsSync( join( dir, 'block.json' ) ) )
			.map( ( dir ) => {
				const metadata = readMetadata( dir );

				return [ metadata.name, metadata ] as const;
			} )
	)
);

// Layout blocks with inner blocks; blocks without them have no template.
const LAYOUTS = readdirSync( LAYOUTS_DIR ).filter(
	( name ) =>
		existsSync( join( LAYOUTS_DIR, name, 'block.json' ) ) &&
		readFileSync( join( LAYOUTS_DIR, name, 'edit.tsx' ), 'utf8' ).includes(
			'InnerBlocks'
		)
);

/**
 * Lists every block in a template that its `parent` or `ancestor` rules would
 * not allow at that position, inside the block named by `chain`.
 *
 * @param template Inner blocks template, as passed to `useInnerBlocksProps`.
 * @param chain    Names of the enclosing blocks, outermost first.
 * @param blocks   Block metadata, by block name.
 */
const templateViolations = (
	template: TemplateItem[],
	chain: string[],
	blocks: Map< string, BlockMetadata >
): string[] =>
	template.flatMap( ( [ name, , innerBlocks = [] ] ) => {
		const path = [ ...chain, name ].join( ' > ' );
		const metadata = blocks.get( name );

		if ( ! metadata ) {
			return [ `${ path }: no block.json` ];
		}

		const violations: string[] = [];
		const parent = chain[ chain.length - 1 ];

		if ( metadata.parent && ! metadata.parent.includes( parent ) ) {
			violations.push(
				`${ path }: parent must be one of ${ metadata.parent.join( ', ' ) }`
			);
		}

		if (
			metadata.ancestor &&
			! chain.some( ( block ) => metadata.ancestor.includes( block ) )
		) {
			violations.push(
				`${ path }: ancestor must be one of ${ metadata.ancestor.join( ', ' ) }`
			);
		}

		return [
			...violations,
			...templateViolations( innerBlocks, [ ...chain, name ], blocks ),
		];
	} );

describe( 'templateViolations', () => {
	const blocks = new Map< string, BlockMetadata >( [
		[ 'test/section', { name: 'test/section' } ],
		[ 'test/column', { name: 'test/column', parent: [ 'test/section' ] } ],
		[ 'test/text', { name: 'test/text', ancestor: [ 'test/other' ] } ],
	] );

	it( 'accepts blocks their rules allow', () => {
		expect(
			templateViolations(
				[ [ 'test/column' ] ],
				[ 'test/section' ],
				blocks
			)
		).toEqual( [] );
	} );

	it( 'reports a block outside its parent', () => {
		expect(
			templateViolations(
				[ [ 'test/column', {}, [ [ 'test/column' ] ] ] ],
				[ 'test/section' ],
				blocks
			)
		).toEqual( [
			'test/section > test/column > test/column: parent must be one of test/section',
		] );
	} );

	it( 'reports a nested block outside its ancestors', () => {
		expect(
			templateViolations(
				[ [ 'test/column', {}, [ [ 'test/text' ] ] ] ],
				[ 'test/section' ],
				blocks
			)
		).toEqual( [
			'test/section > test/column > test/text: ancestor must be one of test/other',
		] );
	} );

	it( 'reports an unknown block', () => {
		expect(
			templateViolations(
				[ [ 'test/missing' ] ],
				[ 'test/section' ],
				blocks
			)
		).toEqual( [ 'test/section > test/missing: no block.json' ] );
	} );
} );

describe.each( LAYOUTS )( 'the %s editor templates', ( layout ) => {
	const dir = join( LAYOUTS_DIR, layout );

	it( 'are kept in template.ts', () => {
		expect( existsSync( join( dir, 'template.ts' ) ) ).toBe( true );
	} );

	it( 'only use blocks whose parent and ancestor rules allow them', async () => {
		const { name } = readMetadata( dir );
		const templates = Object.values(
			await import( `./${ layout }/template.ts` )
		) as TemplateItem[][];

		expect( templates.length ).toBeGreaterThan( 0 );

		templates.forEach( ( template ) => {
			expect( templateViolations( template, [ name ], BLOCKS ) ).toEqual(
				[]
			);
		} );
	} );
} );
