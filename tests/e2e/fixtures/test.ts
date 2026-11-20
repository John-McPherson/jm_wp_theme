import type {
	BrowserContext,
	ConsoleMessage,
	WebError,
} from '@playwright/test';
import { expect, test as base } from '@wordpress/e2e-test-utils-playwright';

import {
	findPhpErrors,
	formatPhpErrors,
	getPhpLogSize,
	readPhpLog,
} from '../helpers/php-log';
import {
	allowedBrowserErrors as globallyAllowedBrowserErrors,
	isAllowedError,
	type AllowedError,
} from './allowed-errors';

type BrowserError = {
	kind: 'console.error' | 'uncaught error';
	text: string;
	pageUrl: string;
	source?: string;
};

type ErrorFixtures = {
	/**
	 * Browser errors this test expects, on top of the shared allow-list in
	 * `allowed-errors.ts`. Set with `test.use( { allowedBrowserErrors } )`.
	 */
	allowedBrowserErrors: AllowedError[];
	failOnBrowserErrors: void;
	failOnPhpErrors: void;
};

/**
 * Describes a console message.
 *
 * @param message Console message.
 */
function fromConsoleMessage( message: ConsoleMessage ): BrowserError {
	const { url, lineNumber, columnNumber } = message.location();

	return {
		kind: 'console.error',
		text: message.text(),
		pageUrl: message.page()?.url() ?? 'unknown page',
		source: url ? `${ url }:${ lineNumber }:${ columnNumber }` : undefined,
	};
}

/**
 * Describes an uncaught exception or unhandled rejection.
 *
 * @param webError Error raised in a page.
 */
function fromWebError( webError: WebError ): BrowserError {
	const error = webError.error();

	return {
		kind: 'uncaught error',
		text: error.stack || `${ error.name }: ${ error.message }`,
		pageUrl: webError.page()?.url() ?? 'unknown page',
	};
}

/**
 * Formats browser errors for a failure message.
 *
 * @param errors Browser errors.
 */
function formatBrowserErrors( errors: BrowserError[] ): string {
	return [
		`${ errors.length } browser error(s) during this test:`,
		...errors.map( ( error, index ) =>
			[
				`  ${ index + 1 }. ${ error.kind } on ${ error.pageUrl }`,
				error.source ? `     at ${ error.source }` : '',
				`     ${ error.text.replace( /\n/g, '\n     ' ) }`,
			]
				.filter( Boolean )
				.join( '\n' )
		),
		'Fix the cause, or allow it with a reason: for one test with test.use( { allowedBrowserErrors } ), or for every test in tests/e2e/fixtures/allowed-errors.ts.',
	].join( '\n' );
}

/**
 * Collects errors from every page in a browser context.
 *
 * @param context Browser context to watch.
 * @param onError Called with each error.
 */
function watchBrowserErrors(
	context: BrowserContext,
	onError: ( error: BrowserError ) => void
): void {
	context.on( 'console', ( message ) => {
		if ( 'error' === message.type() ) {
			onError( fromConsoleMessage( message ) );
		}
	} );
	context.on( 'weberror', ( webError ) =>
		onError( fromWebError( webError ) )
	);
}

/**
 * The theme's Playwright `test`. Import `test` and `expect` from this file in
 * every spec: these automatic fixtures then fail a test when its pages log a
 * console error or throw an uncaught error, or when PHP logs an error,
 * warning, notice or deprecation while it runs. Pages in contexts a test
 * creates itself with `browser.newContext()` are not watched.
 */
export const test = base.extend< ErrorFixtures >( {
	allowedBrowserErrors: [ [], { option: true } ],

	failOnBrowserErrors: [
		async ( { context, allowedBrowserErrors }, use, testInfo ) => {
			const allowed = [
				...globallyAllowedBrowserErrors,
				...allowedBrowserErrors,
			];
			const errors: BrowserError[] = [];

			watchBrowserErrors( context, ( error ) => {
				if ( ! isAllowedError( error.text, allowed ) ) {
					errors.push( error );
				}
			} );

			await use();

			if ( errors.length ) {
				await testInfo.attach( 'browser-errors.json', {
					body: JSON.stringify( errors, null, 2 ),
					contentType: 'application/json',
				} );

				throw new Error( formatBrowserErrors( errors ) );
			}
		},
		{ auto: true },
	],

	failOnPhpErrors: [
		async ( {}, use, testInfo ) => {
			const offset = getPhpLogSize();

			await use();

			const errors = findPhpErrors( readPhpLog( offset ) );

			if ( errors.length ) {
				await testInfo.attach( 'php-errors.log', {
					body: errors.join( '\n' ),
					contentType: 'text/plain',
				} );

				throw new Error(
					formatPhpErrors( errors, 'during this test' )
				);
			}
		},
		{ auto: true },
	],
} );

export { expect };
