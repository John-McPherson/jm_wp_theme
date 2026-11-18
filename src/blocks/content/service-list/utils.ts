export type ListSource = 'children' | 'chosen';

const positiveInteger = ( value: unknown ): number =>
	typeof value === 'number' && Number.isInteger( value ) && value > 0
		? value
		: 0;

/**
 * The page whose children the list shows, mirroring the server: the chosen
 * parent, else the page being edited, else the Services page.
 *
 * @param args                Resolution inputs.
 * @param args.parentId       Parent chosen on the block; 0 when unset.
 * @param args.postId         Edited post ID from block context.
 * @param args.postType       Edited post type from block context.
 * @param args.servicesPageId Resolved Services page ID; 0 when unresolved.
 *
 * @return The parent page ID, or 0 when there is none.
 */
export const listParentId = ( {
	parentId,
	postId,
	postType,
	servicesPageId,
}: {
	parentId: unknown;
	postId: unknown;
	postType: unknown;
	servicesPageId: unknown;
} ): number => {
	const chosen = positiveInteger( parentId );

	if ( chosen > 0 ) {
		return chosen;
	}

	const current = postType === 'page' ? positiveInteger( postId ) : 0;

	return current > 0 ? current : positiveInteger( servicesPageId );
};

/**
 * Whether the list must use chosen pages because its parent has no published
 * children. Unknown while the children are still loading.
 *
 * @param parentId   Resolved parent page ID; 0 when there is none.
 * @param childCount Published children found, or undefined while loading.
 *
 * @return True when locked to chosen pages, false when children exist, and
 *         undefined while loading.
 */
export const isLockedToChosen = (
	parentId: number,
	childCount: number | undefined
): boolean | undefined => {
	if ( parentId <= 0 ) {
		return true;
	}

	return undefined === childCount ? undefined : childCount === 0;
};

/**
 * Normalise stored chosen page IDs for the editor, mirroring the server.
 *
 * @param value Stored page IDs.
 *
 * @return Positive integer IDs, once each, in order.
 */
export const chosenPageIds = ( value: unknown ): number[] =>
	Array.isArray( value )
		? value
				.map( positiveInteger )
				.filter(
					( id, index, ids ) => id > 0 && ids.indexOf( id ) === index
				)
		: [];
