/**
 * Copy the theme's chosen icons into build/assets/icons.
 *
 * Lucide outline icons are listed in src/icons/lucide.json and written to
 * build/assets/icons/lucide; Simple Icons brand logos are listed in
 * src/icons/simple-icons.json and written to build/assets/icons/simple-icons.
 * inc/icons.php registers the generated files with the core Icons API.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const { iconToSvg } = require( './shapes' );

const root = path.resolve( __dirname, '../..' );
const lucide = path.dirname( require.resolve( 'lucide-static/package.json' ) );
const manifest = JSON.parse(
	fs.readFileSync( path.join( root, 'src/icons/lucide.json' ), 'utf8' )
);
const nodes = JSON.parse(
	fs.readFileSync( path.join( lucide, 'icon-nodes.json' ), 'utf8' )
);
const output = path.join( root, 'build/assets/icons/lucide' );

fs.rmSync( output, { recursive: true, force: true } );
fs.mkdirSync( output, { recursive: true } );

Object.keys( manifest ).forEach( ( name ) => {
	if ( ! Array.isArray( nodes[ name ] ) ) {
		throw new Error( `Lucide has no icon named "${ name }".` );
	}

	fs.writeFileSync(
		path.join( output, `${ name }.svg` ),
		iconToSvg( name, nodes[ name ] )
	);
} );

fs.writeFileSync(
	path.join( output, 'manifest.json' ),
	JSON.stringify( manifest, null, '\t' ) + '\n'
);
fs.copyFileSync(
	path.join( lucide, 'LICENSE' ),
	path.join( output, 'LICENSE' )
);

// eslint-disable-next-line no-console
console.log( `Built ${ Object.keys( manifest ).length } Lucide icons.` );

/*
 * Simple Icons: each brand SVG is a single filled path on a 24×24 grid. Only
 * the path is kept, since the core Icons API strips everything but `svg`,
 * `path` and `polygon` (including the <title>); the label comes from the
 * manifest instead.
 */
// The package only exports its entry points, which sit at its root.
const simpleIcons = path.dirname( require.resolve( 'simple-icons' ) );
const brandManifest = JSON.parse(
	fs.readFileSync( path.join( root, 'src/icons/simple-icons.json' ), 'utf8' )
);
const brandOutput = path.join( root, 'build/assets/icons/simple-icons' );

fs.rmSync( brandOutput, { recursive: true, force: true } );
fs.mkdirSync( brandOutput, { recursive: true } );

Object.keys( brandManifest ).forEach( ( slug ) => {
	const source = path.join( simpleIcons, 'icons', `${ slug }.svg` );

	if ( ! fs.existsSync( source ) ) {
		throw new Error( `Simple Icons has no icon named "${ slug }".` );
	}

	const paths = [
		...fs.readFileSync( source, 'utf8' ).matchAll( /<path d="([^"]+)"/g ),
	];

	if ( paths.length !== 1 ) {
		throw new Error( `Simple Icon "${ slug }" is not a single path.` );
	}

	fs.writeFileSync(
		path.join( brandOutput, `${ slug }.svg` ),
		[
			`<svg class="simple-icon simple-icon-${ slug }" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">`,
			`\t<path d="${ paths[ 0 ][ 1 ] }" />`,
			'</svg>',
			'',
		].join( '\n' )
	);
} );

fs.writeFileSync(
	path.join( brandOutput, 'manifest.json' ),
	JSON.stringify( brandManifest, null, '\t' ) + '\n'
);
fs.copyFileSync(
	path.join( simpleIcons, 'LICENSE.md' ),
	path.join( brandOutput, 'LICENSE.md' )
);

// eslint-disable-next-line no-console
console.log( `Built ${ Object.keys( brandManifest ).length } Simple Icons.` );
