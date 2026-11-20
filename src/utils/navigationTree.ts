export type NavigationLink = {
	id: number;
	title: string;
	url: string;
};

export type NavigationItem = NavigationLink & {
	children: NavigationLink[];
};

const MENU_BLOCKS = [ 'core/navigation-link', 'core/navigation-submenu' ];

type MenuBlock = {
	blockName?: unknown;
	attrs?: unknown;
	innerBlocks?: unknown;
};

const asBlocks = ( value: unknown ): MenuBlock[] =>
	Array.isArray( value )
		? value.filter(
				( block ): block is MenuBlock =>
					typeof block === 'object' && block !== null
			)
		: [];

const attributesOf = ( block: MenuBlock ): Record< string, unknown > =>
	typeof block.attrs === 'object' && block.attrs !== null
		? ( block.attrs as Record< string, unknown > )
		: {};

const idOf = ( attributes: Record< string, unknown > ): number => {
	const id = Number( attributes.id );

	return Number.isInteger( id ) && id > 0 ? id : 0;
};

/**
 * Normalise one link or submenu block, like `jmc_get_navigation_item()`.
 *
 * @param block Parsed block.
 *
 * @return The item, or null when the block is unsupported or has no label.
 */
function toItem( block: MenuBlock ): NavigationLink | null {
	if (
		typeof block.blockName !== 'string' ||
		! MENU_BLOCKS.includes( block.blockName )
	) {
		return null;
	}

	const attributes = attributesOf( block );
	const { label, url } = attributes;

	if ( typeof label !== 'string' || label.trim() === '' ) {
		return null;
	}

	return {
		id: idOf( attributes ),
		title: label.trim(),
		url: typeof url === 'string' ? url.trim() : '',
	};
}

/**
 * Flatten every link in a branch, like `get_navigation_links()`.
 *
 * @param blocks Parsed blocks.
 *
 * @return Links with a label and URL, in menu order.
 */
function toLinks( blocks: unknown ): NavigationLink[] {
	return asBlocks( blocks ).flatMap( ( block ) => {
		const item = toItem( block );

		return [
			...( item && item.url !== '' ? [ item ] : [] ),
			...toLinks( block.innerBlocks ),
		];
	} );
}

/**
 * Collect a submenu's links, flattening deeper branches, like
 * `jmc_get_navigation_children()`.
 *
 * @param blocks The submenu's inner blocks.
 *
 * @return Submenu links.
 */
function toChildren( blocks: unknown ): NavigationLink[] {
	return asBlocks( blocks ).flatMap( ( block ) => {
		const item = toItem( block );

		if ( ! item ) {
			return [];
		}

		return [
			...( item.url !== '' ? [ item ] : [] ),
			...toLinks( block.innerBlocks ),
		];
	} );
}

/**
 * Normalise a parsed `wp_navigation` menu into primary items with one
 * optional submenu level.
 *
 * Mirrors `jmc_get_navigation_tree()` (`inc/helpers/navigation.php`) so the
 * editor preview lists the same items as the frontend. Takes the output of
 * `parse()` from `@wordpress/block-serialization-default-parser`, which has
 * the same shape as PHP's `parse_blocks()`. Keep the two in step.
 *
 * @param blocks Parsed menu blocks.
 *
 * @return Navigation items in menu order.
 */
export default function navigationTree( blocks: unknown ): NavigationItem[] {
	return asBlocks( blocks ).flatMap( ( block ) => {
		const item = toItem( block );

		if ( ! item ) {
			return [];
		}

		const children = toChildren( block.innerBlocks );

		if ( item.url === '' && children.length === 0 ) {
			return [];
		}

		return [ { ...item, children } ];
	} );
}
