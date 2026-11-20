import type { Locator, Page } from '@playwright/test';

import { expect, test } from './fixtures/test';
import {
	createPageFromPatterns,
	createPost,
	customizeTemplatePart,
	deletePost,
	getOptions,
	setOptions,
	type OptionBackup,
} from './helpers/wp-cli';

/**
 * Business details read by the header call to action, the Contact details
 * block and the footer.
 */
const businessOptions = {
	jmc_business_name: 'AMC Electrical Phone E2E',
	jmc_business_phone: '01234 567890',
	jmc_business_email: 'enquiries@example.com',
	jmc_business_whatsapp: '+44 7700 900123',
	jmc_business_service_area: [ 'Testville', 'Exampleton' ],
};

const optionNames = [
	'show_on_front',
	'page_on_front',
	'jmc_services_page_id',
	...Object.keys( businessOptions ),
];

const headerCtaText = 'Call us now';

/** Pages built from the theme's patterns, keyed by template. */
const pages = {
	'front page': '',
	'standard page': '',
	'services landing page': '',
	'single service page': '',
	'contact page': '',
};

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
 * The header navigation, once its script has enhanced the menu.
 *
 * @param page Page.
 */
async function enhancedNavigation( page: Page ): Promise< Locator > {
	const navigation = page.locator( '.jmc-header .jmc-navigation' );

	await expect( navigation ).not.toHaveClass( /no-js/ );

	return navigation;
}

/**
 * Check the page is no wider than the phone's screen.
 *
 * @param page Page.
 */
async function expectNoHorizontalOverflow( page: Page ): Promise< void > {
	const { scrollWidth, clientWidth } = await page.evaluate( () => ( {
		scrollWidth: document.documentElement.scrollWidth,
		clientWidth: document.documentElement.clientWidth,
	} ) );

	expect( scrollWidth ).toBeLessThanOrEqual( clientWidth );
}

/**
 * Check a region has a telephone, email and WhatsApp link that a tap would
 * reach. The tap is a trial: it runs Playwright's checks (visible, stable,
 * enabled and not covered by another element at the tap point) without
 * leaving the page for a phone, mail or WhatsApp app.
 *
 * @param region Region holding the links.
 */
async function expectTappableContactLinks( region: Locator ): Promise< void > {
	for ( const prefix of [ 'tel:', 'mailto:', 'https://wa.me/' ] ) {
		const link = region.locator( `a[href^="${ prefix }"]` ).first();

		await expect( link, prefix ).toBeVisible();
		await link.tap( { trial: true } );
	}
}

