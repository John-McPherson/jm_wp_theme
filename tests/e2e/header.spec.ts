import { expect, test } from './fixtures/test';
import {
	deletePost,
	createNavigationRecord,
	createNavigationTestPage,
	getOption,
	updateNavigationRecord,
	updateOption,
} from './helpers/wp-cli';
import { loginToWordPress } from './helpers/login';

const headerSelector = '.jmc-header';

// Tests that set a desktop viewport, need the desktop menu, sign in to the
// admin or open the Site Editor are tagged `@desktop`, so phone projects skip
// them (see playwright.config.ts).
test.describe( 'site header', () => {
	test( 'renders the block-based header', async ( { page } ) => {
		await page.goto( '/' );

		await expect( page.locator( headerSelector ) ).toBeVisible();
	} );

	test(
		'uses sticky positioning',
		{ tag: '@desktop' },
		async ( { page } ) => {
			await page.setViewportSize( {
				width: 1024,
				height: 800,
			} );

			await page.goto( '/' );

			await expect( page.locator( headerSelector ) ).toHaveCSS(
				'position',
				'sticky'
			);
		}
	);

	test(
		'offsets the sticky header below the admin toolbar',
		{ tag: '@desktop' },
		async ( { page } ) => {
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
		}
	);

	test(
		'remains at the viewport edge while scrolling',
		{ tag: '@desktop' },
		async ( { page } ) => {
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
					Number.parseFloat(
						window.getComputedStyle( element ).top
					) || 0
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
		}
	);

	test(
		'disables sticky positioning in a short viewport',
		{ tag: '@desktop' },
		async ( { page } ) => {
			await page.setViewportSize( {
				width: 1024,
				height: 470,
			} );

			await page.goto( '/' );

			await expect( page.locator( headerSelector ) ).toHaveCSS(
				'position',
				'static'
			);
		}
	);

	test(
		'remains sticky above the short viewport breakpoint',
		{ tag: '@desktop' },
		async ( { page } ) => {
			await page.setViewportSize( {
				width: 1024,
				height: 490,
			} );

			await page.goto( '/' );

			await expect( page.locator( headerSelector ) ).toHaveCSS(
				'position',
				'sticky'
			);
		}
	);

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

test.describe( 'theme navigation', () => {
	let navigationId: number;
	let pageId: number;

	test.beforeEach( async () => {
		navigationId = await createNavigationRecord( [
			{ label: 'Home', url: '/' },
			{ label: 'Services', url: '/services/' },
		] );
		pageId = await createNavigationTestPage( navigationId );
	} );

	test.afterEach( async () => {
		await deletePost( pageId );
		await deletePost( navigationId );
	} );

	test( 'opens and closes the enhanced mobile menu by keyboard', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 320, height: 800 } );
		await page.goto( `/?page_id=${ pageId }` );

		const navigation = page.locator( '.jmc-navigation' );
		const toggle = navigation.getByRole( 'button', {
			name: /toggle navigation menu/i,
		} );
		const list = navigation.locator( '.jmc-navigation__list' );

		await expect( navigation ).not.toHaveClass( /no-js/ );
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );

		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( list ).toBeVisible();

		await page.keyboard.press( 'Escape' );
		await expect( toggle ).toBeFocused();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( list ).toBeHidden();
	} );

	test( 'keeps primary links available without JavaScript', async ( {
		browser,
	} ) => {
		const context = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		const page = await context.newPage();

		try {
			await page.goto( `/?page_id=${ pageId }` );

			const navigation = page.locator( '.jmc-navigation' );

			await expect( navigation ).toHaveClass( /no-js/ );
			await expect(
				navigation.getByRole( 'link', { name: 'Services' } )
			).toBeVisible();
			await expect(
				navigation.getByRole( 'button', {
					name: /toggle navigation menu/i,
				} )
			).toBeHidden();
		} finally {
			await context.close();
		}
	} );

	test( 'renders each link with only its label as text', async ( {
		page,
	} ) => {
		await page.goto( `/?page_id=${ pageId }` );

		const navigation = page.locator( '.jmc-navigation' );

		await expect(
			navigation.locator( '.jmc-navigation__link' )
		).toHaveText( [ 'Home', 'Services' ] );

		const itemText = await navigation
			.locator( '.jmc-navigation__item' )
			.allTextContents();

		expect( itemText.map( ( text ) => text.trim() ) ).toEqual( [
			'Home',
			'Services',
		] );
	} );

	test( 'points each link at its configured URL', async ( { page } ) => {
		await page.goto( `/?page_id=${ pageId }` );

		const links = page.locator( '.jmc-navigation .jmc-navigation__link' );

		await expect( links.nth( 0 ) ).toHaveAttribute( 'href', '/' );
		await expect( links.nth( 1 ) ).toHaveAttribute( 'href', '/services/' );
	} );

	// Reads the links by role, so it needs the desktop menu, shown inline.
	test(
		'marks only the current page link with aria-current',
		{ tag: '@desktop' },
		async ( { page } ) => {
			await updateNavigationRecord( navigationId, [
				{ label: 'Home', url: '/' },
				{
					label: 'This page',
					url: `/?page_id=${ pageId }`,
					id: pageId,
				},
			] );

			await page.goto( `/?page_id=${ pageId }` );

			const navigation = page.locator( '.jmc-navigation' );

			await expect(
				navigation.getByRole( 'link', {
					name: 'This page',
					exact: true,
				} )
			).toHaveAttribute( 'aria-current', 'page' );
			await expect(
				navigation.getByRole( 'link', { name: 'Home', exact: true } )
			).not.toHaveAttribute( 'aria-current' );
		}
	);
} );

