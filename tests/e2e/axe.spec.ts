import type { Page } from '@playwright/test';

import { accessibilityExceptions } from './accessibility-exceptions';
import { notFoundPageLoadError } from './fixtures/allowed-errors';
import { expect, test } from './fixtures/test';
import { expectNoAxeViolations } from './helpers/axe';
import {
	createPageFromPatterns,
	createPost,
	customizeTemplatePart,
	deletePost,
	importTestLogo,
	getOptions,
	setOptions,
	type OptionBackup,
} from './helpers/wp-cli';

/**
 * Options the scans change, restored afterwards: the static front page, the
 * Services page and the business details used by contact actions.
 */
const businessOptions = {
	jmc_business_name: 'AMC Electrical E2E',
	jmc_business_address: '1 Test Street\nTestville\nTE1 1ST',
	jmc_business_phone: '01234 567890',
	jmc_business_email: 'enquiries@example.com',
	jmc_business_whatsapp: '+44 7700 900123',
	jmc_business_hours: {
		monday: { opens: '08:00', closes: '17:00' },
		saturday: { opens: '09:00', closes: '12:00' },
	},
	jmc_business_service_area: [ 'Testville', 'Exampleton' ],
};

const optionNames = [
	'show_on_front',
	'page_on_front',
	'jmc_services_page_id',
	...Object.keys( businessOptions ),
];

/**
 * A menu with plain links and a submenu, used by the header and footer.
 *
 * @param servicesUrl Services landing page URL.
 * @param serviceUrl  Single service page URL.
 * @param contactUrl  Contact page URL.
 */
const navigationContent = (
	servicesUrl: string,
	serviceUrl: string,
	contactUrl: string
) =>
	[
		'<!-- wp:navigation-link {"label":"Home","url":"/"} /-->',
		`<!-- wp:navigation-submenu ${ JSON.stringify( {
			label: 'Services',
			url: servicesUrl,
		} ) } -->`,
		`<!-- wp:navigation-link ${ JSON.stringify( {
			label: 'Rewiring',
			url: serviceUrl,
		} ) } /-->`,
		'<!-- wp:navigation-link {"label":"EV charger installation","url":"/ev-chargers/"} /-->',
		'<!-- /wp:navigation-submenu -->',
		`<!-- wp:navigation-link ${ JSON.stringify( {
			label: 'Contact',
			url: contactUrl,
		} ) } /-->`,
	].join( '' );

/**
 * Wait until the navigation script has enhanced the menu, so a scan never
 * sees the no-JavaScript markup mid-switch.
 *
 * @param page Page.
 */
const waitForNavigation = async ( page: Page ) => {
	await expect(
		page.locator( '.jmc-header .jmc-navigation' )
	).not.toHaveClass( /no-js/ );
};

const desktop = { width: 1280, height: 800 };
const mobile = { width: 375, height: 812 };

