import type { Locator, Page } from '@playwright/test';

import { expect, test } from './fixtures/test';
import {
	createNavigationRecord,
	createPost,
	deletePost,
} from './helpers/wp-cli';

const bodyLink = 'the safety guide';

/**
 * Core blocks, as a legal or long-form page would use, with enough copy to
 * scroll so the last link starts well below the sticky header, plus a theme
 * navigation (the e2e site's header has no menu of its own).
 *
 * @param navigationId `wp_navigation` record for the navigation block.
 */
const buildContent = ( navigationId: number ) =>
	[
		`<!-- wp:jmc/navigation ${ JSON.stringify( { navigationId } ) } /-->`,
		`<!-- wp:paragraph --><p>Read <a href="/guide/">${ bodyLink }</a> before booking.</p><!-- /wp:paragraph -->`,
		'<!-- wp:list --><ul class="wp-block-list"><li>First item</li><li>Second item</li></ul><!-- /wp:list -->',
		'<!-- wp:list {"ordered":true} --><ol class="wp-block-list"><li>First step</li><li>Second step</li></ol><!-- /wp:list -->',
		...Array.from(
			{ length: 40 },
			( _, index ) =>
				`<!-- wp:paragraph --><p>Accessibility E2E filler paragraph ${ index }.</p><!-- /wp:paragraph -->`
		),
		'<!-- wp:paragraph --><p>Finally, <a href="/last/">the last link</a>.</p><!-- /wp:paragraph -->',
	].join( '' );

/**
 * Read one computed style property of the located element.
 *
 * @param locator  Element.
 * @param property CSS property name.
 */
const computed = ( locator: Locator, property: string ) =>
	locator.evaluate(
		( element, name ) =>
			getComputedStyle( element ).getPropertyValue( name ),
		property
	);

/**
 * The root element's computed `scroll-behavior`.
 *
 * @param page Page.
 */
const scrollBehavior = ( page: Page ) =>
	page.evaluate(
		() => getComputedStyle( document.documentElement ).scrollBehavior
	);

