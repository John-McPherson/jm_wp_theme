/**
 * Copy the theme's chosen Lucide icons into build/assets/icons/lucide.
 *
 * The icon list and labels live in src/icons/lucide.json; inc/icons.php
 * registers the generated files with the core Icons API.
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
