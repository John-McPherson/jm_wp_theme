import { describe, expect, it } from 'vitest';

import metadata from './block.json';
import {
	DEFAULT_LIST_ICON,
	DEFAULT_LIST_STYLE,
	LIST_ICON_OPTIONS,
	LIST_STYLE_OPTIONS,
	listTagName,
	toListIconName,
	toListStyle,
} from './options';

import lucideIcons from '../../../icons/lucide.json';

const values = ( options: readonly { value: string }[] ) =>
	options.map( ( option ) => option.value );

describe( 'list style controls', () => {
	it( 'offer exactly the styles block.json allows', () => {
		expect( values( LIST_STYLE_OPTIONS ) ).toEqual(
			metadata.attributes.listStyle.enum
		);
	} );

	it( 'offer exactly the icons block.json allows', () => {
		expect( values( LIST_ICON_OPTIONS ) ).toEqual(
			metadata.attributes.icon.enum
		);
	} );

	it( 'default to the same style and icon as block.json', () => {
		expect( DEFAULT_LIST_STYLE ).toBe(
			metadata.attributes.listStyle.default
		);
		expect( DEFAULT_LIST_ICON ).toBe( metadata.attributes.icon.default );
	} );

	it( 'only offer icons the theme builds from Lucide', () => {
		values( LIST_ICON_OPTIONS ).forEach( ( icon ) => {
			expect( Object.keys( lucideIcons ) ).toContain( icon );
		} );
	} );

	it( 'give every option a label', () => {
		[ ...LIST_STYLE_OPTIONS, ...LIST_ICON_OPTIONS ].forEach( ( option ) => {
			expect( option.label.trim() ).not.toBe( '' );
		} );
	} );
} );

describe( 'toListStyle', () => {
	it.each( [ 'icon', 'bullet', 'number' ] )( 'keeps %s', ( style ) => {
		expect( toListStyle( style ) ).toBe( style );
	} );

	it.each( [
		[ 'missing', undefined ],
		[ 'null', null ],
		[ 'unknown', 'roman' ],
		[ 'uppercase', 'NUMBER' ],
		[ 'array', [ 'number' ] ],
		[ 'number value', 1 ],
	] )( 'falls back to icons when %s', ( _label, value ) => {
		expect( toListStyle( value ) ).toBe( 'icon' );
	} );
} );

describe( 'toListIconName', () => {
	it.each( values( LIST_ICON_OPTIONS ) )(
		'names %s in the theme icon collection',
		( icon ) => {
			expect( toListIconName( icon ) ).toBe( `jmc/${ icon }` );
		}
	);

	it.each( [
		[ 'missing', undefined ],
		[ 'null', null ],
		[ 'unknown', 'skull' ],
		[ 'registered but not offered', 'clock' ],
		[ 'already namespaced', 'jmc/plus' ],
		[ 'path traversal', '../plus' ],
		[ 'array', [ 'plus' ] ],
	] )( 'falls back to the tick when %s', ( _label, value ) => {
		expect( toListIconName( value ) ).toBe( 'jmc/check' );
	} );
} );

describe( 'listTagName', () => {
	it( 'orders numbered lists', () => {
		expect( listTagName( 'number' ) ).toBe( 'ol' );
	} );

	it.each( [ 'icon', 'bullet' ] as const )(
		'leaves %s lists unordered',
		( style ) => {
			expect( listTagName( style ) ).toBe( 'ul' );
		}
	);
} );