test.describe( 'site identity', () => {
	let originalSiteTitle = '';

	test.beforeEach( async () => {
		originalSiteTitle = await getOption( 'blogname' );
	} );

	test.afterEach( async () => {
		await updateOption( 'blogname', originalSiteTitle );
	} );

	test( 'shows the inline theme logo instead of the site title', async ( {
		page,
	} ) => {
		await page.goto( '/' );

		const logo = page.locator( '.jmc-logo .jmc-site-logo' );

		await expect( logo.locator( 'svg.jmc-site-logo__svg' ) ).toBeVisible();
		await expect( page.locator( '.jmc-logo img' ) ).toHaveCount( 0 );

		await expect(
			page.locator( '.jmc-logo .wp-block-site-title' )
		).toHaveCount( 0 );
	} );

	test( 'names the logo link with the site title and hides the SVG', async ( {
		page,
	} ) => {
		await updateOption( 'blogname', 'AMC Electrical E2E' );

		await page.goto( '/' );

		const logoLink = page
			.locator( '.jmc-logo' )
			.getByRole( 'link', { name: 'AMC Electrical E2E', exact: true } );

		await expect( logoLink ).toBeVisible();

		const svg = logoLink.locator( 'svg' );

		await expect( svg ).toHaveAttribute( 'aria-hidden', 'true' );
		await expect( svg ).toHaveAttribute( 'focusable', 'false' );
		await expect( svg.locator( 'title' ) ).toHaveCount( 0 );
	} );

	test( 'links the logo to the homepage', async ( { page } ) => {
		await page.goto( '/' );

		const logoLink = page.locator( '.jmc-logo a.jmc-site-logo' );

		await expect( logoLink ).toHaveAttribute( 'rel', 'home' );

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

	test( 'reserves the logo size before it paints', async ( { page } ) => {
		await page.goto( '/' );

		const box = await page
			.locator( '.jmc-logo .jmc-site-logo__svg' )
			.boundingBox();

		expect( box ).not.toBeNull();
		// The artwork's viewBox is 1375 × 457.
		expect( ( box?.width ?? 0 ) / ( box?.height ?? 1 ) ).toBeCloseTo(
			1375 / 457,
			1
		);
	} );

	test( 'follows the surrounding palette without extra markup', async ( {
		page,
	} ) => {
		await page.goto( '/' );

		const fills = () =>
			page
				.locator( '.jmc-logo .jmc-site-logo' )
				.evaluate( ( logo ) =>
					[ 'primary', 'accent', 'secondary' ].map(
						( part ) =>
							getComputedStyle(
								logo.querySelector(
									`.jmc-site-logo__${ part }`
								) as Element
							).fill
					)
				);

		const defaultFills = await fills();

		for ( const palette of [ 'secondary', 'inverse' ] ) {
			await page.locator( '.jmc-header' ).evaluate( ( header, name ) => {
				header.classList.remove(
					'jmc-palette--secondary',
					'jmc-palette--inverse'
				);
				header.classList.add( `jmc-palette--${ name }` );
			}, palette );

			const paletteFills = await fills();

			expect( paletteFills ).not.toEqual( defaultFills );
		}

		// The inverse palette draws the letters in light base-50.
		expect( ( await fills() )[ 0 ] ).toBe( 'rgb(250, 250, 249)' );
	} );
} );

test.describe( 'Site Editor', { tag: '@desktop' }, () => {
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
