import {
	existsSync,
	mkdirSync,
	readFileSync,
	statSync,
	writeFileSync,
} from 'node:fs';
import path from 'node:path';

import { allowedPhpErrors, isAllowedError } from '../fixtures/allowed-errors';

/**
 * The PHP debug log, as seen from the host.
 *
 * `.wp-env.json` maps `wp-content/jmc-e2e-logs` to `artifacts/e2e/php` and
 * points `WP_DEBUG_LOG` at `debug.log` inside it, so the WordPress and CLI
 * containers write here and the tests read it without a `wp-env run` call.
 */
export const PHP_LOG_PATH = path.resolve(
	__dirname,
	'../../../artifacts/e2e/php/debug.log'
);

/**
 * Log entries that fail the run: PHP errors, warnings, notices and
 * deprecations (including `_doing_it_wrong()` and `trigger_error()` output),
 * and database errors reported by `wpdb`.
 */
const FAILING_ENTRY =
	/^\[[^\]]+\] (?:PHP (?:Fatal error|Parse error|Recoverable fatal error|Warning|Notice|Deprecated)|WordPress database error)/;

/**
 * Current size of the PHP log in bytes, or 0 when nothing has been logged.
 */
export function getPhpLogSize(): number {
	return existsSync( PHP_LOG_PATH ) ? statSync( PHP_LOG_PATH ).size : 0;
}

/**
 * Empties the PHP log, creating it when missing.
 */
export function clearPhpLog(): void {
	mkdirSync( path.dirname( PHP_LOG_PATH ), { recursive: true } );
	writeFileSync( PHP_LOG_PATH, '' );
}

/**
 * Reads the PHP log from a byte offset.
 *
 * @param offset Byte offset to start from; a log that has since been
 *               truncated is read from the start.
 */
export function readPhpLog( offset = 0 ): string {
	if ( ! existsSync( PHP_LOG_PATH ) ) {
		return '';
	}

	const log = readFileSync( PHP_LOG_PATH );

	return log.subarray( offset > log.length ? 0 : offset ).toString( 'utf8' );
}

/**
 * Splits log text into entries. Each entry starts with a `[timestamp]` line;
 * following lines (such as a fatal error's stack trace) belong to it.
 *
 * @param log Log text.
 */
function splitEntries( log: string ): string[] {
	return log
		.split( /\n(?=\[[^\]]+\] )/ )
		.map( ( entry ) => entry.trimEnd() )
		.filter( Boolean );
}

/**
 * PHP errors in log text that are not allow-listed.
 *
 * @param log Log text.
 */
export function findPhpErrors( log: string ): string[] {
	return splitEntries( log ).filter(
		( entry ) =>
			FAILING_ENTRY.test( entry ) &&
			! isAllowedError( entry, allowedPhpErrors )
	);
}

/**
 * Formats PHP errors for a failure message.
 *
 * @param errors  Log entries.
 * @param context Where they were raised, such as "during this test".
 */
export function formatPhpErrors( errors: string[], context: string ): string {
	return [
		`${ errors.length } PHP error(s) logged ${ context }:`,
		...errors.map( ( entry ) => `  ${ entry.replace( /\n/g, '\n    ' ) }` ),
		`Full log: ${ PHP_LOG_PATH }`,
		'Fix the cause, or allow-list it with a reason in tests/e2e/fixtures/allowed-errors.ts.',
	].join( '\n' );
}
