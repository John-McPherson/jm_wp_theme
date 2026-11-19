import { describe, expect, it } from 'vitest';
import {
	contrast,
	errorSeparation,
	loadPalettes,
	measure,
	parseHex,
	resolve,
} from './contrast';

const palettes = loadPalettes();
const [ theme, ...variations ] = palettes;
const STATUS = [ 'success', 'warning', 'error' ];

describe( 'contrast helpers', () => {
	it( 'measures the WCAG extremes', () => {
		expect(
			contrast( parseHex( '#000' ), parseHex( '#FFFFFF' ) )
		).toBeCloseTo( 21 );
		expect(
			contrast( parseHex( '#777777' ), parseHex( '#777777' ) )
		).toBeCloseTo( 1 );
	} );

	it( 'resolves presets through custom properties and color-mix', () => {
		const colors = { white: '#FFFFFF', 'base-900': '#000000' };
		const tokens = {
			'--jmc-color-base-900': 'var(--wp--preset--color--base-900)',
			'--jmc-text-color': 'var(--jmc-color-base-900)',
		};

		expect( resolve( 'var(--jmc-text-color)', colors, tokens ) ).toEqual( {
			r: 0,
			g: 0,
			b: 0,
			a: 1,
		} );
		expect(
			resolve(
				'color-mix(in srgb, var(--jmc-text-color) 60%, transparent)',
				colors,
				tokens
			).a
		).toBeCloseTo( 0.6 );
		expect(
			resolve(
				'color-mix(in srgb, var(--wp--preset--color--white) 50%, var(--jmc-text-color))',
				colors,
				tokens
			).r
		).toBeCloseTo( 0.5 );
	} );

	it( 'rejects unknown colours', () => {
		expect( () => resolve( 'var(--jmc-missing)', {}, {} ) ).toThrow();
		expect( () => resolve( 'red', {}, {} ) ).toThrow();
	} );
} );

describe( 'colour palettes', () => {
	it( 'ships the default palette and alternate palettes 1 and 2', () => {
		expect( theme.title ).toBe( 'Default' );
		expect( variations.map( ( { title } ) => title ) ).toEqual( [
			'Alternate palette 1',
			'Alternate palette 2',
		] );
	} );

	it( 'shares no colours between palettes except white and status', () => {
		const shared = [ 'white', ...STATUS ];
		const owners = {};

		variations.concat( theme ).forEach( ( { title, palette } ) => {
			new Set(
				palette
					.filter( ( { slug } ) => ! shared.includes( slug ) )
					.map( ( { color } ) => color.toUpperCase() )
			).forEach( ( color ) => {
				owners[ color ] = [ ...( owners[ color ] ?? [] ), title ];
			} );
		} );

		expect(
			Object.entries( owners ).filter(
				( [ , titles ] ) => titles.length > 1
			)
		).toEqual( [] );
	} );

	it.each( variations.map( ( item ) => [ item.file, item ] ) )(
		'%s uses the default palette slugs and names, in order',
		( file, { palette } ) => {
			expect(
				palette.map( ( { slug, name } ) => ( { slug, name } ) )
			).toEqual(
				theme.palette.map( ( { slug, name } ) => ( { slug, name } ) )
			);
		}
	);

	it.each( variations.map( ( item ) => [ item.file, item ] ) )(
		'%s keeps the status colours',
		( file, { palette } ) => {
			const status = ( list ) =>
				list.filter( ( { slug } ) => STATUS.includes( slug ) );

			expect( status( palette ) ).toEqual( status( theme.palette ) );
		}
	);

	it( 'gives every variation a unique title and slug', () => {
		const titles = palettes.map( ( { title } ) => title );
		const slugs = palettes.map( ( { slug } ) => slug );

		expect( new Set( titles ).size ).toBe( titles.length );
		expect( new Set( slugs ).size ).toBe( slugs.length );
		variations.forEach( ( { title } ) => expect( title ).toBeTruthy() );
	} );

	it.each( palettes.map( ( item ) => [ item.file, item ] ) )(
		'%s uses six-digit hex colours',
		( file, { palette } ) => {
			palette.forEach( ( { color } ) =>
				expect( color ).toMatch( /^#[0-9A-F]{6}$/ )
			);
		}
	);
} );

describe( 'contrast', () => {
	it.each( palettes.map( ( item ) => [ item.title, item ] ) )(
		'%s meets WCAG 2.2 AA in every section palette',
		( title, { palette } ) => {
			const failures = measure( palette )
				.filter( ( result ) => ! result.pass )
				.map(
					( result ) =>
						`${ result.palette }: ${
							result.label
						} ${ result.ratio.toFixed( 2 ) }:1 (needs ${
							result.min
						}:1)`
				);

			expect( failures ).toEqual( [] );
		}
	);

	it.each( palettes.map( ( item ) => [ item.title, item ] ) )(
		'%s keeps its highlight distinct from the error colour',
		( title, { palette } ) => {
			errorSeparation( palette ).forEach( ( { distance } ) =>
				expect( distance ).toBeGreaterThanOrEqual( 0.08 )
			);
		}
	);
} );
