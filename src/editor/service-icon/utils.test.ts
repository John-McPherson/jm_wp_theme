import { describe, expect, it } from 'vitest';

import {
	filterIconOptions,
	isServicePage,
	readServicesSettings,
	toIconOptions,
} from './utils';

describe( 'isServicePage', () => {
	it( 'accepts a page directly beneath the Services page', () => {
		expect( isServicePage( 'page', 10, 10 ) ).toBe( true );
	} );

	it.each( [
		[ 'post beneath Services', 'post', 10, 10 ],
		[ 'top-level page', 'page', 0, 10 ],
		[ 'unrelated parent', 'page', 22, 10 ],
		[ 'unresolved Services page', 'page', 0, 0 ],
		[ 'string parent', 'page', '10', 10 ],
		[ 'missing post type', undefined, 10, 10 ],
	] )( 'rejects %s', ( _label, postType, parentId, pageId ) => {
		expect( isServicePage( postType, parentId, pageId as number ) ).toBe(
			false
		);
	} );
} );

describe( 'toIconOptions', () => {
	it( 'maps, de-duplicates and sorts icons by label', () => {
		expect(
			toIconOptions( [
				{
					name: 'core/wrench',
					label: 'Wrench',
					content: '<svg></svg>',
				},
				{ name: 'core/plug', label: ' Plug ' },
				{ name: 'core/plug', label: 'Plug' },
				{ name: 'jmc/fuse', label: '' },
			] )
		).toEqual( [
			{ label: 'jmc/fuse', value: 'jmc/fuse', content: '' },
			{ label: 'Plug', value: 'core/plug', content: '' },
			{ label: 'Wrench', value: 'core/wrench', content: '<svg></svg>' },
		] );
	} );

	it.each( [
		[ 'null', null ],
		[ 'object', { name: 'core/plug' } ],
		[ 'string', 'core/plug' ],
	] )( 'returns no options for a %s response', ( _label, response ) => {
		expect( toIconOptions( response ) ).toEqual( [] );
	} );

	it( 'hides interface icons: the card arrow, footer and contact details icons', () => {
		expect(
			toIconOptions( [
				{ name: 'jmc/arrow-right', label: 'Arrow right' },
				{ name: 'jmc/clock', label: 'Clock' },
				{ name: 'jmc/house', label: 'House' },
				{ name: 'jmc/mail', label: 'Email' },
				{ name: 'jmc/map-pin', label: 'Map pin' },
				{ name: 'jmc/phone', label: 'Phone' },
				{ name: 'jmc/whatsapp', label: 'WhatsApp' },
				{ name: 'jmc/cctv', label: 'CCTV' },
			] )
		).toEqual( [ { label: 'CCTV', value: 'jmc/cctv', content: '' } ] );
	} );

	it( 'skips malformed entries', () => {
		expect(
			toIconOptions( [ null, { label: 'No name' }, { name: '' } ] )
		).toEqual( [] );
	} );
} );

describe( 'filterIconOptions', () => {
	const options = [
		{ label: 'Plug', value: 'core/plug', content: '' },
		{ label: 'Wrench', value: 'core/wrench', content: '' },
		{ label: 'Fuse', value: 'jmc/fuse-box', content: '' },
	];

	it( 'returns every option for an empty search', () => {
		expect( filterIconOptions( options, '  ' ) ).toEqual( options );
	} );

	it( 'matches labels case-insensitively', () => {
		expect( filterIconOptions( options, 'PLU' ) ).toEqual( [
			options[ 0 ],
		] );
	} );

	it( 'matches icon names', () => {
		expect( filterIconOptions( options, 'fuse-box' ) ).toEqual( [
			options[ 2 ],
		] );
	} );

	it( 'returns nothing when no icon matches', () => {
		expect( filterIconOptions( options, 'kettle' ) ).toEqual( [] );
	} );
} );

describe( 'readServicesSettings', () => {
	it( 'reads localised settings', () => {
		expect(
			readServicesSettings( { pageId: 10, metaKey: 'jmc_service_icon' } )
		).toEqual( { pageId: 10, metaKey: 'jmc_service_icon' } );
	} );

	it.each( [
		[ 'missing', undefined ],
		[ 'negative id', { pageId: -1 } ],
		[ 'string id', { pageId: '10' } ],
		[ 'empty meta key', { pageId: 0, metaKey: '' } ],
	] )( 'falls back safely when %s', ( _label, value ) => {
		expect( readServicesSettings( value ) ).toEqual( {
			pageId: 0,
			metaKey: 'jmc_service_icon',
		} );
	} );
} );
