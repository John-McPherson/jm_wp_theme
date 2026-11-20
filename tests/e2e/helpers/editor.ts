import type { Page } from '@playwright/test';

import { expect } from '../fixtures/test';

type EditorBlock = {
	clientId: string;
	name: string;
	attributes: Record< string, unknown >;
	innerBlocks: EditorBlock[];
};

type BlockEditorWindow = {
	wp: {
		data: {
			select: ( store: string ) => {
				getBlocks: () => EditorBlock[];
				getBlocksByName: ( name: string ) => string[];
			};
			dispatch: ( store: string ) => {
				selectBlock: ( clientId: string ) => void;
				updateBlockAttributes: (
					clientId: string,
					attributes: Record< string, unknown >
				) => void;
			};
		};
	};
};

/**
 * Fail if the editor shows block recovery UI or an unsupported-block warning.
 *
 * @param page Editor page.
 */
export async function expectNoInvalidBlocks( page: Page ): Promise< void > {
	const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );

	await expect(
		canvas.getByText( /this block contains unexpected or invalid content/i )
	).toHaveCount( 0 );
	await expect(
		page.getByText( /your site doesn.t include support for/i )
	).toHaveCount( 0 );
}

/**
 * Close the starter-pattern chooser the editor offers on empty pages, so a
 * test can build the page block by block.
 *
 * @param page Editor page.
 */
export async function closeStarterPatterns( page: Page ): Promise< void > {
	const chooser = page.getByRole( 'dialog', { name: 'Choose a pattern' } );

	try {
		await chooser.waitFor( { timeout: 5000 } );
	} catch {
		return;
	}

	await chooser.getByRole( 'button', { name: 'Close' } ).click();
	await expect( chooser ).toBeHidden();
}

/**
 * Insert a block the way an author does: search the inserter and pick it.
 *
 * @param page  Editor page.
 * @param title Block title shown in the inserter.
 */
export async function insertBlockFromInserter(
	page: Page,
	title: string
): Promise< void > {
	await page
		.getByRole( 'region', { name: 'Editor top bar' } )
		.getByRole( 'button', {
			name: /^(Block Inserter|Toggle block inserter|Add block)$/,
		} )
		.click();

	const inserter = page.getByRole( 'region', { name: 'Block Library' } );

	await inserter.getByRole( 'searchbox' ).fill( title );
	// Search results list patterns and block variations too, so pick the
	// block whose label is exactly the title.
	const label = new RegExp(
		`^${ title.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) }$`
	);

	await inserter
		.getByRole( 'listbox', { name: 'Blocks', exact: true } )
		.getByRole( 'option' )
		.filter( { hasText: label } )
		.click();
}

/**
 * Find the first block with a name in the editor, at any depth.
 *
 * @param page Editor page.
 * @param name Block name, such as `jmc/hero`.
 */
export async function getBlockByName(
	page: Page,
	name: string
): Promise< EditorBlock > {
	const handle = await page.waitForFunction( ( blockName ) => {
		const find = ( blocks: EditorBlock[] ): EditorBlock | null => {
			for ( const block of blocks ) {
				if ( block.name === blockName ) {
					return block;
				}

				const inner = find( block.innerBlocks );

				if ( inner ) {
					return inner;
				}
			}

			return null;
		};

		return (
			find(
				( window as unknown as BlockEditorWindow ).wp?.data
					?.select( 'core/block-editor' )
					?.getBlocks() ?? []
			) ?? false
		);
	}, name );

	return ( await handle.jsonValue() ) as EditorBlock;
}

/**
 * Select a block and show its settings in the editor sidebar.
 *
 * @param page Editor page.
 * @param name Block name, such as `jmc/navigation`.
 * @return The settings sidebar region.
 */
export async function openBlockSettings( page: Page, name: string ) {
	const { clientId } = await getBlockByName( page, name );

	await page.evaluate( ( id ) => {
		( window as unknown as BlockEditorWindow ).wp.data
			.dispatch( 'core/block-editor' )
			.selectBlock( id );
	}, clientId );

	const settings = page.getByRole( 'region', { name: 'Editor settings' } );

	if ( ! ( await settings.isVisible() ) ) {
		await page
			.getByRole( 'region', { name: 'Editor top bar' } )
			.getByRole( 'button', { name: 'Settings', exact: true } )
			.click();
	}

	const blockTab = settings.getByRole( 'tab', { name: 'Block' } );

	if ( await blockTab.isVisible() ) {
		await blockTab.click();
	}

	return settings;
}

/**
 * Set block attributes the editor has no simple control for, such as a
 * button's link type, which authors change with a block variation.
 *
 * @param page       Editor page.
 * @param name       Block name.
 * @param attributes Attributes to merge.
 */
export async function updateBlockAttributes(
	page: Page,
	name: string,
	attributes: Record< string, unknown >
): Promise< void > {
	const { clientId } = await getBlockByName( page, name );

	await page.evaluate(
		( [ id, next ] ) => {
			( window as unknown as BlockEditorWindow ).wp.data
				.dispatch( 'core/block-editor' )
				.updateBlockAttributes( id as string, next as never );
		},
		[ clientId, attributes ] as const
	);
}

/**
 * Save an already published post or page from the post editor.
 *
 * @param page Editor page.
 */
export async function saveExistingPost( page: Page ): Promise< void > {
	await page
		.getByRole( 'region', { name: 'Editor top bar' } )
		.getByRole( 'button', { name: 'Save', exact: true } )
		.click();

	await expect(
		page
			.getByRole( 'button', { name: 'Dismiss this notice' } )
			.filter( { hasText: /updated/i } )
	).toBeVisible();
}
