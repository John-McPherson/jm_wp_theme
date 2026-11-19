import Ajv from 'ajv';
import { describe, expect, it } from 'vitest';

// A copy of https://schemas.wp.org/trunk/block.json, kept in the repo so the
// check runs offline. Refresh it with `npm run schema:update`.
import schema from '../../tests/unit/schemas/block.json';

const metadata = import.meta.glob< { default: Record< string, unknown > } >(
	'./*/*/block.json',
	{ eager: true }
);

const validate = new Ajv( { allErrors: true, strict: false } ).compile(
	schema
);

describe( 'block.json schema', () => {
	it.each( Object.keys( metadata ).sort() )( '%s is valid', ( path ) => {
		const block = metadata[ path ].default;

		validate( block );

		expect( validate.errors ?? [] ).toEqual( [] );
	} );

	it( 'points each file at the WordPress block schema', () => {
		Object.values( metadata ).forEach( ( { default: block } ) =>
			expect( block.$schema ).toBe(
				'https://schemas.wp.org/trunk/block.json'
			)
		);
	} );
} );
