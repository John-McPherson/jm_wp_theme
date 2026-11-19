import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { ComponentType } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { store: 'core' } ) );

const site = vi.hoisted( () => ( {
	record: undefined as Record< string, unknown > | undefined,
} ) );

vi.mock( '@wordpress/data', () => ( {
	useSelect: ( callback: ( select: unknown ) => unknown ) =>
		callback( () => ( {
			getEntityRecord: ( kind: string, name: string ) =>
				'root' === kind && 'site' === name ? site.record : undefined,
		} ) ),
} ) );

// Each preview mount gets a new number, so remounts can be told apart.
const preview = vi.hoisted( () => ( { mounts: 0 } ) );

vi.mock( '@wordpress/server-side-render', async () => {
	const { useState } = await import( 'react' );

	function ServerSideRender( {
		EmptyResponsePlaceholder,
		...props
	}: {
		EmptyResponsePlaceholder: ComponentType;
	} ) {
		const [ mount ] = useState( () => ++preview.mounts );

		return (
			<div
				data-testid="preview"
				data-mount={ mount }
				data-props={ JSON.stringify( props ) }
			>
				<EmptyResponsePlaceholder />
			</div>
		);
	}

	return { default: ServerSideRender };
} );
vi.mock( '../../../components/ContactSettings', () => ( {
	default: ( { showNameAndAddress }: { showNameAndAddress?: boolean } ) => (
		<div
			data-testid="contact-settings"
			data-show-name-and-address={ String( showNameAndAddress ) }
		/>
	),
} ) );

type Attributes = Parameters< typeof Edit >[ 0 ][ 'attributes' ];

const defaults: Attributes = {
	showPhone: true,
	showWhatsApp: false,
	showEmail: true,
	showHours: false,
	showServiceArea: true,
};

const element = (
	attributes: Partial< Attributes > = {},
	setAttributes = vi.fn()
) => (
	<Edit
		attributes={ { ...defaults, ...attributes } }
		setAttributes={ setAttributes }
	/>
);

const toggle = ( name: string ) =>
	screen.getByRole( 'checkbox', { name } ) as HTMLInputElement;

const previewElement = () => screen.getByTestId( 'preview' );

describe( 'Contact details edit', () => {
	beforeEach( () => {
		site.record = {
			jmc_business_phone: '01234 567890',
			jmc_business_email: 'hello@example.com',
		};
	} );

	afterEach( cleanup );

	it.each( [
		[ 'Phone', 'showPhone', true ],
		[ 'WhatsApp', 'showWhatsApp', false ],
		[ 'Email', 'showEmail', true ],
		[ 'Opening hours', 'showHours', false ],
		[ 'Areas covered', 'showServiceArea', true ],
	] )( 'switches the %s field', ( label, key, checked ) => {
		const setAttributes = vi.fn();

		render( element( {}, setAttributes ) );

		expect( toggle( label ).checked ).toBe( checked );

		fireEvent.click( toggle( label ) );

		expect( setAttributes ).toHaveBeenCalledWith( { [ key ]: ! checked } );
	} );

	it( 'offers the shared business details without name and address', () => {
		render( element() );

		expect(
			screen.getByTestId( 'contact-settings' ).dataset.showNameAndAddress
		).toBe( 'undefined' );
	} );

	it( 'previews the block on the server, with links disabled', () => {
		render( element( { showHours: true } ) );

		expect( JSON.parse( previewElement().dataset.props ?? '{}' ) ).toEqual(
			{
				block: 'jmc/contact-details',
				attributes: { ...defaults, showHours: true },
			}
		);
		expect( previewElement().closest( '[inert]' ) ).toBeTruthy();
	} );

	it( 'explains an empty preview', () => {
		render( element() );

		expect(
			previewElement().querySelector( '.jmc-contact-details__empty' )
				?.textContent
		).toBe(
			'No contact details to show. Add them in Settings → Business, or switch on a field in the block settings.'
		);
	} );

	it( 'refreshes the preview once changed details are saved', () => {
		const { rerender } = render( element() );
		const first = previewElement().dataset.mount;

		site.record = { ...site.record, jmc_business_phone: '07700 900123' };
		rerender( element() );

		expect( previewElement().dataset.mount ).not.toBe( first );
	} );

	it( 'keeps the preview when other settings change', () => {
		const { rerender } = render( element() );
		const first = previewElement().dataset.mount;

		site.record = { ...site.record, title: 'Sparks Ltd' };
		rerender( element() );

		expect( previewElement().dataset.mount ).toBe( first );
	} );

	it( 'previews before the settings can be read', () => {
		site.record = undefined;

		render( element() );

		expect( previewElement() ).toBeTruthy();
	} );
} );
