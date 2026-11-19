import { useEntityRecord } from '@wordpress/core-data';
import {
	cleanup,
	fireEvent,
	render,
	screen,
	within,
} from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import Edit from './edit';

vi.mock(
	'@wordpress/block-editor',
	() => import( '../../../../tests/unit/mocks/block-editor' )
);
vi.mock( '@wordpress/core-data', () => ( { useEntityRecord: vi.fn() } ) );

type Attributes = Parameters< typeof Edit >[ 0 ][ 'attributes' ];

const logo = {
	source_url: 'https://example.com/niceic.png',
	alt_text: ' NICEIC approved contractor ',
	media_details: {
		sizes: { medium: { source_url: 'https://example.com/niceic-300.png' } },
	},
};

const renderEdit = (
	attributes: Partial< Attributes > = {},
	record: unknown = undefined
) => {
	const setAttributes = vi.fn();

	vi.mocked( useEntityRecord ).mockReturnValue( {
		record,
	} as ReturnType< typeof useEntityRecord > );

	render(
		<Edit
			attributes={ {
				imageId: 0,
				link: { url: '', opensInNewTab: false },
				...attributes,
			} }
			setAttributes={ setAttributes }
		/>
	);

	return setAttributes;
};

const tile = () => document.querySelector( 'li.jmc-credential' );

const settings = () =>
	within( screen.getByRole( 'region', { name: 'Settings' } ) );

const altWarning = /This logo has no alternative text/;

describe( 'Credential edit', () => {
	afterEach( () => {
		cleanup();
		vi.mocked( useEntityRecord ).mockReset();
	} );

	describe( 'without a logo', () => {
		it( 'skips loading an attachment', () => {
			renderEdit();

			expect( useEntityRecord ).toHaveBeenCalledWith(
				'root',
				'media',
				0,
				{
					enabled: false,
				}
			);
		} );

		it( 'shows an Add logo tile that picks an image', () => {
			const setAttributes = renderEdit();
			const add = within( tile() as HTMLElement ).getByRole( 'button', {
				name: 'Add logo',
			} );

			expect(
				add
					.closest( '[data-allowed-types]' )
					?.getAttribute( 'data-allowed-types' )
			).toBe( 'image' );

			fireEvent.click( add );

			expect( setAttributes ).toHaveBeenCalledWith( { imageId: 101 } );
		} );

		it( 'offers Select image without Remove or a warning', () => {
			renderEdit();

			expect(
				settings().getByRole( 'button', { name: 'Select image' } )
			).toBeTruthy();
			expect(
				settings().queryByRole( 'button', { name: 'Remove image' } )
			).toBeNull();
			expect( settings().queryByText( altWarning ) ).toBeNull();
		} );
	} );

	describe( 'with a logo', () => {
		it( 'loads the attachment', () => {
			renderEdit( { imageId: 12 }, logo );

			expect( useEntityRecord ).toHaveBeenCalledWith(
				'root',
				'media',
				12,
				{ enabled: true }
			);
		} );

		it( 'shows the medium size with trimmed alternative text', () => {
			renderEdit( { imageId: 12 }, logo );

			const image = within( tile() as HTMLElement ).getByRole( 'img' );

			expect( image.getAttribute( 'src' ) ).toBe(
				'https://example.com/niceic-300.png'
			);
			expect( image.getAttribute( 'alt' ) ).toBe(
				'NICEIC approved contractor'
			);
			expect( image.className ).toBe( 'jmc-credential__image' );
			expect(
				screen.queryByRole( 'button', { name: 'Add logo' } )
			).toBeNull();
		} );

		it( 'falls back to the full size without a medium size', () => {
			renderEdit(
				{ imageId: 12 },
				{
					source_url: 'https://example.com/niceic.png',
					alt_text: 'NICEIC',
				}
			);

			expect( screen.getByRole( 'img' ).getAttribute( 'src' ) ).toBe(
				'https://example.com/niceic.png'
			);
		} );

		it( 'shows the Add logo tile while the attachment loads', () => {
			renderEdit( { imageId: 12 } );

			expect(
				screen.getByRole( 'button', { name: 'Add logo' } )
			).toBeTruthy();
		} );

		it( 'replaces and removes the image', () => {
			const setAttributes = renderEdit( { imageId: 12 }, logo );

			fireEvent.click(
				settings().getByRole( 'button', { name: 'Replace image' } )
			);
			fireEvent.click(
				settings().getByRole( 'button', { name: 'Remove image' } )
			);

			expect( setAttributes ).toHaveBeenNthCalledWith( 1, {
				imageId: 101,
			} );
			expect( setAttributes ).toHaveBeenNthCalledWith( 2, {
				imageId: 0,
			} );
		} );

		it.each( [
			[ 'missing', undefined ],
			[ 'blank', '   ' ],
		] )( 'warns when the alternative text is %s', ( _label, alt ) => {
			renderEdit( { imageId: 12 }, { ...logo, alt_text: alt } );

			expect( settings().getByText( altWarning ) ).toBeTruthy();
			expect(
				tile()
					?.querySelector( '.jmc-credential__image' )
					?.getAttribute( 'alt' )
			).toBe( '' );
		} );

		it( 'warns while the attachment loads, before its text is known', () => {
			renderEdit( { imageId: 12 } );

			expect( settings().getByText( altWarning ) ).toBeTruthy();
		} );
	} );

	describe( 'link', () => {
		it( 'shows the logo unlinked without a link', () => {
			renderEdit( { imageId: 12 }, logo );

			expect( tile()?.querySelector( 'a' ) ).toBeNull();
		} );

		it( 'wraps the logo in its link, without navigating', () => {
			renderEdit(
				{
					imageId: 12,
					link: { url: 'https://niceic.com', opensInNewTab: true },
				},
				logo
			);

			const link = within( tile() as HTMLElement ).getByRole( 'link', {
				name: 'NICEIC approved contractor',
			} );
			const click = new MouseEvent( 'click', {
				bubbles: true,
				cancelable: true,
			} );

			expect( link.getAttribute( 'href' ) ).toBe( 'https://niceic.com' );
			expect( link.className ).toBe( 'jmc-credential__link' );

			fireEvent( link, click );

			expect( click.defaultPrevented ).toBe( true );
		} );

		it( 'sets and removes the link from the toolbar', () => {
			const setAttributes = renderEdit(
				{
					imageId: 12,
					link: { url: 'https://niceic.com', opensInNewTab: false },
				},
				logo
			);

			fireEvent.click(
				screen.getByRole( 'button', { name: 'Edit link' } )
			);
			fireEvent.change( screen.getByRole( 'textbox', { name: 'URL' } ), {
				target: { value: 'https://napit.org.uk' },
			} );
			fireEvent.click(
				screen.getByRole( 'button', { name: 'Remove link' } )
			);

			expect( setAttributes ).toHaveBeenNthCalledWith( 1, {
				link: { url: 'https://napit.org.uk', opensInNewTab: false },
			} );
			expect( setAttributes ).toHaveBeenNthCalledWith( 2, {
				link: { url: '', opensInNewTab: false },
			} );
		} );
	} );
} );