// The scans set their own desktop and mobile viewports, so phone projects
// skip them (see playwright.config.ts).
test.describe( 'axe accessibility scans', { tag: '@desktop' }, () => {
	// Scan the settled page: with reduced motion, transitions finish at once,
	// so colour contrast is never measured mid-transition.
	test.use( { contextOptions: { reducedMotion: 'reduce' } } );

	let options: OptionBackup | undefined;
	const postIds: number[] = [];
	const urls = {
		standard: '',
		services: '',
		service: '',
		contact: '',
	};

	test.beforeAll( async () => {
		options = await getOptions( optionNames );
		await setOptions( businessOptions );

		const frontId = await createPageFromPatterns( {
			title: 'Axe home E2E',
			patterns: [ 'jmc/home-page' ],
		} );
		postIds.push( frontId );

		const servicesId = await createPageFromPatterns( {
			title: 'Axe services E2E',
			patterns: [ 'jmc/services-landing' ],
		} );
		postIds.push( servicesId );

		const serviceId = await createPageFromPatterns( {
			title: 'Axe service E2E',
			patterns: [ 'jmc/service-page' ],
			parent: servicesId,
		} );
		postIds.push( serviceId );

		// The contact template is chosen by the `contact` slug.
		const contactId = await createPageFromPatterns( {
			title: 'Axe contact E2E',
			patterns: [ 'jmc/contact-page' ],
			slug: 'contact',
		} );
		postIds.push( contactId );

		// A standard page opens with the page title, not a hero.
		const standardId = await createPageFromPatterns( {
			title: 'Axe standard page E2E',
			patterns: [
				'jmc/service-introduction',
				'jmc/why-choose-us',
				'jmc/contact-details',
				'jmc/call-to-action',
			],
		} );
		postIds.push( standardId );

		await setOptions( {
			show_on_front: 'page',
			page_on_front: frontId,
			jmc_services_page_id: servicesId,
		} );

		urls.standard = `/?page_id=${ standardId }`;
		urls.services = `/?page_id=${ servicesId }`;
		urls.service = `/?page_id=${ serviceId }`;
		urls.contact = `/?page_id=${ contactId }`;

		// The theme's header and footer parts ship without a menu, header call
		// to action or credentials; fill them in, as the Site Editor does on a
		// real site. Menus are named after their records, so each landmark
		// gets a distinct name.
		const menu = navigationContent(
			urls.services,
			urls.service,
			urls.contact
		);
		const headerMenuId = await createPost( {
			type: 'wp_navigation',
			title: 'Axe main menu E2E',
			content: menu,
		} );
		postIds.push( headerMenuId );

		const footerMenuId = await createPost( {
			type: 'wp_navigation',
			title: 'Axe footer menu E2E',
			content: menu,
		} );
		postIds.push( footerMenuId );

		// A credential logo, so the trust credentials render.
		const logoId = await importTestLogo();
		postIds.push( logoId );

		postIds.push(
			await customizeTemplatePart( 'header', 'header', {
				'<!-- wp:jmc/navigation -->': `<!-- wp:jmc/navigation ${ JSON.stringify(
					{ navigationId: headerMenuId }
				) } -->`,
				'<!-- wp:jmc/header-cta {"lock":{"remove":true}} /-->': `<!-- wp:jmc/header-cta ${ JSON.stringify(
					{
						text: 'Get a quote',
						link: { url: urls.contact, opensInNewTab: false },
					}
				) } /-->`,
			} ),
			await customizeTemplatePart( 'footer', 'footer', {
				'<!-- wp:jmc/footer-navigation /-->': `<!-- wp:jmc/footer-navigation ${ JSON.stringify(
					{ navigationId: footerMenuId }
				) } /-->`,
				'<!-- wp:jmc/credential /-->': `<!-- wp:jmc/credential ${ JSON.stringify(
					{
						imageId: logoId,
						link: {
							url: 'https://example.com/',
							opensInNewTab: true,
						},
					}
				) } /-->`,
			} )
		);
	} );

	// Undo only what setup did, so a failed setup never blanks the options.
	test.afterAll( async () => {
		for ( const postId of postIds.reverse() ) {
			await deletePost( postId );
		}

		if ( options ) {
			await setOptions( options );
		}
	} );

	test( 'records every exception with a rationale, owner and review date', () => {
		const today = new Date().toISOString().slice( 0, 10 );

		for ( const exception of accessibilityExceptions ) {
			const name = `${ exception.rule } (${ exception.targets.join(
				', '
			) })`;

			expect( exception.targets, name ).not.toHaveLength( 0 );
			expect( exception.rationale.trim(), name ).not.toBe( '' );
			expect( exception.owner.trim(), name ).not.toBe( '' );
			expect( exception.issue.trim(), name ).not.toBe( '' );
			expect( exception.reviewBy, name ).toMatch( /^\d{4}-\d{2}-\d{2}$/ );
			expect(
				exception.reviewBy >= today,
				`${ name } passed its review date (${ exception.reviewBy }): fix it, or review it and set a new date.`
			).toBe( true );
		}
	} );

	test.describe( 'templates', () => {
		test.use( { viewport: desktop } );

		test( 'front page', async ( { page } ) => {
			await page.goto( '/' );
			await expect( page.locator( 'main .jmc-hero' ) ).toBeVisible();
			await waitForNavigation( page );

			await expectNoAxeViolations( page, 'front page' );
		} );

		test( 'standard page', async ( { page } ) => {
			await page.goto( urls.standard );
			await expect(
				page.locator( 'main .jmc-page-title' )
			).toBeVisible();
			await waitForNavigation( page );

			await expectNoAxeViolations( page, 'standard page' );
		} );

		test( 'services landing page', async ( { page } ) => {
			await page.goto( urls.services );
			await expect(
				page.locator( 'main.jmc-main--services-landing' )
			).toBeVisible();
			await expect(
				page.locator( 'main .jmc-service-grid' ).first()
			).toBeVisible();
			await waitForNavigation( page );

			await expectNoAxeViolations( page, 'services landing page' );
		} );

		test( 'single service page', async ( { page } ) => {
			await page.goto( urls.service );
			await expect(
				page.locator( 'main.jmc-main--service' )
			).toBeVisible();
			await waitForNavigation( page );

			await expectNoAxeViolations( page, 'single service page' );
		} );

		test( 'contact page', async ( { page } ) => {
			await page.goto( urls.contact );
			await expect(
				page.locator( 'main.jmc-main--contact' )
			).toBeVisible();
			await expect( page.locator( 'main form' ) ).toBeVisible();
			await waitForNavigation( page );

			await expectNoAxeViolations( page, 'contact page' );
		} );

		test.describe( 'not found', () => {
			// These pages answer 404 on purpose, which the browser logs.
			test.use( { allowedBrowserErrors: [ notFoundPageLoadError ] } );

			// Site search is turned off: a search is answered by the 404 template.
			test( 'search', async ( { page } ) => {
				await page.goto( '/?s=electrician' );
				await expect(
					page.locator( 'main.jmc-not-found' )
				).toBeVisible();
				await waitForNavigation( page );

				await expectNoAxeViolations( page, 'search' );
			} );

			test( '404 page', async ( { page } ) => {
				const response = await page.goto( '/?p=999999999' );

				expect( response?.status() ).toBe( 404 );
				await expect(
					page.locator( 'main.jmc-not-found' )
				).toBeVisible();
				await waitForNavigation( page );

				await expectNoAxeViolations( page, '404 page' );
			} );
		} );
	} );

	test.describe( 'navigation', () => {
		test( 'mobile menu open with submenus expanded', async ( { page } ) => {
			await page.setViewportSize( mobile );
			await page.goto( urls.standard );
			await waitForNavigation( page );

			const navigation = page.locator( '.jmc-header .jmc-navigation' );
			const toggle = navigation.getByRole( 'button', {
				name: /toggle navigation menu/i,
			} );

			await toggle.click();
			await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
			// Below the desktop breakpoint every submenu is expanded in the menu.
			await expect(
				navigation.getByRole( 'link', { name: 'Rewiring' } )
			).toBeVisible();

			await expectNoAxeViolations( page, 'mobile menu open' );
		} );

		test( 'desktop submenu expanded', async ( { page } ) => {
			await page.setViewportSize( desktop );
			await page.goto( urls.standard );
			await waitForNavigation( page );

			const navigation = page.locator( '.jmc-header .jmc-navigation' );
			const submenuToggle = navigation.locator(
				'.jmc-navigation__submenu-toggle'
			);

			// By keyboard: a pointer would open the submenu on hover first.
			await submenuToggle.focus();
			await page.keyboard.press( 'Enter' );
			await expect( submenuToggle ).toHaveAttribute(
				'aria-expanded',
				'true'
			);
			await expect(
				navigation.getByRole( 'link', { name: 'Rewiring' } )
			).toBeVisible();

			await expectNoAxeViolations( page, 'desktop submenu expanded' );
		} );
	} );

	test.describe( 'enquiry form', () => {
		test.use( { viewport: desktop } );

		test( 'error summary after an empty submission', async ( { page } ) => {
			await page.goto( urls.contact );

			const form = page.locator( 'form[data-jmc-enquiry-endpoint]' );

			await form.locator( 'button[type="submit"]' ).click();
			// The summary sits in the notice above the form.
			await expect(
				page.locator( '.jmc-enquiry-form__summary' )
			).toBeVisible();

			await expectNoAxeViolations( page, 'enquiry form errors' );
		} );

		for ( const result of [ 'sent', 'invalid' ] ) {
			test( `"${ result }" result notice`, async ( { page } ) => {
				await page.goto(
					`${ urls.contact }&jmc_enquiry_result=${ result }`
				);
				await expect(
					page.locator( '.jmc-enquiry-form__notice-message' )
				).not.toBeEmpty();

				await expectNoAxeViolations(
					page,
					`enquiry form "${ result }" notice`
				);
			} );
		}
	} );
} );
