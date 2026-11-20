import type { Page } from '@playwright/test';

import { expect, test } from './fixtures/test';
import {
	createPost,
	deletePost,
	getPermalink,
	restoreOptions,
	setOptions,
	snapshotOptions,
} from './helpers/wp-cli';

/**
 * Block markup for a hero with a level-one heading.
 *
 * @param heading Hero heading text.
 */
function heroMarkup( heading: string ): string {
	return [
		'<!-- wp:jmc/hero -->',
		`<!-- wp:jmc/heading ${ JSON.stringify( {
			text: heading,
			level: '1',
			lockLevel: true,
		} ) } /-->`,
		'<!-- /wp:jmc/hero -->',
	].join( '' );
}

/**
 * Check the shared page frame: header, one main landmark and the footer.
 *
 * @param page Frontend page.
 */
async function expectPageFrame( page: Page ): Promise< void > {
	await expect( page.locator( 'header.jmc-header' ) ).toBeVisible();
	await expect( page.getByRole( 'main' ) ).toHaveCount( 1 );
	await expect( page.getByRole( 'contentinfo' ) ).toBeVisible();
	await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveCount( 1 );
}

test.describe( 'primary templates', () => {
	const created: number[] = [];

	/**
	 * Create a published page and delete it after the suite.
	 *
	 * @param fields Post fields for createPost().
	 */
	async function createPage(
		fields: Omit< Parameters< typeof createPost >[ 0 ], 'type' >
	): Promise< number > {
		const id = await createPost( { type: 'page', ...fields } );

		created.push( id );

		return id;
	}

	test.afterAll( async () => {
		// Children first, so no page is left with a deleted parent.
		for ( const id of created.reverse() ) {
			await deletePost( id );
		}
	} );

	test( 'standard page shows its title and content', async ( { page } ) => {
		const id = await createPage( {
			title: 'Standard E2E page',
			content: `<!-- wp:jmc/text-with-image {"palette":"secondary"} -->${ [
				'<!-- wp:jmc/heading {"text":"Standard E2E section","level":"2"} /-->',
				'<!-- wp:jmc/paragraph {"text":"Standard E2E body copy."} /-->',
			].join( '' ) }<!-- /wp:jmc/text-with-image -->`,
		} );

		await page.goto( await getPermalink( id ) );

		const main = page.getByRole( 'main' );

		await expectPageFrame( page );
		await expect( main.getByRole( 'heading', { level: 1 } ) ).toHaveText(
			'Standard E2E page'
		);
		await expect( main.locator( '.jmc-page-title' ) ).toBeVisible();
		await expect(
			main.getByRole( 'heading', {
				level: 2,
				name: 'Standard E2E section',
			} )
		).toBeVisible();
		await expect( main ).toContainText( 'Standard E2E body copy.' );
	} );

	test.describe( 'services', () => {
		let landingId: number;
		let serviceId: number;
		let options: Record< string, unknown > | undefined;

		test.beforeAll( async () => {
			options = await snapshotOptions( [ 'jmc_services_page_id' ] );

			landingId = await createPage( {
				title: 'Services E2E',
				content: [
					heroMarkup( 'Services E2E heading' ),
					'<!-- wp:jmc/service-grid -->',
					'<!-- wp:jmc/column --><!-- wp:jmc/heading {"text":"Our services","level":"2"} /--><!-- /wp:jmc/column -->',
					'<!-- wp:jmc/column --><!-- wp:jmc/service-list /--><!-- /wp:jmc/column -->',
					'<!-- /wp:jmc/service-grid -->',
				].join( '' ),
			} );

			serviceId = await createPage( {
				title: 'Rewiring E2E',
				parent: landingId,
				excerpt: 'Rewiring E2E summary.',
				content: heroMarkup( 'Rewiring E2E heading' ),
			} );

			await setOptions( { jmc_services_page_id: landingId } );
		} );

		test.afterAll( async () => {
			if ( options ) {
				await restoreOptions( options );
			}
		} );

		test( 'landing page lists each service as a linked card', async ( {
			page,
		} ) => {
			await page.goto( await getPermalink( landingId ) );

			const main = page.locator( 'main.jmc-main--services-landing' );

			await expect( main ).toBeVisible();
			await expectPageFrame( page );
			await expect(
				main.getByRole( 'heading', { level: 1 } )
			).toHaveText( 'Services E2E heading' );

			const card = main
				.locator( '.jmc-service-list' )
				.getByRole( 'listitem' )
				.filter( { hasText: 'Rewiring E2E' } );

			await expect(
				card.getByRole( 'link', { name: 'Rewiring E2E' } )
			).toHaveAttribute( 'href', await getPermalink( serviceId ) );
			await expect( card ).toContainText( 'Rewiring E2E summary.' );
		} );

		test( 'service page uses the service template with breadcrumbs', async ( {
			page,
		} ) => {
			await page.goto( await getPermalink( serviceId ) );

			const main = page.locator( 'main.jmc-main--service' );

			await expect( main ).toBeVisible();
			await expectPageFrame( page );
			await expect(
				main.getByRole( 'heading', { level: 1 } )
			).toHaveText( 'Rewiring E2E heading' );
			await expect(
				main
					.locator( '.jmc-hero__breadcrumbs' )
					.getByRole( 'link', { name: 'Services E2E' } )
			).toHaveAttribute( 'href', await getPermalink( landingId ) );
		} );
	} );

	test( 'contact page uses the contact template with the enquiry form', async ( {
		page,
	} ) => {
		const id = await createPage( {
			title: 'Contact E2E',
			template: 'page-contact',
			content: `${ heroMarkup(
				'Contact E2E heading'
			) }<!-- wp:jmc/enquiry-form /-->`,
		} );

		await page.goto( await getPermalink( id ) );

		const main = page.locator( 'main.jmc-main--contact' );

		await expect( main ).toBeVisible();
		await expectPageFrame( page );
		await expect( main.getByRole( 'heading', { level: 1 } ) ).toHaveText(
			'Contact E2E heading'
		);

		const form = main.locator( 'form.jmc-enquiry-form__form' );

		await expect( form ).toBeVisible();
		await expect(
			form.getByRole( 'button', { name: /send|submit/i } )
		).toBeVisible();
	} );

	test( 'a missing page answers 404 with the not-found template', async ( {
		page,
	} ) => {
		const response = await page.goto( '/e2e-page-that-does-not-exist/' );

		expect( response?.status() ).toBe( 404 );
		await expectNotFound( page );
	} );

	test( 'a search answers 404 with the not-found template', async ( {
		page,
	} ) => {
		const response = await page.goto( '/?s=electrician' );

		expect( response?.status() ).toBe( 404 );
		await expectNotFound( page );
	} );
} );

/**
 * Check the theme's 404 page: its heading, homepage link and call to action.
 *
 * @param page Frontend page.
 */
async function expectNotFound( page: Page ): Promise< void > {
	const main = page.locator( 'main.jmc-not-found' );

	await expect( main ).toBeVisible();
	await expectPageFrame( page );
	await expect( main.getByRole( 'heading', { level: 1 } ) ).toHaveText(
		'Sorry, we can’t find that page'
	);
	await expect(
		main.getByRole( 'link', { name: 'Go to the homepage' } )
	).toHaveAttribute( 'href', '/' );
	await expect(
		main.getByRole( 'link', { name: 'View our services' } )
	).toHaveAttribute( 'href', '/services/' );
}
