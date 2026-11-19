import { describe, expect, it, vi } from 'vitest';

import bindFields from './bindFields';

describe( 'bindFields', () => {
	const attributes = {
		heading: 'Hello',
		palette: 'secondary',
		imageId: 12,
		link: { url: 'https://example.com', opensInNewTab: true },
	};

	it.each( [ 'text', 'select' ] as const )(
		'binds a %s field to its attribute',
		( type ) => {
			const setAttributes = vi.fn();
			const key = type === 'text' ? 'heading' : 'palette';
			const field = bindFields( attributes, setAttributes )[ type ](
				key
			);

			expect( field.value ).toBe( attributes[ key ] );

			field.set( 'Updated' );

			expect( setAttributes ).toHaveBeenCalledWith( {
				[ key ]: 'Updated',
			} );
		}
	);

	it( 'reads an attribute that has not been set as undefined', () => {
		const field = bindFields( {}, vi.fn() ).text( 'heading' );

		expect( field.value ).toBeUndefined();
	} );

	it( 'stores the media ID and URL when both keys are given', () => {
		const setAttributes = vi.fn();
		const field = bindFields( attributes, setAttributes ).media(
			'imageId',
			'imageUrl'
		);

		expect( field.value ).toBe( 12 );

		field.set( { id: 34, url: 'https://example.com/image.jpg' } );

		expect( setAttributes ).toHaveBeenCalledWith( {
			imageId: 34,
			imageUrl: 'https://example.com/image.jpg',
		} );
	} );

	it( 'stores only the media ID without a URL key', () => {
		const setAttributes = vi.fn();

		bindFields( attributes, setAttributes )
			.media( 'imageId' )
			.set( { id: 34, url: 'https://example.com/image.jpg' } );

		expect( setAttributes ).toHaveBeenCalledWith( { imageId: 34 } );
	} );

	it( 'sets and resets a link', () => {
		const setAttributes = vi.fn();
		const field = bindFields( attributes, setAttributes ).link( 'link' );
		const link = { url: '/contact', opensInNewTab: false };

		expect( field.value ).toBe( attributes.link );

		field.set( link );
		field.reset();

		expect( setAttributes ).toHaveBeenNthCalledWith( 1, { link } );
		expect( setAttributes ).toHaveBeenNthCalledWith( 2, {
			link: { url: '', opensInNewTab: false },
		} );
	} );
} );
