import { clearPhpLog, PHP_LOG_PATH, readPhpLog } from '../helpers/php-log';
import { runWpCli } from '../helpers/wp-cli';

const MARKER = 'jmc-e2e: PHP debug log check';

/**
 * Confirms WordPress writes PHP errors where the suite reads them, then
 * empties the log so the run starts clean.
 *
 * Without this, a missing or misconfigured log would let every PHP check pass
 * silently.
 */
export default async function globalSetup(): Promise< void > {
	clearPhpLog();

	await runWpCli( [ 'eval', `error_log( ${ JSON.stringify( MARKER ) } );` ] );

	if ( ! readPhpLog().includes( MARKER ) ) {
		throw new Error(
			[
				`WordPress did not write to the PHP debug log at ${ PHP_LOG_PATH }.`,
				'Check that .wp-env.json sets WP_DEBUG_LOG and maps wp-content/jmc-e2e-logs to artifacts/e2e/php,',
				'then restart the environment with `npm run env:start`.',
			].join( '\n' )
		);
	}

	clearPhpLog();
}
