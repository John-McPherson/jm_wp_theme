import { expect, test } from './fixtures/test';
import {
	deleteAttachment,
	getOption,
	getThemeMod,
	importTestLogo,
	removeThemeMod,
	setThemeMod,
	updateOption,
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

	test( 'offsets the sticky header below the admin toolbar', async ( {
		page,
	} ) => {
		await page.setViewportSize( {
			width: 1024,
			height: 800,
		} );

		await loginToWordPress( page );
		await page.goto( '/' );

		await expect( page.locator( 'body' ) ).toHaveClass( /admin-bar/ );

		await expect( page.locator( headerSelector ) ).toHaveCSS(
			'top',
			'32px'
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
		await page.waitForLoadState( 'domcontentloaded' );

		const header = page.locator( '.jmc-header' );
		const siteBlocks = page.locator( '.wp-site-blocks' );

		await expect( header ).toBeVisible();
		await expect( siteBlocks ).toBeVisible();
		await expect( header ).toHaveCSS( 'position', 'sticky' );

		await siteBlocks.evaluate( ( container ) => {
			const spacer = document.createElement( 'div' );

			spacer.style.height = '3000px';
			spacer.setAttribute( 'data-e2e-spacer', '' );

			container.appendChild( spacer );
		} );

		const stickyTop = await header.evaluate( ( element ) => {
			return (
				Number.parseFloat( window.getComputedStyle( element ).top ) || 0
			);
		} );

		await page.evaluate( () => {
			window.scrollTo( {
				top: 800,
				behavior: 'instant',
			} );
		} );

		await expect
			.poll( () => page.evaluate( () => window.scrollY ) )
			.toBeGreaterThanOrEqual( 798 );

		await expect
			.poll( async () => {
				return header.evaluate( ( element, expectedTop ) => {
					return Math.abs(
						element.getBoundingClientRect().top - expectedTop
					);
				}, stickyTop );
			} )
			.toBeLessThanOrEqual( 2 );
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
	let originalSiteTitle = '';

	test.beforeEach( async () => {
		originalLogoId = await getThemeMod( 'custom_logo' );
		originalSiteTitle = await getOption( 'blogname' );
		importedLogoId = null;
	} );

	test.afterEach( async () => {
		await updateOption( 'blogname', originalSiteTitle );

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
		await updateOption( 'blogname', 'AMC Electrical E2E' );

		await page.goto( '/' );

		const siteTitle = page.locator( '.jmc-logo .wp-block-site-title' );

		await expect( siteTitle ).toBeVisible();
		await expect( siteTitle ).toContainText( 'AMC Electrical E2E' );

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
	test( 'links the configured logo to the homepage', async ( { page } ) => {
		importedLogoId = await importTestLogo();

		await setThemeMod( 'custom_logo', importedLogoId );

		await page.goto( '/' );

		const logoLink = page.locator( '.jmc-logo .wp-block-site-logo a' );

		await expect( logoLink ).toBeVisible();

		const href = await logoLink.getAttribute( 'href' );

		expect( href ).not.toBeNull();

		const actualUrl = new URL( href as string, page.url() );

		const expectedUrl = new URL(
			process.env.WP_BASE_URL ?? 'http://localhost:8888'
		);

		expect( actualUrl.origin + actualUrl.pathname ).toBe(
			expectedUrl.origin + expectedUrl.pathname
		);
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
