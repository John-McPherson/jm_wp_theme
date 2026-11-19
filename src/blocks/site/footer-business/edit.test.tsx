import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import useThemeIcons from '../../../utils/useThemeIcons';
import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { store: 'core' } ) );

const site = vi.hoisted( () => ( {
	record: undefined as Record< string, unknown > | undefined,
	getEntityRecord: vi.fn(),
} ) );

vi.mock( '@wordpress/data', () => ( {
	useSelect: ( callback: ( select: unknown ) => unknown ) =>
		callback( () => ( { getEntityRecord: site.getEntityRecord } ) ),
} ) );
vi.mock( '../../../utils/useThemeIcons', () => ( { default: vi.fn() } ) );
vi.mock( '../../../components/ContactSettings', () => ( {
	default: ( { showNameAndAddress }: { showNameAndAddress?: boolean } ) => (
		<div
			data-testid="contact-settings"
			data-show-name-and-address={ String( showNameAndAddress ) }
		/>
	),
} ) );

const block = () => document.querySelector( '.jmc-footer-business' );

const line = ( name: string ) =>
	document.querySelector( `.jmc-footer-business__${ name }` );

const renderWith = ( record?: Record< string, unknown > ) => {
	site.record = record;
	render( <Edit /> );
};

describe( 'Footer business edit', () => {
	beforeEach( () => {
		site.getEntityRecord.mockImplementation( () => site.record );
		vi.mocked( useThemeIcons ).mockReturnValue( {
			'jmc/map-pin': '<svg data-icon="map-pin"></svg>',
			'jmc/phone': '<svg data-icon="phone"></svg>',
			'jmc/whatsapp': '<svg data-icon="whatsapp"></svg>',
			'jmc/mail': '<svg data-icon="mail"></svg>',
			'jmc/house': '<svg data-icon="house"></svg>',
		} );
	} );

	afterEach( () => {
		cleanup();
		site.getEntityRecord.mockReset();
	} );

	it( 'reads the saved site settings', () => {
		renderWith( {} );

		expect( site.getEntityRecord ).toHaveBeenCalledWith( 'root', 'site' );
	} );

	it( 'offers the shared business details, including name and address', () => {
		renderWith( {} );

		expect(
			screen.getByTestId( 'contact-settings' ).dataset.showNameAndAddress
		).toBe( 'true' );
		expect( screen.getByText( 'Footer details' ) ).toBeTruthy();
	} );

	it( 'shows every saved detail in the order the site does', () => {
		renderWith( {
			jmc_business_name: 'Sparks Ltd',
			jmc_business_address: '1 High Street\nLeeds',
			jmc_business_phone: '01234 567890',
			jmc_business_whatsapp: '+44 7700 900123',
			jmc_business_email: 'hello@example.com',
			jmc_business_service_area: [ 'Leeds' ],
		} );

		expect(
			Array.from(
				block()?.querySelectorAll( 'p' ) ?? [],
				( paragraph ) => paragraph.className
			)
		).toEqual( [
			'jmc-footer-business__name',
			'jmc-footer-business__line jmc-footer-business__address',
			'jmc-footer-business__line jmc-footer-business__phone',
			'jmc-footer-business__line jmc-footer-business__whatsapp',
			'jmc-footer-business__line jmc-footer-business__email',
			'jmc-footer-business__line jmc-footer-business__service-area',
		] );
		expect(
			screen.getByRole( 'link', { name: 'Sparks Ltd' } )
		).toBeTruthy();
		expect(
			screen.getByRole( 'link', { name: '01234 567890' } )
		).toBeTruthy();
		expect(
			screen.getByRole( 'link', { name: 'WhatsApp: +44 7700 900123' } )
		).toBeTruthy();
		expect(
			screen.getByRole( 'link', { name: 'hello@example.com' } )
		).toBeTruthy();
		expect( block()?.querySelector( 'address' )?.children ).toHaveLength(
			4
		);
		expect( line( 'empty' ) ).toBeNull();
	} );

	it.each( [
		[ 'address', 'map-pin' ],
		[ 'phone', 'phone' ],
		[ 'whatsapp', 'whatsapp' ],
		[ 'email', 'mail' ],
		[ 'service-area', 'house' ],
	] )( 'marks the %s line with the decorative %s icon', ( name, icon ) => {
		renderWith( {
			jmc_business_address: '1 High Street',
			jmc_business_phone: '01234 567890',
			jmc_business_whatsapp: '+44 7700 900123',
			jmc_business_email: 'hello@example.com',
			jmc_business_service_area: [ 'Leeds' ],
		} );

		const lineIcon = line( name )?.firstElementChild;

		expect( lineIcon?.className ).toBe( 'jmc-footer-business__icon' );
		expect( lineIcon?.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( lineIcon?.innerHTML ).toBe(
			`<svg data-icon="${ icon }"></svg>`
		);
	} );

	it( 'keeps an empty icon box until the icons load', () => {
		vi.mocked( useThemeIcons ).mockReturnValue( {} );

		renderWith( { jmc_business_phone: '01234 567890' } );

		expect( line( 'phone' )?.firstElementChild?.innerHTML ).toBe( '' );
	} );

	it( 'puts each address line on its own line, dropping blank lines', () => {
		renderWith( {
			jmc_business_address: ' 1 High Street \n\n  Leeds\n ',
		} );

		const address = line( 'address' )?.querySelector( 'span:last-child' );

		expect( address?.innerHTML ).toBe( '1 High Street<br>Leeds' );
	} );

	it.each( [
		[ [ 'Leeds' ], 'Leeds' ],
		[ [ 'Pudsey', 'Leeds' ], 'Pudsey and Leeds' ],
		[ [ 'Pudsey', 'Leeds', 'Bradford' ], 'Pudsey, Leeds and Bradford' ],
		[ [ ' Pudsey ', '', 7, 'Leeds' ], 'Pudsey and Leeds' ],
	] )( 'reads the service area %j as “%s”', ( places, phrase ) => {
		renderWith( { jmc_business_service_area: places } );

		expect( line( 'service-area' )?.textContent ).toBe(
			`Areas covered: ${ phrase }`
		);
	} );

	it( 'shows only the name when there are no contact details', () => {
		renderWith( { jmc_business_name: 'Sparks Ltd' } );

		expect( block()?.querySelector( 'address' ) ).toBeNull();
		expect( line( 'service-area' ) ).toBeNull();
		expect( line( 'empty' ) ).toBeNull();
	} );

	it( 'shows the service area without contact details', () => {
		renderWith( { jmc_business_service_area: [ 'Leeds' ] } );

		expect( block()?.querySelector( 'address' ) ).toBeNull();
		expect( line( 'service-area' ) ).toBeTruthy();
		expect( line( 'empty' ) ).toBeNull();
	} );

	it.each( [
		[ 'there are no saved details', {} ],
		[ 'the settings cannot be read', undefined ],
		[
			'every detail is blank or malformed',
			{
				jmc_business_name: '   ',
				jmc_business_address: 42,
				jmc_business_phone: [ '01234' ],
				jmc_business_whatsapp: null,
				jmc_business_email: '',
				jmc_business_service_area: 'Leeds',
			},
		],
	] )( 'asks for details when %s', ( _label, record ) => {
		renderWith( record );

		expect( line( 'empty' )?.textContent ).toContain(
			'under Business details in the block settings'
		);
		expect( block()?.querySelectorAll( 'p' ) ).toHaveLength( 1 );
	} );

	it( 'keeps links from leaving the editor', () => {
		renderWith( {
			jmc_business_name: 'Sparks Ltd',
			jmc_business_phone: '01234 567890',
		} );

		for ( const link of screen.getAllByRole( 'link' ) ) {
			const event = new MouseEvent( 'click', {
				bubbles: true,
				cancelable: true,
			} );

			fireEvent( link, event );

			expect( event.defaultPrevented ).toBe( true );
		}
	} );
} );