test.describe( 'accessibility foundations', () => {
	let navigationId: number | undefined;
	let pageId: number | undefined;
	let url: string;

	test.beforeAll( async () => {
		navigationId = await createNavigationRecord( [
			{ label: 'Home', url: '/' },
			{ label: 'Services', url: '/services/' },
		] );
		pageId = await createPost( {
			type: 'page',
			title: 'Accessibility foundations E2E',
			content: buildContent( navigationId ),
		} );
		url = `/?page_id=${ pageId }`;
	} );

	test.afterAll( async () => {
		if ( pageId ) {
			await deletePost( pageId );
		}

		if ( navigationId ) {
			await deletePost( navigationId );
		}
	} );

	test( 'makes the skip link the first focus target and moves focus to main', async ( {
		page,
		browserName,
	} ) => {
		await page.goto( url );

		// WebKit, like Safari, only moves Tab focus to links with Option.
		await page.keyboard.press(
			browserName === 'webkit' ? 'Alt+Tab' : 'Tab'
		);

		const skipLink = page.getByRole( 'link', { name: 'Skip to content' } );

		await expect( skipLink ).toBeFocused();

		// Visible on focus, not left clipped to a 1px box.
		const box = await skipLink.boundingBox();

		expect( box?.width ).toBeGreaterThan( 1 );
		expect( box?.height ).toBeGreaterThan( 1 );

		await page.keyboard.press( 'Enter' );

		const main = page.getByRole( 'main' );

		await expect( main ).toBeFocused();
		await expect( main ).toHaveAttribute( 'tabindex', '-1' );
	} );

	test( 'underlines body links at rest, on hover and on focus', async ( {
		page,
	} ) => {
		await page.goto( url );

		const link = page.getByRole( 'main' ).getByRole( 'link', {
			name: bodyLink,
		} );

		expect( await computed( link, 'text-decoration-line' ) ).toBe(
			'underline'
		);

		await link.hover();
		expect( await computed( link, 'text-decoration-line' ) ).toBe(
			'underline'
		);

		await link.focus();
		expect( await computed( link, 'text-decoration-line' ) ).toBe(
			'underline'
		);
	} );

	test( 'shows content list markers and hides navigation list markers', async ( {
		page,
	} ) => {
		await page.goto( url );

		const main = page.getByRole( 'main' );

		expect(
			await computed(
				main.locator( 'ul.wp-block-list' ),
				'list-style-type'
			)
		).toBe( 'disc' );
		expect(
			await computed(
				main.locator( 'ol.wp-block-list' ),
				'list-style-type'
			)
		).toBe( 'decimal' );
		expect(
			await computed(
				page.locator( '.jmc-navigation__list' ).first(),
				'list-style-type'
			)
		).toBe( 'none' );
	} );

	test( 'draws the shared focus ring on links and navigation', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 1280, height: 800 } );
		await page.goto( url );

		const link = page.getByRole( 'main' ).getByRole( 'link', {
			name: bodyLink,
		} );

		// A key press first, so the browser treats the focus as keyboard
		// focus and `:focus-visible` applies.
		await page.keyboard.press( 'Tab' );
		await link.focus();

		await expect( link ).toBeFocused();
		expect( await computed( link, 'outline-style' ) ).toBe( 'solid' );
		expect( await computed( link, 'outline-width' ) ).toBe( '2px' );

		const navigationLink = page.locator( '.jmc-navigation__link' ).first();

		if ( ( await navigationLink.count() ) > 0 ) {
			await navigationLink.focus();
			expect( await computed( navigationLink, 'outline-style' ) ).toBe(
				'solid'
			);
		}
	} );

	test( 'keeps focused content clear of the sticky header', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 1280, height: 600 } );
		await page.goto( url );

		// Scroll to the end, then move focus back up to a link near the top
		// with the keyboard. Wait for the smooth scroll to the end to finish
		// first: WebKit drops a focus scroll that starts mid-animation.
		const lastLink = page.getByRole( 'link', { name: 'the last link' } );
		const link = page.getByRole( 'main' ).getByRole( 'link', {
			name: bodyLink,
		} );

		await lastLink.focus();
		await expect
			.poll( () =>
				page.evaluate(
					() =>
						Math.ceil( window.scrollY + window.innerHeight ) >=
						document.documentElement.scrollHeight
				)
			)
			.toBe( true );
		await page.keyboard.press( 'Shift+Tab' );
		await expect( link ).toBeFocused();

		const header = await page.locator( '.jmc-header' ).boundingBox();

		expect( header ).not.toBeNull();
		// Poll: the focus scroll is smooth-animated too.
		await expect
			.poll( async () => ( await link.boundingBox() )?.y )
			.toBeGreaterThanOrEqual(
				( header?.y ?? 0 ) + ( header?.height ?? 0 )
			);
	} );

	test( 'scrolls smoothly only without a reduced-motion preference', async ( {
		page,
	} ) => {
		await page.emulateMedia( { reducedMotion: 'no-preference' } );
		await page.goto( url );

		expect( await scrollBehavior( page ) ).toBe( 'smooth' );

		await page.emulateMedia( { reducedMotion: 'reduce' } );

		expect( await scrollBehavior( page ) ).toBe( 'auto' );
	} );

	test( 'switches off transitions for reduced motion', async ( { page } ) => {
		await page.emulateMedia( { reducedMotion: 'reduce' } );
		await page.goto( url );

		// Buttons, navigation and cards all declare transitions.
		const durations = await page.evaluate( () =>
			Array.from( document.querySelectorAll( 'body *' ) ).flatMap(
				( element ) =>
					getComputedStyle( element )
						.transitionDuration.split( ',' )
						.map( ( value ) => parseFloat( value ) )
			)
		);

		expect( Math.max( ...durations ) ).toBeLessThan( 0.001 );
	} );
} );
