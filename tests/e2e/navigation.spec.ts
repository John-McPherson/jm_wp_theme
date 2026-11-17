import { expect, test } from './fixtures/test';
import {
	createPost,
	deletePost,
	getPermalink,
	updatePostContent,
} from './helpers/wp-cli';

const externalLink = {
	label: 'Contact Us',
	url: 'https://example.com/contact',
};

test.describe( 'navigation links', () => {
	let pageId: number | null = null;
	let navigationId: number | null = null;
	let pageUrl = '';
	const pageTitle = 'Navigation E2E';

	test.beforeAll( async () => {
		pageId = await createPost( { type: 'page', title: pageTitle } );
		pageUrl = await getPermalink( pageId );

		const menuContent = [
			`<!-- wp:navigation-link ${ JSON.stringify( {
				label: pageTitle,
				url: pageUrl,
				id: pageId,
				kind: 'post-type',
				type: 'page',
			} ) } /-->`,
			`<!-- wp:navigation-link ${ JSON.stringify( externalLink ) } /-->`,
		].join( '\n' );

		navigationId = await createPost( {
			type: 'wp_navigation',
			title: 'E2E Menu',
			content: menuContent,
		} );

		await updatePostContent(
			pageId,
			`<!-- wp:jmc/navigation {"navigationId":${ navigationId }} /-->`
		);
	} );

	test.afterAll( async () => {
		if ( pageId !== null ) {
			await deletePost( pageId );
		}

		if ( navigationId !== null ) {
			await deletePost( navigationId );
		}
	} );

	test( 'renders each link with only its label as text', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 1280, height: 800 } );
		await page.goto( pageUrl );

		const links = page.locator( '.jmc-navigation__link' );

		await expect( links ).toHaveText( [ pageTitle, externalLink.label ] );

		const itemText = await page
			.locator( '.jmc-navigation__item' )
			.allTextContents();

		expect( itemText.map( ( text ) => text.trim() ) ).toEqual( [
			pageTitle,
			externalLink.label,
		] );
	} );

	test( 'links point at the configured URLs', async ( { page } ) => {
		await page.goto( pageUrl );

		const links = page.locator( '.jmc-navigation__link' );

		await expect( links.nth( 0 ) ).toHaveAttribute( 'href', pageUrl );
		await expect( links.nth( 1 ) ).toHaveAttribute(
			'href',
			externalLink.url
		);
	} );

	test( 'marks only the current page link with aria-current', async ( {
		page,
	} ) => {
		await page.goto( pageUrl );

		const links = page.locator( '.jmc-navigation__link' );

		await expect( links.nth( 0 ) ).toHaveAttribute(
			'aria-current',
			'page'
		);
		await expect( links.nth( 1 ) ).not.toHaveAttribute( 'aria-current' );
	} );
} );
