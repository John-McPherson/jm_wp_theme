import { expect, test } from './fixtures/test';
import {
	deleteAttachment,
	getThemeMod,
	importTestLogo,
	removeThemeMod,
	setThemeMod,
} from './helpers/wp-cli';
import { loginToWordPress } from './helpers/login';

const headerSelector = '.jmc-header';

test.describe( 'site header', () => {
	test( 'renders the block-based header', async ( { page } ) => {
		await page.goto( '/' );

		await expect( page.locator( headerSelector ) ).toBeVisible();
	} );

	test( 'uses sticky positioning', async ( { page } ) => {
		await page.setViewportSize( {
			width: 1024,
			height: 800,
		} );

		await page.goto( '/' );

		await expect( page.locator( headerSelector ) ).toHaveCSS(
			'position',
			'sticky'
		);
	} );

	test( 'remains at the viewport edge while scrolling', async ( {
		page,
	} ) => {
		await page.setViewportSize( {
			width: 1024,
			height: 800,
		} );

		await page.goto( '/' );
		await page.waitForLoadState( 'networkidle' );

		const header = page.locator( '.jmc-header' );

		await expect( header ).toBeVisible();
		await expect( header ).toHaveCSS( 'position', 'sticky' );

		await page.evaluate( () => {
			const spacer = document.createElement( 'div' );
			spacer.style.height = '2000px';
			document.body.appendChild( spacer );

			window.scrollTo( 0, 800 );
		} );

		await expect
			.poll( async () => {
				return header.evaluate(
					( element ) => element.getBoundingClientRect().top
				);
			} )
			.toBeCloseTo( 0, 0 );
	} );

	test( 'disables sticky positioning in a short viewport', async ( {
		page,
	} ) => {
		await page.setViewportSize( {
			width: 1024,
			height: 470,
		} );

		await page.goto( '/' );

		await expect( page.locator( headerSelector ) ).toHaveCSS(
			'position',
			'static'
		);
	} );

	test( 'remains sticky above the short viewport breakpoint', async ( {
		page,
	} ) => {
		await page.setViewportSize( {
			width: 1024,
			height: 490,
		} );

		await page.goto( '/' );

		await expect( page.locator( headerSelector ) ).toHaveCSS(
			'position',
			'sticky'
		);
	} );

	test( 'does not create horizontal overflow at 320px', async ( {
		page,
	} ) => {
		await page.setViewportSize( {
			width: 320,
			height: 800,
		} );

		await page.goto( '/' );

		const dimensions = await page.evaluate( () => ( {
			scrollWidth: document.documentElement.scrollWidth,
			clientWidth: document.documentElement.clientWidth,
		} ) );

		expect( dimensions.scrollWidth ).toBeLessThanOrEqual(
			dimensions.clientWidth
		);
	} );
} );

test.describe( 'site identity', () => {
	let originalLogoId: string | null = null;
	let importedLogoId: number | null = null;

	test.beforeEach( async () => {
		originalLogoId = await getThemeMod( 'custom_logo' );

		importedLogoId = null;
	} );

	test.afterEach( async () => {
		/*
		 * Restore the original theme state before deleting the
		 * temporary attachment.
		 */
		if ( originalLogoId !== null ) {
			await setThemeMod( 'custom_logo', originalLogoId );
		} else {
			await removeThemeMod( 'custom_logo' );
		}

		if ( importedLogoId !== null ) {
			await deleteAttachment( importedLogoId );
			importedLogoId = null;
		}
	} );

	test( 'shows the site title when no custom logo exists', async ( {
		page,
	} ) => {
		await removeThemeMod( 'custom_logo' );

		await page.goto( '/' );

		await expect(
			page.locator( '.jmc-logo .wp-block-site-title' )
		).toBeVisible();

		await expect(
			page.locator( '.jmc-logo .wp-block-site-logo img' )
		).toHaveCount( 0 );
	} );

	test( 'shows the logo and hides the title when a custom logo exists', async ( {
		page,
	} ) => {
		importedLogoId = await importTestLogo();

		await setThemeMod( 'custom_logo', importedLogoId );

		await page.goto( '/' );

		await expect(
			page.locator( '.jmc-logo .wp-block-site-logo img' )
		).toBeVisible();

		await expect(
			page.locator( '.jmc-logo .wp-block-site-title' )
		).toBeHidden();
	} );
} );

test.describe( 'Site Editor', () => {
	test( 'administrator can open the Site Editor without recovery UI', async ( {
		page,
	} ) => {
		await loginToWordPress( page );

		await page.goto( '/wp-admin/site-editor.php' );

		await expect( page ).toHaveURL( /site-editor\.php/ );

		await expect(
			page.getByText(
				/this block contains unexpected or invalid content/i
			)
		).toHaveCount( 0 );

		await expect(
			page.getByRole( 'button', {
				name: /attempt block recovery/i,
			} )
		).toHaveCount( 0 );
	} );
} );
