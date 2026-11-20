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
		pattern:
			/XMLHttpRequest cannot load \S+\/wp-admin\/admin-ajax\.php\S* due to access control checks\.[\s\S]*\/wp-admin\/(?:js\/dashboard\.js|:\d)/,
		reason: "Logging in lands on the dashboard, whose core scripts start admin-ajax requests (the events and news widgets in dashboard.js, and the inline compression test). When a test navigates away before they finish, WebKit reports each cancelled request as an access-control error. Limited to requests made from the dashboard page's own scripts.",
	},
];

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
