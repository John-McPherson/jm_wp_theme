import { expect, test } from './fixtures/test';
import {
	createPost,
	deletePost,
	getOption,
	updateOption,
} from './helpers/wp-cli';

const heroHeading = 'Front page E2E hero heading';
const heroButton = 'Book a callout';

const heroContent = [
	'<!-- wp:jmc/hero -->',
	'<!-- wp:jmc/paragraph {"text":"Front page E2E","variant":"label"} /-->',
	`<!-- wp:jmc/heading ${ JSON.stringify( {
		text: heroHeading,
		level: '1',
		lockLevel: true,
	} ) } /-->`,
	'<!-- wp:jmc/paragraph {"text":"Front page E2E introduction."} /-->',
	`<!-- wp:jmc/button ${ JSON.stringify( {
		text: heroButton,
		link: { url: '/contact/', opensInNewTab: false },
	} ) } /-->`,
	'<!-- /wp:jmc/hero -->',
].join( '' );

test.describe( 'front page', () => {
	let pageId: number | undefined;
	let showOnFront: string | undefined;
	let pageOnFront: string | undefined;
	let siteTitle: string;

	test.beforeAll( async () => {
		showOnFront = await getOption( 'show_on_front' );
		pageOnFront = await getOption( 'page_on_front' );
		siteTitle = await getOption( 'blogname' );

		pageId = await createPost( {
			type: 'page',
			title: 'Front page E2E',
			content: heroContent,
		} );

		await updateOption( 'show_on_front', 'page' );
		await updateOption( 'page_on_front', String( pageId ) );
	} );

	// Restore only what setup read, so a failed setup never blanks the options.
	test.afterAll( async () => {
		if ( showOnFront !== undefined && pageOnFront !== undefined ) {
			await updateOption( 'show_on_front', showOnFront );
			await updateOption( 'page_on_front', pageOnFront );
		}

		if ( pageId ) {
			await deletePost( pageId );
		}
	} );

	test( 'names the page with the hero heading instead of the title', async ( {
		page,
	} ) => {
		await page.goto( '/' );

		const main = page.getByRole( 'main' );

		await expect( main.locator( '.jmc-hero' ) ).toBeVisible();
		await expect( page.getByRole( 'heading', { level: 1 } ) ).toHaveText(
			heroHeading
		);
		await expect( main.locator( '.jmc-page-title' ) ).toHaveCount( 0 );
	} );

	test( 'links the hero button to its destination', async ( { page } ) => {
		await page.goto( '/' );

		await expect(
			page
				.locator( '.jmc-hero' )
				.getByRole( 'link', { name: heroButton } )
		).toHaveAttribute( 'href', '/contact/' );
	} );

	test( 'renders the header, main content and footer landmarks', async ( {
		page,
	} ) => {
		await page.goto( '/' );

		await expect( page.getByRole( 'banner' ) ).toBeVisible();
		await expect( page.getByRole( 'main' ) ).toHaveCount( 1 );

		const footer = page.getByRole( 'contentinfo' );

		await expect( footer ).toBeVisible();

		const copyright = footer.locator( '.jmc-footer-legal__copyright' );

		await expect( copyright ).toHaveText( /^© \d{4} / );
		await expect( copyright ).toContainText( siteTitle );
	} );

	test( 'does not create horizontal overflow at 320px', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 320, height: 800 } );
		await page.goto( '/' );

		await expect( page.locator( '.jmc-hero' ) ).toBeVisible();

		const { scrollWidth, clientWidth } = await page.evaluate( () => ( {
			scrollWidth: document.documentElement.scrollWidth,
			clientWidth: document.documentElement.clientWidth,
		} ) );

		expect( scrollWidth ).toBeLessThanOrEqual( clientWidth );
	} );
} );
