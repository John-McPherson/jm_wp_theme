/**
 * Convert Lucide icon nodes into path-only SVG markup.
 *
 * The core Icons API sanitises registered SVGs down to `svg`, `path` and
 * `polygon`, so every other shape is rewritten as an equivalent path.
 */

const num = ( value, fallback = 0 ) => {
	const parsed = Number.parseFloat( value );

	return Number.isFinite( parsed ) ? parsed : fallback;
};

const rectToPath = ( { x, y, width, height, rx, ry } ) => {
	const left = num( x );
	const top = num( y );
	const w = num( width );
	const h = num( height );

	let radiusX = num( rx, num( ry ) );
	let radiusY = num( ry, radiusX );

	radiusX = Math.min( Math.max( radiusX, 0 ), w / 2 );
	radiusY = Math.min( Math.max( radiusY, 0 ), h / 2 );

	if ( radiusX === 0 || radiusY === 0 ) {
		return `M${ left } ${ top }h${ w }v${ h }h${ -w }Z`;
	}

	const arc = ( dx, dy ) =>
		`a${ radiusX } ${ radiusY } 0 0 1 ${ dx } ${ dy }`;

	return [
		`M${ left + radiusX } ${ top }`,
		`h${ w - 2 * radiusX }`,
		arc( radiusX, radiusY ),
		`v${ h - 2 * radiusY }`,
		arc( -radiusX, radiusY ),
		`h${ -( w - 2 * radiusX ) }`,
		arc( -radiusX, -radiusY ),
		`v${ -( h - 2 * radiusY ) }`,
		arc( radiusX, -radiusY ),
		'Z',
	].join( '' );
};

const ellipseToPath = ( cx, cy, rx, ry ) =>
	`M${ cx - rx } ${ cy }a${ rx } ${ ry } 0 1 0 ${
		2 * rx
	} 0a${ rx } ${ ry } 0 1 0 ${ -2 * rx } 0Z`;

const pointsToPath = ( points, close ) => {
	const values = String( points ?? '' )
		.trim()
		.split( /[\s,]+/ )
		.map( ( value ) => num( value ) );

	if ( values.length < 4 || values.length % 2 !== 0 ) {
		return '';
	}

	const pairs = [];

	for ( let i = 0; i < values.length; i += 2 ) {
		pairs.push( `${ values[ i ] } ${ values[ i + 1 ] }` );
	}

	return `M${ pairs.join( 'L' ) }${ close ? 'Z' : '' }`;
};

/**
 * Convert one icon node to path data.
 *
 * @param {[string, Record<string, string>]} node Lucide icon node.
 * @return {string} Path data, or an empty string for unsupported nodes.
 */
const nodeToPathData = ( [ tag, attributes = {} ] ) => {
	switch ( tag ) {
		case 'path':
			return typeof attributes.d === 'string' ? attributes.d : '';
		case 'rect':
			return rectToPath( attributes );
		case 'circle':
			return ellipseToPath(
				num( attributes.cx ),
				num( attributes.cy ),
				num( attributes.r ),
				num( attributes.r )
			);
		case 'ellipse':
			return ellipseToPath(
				num( attributes.cx ),
				num( attributes.cy ),
				num( attributes.rx ),
				num( attributes.ry )
			);
		case 'line':
			return `M${ num( attributes.x1 ) } ${ num( attributes.y1 ) }L${ num(
				attributes.x2
			) } ${ num( attributes.y2 ) }`;
		case 'polyline':
			return pointsToPath( attributes.points, false );
		case 'polygon':
			return pointsToPath( attributes.points, true );
		default:
			return '';
	}
};

/**
 * Build path-only SVG markup for a Lucide icon.
 *
 * Stroke styling is applied by theme CSS through the `lucide` class.
 *
 * @param {string}                                  name  Icon name.
 * @param {Array<[string, Record<string, string>]>} nodes Icon nodes.
 * @return {string} SVG markup.
 */
const iconToSvg = ( name, nodes ) => {
	const paths = nodes
		.map( nodeToPathData )
		.filter( ( d ) => d !== '' )
		.map( ( d ) => `\t<path d="${ d.replace( /"/g, '' ) }" />` );

	if ( paths.length !== nodes.length ) {
		throw new Error( `Icon "${ name }" contains unsupported shapes.` );
	}

	return [
		`<svg class="lucide lucide-${ name }" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">`,
		...paths,
		'</svg>',
		'',
	].join( '\n' );
};

module.exports = { iconToSvg, nodeToPathData };
