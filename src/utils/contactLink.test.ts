import { describe, expect, it } from 'vitest';

import toContactUrl from './contactLink';

describe( 'toContactUrl', () => {
	it.each( [
		[ '01234 567890', 'tel:01234567890' ],
		[ 'https://01234 567890', 'tel:01234567890' ],
		[ 'https://+44 (0)1234 567-890', 'tel:+4401234567890' ],
		[ 'http://01234.567.890', 'tel:01234567890' ],
		[ 'hello@example.com', 'mailto:hello@example.com' ],
		[ 'https://hello@example.com', 'mailto:hello@example.com' ],
	] )( 'converts %s to %s', ( input, expected ) => {
		expect( toContactUrl( input ) ).toBe( expected );
	} );

	it.each( [
		'',
		'https://example.com/contact/',
		'https://user@example.com/path',
		'https://example.com:8080',
		'/contact/',
		'tel:01234567890',
		'mailto:hello@example.com',
		'https://12',
		'https://1234567890123456',
		'https://0800-ELECTRIC',
	] )( 'leaves %p unchanged', ( input ) => {
		expect( toContactUrl( input ) ).toBe( input );
	} );
} );
