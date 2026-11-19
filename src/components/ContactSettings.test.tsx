import { useEntityProp } from '@wordpress/core-data';
import {
	cleanup,
	fireEvent,
	render,
	screen,
	within,
} from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ContactSettings from './ContactSettings';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { useEntityProp: vi.fn() } ) );

// Saved settings by option name; a missing option can't be read.
let settings: Record< string, unknown >;
const setters: Record< string, ReturnType< typeof vi.fn > > = {};

const setterFor = ( option: string ) => {
	setters[ option ] ??= vi.fn();
	return setters[ option ];
};

const open = ( props: { showNameAndAddress?: boolean } = {} ) => {
	render( <ContactSettings { ...props } /> );
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Business details' } )
	);
};

const day = ( name: string ) => within( screen.getByRole( 'group', { name } ) );

const changeTime = ( dayName: string, field: string, value: string ) =>
	fireEvent.change( day( dayName ).getByLabelText( field ), {
		target: { value },
	} );

describe( 'ContactSettings', () => {
	beforeEach( () => {
		settings = {
			jmc_business_name: 'Sparks Ltd',
			jmc_business_address: '1 High Street\nLeeds',
			jmc_business_phone: '01234 567890',
			jmc_business_whatsapp: '+44 7700 900123',
			jmc_business_email: 'hello@example.com',
			jmc_business_hours: {
				monday: { opens: '08:00', closes: '17:00' },
			},
			jmc_business_service_area: [ 'Leeds', 'Pudsey' ],
		};

		vi.mocked( useEntityProp ).mockImplementation(
			( _kind, _name, option ) =>
				[
					settings[ option as string ],
					setterFor( option as string ),
					undefined,
				] as ReturnType< typeof useEntityProp >
		);
	} );

	afterEach( () => {
		cleanup();
		Object.values( setters ).forEach( ( setter ) => setter.mockReset() );
	} );

	it( 'starts closed', () => {
		render( <ContactSettings /> );

		expect(
			screen
				.getByRole( 'button', { name: 'Business details' } )
				.getAttribute( 'aria-expanded' )
		).toBe( 'false' );
		expect( screen.queryByLabelText( 'Business phone number' ) ).toBeNull();
	} );

	it.each( [
		[
			'Business phone number',
			'jmc_business_phone',
			'tel',
			'01234 567890',
		],
		[
			'WhatsApp number',
			'jmc_business_whatsapp',
			'tel',
			'+44 7700 900123',
		],
		[
			'Business email address',
			'jmc_business_email',
			'email',
			'hello@example.com',
		],
	] )( 'edits the %s', ( label, option, type, value ) => {
		open();

		const field = screen.getByLabelText( label );

		expect( field.getAttribute( 'type' ) ).toBe( type );
		expect( field ).toHaveProperty( 'value', value );

		fireEvent.change( field, { target: { value: 'new' } } );

		expect( setters[ option ] ).toHaveBeenCalledWith( 'new' );
	} );

	it( 'leaves out the name and address by default', () => {
		open();

		expect( screen.queryByLabelText( 'Business name' ) ).toBeNull();
		expect( screen.queryByLabelText( 'Business address' ) ).toBeNull();
	} );

	it( 'edits the name and address for blocks that show them', () => {
		open( { showNameAndAddress: true } );

		const name = screen.getByLabelText( 'Business name' );
		const address = screen.getByLabelText( 'Business address' );

		expect( name ).toHaveProperty( 'value', 'Sparks Ltd' );
		expect( address.tagName ).toBe( 'TEXTAREA' );
		expect( address ).toHaveProperty( 'value', '1 High Street\nLeeds' );

		fireEvent.change( name, { target: { value: 'Sparks' } } );
		fireEvent.change( address, { target: { value: '2 Low Road' } } );

		expect( setters.jmc_business_name ).toHaveBeenCalledWith( 'Sparks' );
		expect( setters.jmc_business_address ).toHaveBeenCalledWith(
			'2 Low Road'
		);
	} );

	it( 'shows empty fields for empty settings', () => {
		settings.jmc_business_phone = null;

		open();

		expect(
			screen.getByLabelText( 'Business phone number' )
		).toHaveProperty( 'value', '' );
	} );

	it( 'explains settings the user cannot read', () => {
		settings = {};

		open( { showNameAndAddress: true } );

		expect( screen.queryByLabelText( 'Business phone number' ) ).toBeNull();
		expect(
			screen.getAllByText(
				'These details come from Settings → Business. Ask an administrator to change them.',
				{ selector: '.components-notice__content' }
			)
		).toHaveLength( 7 );
	} );

	describe( 'opening hours', () => {
		it( 'shows each day’s saved times', () => {
			open();

			expect( day( 'Monday' ).getByLabelText( 'Opens' ) ).toHaveProperty(
				'value',
				'08:00'
			);
			expect( day( 'Monday' ).getByLabelText( 'Closes' ) ).toHaveProperty(
				'value',
				'17:00'
			);
			expect( day( 'Sunday' ).getByLabelText( 'Opens' ) ).toHaveProperty(
				'value',
				''
			);
			expect(
				within( screen.getByRole( 'group', { name: 'Opening hours' } ) )
					.getAllByRole( 'group' )
					.map( ( group ) => group.getAttribute( 'aria-label' ) )
			).toEqual( [
				'Monday',
				'Tuesday',
				'Wednesday',
				'Thursday',
				'Friday',
				'Saturday',
				'Sunday',
			] );
		} );

		it( 'adds a time to a closed day', () => {
			open();

			changeTime( 'Tuesday', 'Opens', '09:00' );

			expect( setters.jmc_business_hours ).toHaveBeenCalledWith( {
				monday: { opens: '08:00', closes: '17:00' },
				tuesday: { opens: '09:00' },
			} );
		} );

		it( 'removes a cleared time', () => {
			open();

			changeTime( 'Monday', 'Closes', '' );

			expect( setters.jmc_business_hours ).toHaveBeenCalledWith( {
				monday: { opens: '08:00' },
			} );
		} );

		it( 'removes a day once both times are cleared', () => {
			settings.jmc_business_hours = {
				monday: { opens: '08:00' },
				friday: { opens: '08:00', closes: '12:00' },
			};

			open();

			changeTime( 'Monday', 'Opens', '' );

			expect( setters.jmc_business_hours ).toHaveBeenCalledWith( {
				friday: { opens: '08:00', closes: '12:00' },
			} );
		} );

		it.each( [
			[ 'an empty list', [] ],
			[ 'missing', null ],
			[ 'a string', 'closed' ],
		] )( 'treats saved hours that are %s as closed', ( _label, value ) => {
			settings.jmc_business_hours = value;

			open();

			expect( day( 'Monday' ).getByLabelText( 'Opens' ) ).toHaveProperty(
				'value',
				''
			);

			changeTime( 'Monday', 'Opens', '07:30' );

			expect( setters.jmc_business_hours ).toHaveBeenCalledWith( {
				monday: { opens: '07:30' },
			} );
		} );

		it.each( [
			[
				'only an opening time',
				{ opens: '08:00' },
				'Add both times, or clear both if you are closed. Saved as closed until then.',
			],
			[
				'only a closing time',
				{ closes: '17:00' },
				'Add both times, or clear both if you are closed. Saved as closed until then.',
			],
			[
				'a closing time before the opening time',
				{ opens: '17:00', closes: '08:00' },
				'Closing time must be after opening time. Saved as closed until then.',
			],
			[
				'equal times',
				{ opens: '08:00', closes: '08:00' },
				'Closing time must be after opening time. Saved as closed until then.',
			],
		] )( 'warns about %s', ( _label, times, message ) => {
			settings.jmc_business_hours = { wednesday: times };

			open();

			expect( day( 'Wednesday' ).getByText( message ) ).toBeTruthy();
		} );

		it( 'shows no warning for valid or empty days', () => {
			open();

			expect(
				screen.queryByText( /Saved as closed until then/ )
			).toBeNull();
		} );
	} );

	describe( 'service area', () => {
		it( 'shows one place per line', () => {
			open();

			expect( screen.getByLabelText( 'Service area' ) ).toHaveProperty(
				'value',
				'Leeds\nPudsey'
			);
		} );

		it( 'shows nothing for a malformed setting', () => {
			settings.jmc_business_service_area = 'Leeds';

			open();

			expect( screen.getByLabelText( 'Service area' ) ).toHaveProperty(
				'value',
				''
			);
		} );

		it( 'saves each line as a place, keeping blank lines while typing', () => {
			open();

			fireEvent.change( screen.getByLabelText( 'Service area' ), {
				target: { value: 'Leeds\n\nBradford' },
			} );

			expect( setters.jmc_business_service_area ).toHaveBeenCalledWith( [
				'Leeds',
				'',
				'Bradford',
			] );
		} );
	} );
} );
