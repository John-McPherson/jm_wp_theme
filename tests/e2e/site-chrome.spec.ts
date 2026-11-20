import type { Locator } from '@playwright/test';

import { expect, test } from './fixtures/test';
import {
	expectNoInvalidBlocks,
	getBlockByName,
	openBlockSettings,
	updateBlockAttributes,
} from './helpers/editor';
import { loginToWordPress } from './helpers/login';
import {
	createNavigationRecord,
	deletePost,
	getOption,
	getOptions,
	restoreTemplatePart,
	setOptions,
	snapshotTemplatePart,
} from './helpers/wp-cli';

// Business settings read by the header button and the footer.
const BUSINESS = {
	jmc_business_name: 'Chrome E2E Electrical',
	jmc_business_phone: '0113 496 0000',
	jmc_business_email: 'hello@example.com',
	jmc_business_service_area: [ 'Leeds', 'Bradford' ],
};

const MENU_LINKS = [
	{ label: 'Chrome E2E Services', url: '/services/' },
	{ label: 'Chrome E2E About', url: '/about/' },
];

/**
 * The navigation's structure, for comparing the editor preview with the
 * frontend: whether it is enhanced, and each list item's classes and label.
 *
 * @param navigation The `.jmc-navigation` element.
 */
const navigationShape = ( navigation: Locator ) =>
	navigation.evaluate( ( element ) => ( {
		enhanced: ! element.classList.contains( 'no-js' ),
		items: Array.from(
			element.querySelectorAll( ':scope > .jmc-navigation__list > li' ),
			( item ) => ( {
				classes: Array.from( item.classList )
					.filter( ( name ) => name.startsWith( 'jmc-navigation__' ) )
					.sort(),
				label:
					item
						.querySelector(
							':scope > .jmc-navigation__link, :scope > .jmc-navigation__label'
						)
						?.textContent?.trim() ?? '',
			} )
		),
	} ) );

