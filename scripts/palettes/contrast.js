/**
 * Measure WCAG contrast for every colour pairing the theme uses, in every
 * colour palette (the theme.json default plus each style variation in
 * styles/colors) and every section palette (default, secondary, inverse).
 *
 * The semantic token mappings are read from the SCSS that defines them, so
 * the report follows src/scss/tokens/_semantic.scss and src/scss/palettes/
 * without a copy to keep in sync. Run `npm run test:contrast` for the table;
 * scripts/palettes/contrast.test.js enforces the same thresholds.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '../..' );

const SECTION_PALETTES = {
	default: null,
	secondary: 'src/scss/palettes/_secondary.scss',
	inverse: 'src/scss/palettes/_inverse.scss',
};

const TEXT = 4.5;
const NON_TEXT = 3;

/**
 * Every foreground/background pairing rendered by the theme. `fg` is drawn
 * over `bg`, which is drawn over the section background. Values are CSS
 * expressions in the same form the SCSS uses.
 */
const PAIRINGS = [
	{
		id: 'heading-section',
		label: 'Heading / section',
		fg: 'var(--jmc-heading-color)',
		bg: 'var(--jmc-background-color)',
		min: TEXT,
	},
	{
		id: 'heading-card',
		label: 'Heading / card',
		fg: 'var(--jmc-heading-color)',
		bg: 'var(--jmc-card-background)',
		min: TEXT,
	},
	{
		id: 'text-section',
		label: 'Body text / section',
		fg: 'var(--jmc-text-color)',
		bg: 'var(--jmc-background-color)',
		min: TEXT,
	},
	{
		id: 'text-card',
		label: 'Body text / card, input',
		fg: 'var(--jmc-text-color)',
		bg: 'var(--jmc-card-background)',
		min: TEXT,
	},
	{
		id: 'highlight-section',
		label: 'Highlight text, link hover / section',
		fg: 'var(--jmc-text-highlight)',
		bg: 'var(--jmc-background-color)',
		min: TEXT,
	},
	{
		id: 'highlight-card',
		label: 'Highlight text, icon / card',
		fg: 'var(--jmc-text-highlight)',
		bg: 'var(--jmc-card-background)',
		min: TEXT,
	},
	{
		id: 'button',
		label: 'Primary button label / fill',
		fg: 'var(--jmc-button-color)',
		bg: 'var(--jmc-button-background)',
		min: TEXT,
	},
	{
		id: 'button-hover',
		label: 'Primary button hover label / fill',
		fg: 'var(--jmc-button-hover-color)',
		bg: 'var(--jmc-button-hover-background)',
		min: TEXT,
	},
	{
		id: 'button-secondary',
		label: 'Secondary button label, border / section',
		fg: 'var(--jmc-button-secondary-color)',
		bg: 'var(--jmc-button-secondary-background)',
		min: TEXT,
	},
	{
		id: 'button-secondary-card',
		label: 'Secondary button label, border / card',
		fg: 'var(--jmc-button-secondary-color)',
		bg: 'var(--jmc-card-background)',
		min: TEXT,
	},
	{
		id: 'button-secondary-hover',
		label: 'Secondary button hover label / fill',
		fg: 'var(--jmc-button-secondary-hover-color)',
		bg: 'var(--jmc-button-secondary-hover-background)',
		min: TEXT,
	},
	{
		id: 'error-text',
		label: 'Form error message / section',
		fg: 'var(--jmc-error-color)',
		bg: 'var(--jmc-background-color)',
		min: TEXT,
	},
	{
		id: 'focus',
		label: 'Focus ring / section',
		fg: 'var(--jmc-text-highlight)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
	{
		id: 'button-fill',
		label: 'Primary button fill / section',
		fg: 'var(--jmc-button-background)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
	{
		id: 'button-hover-fill',
		label: 'Primary button hover fill / section',
		fg: 'var(--jmc-button-hover-background)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
	{
		id: 'input-border-card',
		label: 'Input border / input',
		fg: 'color-mix(in srgb, var(--jmc-text-color) 60%, transparent)',
		bg: 'var(--jmc-card-background)',
		min: NON_TEXT,
	},
	{
		id: 'input-border-section',
		label: 'Input border / section',
		fg: 'color-mix(in srgb, var(--jmc-text-color) 60%, transparent)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
	{
		id: 'error-border-card',
		label: 'Error border / input, notice',
		fg: 'var(--jmc-error-color)',
		bg: 'var(--jmc-card-background)',
		min: NON_TEXT,
	},
	{
		id: 'error-border-section',
		label: 'Error border / section',
		fg: 'var(--jmc-error-color)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
	{
		id: 'success-border-card',
		label: 'Success notice border / notice',
		fg: 'var(--jmc-color-success)',
		bg: 'var(--jmc-card-background)',
		min: NON_TEXT,
	},
	{
		id: 'logo-primary',
		label: 'Logo primary / section',
		fg: 'var(--jmc-logo-primary)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
	{
		id: 'logo-accent',
		label: 'Logo accent / section',
		fg: 'var(--jmc-logo-accent)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
	{
		id: 'logo-secondary',
		label: 'Logo secondary / section',
		fg: 'var(--jmc-logo-secondary)',
		bg: 'var(--jmc-background-color)',
		min: NON_TEXT,
	},
];

/**
 * Read a JSON file relative to the theme root.
 *
 * @param {string} file Path relative to the theme root.
 * @return {Object} Parsed JSON.
 */
function readJson( file ) {
	return JSON.parse( fs.readFileSync( path.join( root, file ), 'utf8' ) );
}

/**
 * The default palette and every colour style variation.
 *
 * @return {Array<{slug: string, title: string, file: string, palette: Array<{slug: string, name: string, color: string}>}>} Palettes.
 */
function loadPalettes() {
	const theme = readJson( 'theme.json' );
	const palettes = [
		{
			slug: 'default',
			title: 'Default',
			file: 'theme.json',
			palette: theme.settings.color.palette,
		},
	];
	const dir = path.join( root, 'styles/colors' );

	if ( fs.existsSync( dir ) ) {
		fs.readdirSync( dir )
			.filter( ( name ) => name.endsWith( '.json' ) )
			.sort()
			.forEach( ( name ) => {
				const file = `styles/colors/${ name }`;
				const variation = readJson( file );

				palettes.push( {
					slug: variation.slug ?? path.basename( name, '.json' ),
					title: variation.title,
					file,
					palette: variation.settings?.color?.palette ?? [],
				} );
			} );
	}

	return palettes;
}

/**
 * Read the `--jmc-*` custom property declarations from an SCSS file.
 *
 * @param {string} file Path relative to the theme root.
 * @return {Object<string, string>} Property name to value.
 */
function readTokens( file ) {
	const source = fs
		.readFileSync( path.join( root, file ), 'utf8' )
		.replace( /\/\/.*$/gm, '' );
	const tokens = {};

	for ( const match of source.matchAll(
		/(--jmc-[a-z0-9-]+)\s*:\s*([^;]+);/g
	) ) {
		tokens[ match[ 1 ] ] = match[ 2 ].trim();
	}

	return tokens;
}

/**
 * The semantic tokens in effect inside each section palette.
 *
 * @return {Object<string, Object<string, string>>} Section palette to tokens.
 */
function sectionTokens() {
	const base = {
		...readTokens( 'src/scss/tokens/_aliases.scss' ),
		...readTokens( 'src/scss/tokens/_semantic.scss' ),
	};

	return Object.fromEntries(
		Object.entries( SECTION_PALETTES ).map( ( [ name, file ] ) => [
			name,
			file ? { ...base, ...readTokens( file ) } : base,
		] )
	);
}

/**
 * Split a CSS argument list on its top-level commas.
 *
 * @param {string} value Arguments.
 * @return {string[]} Arguments.
 */
function splitArgs( value ) {
	const args = [];
	let depth = 0;
	let current = '';

	for ( const char of value ) {
		if ( char === '(' ) {
			depth++;
		} else if ( char === ')' ) {
			depth--;
		}

		if ( char === ',' && depth === 0 ) {
			args.push( current.trim() );
			current = '';
		} else {
			current += char;
		}
	}

	args.push( current.trim() );

	return args;
}

/**
 * Parse a hex colour.
 *
 * @param {string} hex `#rgb` or `#rrggbb`.
 * @return {{r: number, g: number, b: number, a: number}} Channels from 0 to 1.
 */
function parseHex( hex ) {
	let value = hex.replace( '#', '' );

	if ( value.length === 3 ) {
		value = [ ...value ].map( ( char ) => char + char ).join( '' );
	}

	if ( ! /^[0-9a-f]{6}$/i.test( value ) ) {
		throw new Error( `Unsupported colour "${ hex }".` );
	}

	return {
		r: parseInt( value.slice( 0, 2 ), 16 ) / 255,
		g: parseInt( value.slice( 2, 4 ), 16 ) / 255,
		b: parseInt( value.slice( 4, 6 ), 16 ) / 255,
		a: 1,
	};
}

/**
 * Resolve a CSS colour expression to RGBA.
 *
 * Supports hex, `transparent`, `var()` to theme tokens and presets, and
 * `color-mix(in srgb, …)`.
 *
 * @param {string}                 value   Expression.
 * @param {Object<string, string>} colors  Preset slug to hex.
 * @param {Object<string, string>} tokens  Custom property to value.
 * @param {number}                 [depth] Recursion guard.
 * @return {{r: number, g: number, b: number, a: number}} Colour.
 */
function resolve( value, colors, tokens, depth = 0 ) {
	if ( depth > 20 ) {
		throw new Error( `Circular colour reference at "${ value }".` );
	}

	const expression = value.trim();

	if ( expression === 'transparent' ) {
		return { r: 0, g: 0, b: 0, a: 0 };
	}

	if ( expression.startsWith( '#' ) ) {
		return parseHex( expression );
	}

	const preset = expression.match(
		/^var\(--wp--preset--color--([a-z0-9-]+)\)$/
	);

	if ( preset ) {
		if ( ! colors[ preset[ 1 ] ] ) {
			throw new Error( `Missing preset colour "${ preset[ 1 ] }".` );
		}

		return parseHex( colors[ preset[ 1 ] ] );
	}

	const variable = expression.match( /^var\((--[a-z0-9-]+)\)$/ );

	if ( variable ) {
		if ( ! tokens[ variable[ 1 ] ] ) {
			throw new Error( `Unknown custom property "${ variable[ 1 ] }".` );
		}

		return resolve( tokens[ variable[ 1 ] ], colors, tokens, depth + 1 );
	}

	const mix = expression.match( /^color-mix\(in srgb,(.*)\)$/ );

	if ( mix ) {
		const [ first, second ] = splitArgs( mix[ 1 ] ).map( ( arg ) => {
			const weighted = arg.match( /^(.*?)\s+([\d.]+)%$/ );

			return weighted
				? {
						color: weighted[ 1 ],
						weight: Number( weighted[ 2 ] ) / 100,
					}
				: { color: arg, weight: null };
		} );
		const p =
			first.weight ??
			( second.weight === null ? 0.5 : 1 - second.weight );
		const a = resolve( first.color, colors, tokens, depth + 1 );
		const b = resolve( second.color, colors, tokens, depth + 1 );
		const alpha = a.a * p + b.a * ( 1 - p );
		const channel = ( key ) =>
			alpha === 0
				? 0
				: ( a[ key ] * a.a * p + b[ key ] * b.a * ( 1 - p ) ) / alpha;

		return {
			r: channel( 'r' ),
			g: channel( 'g' ),
			b: channel( 'b' ),
			a: alpha,
		};
	}

	throw new Error( `Unsupported colour expression "${ expression }".` );
}

/**
 * Paint a colour over an opaque backdrop.
 *
 * @param {{r: number, g: number, b: number, a: number}} top      Colour.
 * @param {{r: number, g: number, b: number, a: number}} backdrop Opaque colour.
 * @return {{r: number, g: number, b: number, a: number}} Opaque colour.
 */
function composite( top, backdrop ) {
	const mixChannel = ( key ) =>
		top[ key ] * top.a + backdrop[ key ] * ( 1 - top.a );

	return {
		r: mixChannel( 'r' ),
		g: mixChannel( 'g' ),
		b: mixChannel( 'b' ),
		a: 1,
	};
}

/**
 * WCAG relative luminance.
 *
 * @param {{r: number, g: number, b: number}} color Opaque colour.
 * @return {number} Luminance from 0 to 1.
 */
function luminance( { r, g, b } ) {
	const linear = ( c ) =>
		c <= 0.04045 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );

	return 0.2126 * linear( r ) + 0.7152 * linear( g ) + 0.0722 * linear( b );
}

/**
 * WCAG contrast ratio between two opaque colours.
 *
 * @param {{r: number, g: number, b: number}} a Colour.
 * @param {{r: number, g: number, b: number}} b Colour.
 * @return {number} Ratio from 1 to 21.
 */
function contrast( a, b ) {
	const [ light, dark ] = [ luminance( a ), luminance( b ) ].sort(
		( x, y ) => y - x
	);

	return ( light + 0.05 ) / ( dark + 0.05 );
}

/**
 * Convert an opaque colour to OKLCH.
 *
 * @param {{r: number, g: number, b: number}} color Colour.
 * @return {{l: number, c: number, h: number}} Lightness 0-1, chroma, hue in degrees.
 */
function oklch( { r, g, b } ) {
	const linear = ( c ) =>
		c <= 0.04045 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
	const [ lr, lg, lb ] = [ linear( r ), linear( g ), linear( b ) ];
	const l = Math.cbrt(
		0.4122214708 * lr + 0.5363325363 * lg + 0.0514459929 * lb
	);
	const m = Math.cbrt(
		0.2119034982 * lr + 0.6806995451 * lg + 0.1073969566 * lb
	);
	const s = Math.cbrt(
		0.0883024619 * lr + 0.2817188376 * lg + 0.6299787005 * lb
	);
	const L = 0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s;
	const A = 1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s;
	const B = 0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s;
	const hue = ( Math.atan2( B, A ) * 180 ) / Math.PI;

	return { l: L, c: Math.hypot( A, B ), h: hue < 0 ? hue + 360 : hue };
}

/**
 * Measure every pairing in one colour palette.
 *
 * @param {Array<{slug: string, color: string}>} palette Preset colours.
 * @return {Array<{palette: string, id: string, label: string, min: number, ratio: number, pass: boolean}>} Results.
 */
function measure( palette ) {
	const colors = Object.fromEntries(
		palette.map( ( { slug, color } ) => [ slug, color ] )
	);
	const results = [];

	Object.entries( sectionTokens() ).forEach( ( [ section, tokens ] ) => {
		const page = resolve( 'var(--jmc-background-color)', colors, tokens );

		PAIRINGS.forEach( ( pairing ) => {
			const bg = composite( resolve( pairing.bg, colors, tokens ), page );
			const fg = composite( resolve( pairing.fg, colors, tokens ), bg );
			const ratio = contrast( fg, bg );

			results.push( {
				palette: section,
				id: pairing.id,
				label: pairing.label,
				min: pairing.min,
				ratio,
				pass: ratio >= pairing.min,
			} );
		} );
	} );

	return results;
}

/**
 * How far the highlight colour sits from the error colour in each section
 * palette, so an accent never reads as an error state.
 *
 * @param {Array<{slug: string, color: string}>} palette Preset colours.
 * @return {Array<{palette: string, lightness: number, hue: number, distance: number}>} Differences.
 */
function errorSeparation( palette ) {
	const colors = Object.fromEntries(
		palette.map( ( { slug, color } ) => [ slug, color ] )
	);

	return Object.entries( sectionTokens() ).map( ( [ section, tokens ] ) => {
		const highlight = oklch(
			resolve( 'var(--jmc-text-highlight)', colors, tokens )
		);
		const error = oklch(
			resolve( 'var(--jmc-error-color)', colors, tokens )
		);
		const hue = Math.abs( highlight.h - error.h );
		const toLab = ( { l, c, h } ) => [
			l,
			c * Math.cos( ( h * Math.PI ) / 180 ),
			c * Math.sin( ( h * Math.PI ) / 180 ),
		];
		const [ a, b ] = [ toLab( highlight ), toLab( error ) ];

		return {
			palette: section,
			lightness: Math.abs( highlight.l - error.l ),
			hue: Math.min( hue, 360 - hue ),
			distance: Math.hypot(
				a[ 0 ] - b[ 0 ],
				a[ 1 ] - b[ 1 ],
				a[ 2 ] - b[ 2 ]
			),
		};
	} );
}

/**
 * Print a contrast table per colour palette and return whether all pass.
 *
 * @return {boolean} True when every pairing meets its minimum.
 */
function report() {
	let ok = true;
	const sections = Object.keys( SECTION_PALETTES );

	loadPalettes().forEach( ( { slug, title, palette } ) => {
		const results = measure( palette );
		const rows = PAIRINGS.map( ( pairing ) => {
			const cells = sections.map( ( section ) => {
				const result = results.find(
					( item ) =>
						item.palette === section && item.id === pairing.id
				);

				ok = ok && result.pass;

				return `${ result.ratio.toFixed( 2 ) }${
					result.pass ? '' : ' FAIL'
				}`;
			} );

			return `| ${ pairing.label } | ${ pairing.min }:1 | ${ cells.join( ' | ' ) } |`;
		} );
		const separation = errorSeparation( palette )
			.map(
				( item ) =>
					`${ item.palette } ΔL ${ item.lightness.toFixed( 2 ) }, Δh ${ item.hue.toFixed(
						0
					) }°, ΔE ${ item.distance.toFixed( 2 ) }`
			)
			.join( '; ' );

		process.stdout.write(
			[
				`\n### ${ title } (${ slug })\n`,
				`| Pairing | Min | ${ sections.join( ' | ' ) } |`,
				`| --- | --- | ${ sections.map( () => '---' ).join( ' | ' ) } |`,
				...rows,
				`\nHighlight vs error (OKLCH): ${ separation }\n`,
			].join( '\n' )
		);
	} );

	return ok;
}

module.exports = {
	PAIRINGS,
	contrast,
	errorSeparation,
	loadPalettes,
	measure,
	oklch,
	parseHex,
	resolve,
	sectionTokens,
};

if ( require.main === module ) {
	process.exitCode = report() ? 0 : 1;
}
