import { findPhpErrors, formatPhpErrors, readPhpLog } from '../helpers/php-log';

/**
 * Fails the run when PHP logged an error at any point, including in
 * `beforeAll` and `afterAll` hooks and in tests that passed on a retry, which
 * the per-test check does not cover.
 */
export default async function globalTeardown(): Promise< void > {
	const errors = findPhpErrors( readPhpLog() );

	if ( errors.length ) {
		throw new Error( formatPhpErrors( errors, 'during this run' ) );
	}
}