test.describe( 'header and footer workflows', () => {
	let theme: string;
	let navigationId: number | undefined;
	let options: Record< string, unknown > | undefined;

	test.beforeAll( async () => {
		theme = await getOption( 'stylesheet' );
		options = await getOptions( Object.keys( BUSINESS ) );
		await setOptions( BUSINESS );
		navigationId = await createNavigationRecord( MENU_LINKS );
	} );

	test.afterAll( async () => {
		if ( navigationId ) {
			await deletePost( navigationId );
		}

		if ( options ) {
			await setOptions( options );
		}
	} );

	test.beforeEach( async ( { page } ) => {
		await loginToWordPress( page );
	} );

	test.describe( 'header', () => {
		let part: Awaited< ReturnType< typeof snapshotTemplatePart > >;

		test.beforeEach( async () => {
			part = await snapshotTemplatePart( 'header' );
		} );

		test.afterEach( async () => {
			await restoreTemplatePart( 'header', part );
		} );

		test( 'chooses a menu and call to action in the Site Editor', async ( {
			admin,
			editor,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `${ theme }//header`,
				postType: 'wp_template_part',
				canvas: 'edit',
			} );

			const settings = await openBlockSettings( page, 'jmc/navigation' );

			await settings
				.getByRole( 'combobox', { name: 'Navigation', exact: false } )
				.selectOption( String( navigationId ) );

			// Authors switch the button to the business phone with a block
			// variation, then type its label on the canvas.
			await updateBlockAttributes( page, 'jmc/header-cta', {
				linkType: 'phone',
			} );

			// The phone button replaces the link button with a label field
			// named by its placeholder; type into that one.
			const label = editor.canvas.getByRole( 'textbox', {
				name: 'Call us',
			} );

			await label.fill( 'Call Chrome E2E' );
			await expect( label ).toHaveText( 'Call Chrome E2E' );

			// The editor previews the menu as the frontend renders it, with
			// the call to action as the last list item.
			const preview = editor.canvas.locator( 'nav.jmc-navigation' );

			await expect(
				preview.locator( '.jmc-navigation__link' ).first()
			).toHaveText( MENU_LINKS[ 0 ].label );

			const editorShape = await navigationShape( preview );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.reload();

			const saved = await getBlockByName( page, 'jmc/header-cta' );

			expect( saved.attributes ).toMatchObject( {
				linkType: 'phone',
				text: 'Call Chrome E2E',
			} );
			await expectNoInvalidBlocks( page );

			await page.goto( '/' );

			const header = page.getByRole( 'banner' );
			const menu = header.getByRole( 'navigation', {
				name: 'Navigation E2E',
			} );

			for ( const link of MENU_LINKS ) {
				await expect(
					menu.getByRole( 'link', { name: link.label } )
				).toHaveAttribute( 'href', link.url );
			}

			await expect(
				menu.getByRole( 'link', { name: 'Call Chrome E2E' } )
			).toHaveAttribute( 'href', /^tel:/ );

			await expect( menu ).not.toHaveClass( /no-js/ );
			expect( await navigationShape( menu ) ).toEqual( editorShape );
			expect( editorShape.items.at( -1 )?.classes ).toEqual( [
				'jmc-navigation__item',
				'jmc-navigation__item--cta',
			] );
		} );

		test( 'saves the chosen menu as a number', async ( {
			admin,
			editor,
			page,
		} ) => {
			// Known bug: the Navigation block saves the menu ID from its select
			// as a string, so the editor drops it on reload (the attribute is a
			// number) and picks the first menu again. The frontend still renders
			// the chosen menu. Remove test.fail() once the block saves a number.
			test.fail();

			await admin.visitSiteEditor( {
				postId: `${ theme }//header`,
				postType: 'wp_template_part',
				canvas: 'edit',
			} );

			const settings = await openBlockSettings( page, 'jmc/navigation' );

			await settings
				.getByRole( 'combobox', { name: 'Navigation', exact: false } )
				.selectOption( String( navigationId ) );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			const saved = await snapshotTemplatePart( 'header' );

			expect( saved?.content ).toContain(
				`"navigationId":${ navigationId }`
			);
		} );
	} );

	test.describe( 'footer', () => {
		let part: Awaited< ReturnType< typeof snapshotTemplatePart > >;

		test.beforeEach( async () => {
			part = await snapshotTemplatePart( 'footer' );
		} );

		test.afterEach( async () => {
			await restoreTemplatePart( 'footer', part );
		} );

		test( 'shows the business details, chosen menu and legal row', async ( {
			admin,
			editor,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `${ theme }//footer`,
				postType: 'wp_template_part',
				canvas: 'edit',
			} );

			const settings = await openBlockSettings(
				page,
				'jmc/footer-navigation'
			);

			await settings
				.getByRole( 'combobox', { name: 'Navigation', exact: false } )
				.selectOption( String( navigationId ) );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.reload();

			const navigation = await getBlockByName(
				page,
				'jmc/footer-navigation'
			);

			expect( navigation.attributes.navigationId ).toBe( navigationId );
			await expectNoInvalidBlocks( page );

			await page.goto( '/' );

			const footer = page.getByRole( 'contentinfo' );

			await expect(
				footer.locator( '.jmc-palette--inverse' )
			).toBeVisible();
			await expect(
				footer.getByRole( 'link', { name: BUSINESS.jmc_business_name } )
			).toBeVisible();
			await expect(
				footer.getByRole( 'link', {
					name: BUSINESS.jmc_business_phone,
				} )
			).toHaveAttribute( 'href', /^tel:/ );
			await expect(
				footer.getByRole( 'link', {
					name: BUSINESS.jmc_business_email,
				} )
			).toHaveAttribute(
				'href',
				`mailto:${ BUSINESS.jmc_business_email }`
			);
			await expect( footer ).toContainText( 'Leeds and Bradford' );

			const menu = footer.getByRole( 'navigation', {
				name: 'Navigation E2E',
			} );

			for ( const link of MENU_LINKS ) {
				await expect(
					menu.getByRole( 'link', { name: link.label } )
				).toHaveAttribute( 'href', link.url );
			}

			await expect(
				footer.locator( '.jmc-footer-legal__copyright' )
			).toHaveText( /^© \d{4} / );
		} );
	} );
} );
