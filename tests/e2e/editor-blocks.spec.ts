import { expect, test } from './fixtures/test';
import {
	closeStarterPatterns,
	expectNoInvalidBlocks,
	getBlockByName,
	insertBlockFromInserter,
	openBlockSettings,
	saveExistingPost,
} from './helpers/editor';
import { loginToWordPress } from './helpers/login';
import { createPost, deletePost, getPermalink } from './helpers/wp-cli';

type SectionCase = {
	name: string;
	title: string;
	className: string;
	// Level of the heading the section's template seeds, if it has one.
	headingLevel?: 1 | 2;
};

// The insertable page sections authors build pages from. Trust and
// Credentials is left out: it renders nothing until a logo is chosen from
// the Media Library.
const SECTIONS: SectionCase[] = [
	{ name: 'jmc/hero', title: 'Hero', className: 'jmc-hero', headingLevel: 1 },
	{
		name: 'jmc/text-with-image',
		title: 'Text with Image',
		className: 'jmc-text-with-image',
		headingLevel: 1,
	},
	{
		name: 'jmc/cta-banner',
		title: 'CTA Banner',
		className: 'jmc-cta-banner',
		headingLevel: 2,
	},
	{
		name: 'jmc/service-grid',
		title: 'Service Grid',
		className: 'jmc-service-grid',
		headingLevel: 2,
	},
	{
		name: 'jmc/enquiry-form',
		title: 'Enquiry Form',
		className: 'jmc-enquiry-form',
	},
];

// Editor-only: phone projects skip it (see playwright.config.ts).
test.describe( 'editor block journeys', { tag: '@desktop' }, () => {
	let pageId: number | undefined;

	test.beforeEach( async ( { page } ) => {
		await loginToWordPress( page );
	} );

	test.afterEach( async () => {
		if ( pageId ) {
			await deletePost( pageId );
			pageId = undefined;
		}
	} );

	for ( const section of SECTIONS ) {
		test( `${ section.title } can be inserted, configured, saved and reloaded`, async ( {
			admin,
			editor,
			page,
		} ) => {
			const heading = `${ section.title } E2E heading`;

			pageId = await createPost( {
				type: 'page',
				title: `${ section.title } E2E`,
				content: '',
			} );

			await admin.editPost( pageId );
			await closeStarterPatterns( page );
			await insertBlockFromInserter( page, section.title );

			const block = editor.canvas.locator( `.${ section.className }` );

			await expect( block ).toBeVisible();

			if ( section.headingLevel ) {
				await block
					.locator(
						`h${ section.headingLevel }[contenteditable="true"]`
					)
					.first()
					.fill( heading );
			}

			const settings = await openBlockSettings( page, section.name );

			await settings
				.getByRole( 'combobox', { name: 'Palette', exact: false } )
				.selectOption( 'inverse' );

			await expect( block ).toHaveClass( /jmc-palette--inverse/ );

			await saveExistingPost( page );
			await page.reload();

			// The saved block comes back unchanged and valid.
			const saved = await getBlockByName( page, section.name );

			expect( saved.attributes.palette ).toBe( 'inverse' );

			if ( section.headingLevel ) {
				const savedHeading = await getBlockByName(
					page,
					'jmc/heading'
				);

				expect( savedHeading.attributes.text ).toBe( heading );
			}

			await expect(
				editor.canvas.locator(
					`.${ section.className }.jmc-palette--inverse`
				)
			).toBeVisible();
			await expectNoInvalidBlocks( page );

			// The frontend renders the saved configuration.
			await page.goto( await getPermalink( pageId ) );

			const rendered = page
				.getByRole( 'main' )
				.locator( `.${ section.className }` );

			await expect( rendered ).toHaveClass( /jmc-palette--inverse/ );

			if ( section.headingLevel ) {
				await expect(
					rendered.getByRole( 'heading', {
						level: section.headingLevel,
						name: heading,
					} )
				).toBeVisible();
			}
		} );
	}
} );