// Touch journeys in emulated phones. `@phone` keeps them off the desktop
// projects, which have no touch screen (see playwright.config.ts).
test.describe( 'phone journeys', { tag: '@phone' }, () => {
	let options: OptionBackup | undefined;
	const postIds: number[] = [];

	test.beforeAll( async () => {
		options = await getOptions( optionNames );
		await setOptions( businessOptions );

		const frontId = await createPageFromPatterns( {
			title: 'Phone home E2E',
			patterns: [ 'jmc/home-page' ],
		} );
		postIds.push( frontId );

		const servicesId = await createPageFromPatterns( {
			title: 'Phone services E2E',
			patterns: [ 'jmc/services-landing' ],
		} );
		postIds.push( servicesId );

		const serviceId = await createPageFromPatterns( {
			title: 'Phone service E2E',
			patterns: [ 'jmc/service-page' ],
			parent: servicesId,
		} );
		postIds.push( serviceId );

		// The contact template is chosen by the `contact` slug.
		const contactId = await createPageFromPatterns( {
			title: 'Phone contact E2E',
			patterns: [ 'jmc/contact-page' ],
			slug: 'contact',
		} );
		postIds.push( contactId );

		const standardId = await createPageFromPatterns( {
			title: 'Phone standard page E2E',
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

		pages[ 'front page' ] = '/';
		pages[ 'standard page' ] = `/?page_id=${ standardId }`;
		pages[ 'services landing page' ] = `/?page_id=${ servicesId }`;
		pages[ 'single service page' ] = `/?page_id=${ serviceId }`;
		pages[ 'contact page' ] = `/?page_id=${ contactId }`;

		// Fill in the header and footer as the Site Editor does on a real
		// site: a menu with a submenu, and a call to action that phones the
		// business.
		const menu = navigationContent(
			pages[ 'services landing page' ],
			pages[ 'single service page' ],
			pages[ 'contact page' ]
		);
		const headerMenuId = await createPost( {
			type: 'wp_navigation',
			title: 'Phone main menu E2E',
			content: menu,
		} );
		postIds.push( headerMenuId );

		const footerMenuId = await createPost( {
			type: 'wp_navigation',
			title: 'Phone footer menu E2E',
			content: menu,
		} );
		postIds.push( footerMenuId );

		postIds.push(
			await customizeTemplatePart( 'header', 'header', {
				'<!-- wp:jmc/navigation -->': `<!-- wp:jmc/navigation ${ JSON.stringify(
					{ navigationId: headerMenuId }
				) } -->`,
				'<!-- wp:jmc/header-cta {"lock":{"remove":true}} /-->': `<!-- wp:jmc/header-cta ${ JSON.stringify(
					{ text: headerCtaText, linkType: 'phone' }
				) } /-->`,
			} ),
			await customizeTemplatePart( 'footer', 'footer', {
				'<!-- wp:jmc/footer-navigation /-->': `<!-- wp:jmc/footer-navigation ${ JSON.stringify(
					{ navigationId: footerMenuId }
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

	test( 'opens, uses and closes the menu and its submenu by tap', async ( {
		page,
	} ) => {
		await page.goto( pages[ 'standard page' ] );

		const navigation = await enhancedNavigation( page );
		const toggle = navigation.getByRole( 'button', {
			name: /toggle navigation menu/i,
		} );
		const list = navigation.locator( '.jmc-navigation__list' );
		const submenuLink = navigation.getByRole( 'link', {
			name: 'Rewiring',
		} );

		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( list ).toBeHidden();

		await toggle.tap();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( list ).toBeVisible();
		await expectNoHorizontalOverflow( page );

		// On phones every submenu is expanded in the menu, with nothing to
		// open: its desktop toggle is hidden.
		await expect( submenuLink ).toBeVisible();
		await expect(
			navigation.locator( '.jmc-navigation__submenu-toggle' )
		).toBeHidden();

		await toggle.tap();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( list ).toBeHidden();

		await toggle.tap();
		await submenuLink.tap();

		await expect( page.locator( 'main.jmc-main--service' ) ).toBeVisible();
		await expect(
			( await enhancedNavigation( page ) ).getByRole( 'button', {
				name: /toggle navigation menu/i,
			} )
		).toHaveAttribute( 'aria-expanded', 'false' );
	} );

	for ( const name of Object.keys( pages ) as Array< keyof typeof pages > ) {
		test( `${ name } fits the screen without horizontal overflow`, async ( {
			page,
		} ) => {
			await page.goto( pages[ name ] );
			await expect( page.getByRole( 'contentinfo' ) ).toBeVisible();
			await enhancedNavigation( page );

			await expectNoHorizontalOverflow( page );
		} );
	}

	test( 'offers tappable phone, email and WhatsApp links', async ( {
		page,
	} ) => {
		await page.goto( pages[ 'contact page' ] );

		// The header call to action sits in the menu.
		const navigation = await enhancedNavigation( page );
		const toggle = navigation.getByRole( 'button', {
			name: /toggle navigation menu/i,
		} );

		await toggle.tap();

		const headerCta = navigation.getByRole( 'link', {
			name: headerCtaText,
		} );

		await expect( headerCta ).toHaveAttribute( 'href', /^tel:/ );
		await headerCta.tap( { trial: true } );

		// The open menu covers the page, so close it first.
		await toggle.tap();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );

		await expectTappableContactLinks(
			page.getByRole( 'main' ).locator( '.jmc-contact-details' )
		);
		await expectTappableContactLinks( page.getByRole( 'contentinfo' ) );
	} );

	test( 'sends an enquiry by touch with the right keyboards', async ( {
		page,
	} ) => {
		await page.goto( pages[ 'contact page' ] );

		const form = page.locator( 'form.jmc-enquiry-form__form' );
		const name = form.getByRole( 'textbox', { name: 'Name', exact: true } );
		const email = form.getByRole( 'textbox', { name: 'Email address' } );
		const phone = form.getByRole( 'textbox', { name: 'Phone number' } );
		const postcode = form.getByRole( 'textbox', { name: 'Postcode' } );
		const message = form.getByRole( 'textbox', { name: 'Message' } );

		// The input type picks the phone's keyboard; autocomplete lets it
		// offer saved details.
		await expect( name ).toHaveAttribute( 'autocomplete', 'name' );
		await expect( email ).toHaveAttribute( 'type', 'email' );
		await expect( email ).toHaveAttribute( 'autocomplete', 'email' );
		await expect( phone ).toHaveAttribute( 'type', 'tel' );
		await expect( phone ).toHaveAttribute( 'autocomplete', 'tel' );
		// UK postcodes mix letters and digits, so they keep the text
		// keyboard, in capitals.
		await expect( postcode ).toHaveAttribute( 'type', 'text' );
		await expect( postcode ).not.toHaveAttribute(
			'inputmode',
			/numeric|decimal|tel/
		);
		await expect( postcode ).toHaveAttribute(
			'autocomplete',
			'postal-code'
		);
		await expect( postcode ).toHaveAttribute(
			'autocapitalize',
			'characters'
		);

		const entries: Array< [ Locator, string ] > = [
			[ name, 'Phone Tester' ],
			[ email, 'phone.tester@example.com' ],
			[ phone, '07700 900456' ],
			[ postcode, 'LS1 4AP' ],
			[ message, 'Please quote for a consumer unit upgrade.' ],
		];

		for ( const [ field, value ] of entries ) {
			await field.tap();
			await expect( field ).toBeFocused();
			await page.keyboard.type( value );
			await expect( field ).toHaveValue( value );
		}

		const consent = form.getByRole( 'checkbox' );

		await consent.tap();
		await expect( consent ).toBeChecked();

		// Answer the submission here, so the test stores no enquiry and
		// sends no email.
		const endpoint = await form.getAttribute( 'data-jmc-enquiry-endpoint' );
		let submitted = '';

		await page.route(
			( url ) => url.href === endpoint,
			async ( route ) => {
				submitted = route.request().postData() ?? '';
				await route.fulfill( { json: { status: 'sent' } } );
			}
		);

		await form.getByRole( 'button', { name: /send|submit/i } ).tap();

		// The script holds the submission for a few seconds after load, as
		// the server drops faster ones as automated.
		await expect(
			page.locator( '.jmc-enquiry-form__notice--success' )
		).toBeVisible( { timeout: 15_000 } );
		await expect( form ).toBeHidden();

		for ( const [ , value ] of entries ) {
			expect( submitted ).toContain( value );
		}
	} );
} );
