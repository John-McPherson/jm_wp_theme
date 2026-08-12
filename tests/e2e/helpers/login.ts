import type { Page } from '@playwright/test';

export async function loginToWordPress( page: Page ): Promise< void > {
	const username = process.env.WP_USERNAME;
	const password = process.env.WP_PASSWORD;

	if ( ! username || ! password ) {
		throw new Error( 'WP_USERNAME and WP_PASSWORD must be set.' );
	}

	await page.goto( '/wp-login.php' );

	await page.locator( '#user_login' ).fill( username );
	await page.locator( '#user_pass' ).fill( password );
	await page.locator( '#wp-submit' ).click();

	await page.waitForURL( /\/wp-admin\// );
}
