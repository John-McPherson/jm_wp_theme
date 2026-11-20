/**
 * Errors the E2E suite tolerates.
 *
 * Every browser console error, uncaught page error and PHP error, warning,
 * notice or deprecation fails the run unless it matches an entry here. Keep
 * each entry as narrow as possible and give the reason it cannot be fixed;
 * docs/testing.md lists them too, so update both together.
 */

export type AllowedError = {
	/** Matched against the message text (and, for PHP, the whole log entry). */
	pattern: RegExp;
	/** Why this message is expected and cannot be fixed in the theme. */
	reason: string;
};

/** The origin of the WordPress site under test, as in playwright.config.ts. */
const siteOrigin = new URL( process.env.WP_BASE_URL ?? 'http://localhost:8888' )
	.origin;

/**
 * Escapes text for use inside a regular expression.
 *
 * @param text Text to escape.
 */
const escapeRegExp = ( text: string ): string =>
	text.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );

/**
 * Browser console errors and uncaught page errors allowed in every test.
 *
 * A test that provokes an error on purpose should allow it for itself with
 * `test.use( { allowedBrowserErrors: [ … ] } )` instead of adding it here.
 */
export const allowedBrowserErrors: AllowedError[] = [
	{
		pattern: /^Prefetch request denied: URL must be secure \(HTTPS\)$/,
		reason: 'WebKit refuses the prefetches requested by WordPress core\'s speculative loading (`<script type="speculationrules">`) on a non-HTTPS origin. wp-env serves plain HTTP; production uses HTTPS.',
	},
	{
		pattern: /NS_BINDING_ABORTED.*chrome:\/\/juggler\//,
		reason: "Playwright's Firefox automation layer (Juggler) logs a worker request it aborted when a test navigates away. It comes from the test harness, not the page.",
	},
	{
		pattern: new RegExp(
			`(?:XMLHttpRequest|Fetch API) cannot load ${ escapeRegExp(
				siteOrigin
			) }/\\S* due to access control checks\\.`
		),
		reason: "WebKit reports a request it cancels because the page navigated away (dashboard widgets, editor previews) as an access-control error. Requests to the test site's own origin are never subject to CORS, so on that origin the message only means a cancelled request. Cross-origin access-control failures still fail.",
	},
];

/**
 * The browser's report of a page answered with 404 Not Found.
 *
 * Not allowed globally: a 404 anywhere else is a broken link or asset. Tests
 * that visit a missing page on purpose opt in with
 * `test.use( { allowedBrowserErrors: [ notFoundPageLoadError ] } )`.
 */
export const notFoundPageLoadError: AllowedError = {
	pattern:
		/^Failed to load resource: the server responded with a status of 404/,
	reason: 'Chromium and WebKit log the 404 response of a page the test deliberately requests to check the not-found template.',
};

/**
 * PHP log entries allowed during every run.
 */
export const allowedPhpErrors: AllowedError[] = [];

/**
 * Whether a message matches one of the allowed patterns.
 *
 * @param text    Message to check.
 * @param allowed Allowed patterns.
 */
export const isAllowedError = (
	text: string,
	allowed: AllowedError[]
): boolean => allowed.some( ( { pattern } ) => pattern.test( text ) );
