import type { Page } from '@playwright/test';

import { expect, test } from './fixtures/test';
import { expectNoInvalidBlocks } from './helpers/editor';
import { loginToWordPress } from './helpers/login';
import {
	createNavigationRecord,
	deletePost,
	getOption,
} from './helpers/wp-cli';

// Mirrors JMC_TEMPLATE_CORE_BLOCKS and JMC_NAVIGATION_CORE_BLOCKS in inc/helpers/blocks.php.
const TEMPLATE_CORE_BLOCKS = [
	'core/template-part',
	'core/group',
	'core/post-content',
	'core/post-title',
	'core/site-title',
];
const NAVIGATION_CORE_BLOCKS = [
	'core/navigation-link',
	'core/navigation-submenu',
];

// Never allowed: other core blocks, synced patterns and the core navigation container.
const DISALLOWED_BLOCKS = [
	'core/paragraph',
	'core/navigation',
	'core/block',
	'core/pattern',
];

/**
 * Read the block editor's allowed block types once the editor has loaded.
 *
 * @param page Editor page.
 */
async function getAllowedBlocks( page: Page ): Promise< string[] > {
	const handle = await page.waitForFunction( () => {
		const settings = (
			window as unknown as {
				wp?: {
					data?: {
						select: ( store: string ) => {
							getSettings: () => { allowedBlockTypes?: unknown };
						};
					};
				};
			}
		 ).wp?.data
			?.select( 'core/block-editor' )
			?.getSettings();

		return Array.isArray( settings?.allowedBlockTypes )
			? settings.allowedBlockTypes
			: false;
	} );

	return ( await handle.jsonValue() ) as string[];
}

/**
 * Check a Site Editor list: theme blocks plus the template and navigation core blocks.
 *
 * @param allowed Allowed block names.
 */
function expectSiteEditorBlocks( allowed: string[] ): void {
	expect( allowed ).toContain( 'jmc/hero' );
	expect( allowed ).toContain( 'jmc/navigation' );
	expect( allowed ).toEqual(
		expect.arrayContaining( [
			...TEMPLATE_CORE_BLOCKS,
			...NAVIGATION_CORE_BLOCKS,
		] )
	);

	for ( const name of DISALLOWED_BLOCKS ) {
		expect( allowed ).not.toContain( name );
	}

	for ( const name of allowed ) {
		expect(
			name.startsWith( 'jmc/' ) ||
				TEMPLATE_CORE_BLOCKS.includes( name ) ||
				NAVIGATION_CORE_BLOCKS.includes( name )
		).toBe( true );
	}
}

test.describe( 'block allow-list', () => {
	let theme: string;

	test.beforeAll( async () => {
		theme = await getOption( 'stylesheet' );
	} );

	test.beforeEach( async ( { page } ) => {
		await loginToWordPress( page );
	} );

	test( 'page editor offers only theme blocks', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=page' );

		const allowed = await getAllowedBlocks( page );

		expect( allowed ).toContain( 'jmc/hero' );
		expect( allowed ).toContain( 'jmc/text-with-image' );

		for ( const name of allowed ) {
			expect( name ).toMatch( /^jmc\// );
		}

		const canInsert = await page.evaluate( () => {
			const editor = (
				window as unknown as {
					wp: {
						data: {
							select: ( store: string ) => {
								canInsertBlockType: (
									name: string,
									rootClientId?: string
								) => boolean;
							};
						};
					};
				}
			 ).wp.data.select( 'core/block-editor' );

			return {
				hero: editor.canInsertBlockType( 'jmc/hero' ),
				paragraph: editor.canInsertBlockType( 'core/paragraph' ),
				group: editor.canInsertBlockType( 'core/group' ),
			};
		} );

		expect( canInsert ).toEqual( {
			hero: true,
			paragraph: false,
			group: false,
		} );
	} );

	test( 'Site Editor templates offer theme and template blocks', async ( {
		page,
	} ) => {
		await page.goto(
			`/wp-admin/site-editor.php?p=${ encodeURIComponent(
				`/wp_template/${ theme }//page`
			) }&canvas=edit`
		);

		expectSiteEditorBlocks( await getAllowedBlocks( page ) );
		await expectNoInvalidBlocks( page );
	} );

	test( 'header template part offers theme and template blocks', async ( {
		page,
	} ) => {
		await page.goto(
			`/wp-admin/site-editor.php?p=${ encodeURIComponent(
				`/wp_template_part/${ theme }//header`
			) }&canvas=edit`
		);

		expectSiteEditorBlocks( await getAllowedBlocks( page ) );
		await expectNoInvalidBlocks( page );
	} );

	test.describe( 'navigation menus', () => {
		let navigationId: number;

		test.beforeEach( async () => {
			navigationId = await createNavigationRecord( [
				{ label: 'Home', url: '/' },
			] );
		} );

		test.afterEach( async () => {
			await deletePost( navigationId );
		} );

		test( 'offer the menu link blocks', async ( { page } ) => {
			await page.goto(
				`/wp-admin/site-editor.php?p=${ encodeURIComponent(
					`/wp_navigation/${ navigationId }`
				) }&canvas=edit`
			);

			expectSiteEditorBlocks( await getAllowedBlocks( page ) );
			await expectNoInvalidBlocks( page );
		} );
	} );
} );
