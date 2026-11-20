import { useEntityProp } from '@wordpress/core-data';
import {
	cleanup,
	fireEvent,
	render,
	screen,
	within,
} from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import useThemeIcons from '../utils/useThemeIcons';
import ButtonEditor, { type ButtonAttributes } from './ButtonEditor';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { useEntityProp: vi.fn() } ) );
vi.mock( '../utils/useThemeIcons', () => ( { default: vi.fn() } ) );

const defaults: ButtonAttributes = {
	text: '',
	buttonType: 'primary',
	linkType: 'link',
	link: { url: '', opensInNewTab: false },
	emailSubject: '',
	emailBody: '',
	whatsappMessage: '',
};

const setSetting = vi.fn();

const renderEditor = (
	attributes: Partial< ButtonAttributes > = {},
	className?: string
) => {
	const setAttributes = vi.fn();

	render(
		<ButtonEditor
			attributes={ { ...defaults, ...attributes } }
			setAttributes={ setAttributes }
			className={ className }
		/>
	);

	return setAttributes;
};

const settings = () => screen.getByRole( 'region', { name: 'Settings' } );

describe( 'ButtonEditor', () => {
	beforeEach( () => {
		vi.mocked( useEntityProp ).mockReturnValue( [
			'01234 567890',
			setSetting,
			undefined,
		] as unknown as ReturnType< typeof useEntityProp > );
		vi.mocked( useThemeIcons ).mockReturnValue( {
			'jmc/whatsapp': '<svg data-icon="whatsapp"></svg>',
		} );
	} );

	afterEach( () => {
		cleanup();
		setSetting.mockReset();
	} );

	describe( 'link buttons', () => {
		it( 'edits the label and button type', () => {
			const setAttributes = renderEditor( {
				text: 'Get a quote',
				buttonType: 'secondary',
			} );

			const label = screen.getByRole( 'textbox', {
				name: 'Enter text here',
			} );

			expect( label ).toHaveProperty( 'value', 'Get a quote' );
			expect( label.dataset.className ).toBe(
				'jmc-button--secondary jmc-button'
			);

			fireEvent.change( label, { target: { value: 'Book now' } } );
			fireEvent.change( screen.getByRole( 'combobox' ), {
				target: { value: 'primary' },
			} );

			expect( setAttributes ).toHaveBeenNthCalledWith( 1, {
				text: 'Book now',
			} );
			expect( setAttributes ).toHaveBeenNthCalledWith( 2, {
				buttonType: 'primary',
			} );
		} );

		it( 'keeps the block class names on a primary button', () => {
			renderEditor( {}, 'is-style-wide' );

			expect(
				screen.getByRole( 'textbox', { name: 'Enter text here' } )
					.dataset.className
			).toBe( 'is-style-wide jmc-button' );
		} );

		it( 'offers the link toolbar and no contact settings', () => {
			renderEditor();

			expect(
				screen.getByRole( 'button', { name: 'Edit link' } )
			).toBeTruthy();
			expect( screen.queryByText( 'Contact Details' ) ).toBeNull();
			expect( screen.queryByText( 'Pre-filled Message' ) ).toBeNull();
		} );

		it.each( [
			[ '01234 567890', 'tel:01234567890' ],
			[ 'hello@example.com', 'mailto:hello@example.com' ],
			[ 'https://example.com/contact', 'https://example.com/contact' ],
		] )( 'saves a destination of %s as %s', ( typed, saved ) => {
			const setAttributes = renderEditor();

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Edit link' } )
			);
			fireEvent.change( screen.getByRole( 'textbox', { name: 'URL' } ), {
				target: { value: typed },
			} );

			expect( setAttributes ).toHaveBeenCalledWith( {
				link: { url: saved, opensInNewTab: false },
			} );
		} );

		it( 'clears the link when it is removed', () => {
			const setAttributes = renderEditor( {
				link: { url: '/contact/', opensInNewTab: true },
			} );

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Edit link' } )
			);
			fireEvent.click(
				screen.getByRole( 'button', { name: 'Remove link' } )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				link: { url: '', opensInNewTab: false },
			} );
			expect(
				screen.queryByRole( 'textbox', { name: 'URL' } )
			).toBeNull();
		} );
	} );

	describe.each( [
		[ 'phone', 'Call us', 'jmc_business_phone', 'Business phone number' ],
		[ 'email', 'Email us', 'jmc_business_email', 'Business email address' ],
		[
			'whatsapp',
			'Message us on WhatsApp',
			'jmc_business_whatsapp',
			'WhatsApp number',
		],
	] )( '%s buttons', ( linkType, placeholder, option, settingLabel ) => {
		it( 'edits the label without a link toolbar', () => {
			const setAttributes = renderEditor( { linkType } );
			const label = screen.getByRole( 'textbox', { name: placeholder } );

			fireEvent.change( label, { target: { value: 'Ring us' } } );

			expect( setAttributes ).toHaveBeenCalledWith( { text: 'Ring us' } );
			expect(
				screen.queryByRole( 'button', { name: 'Edit link' } )
			).toBeNull();
		} );

		it( 'edits the shared business detail', () => {
			renderEditor( { linkType } );

			expect( useEntityProp ).toHaveBeenCalledWith(
				'root',
				'site',
				option
			);

			const field = screen.getByLabelText( settingLabel );

			expect( field ).toHaveProperty( 'value', '01234 567890' );

			fireEvent.change( field, { target: { value: '07700 900123' } } );

			expect( setSetting ).toHaveBeenCalledWith( '07700 900123' );
		} );

		it( 'explains the setting to users who cannot read it', () => {
			vi.mocked( useEntityProp ).mockReturnValue( [
				undefined,
				setSetting,
				undefined,
			] as unknown as ReturnType< typeof useEntityProp > );

			renderEditor( { linkType } );

			expect( screen.queryByLabelText( settingLabel ) ).toBeNull();
			expect(
				within( settings() ).getByText(
					/Ask an administrator to change them/
				)
			).toBeTruthy();
		} );
	} );

	it( 'shows the WhatsApp icon inside the button, before the label', () => {
		renderEditor( { linkType: 'whatsapp', buttonType: 'secondary' } );

		const label = screen.getByRole( 'textbox', {
			name: 'Message us on WhatsApp',
		} );
		const button = label.parentElement as HTMLElement;
		const icon = button.querySelector( '.jmc-button__icon' );

		expect( button.className ).toBe( 'jmc-button--secondary jmc-button' );
		expect( label.dataset.tagName ).toBe( 'span' );
		expect( label.dataset.className ).toBe( '' );
		expect( icon?.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		expect( icon?.innerHTML ).toBe( '<svg data-icon="whatsapp"></svg>' );
		expect( button.firstElementChild ).toBe( icon );
	} );

	it( 'keeps the WhatsApp button usable before the icons load', () => {
		vi.mocked( useThemeIcons ).mockReturnValue( {} );

		renderEditor( { linkType: 'whatsapp' } );

		expect( document.querySelector( '.jmc-button__icon' )?.innerHTML ).toBe(
			''
		);
	} );

	it.each( [ 'phone', 'email' ] )(
		'styles the %s label as the button, without an icon',
		( linkType ) => {
			renderEditor( { linkType } );

			const label = screen.getByRole( 'textbox', {
				name: linkType === 'phone' ? 'Call us' : 'Email us',
			} );

			expect( label.dataset.tagName ).toBe( 'p' );
			expect( label.dataset.className ).toBe( 'jmc-button' );
			expect( document.querySelector( '.jmc-button__icon' ) ).toBeNull();
		}
	);

	it( 'pre-fills an email subject and message', () => {
		const setAttributes = renderEditor( {
			linkType: 'email',
			emailSubject: 'Quote',
		} );

		expect( screen.getByText( 'Pre-filled Message' ) ).toBeTruthy();

		const subject = screen.getByLabelText( 'Subject' );

		expect( subject.tagName ).toBe( 'INPUT' );
		expect( subject ).toHaveProperty( 'value', 'Quote' );
		expect( screen.getByLabelText( 'Message' ).tagName ).toBe( 'TEXTAREA' );

		fireEvent.change( subject, { target: { value: 'Rewire' } } );
		fireEvent.change( screen.getByLabelText( 'Message' ), {
			target: { value: 'Hello' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			emailSubject: 'Rewire',
		} );
		expect( setAttributes ).toHaveBeenCalledWith( { emailBody: 'Hello' } );
	} );

	it( 'pre-fills a WhatsApp message', () => {
		const setAttributes = renderEditor( { linkType: 'whatsapp' } );

		expect( screen.queryByLabelText( 'Subject' ) ).toBeNull();

		fireEvent.change( screen.getByLabelText( 'Message' ), {
			target: { value: 'Hi' },
		} );

		expect( setAttributes ).toHaveBeenCalledWith( {
			whatsappMessage: 'Hi',
		} );
	} );

	it( 'offers no pre-filled message for phone buttons', () => {
		renderEditor( { linkType: 'phone' } );

		expect( screen.queryByText( 'Pre-filled Message' ) ).toBeNull();
		expect( settings().textContent ).toContain( 'Contact Details' );
	} );

	it( 'treats an unknown link type as a link button', () => {
		renderEditor( { linkType: 'fax' } );

		expect(
			screen.getByRole( 'button', { name: 'Edit link' } )
		).toBeTruthy();
		expect( screen.queryByText( 'Contact Details' ) ).toBeNull();
	} );
} );
