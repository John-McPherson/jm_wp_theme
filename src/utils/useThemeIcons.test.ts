import apiFetch from '@wordpress/api-fetch';
import { createElement, createRoot } from '@wordpress/element';
import { act } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import useThemeIcons, { type IconContent } from './useThemeIcons';

vi.mock( '@wordpress/api-fetch', () => ( { default: vi.fn() } ) );

declare global {
	var IS_REACT_ACT_ENVIRONMENT: boolean | undefined;
}

let root: ReturnType< typeof createRoot >;
let container: HTMLElement;
let rendered: IconContent[];

function Probe() {
	rendered.push( useThemeIcons() );
	return null;
}

const mount = async () => {
	await act( async () => {
		root.render( createElement( Probe ) );
	} );
};

const latest = () => rendered[ rendered.length - 1 ];

describe( 'useThemeIcons', () => {
	beforeEach( () => {
		globalThis.IS_REACT_ACT_ENVIRONMENT = true;
		container = document.createElement( 'div' );
		root = createRoot( container );
		rendered = [];
	} );

	afterEach( () => {
		act( () => root.unmount() );
		vi.mocked( apiFetch ).mockReset();
		globalThis.IS_REACT_ACT_ENVIRONMENT = undefined;
	} );

	it( 'fetches the theme icon collection once', async () => {
		vi.mocked( apiFetch ).mockResolvedValue( [] );

		await mount();
		await mount();

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/wp/v2/icons/jmc',
		} );
	} );

	it( 'starts empty, then keys each icon’s SVG by name', async () => {
		vi.mocked( apiFetch ).mockResolvedValue( [
			{ name: 'jmc/plug', content: '<svg>plug</svg>' },
			{ name: 'jmc/fuse', content: '<svg>fuse</svg>', label: 'Fuse' },
		] );

		await mount();

		expect( rendered[ 0 ] ).toEqual( {} );
		expect( latest() ).toEqual( {
			'jmc/plug': '<svg>plug</svg>',
			'jmc/fuse': '<svg>fuse</svg>',
		} );
	} );

	it( 'skips malformed icons', async () => {
		vi.mocked( apiFetch ).mockResolvedValue( [
			null,
			{ name: 'jmc/plug' },
			{ content: '<svg></svg>' },
			{ name: 7, content: '<svg></svg>' },
			{ name: 'jmc/fuse', content: '<svg>fuse</svg>' },
		] );

		await mount();

		expect( latest() ).toEqual( { 'jmc/fuse': '<svg>fuse</svg>' } );
	} );

	it.each( [
		[ 'a non-array response', () => Promise.resolve( { code: 'x' } ) ],
		[ 'a failed request', () => Promise.reject( new Error( 'offline' ) ) ],
	] )( 'stays empty after %s', async ( _label, response ) => {
		vi.mocked( apiFetch ).mockImplementation( response );

		await mount();

		expect( latest() ).toEqual( {} );
	} );
} );
